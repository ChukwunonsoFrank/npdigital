<?php

namespace App\Support;

class Navigation
{
    /**
     * @return array<int, array{label: string, route: string|null, icon: string, active: string|array<int, string>, mobile?: bool}>
     */
    public static function user(): array
    {
        return [
            ['label' => 'Home', 'route' => 'dashboard', 'icon' => 'layout-dashboard', 'active' => 'dashboard', 'mobile' => true],
            ['label' => 'Start Tasks', 'route' => 'dashboard.start', 'icon' => 'send', 'active' => 'dashboard.start*', 'mobile' => true],
            ['label' => 'Records', 'route' => 'dashboard.record', 'icon' => 'file-text', 'active' => 'dashboard.record*', 'mobile' => true],
            ['label' => 'Wallet', 'route' => 'dashboard.wallet', 'icon' => 'wallet', 'active' => ['dashboard.wallet*', 'dashboard.deposit*', 'dashboard.withdraw*'], 'mobile' => true],
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
            ['label' => 'Product Warehouse', 'route' => null, 'icon' => 'warehouse', 'active' => 'admin.dashboard.warehouse*'],
            ['label' => 'Training', 'route' => null, 'icon' => 'graduation-cap', 'active' => 'admin.dashboard.training*'],
            ['label' => 'Payments', 'route' => 'admin.dashboard.deposits', 'icon' => 'credit-card', 'active' => 'admin.dashboard.deposits*'],
            ['label' => 'Upgrades', 'route' => 'admin.dashboard.membershiplevels', 'icon' => 'circle-arrow-up', 'active' => 'admin.dashboard.membershiplevels*'],
            ['label' => 'Manage Admins', 'route' => null, 'icon' => 'shield-check', 'active' => 'admin.dashboard.admins*'],
            ['label' => 'Platform Settings', 'route' => null, 'icon' => 'settings', 'active' => 'admin.dashboard.settings*'],
            ['label' => 'Live Chat', 'route' => null, 'icon' => 'message-circle', 'active' => 'admin.dashboard.chat*'],
            ['label' => 'Announcements', 'route' => 'admin.dashboard.broadcast', 'icon' => 'megaphone', 'active' => 'admin.dashboard.broadcast*'],
        ];
    }
}
