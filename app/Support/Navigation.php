<?php

namespace App\Support;

class Navigation
{
    /**
     * @return array<int, array{label: string, route: string|null, icon: string, active: string|array<int, string>, mobile?: bool, prefetch?: bool}>
     */
    public static function user(): array
    {
        return [
            ['label' => 'Home', 'route' => 'dashboard', 'icon' => 'layout-dashboard', 'active' => 'dashboard', 'mobile' => true, 'prefetch' => true],
            ['label' => 'Start Tasks', 'route' => 'dashboard.start', 'icon' => 'send', 'active' => 'dashboard.start*', 'mobile' => true],
            ['label' => 'Records', 'route' => 'dashboard.record', 'icon' => 'file-text', 'active' => 'dashboard.record*', 'mobile' => true],
            ['label' => 'Wallet', 'route' => 'dashboard.wallet', 'icon' => 'wallet', 'active' => ['dashboard.wallet*', 'dashboard.deposit*', 'dashboard.withdraw*'], 'mobile' => true],
            ['label' => 'Bonus Center', 'route' => 'dashboard.bonus-center', 'icon' => 'gift', 'active' => 'dashboard.bonus-center*', 'mobile' => false, 'prefetch' => true],
            ['label' => 'Notifications', 'route' => 'dashboard.notifications', 'icon' => 'bell', 'active' => 'dashboard.notifications*', 'mobile' => false],
            ['label' => 'Profile', 'route' => 'dashboard.account', 'icon' => 'circle-user-round', 'active' => 'dashboard.account*', 'mobile' => true],
        ];
    }

    /**
     * @return array<int, array{label: string, route: string|null, icon: string, active: string}>
     */
    public static function admin(): array
    {
        return [
            ['label' => 'Overview', 'route' => 'admin.dashboard', 'icon' => 'layout-dashboard', 'active' => 'admin.dashboard'],
            ['label' => 'User Management', 'route' => 'admin.dashboard.users', 'icon' => 'users', 'active' => 'admin.dashboard.users*'],
            ['label' => 'Product Tasks', 'route' => 'admin.dashboard.tasks', 'icon' => 'film', 'active' => 'admin.dashboard.tasks*'],
            ['label' => 'Assign Orders', 'route' => 'admin.dashboard.assign-orders', 'icon' => 'list-todo', 'active' => 'admin.dashboard.assign-orders*'],
            ['label' => 'Bonus Center', 'route' => 'admin.dashboard.bonus-center', 'icon' => 'gift', 'active' => 'admin.dashboard.bonus-center*'],
            ['label' => 'Product Warehouse', 'route' => 'admin.dashboard.warehouse', 'icon' => 'warehouse', 'active' => 'admin.dashboard.warehouse*'],
            ['label' => 'Training', 'route' => 'admin.training', 'icon' => 'graduation-cap', 'active' => 'admin.training'],
            ['label' => 'Payments', 'route' => 'admin.payments', 'icon' => 'credit-card', 'active' => 'admin.payments'],
            ['label' => 'Upgrades', 'route' => 'admin.upgrades', 'icon' => 'circle-arrow-up', 'active' => 'admin.upgrades'],
            ['label' => 'Manage Admins', 'route' => 'admin.admins', 'icon' => 'shield-check', 'active' => 'admin.admins'],
            ['label' => 'Platform Settings', 'route' => 'admin.settings', 'icon' => 'settings', 'active' => 'admin.settings*'],
            // ['label' => 'Live Chat', 'route' => null, 'icon' => 'message-circle', 'active' => 'admin.dashboard.chat*'],
            ['label' => 'Announcements', 'route' => 'admin.announcements', 'icon' => 'megaphone', 'active' => 'admin.announcements'],
        ];
    }
}
