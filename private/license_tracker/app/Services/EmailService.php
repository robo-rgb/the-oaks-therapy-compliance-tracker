<?php

declare(strict_types=1);

namespace App\Services;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

final class EmailService
{
    public function send(string $to, string $subject, string $body): array
    {
        try {
            $m = new PHPMailer(true);
            $m->isSMTP();
            $m->Host = (string)config('smtp.host');
            $m->Port = (int)config('smtp.port',587);
            $m->SMTPSecure = (string)config('smtp.encryption','tls');
            $m->SMTPAuth = true;
            $m->Username = (string)config('smtp.username');
            $m->Password = (string)config('smtp.password');
            $m->setFrom((string)config('smtp.from_email'), (string)config('smtp.from_name'));
            $m->addAddress($to);
            $m->Subject = $subject;
            $m->Body = $body;
            $m->isHTML(false);
            $m->send();
            return ['ok'=>true,'error'=>null];
        } catch (\Throwable $e) {
            return ['ok'=>false,'error'=>'Email send failed.'];
        }
    }

    public function sendTest(string $to): array
    {
        return $this->send($to, 'SMTP Test Email', "SMTP configuration test message from The Oaks Compliance Tracker.");
    }

    public function smtpPresent(): bool
    {
        return (string)config('smtp.host') !== '' && (string)config('smtp.username') !== '' && (string)config('smtp.password') !== '';
    }
}
