<?php

namespace App\Services;

/**
 * Sends email via Microsoft Graph API using OAuth2 client credentials.
 *
 * Required Azure app permissions (Application type):
 *   - Mail.Send
 *
 * No user interaction or refresh tokens needed.
 */
class GraphMailService
{
    private const TOKEN_URL   = 'https://login.microsoftonline.com/%s/oauth2/v2.0/token';
    private const SEND_URL    = 'https://graph.microsoft.com/v1.0/users/%s/sendMail';
    private const GRAPH_SCOPE = 'https://graph.microsoft.com/.default';

    private string $tenantId;
    private string $clientId;
    private string $clientSecret;
    private string $fromEmail;
    private string $fromName;
    private ?string $replyTo;

    /** Cached access token */
    private ?string $accessToken  = null;
    private int     $tokenExpires = 0;

    public function __construct(array $cfg)
    {
        $this->tenantId     = $cfg['oauth']['tenant_id'];
        $this->clientId     = $cfg['oauth']['client_id'];
        $this->clientSecret = $cfg['oauth']['client_secret'];
        $this->fromEmail    = $cfg['smtp']['username'];   // mailbox to send from
        $this->fromName     = $cfg['from_name'];
        $this->replyTo      = $cfg['reply_to'] ?? null;
    }

    /**
     * Send an email via Microsoft Graph.
     *
     * @param string      $to          Recipient email address
     * @param string      $toName      Recipient display name
     * @param string      $subject     Email subject
     * @param string      $htmlBody    HTML message body
     * @param string|null $attachPath  Full path to attachment file (PDF/xlsx)
     * @param string|null $attachName  Filename shown to recipient
     *
     * @throws \RuntimeException on auth or send failure
     */
    public function send(
        string  $to,
        string  $toName,
        string  $subject,
        string  $htmlBody,
        ?string $attachPath = null,
        ?string $attachName = null
    ): void {
        $token   = $this->getAccessToken();
        $payload = $this->buildPayload($to, $toName, $subject, $htmlBody, $attachPath, $attachName);
        $url     = sprintf(self::SEND_URL, urlencode($this->fromEmail));

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $token,
                'Content-Type: application/json',
            ],
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr  = curl_error($ch);
        curl_close($ch);

        if ($curlErr) {
            throw new \RuntimeException('Graph API network error: ' . $curlErr);
        }

        // 202 Accepted = success for sendMail
        if ($httpCode !== 202) {
            $detail = $this->extractGraphError($response);
            error_log('Graph sendMail failed (' . $httpCode . '): ' . $response);
            throw new \RuntimeException('Graph sendMail failed (' . $httpCode . '): ' . $detail);
        }
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    private function getAccessToken(): string
    {
        if ($this->accessToken && time() < $this->tokenExpires - 60) {
            return $this->accessToken;
        }

        $url = sprintf(self::TOKEN_URL, urlencode($this->tenantId));

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query([
                'grant_type'    => 'client_credentials',
                'client_id'     => $this->clientId,
                'client_secret' => $this->clientSecret,
                'scope'         => self::GRAPH_SCOPE,
            ]),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
        ]);

        $response = curl_exec($ch);
        $curlErr  = curl_error($ch);
        curl_close($ch);

        if ($curlErr) {
            throw new \RuntimeException('OAuth token request failed: ' . $curlErr);
        }

        $data = json_decode($response, true);

        if (empty($data['access_token'])) {
            $error = $data['error_description'] ?? $data['error'] ?? $response;
            throw new \RuntimeException('Failed to obtain OAuth token: ' . $error);
        }

        $this->accessToken  = $data['access_token'];
        $this->tokenExpires = time() + (int)($data['expires_in'] ?? 3600);

        return $this->accessToken;
    }

    private function buildPayload(
        string  $to,
        string  $toName,
        string  $subject,
        string  $htmlBody,
        ?string $attachPath,
        ?string $attachName
    ): array {
        $message = [
            'subject' => $subject,
            'from'    => [
                'emailAddress' => [
                    'address' => $this->fromEmail,
                    'name'    => $this->fromName,
                ],
            ],
            'toRecipients' => [
                [
                    'emailAddress' => [
                        'address' => $to,
                        'name'    => $toName,
                    ],
                ],
            ],
            'body' => [
                'contentType' => 'HTML',
                'content'     => $htmlBody,
            ],
        ];

        if ($this->replyTo) {
            $message['replyTo'] = [
                ['emailAddress' => ['address' => $this->replyTo]],
            ];
        }

        if ($attachPath && file_exists($attachPath)) {
            $name    = $attachName ?? basename($attachPath);
            $content = base64_encode(file_get_contents($attachPath));
            $mime    = mime_content_type($attachPath) ?: 'application/octet-stream';

            $message['attachments'] = [
                [
                    '@odata.type'  => '#microsoft.graph.fileAttachment',
                    'name'         => $name,
                    'contentType'  => $mime,
                    'contentBytes' => $content,
                ],
            ];
        }

        return ['message' => $message, 'saveToSentItems' => false];
    }

    private function extractGraphError(?string $response): string
    {
        if (!$response) {
            return 'empty response';
        }
        $data = json_decode($response, true);
        return $data['error']['message'] ?? $response;
    }
}
