<?php

use App\Models\UpgradeRequest;
use App\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('guests are redirected to the login page', function () {
    $this->get('/dashboard')->assertRedirect('/login');
});

test('authenticated users can visit the dashboard', function () {
    $this->actingAs($user = User::factory()->create());

    $this->get('/dashboard')->assertStatus(200);
});

test('dashboard footer links to the support email', function () {
    $html = view('components.dashboard.footer')->render();

    expect($html)->toContain('mailto:support@npdigital.work')
        ->and($html)->toContain('support@npdigital.work')
        ->and($html)->not->toContain('sales@npdigital.com');
});

test('user dashboard header includes the google translate widget', function () {
    $this->actingAs(User::factory()->create());

    $html = view('components.layouts.dashboard', [
        'slot' => '',
        'attributes' => new \Illuminate\View\ComponentAttributeBag,
    ])->render();

    expect($html)->toContain('gtranslate_wrapper')
        ->and($html)->toContain('cdn.gtranslate.net/widgets/latest/popup.js')
        ->and($html)->toContain('gtranslateSettings');
});

test('admin overview shows the five newest upgrades and combined transactions with working view all links', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    foreach ([3, 7, 1, 5, 0, 6, 2, 4] as $age) {
        $user = User::factory()->create(['name' => 'Overview Customer '.$age]);
        UpgradeRequest::factory()->for($user)->create([
            'status' => $age % 2 === 0 ? 'approved' : 'pending',
            'created_at' => now()->subHours($age),
        ]);
        $attributes = ['amount' => 12345 + $age, 'status' => 'pending'];
        $transaction = $age % 2 === 0
            ? $user->deposits()->create($attributes + ['cryptocurrency' => 'USDT'])
            : $user->withdrawals()->create($attributes + ['payment_method' => 'TRC20', 'address' => 'TestAddress']);
        $transaction->forceFill([
            'created_at' => now()->subHours($age),
            'status' => $age === 0 ? 'confirmed' : 'pending',
        ])->save();
    }

    $admin->deposits()->create(['amount' => 9900, 'cryptocurrency' => 'ADMIN-ONLY', 'status' => 'pending']);

    $response = $this->actingAs($admin)->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSeeInOrder(array_map(fn (int $age): string => 'Overview Customer '.$age, [...range(0, 4), ...range(0, 4)]))
        ->assertSee('Normal → Silver')
        ->assertSee('approved')
        ->assertSee('confirmed')
        ->assertSee('Deposit #')
        ->assertSee('Withdrawal #')
        ->assertSee('$123.45')
        ->assertSee('No new messages')
        ->assertDontSee('Overview Customer 5')
        ->assertDontSee('Overview Customer 6')
        ->assertDontSee('Overview Customer 7')
        ->assertDontSee('ADMIN-ONLY');

    foreach (['admin.upgrades', 'admin.payments'] as $route) {
        expect($response->getContent())->toMatch('/<a href="'.preg_quote(route($route), '/').'"[^>]*>View All<\/a>/');
        $this->get(route($route))->assertOk();
    }

    expect(substr_count($response->getContent(), 'Overview Customer'))->toBe(10);
});

test('admin overview retains empty states and view all links without any requests or transactions', function () {
    $this->actingAs(User::factory()->create(['is_admin' => true]))
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('No upgrade requests yet')
        ->assertSee('No transactions yet')
        ->assertSee('No new messages')
        ->assertSee(route('admin.upgrades'))
        ->assertSee(route('admin.payments'));
});
