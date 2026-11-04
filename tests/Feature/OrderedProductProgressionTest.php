<?php

use App\Livewire\Admin\AssignOrders;
use App\Livewire\Dashboard\Start;
use App\Models\CompletedTask;
use App\Models\MembershipLevel;
use App\Models\Task;
use App\Models\User;
use App\Models\WarehouseProduct;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(fn () => freezeTaskOperatingHours());

afterEach(fn () => unfreezeTaskOperatingHours());

it('delivers products in day and group order for real and training accounts', function (bool $isTraining) {
    MembershipLevel::create(['name' => 'Silver', 'percentage' => '0.75']);

    $user = User::factory()->create([
        'membership_level' => 'Silver',
        'is_training' => $isTraining,
        'balance' => $isTraining ? 0 : 100000,
        'training_balance' => $isTraining ? 100000 : 0,
        'has_made_first_deposit' => ! $isTraining,
        'tasks_completed' => 0,
        'task_batch' => 0,
        'current_day' => 1,
    ]);

    $groups = ['a' => ['A1', 'A2'], 'b' => ['B1', 'B2'], 'c' => ['C1', 'C2']];
    $expected = [];

    foreach ($groups as $group => $names) {
        foreach ($names as $name) {
            WarehouseProduct::factory()->create([
                'level' => 'silver',
                'day' => 1,
                'group_key' => $group,
                'name' => $name,
                'price' => 10,
            ]);
            $expected[] = $name;
        }
    }

    foreach ($expected as $index => $name) {
        $component = Livewire::actingAs($user->fresh())->test(Start::class)
            ->call('startTask')
            ->assertDispatched('open-task-modal');

        $pending = $user->tasks()->where('status', 'pending')->sole();

        expect($pending->title)->toBe($name)
            ->and($pending->warehouse_product_id)->not->toBeNull()
            ->and($user->fresh()->current_day)->toBe(1);

        $component->call('submitTask');

        if ($index < count($expected) - 1) {
            expect($user->fresh()->current_day)->toBe(1);
        }
    }

    expect($user->fresh()->current_day)->toBe(2)
        ->and($user->fresh()->submitted_warehouse_products)->toHaveCount(6);

    Livewire::actingAs($user->fresh())->test(Start::class)
        ->call('startTask')
        ->assertNotDispatched('open-task-modal')
        ->assertDispatched('task-limit-reached', message: 'All warehouse products for your membership level have been completed. Contact support for more products.');
})->with(['real' => false, 'training' => true]);

it('falls back to the legacy task pool in insertion order instead of randomly', function () {
    MembershipLevel::create(['name' => 'Normal', 'percentage' => '0.5']);

    Task::create(['title' => 'First legacy product', 'cost' => 10, 'task_image_path' => '', 'brand' => 'amazon', 'task_level' => 'Normal']);
    Task::create(['title' => 'Second legacy product', 'cost' => 10, 'task_image_path' => '', 'brand' => 'amazon', 'task_level' => 'Normal']);

    $user = User::factory()->create([
        'membership_level' => 'Normal',
        'balance' => 100000,
        'has_made_first_deposit' => true,
        'tasks_completed' => 0,
        'task_batch' => 0,
    ]);

    foreach (['First legacy product', 'Second legacy product'] as $title) {
        $component = Livewire::actingAs($user->fresh())->test(Start::class)
            ->call('startTask')
            ->assertDispatched('open-task-modal');

        expect($user->tasks()->where('status', 'pending')->sole()->title)->toBe($title);

        $component->call('submitTask');
    }
});

it('restarts a fresh five-day cycle after the last day is completed', function () {
    MembershipLevel::create(['name' => 'Silver', 'percentage' => '0.75']);

    $user = User::factory()->create([
        'membership_level' => 'Silver',
        'balance' => 100000,
        'has_made_first_deposit' => true,
        'tasks_completed' => 0,
        'task_batch' => 0,
        'current_day' => 5,
    ]);

    $dayOneProduct = WarehouseProduct::factory()->create([
        'level' => 'silver', 'day' => 1, 'group_key' => 'a', 'name' => 'Cycle Day 1 A', 'price' => 10,
    ]);

    foreach (['a' => 'Cycle Day 5 A', 'b' => 'Cycle Day 5 B', 'c' => 'Cycle Day 5 C'] as $group => $name) {
        WarehouseProduct::factory()->create([
            'level' => 'silver', 'day' => 5, 'group_key' => $group, 'name' => $name, 'price' => 10,
        ]);
    }

    foreach (['Cycle Day 5 A', 'Cycle Day 5 B', 'Cycle Day 5 C'] as $name) {
        $component = Livewire::actingAs($user->fresh())->test(Start::class)->call('startTask');

        expect($user->tasks()->where('status', 'pending')->sole()->title)->toBe($name);

        $component->call('submitTask');
    }

    expect($user->fresh()->current_day)->toBe(1)
        ->and($user->fresh()->submitted_warehouse_products)->toBe([]);

    Livewire::actingAs($user->fresh())->test(Start::class)
        ->call('startTask')
        ->assertDispatched('open-task-modal');

    $pending = $user->tasks()->where('status', 'pending')->sole();

    expect($pending->title)->toBe('Cycle Day 1 A')
        ->and($pending->warehouse_product_id)->toBe($dayOneProduct->product_id);
});

it('queues admin assigned orders in day and group order without shuffling', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $user = User::factory()->create([
        'membership_level' => 'Normal',
        'balance' => 100000,
        'tasks_completed' => 0,
        'products_per_set' => null,
    ]);

    foreach (range(1, 20) as $index) {
        WarehouseProduct::factory()->create([
            'level' => 'normal', 'day' => 1, 'group_key' => 'a',
            'name' => sprintf('A%02d', $index), 'price' => 10,
        ]);
    }

    foreach (range(1, 20) as $index) {
        WarehouseProduct::factory()->create([
            'level' => 'normal', 'day' => 1, 'group_key' => 'b',
            'name' => sprintf('B%02d', $index), 'price' => 10,
        ]);
    }

    Livewire::actingAs($admin)->test(AssignOrders::class)
        ->call('selectUser', $user->id)
        ->set('totalOrders', 30)
        ->call('autoAssign')
        ->assertHasNoErrors();

    $titles = CompletedTask::query()
        ->where('user_id', $user->id)
        ->orderBy('id')
        ->pluck('title')
        ->all();

    expect($titles)->toBe([
        ...array_map(fn (int $index): string => sprintf('A%02d', $index), range(1, 20)),
        ...array_map(fn (int $index): string => sprintf('B%02d', $index), range(1, 10)),
    ]);
});
