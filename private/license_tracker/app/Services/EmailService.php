<?php

declare(strict_types=1);

namespace App\Services;

use App\Database\Connection;
use PHPMailer\PHPMailer\PHPMailer;

final class EmailService
{
    public function send(string $to, string $subject, string $body): array
    {
        $settings = $this->loadSettings();

        $host = $this->setting($settings, 'smtp_host', 'smtp.host', '');
        $port = (int) $this->setting($settings, 'smtp_port', 'smtp.port', '587');
        $encryption = strtolower($this->setting($settings, 'smtp_encryption', 'smtp.encryption', 'tls'));
        $username = $this->setting($settings, 'smtp_username', 'smtp.username', '');
        $password = $this->smtpPassword($settings);

        $fromEmail = $this->setting($settings, 'smtp_from_email', 'smtp.from_email', $username);
        $fromName = $this->setting($settings, 'smtp_from_name', 'smtp.from_name', 'The Oaks Therapy | Compliance Tracker');
        $replyToEmail = $this->setting($settings, 'smtp_reply_to_email', null, $fromEmail);

        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            return ['ok' => false, 'error' => 'Recipient email address is invalid.'];
        }

        if ($host === '' || $port < 1 || $username === '' || $password === '') {
            return ['ok' => false, 'error' => 'SMTP authentication fields are incomplete.'];
        }

        if (!filter_var($fromEmail, FILTER_VALIDATE_EMAIL)) {
            return ['ok' => false, 'error' => 'From email address is invalid.'];
        }

        try {
            $m = new PHPMailer(true);
            $m->CharSet = 'UTF-8';
            $m->isSMTP();
            $m->Host = $host;
            $m->Port = $port;
            $m->SMTPAuth = true;
            $m->Username = $username;
            $m->Password = $password;

            if ($encryption === 'ssl') {
                $m->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            } elseif ($encryption === 'tls') {
                $m->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            } else {
                $m->SMTPSecure = false;
            }

            $m->setFrom($fromEmail, $fromName);

            if ($replyToEmail !== '' && filter_var($replyToEmail, FILTER_VALIDATE_EMAIL)) {
                $m->addReplyTo($replyToEmail, $fromName);
            }

            $m->addAddress($to);
            $m->Subject = $subject;
            $m->Body = $body;
            $m->isHTML(false);
            $m->send();

            return ['ok' => true, 'error' => null];
        } catch (\Throwable $e) {
            $safeError = $this->sanitizeError($e->getMessage(), $username, $password);
            error_log('Email send failed: ' . $safeError);

            return ['ok' => false, 'error' => $safeError];
        }
    }

    public function sendTest(string $to): array
    {
        return $this->send(
            $to,
            'SMTP Test Email',
            "SMTP configuration test message from The Oaks Compliance Tracker."
        );
    }

    public function smtpPresent(): bool
    {
        $settings = $this->loadSettings();

        $host = $this->setting($settings, 'smtp_host', 'smtp.host', '');
        $username = $this->setting($settings, 'smtp_username', 'smtp.username', '');
        $password = $this->smtpPassword($settings);

        return $host !== '' && $username !== '' && $password !== '';
    }

    private function loadSettings(): array
    {
        try {
            $pdo = Connection::get();
            $rows = $pdo
                ->query('SELECT setting_key, setting_value FROM app_settings')
                ->fetchAll(\PDO::FETCH_KEY_PAIR);

            return is_array($rows) ? $rows : [];
        } catch (\Throwable $e) {
            error_log('Unable to load app settings for email: ' . $e->getMessage());
            return [];
        }
    }

    private function setting(array $settings, string $dbKey, ?string $configKey = null, string $default = ''): string
    {
        $value = trim((string)($settings[$dbKey] ?? ''));

        if ($value !== '') {
            return $value;
        }

        if ($configKey !== null) {
            return trim((string)config($configKey, $default));
        }

        return $default;
    }

    private function smtpPassword(array $settings): string
    {
        $encrypted = trim((string)($settings['smtp_password_encrypted'] ?? ''));

        if ($encrypted !== '') {
            $decrypted = $this->decryptSecret($encrypted);

            if ($decrypted !== '') {
                return $decrypted;
            }
        }

        return trim((string)config('smtp.password', ''));
    }

    private function decryptSecret(string $payload): string
    {
        $keySource = (string)config('app.encryption_key', '');

        if ($keySource === '') {
            error_log('Missing app.encryption_key. Cannot decrypt SMTP password.');
            return '';
        }

        $raw = base64_decode($payload, true);

        if ($raw === false || strlen($raw) <= 16) {
            error_log('Invalid encrypted SMTP password payload.');
            return '';
        }

        $iv = substr($raw, 0, 16);
        $cipherText = substr($raw, 16);
        $key = hash('sha256', $keySource, true);

        $plain = openssl_decrypt($cipherText, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);

        return $plain === false ? '' : $plain;
    }

    private function sanitizeError(string $message, string $username, string $password): string
    {
        $safe = $message;

        if ($username !== '') {
            $safe = str_replace($username, '[smtp username]', $safe);
        }

        if ($password !== '') {
            $safe = str_replace($password, '[smtp password]', $safe);
        }

        $safe = preg_replace('/[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}/i', '[email]', $safe) ?? $safe;
        $safe = trim($safe);

        if ($safe === '') {
            return 'SMTP error: email failed to send.';
        }

        return 'SMTP error: ' . mb_substr($safe, 0, 250);
    }
}