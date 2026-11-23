<?php
/**
 * Admin: Users Management (Owner ONLY)
 */

declare(strict_types=1);

use App\Core\Csrf;

require APP_ROOT . '/app/Views/admin/nav.php';
?>

<div class="space-y-5">
    <!-- Create New User -->
    <section class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-4 sm:p-5 shadow-sm">
        <h2 class="text-base sm:text-lg font-bold mb-3"><?= e(t('add_user')) ?></h2>
        <form method="post" action="<?= e(app_url('?route=admin/users')) ?>" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-2.5">
            <?= Csrf::input() ?>
            <input type="hidden" name="action" value="create_user">
            <input type="text" name="name" required placeholder="<?= e(t('user_name')) ?>"
                   class="min-h-[44px] px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-sm">
            <input type="email" name="email" required placeholder="<?= e(t('email')) ?>"
                   class="min-h-[44px] px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-sm">
            <input type="password" name="password" required minlength="6" placeholder="<?= e(t('password')) ?>"
                   class="min-h-[44px] px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-sm">
            <select name="role" class="min-h-[44px] px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-sm">
                <option value="staff"><?= e(t('role_staff')) ?></option>
                <option value="manager"><?= e(t('role_manager')) ?></option>
                <option value="owner"><?= e(t('role_owner')) ?></option>
            </select>
            <button type="submit" class="min-h-[44px] px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-sm">
                + <?= e(t('add_new')) ?>
            </button>
        </form>
    </section>

    <!-- User List -->
    <section class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-4 sm:p-5 shadow-sm space-y-3">
        <?php foreach ($users as $u):
            $uid = (int) $u['id'];
            $isSelf = ($uid === (int) $currentUser['id']);
        ?>
            <div class="p-3.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-800/40 flex flex-col lg:flex-row lg:items-center justify-between gap-3">
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="font-bold text-sm sm:text-base text-slate-900 dark:text-white"><?= e($u['name']) ?></span>
                        <span class="px-2 py-0.5 rounded-md text-xs font-semibold bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300">
                            <?= e(t('role_' . $u['role'])) ?>
                        </span>
                        <span class="px-2 py-0.5 rounded-md text-xs font-semibold <?= (int) $u['is_active'] ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300' : 'bg-rose-50 text-rose-700 dark:bg-rose-950/50 dark:text-rose-300' ?>">
                            <?= (int) $u['is_active'] ? e(t('active')) : e(t('inactive')) ?>
                        </span>
                    </div>
                    <div class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                        <?= e($u['email']) ?> · <?= e(t('last_login')) ?>: <?= e(bn_datetime($u['last_login_at'])) ?>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <!-- Reset Password Form -->
                    <form method="post" action="<?= e(app_url('?route=admin/users')) ?>" class="flex items-center gap-1.5">
                        <?= Csrf::input() ?>
                        <input type="hidden" name="action" value="reset_password">
                        <input type="hidden" name="id" value="<?= $uid ?>">
                        <input type="password" name="new_password" required minlength="6" placeholder="<?= e(t('new_password')) ?>"
                               class="w-36 sm:w-44 min-h-[40px] px-2.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-xs">
                        <button type="submit" class="min-h-[40px] px-3 rounded-lg bg-slate-800 dark:bg-indigo-600 text-white text-xs font-semibold whitespace-nowrap">
                            <?= e(t('reset_password')) ?>
                        </button>
                    </form>

                    <!-- Enable / Disable -->
                    <?php if (!$isSelf): ?>
                        <form method="post" action="<?= e(app_url('?route=admin/users')) ?>">
                            <?= Csrf::input() ?>
                            <input type="hidden" name="action" value="toggle_user">
                            <input type="hidden" name="id" value="<?= $uid ?>">
                            <button type="submit" class="min-h-[40px] px-3 rounded-lg text-xs font-semibold border <?= (int) $u['is_active'] ? 'bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-950/50 dark:text-rose-300 dark:border-rose-800' : 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/50 dark:text-emerald-300 dark:border-emerald-800' ?>">
                                <?= (int) $u['is_active'] ? e(t('inactive')) . ' করুন' : e(t('active')) . ' করুন' ?>
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </section>
</div>
