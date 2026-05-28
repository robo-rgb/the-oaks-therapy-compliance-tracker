<?php
declare(strict_types=1);
?>
<nav>
    <a href="<?= e(app_base_path('dashboard.php')) ?>">Dashboard</a> |
    <a href="<?= e(app_base_path('license.php')) ?>">License Profile</a> |
    <a href="<?= e(app_base_path('cycles.php')) ?>">Renewal Cycles</a> |
    <a href="<?= e(app_base_path('ce/index.php')) ?>">CE Courses</a> |
    <a href="<?= e(app_base_path('documents.php')) ?>">Documents</a> |
    <a href="<?= e(app_base_path('reports/index.php')) ?>">Reports</a> |
    <a href="<?= e(app_base_path('settings.php')) ?>">Settings</a> |
    <a href="<?= e(app_base_path('reminders_log.php')) ?>">Reminder Log</a>

    <form style="display:inline" method="post" action="<?= e(app_base_path('logout.php')) ?>">
        <?= csrf_input() ?>
        <button type="submit">Logout</button>
    </form>
</nav>
<hr>