<?php
declare(strict_types=1);
require __DIR__ . '/../../private/license_tracker/vendor/autoload.php';
App\Middleware\RequireAuth::enforce();
start_secure_session();
$userId = (int)$_SESSION['user_id'];
$service = new App\Services\LicenseService();
$license = $service->getByUserId($userId);
?>
<!doctype html><html><body>
<nav><a href="<?= e(app_base_path('dashboard.php')) ?>">Dashboard</a> | <a href="<?= e(app_base_path('license.php')) ?>">License Profile</a> | <a href="<?= e(app_base_path('cycles.php')) ?>">Renewal Cycles</a>
<form style="display:inline" method="post" action="<?= e(app_base_path('logout.php')) ?>"><?= csrf_input() ?><button>Logout</button></form></nav>
<h1>License Profile</h1>
<?php if (!$license): ?>
<p>No license profile found. <a href="<?= e(app_base_path('license_edit.php')) ?>">Create License Profile</a></p>
<?php else: ?>
<p>Name: <?= e(($license['licensee_first_name'] ?? '') . ' ' . ($license['licensee_last_name'] ?? '')) ?></p>
<p>Type: <?= e($license['license_type'] ?? '') ?></p><p>State: <?= e($license['state'] ?? '') ?></p><p>Number: <?= e($license['license_number'] ?? '') ?></p><p>Status: <?= e($license['status'] ?? '') ?></p>
<p>Issue Date: <?= e($license['original_issue_date'] ?? '') ?></p><p>Notes: <?= e($license['notes'] ?? '') ?></p>
<p><a href="<?= e(app_base_path('license_edit.php')) ?>">Edit Profile</a></p>
<?php endif; ?>
</body></html>
