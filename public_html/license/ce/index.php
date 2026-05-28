<?php
declare(strict_types=1);

require __DIR__ . '/../../../private/license_tracker/vendor/autoload.php';

App\Middleware\RequireAuth::enforce();
start_secure_session();

$userId = (int) $_SESSION['user_id'];

$licenseService = new App\Services\LicenseService();
$cycleService = new App\Services\RenewalCycleService();
$ceService = new App\Services\CeCourseService();
$docService = new App\Services\DocumentService();

$license = $licenseService->getByUserId($userId);
$noContext = '';

if (!$license) {
    $noContext = 'No active license. ';
}

$activeCycle = $license ? $cycleService->getActive((int) $license['id']) : null;

if ($license && !$activeCycle) {
    $noContext = 'No active renewal cycle. ';
}

$filters = [
    'license_id' => (int) ($license['id'] ?? 0),
    'renewal_cycle_id' => (int) ($_GET['renewal_cycle_id'] ?? ($activeCycle['id'] ?? 0)),
    'category' => trim((string) ($_GET['category'] ?? '')),
    'delivery_mode' => trim((string) ($_GET['delivery_mode'] ?? '')),
    'q' => trim((string) ($_GET['q'] ?? '')),
];

$courses = ($license && $activeCycle) ? $ceService->list($filters) : [];
$cycles = $license ? $cycleService->listByLicense((int) $license['id']) : [];

$cycleById = [];
foreach ($cycles as $cc) {
    $cycleById[(int) $cc['id']] = $cc;
}

$activeFilterLabels = [];

if (!empty($filters['renewal_cycle_id'])) {
    $filterCycleId = (int) $filters['renewal_cycle_id'];

    if (isset($cycleById[$filterCycleId])) {
        $filterCycle = $cycleById[$filterCycleId];
        $activeFilterLabels[] = 'renewal cycle ' . $filterCycle['cycle_start'] . ' to ' . $filterCycle['cycle_end'];
    } else {
        $activeFilterLabels[] = 'selected renewal cycle';
    }
}

if ($filters['category'] !== '') {
    $activeFilterLabels[] = 'category "' . $filters['category'] . '"';
}

if ($filters['delivery_mode'] !== '') {
    $activeFilterLabels[] = 'delivery mode "' . $filters['delivery_mode'] . '"';
}

if ($filters['q'] !== '') {
    $activeFilterLabels[] = 'search "' . $filters['q'] . '"';
}

$resultCount = count($courses);
?>
<!doctype html>
<html>
<body>
<?php require __DIR__ . '/../_auth_nav.php'; ?>

<h1>CE Courses</h1>

<?php if (!empty($noContext)): ?>
    <p>
        <?= e($noContext) ?>
        <a href="<?= e(app_base_path('license_edit.php')) ?>">Set up now</a>
    </p>
<?php else: ?>

    <p>
        <a href="<?= e(app_base_path('ce/create.php')) ?>">Add CE Course</a>
    </p>

    <form method="get">
        <p>
            <label>
                Renewal cycle<br>
                <select name="renewal_cycle_id">
                    <option value="">All cycles</option>
                    <?php foreach ($cycles as $c): ?>
                        <option value="<?= (int) $c['id'] ?>" <?= (int) $filters['renewal_cycle_id'] === (int) $c['id'] ? 'selected' : '' ?>>
                            <?= e($c['cycle_start'] . ' to ' . $c['cycle_end']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
        </p>

        <p>
            <label>
                Category<br>
                <select name="category">
                    <option value="">All categories</option>
                    <?php foreach (App\Services\CeCourseService::CATEGORIES as $v): ?>
                        <option value="<?= e($v) ?>" <?= $filters['category'] === $v ? 'selected' : '' ?>>
                            <?= e($v) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
        </p>

        <p>
            <label>
                Delivery mode<br>
                <select name="delivery_mode">
                    <option value="">All modes</option>
                    <?php foreach (App\Services\CeCourseService::DELIVERY_MODES as $v): ?>
                        <option value="<?= e($v) ?>" <?= $filters['delivery_mode'] === $v ? 'selected' : '' ?>>
                            <?= e($v) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
        </p>

        <p>
            <label>
                Search title/provider<br>
                <input name="q" value="<?= e($filters['q']) ?>" placeholder="Search title/provider">
            </label>
        </p>

        <button type="submit">Filter</button>
    </form>

        <p>
        <strong>Search results:</strong>
        <?= (int) $resultCount ?>
        <?= $resultCount === 1 ? 'CE course found' : 'CE courses found' ?>

        <?php if ($activeFilterLabels): ?>
            for <?= e(implode(', ', $activeFilterLabels)) ?>.
        <?php else: ?>
            across all filters.
        <?php endif; ?>

        <?php if ($activeFilterLabels): ?>
            <a href="<?= e(app_base_path('ce/index.php')) ?>">Clear filters</a>
        <?php endif; ?>
    </p>

    <table border="1">
        <tr>
            <th>Date completed</th>
            <th>Course title</th>
            <th>Provider</th>
            <th>Hours</th>
            <th>Category</th>
            <th>Format</th>
            <th>Delivery mode</th>
            <th>Counts toward cycle</th>
            <th>Document status</th>
            <th>Issues</th>
            <th>Actions</th>
        </tr>

        <?php foreach ($courses as $row): ?>
            <?php
            $hasDocument = $docService->hasCertificateForCourse(
                (int) $row['id'],
                (int) $license['id'],
                (int) ($row['renewal_cycle_id'] ?? 0)
            );

            $issues = [];

            if ((string) $row['category'] === 'ethics' && (string) $row['delivery_mode'] !== 'synchronous') {
                $issues[] = 'Ethics async';
            }

            if ((float) $row['hours'] > 20 && (int) $row['is_professional_conference'] !== 1) {
                $issues[] = 'Over 20h';
            }

            if ((int) $row['counts_toward_cycle'] === 1 && isset($cycleById[(int) $row['renewal_cycle_id']])) {
                $cy = $cycleById[(int) $row['renewal_cycle_id']];

                if (
                    (string) $row['date_completed'] < (string) $cy['cycle_start']
                    || (string) $row['date_completed'] > (string) $cy['cycle_end']
                ) {
                    $issues[] = 'Outside cycle';
                }
            }

            if (!$hasDocument) {
                $issues[] = 'Missing cert';
            }
            ?>

            <tr>
                <td><?= e((string) $row['date_completed']) ?></td>
                <td><?= e((string) $row['course_title']) ?></td>
                <td><?= e((string) $row['provider_name']) ?></td>
                <td><?= e((string) $row['hours']) ?></td>
                <td><?= e((string) $row['category']) ?></td>
                <td><?= e((string) $row['format']) ?></td>
                <td><?= e((string) $row['delivery_mode']) ?></td>
                <td><?= (int) $row['counts_toward_cycle'] === 1 ? 'Yes' : 'No' ?></td>
                <td><?= $hasDocument ? 'Present' : 'Missing' ?></td>
                <td><?= e(implode(', ', $issues)) ?></td>
                <td>
                    <a href="<?= e(app_base_path('ce/edit.php')) . '?id=' . (int) $row['id'] ?>">Edit</a>

                    <form
                        method="post"
                        action="<?= e(app_base_path('ce/delete.php')) . '?id=' . (int) $row['id'] ?>"
                        style="display:inline"
                        onsubmit="return confirm('Are you sure you want to delete this CE course? This cannot be undone.');"
                    >
                        <?= csrf_input() ?>
                        <button type="submit">Delete</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>

    <?php if (!$courses): ?>
    <p>
        No CE courses match the selected filters.
        <a href="<?= e(app_base_path('ce/create.php')) ?>">Add a CE course</a>
        or
        <a href="<?= e(app_base_path('ce/index.php')) ?>">clear filters</a>.
    </p>
<?php endif; ?>

<?php endif; ?>
</body>
</html>