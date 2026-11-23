<?php
/**
 * Admin Sub-Navigation Tabs (Horizontally scrollable on mobile)
 */

declare(strict_types=1);

$adminTab = $adminTab ?? 'floors';
$isOwner  = ($currentUser['role'] ?? '') === 'owner';

$tabs = [
    'floors'   => ['label' => t('admin_floors_rooms'), 'route' => 'admin/floors'],
    'sections' => ['label' => t('admin_sections'),     'route' => 'admin/sections'],
    'options'  => ['label' => t('admin_options'),      'route' => 'admin/options'],
    'extras'   => ['label' => t('admin_extras'),       'route' => 'admin/extras'],
    'defaults' => ['label' => t('admin_defaults'),     'route' => 'admin/defaults'],
];
if ($isOwner) {
    $tabs['users'] = ['label' => t('admin_users'), 'route' => 'admin/users'];
}
$tabs['audit'] = ['label' => t('admin_audit'), 'route' => 'admin/audit'];
?>

<div class="mb-4 flex items-center gap-1.5 overflow-x-auto no-scrollbar pb-1">
    <?php foreach ($tabs as $key => $tab):
        $active = ($adminTab === $key);
    ?>
        <a href="<?= e(app_url('?route=' . $tab['route'])) ?>"
           class="min-h-[42px] px-3.5 py-2 rounded-xl text-xs sm:text-sm font-semibold whitespace-nowrap shrink-0 border transition inline-flex items-center <?= $active
               ? 'bg-indigo-600 text-white border-indigo-600 shadow-sm'
               : 'bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-200 border-slate-200 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800' ?>">
            <?= e($tab['label']) ?>
        </a>
    <?php endforeach; ?>
</div>
