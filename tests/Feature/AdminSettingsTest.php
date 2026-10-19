<?php

use App\Models\PlatformSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Volt\Volt;
use Symfony\Component\HttpKernel\Exception\HttpException;

uses(RefreshDatabase::class);

it('shows all platform settings in reference order and links the admin navigation', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin)->get('/admin/settings')
        ->assertOk()
        ->assertSee('<title>Platform Settings — NP Digital</title>', false)
        ->assertSee('md:bottom-6! md:left-auto md:right-6', false)
        ->assertSee('Payment methods and platform configuration.')
        ->assertSeeInOrder([
            'Maintenance Mode',
            'Allow New Registrations',
            'Registration Bonus',
            'Deposit Wallet Addresses',
            'WhatsApp Support Number',
            'Telegram Support',
            'Activities Management',
            'Business Licenses',
        ])
        ->assertSee('href="'.route('admin.settings').'"', false)
        ->assertSee('aria-label="Back to overview"', false)
        ->assertSee('$10.00 registration bonus')
        ->assertSee('Every newly registered account receives a one-time $10.00 welcome bonus.')
        ->assertSee('Not configured')
        ->assertDontSee('Carousel Posters')
        ->assertDontSee('Events & Rewards')
        ->assertDontSee('Testimonial Photos')
        ->assertDontSee('Terms & Conditions')
        ->assertDontSee('FAQ')
        ->assertDontSee('About Page');
});

it('restricts platform settings to administrators', function () {
    $this->get('/admin/settings')->assertRedirect(route('login'));

    $user = User::factory()->create(['is_admin' => false]);
    $this->withoutExceptionHandling();
    $this->actingAs($user);
    expect(fn () => $this->get('/admin/settings'))->toThrow(HttpException::class);
    expect(fn () => Volt::actingAs($user)->test('admin.settings'))->toThrow(HttpException::class);
});

it('saves both switches independently and restores them on a new page visit', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $component = Volt::actingAs($admin)->test('admin.settings')
        ->assertSet('maintenanceMode', false)->assertSet('allowRegistrations', true);
    $otherPage = Volt::actingAs($admin)->test('admin.settings');

    $component->set('maintenanceMode', true)->assertHasNoErrors()
        ->assertDispatched('notify', message: 'Maintenance mode enabled.', type: 'success');
    $otherPage->set('allowRegistrations', false)->assertHasNoErrors()
        ->assertDispatched('notify', message: 'New registrations disabled.', type: 'success');

    Volt::actingAs($admin)->test('admin.settings')
        ->assertSet('maintenanceMode', true)->assertSet('allowRegistrations', false)
        ->set('maintenanceMode', false)->assertHasNoErrors()
        ->assertDispatched('notify', message: 'Maintenance mode disabled.', type: 'success')
        ->set('allowRegistrations', true)->assertHasNoErrors()
        ->assertDispatched('notify', message: 'New registrations enabled.', type: 'success');

    expect(PlatformSetting::current()->maintenance_mode)->toBeFalse()
        ->and(PlatformSetting::current()->allow_registrations)->toBeTrue()
        ->and(PlatformSetting::query()->count())->toBe(1)
        ->and($admin->fresh()->balance)->toBe($admin->balance);
});

it('rejects switch changes after administrator access is removed', function () {
    $this->withoutExceptionHandling();
    $admin = User::factory()->create(['is_admin' => true]);
    $component = Volt::actingAs($admin)->test('admin.settings');
    $admin->update(['is_admin' => false]);

    expect(fn () => $component->set('maintenanceMode', true))->toThrow(HttpException::class);
    expect(PlatformSetting::current()->maintenance_mode)->toBeFalse();
});

it('blocks regular users during maintenance but keeps admin controls login and logout available', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $user = User::factory()->create(['is_admin' => false, 'is_banned' => false]);
    Volt::actingAs($admin)->test('admin.settings')->set('maintenanceMode', true);

    $this->actingAs($admin)->get('/admin/settings')->assertOk();
    $this->actingAs($user)->get('/dashboard')->assertServiceUnavailable();
    $this->actingAs($user)->post('/livewire/update')->assertServiceUnavailable();
    $this->withSession(['view_as_admin_id' => $admin->id, 'view_as_user_id' => $user->id])
        ->post('/view-as/exit')->assertRedirect(route('admin.dashboard.users'));
    $this->assertAuthenticatedAs($admin);
    $this->actingAs($user)->post('/logout')->assertRedirect('/login');
    $this->get('/login')->assertOk();

    Volt::actingAs($admin)->test('admin.settings')->set('maintenanceMode', false)->assertHasNoErrors();
    $this->actingAs($user)->get('/dashboard')->assertOk();
});

it('edits and saves all dashboard cryptocurrencies without changing switches or user wallets', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $userWallet = $admin->wallets()->create(['label' => 'Personal', 'cryptocurrency' => 'BTC', 'address' => 'personal-address']);
    $addresses = [
        'BTC' => 'bc1qxy2kgdygjrsqtzq2n0yrf2493p83kkfjhx0wlh',
        'ETH' => '0x71C7656EC7ab88b098defB751B7401B5f6d8976F',
        'USDT-TRC20' => 'TN2YqU6VPnMBKbp6GbZKpNcAqJx7ZwGh9A',
        'USDT-ERC20' => '0x1111111111111111111111111111111111111111',
        'BNB' => '0x2222222222222222222222222222222222222222',
        'SOL' => 'solana-address-123',
        'XRP' => 'xrp-address-456',
        'DOGE' => 'dogecoin-address-789',
        'LTC' => 'litecoin-address-123',
        'TRX' => 'tron-address-456',
    ];
    $component = Volt::actingAs($admin)->test('admin.settings')->assertSet('editingWalletAddresses', false)
        ->call('editWalletAddresses')->assertSet('editingWalletAddresses', true)
        ->assertSet('walletAddresses', array_fill_keys(array_keys($addresses), ''))
        ->set('walletAddresses', $addresses);

    expect(PlatformSetting::current()->deposit_wallet_addresses)->toBeNull();
    Volt::actingAs($admin)->test('admin.settings')->set('allowRegistrations', false);

    $component->call('saveWalletAddresses')->assertHasNoErrors()->assertSet('editingWalletAddresses', false)
        ->assertDispatched('notify', message: 'Deposit wallet addresses saved.', type: 'success');

    expect(PlatformSetting::current()->deposit_wallet_addresses)->toBe($addresses)
        ->and(PlatformSetting::current()->allow_registrations)->toBeFalse()
        ->and(PlatformSetting::current()->maintenance_mode)->toBeFalse()
        ->and($userWallet->fresh()->address)->toBe('personal-address');

    $page = Volt::actingAs($admin)->test('admin.settings')->assertSet('editingWalletAddresses', false);
    foreach ($addresses as $address) {
        $page->assertSee($address);
    }
    $page->call('editWalletAddresses')->assertSet('walletAddresses', $addresses);
});

it('does not save invalid wallet addresses or show a success toast', function (string $code, mixed $value, string $error) {
    $admin = User::factory()->create(['is_admin' => true]);
    $addresses = ['BTC' => 'bitcoin123', 'ETH' => 'ethereum456', 'USDT-TRC20' => 'tron789', 'USDT-ERC20' => 'erc20123', 'BNB' => 'bnb456', 'SOL' => 'sol123', 'XRP' => 'xrp456', 'DOGE' => 'doge789', 'LTC' => 'ltc123', 'TRX' => 'trx456'];
    PlatformSetting::factory()->create(['deposit_wallet_addresses' => $addresses]);

    Volt::actingAs($admin)->test('admin.settings')->call('editWalletAddresses')
        ->set('walletAddresses.'.$code, $value)->call('saveWalletAddresses')
        ->assertHasErrors([$error])->assertSet('editingWalletAddresses', true)->assertNotDispatched('notify');

    expect(PlatformSetting::current()->deposit_wallet_addresses)->toBe($addresses);
})->with([
    'empty address' => ['BTC', '', 'walletAddresses.BTC'],
    'non-string address' => ['ETH', ['invalid'], 'walletAddresses.ETH'],
    'overlong address' => ['USDT-ERC20', str_repeat('a', 256), 'walletAddresses.USDT-ERC20'],
    'embedded whitespace' => ['USDT-TRC20', 'tron address', 'walletAddresses.USDT-TRC20'],
    'unexpected currency' => ['OTHER', 'unexpected', 'walletAddresses'],
]);

it('rejects wallet editing and saving after administrator access is removed', function (string $action) {
    $this->withoutExceptionHandling();
    $admin = User::factory()->create(['is_admin' => true]);
    $component = Volt::actingAs($admin)->test('admin.settings')->call('editWalletAddresses');
    $admin->update(['is_admin' => false]);

    expect(fn () => $component->call($action))->toThrow(HttpException::class);
    expect(PlatformSetting::current()->deposit_wallet_addresses)->toBeNull();
})->with(['editWalletAddresses', 'saveWalletAddresses']);

it('saves support contacts independently and restores saved values when editing', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    PlatformSetting::factory()->create(['deposit_wallet_addresses' => ['BTC' => 'existing-address']]);
    $whatsapp = Volt::actingAs($admin)->test('admin.settings')->assertSee('Not set')
        ->call('editWhatsapp')->assertSet('editingWhatsapp', true)->assertSet('whatsappNumber', '');
    $telegram = Volt::actingAs($admin)->test('admin.settings')->call('editTelegram');

    $whatsapp->set('whatsappNumber', '+442079460123')->call('saveWhatsapp')->assertHasNoErrors()
        ->assertSet('editingWhatsapp', false)
        ->assertDispatched('notify', message: 'WhatsApp support number saved.', type: 'success');
    $telegram->set('telegramUsername', 'support_bot')->call('saveTelegram')->assertHasNoErrors()
        ->assertSet('editingTelegram', false)
        ->assertDispatched('notify', message: 'Telegram support username saved.', type: 'success');

    expect(PlatformSetting::current()->whatsapp_support_number)->toBe('+442079460123')
        ->and(PlatformSetting::current()->telegram_support_username)->toBe('support_bot')
        ->and(PlatformSetting::current()->deposit_wallet_addresses)->toBe(['BTC' => 'existing-address'])
        ->and(PlatformSetting::current()->allow_registrations)->toBeTrue();

    Volt::actingAs($admin)->test('admin.settings')->assertSee('+442079460123')->assertSee('support_bot')
        ->call('editWhatsapp')->assertSet('whatsappNumber', '+442079460123')
        ->call('editTelegram')->assertSet('telegramUsername', 'support_bot');
});

it('rejects invalid support contacts without saving or showing success', function (string $property, string $value, string $action) {
    $admin = User::factory()->create(['is_admin' => true]);
    PlatformSetting::factory()->create(['whatsapp_support_number' => '+1234567890', 'telegram_support_username' => 'support_bot']);

    Volt::actingAs($admin)->test('admin.settings')
        ->set($property, $value)->call($action)->assertHasErrors($property)->assertNotDispatched('notify');

    expect(PlatformSetting::current()->whatsapp_support_number)->toBe('+1234567890')
        ->and(PlatformSetting::current()->telegram_support_username)->toBe('support_bot');
})->with([
    'empty phone' => ['whatsappNumber', '', 'saveWhatsapp'],
    'missing country prefix' => ['whatsappNumber', '1234567890', 'saveWhatsapp'],
    'phone with spaces' => ['whatsappNumber', '+1 234567890', 'saveWhatsapp'],
    'overlong phone' => ['whatsappNumber', '+1234567890123456', 'saveWhatsapp'],
    'empty username' => ['telegramUsername', '', 'saveTelegram'],
    'username with at sign' => ['telegramUsername', '@support_bot', 'saveTelegram'],
    'username URL' => ['telegramUsername', 'https://t.me/support_bot', 'saveTelegram'],
    'short username' => ['telegramUsername', 'abcd', 'saveTelegram'],
    'overlong username' => ['telegramUsername', str_repeat('a', 33), 'saveTelegram'],
]);

it('rejects support contact actions after administrator access is removed', function (string $action) {
    $this->withoutExceptionHandling();
    $admin = User::factory()->create(['is_admin' => true]);
    $component = Volt::actingAs($admin)->test('admin.settings');
    $admin->update(['is_admin' => false]);

    expect(fn () => $component->call($action))->toThrow(HttpException::class);
    expect(PlatformSetting::current()->whatsapp_support_number)->toBeNull()
        ->and(PlatformSetting::current()->telegram_support_username)->toBeNull();
})->with(['editWhatsapp', 'saveWhatsapp', 'editTelegram', 'saveTelegram']);

it('saves check-in milestones and terms and displays them on the user activities page', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $user = User::factory()->create(['is_admin' => false]);
    PlatformSetting::factory()->create(['whatsapp_support_number' => '+1234567890']);

    Volt::actingAs($admin)->test('admin.settings')->assertSee('Check-in Day 3')
        ->call('editCheckIn')->assertSet('editingCheckIn', true)
        ->set('checkInMilestones', [['day' => 14, 'reward' => '1250.75'], ['day' => 2, 'reward' => '60']])
        ->set('checkInTerms', "First custom condition.\nSecond custom condition.")
        ->call('saveCheckIn')->assertHasNoErrors()->assertSet('editingCheckIn', false)
        ->assertDispatched('notify', message: 'Check-in program saved.', type: 'success');

    expect(PlatformSetting::current()->whatsapp_support_number)->toBe('+1234567890');
    \Livewire\Livewire::actingAs($user)->test(\App\Livewire\Dashboard\Activities::class)
        ->assertSeeInOrder(['Day 2', '$60', 'Day 14', '$1,250.75'])
        ->assertDontSee('Day 30')->assertSee('First custom condition.')->assertSee('Second custom condition.');
    Volt::actingAs($admin)->test('admin.settings')->call('editCheckIn')
        ->assertSet('checkInMilestones', [['day' => 2, 'reward' => '60'], ['day' => 14, 'reward' => '1250.75']]);
});

it('adds and removes draft milestones and discards them on cancel', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    Volt::actingAs($admin)->test('admin.settings')->call('editCheckIn')
        ->call('removeCheckInMilestone', 1)->assertSet('checkInMilestones.1.day', 15)
        ->call('addCheckInMilestone')->assertSet('checkInMilestones.3.day', '')
        ->set('checkInTerms', 'Unsaved terms')->call('cancelCheckIn')->assertSet('editingCheckIn', false)
        ->call('editCheckIn')->assertSet('checkInMilestones.1.day', 5)
        ->assertSet('checkInMilestones.3.day', 30);

    expect(PlatformSetting::current()->check_in_program)->toBeNull();
});

it('rejects invalid check-in settings without saving', function (array $milestones, string $terms, string $error) {
    $admin = User::factory()->create(['is_admin' => true]);
    Volt::actingAs($admin)->test('admin.settings')->call('editCheckIn')
        ->set('checkInMilestones', $milestones)->set('checkInTerms', $terms)
        ->call('saveCheckIn')->assertHasErrors($error)->assertNotDispatched('notify');

    expect(PlatformSetting::current()->check_in_program)->toBeNull();
})->with([
    'no milestones' => [[], 'Terms', 'checkInMilestones'],
    'duplicate days' => [[['day' => 3, 'reward' => 100], ['day' => '3', 'reward' => 200]], 'Terms', 'checkInMilestones.0.day'],
    'zero day' => [[['day' => 0, 'reward' => 100]], 'Terms', 'checkInMilestones.0.day'],
    'fractional day' => [[['day' => 1.5, 'reward' => 100]], 'Terms', 'checkInMilestones.0.day'],
    'negative reward' => [[['day' => 3, 'reward' => -1]], 'Terms', 'checkInMilestones.0.reward'],
    'excess precision' => [[['day' => 3, 'reward' => '1.005']], 'Terms', 'checkInMilestones.0.reward'],
    'missing terms' => [[['day' => 3, 'reward' => 100]], '', 'checkInTerms'],
]);

it('saves the membership chart for the user dashboard without changing financial membership records', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $user = User::factory()->create(['is_admin' => false]);
    $this->seed(\Database\Seeders\MembershipLevelSeeder::class);
    $levels = \App\Models\MembershipLevel::all()->toArray();
    PlatformSetting::factory()->create(['whatsapp_support_number' => '+1234567890']);

    Volt::actingAs($admin)->test('admin.settings')->call('editMembershipChart')
        ->set('membershipChartRows.1.name', 'Silver Plus')
        ->set('membershipChartRows.1.deposit', '$425.50')
        ->set('membershipChartRows.1.tasks', 37)
        ->set('membershipChartRows.1.commission', '0.85%')
        ->call('saveMembershipChart')->assertHasNoErrors()->assertSet('editingMembershipChart', false)
        ->assertDispatched('notify', message: 'Membership level chart saved.', type: 'success');

    Volt::actingAs($admin)->test('admin.settings')->call('editMembershipChart')
        ->assertSet('membershipChartRows.1.name', 'Silver Plus')
        ->assertSet('membershipChartRows.1.deposit', '$425.50')
        ->assertSet('membershipChartRows.1.tasks', 37)
        ->assertSet('membershipChartRows.1.commission', '0.85%')
        ->set('membershipChartRows.1.name', 'Discarded draft')->call('cancelMembershipChart')
        ->assertSet('editingMembershipChart', false)->call('editMembershipChart')
        ->assertSet('membershipChartRows.1.name', 'Silver Plus');

    \Livewire\Livewire::actingAs($user)->test(\App\Livewire\Dashboard\Activities::class)
        ->assertSeeInOrder(['Silver Plus', '$425.50', '37', '0.85%']);
    expect(\App\Models\MembershipLevel::all()->toArray())->toBe($levels)
        ->and(PlatformSetting::current()->whatsapp_support_number)->toBe('+1234567890')
        ->and(PlatformSetting::current()->check_in_program)->toBeNull();
});

it('rejects invalid membership chart fields without replacing the saved chart', function (string $field, mixed $value) {
    $admin = User::factory()->create(['is_admin' => true]);
    $saved = PlatformSetting::current()->membershipChart();
    PlatformSetting::factory()->create(['membership_chart' => $saved]);

    Volt::actingAs($admin)->test('admin.settings')->call('editMembershipChart')
        ->set('membershipChartRows.1.'.$field, $value)->call('saveMembershipChart')
        ->assertHasErrors('membershipChartRows.1.'.$field)->assertNotDispatched('notify');

    expect(PlatformSetting::current()->membershipChart())->toBe($saved);
})->with([
    'empty name' => ['name', ''],
    'duplicate name' => ['name', 'Normal'],
    'empty deposit' => ['deposit', ''],
    'negative tasks' => ['tasks', -1],
    'fractional tasks' => ['tasks', 2.5],
    'empty commission' => ['commission', ''],
]);

it('denies membership chart actions after administrator access is revoked', function (string $action) {
    $this->withoutExceptionHandling();
    $admin = User::factory()->create(['is_admin' => true]);
    $component = Volt::actingAs($admin)->test('admin.settings');
    $admin->update(['is_admin' => false]);

    expect(fn () => $component->call($action))->toThrow(HttpException::class);
    expect(PlatformSetting::current()->membership_chart)->toBeNull();
})->with(['editMembershipChart', 'saveMembershipChart', 'cancelMembershipChart']);

it('edits adds and removes membership perks and displays saved content on the correct user cards', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $user = User::factory()->create(['is_admin' => false]);
    $this->seed(\Database\Seeders\MembershipLevelSeeder::class);
    $levels = \App\Models\MembershipLevel::all()->toArray();
    PlatformSetting::factory()->create(['telegram_support_username' => 'support_bot']);

    Volt::actingAs($admin)->test('admin.settings')->call('editMembershipPerks')
        ->set('membershipPerkDraft.Silver.0', 'Silver-specific access')
        ->call('removeMembershipPerk', 'Silver', 1)
        ->call('addMembershipPerk', 'Gold')->set('membershipPerkDraft.Gold.4', 'Gold-only priority support')
        ->set('membershipPerkDraft.Normal', [])
        ->call('saveMembershipPerks')->assertHasNoErrors()->assertSet('editingMembershipPerks', false)
        ->assertDispatched('notify', message: 'Membership levels saved.', type: 'success');

    Volt::actingAs($admin)->test('admin.settings')->call('editMembershipPerks')
        ->assertSet('membershipPerkDraft.Silver.0', 'Silver-specific access')
        ->assertSet('membershipPerkDraft.Silver.1', 'Profits of 0.75% per Product - 35 products per set')
        ->assertSet('membershipPerkDraft.Gold.4', 'Gold-only priority support')
        ->set('membershipPerkDraft.Silver.0', 'Discarded text')->call('cancelMembershipPerks')
        ->assertSet('editingMembershipPerks', false)->call('editMembershipPerks')
        ->assertSet('membershipPerkDraft.Silver.0', 'Silver-specific access');

    \Livewire\Livewire::actingAs($user)->test(\App\Livewire\Dashboard\Activities::class)
        ->assertSeeInOrder(['Silver-specific access', 'Gold-only priority support'])
        ->assertDontSee('Normal users are assigned general usage access to data collection');
    expect(\App\Models\MembershipLevel::all()->toArray())->toBe($levels)
        ->and(PlatformSetting::current()->telegram_support_username)->toBe('support_bot');
});

it('rejects invalid perks without overwriting saved content', function (mixed $perk) {
    $admin = User::factory()->create(['is_admin' => true]);
    $saved = PlatformSetting::current()->membershipPerks();
    PlatformSetting::factory()->create(['membership_perks' => $saved]);

    Volt::actingAs($admin)->test('admin.settings')->call('editMembershipPerks')
        ->set('membershipPerkDraft.Gold.1', $perk)->call('saveMembershipPerks')
        ->assertHasErrors('membershipPerkDraft.Gold.1')->assertNotDispatched('notify');

    expect(PlatformSetting::current()->membershipPerks())->toBe($saved);
})->with(['empty' => [''], 'too long' => [str_repeat('x', 1001)], 'not text' => [123]]);

it('denies membership perk changes after administrator access is revoked', function (string $action, array $arguments) {
    $this->withoutExceptionHandling();
    $admin = User::factory()->create(['is_admin' => true]);
    $component = Volt::actingAs($admin)->test('admin.settings')->call('editMembershipPerks');
    $admin->update(['is_admin' => false]);

    expect(fn () => $component->call($action, ...$arguments))->toThrow(HttpException::class);
    expect(PlatformSetting::current()->membership_perks)->toBeNull();
})->with([
    ['editMembershipPerks', []],
    ['saveMembershipPerks', []],
    ['addMembershipPerk', ['Normal']],
    ['removeMembershipPerk', ['Normal', 0]],
    ['cancelMembershipPerks', []],
]);

it('uploads multiple business licenses and removes only the selected license from storage and the dashboard', function () {
    Storage::fake('public');
    $admin = User::factory()->create(['is_admin' => true]);
    PlatformSetting::factory()->create(['id' => 1, 'telegram_support_username' => 'support_bot']);

    $component = Volt::actingAs($admin)->test('admin.settings')
        ->assertSee('No licenses uploaded yet')
        ->set('businessLicenseUpload', UploadedFile::fake()->image('license.jpg'))
        ->assertHasNoErrors()
        ->assertDispatched('notify', message: 'Business license uploaded.', type: 'success');
    $first = PlatformSetting::current()->business_licenses[0];
    $component->set('businessLicenseUpload', UploadedFile::fake()->image('license.png'))->assertHasNoErrors();
    $paths = PlatformSetting::current()->business_licenses;
    expect($paths)->toHaveCount(2);
    Storage::disk('public')->assertExists($paths);
    Volt::actingAs($admin)->test('admin.settings')->assertSee(route('storage.file', $first));

    $user = User::factory()->create(['is_admin' => false, 'is_banned' => false]);
    $this->actingAs($user)->get(route('dashboard.certificate'))
        ->assertOk()->assertSee(route('storage.file', $first))->assertSee(route('storage.file', $paths[1]));
    $this->get(route('storage.file', $first))->assertOk()->assertHeader('Content-Type', 'image/jpeg');

    Volt::actingAs($admin)->test('admin.settings')->call('removeBusinessLicense', $first)->assertHasNoErrors();
    Storage::disk('public')->assertMissing($first);
    Storage::disk('public')->assertExists($paths[1]);
    expect(PlatformSetting::current()->business_licenses)->toBe([$paths[1]])
        ->and(PlatformSetting::current()->telegram_support_username)->toBe('support_bot');
    $this->actingAs($user)->get(route('dashboard.certificate'))
        ->assertDontSee(route('storage.file', $first))->assertSee(route('storage.file', $paths[1]));

    Volt::actingAs($admin)->test('admin.settings')->call('removeBusinessLicense', $paths[1])->assertSee('No licenses uploaded yet');
    $this->actingAs($user)->get(route('dashboard.certificate'))->assertSee('No business licenses available yet');
});

it('rejects invalid business license uploads without changing saved licenses', function (string $kind, string $rule) {
    Storage::fake('public');
    $admin = User::factory()->create(['is_admin' => true]);
    PlatformSetting::factory()->create(['id' => 1, 'business_licenses' => ['business-licenses/existing.png']]);
    $file = match ($kind) {
        'pdf' => UploadedFile::fake()->create('license.pdf', 100, 'application/pdf'),
        'gif' => UploadedFile::fake()->image('license.gif'),
        'large' => UploadedFile::fake()->image('license.png')->size(5121),
    };

    Volt::actingAs($admin)->test('admin.settings')->set('businessLicenseUpload', $file)
        ->assertHasErrors(['businessLicenseUpload' => $rule])->assertNotDispatched('notify');

    expect(PlatformSetting::current()->business_licenses)->toBe(['business-licenses/existing.png'])
        ->and(Storage::disk('public')->allFiles('business-licenses'))->toBe([]);
})->with([['pdf', 'image'], ['gif', 'mimes'], ['large', 'max']]);

it('does not delete files outside the saved business license list', function () {
    Storage::fake('public');
    Storage::disk('public')->put('unrelated.png', 'keep');

    Volt::actingAs(User::factory()->create(['is_admin' => true]))->test('admin.settings')
        ->call('removeBusinessLicense', 'unrelated.png')->assertNotFound();

    Storage::disk('public')->assertExists('unrelated.png');
});

it('blocks business license changes after administrator access is revoked', function (string $action) {
    $this->withoutExceptionHandling();
    Storage::fake('public');
    $admin = User::factory()->create(['is_admin' => true]);
    $component = Volt::actingAs($admin)->test('admin.settings');
    $admin->update(['is_admin' => false]);

    if ($action === 'upload') {
        expect(fn () => $component->set('businessLicenseUpload', UploadedFile::fake()->image('license.png')))->toThrow(HttpException::class);
    } else {
        expect(fn () => $component->call('removeBusinessLicense', 'business-licenses/existing.png'))->toThrow(HttpException::class);
    }

    expect(PlatformSetting::current()->business_licenses)->toBeNull();
})->with(['upload', 'remove']);
