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
    ]);
});

it('does not duplicate or overwrite tiers that already exist', function () {
    MembershipLevel::query()->create([
        'name' => 'Silver',
        'percentage' => '0.80',
        'minimum_balance' => '50000',
    ]);

    $this->seed(MembershipLevelSeeder::class);

    expect(MembershipLevel::query()->count())->toBe(5)
        ->and(MembershipLevel::query()->where('name', 'Silver')->value('percentage'))->toBe('0.80')
        ->and(MembershipLevel::query()->where('name', 'Silver')->value('minimum_balance'))->toBe('50000');
});
