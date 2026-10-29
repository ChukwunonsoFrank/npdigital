<?php

use App\Livewire\Dashboard\Optimize;
use App\Livewire\Dashboard\Start;
use App\Models\CompletedTask;
use App\Models\MembershipLevel;
use App\Models\Task;
use App\Models\User;
use Livewire\Livewire;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->membershipLevel = MembershipLevel::create([
        'name' => 'Normal',
        'percentage' => 0.5,
    ]);

    $this->task = Task::create([
        'title' => 'Test Product',
        'cost' => 100,
        'task_image_path' => 'tasks/test.jpg',
        'brand' => 'walmart',
        'task_level' => 'Normal',
    ]);

    $this->user = User::factory()->create([
        'membership_level' => 'Normal',
        'balance' => 50000,
        'has_made_first_deposit' => true,
        'tasks_completed' => 0,
        'daily_commission' => 0,
        'total_commission' => 0,
        'processing_amount' => 0,
    ]);
});

test('start page displays user balance data', function () {
    Livewire::actingAs($this->user)
        ->test(Start::class)
        ->assertSee('Starting')
        ->assertSee('Balance')
        ->assertSee('On Hold')
        ->assertSee('500.00')
        ->assertSee('0.00')
        ->assertSee('0/3 sets completed today')
        ->assertSee('START TASK');
});

test('start page shows a uniform set size for every membership level', function (string $level) {
    MembershipLevel::firstOrCreate(['name' => $level], ['percentage' => 0.5]);
    $this->user->update(['membership_level' => $level]);

    Livewire::actingAs($this->user->fresh())
        ->test(Start::class)
        ->assertSee('0/3 sets completed today')
        ->assertSee('0 / 30 products')
        ->assertSee('START TASK');
})->with(['Silver', 'Gold', 'Platinum', 'Diamond', 'VVIP']);

test('optimize page displays commission in USDT', function () {
    $completedTask = CompletedTask::create([
        'user_id' => $this->user->id,
        'title' => 'Test Product',
        'cost' => 100,
        'task_image_path' => 'tasks/test.jpg',
        'rating' => '',
        'rating_id' => '',
        'status' => 'pending',
    ]);

    session(['pending_task_id' => $completedTask->id]);

    Livewire::actingAs($this->user)
        ->test(Optimize::class)
        ->assertSee('0.50 USDT');
});

test('clicking start now creates a pending completed task and opens the modal', function () {
    Livewire::actingAs($this->user)
        ->test(Start::class)
        ->call('startTask')
        ->assertSet('showTaskModal', true)
        ->assertNoRedirect();

    $completedTask = CompletedTask::where('user_id', $this->user->id)->first();

    expect($completedTask)->not->toBeNull()
        ->and($completedTask->status)->toBe('pending')
        ->and($completedTask->title)->toBe('Test Product')
        ->and($completedTask->cost)->toBe('100')
        ->and($completedTask->brand)->toBe('walmart');
});

test('submitting the task modal approves the task and dispatches a toast', function () {
    Livewire::actingAs($this->user)
        ->test(Start::class)
        ->call('startTask')
        ->assertSet('showTaskModal', true)
        ->call('setRating', 4)
        ->set('review', 'Great task')
        ->call('submitTask')
        ->assertSet('showTaskModal', false)
        ->assertNoRedirect()
        ->assertDispatched('task-completed', message: 'Task completed successfully');

    $completedTask = CompletedTask::where('user_id', $this->user->id)->first();
    $this->user->refresh();

    expect($completedTask->status)->toBe('approved')
        ->and($completedTask->rating)->toBe('4')
        ->and($completedTask->review)->toBe('Great task')
        ->and($this->user->tasks_completed)->toBe(1)
        ->and($this->user->daily_commission)->toBe(50)
        ->and($this->user->total_commission)->toBe(50)
        ->and($this->user->balance)->toBe(50050);
});

test('start page does not auto open the task modal on load', function () {
    $pendingTask = CompletedTask::create([
        'user_id' => $this->user->id,
        'title' => 'Diamond Tiara Custom',
        'cost' => 66,
        'task_image_path' => 'tasks/test.jpg',
        'rating' => '0',
        'rating_id' => 'abc12345678901',
        'status' => 'pending',
    ]);

    session(['pending_task_id' => $pendingTask->id]);

    Livewire::actingAs($this->user)
        ->test(Start::class)
        ->assertSet('showTaskModal', false);
});

test('start page renders the task modal after starting a task', function () {
    Livewire::actingAs($this->user)
        ->test(Start::class)
        ->call('startTask')
        ->assertSet('showTaskModal', true)
        ->assertSee('Test Product')
        ->assertSee('Cost:')
        ->assertSee('Profit:')
        ->assertSee('Commission:')
        ->assertSee('Rating (optional):')
        ->assertSee('Review (optional):')
        ->assertSee('Submit Task')
        ->assertSeeHtml('brands/walmart.png')
        ->assertDontSee('Pending Product');
});

test('optimize page displays pending task data', function () {
    $completedTask = CompletedTask::create([
        'user_id' => $this->user->id,
        'title' => 'Test Product',
        'cost' => 100,
        'task_image_path' => 'tasks/test.jpg',
        'rating' => '',
        'rating_id' => '',
        'status' => 'pending',
    ]);

    session(['pending_task_id' => $completedTask->id]);

    Livewire::actingAs($this->user)
        ->test(Optimize::class)
        ->assertSee('Test Product')
        ->assertSee('100.00 USDT')
        ->assertSee('0.50 USDT');
});

test('submitting task sets status to approved and increments user data', function () {
    $completedTask = CompletedTask::create([
        'user_id' => $this->user->id,
        'title' => 'Test Product',
        'cost' => 100,
        'task_image_path' => 'tasks/test.jpg',
        'rating' => '',
        'rating_id' => '',
        'status' => 'pending',
    ]);

    session(['pending_task_id' => $completedTask->id]);

    Livewire::actingAs($this->user)
        ->test(Optimize::class)
        ->call('submitTask')
        ->assertRedirect(route('dashboard.start'));

    $completedTask->refresh();
    $this->user->refresh();

    expect($completedTask->status)->toBe('approved')
        ->and($this->user->tasks_completed)->toBe(1)
        ->and($this->user->daily_commission)->toBe(50)
        ->and($this->user->total_commission)->toBe(50)
        ->and($this->user->balance)->toBe(50050);
});

test('start page shows the membership level badge', function () {
    $this->user->update(['membership_level' => 'Diamond']);

    Livewire::actingAs($this->user)
        ->test(Start::class)
        ->assertSee('VIP')
        ->assertSee('Diamond');
});

test('start page dispatches task-completed toast after task submission', function () {
    $completedTask = CompletedTask::create([
        'user_id' => $this->user->id,
        'title' => 'Test Product',
        'cost' => 100,
        'task_image_path' => 'tasks/test.jpg',
        'rating' => '',
        'rating_id' => '',
        'status' => 'pending',
    ]);

    session(['pending_task_id' => $completedTask->id]);

    Livewire::actingAs($this->user)
        ->test(Optimize::class)
        ->call('submitTask')
        ->assertRedirect(route('dashboard.start'))
        ->assertSessionHas('task_completed', 'Task completed successfully');

    Livewire::actingAs($this->user)
        ->test(Start::class)
        ->assertDispatched('task-completed', message: 'Task completed successfully');
});

test('optimize page redirects if no pending task', function () {
    Livewire::actingAs($this->user)
        ->test(Optimize::class)
        ->assertRedirect(route('dashboard.start'));
});

test('starting a task with no tasks for membership level dispatches message', function () {
    $user = User::factory()->create([
        'membership_level' => 'Platinum',
        'balance' => 50000,
        'has_made_first_deposit' => true,
        'tasks_completed' => 0,
        'daily_commission' => 0,
        'total_commission' => 0,
        'processing_amount' => 0,
    ]);

    Livewire::actingAs($user)
        ->test(Start::class)
        ->call('startTask')
        ->assertDispatched('task-limit-reached', message: 'No tasks available for your membership level at this time.');

    expect(CompletedTask::where('user_id', $user->id)->exists())->toBeFalse();
});

test('on hold user at task limit gets extra deposit message', function () {
    $user = User::factory()->create([
        'membership_level' => 'Normal',
        'balance' => 50000,
        'has_made_first_deposit' => true,
        'tasks_completed' => 30,
        'daily_commission' => 0,
        'total_commission' => 0,
        'processing_amount' => 0,
        'lien_status' => 'on_hold',
        'lien_amount' => '750000',
    ]);

    Livewire::actingAs($user)
        ->test(Start::class)
        ->call('startTask')
        ->assertDispatched('task-limit-reached', message: 'You need to make an extra deposit to proceed with completion of tasks.');
});

test('start page shows the on hold amount when the account is on hold', function () {
    $user = User::factory()->create([
        'membership_level' => 'Normal',
        'balance' => 50000,
        'tasks_completed' => 0,
        'daily_commission' => 0,
        'total_commission' => 0,
        'processing_amount' => 0,
        'lien_status' => 'on_hold',
        'lien_amount' => '750000',
    ]);

    Livewire::actingAs($user)
        ->test(Start::class)
        ->assertSee('7,500.00');
});

test('starting a new task with a pending task redirects to the records page', function () {
    CompletedTask::create([
        'user_id' => $this->user->id,
        'title' => 'Existing Pending Task',
        'cost' => 100,
        'task_image_path' => 'tasks/test.jpg',
        'rating' => '',
        'rating_id' => '',
        'status' => 'pending',
    ]);

    Livewire::actingAs($this->user)
        ->test(Start::class)
        ->call('startTask')
        ->assertSet('showTaskModal', false)
        ->assertRedirect(route('dashboard.record'));

    expect(CompletedTask::where('user_id', $this->user->id)->count())->toBe(1);
});

test('user with balance below membership minimum balance gets toast', function () {
    $this->membershipLevel->update(['minimum_balance' => '100000']);

    $user = User::factory()->create([
        'membership_level' => 'Normal',
        'balance' => 50000,
        'has_made_first_deposit' => true,
        'tasks_completed' => 0,
        'daily_commission' => 0,
        'total_commission' => 0,
        'processing_amount' => 0,
    ]);

    Livewire::actingAs($user)
        ->test(Start::class)
        ->call('startTask')
        ->assertDispatched('minimum-balance-required', message: 'The minimum balance for this level is 1,000.00 USDT');
});

test('star rating saves to completed task on submit', function () {
    $completedTask = CompletedTask::create([
        'user_id' => $this->user->id,
        'title' => 'Test Product',
        'cost' => 100,
        'task_image_path' => 'tasks/test.jpg',
        'rating' => '0',
        'rating_id' => 'abc12345678901',
        'status' => 'pending',
    ]);

    session(['pending_task_id' => $completedTask->id]);

    Livewire::actingAs($this->user)
        ->test(Optimize::class)
        ->assertSee('abc12345678901')
        ->assertSet('rating', 0)
        ->call('setRating', 4)
        ->assertSet('rating', 4)
        ->call('setRating', 2)
        ->assertSet('rating', 2)
        ->call('setRating', 2)
        ->assertSet('rating', 0)
        ->call('setRating', 5)
        ->assertSet('rating', 5)
        ->call('submitTask')
        ->assertRedirect(route('dashboard.start'));

    $completedTask->refresh();
    expect($completedTask->rating)->toBe('5');
});

test('user with insufficient balance gets insufficient balance toast', function () {
    $user = User::factory()->create([
        'membership_level' => 'Normal',
        'balance' => 500,
        'has_made_first_deposit' => true,
        'tasks_completed' => 0,
        'daily_commission' => 0,
        'total_commission' => 0,
        'processing_amount' => 0,
    ]);

    Livewire::actingAs($user)
        ->test(Start::class)
        ->call('startTask')
        ->assertDispatched('insufficient-balance', message: 'Insufficient balance to complete task. Top up at least $95.00 to continue.');
});

test('real user spends the wallet balance even when a training balance exists', function () {
    $user = User::factory()->create([
        'membership_level' => 'Normal',
        'balance' => 145040,
        'training_balance' => 2000,
        'has_made_first_deposit' => true,
        'tasks_completed' => 1,
        'task_batch' => 0,
        'daily_commission' => 0,
        'total_commission' => 0,
        'processing_amount' => 0,
    ]);

    Livewire::actingAs($user)
        ->test(Start::class)
        ->call('startTask')
        ->assertNotDispatched('insufficient-balance')
        ->assertSet('showTaskModal', true);
});

test('training account spends the training balance instead of the wallet balance', function () {
    $user = User::factory()->create([
        'membership_level' => 'Normal',
        'is_training' => true,
        'balance' => 145040,
        'training_balance' => 2000,
        'tasks_completed' => 1,
        'task_batch' => 0,
        'daily_commission' => 0,
        'total_commission' => 0,
        'processing_amount' => 0,
    ]);

    Livewire::actingAs($user)
        ->test(Start::class)
        ->call('startTask')
        ->assertSet('showTaskModal', false)
        ->assertDispatched('insufficient-balance', message: 'Insufficient balance to complete task. Top up at least $80.00 to continue.');
});

test('training account with a funded training balance can start a task', function () {
    $user = User::factory()->create([
        'membership_level' => 'Normal',
        'is_training' => true,
        'balance' => 0,
        'training_balance' => 50000,
        'tasks_completed' => 0,
        'task_batch' => 0,
        'daily_commission' => 0,
        'total_commission' => 0,
        'processing_amount' => 0,
    ]);

    Livewire::actingAs($user)
        ->test(Start::class)
        ->call('startTask')
        ->assertNotDispatched('insufficient-balance')
        ->assertSet('showTaskModal', true);
});

test('completing a full task set moves user to next task batch', function () {
    $user = User::factory()->create([
        'membership_level' => 'Normal',
        'balance' => 1000,
        'training_balance' => 50000,
        'has_made_first_deposit' => false,
        'tasks_completed' => 29,
        'task_batch' => 0,
        'daily_commission' => 0,
        'total_commission' => 0,
        'processing_amount' => 0,
    ]);

    $completedTask = CompletedTask::create([
        'user_id' => $user->id,
        'title' => 'Test Product',
        'cost' => 100,
        'task_image_path' => 'tasks/test.jpg',
        'rating' => '',
        'rating_id' => '',
        'status' => 'pending',
    ]);

    session(['pending_task_id' => $completedTask->id]);

    Livewire::actingAs($user)
        ->test(Optimize::class)
        ->call('submitTask')
        ->assertRedirect(route('dashboard.start'));

    $user->refresh();
    expect($user->task_batch)->toBe(1)
        ->and($user->tasks_completed)->toBe(0)
        ->and($user->balance)->toBe(1000)
        ->and($user->training_balance)->toBe(50050);
});

test('user without a first deposit cannot start tasks', function (int $taskBatch) {
    $user = User::factory()->create([
        'membership_level' => 'Normal',
        'balance' => 50000,
        'tasks_completed' => 0,
        'task_batch' => $taskBatch,
        'has_made_first_deposit' => false,
        'daily_commission' => 0,
        'total_commission' => 0,
        'processing_amount' => 0,
    ]);

    Livewire::actingAs($user)
        ->test(Start::class)
        ->call('startTask')
        ->assertSet('showTaskModal', false)
        ->assertDispatched('deposit-required', message: 'You need to make your first deposit of $100 before you can start tasks.');

    expect(CompletedTask::where('user_id', $user->id)->exists())->toBeFalse();
})->with([0, 1]);

test('user who has made first deposit can continue later batches', function () {
    $user = User::factory()->create([
        'membership_level' => 'Normal',
        'balance' => 50000,
        'tasks_completed' => 0,
        'task_batch' => 1,
        'has_made_first_deposit' => true,
        'daily_commission' => 0,
        'total_commission' => 0,
        'processing_amount' => 0,
    ]);

    Livewire::actingAs($user)
        ->test(Start::class)
        ->call('startTask')
        ->assertSet('showTaskModal', true)
        ->assertNoRedirect();

    expect(CompletedTask::where('user_id', $user->id)->exists())->toBeTrue();
});

test('user who has completed two task batches can start the third set', function () {
    $user = User::factory()->create([
        'membership_level' => 'Normal',
        'balance' => 50000,
        'tasks_completed' => 0,
        'task_batch' => 2,
        'has_made_first_deposit' => true,
        'daily_commission' => 0,
        'total_commission' => 0,
        'processing_amount' => 0,
    ]);

    Livewire::actingAs($user)
        ->test(Start::class)
        ->call('startTask')
        ->assertSet('showTaskModal', true)
        ->assertNoRedirect();

    expect(CompletedTask::where('user_id', $user->id)->exists())->toBeTrue();
});

test('user who has completed three task batches is blocked until reset', function () {
    $user = User::factory()->create([
        'membership_level' => 'Normal',
        'balance' => 50000,
        'tasks_completed' => 0,
        'task_batch' => 3,
        'has_made_first_deposit' => true,
        'daily_commission' => 0,
        'total_commission' => 0,
        'processing_amount' => 0,
    ]);

    Livewire::actingAs($user)
        ->test(Start::class)
        ->call('startTask')
        ->assertDispatched('task-limit-reached', message: 'Task limit reached. Come back tommorrow for more tasks.');
});

test('user with a required upgrade cannot start tasks', function () {
    MembershipLevel::create(['name' => 'Diamond', 'percentage' => '1.50']);

    $user = User::factory()->create([
        'membership_level' => 'Normal',
        'required_upgrade_level' => 'Diamond',
        'balance' => 50000,
        'has_made_first_deposit' => true,
        'tasks_completed' => 0,
        'daily_commission' => 0,
        'total_commission' => 0,
        'processing_amount' => 0,
    ]);

    Livewire::actingAs($user)
        ->test(Start::class)
        ->call('startTask')
        ->assertSet('showTaskModal', false)
        ->assertDispatched('upgrade-required', message: 'You must upgrade to Diamond before you can submit tasks.');

    expect(CompletedTask::where('user_id', $user->id)->exists())->toBeFalse();
});

test('user with a required upgrade cannot submit a pending task', function () {
    MembershipLevel::create(['name' => 'Diamond', 'percentage' => '1.50']);

    $user = User::factory()->create([
        'membership_level' => 'Normal',
        'required_upgrade_level' => 'Diamond',
        'balance' => 50000,
        'tasks_completed' => 0,
        'daily_commission' => 0,
        'total_commission' => 0,
        'processing_amount' => 0,
    ]);

    $completedTask = CompletedTask::create([
        'user_id' => $user->id,
        'title' => 'Test Product',
        'cost' => 100,
        'task_image_path' => 'tasks/test.jpg',
        'rating' => '',
        'rating_id' => '',
        'status' => 'pending',
    ]);

    session(['pending_task_id' => $completedTask->id]);

    Livewire::actingAs($user)
        ->test(Start::class)
        ->call('submitTask')
        ->assertSet('showTaskModal', false)
        ->assertDispatched('upgrade-required', message: 'You must upgrade to Diamond before you can submit tasks.');

    expect($completedTask->fresh()->status)->toBe('pending')
        ->and($user->fresh()->tasks_completed)->toBe(0)
        ->and($user->fresh()->balance)->toBe(50000);
});

test('optimize page blocks submission while an upgrade is required', function () {
    MembershipLevel::create(['name' => 'Diamond', 'percentage' => '1.50']);

    $user = User::factory()->create([
        'membership_level' => 'Normal',
        'required_upgrade_level' => 'Diamond',
        'balance' => 50000,
        'tasks_completed' => 0,
        'daily_commission' => 0,
        'total_commission' => 0,
        'processing_amount' => 0,
    ]);

    $completedTask = CompletedTask::create([
        'user_id' => $user->id,
        'title' => 'Test Product',
        'cost' => 100,
        'task_image_path' => 'tasks/test.jpg',
        'rating' => '',
        'rating_id' => '',
        'status' => 'pending',
    ]);

    session(['pending_task_id' => $completedTask->id]);

    Livewire::actingAs($user)
        ->test(Optimize::class)
        ->call('submitTask')
        ->assertNoRedirect()
        ->assertDispatched('upgrade-required', message: 'You must upgrade to Diamond before you can submit tasks.');

    expect($completedTask->fresh()->status)->toBe('pending');
});

test('user who upgraded to the required level can start and submit tasks', function () {
    MembershipLevel::create(['name' => 'Silver', 'percentage' => '0.75']);
    MembershipLevel::create(['name' => 'Gold', 'percentage' => '1.00']);

    $user = User::factory()->create([
        'membership_level' => 'Gold',
        'required_upgrade_level' => 'Silver',
        'balance' => 50000,
        'has_made_first_deposit' => true,
        'tasks_completed' => 0,
        'daily_commission' => 0,
        'total_commission' => 0,
        'processing_amount' => 0,
    ]);

    Task::create([
        'title' => 'Gold Product',
        'cost' => 100,
        'task_image_path' => 'tasks/test.jpg',
        'task_level' => 'Gold',
    ]);

    Livewire::actingAs($user)
        ->test(Start::class)
        ->call('startTask')
        ->assertSet('showTaskModal', true)
        ->call('submitTask')
        ->assertSet('showTaskModal', false)
        ->assertDispatched('task-completed', message: 'Task completed successfully');

    $completedTask = CompletedTask::where('user_id', $user->id)->first();

    expect($completedTask->status)->toBe('approved')
        ->and($user->fresh()->tasks_completed)->toBe(1);
});
