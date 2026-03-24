<?php

namespace App\Services;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class MailService
{
    private PHPMailer $mailer;
    private array $cfg;

    public function __construct()
    {
        $this->cfg    = require ROOT_PATH . '/config/mail.php';
        $this->mailer = new PHPMailer(true);
        $this->configure();
    }

    private function configure(): void
    {
        $smtp = $this->cfg['smtp'];

        $this->mailer->isSMTP();
        $this->mailer->Host       = $smtp['host'];
        $this->mailer->Port       = (int)$smtp['port'];
        $this->mailer->SMTPAuth   = $smtp['auth'] ?? true;
        $this->mailer->Username   = $smtp['username'];
        $this->mailer->Password   = $smtp['password'];
        $this->mailer->SMTPSecure = match ($smtp['encryption'] ?? 'tls') {
            'ssl'  => PHPMailer::ENCRYPTION_SMTPS,
            'tls'  => PHPMailer::ENCRYPTION_STARTTLS,
            default => ''
        };
        $this->mailer->Timeout    = (int)($this->cfg['timeout'] ?? 10);
        $this->mailer->CharSet    = PHPMailer::CHARSET_UTF8;

        $this->mailer->setFrom($this->cfg['from_address'], $this->cfg['from_name']);

        if (!empty($this->cfg['reply_to'])) {
            $this->mailer->addReplyTo($this->cfg['reply_to']);
        }
    }

    /**
     * Send permissions report via email.
     *
     * @param string      $to          Recipient email
     * @param string      $toName      Recipient name
     * @param string      $subject     Email subject
     * @param string      $htmlBody    HTML body
     * @param string|null $attachPath  Path to attachment file (PDF/xlsx)
     * @param string|null $attachName  Attachment filename
     */
    public function send(
        string  $to,
        string  $toName,
        string  $subject,
        string  $htmlBody,
        ?string $attachPath = null,
        ?string $attachName = null
    ): void {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->clearAttachments();

            $this->mailer->addAddress($to, $toName);
            $this->mailer->isHTML(true);
            $this->mailer->Subject = $subject;
            $this->mailer->Body    = $htmlBody;
            $this->mailer->AltBody = strip_tags($htmlBody);

            if ($attachPath && file_exists($attachPath)) {
                $this->mailer->addAttachment($attachPath, $attachName ?? basename($attachPath));
            }

            $this->mailer->send();
        } catch (Exception $e) {
            error_log('Mail error: ' . $this->mailer->ErrorInfo);
            throw new \RuntimeException('Αποτυχία αποστολής email: ' . $this->mailer->ErrorInfo);
        }
    }

    /**
     * Generate permissions report as a temp file and return its path.
     * Caller is responsible for unlinking the file.
     */
    public function generateTempReport(array $filters, string $format): string
    {
        $exportService = new ExportService();
        $tmpFile = sys_get_temp_dir() . '/perm_report_' . uniqid() . '.' . $format;

        if ($format === 'pdf') {
            ob_start();
            // Capture PDF output
            $filters['_to_string'] = true;
            $exportService->exportPdfToFile($filters, $tmpFile);
        } elseif ($format === 'xlsx') {
            $exportService->exportExcelToFile($filters, $tmpFile);
        }

        return $tmpFile;
    }
}
