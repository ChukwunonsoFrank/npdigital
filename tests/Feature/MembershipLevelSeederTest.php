<?php

use App\Models\MembershipLevel;
use Database\Seeders\MembershipLevelSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('seeds the membership tiers used by the upgrade dropdowns', function () {
    $this->seed(MembershipLevelSeeder::class);

    expect(MembershipLevel::query()->orderBy('percentage')->pluck('percentage', 'name')->all())->toBe([
        'Normal' => '0.50',
        'Silver' => '0.75',
        'Gold' => '1.00',
        'Platinum' => '1.25',
        'Diamond' => '1.50',
        'VVIP' => '2.00',
    ]);
});

it('seeds VVIP with empty warehouse groups', function () {
    $this->seed(MembershipLevelSeeder::class);

    expect(\App\Support\ProductWarehouse::levels())->toContain(['value' => 'vvip', 'label' => 'VVIP'])
        ->and(\App\Support\ProductWarehouse::groupItems('vvip', 1, 'a'))->toBe([])
        ->and(array_sum(\App\Support\ProductWarehouse::groupCounts('vvip')[1]))->toBe(0);
});

it('does not duplicate or overwrite tiers that already exist', function () {
    MembershipLevel::query()->create([
        'name' => 'Silver',
        'percentage' => '0.80',
        'minimum_balance' => '50000',
    ]);

    $this->seed(MembershipLevelSeeder::class);

    expect(MembershipLevel::query()->count())->toBe(6)
        ->and(MembershipLevel::query()->where('name', 'Silver')->value('percentage'))->toBe('0.80')
        ->and(MembershipLevel::query()->where('name', 'Silver')->value('minimum_balance'))->toBe('50000');
});
