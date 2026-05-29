<?php
declare(strict_types=1);

require __DIR__ . '/../../private/license_tracker/vendor/autoload.php';

App\Middleware\RequireAuth::enforce();
start_secure_session();

$rows = App\Database\Connection::get()
    ->query('SELECT attempted_at, recipient_email, subject, reminder_key, related_deadline, status, response_message FROM reminder_logs ORDER BY attempted_at DESC LIMIT 500')
    ->fetchAll();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Reminder Log - The Oaks Therapy Compliance Tracker</title>
</head>
<body>
<?php require __DIR__ . '/_auth_nav.php'; ?>

<main id="main-content" class="page-shell">
    <header class="page-header">
        <div class="page-header__content">
            <p class="page-eyebrow">Automation</p>
            <h1>Reminder Log</h1>
            <p class="page-subtitle">Recent reminder and test-email delivery attempts. Showing the latest 500 entries.</p>
        </div>
    </header>

    <section class="card">
        <div class="card__header">
            <div>
                <h2 class="card__title">Email activity</h2>
                <p class="card__subtitle"><?= count($rows) ?> <?= count($rows) === 1 ? 'entry' : 'entries' ?> found.</p>
            </div>
        </div>

        <?php if (!$rows): ?>
            <div class="empty-state">
                <h3>No reminder activity yet</h3>
                <p>Test emails and reminder attempts will appear here once they run.</p>
            </div>
        <?php else: ?>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Date sent</th>
                            <th>Recipient</th>
                            <th>Subject</th>
                            <th>Reminder key</th>
                            <th>Related deadline</th>
                            <th>Status</th>
                            <th>Error message</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rows as $r): ?>
                            <?php
                                $status = strtolower((string)$r['status']);
                                $statusClass = $status === 'sent' ? 'badge--success' : ($status === 'failed' ? 'badge--danger' : 'badge--muted');
                            ?>
                            <tr>
                                <td><?= e((string)$r['attempted_at']) ?></td>
                                <td><?= e((string)$r['recipient_email']) ?></td>
                                <td><?= e((string)$r['subject']) ?></td>
                                <td><?= e((string)$r['reminder_key']) ?></td>
                                <td><?= e((string)$r['related_deadline']) ?></td>
                                <td><span class="badge <?= e($statusClass) ?>"><?= e((string)$r['status']) ?></span></td>
                                <td><?= e((string)$r['response_message']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
</main>
</body>
</html>
