<?php

use App\Livewire\Admin\Payments;
use App\Models\Deposit;
use App\Models\User;
use App\Models\Withdrawal;
use App\Notifications\TransactionReviewed;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('renders the payment management page and links to it from the admin sidebar', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin)
        ->get(route('admin.payments'))
        ->assertOk()
        ->assertSee('Payment Management')
        ->assertSee('Search users...')
        ->assertSee('No pending transactions')
        ->assertSee(route('admin.payments'));
});

it('separates pending payments from completed deposit and withdrawal history', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $user = User::factory()->create(['name' => 'Payment Customer']);

    Deposit::create([
        'user_id' => $user->id,
        'cryptocurrency' => 'USDT',
        'amount' => 150_00,
        'status' => 'pending',
    ]);
    Withdrawal::create([
        'user_id' => $user->id,
        'amount' => 100_00,
        'payment_method' => 'TRC20',
        'address' => 'TPendingAddressForTesting',
        'status' => 'pending',
    ]);
    Deposit::create([
        'user_id' => $user->id,
        'cryptocurrency' => 'BTC',
        'amount' => 250_00,
        'status' => 'confirmed',
    ]);
    Withdrawal::create([
        'user_id' => $user->id,
        'amount' => 300_00,
        'payment_method' => 'TRC20',
        'address' => 'TXyzAddressForTesting',
        'status' => 'completed',
    ]);

    Livewire::actingAs($admin)
        ->test(Payments::class)
        ->assertSee('4 Total')
        ->assertSee('Deposit #1')
        ->assertSee('Withdrawal #1')
        ->assertDontSee('Deposit #2')
        ->call('selectStatus', 'completed')
        ->assertSee('4 Total')
        ->assertSee('Deposit #2')
        ->call('selectHistoryType', 'withdrawals')
        ->assertSee('Withdrawal #2');
});

it('reviews pending payments once and emails the customer the transaction outcome', function (string $type, bool $approve, string $status, int $balance, int $processing, string $explanation) {
    Notification::fake();
    $admin = User::factory()->create(['is_admin' => true]);
    $user = User::factory()->create(['balance' => 80_000, 'processing_amount' => 30_000]);
    $transaction = $type === 'Deposit'
        ? $user->deposits()->create(['amount' => 12_345, 'cryptocurrency' => 'USDT-TRC20', 'status' => 'pending'])
        : $user->withdrawals()->create(['amount' => 12_345, 'payment_method' => 'USDT-TRC20', 'address' => 'TTestAddress', 'status' => 'pending']);

    $component = Livewire::actingAs($admin)->test(Payments::class)
        ->assertSee('Actions for '.$type.' #'.$transaction->id)
        ->call('review', $type, $transaction->id, $approve)
        ->assertHasNoErrors()
        ->assertDontSee('Actions for '.$type.' #'.$transaction->id)
        ->call('selectStatus', 'completed')
        ->call('selectHistoryType', $type === 'Deposit' ? 'deposits' : 'withdrawals')
        ->assertSee(ucfirst($status));

    expect($transaction->fresh()->status)->toBe($status)
        ->and((int) $user->fresh()->balance)->toBe($balance)
        ->and((int) $user->fresh()->processing_amount)->toBe($processing)
        ->and($user->fresh()->has_made_first_deposit)->toBe($type === 'Deposit' && $approve);

    Notification::assertSentTo($user, TransactionReviewed::class, function ($notification, $channels) use ($user, $type, $approve, $transaction, $explanation) {
        $mail = $notification->toMail($user);
        expect($channels)->toBe(['mail'])
            ->and($notification->afterCommit)->toBeTrue()
            ->and($mail->subject)->toBe($type.($approve ? ' Approved' : ' Declined').' — Transaction #'.$transaction->id)
            ->and(implode(' ', $mail->introLines))->toContain('$123.45', 'USDT-TRC20', $explanation)
            ->and($mail->actionUrl)->toBe(route('dashboard.wallet'));

        $html = (string) $mail->render();
        expect($html)->toContain('NP Digital', '$123.45', $explanation)
            ->not->toContain('Voldex', 'voldexglobal.com');

        return true;
    });

    $component->call('review', $type, $transaction->id, $approve)->assertHasErrors('transaction')
        ->call('review', $type, $transaction->id, ! $approve)->assertHasErrors('transaction');

    Notification::assertSentToTimes($user, TransactionReviewed::class, 1);
    expect((int) $user->fresh()->balance)->toBe($balance)
        ->and((int) $user->fresh()->processing_amount)->toBe($processing)
        ->and($transaction->fresh()->status)->toBe($status);
})->with([
    'approved deposit' => ['Deposit', true, 'confirmed', 92_345, 30_000, 'credited to your account balance'],
    'declined deposit' => ['Deposit', false, 'declined', 80_000, 30_000, 'No funds have been added'],
    'approved withdrawal' => ['Withdrawal', true, 'completed', 67_655, 17_655, 'marked as completed'],
    'declined withdrawal' => ['Withdrawal', false, 'declined', 80_000, 17_655, 'pending hold has been released'],
]);

it('does not approve a withdrawal without sufficient funds', function () {
    Notification::fake();
    $admin = User::factory()->create(['is_admin' => true]);
    $user = User::factory()->create(['balance' => 9_999, 'processing_amount' => 10_000]);
    $withdrawal = $user->withdrawals()->create(['amount' => 10_000, 'payment_method' => 'USDT', 'address' => 'TTestAddress', 'status' => 'pending']);

    Livewire::actingAs($admin)->test(Payments::class)
        ->call('review', 'Withdrawal', $withdrawal->id, true)
        ->assertHasErrors('transaction');

    expect($withdrawal->fresh()->status)->toBe('pending')
        ->and((int) $user->fresh()->balance)->toBe(9_999)
        ->and((int) $user->fresh()->processing_amount)->toBe(10_000);
    Notification::assertNothingSent();
});

it('only allows administrators to access payments', function () {
    Livewire::actingAs(User::factory()->create(['is_admin' => false]))
        ->test(Payments::class)->assertForbidden();
});

it('unlocks withdrawals only for an approved deposit of at least 100 dollars', function (int $amount, bool $unlocked) {
    Notification::fake();
    $admin = User::factory()->create(['is_admin' => true]);
    $user = User::factory()->create(['balance' => 1_000]);
    $deposit = $user->deposits()->create(['amount' => $amount, 'cryptocurrency' => 'USDT', 'status' => 'pending']);

    Livewire::actingAs($admin)->test(Payments::class)->call('review', 'Deposit', $deposit->id, true)->assertHasNoErrors();

    expect($user->fresh()->has_made_first_deposit)->toBe($unlocked)
        ->and((int) $user->fresh()->balance)->toBe(1_000 + $amount);
})->with([[9_999, false], [10_000, true]]);

it('reduces a user negative balance when a deposit is approved', function () {
    Notification::fake();
    $admin = User::factory()->create(['is_admin' => true]);
    $user = User::factory()->create(['balance' => 5000, 'negative_balance' => 3000]);
    $deposit = $user->deposits()->create([
        'amount' => 3000, 'cryptocurrency' => 'USDT-TRC20', 'status' => 'pending',
    ]);

    Livewire::actingAs($admin)->test(Payments::class)
        ->call('review', 'Deposit', $deposit->id, true)
        ->assertHasNoErrors();

    expect((int) $user->fresh()->balance)->toBe(8000)
        ->and((int) $user->fresh()->negative_balance)->toBe(0)
        ->and($deposit->fresh()->status)->toBe('confirmed');
});
