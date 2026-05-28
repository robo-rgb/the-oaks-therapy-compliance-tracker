<?php
declare(strict_types=1);
require __DIR__ . '/../../private/license_tracker/vendor/autoload.php';
App\Middleware\RequireAuth::enforce();
start_secure_session();
$userId=(int)$_SESSION['user_id'];
$licenseService = new App\Services\LicenseService();
$license = $licenseService->getByUserId($userId);
?>
<!doctype html><html><body>
<?php require __DIR__ . '/_auth_nav.php'; ?>
<form style="display:inline" method="post" action="<?= e(app_base_path('logout.php')) ?>"><?= csrf_input() ?><button>Logout</button></form></nav>
<h1>Renewal Cycles</h1>
<?php if (!$license): ?>
<p>Create a <a href="<?= e(app_base_path('license_edit.php')) ?>">license profile</a> first.</p>
<?php else: $svc = new App\Services\RenewalCycleService(); $cycles=$svc->listByLicense((int)$license['id']); ?>
<p><a href="<?= e(app_base_path('cycle_create.php')) ?>">Create Cycle</a></p>
<?php if (!$cycles): ?><p>No cycles found.</p><?php endif; ?>
<?php foreach($cycles as $c): ?>
<div>
<?= e($c['cycle_start']) ?> to <?= e($c['cycle_end']) ?> | Active: <?= (int)$c['is_active']===1?'Yes':'No' ?>
| <a href="<?= e(app_base_path('cycle_edit.php')) . '?id=' . (int)$c['id'] ?>">Edit</a>
</div>
<?php endforeach; endif; ?>
</body></html>
