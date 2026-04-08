<?php

namespace App\Controllers;

use App\Auth\Middleware;
use App\Core\{View, Session, Csrf};
use App\Models\{User, Permission};
use App\Services\{ExportService, MailService};

class EmailController
{
    /** POST /email/send */
    public function send(): void
    {
        Middleware::requireLogin();
        Csrf::check();

        $scope      = $_POST['scope']      ?? 'user';   // 'user' | 'department'
        $format     = $_POST['format']     ?? 'pdf';    // 'pdf' | 'xlsx'
        $userId     = (int)($_POST['user_id']     ?? 0);
        $department = trim($_POST['department']   ?? '');
        $recipient  = trim($_POST['recipient_email'] ?? '');

        // Managers can only send their own department
        if (Session::isManager()) {
            $scope      = 'department';
            $department = Session::department();
        }

        // Build filters
        $filters = [];
        $title   = 'Αναφορά Δικαιωμάτων';
        $toName  = 'Παραλήπτης';
        $toEmail = $recipient;

        if ($scope === 'user' && $userId > 0) {
            $user = (new User())->findById($userId);
            if (!$user) {
                View::json(['success' => false, 'message' => 'Χρήστης δεν βρέθηκε.']);
            }
            $filters['user_id'] = $userId;
            $title   = 'Δικαιώματα: ' . ($user['full_name'] ?? $user['username']);
            $toName  = $user['full_name'] ?? $user['username'];
            $toEmail = $toEmail ?: $user['email'];
        } elseif ($scope === 'department' && $department) {
            $filters['department'] = $department;
            $title   = 'Δικαιώματα Τμήματος: ' . $department;
        }

        if (empty($toEmail)) {
            View::json(['success' => false, 'message' => 'Δεν βρέθηκε email παραλήπτη.']);
        }

        try {
            $tmpFile = $this->generateReport($filters, $format, $title);

            $body = $this->buildEmailBody($title, $filters);
            $ext  = $format === 'xlsx' ? 'xlsx' : 'pdf';
            $name = 'permissions_report_' . date('Ymd') . '.' . $ext;

            (new MailService())->send($toEmail, $toName, $title, $body, $tmpFile, $name);

            if (file_exists($tmpFile)) {
                unlink($tmpFile);
            }

            View::json(['success' => true, 'message' => 'Το email στάλθηκε στο ' . $toEmail]);
        } catch (\Throwable $e) {
            error_log('Email send error: ' . $e->getMessage());
            View::json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    private function generateReport(array $filters, string $format, string $title): string
    {
        $tmpFile = sys_get_temp_dir() . '/perm_report_' . uniqid() . '.' . ($format === 'xlsx' ? 'xlsx' : 'pdf');
        $export  = new ExportService();

        if ($format === 'xlsx') {
            $export->exportExcelToFile($filters, $tmpFile);
        } else {
            $export->exportPdfToFile($filters, $tmpFile, $title);
        }

        return $tmpFile;
    }

    private function buildEmailBody(string $title, array $filters): string
    {
        $appName = \App\Core\Config::get('app_name', 'Μητρώο Δικαιωμάτων');
        $date    = date('d/m/Y H:i');
        $sender  = Session::get('full_name', 'Σύστημα');

        return "
        <html><body style='font-family:Arial,sans-serif;color:#333'>
        <h2 style='color:#0d6efd'>{$title}</h2>
        <p>Σας αποστέλλεται η αναφορά δικαιωμάτων πρόσβασης.</p>
        <p><strong>Ημερομηνία:</strong> {$date}<br>
           <strong>Αποστολέας:</strong> {$sender}</p>
        <p>Η αναφορά επισυνάπτεται στο παρόν email.</p>
        <hr>
        <small style='color:#888'>{$appName}</small>
        </body></html>";
    }
}
