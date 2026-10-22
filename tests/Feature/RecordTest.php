<?php

use App\Livewire\Dashboard\Record;
use App\Models\CompletedTask;
use App\Models\MembershipLevel;
use App\Models\User;
use Livewire\Livewire;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->membershipLevel = MembershipLevel::create([
        'name' => 'VIP0',
        'percentage' => 0.5,
    ]);

    $this->user = User::factory()->create([
        'membership_level' => 'VIP0',
        'balance' => 50000,
        'tasks_completed' => 0,
        'daily_commission' => 0,
        'total_commission' => 0,
        'processing_amount' => 0,
    ]);
});

test('record page displays submitted tasks', function () {
    CompletedTask::create([
        'user_id' => $this->user->id,
        'title' => 'Test Product',
        'cost' => 100,
        'task_image_path' => 'tasks/test.jpg',
        'rating' => '',
        'rating_id' => '',
        'status' => 'approved',
    ]);

    Livewire::actingAs($this->user)
        ->test(Record::class)
        ->assertSee('Submission Records')
        ->assertSee('Test Product')
        ->assertSee('$100.00')
        ->assertSee('$0.50')
        ->assertSee('Completed')
        ->assertSee('Approved');
});

test('record page shows empty state when no tasks exist', function () {
    Livewire::actingAs($this->user)
        ->test(Record::class)
        ->assertSee('No submissions yet. Start submitting tasks!');
});

test('record page shows submission statistics', function () {
    CompletedTask::create([
        'user_id' => $this->user->id,
        'title' => 'Approved One',
        'cost' => 100,
        'task_image_path' => 'tasks/test.jpg',
        'rating' => '',
        'rating_id' => '',
        'status' => 'approved',
    ]);

    CompletedTask::create([
        'user_id' => $this->user->id,
        'title' => 'Pending One',
        'cost' => 100,
        'task_image_path' => 'tasks/test.jpg',
        'rating' => '',
        'rating_id' => '',
        'status' => 'pending',
    ]);

    $this->user->update(['total_commission' => 125]);

    Livewire::actingAs($this->user)
        ->test(Record::class)
        ->assertSee('Total Submitted')
        ->assertSee('Pending')
        ->assertSee('Total Earned')
        ->assertSee('$1.25');
});

test('record page paginates completed tasks', function () {
    foreach (range(1, 15) as $i) {
        $task = CompletedTask::create([
            'user_id' => $this->user->id,
            'title' => "Item-{$i}",
            'cost' => 100,
            'task_image_path' => 'tasks/test.jpg',
            'rating' => '',
            'rating_id' => '',
            'status' => 'approved',
        ]);
        $task->forceFill(['created_at' => now()->addMinutes($i)])->save();
    }

    Livewire::actingAs($this->user)
        ->test(Record::class)
        ->assertSee('Item-15')
        ->assertDontSee('Item-5')
        ->call('nextPage')
        ->assertSee('Item-5')
        ->assertDontSee('Item-15');
});

test('record page only shows tasks for the authenticated user', function () {
    $otherUser = User::factory()->create([
        'membership_level' => 'VIP0',
        'balance' => 50000,
        'tasks_completed' => 0,
        'daily_commission' => 0,
        'total_commission' => 0,
        'processing_amount' => 0,
    ]);

    CompletedTask::create([
        'user_id' => $otherUser->id,
        'title' => 'Other User Task',
        'cost' => 200,
        'task_image_path' => 'tasks/test.jpg',
        'rating' => '',
        'rating_id' => '',
        'status' => 'approved',
    ]);

    CompletedTask::create([
        'user_id' => $this->user->id,
        'title' => 'My Task',
        'cost' => 100,
        'task_image_path' => 'tasks/test.jpg',
        'rating' => '',
        'rating_id' => '',
        'status' => 'approved',
    ]);

    Livewire::actingAs($this->user)
        ->test(Record::class)
        ->assertSee('My Task')
        ->assertDontSee('Other User Task');
});

test('record page shows pending tasks as tappable to submit', function () {
    CompletedTask::create([
        'user_id' => $this->user->id,
        'title' => 'Pending Task',
        'cost' => 100,
        'task_image_path' => 'tasks/test.jpg',
        'rating' => '',
        'rating_id' => '',
        'status' => 'pending',
    ]);

    Livewire::actingAs($this->user)
        ->test(Record::class)
        ->assertSee('Pending Task')
        ->assertSee('Tap to submit this product')
        ->assertSeeHtml('wire:click="openSubmission');
});

test('record page shows the pending product alert when opening an existing submission', function () {
    $pendingTask = CompletedTask::create([
        'user_id' => $this->user->id,
        'title' => 'Pending Task',
        'cost' => 100,
        'task_image_path' => 'tasks/test.jpg',
        'rating' => '',
        'rating_id' => '',
        'status' => 'pending',
    ]);

    Livewire::actingAs($this->user)
        ->test(Record::class)
        ->call('openSubmission', $pendingTask->id)
        ->assertSet('showTaskModal', true)
        ->assertSet('showPendingAlert', true)
        ->assertSee('Pending Product');
});

test('record page does not show the pending call to action for approved tasks', function () {
    CompletedTask::create([
        'user_id' => $this->user->id,
        'title' => 'Approved Task',
        'cost' => 100,
        'task_image_path' => 'tasks/test.jpg',
        'rating' => '',
        'rating_id' => '',
        'status' => 'approved',
    ]);

    Livewire::actingAs($this->user)
        ->test(Record::class)
        ->assertSee('Approved Task')
        ->assertDontSeeHtml('wire:click="openSubmission')
        ->assertDontSee('Tap to submit this product');
});

test('submitting the task modal from the records page approves it', function () {
    $completedTask = CompletedTask::create([
        'user_id' => $this->user->id,
        'title' => 'Pending Task',
        'cost' => 100,
        'task_image_path' => 'tasks/test.jpg',
        'rating' => '',
        'rating_id' => '',
        'status' => 'pending',
    ]);

    Livewire::actingAs($this->user)
        ->test(Record::class)
        ->call('openSubmission', $completedTask->id)
        ->assertSet('showTaskModal', true)
        ->set('rating', 5)
        ->set('review', 'Great product')
        ->call('submitTask')
        ->assertSet('showTaskModal', false)
        ->assertNoRedirect()
        ->assertDispatched('task-completed', message: 'Task completed successfully');

    $completedTask->refresh();
    $this->user->refresh();

    expect($completedTask->status)->toBe('approved')
        ->and($completedTask->rating)->toBe('5')
        ->and($completedTask->review)->toBe('Great product')
        ->and($this->user->tasks_completed)->toBe(1)
        ->and($this->user->daily_commission)->toBe(50)
        ->and($this->user->total_commission)->toBe(50)
        ->and($this->user->balance)->toBe(50050);
});

test('record page shows brand logos on pending and approved products', function () {
    CompletedTask::create([
        'user_id' => $this->user->id,
        'title' => 'Pending Product',
        'cost' => 100,
        'task_image_path' => 'tasks/test.jpg',
        'brand' => 'amazon',
        'rating' => '',
        'rating_id' => '',
        'status' => 'pending',
    ]);

    CompletedTask::create([
        'user_id' => $this->user->id,
        'title' => 'Approved Product',
        'cost' => 100,
        'task_image_path' => 'tasks/test.jpg',
        'brand' => 'target',
        'rating' => '',
        'rating_id' => '',
        'status' => 'approved',
    ]);

    Livewire::actingAs($this->user)
        ->test(Record::class)
        ->assertSeeHtml('brands/amazon.png')
        ->assertSeeHtml('brands/target.png');
});
