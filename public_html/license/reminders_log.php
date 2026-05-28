<?php declare(strict_types=1);
require __DIR__.'/../../private/license_tracker/vendor/autoload.php';
App\Middleware\RequireAuth::enforce();
$rows=App\Database\Connection::get()->query('SELECT attempted_at, recipient_email, subject, reminder_key, related_deadline, status, response_message FROM reminder_logs ORDER BY attempted_at DESC LIMIT 500')->fetchAll();
?>
<!doctype html>
<html>
<body>
<?php require __DIR__ . '/_auth_nav.php'; ?>

<h1>Reminder Log</h1><table border="1"><tr><th>Date sent</th><th>Recipient</th><th>Subject</th><th>Reminder key</th><th>Related deadline</th><th>Status</th><th>Error message</th></tr><?php foreach($rows as $r): ?><tr><td><?= e((string)$r['attempted_at']) ?></td><td><?= e((string)$r['recipient_email']) ?></td><td><?= e((string)$r['subject']) ?></td><td><?= e((string)$r['reminder_key']) ?></td><td><?= e((string)$r['related_deadline']) ?></td><td><?= e((string)$r['status']) ?></td><td><?= e((string)$r['response_message']) ?></td></tr><?php endforeach; ?></table></body></html>
