<?php

use App\Livewire\Admin\BonusCenter as AdminBonusCenter;
use App\Livewire\Dashboard\BonusCenter;
use App\Models\DailyCheckIn;
use App\Models\PlatformSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('keeps the daily check in locked until the required sets are complete', function () {
    $this->travelTo(Carbon::parse('2026-10-27 12:00:00', 'UTC'));
    $user = User::factory()->create([
        'is_admin' => false, 'membership_level' => 'Normal', 'timezone' => 'UTC',
        'task_batch' => 2, 'tasks_completed' => 10, 'balance' => 0,
    ]);

    Livewire::actingAs($user)->test(BonusCenter::class)
        ->assertSee('Locked')
        ->assertSee('2/3 sets')
        ->call('claim')
        ->assertDispatched('toast', message: 'Complete 3 sets today to unlock your bonus check-in.');

    expect(DailyCheckIn::query()->count())->toBe(0)
        ->and((int) $user->fresh()->balance)->toBe(0);
});

it('claims the daily reward once and credits the configured amount', function () {
    $this->travelTo(Carbon::parse('2026-10-27 12:00:00', 'UTC'));
    $user = User::factory()->create([
        'is_admin' => false, 'membership_level' => 'Normal', 'timezone' => 'UTC',
        'task_batch' => 3, 'tasks_completed' => 0, 'balance' => 0,
    ]);

    $component = Livewire::actingAs($user)->test(BonusCenter::class)
        ->assertSee('Unlocked')
        ->assertSee('Day 1 reward')
        ->call('claim')
        ->assertDispatched('toast', message: 'Check-in complete. $5.00 added to your balance.');

    expect(DailyCheckIn::query()->sole())
        ->day_number->toBe(1)
        ->reward->toBe(500)
        ->and((int) $user->fresh()->balance)->toBe(500);

    $component->call('claim')
        ->assertDispatched('toast', message: 'You have already checked in today.');

    expect(DailyCheckIn::query()->count())->toBe(1)
        ->and((int) $user->fresh()->balance)->toBe(500);
});

it('pays the level specific fifth day reward then resets the cycle', function () {
    $user = User::factory()->create([
        'is_admin' => false, 'membership_level' => 'Gold', 'timezone' => 'UTC',
        'task_batch' => 3, 'balance' => 0,
    ]);

    foreach (range(1, 4) as $day) {
        DailyCheckIn::create([
            'user_id' => $user->id,
            'checked_in_on' => Carbon::parse('2026-10-22 12:00:00', 'UTC')->addDays($day - 1)->toDateString(),
            'day_number' => $day,
            'reward' => 500,
        ]);
    }

    $this->travelTo(Carbon::parse('2026-10-26 12:00:00', 'UTC'));

    Livewire::actingAs($user)->test(BonusCenter::class)
        ->assertSee('Day 5 reward')
        ->call('claim')
        ->assertDispatched('toast', message: 'Check-in complete. $250.00 added to your balance.');

    expect(DailyCheckIn::query()->latest('id')->first())
        ->day_number->toBe(5)
        ->reward->toBe(25000);

    $this->travelTo(Carbon::parse('2026-10-27 12:00:00', 'UTC'));

    Livewire::actingAs($user->fresh())->test(BonusCenter::class)
        ->assertSee('Day 1 reward')
        ->call('claim')
        ->assertDispatched('toast', message: 'Check-in complete. $5.00 added to your balance.');

    expect(DailyCheckIn::query()->latest('id')->first())
        ->day_number->toBe(1)
        ->reward->toBe(500)
        ->and((int) $user->fresh()->balance)->toBe(25500);
});

it('restarts the streak at day one after a missed day', function () {
    $this->travelTo(Carbon::parse('2026-10-27 12:00:00', 'UTC'));
    $user = User::factory()->create([
        'is_admin' => false, 'membership_level' => 'Silver', 'timezone' => 'UTC',
        'task_batch' => 3, 'balance' => 0,
    ]);
    DailyCheckIn::create([
        'user_id' => $user->id,
        'checked_in_on' => '2026-10-25',
        'day_number' => 3,
        'reward' => 1000,
    ]);

    Livewire::actingAs($user)->test(BonusCenter::class)
        ->call('claim')
        ->assertDispatched('toast');

    expect(DailyCheckIn::query()->latest('id')->first())
        ->day_number->toBe(1)
        ->reward->toBe(500);
});

it('saves bonus center configuration from the admin page', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin)->get('/admin/bonus-center')
        ->assertOk()
        ->assertSee('Bonus Center')
        ->assertSee('Rewards by membership level');

    Livewire::actingAs($admin)->test(AdminBonusCenter::class)
        ->assertSet('setsRequired', 3)
        ->assertSet('cycleDays', 5)
        ->set('setsRequired', 2)
        ->set('rewards.Normal.0', '7.50')
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('toast', message: 'Bonus Center settings saved.');

    $configuration = PlatformSetting::current()->bonusCenter();

    expect($configuration['sets_required'])->toBe(2)
        ->and($configuration['cycle_days'])->toBe(5)
        ->and($configuration['rewards']['Normal'][0])->toBe('7.50')
        ->and($configuration['rewards']['Gold'][4])->toBe('250.00');
});

it('validates admin bonus configuration and forbids regular users', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $user = User::factory()->create(['is_admin' => false]);

    Livewire::actingAs($admin)->test(AdminBonusCenter::class)
        ->set('setsRequired', 0)
        ->call('save')
        ->assertHasErrors('setsRequired');

    Livewire::actingAs($user)->test(AdminBonusCenter::class)->assertForbidden();
});
