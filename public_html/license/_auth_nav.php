<?php
declare(strict_types=1);

$currentPath = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? ''));

$navItems = [
    [
        'label' => 'Dashboard',
        'href' => 'dashboard.php',
        'matches' => ['dashboard.php'],
    ],
    [
        'label' => 'License Profile',
        'href' => 'license.php',
        'matches' => ['license.php'],
    ],
    [
        'label' => 'Renewal Cycles',
        'href' => 'cycles.php',
        'matches' => ['cycles.php'],
    ],
    [
        'label' => 'CE Courses',
        'href' => 'ce/index.php',
        'matches' => ['/ce/', 'ce/index.php'],
    ],
    [
        'label' => 'Documents',
        'href' => 'documents.php',
        'matches' => ['documents.php'],
    ],
    [
        'label' => 'Reports',
        'href' => 'reports/index.php',
        'matches' => ['/reports/', 'reports/index.php'],
    ],
    [
        'label' => 'Settings',
        'href' => 'settings.php',
        'matches' => ['settings.php'],
    ],
    [
        'label' => 'Reminder Log',
        'href' => 'reminders_log.php',
        'matches' => ['reminders_log.php'],
    ],
];

function oaks_nav_is_active(string $currentPath, array $matches): bool
{
    foreach ($matches as $match) {
        if ($match !== '' && str_contains($currentPath, $match)) {
            return true;
        }
    }

    return false;
}
?>

<script>
(function () {
    try {
        const savedTheme = localStorage.getItem('oaks-theme');
        const systemTheme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
        document.documentElement.dataset.theme = savedTheme || systemTheme;
    } catch (error) {
        document.documentElement.dataset.theme = 'light';
    }
})();
</script>

<link rel="stylesheet" href="<?= e(app_base_path('assets/css/app.css')) ?>">

<a class="skip-link" href="#main-content">Skip to content</a>

<header class="app-header">
    <div class="app-header__inner">
        <a class="brand" href="<?= e(app_base_path('dashboard.php')) ?>" aria-label="The Oaks Therapy dashboard">
            <img
                class="brand__logo"
                src="https://www.theoakstherapy.com/img/the-oaks-logo-circle.png"
                alt="The Oaks Therapy logo"
            >
            <span class="brand__text">
                <span class="brand__name">The Oaks Therapy</span>
                <span class="brand__sub">Compliance Tracker</span>
            </span>
        </a>

        <nav class="app-nav" aria-label="Primary navigation">
            <?php foreach ($navItems as $item): ?>
                <?php $active = oaks_nav_is_active($currentPath, $item['matches']); ?>
                <a
                    class="app-nav__link<?= $active ? ' is-active' : '' ?>"
                    href="<?= e(app_base_path($item['href'])) ?>"
                    <?= $active ? 'aria-current="page"' : '' ?>
                >
                    <?= e($item['label']) ?>
                </a>
            <?php endforeach; ?>
        </nav>

        <div class="app-header__actions">
            <button class="theme-toggle" id="themeToggle" type="button" aria-label="Toggle light and dark mode" aria-pressed="false">
                <span class="theme-toggle__icon" aria-hidden="true">◐</span>
                <span class="theme-toggle__text">Theme</span>
            </button>

            <form class="logout-form" method="post" action="<?= e(app_base_path('logout.php')) ?>">
                <?= csrf_input() ?>
                <button class="button button--ghost" type="submit">Logout</button>
            </form>
        </div>
    </div>
</header>

<script src="<?= e(app_base_path('assets/js/theme.js')) ?>" defer></script>
