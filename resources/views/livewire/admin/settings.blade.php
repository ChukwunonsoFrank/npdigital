<?php

use App\Models\MembershipLevel;
use App\Models\PlatformSetting;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;

new #[Layout('components.layouts.dashboard')] #[Title('Platform Settings')] class extends Component {
    use WithFileUploads;

    private const CRYPTOCURRENCIES = [
        'BTC' => 'Bitcoin (BTC)',
        'ETH' => 'Ethereum (ETH)',
        'USDT-TRC20' => 'USDT (TRC20)',
        'USDT-ERC20' => 'USDT (ERC20)',
        'BNB' => 'BNB (BSC)',
        'SOL' => 'Solana (SOL)',
        'XRP' => 'XRP',
        'DOGE' => 'Dogecoin (DOGE)',
        'LTC' => 'Litecoin (LTC)',
        'TRX' => 'Tron (TRX)',
    ];

    public $businessLicenseUpload;

    public bool $maintenanceMode = false;

    public bool $allowRegistrations = true;

    #[Locked]
    public bool $editingWalletAddresses = false;

    /** @var array<string, string> */
    public array $walletAddresses = [];

    #[Locked]
    public bool $editingWhatsapp = false;

    #[Locked]
    public bool $editingTelegram = false;

    public string $whatsappNumber = '';

    public string $telegramUsername = '';

    #[Locked]
    public bool $editingCheckIn = false;

    /** @var list<array{day: int|string, reward: int|float|string}> */
    public array $checkInMilestones = [];

    public string $checkInTerms = '';

    #[Locked]
    public bool $editingMembershipChart = false;

    /** @var list<array{name: string, deposit: string, tasks: int|string, commission: string}> */
    public array $membershipChartRows = [];

    #[Locked]
    public bool $editingMembershipPerks = false;

    /** @var array<string, list<string>> */
    public array $membershipPerkDraft = [];

    #[Locked]
    public bool $showMembershipLevelModal = false;

    public string $membershipLevelName = '';

    /** @var list<string> */
    public array $membershipLevelPerkDraft = [];

    public function boot(): void
    {
        abort_unless(auth()->user()?->is_admin, 403);
    }

    public function mount(): void
    {
        $settings = PlatformSetting::current();
        $this->maintenanceMode = $settings->maintenance_mode;
        $this->allowRegistrations = $settings->allow_registrations;
    }

    public function updatedMaintenanceMode(): void
    {
        $this->validateOnly('maintenanceMode', ['maintenanceMode' => ['required', 'boolean']]);
        PlatformSetting::query()->updateOrCreate(['id' => 1], ['maintenance_mode' => $this->maintenanceMode]);
        $this->dispatch('notify', message: 'Maintenance mode '.($this->maintenanceMode ? 'enabled.' : 'disabled.'), type: 'success');
    }

    public function updatedAllowRegistrations(): void
    {
        $this->validateOnly('allowRegistrations', ['allowRegistrations' => ['required', 'boolean']]);
        PlatformSetting::query()->updateOrCreate(['id' => 1], ['allow_registrations' => $this->allowRegistrations]);
        $this->dispatch('notify', message: 'New registrations '.($this->allowRegistrations ? 'enabled.' : 'disabled.'), type: 'success');
    }

    public function editWalletAddresses(): void
    {
        $this->walletAddresses = array_replace(
            array_fill_keys(array_keys(self::CRYPTOCURRENCIES), ''),
            PlatformSetting::current()->deposit_wallet_addresses ?? [],
        );
        $this->resetValidation();
        $this->editingWalletAddresses = true;
    }

    public function saveWalletAddresses(): void
    {
        $rules = ['walletAddresses' => ['required', 'array:'.implode(',', array_keys(self::CRYPTOCURRENCIES))]];
        $attributes = [];

        foreach (self::CRYPTOCURRENCIES as $code => $label) {
            $rules['walletAddresses.'.$code] = ['required', 'string', 'max:255', 'not_regex:/\s/'];
            $attributes['walletAddresses.'.$code] = $label.' address';
        }

        $validated = $this->validate($rules, [
            'walletAddresses.*.not_regex' => 'The :attribute must not contain spaces.',
        ], $attributes);

        PlatformSetting::query()->updateOrCreate(['id' => 1], ['deposit_wallet_addresses' => $validated['walletAddresses']]);
        $this->editingWalletAddresses = false;
        $this->dispatch('notify', message: 'Deposit wallet addresses saved.', type: 'success');
    }

    public function editWhatsapp(): void
    {
        $this->whatsappNumber = PlatformSetting::current()->whatsapp_support_number ?? '';
        $this->resetValidation('whatsappNumber');
        $this->editingWhatsapp = true;
    }

    public function saveWhatsapp(): void
    {
        $validated = $this->validate([
            'whatsappNumber' => ['required', 'string', 'regex:/^\+[1-9][0-9]{6,14}$/'],
        ], [
            'whatsappNumber.required' => 'Enter a WhatsApp support number.',
            'whatsappNumber.regex' => 'Enter a valid number with country code, e.g. +1234567890.',
        ]);

        PlatformSetting::query()->updateOrCreate(['id' => 1], ['whatsapp_support_number' => $validated['whatsappNumber']]);
        $this->editingWhatsapp = false;
        $this->dispatch('notify', message: 'WhatsApp support number saved.', type: 'success');
    }

    public function editTelegram(): void
    {
        $this->telegramUsername = PlatformSetting::current()->telegram_support_username ?? '';
        $this->resetValidation('telegramUsername');
        $this->editingTelegram = true;
    }

    public function saveTelegram(): void
    {
        $validated = $this->validate([
            'telegramUsername' => ['required', 'string', 'regex:/^[a-zA-Z][a-zA-Z0-9_]{4,31}$/'],
        ], [
            'telegramUsername.required' => 'Enter a Telegram support username.',
            'telegramUsername.regex' => 'Use 5–32 letters, numbers or underscores, starting with a letter, without @.',
        ]);

        PlatformSetting::query()->updateOrCreate(['id' => 1], ['telegram_support_username' => $validated['telegramUsername']]);
        $this->editingTelegram = false;
        $this->dispatch('notify', message: 'Telegram support username saved.', type: 'success');
    }

    public function editCheckIn(): void
    {
        $program = PlatformSetting::current()->checkInProgram();
        $this->checkInMilestones = $program['milestones'];
        $this->checkInTerms = $program['terms'];
        $this->resetValidation();
        $this->editingCheckIn = true;
    }

    public function cancelCheckIn(): void
    {
        $this->reset('checkInMilestones', 'checkInTerms', 'editingCheckIn');
        $this->resetValidation();
    }

    public function addCheckInMilestone(): void
    {
        if ($this->editingCheckIn) {
            $this->checkInMilestones[] = ['day' => '', 'reward' => ''];
        }
    }

    public function removeCheckInMilestone(int $index): void
    {
        if ($this->editingCheckIn) {
            unset($this->checkInMilestones[$index]);
            $this->checkInMilestones = array_values($this->checkInMilestones);
            $this->resetValidation();
        }
    }

    public function saveCheckIn(): void
    {
        $validated = $this->validate([
            'checkInMilestones' => ['required', 'array', 'min:1', 'max:100'],
            'checkInMilestones.*' => ['required', 'array:day,reward'],
            'checkInMilestones.*.day' => ['required', 'integer', 'min:1', 'max:2147483647', 'distinct'],
            'checkInMilestones.*.reward' => ['required', 'numeric', 'min:0', 'max:999999999.99', 'decimal:0,2'],
            'checkInTerms' => ['required', 'string', 'max:10000'],
        ], [
            'checkInMilestones.required' => 'Add at least one milestone.',
            'checkInMilestones.*.day.distinct' => 'Each milestone must have a different day.',
        ], [
            'checkInMilestones.*.day' => 'day',
            'checkInMilestones.*.reward' => 'reward',
            'checkInTerms' => 'terms and conditions',
        ]);

        $milestones = collect($validated['checkInMilestones'])->sortBy('day', SORT_NUMERIC)->values()->all();
        PlatformSetting::query()->updateOrCreate(['id' => 1], [
            'check_in_program' => ['milestones' => $milestones, 'terms' => $validated['checkInTerms']],
        ]);
        $this->editingCheckIn = false;
        $this->dispatch('notify', message: 'Check-in program saved.', type: 'success');
    }

    public function editMembershipChart(): void
    {
        $this->membershipChartRows = PlatformSetting::current()->membershipChart();
        $this->resetValidation();
        $this->editingMembershipChart = true;
    }

    public function cancelMembershipChart(): void
    {
        $this->reset('membershipChartRows', 'editingMembershipChart');
        $this->resetValidation();
    }

    public function saveMembershipChart(): void
    {
        $validated = $this->validate([
            'membershipChartRows' => ['required', 'array', 'size:6'],
            'membershipChartRows.*' => ['required', 'array:name,deposit,tasks,commission'],
            'membershipChartRows.*.name' => ['required', 'string', 'max:50', 'distinct'],
            'membershipChartRows.*.deposit' => ['required', 'string', 'max:50'],
            'membershipChartRows.*.tasks' => ['required', 'integer', 'min:0', 'max:1000000'],
            'membershipChartRows.*.commission' => ['required', 'string', 'max:50'],
        ], [
            'membershipChartRows.size' => 'The chart must contain all five membership levels.',
            'membershipChartRows.*.name.distinct' => 'Each level must have a different name.',
        ], [
            'membershipChartRows.*.name' => 'level name',
            'membershipChartRows.*.deposit' => 'deposit',
            'membershipChartRows.*.tasks' => 'tasks per day',
            'membershipChartRows.*.commission' => 'commission',
        ]);

        PlatformSetting::query()->updateOrCreate(['id' => 1], ['membership_chart' => array_values($validated['membershipChartRows'])]);
        $this->editingMembershipChart = false;
        $this->dispatch('notify', message: 'Membership level chart saved.', type: 'success');
    }

    public function editMembershipPerks(): void
    {
        $savedPerks = PlatformSetting::current()->membershipPerks();

        $this->membershipPerkDraft = collect($this->membershipLevelNames())
            ->mapWithKeys(fn (string $level): array => [$level => $savedPerks[$level] ?? []])
            ->all();
        $this->resetValidation();
        $this->editingMembershipPerks = true;
    }

    public function cancelMembershipPerks(): void
    {
        $this->reset('membershipPerkDraft', 'editingMembershipPerks');
        $this->resetValidation();
    }

    public function addMembershipPerk(string $level): void
    {
        if ($this->editingMembershipPerks && array_key_exists($level, $this->membershipPerkDraft)) {
            $this->membershipPerkDraft[$level][] = '';
        }
    }

    public function removeMembershipPerk(string $level, int $index): void
    {
        if ($this->editingMembershipPerks && array_key_exists($level, $this->membershipPerkDraft)) {
            unset($this->membershipPerkDraft[$level][$index]);
            $this->membershipPerkDraft[$level] = array_values($this->membershipPerkDraft[$level]);
            $this->resetValidation();
        }
    }

    public function saveMembershipPerks(): void
    {
        $levels = array_keys($this->membershipPerkDraft);
        $rules = ['membershipPerkDraft' => ['required', 'array', 'min:1']];

        foreach ($levels as $level) {
            $rules['membershipPerkDraft.'.$level] = ['present', 'array', 'list', 'max:30'];
            $rules['membershipPerkDraft.'.$level.'.*'] = ['required', 'string', 'max:1000'];
        }

        $validated = $this->validate($rules, [
            'membershipPerkDraft.*.*.required' => 'Enter a perk or remove the empty row.',
            'membershipPerkDraft.*.*.max' => 'Each perk may contain up to 1,000 characters.',
        ]);

        PlatformSetting::query()->updateOrCreate(['id' => 1], ['membership_perks' => $validated['membershipPerkDraft']]);
        $this->editingMembershipPerks = false;
        $this->dispatch('notify', message: 'Membership levels saved.', type: 'success');
    }

    public function openMembershipLevelModal(): void
    {
        $this->membershipLevelName = '';
        $this->membershipLevelPerkDraft = [''];
        $this->resetValidation();
        $this->showMembershipLevelModal = true;
    }

    public function closeMembershipLevelModal(): void
    {
        $this->reset('showMembershipLevelModal', 'membershipLevelName', 'membershipLevelPerkDraft');
        $this->resetValidation();
    }

    public function addMembershipLevelPerk(): void
    {
        if ($this->showMembershipLevelModal) {
            $this->membershipLevelPerkDraft[] = '';
        }
    }

    public function removeMembershipLevelPerk(int $index): void
    {
        if ($this->showMembershipLevelModal) {
            unset($this->membershipLevelPerkDraft[$index]);
            $this->membershipLevelPerkDraft = array_values($this->membershipLevelPerkDraft);
            $this->resetValidation();
        }
    }

    public function saveMembershipLevel(): void
    {
        $this->validate([
            'membershipLevelName' => ['required', 'string', 'max:50', 'not_regex:/[.,]/'],
            'membershipLevelPerkDraft' => ['present', 'array', 'list', 'max:30'],
            'membershipLevelPerkDraft.*' => ['required', 'string', 'max:1000'],
        ], [
            'membershipLevelName.not_regex' => 'Level names may not contain periods or commas.',
            'membershipLevelPerkDraft.*.required' => 'Enter a perk or remove the empty row.',
            'membershipLevelPerkDraft.*.max' => 'Each perk may contain up to 1,000 characters.',
        ]);

        $name = trim($this->membershipLevelName);

        if ($name === '') {
            $this->addError('membershipLevelName', 'Enter a level name.');

            return;
        }

        $exists = MembershipLevel::query()->get()->contains(
            fn (MembershipLevel $level): bool => strtolower($level->name) === strtolower($name),
        );

        if ($exists) {
            $this->addError('membershipLevelName', 'The level name has already been taken.');

            return;
        }

        MembershipLevel::query()->create([
            'name' => $name,
            'percentage' => '0.00',
            'minimum_balance' => '0.00',
        ]);

        $perks = PlatformSetting::current()->membershipPerks();
        $perks[$name] = array_values($this->membershipLevelPerkDraft);
        PlatformSetting::query()->updateOrCreate(['id' => 1], ['membership_perks' => $perks]);

        if ($this->editingMembershipPerks) {
            $this->membershipPerkDraft[$name] = $perks[$name];
        }

        $this->closeMembershipLevelModal();
        $this->dispatch('notify', message: 'Membership level created.', type: 'success');
    }

    public function updatedBusinessLicenseUpload(): void
    {
        $this->validate([
            'businessLicenseUpload' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:5120'],
        ], [
            'businessLicenseUpload.image' => 'Upload a JPG or PNG image.',
            'businessLicenseUpload.mimes' => 'Upload a JPG or PNG image.',
            'businessLicenseUpload.max' => 'Business licenses must be 5 MB or smaller.',
        ]);

        $path = $this->businessLicenseUpload->store('business-licenses', 'public');
        if (! $path) {
            $this->addError('businessLicenseUpload', 'The license could not be stored. Please try again.');

            return;
        }

        try {
            $licenses = PlatformSetting::current()->business_licenses ?? [];
            $licenses[] = $path;
            PlatformSetting::query()->updateOrCreate(['id' => 1], ['business_licenses' => $licenses]);
        } catch (\Throwable $exception) {
            Storage::disk('public')->delete($path);

            throw $exception;
        }

        $this->reset('businessLicenseUpload');
        $this->dispatch('notify', message: 'Business license uploaded.', type: 'success');
    }

    public function removeBusinessLicense(string $path): void
    {
        $licenses = PlatformSetting::current()->business_licenses ?? [];
        abort_unless(in_array($path, $licenses, true), 404);

        PlatformSetting::query()->updateOrCreate(['id' => 1], [
            'business_licenses' => array_values(array_diff($licenses, [$path])),
        ]);
        Storage::disk('public')->delete($path);
        $this->dispatch('notify', message: 'Business license removed.', type: 'success');
    }

    /**
     * Membership levels shown in the editor, including levels created from the dashboard.
     *
     * @return list<string>
     */
    private function membershipLevelNames(): array
    {
        $names = MembershipLevel::query()->orderBy('id')->pluck('name')->all();

        if ($names === []) {
            $names = array_keys(PlatformSetting::current()->membershipPerks());
        }

        return array_values($names);
    }

    /** @return array<string, mixed> */
    public function with(): array
    {
        $settings = PlatformSetting::current();

        return [
            'walletOptions' => self::CRYPTOCURRENCIES,
            'savedWalletAddresses' => $settings->deposit_wallet_addresses ?? [],
            'savedWhatsappNumber' => $settings->whatsapp_support_number,
            'savedTelegramUsername' => $settings->telegram_support_username,
            'checkInProgram' => $settings->checkInProgram(),
            'membershipChart' => $settings->membershipChart(),
            'membershipPerks' => $settings->membershipPerks(),
            'membershipLevelNames' => $this->membershipLevelNames(),
            'businessLicenses' => $settings->business_licenses ?? [],
        ];
    }
}; ?>

<div class="space-y-5 animate-fade-in" x-data @notify.window="window.showToast($event.detail.message)">
    <div class="flex items-center gap-3">
        <a href="{{ route('admin.dashboard') }}" aria-label="Back to overview"
            class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-md transition-colors hover:bg-muted focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2">
            <x-dashboard.icon name="arrow-left" class="h-5 w-5" />
        </a>
        <h1 class="font-display text-xl font-bold">Platform Settings</h1>
    </div>

    <p class="text-sm text-muted-foreground">Payment methods and platform configuration.</p>

    @php
        $editors = [
            3 => ['editing' => $editingWalletAddresses, 'edit' => 'editWalletAddresses', 'save' => 'saveWalletAddresses', 'label' => 'wallet addresses'],
            4 => ['editing' => $editingWhatsapp, 'edit' => 'editWhatsapp', 'save' => 'saveWhatsapp', 'label' => 'WhatsApp support number'],
            5 => ['editing' => $editingTelegram, 'edit' => 'editTelegram', 'save' => 'saveTelegram', 'label' => 'Telegram support username'],
        ];
        $options = [
            ['title' => 'Maintenance Mode', 'description' => 'Temporarily disable the platform for users', 'icon' => 'wrench', 'color' => 'bg-orange-500/10 text-orange-500'],
            ['title' => 'Allow New Registrations', 'description' => 'Allow new users to sign up', 'icon' => 'user-plus', 'color' => 'bg-blue-500/10 text-blue-500'],
            ['title' => 'Registration Bonus', 'description' => 'Welcome credit for new sign-ups', 'icon' => 'gift', 'color' => 'bg-green-500/10 text-green-500'],
            ['title' => 'Deposit Wallet Addresses', 'description' => 'Update the crypto wallet addresses users send deposits to', 'icon' => 'wallet', 'color' => 'bg-primary/10 text-primary'],
            ['title' => 'WhatsApp Support Number', 'description' => 'Update the WhatsApp number for customer support', 'icon' => 'message-circle', 'color' => 'bg-green-500/10 text-green-500'],
            ['title' => 'Telegram Support', 'description' => 'Update the Telegram username for customer support', 'icon' => 'send', 'color' => 'bg-blue-400/10 text-blue-400'],
            // ['title' => 'Carousel Posters', 'description' => 'Manage the moving carousel products per VIP level', 'icon' => 'image', 'color' => 'bg-purple-500/10 text-purple-500'],
            ['title' => 'Activities Management', 'description' => 'Edit check-in program, membership & employee levels', 'icon' => 'chart-column', 'color' => 'bg-blue-500/10 text-blue-500'],
            // ['title' => 'Events & Rewards', 'description' => 'Manage check-in, spin wheel, and magic box events', 'icon' => 'gamepad-2', 'color' => 'bg-pink-500/10 text-pink-500'],
            // ['title' => 'Testimonial Photos', 'description' => 'Upload, replace, or remove testimonial logos and photos', 'icon' => 'image', 'color' => 'bg-amber-500/10 text-amber-500'],
            ['title' => 'Business Licenses', 'description' => 'Upload business license certificates visible to users', 'icon' => 'shield-check', 'color' => 'bg-emerald-500/10 text-emerald-500'],
            // ['title' => 'Terms & Conditions', 'description' => 'Edit the T&C sections shown to users', 'icon' => 'file-text', 'color' => 'bg-indigo-500/10 text-indigo-500'],
            // ['title' => 'FAQ', 'description' => 'Edit the frequently asked questions', 'icon' => 'circle-help', 'color' => 'bg-cyan-500/10 text-cyan-500'],
            // ['title' => 'About Page', 'description' => 'Edit About page text and images', 'icon' => 'info', 'color' => 'bg-teal-500/10 text-teal-500'],
        ];
    @endphp

    <div class="space-y-3">
        @foreach ($options as $option)
            @php
                $editor = $editors[$loop->index] ?? null;
            @endphp
            <div wire:key="setting-{{ $loop->index }}" x-data="{ open: false }" class="overflow-hidden rounded-xl border border-border bg-card">
                <div class="relative">
                    <button type="button"
                        @if ($loop->index <= 7)
                            @click="open = !open" x-bind:aria-expanded="open" aria-controls="settings-panel-{{ $loop->index }}"
                        @endif
                        class="flex w-full items-center justify-between gap-3 p-4 text-left transition-colors hover:bg-muted/30 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-ring">
                        <span class="flex min-w-0 items-center gap-3">
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full {{ $option['color'] }}">
                                <x-dashboard.icon :name="$option['icon']" class="h-4 w-4" />
                            </span>
                            <span class="min-w-0">
                                <span class="block text-sm font-semibold">{{ $option['title'] }}</span>
                                <span class="block text-xs text-muted-foreground">{{ $option['description'] }}</span>
                            </span>
                        </span>
                        <span class="flex shrink-0 items-center gap-2">
                            @if ($editor)
                                <span class="h-7 w-7" aria-hidden="true"></span>
                            @endif
                            <x-dashboard.icon name="chevron-right" x-bind:class="{ 'rotate-90': open }" class="h-4 w-4 shrink-0 text-muted-foreground" />
                        </span>
                    </button>
                    @if ($editor)
                        <button type="button" @click="open = true"
                            wire:click="{{ $editor['editing'] ? $editor['save'] : $editor['edit'] }}"
                            aria-label="{{ ($editor['editing'] ? 'Save ' : 'Edit ').$editor['label'] }}"
                            wire:loading.attr="disabled" wire:target="{{ $editor['edit'] }},{{ $editor['save'] }}"
                            class="absolute right-10 top-1/2 -translate-y-1/2 rounded-md p-1.5 transition-colors hover:bg-muted focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring disabled:opacity-50">
                            <x-dashboard.icon :name="$editor['editing'] ? 'save' : 'pencil'" :class="'h-4 w-4 '.($editor['editing'] ? 'text-green-500' : 'text-muted-foreground')" />
                        </button>
                    @endif
                </div>

                @if ($loop->index <= 7)
                    <div x-cloak x-show="open" id="settings-panel-{{ $loop->index }}" class="border-t border-border p-4">
                        @if ($loop->index === 7)
                            <div class="space-y-4">
                                <label class="relative flex cursor-pointer items-center gap-3 rounded-xl border-2 border-dashed border-primary/30 p-4 focus-within:ring-2 focus-within:ring-ring hover:bg-primary/5">
                                    <x-dashboard.icon name="upload" class="h-5 w-5 shrink-0 text-primary" />
                                    <span class="text-sm">Upload Business License (JPG/PNG)</span>
                                    <input type="file" wire:model="businessLicenseUpload" accept="image/jpeg,image/png" aria-label="Upload Business License (JPG/PNG)"
                                        wire:loading.attr="disabled" wire:target="businessLicenseUpload,removeBusinessLicense" class="absolute inset-0 h-full w-full cursor-pointer opacity-0" />
                                </label>
                                <p class="text-xs text-muted-foreground">JPG or PNG, up to 5 MB. Uploads are saved automatically and visible to users.</p>
                                <p wire:loading wire:target="businessLicenseUpload" role="status" class="text-sm text-primary">Uploading business license…</p>
                                <flux:error name="businessLicenseUpload" />
                                @if ($businessLicenses)
                                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                                        @foreach ($businessLicenses as $path)
                                            <div wire:key="business-license-{{ $path }}" class="space-y-3 rounded-xl border border-border p-3">
                                                <a href="{{ route('storage.file', $path) }}" target="_blank" rel="noopener noreferrer" aria-label="View business license {{ $loop->iteration }} full size" class="block rounded-lg focus-visible:ring-2 focus-visible:ring-ring">
                                                    <img src="{{ route('storage.file', $path) }}" alt="Business license {{ $loop->iteration }}" class="h-48 w-full rounded-lg object-contain" />
                                                </a>
                                                <flux:button type="button" size="sm" wire:click="removeBusinessLicense('{{ $path }}')" wire:confirm="Remove this business license? It will no longer be visible to users."
                                                    wire:loading.attr="disabled" wire:target="businessLicenseUpload,removeBusinessLicense" aria-label="Remove business license {{ $loop->iteration }}" class="w-full text-destructive!">
                                                    <span class="inline-flex items-center gap-2"><x-dashboard.icon name="trash-2" class="h-4 w-4" />Remove</span>
                                                </flux:button>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <p class="py-6 text-center text-sm text-muted-foreground">No licenses uploaded yet</p>
                                @endif
                            </div>
                        @elseif ($loop->index === 0)
                            <div class="flex items-center justify-between gap-3">
                                <p id="maintenance-mode-label" class="text-sm">Enable maintenance mode</p>
                                <flux:switch wire:model.live="maintenanceMode" aria-labelledby="maintenance-mode-label"
                                    wire:loading.attr="disabled" wire:target="maintenanceMode"
                                    class="h-6! w-11! min-w-11! border-2! border-transparent! bg-input! data-checked:bg-primary! [&>span]:size-5! [&>span]:translate-x-0! [&[data-checked]>span]:translate-x-5! [&>span]:bg-background! [&>span]:shadow-lg" />
                            </div>
                        @elseif ($loop->index === 1)
                            <div class="flex items-center justify-between gap-3">
                                <p id="allow-registrations-label" class="text-sm">Allow registrations</p>
                                <flux:switch wire:model.live="allowRegistrations" aria-labelledby="allow-registrations-label"
                                    wire:loading.attr="disabled" wire:target="allowRegistrations"
                                    class="h-6! w-11! min-w-11! border-2! border-transparent! bg-input! data-checked:bg-primary! [&>span]:size-5! [&>span]:translate-x-0! [&[data-checked]>span]:translate-x-5! [&>span]:bg-background! [&>span]:shadow-lg" />
                            </div>
                        @elseif ($loop->index === 2)
                            <div class="space-y-2 rounded-lg border border-border p-3">
                                <p class="text-sm font-medium">$10.00 registration bonus</p>
                                <p class="text-xs text-muted-foreground">Every newly registered account receives a one-time $10.00 welcome bonus.</p>
                            </div>
                        @elseif ($loop->index === 3)
                            <form wire:submit="saveWalletAddresses" class="space-y-4">
                                @foreach ($walletOptions as $code => $label)
                                    <div wire:key="deposit-wallet-{{ $code }}" class="space-y-1.5">
                                        <label for="deposit-wallet-{{ $code }}" class="flex items-center gap-2 text-xs font-medium">
                                            <span class="text-base" aria-hidden="true">{{ match ($code) {
                                                'BTC' => '₿',
                                                'ETH' => 'Ξ',
                                                'USDT-TRC20', 'USDT-ERC20' => '₮',
                                                'BNB' => '◆',
                                                'SOL' => '◎',
                                                'XRP' => '✕',
                                                'DOGE' => 'Ð',
                                                'LTC' => 'Ł',
                                                'TRX' => '◈',
                                                default => $code,
                                            } }}</span>
                                            {{ $label }}
                                        </label>
                                        @if ($editingWalletAddresses)
                                            <flux:input id="deposit-wallet-{{ $code }}" wire:model="walletAddresses.{{ $code }}"
                                                placeholder="Enter {{ $label }} address" autocomplete="off" spellcheck="false"
                                                class:input="rounded-md! border-input! bg-background! font-mono! text-xs! text-foreground! shadow-none! md:text-sm!" />
                                            <flux:error name="walletAddresses.{{ $code }}" />
                                        @else
                                            <p id="deposit-wallet-{{ $code }}" class="break-all rounded-md bg-muted/50 px-3 py-2 font-mono text-xs text-muted-foreground">{{ $savedWalletAddresses[$code] ?? 'Not configured' }}</p>
                                        @endif
                                    </div>
                                @endforeach

                                @if ($editingWalletAddresses)
                                    <flux:button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="saveWalletAddresses"
                                        class="w-full rounded-md! bg-primary! text-primary-foreground! hover:bg-primary/90! sm:w-auto">
                                        Save Wallet Addresses
                                    </flux:button>
                                @endif
                            </form>
                        @elseif ($loop->index === 4 || $loop->index === 5)
                            @php
                                $isWhatsapp = $loop->index === 4;
                                $contactProperty = $isWhatsapp ? 'whatsappNumber' : 'telegramUsername';
                                $savedContact = $isWhatsapp ? $savedWhatsappNumber : $savedTelegramUsername;
                            @endphp
                            <form wire:submit="{{ $editor['save'] }}" class="space-y-2">
                                <label for="support-contact-{{ $loop->index }}" class="block text-xs font-medium">
                                    {{ $isWhatsapp ? 'Phone Number (with country code)' : 'Telegram Username (without @)' }}
                                </label>
                                @if ($editor['editing'])
                                    <div class="flex items-center gap-2">
                                        <flux:input id="support-contact-{{ $loop->index }}" wire:model="{{ $contactProperty }}"
                                            type="{{ $isWhatsapp ? 'tel' : 'text' }}" placeholder="{{ $isWhatsapp ? '+1234567890' : 'username' }}"
                                            autocomplete="off" spellcheck="false" class="min-w-0 max-w-[250px]"
                                            class:input="rounded-md! border-input! bg-background! text-base! text-foreground! shadow-none! md:text-sm!" />
                                        <flux:button type="submit" variant="primary" size="sm" wire:loading.attr="disabled" wire:target="{{ $editor['save'] }}"
                                            class="shrink-0 rounded-md! bg-primary! text-primary-foreground! hover:bg-primary/90!">
                                            Save
                                        </flux:button>
                                    </div>
                                    <flux:error :name="$contactProperty" />
                                @else
                                    <p id="support-contact-{{ $loop->index }}" class="break-all text-sm font-semibold">{{ $savedContact ?: 'Not set' }}</p>
                                @endif
                                <p class="text-xs text-muted-foreground">
                                    {{ $isWhatsapp ? 'Include country code, e.g. +1 for US, +44 for UK' : 'Enter just the username, e.g. "support_bot"' }}
                                </p>
                            </form>
                        @elseif ($loop->index === 6)
                            <div class="space-y-3">
                                <div x-data="{ checkInOpen: true }" class="rounded-xl border border-border bg-card px-5 shadow-sm">
                                    <h3>
                                        <button type="button" @click="checkInOpen = !checkInOpen" x-bind:aria-expanded="checkInOpen"
                                            aria-controls="check-in-editor" class="flex w-full items-center justify-between gap-3 py-5 text-left focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                                            <span class="flex min-w-0 items-center gap-3">
                                                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-primary/10">
                                                    <x-dashboard.icon name="calendar-check" class="h-5 w-5 text-primary" />
                                                </span>
                                                <span class="min-w-0">
                                                    <span class="block font-display text-sm font-bold uppercase tracking-wide">Check In Program</span>
                                                    <span class="block text-xs font-normal text-muted-foreground">Manage check-in milestones and rewards</span>
                                                </span>
                                            </span>
                                            <x-dashboard.icon name="chevron-down" x-bind:class="{ 'rotate-180': checkInOpen }" class="h-4 w-4 shrink-0 transition-transform" />
                                        </button>
                                    </h3>
                                    <form id="check-in-editor" x-show="checkInOpen" wire:submit="saveCheckIn" class="space-y-3 pb-4">
                                        <div class="flex justify-end gap-2">
                                            @if ($editingCheckIn)
                                                <flux:button type="button" size="sm" wire:click="cancelCheckIn" class="rounded-md! border-input! bg-background! shadow-none!"><span class="inline-flex items-center gap-2"><x-dashboard.icon name="x" class="h-4 w-4" />Cancel</span></flux:button>
                                                <flux:button type="submit" variant="primary" size="sm" wire:loading.attr="disabled" wire:target="saveCheckIn" class="rounded-md! bg-primary! text-primary-foreground! hover:bg-primary/90!"><span class="inline-flex items-center gap-2"><x-dashboard.icon name="save" class="h-4 w-4" />Save</span></flux:button>
                                            @else
                                                <flux:button type="button" size="sm" wire:click="editCheckIn" class="rounded-md! border-input! bg-background! shadow-none!"><span class="inline-flex items-center gap-2"><x-dashboard.icon name="pencil" class="h-4 w-4" />Edit</span></flux:button>
                                            @endif
                                        </div>
                                        @if ($editingCheckIn)
                                            @error('checkInMilestones')
                                                <p role="alert" class="text-sm text-destructive">{{ $message }}</p>
                                            @enderror
                                            @foreach ($checkInMilestones as $index => $milestone)
                                                <div wire:key="check-in-draft-{{ $index }}" class="flex items-center gap-3 rounded-lg border border-border p-3">
                                                    <div class="grid min-w-0 flex-1 grid-cols-1 gap-3 sm:grid-cols-2">
                                                        <div class="min-w-0 space-y-1">
                                                            <label for="milestone-day-{{ $index }}" class="block text-xs text-muted-foreground">Day</label>
                                                            <flux:input id="milestone-day-{{ $index }}" type="number" min="1" step="1" wire:model="checkInMilestones.{{ $index }}.day" class:input="rounded-md! border-input! bg-background! text-foreground! shadow-none!" />
                                                            <flux:error name="checkInMilestones.{{ $index }}.day" />
                                                        </div>
                                                        <div class="min-w-0 space-y-1">
                                                            <label for="milestone-reward-{{ $index }}" class="block text-xs text-muted-foreground">Reward (USD)</label>
                                                            <flux:input id="milestone-reward-{{ $index }}" type="number" min="0" step="0.01" wire:model="checkInMilestones.{{ $index }}.reward" class:input="rounded-md! border-input! bg-background! text-foreground! shadow-none!" />
                                                            <flux:error name="checkInMilestones.{{ $index }}.reward" />
                                                        </div>
                                                    </div>
                                                    <button type="button" wire:click="removeCheckInMilestone({{ $index }})" aria-label="Remove milestone {{ $index + 1 }}"
                                                        class="shrink-0 rounded-md p-2 text-destructive hover:bg-destructive/10 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                                                        <x-dashboard.icon name="trash-2" class="h-4 w-4" />
                                                    </button>
                                                </div>
                                            @endforeach
                                            <flux:button type="button" wire:click="addCheckInMilestone" class="w-full rounded-md! border-input! bg-background! shadow-none!"><span class="inline-flex items-center gap-2"><x-dashboard.icon name="plus" class="h-4 w-4" />Add Milestone</span></flux:button>
                                            <flux:textarea wire:model="checkInTerms" label="Terms & Conditions" rows="auto" class="rounded-md! border-input! bg-background! text-foreground! shadow-none!" />
                                        @else
                                            @foreach ($checkInProgram['milestones'] as $milestone)
                                                <div wire:key="check-in-saved-{{ $milestone['day'] }}" class="flex items-center gap-3 rounded-lg border border-border p-3">
                                                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-primary/10 font-bold text-primary">{{ str_pad((string) $milestone['day'], 2, '0', STR_PAD_LEFT) }}</span>
                                                    <div class="min-w-0">
                                                        <p class="text-sm font-medium">Check-in Day {{ $milestone['day'] }}</p>
                                                        <p class="break-all text-lg font-bold text-primary">${{ number_format((float) $milestone['reward'], (float) $milestone['reward'] == (int) $milestone['reward'] ? 0 : 2) }}</p>
                                                    </div>
                                                </div>
                                            @endforeach
                                            <div class="space-y-2 pt-1">
                                                <p class="text-sm font-semibold">Terms & Conditions</p>
                                                <p class="whitespace-pre-line break-words text-xs leading-relaxed text-muted-foreground">{{ $checkInProgram['terms'] }}</p>
                                            </div>
                                        @endif
                                    </form>
                                </div>
                                <div x-data="{ chartOpen: false }" class="rounded-xl border border-border bg-card px-5 shadow-sm">
                                    <h3>
                                        <button type="button" @click="chartOpen = !chartOpen" x-bind:aria-expanded="chartOpen" aria-controls="membership-chart-editor"
                                            class="flex w-full items-center justify-between gap-3 py-5 text-left focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                                            <span class="flex min-w-0 items-center gap-3">
                                                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-primary/10"><x-dashboard.icon name="trending-up" class="h-5 w-5 text-primary" /></span>
                                                <span class="min-w-0">
                                                    <span class="block font-display text-sm font-bold uppercase tracking-wide">Membership Level Chart</span>
                                                    <span class="block text-xs font-normal text-muted-foreground">Benefits and requirements for each level</span>
                                                </span>
                                            </span>
                                            <x-dashboard.icon name="chevron-down" x-bind:class="{ 'rotate-180': chartOpen }" class="h-4 w-4 shrink-0 transition-transform" />
                                        </button>
                                    </h3>
                                    <form id="membership-chart-editor" x-show="chartOpen" x-cloak wire:submit="saveMembershipChart" class="space-y-3 pb-4">
                                        <div class="flex justify-end gap-2">
                                            @if ($editingMembershipChart)
                                                <flux:button type="button" size="sm" wire:click="cancelMembershipChart" class="rounded-md! border-input! bg-background! shadow-none!"><span class="inline-flex items-center gap-2"><x-dashboard.icon name="x" class="h-4 w-4" />Cancel</span></flux:button>
                                                <flux:button type="submit" variant="primary" size="sm" wire:loading.attr="disabled" wire:target="saveMembershipChart" class="rounded-md! bg-primary! text-primary-foreground! hover:bg-primary/90!"><span class="inline-flex items-center gap-2"><x-dashboard.icon name="save" class="h-4 w-4" />Save</span></flux:button>
                                            @else
                                                <flux:button type="button" size="sm" wire:click="editMembershipChart" class="rounded-md! border-input! bg-background! shadow-none!"><span class="inline-flex items-center gap-2"><x-dashboard.icon name="pencil" class="h-4 w-4" />Edit</span></flux:button>
                                            @endif
                                        </div>
                                        @error('membershipChartRows')
                                            <p role="alert" class="text-sm text-destructive">{{ $message }}</p>
                                        @enderror
                                        <div class="overflow-x-auto">
                                            <table class="w-full min-w-[560px] table-fixed text-sm">
                                                <thead>
                                                    <tr class="border-b border-border">
                                                        @foreach (['Level', 'Deposit', 'Tasks/Day', 'Commission'] as $heading)
                                                            <th scope="col" class="px-1 pb-2 font-semibold {{ $loop->first ? 'text-left text-muted-foreground' : 'text-center' }}">{{ $heading }}</th>
                                                        @endforeach
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach ($editingMembershipChart ? $membershipChartRows : $membershipChart as $index => $row)
                                                        <tr wire:key="membership-chart-{{ $editingMembershipChart ? 'draft' : 'saved' }}-{{ $index }}" class="border-b border-border last:border-0">
                                                            @foreach (['name' => 'Level', 'deposit' => 'Deposit', 'tasks' => 'Tasks/Day', 'commission' => 'Commission'] as $field => $label)
                                                                <td class="px-1 py-2 {{ $loop->first ? 'text-left' : 'text-center' }}">
                                                                    @if ($editingMembershipChart)
                                                                        <flux:input aria-label="{{ $label }} for row {{ $index + 1 }}" wire:model="membershipChartRows.{{ $index }}.{{ $field }}"
                                                                            type="{{ $field === 'tasks' ? 'number' : 'text' }}"
                                                                            class:input="rounded-md! border-input! bg-background! text-foreground! shadow-none! {{ $field === 'name' ? '' : 'text-center!' }}" />
                                                                        <flux:error name="membershipChartRows.{{ $index }}.{{ $field }}" />
                                                                    @else
                                                                        <span class="break-words">{{ $row[$field] }}</span>
                                                                    @endif
                                                                </td>
                                                            @endforeach
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </form>
                                </div>
                                <div x-data="{ levelsOpen: false }" class="rounded-xl border border-border bg-card px-5 shadow-sm">
                                    <h3>
                                        <button type="button" @click="levelsOpen = !levelsOpen" x-bind:aria-expanded="levelsOpen" aria-controls="membership-perks-editor"
                                            class="flex w-full items-center justify-between gap-3 py-5 text-left focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                                            <span class="flex min-w-0 items-center gap-3">
                                                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-primary/10"><x-dashboard.icon name="users" class="h-5 w-5 text-primary" /></span>
                                                <span class="min-w-0">
                                                    <span class="block font-display text-sm font-bold uppercase tracking-wide">Membership Levels</span>
                                                    <span class="block text-xs font-normal text-muted-foreground">Manage the exact content shown for each membership level</span>
                                                </span>
                                            </span>
                                            <x-dashboard.icon name="chevron-down" x-bind:class="{ 'rotate-180': levelsOpen }" class="h-4 w-4 shrink-0 transition-transform" />
                                        </button>
                                    </h3>
                                    <form id="membership-perks-editor" x-show="levelsOpen" x-cloak wire:submit="saveMembershipPerks" class="space-y-3 pb-4">
                                        <div class="flex justify-end gap-2">
                                            <flux:button type="button" size="sm" wire:click="openMembershipLevelModal" class="rounded-md! border-transparent! bg-blue-600! text-white! shadow-none! hover:bg-blue-700!"><span class="inline-flex items-center gap-2"><x-dashboard.icon name="plus" class="h-4 w-4" />Add Level</span></flux:button>
                                            @if ($editingMembershipPerks)
                                                <flux:button type="button" size="sm" wire:click="cancelMembershipPerks" class="rounded-md! border-input! bg-background! shadow-none!"><span class="inline-flex items-center gap-2"><x-dashboard.icon name="x" class="h-4 w-4" />Cancel</span></flux:button>
                                                <flux:button type="submit" variant="primary" size="sm" wire:loading.attr="disabled" wire:target="saveMembershipPerks" class="rounded-md! bg-primary! text-primary-foreground! hover:bg-primary/90!"><span class="inline-flex items-center gap-2"><x-dashboard.icon name="save" class="h-4 w-4" />Save</span></flux:button>
                                            @else
                                                <flux:button type="button" size="sm" wire:click="editMembershipPerks" class="rounded-md! border-input! bg-background! shadow-none!"><span class="inline-flex items-center gap-2"><x-dashboard.icon name="pencil" class="h-4 w-4" />Edit</span></flux:button>
                                            @endif
                                        </div>
                                        @error('membershipPerkDraft')
                                            <p role="alert" class="text-sm text-destructive">{{ $message }}</p>
                                        @enderror
                                        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                                            @php
                                                $levelColors = ['Normal' => 'bg-gray-500', 'Silver' => 'bg-gray-400', 'Gold' => 'bg-amber-500', 'Platinum' => 'bg-slate-400', 'Diamond' => 'bg-sky-500'];
                                                $fallbackLevelColors = ['bg-violet-500', 'bg-rose-500', 'bg-emerald-500', 'bg-cyan-500', 'bg-indigo-500', 'bg-orange-400'];
                                            @endphp
                                            @foreach ($membershipLevelNames as $level)
                                                @php($color = $levelColors[$level] ?? $fallbackLevelColors[$loop->index % count($fallbackLevelColors)])
                                                <div wire:key="membership-perks-{{ $level }}" class="min-w-0 space-y-3 rounded-xl border border-border p-4">
                                                    <h4 class="flex items-center gap-2 text-sm font-bold"><span aria-hidden="true" class="h-3 w-3 shrink-0 rounded-full {{ $color }}"></span>{{ $level }}</h4>
                                                    @if ($editingMembershipPerks)
                                                        @error('membershipPerkDraft.'.$level)
                                                            <p role="alert" class="text-sm text-destructive">{{ $message }}</p>
                                                        @enderror
                                                        @foreach ($membershipPerkDraft[$level] ?? [] as $index => $perk)
                                                            <div wire:key="perk-draft-{{ $level }}-{{ $index }}" class="space-y-1">
                                                                <div class="flex items-center gap-2">
                                                                    <flux:input aria-label="{{ $level }} perk {{ $index + 1 }}" wire:model="membershipPerkDraft.{{ $level }}.{{ $index }}" class="min-w-0 flex-1" class:input="rounded-md! border-input! bg-background! text-muted-foreground! shadow-none!" />
                                                                    <button type="button" wire:click="removeMembershipPerk('{{ $level }}', {{ $index }})" aria-label="Remove {{ $level }} perk {{ $index + 1 }}" class="shrink-0 rounded-md p-1.5 text-destructive hover:bg-destructive/10 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"><x-dashboard.icon name="trash-2" class="h-4 w-4" /></button>
                                                                </div>
                                                                <flux:error name="membershipPerkDraft.{{ $level }}.{{ $index }}" />
                                                            </div>
                                                        @endforeach
                                                        <flux:button type="button" variant="ghost" size="sm" wire:click="addMembershipPerk('{{ $level }}')" aria-label="Add {{ $level }} perk" class="w-full"><span class="inline-flex items-center gap-2"><x-dashboard.icon name="plus" class="h-4 w-4" />Add Perk</span></flux:button>
                                                    @else
                                                        @if (count($membershipPerks[$level] ?? []) > 0)
                                                            <ul class="space-y-1.5 text-xs text-muted-foreground">
                                                                @foreach ($membershipPerks[$level] as $perk)
                                                                    <li wire:key="perk-saved-{{ $level }}-{{ $loop->index }}" class="flex items-start gap-1.5"><span class="shrink-0 text-primary">&bull;</span><span class="min-w-0 break-words">{{ $perk }}</span></li>
                                                                @endforeach
                                                            </ul>
                                                        @else
                                                            <p class="text-xs text-muted-foreground">No perks yet.</p>
                                                        @endif
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>
                                    </form>
                                </div>
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        @endforeach
    </div>

    @if ($showMembershipLevelModal)
        @teleport('body')
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 p-4" x-data x-trap.inert.noscroll="true" @keydown.escape.window="$wire.closeMembershipLevelModal()">
            <div class="absolute inset-0" wire:click="closeMembershipLevelModal"></div>
            <section role="dialog" aria-modal="true" aria-labelledby="membership-level-modal-title" class="relative max-h-[90dvh] w-full max-w-lg overflow-y-auto rounded-lg border border-border bg-background p-6 shadow-lg">
                <button type="button" wire:click="closeMembershipLevelModal" aria-label="Close dialog" class="absolute right-4 top-4 text-muted-foreground hover:text-foreground"><x-dashboard.icon name="x" class="h-4 w-4" /></button>
                <h2 id="membership-level-modal-title" class="pr-6 text-lg font-semibold leading-none tracking-tight">Add Membership Level</h2>
                <p class="mt-2 text-sm text-muted-foreground">Create a level and add the perks shown on the activities page.</p>
                <form wire:submit="saveMembershipLevel" class="mt-4 space-y-4">
                    <div class="space-y-1.5">
                        <label for="membership-level-name" class="text-sm font-medium">Level Name</label>
                        <flux:input id="membership-level-name" wire:model="membershipLevelName" placeholder="e.g. VVIP" maxlength="50" autofocus class:input="rounded-md! border-input! bg-background! text-foreground! shadow-none!" />
                        <flux:error name="membershipLevelName" />
                    </div>

                    <div class="space-y-2">
                        <p class="text-sm font-medium">Perks</p>
                        @foreach ($membershipLevelPerkDraft as $index => $perk)
                            <div wire:key="new-level-perk-{{ $index }}" class="space-y-1">
                                <div class="flex items-center gap-2">
                                    <flux:input aria-label="Perk {{ $index + 1 }}" wire:model="membershipLevelPerkDraft.{{ $index }}" placeholder="Perk description" class="min-w-0 flex-1" class:input="rounded-md! border-input! bg-background! text-muted-foreground! shadow-none!" />
                                    <button type="button" wire:click="removeMembershipLevelPerk({{ $index }})" aria-label="Remove perk {{ $index + 1 }}" class="shrink-0 rounded-md p-1.5 text-destructive hover:bg-destructive/10 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"><x-dashboard.icon name="trash-2" class="h-4 w-4" /></button>
                                </div>
                                <flux:error name="membershipLevelPerkDraft.{{ $index }}" />
                            </div>
                        @endforeach
                        <flux:button type="button" variant="ghost" size="sm" wire:click="addMembershipLevelPerk" class="w-full"><span class="inline-flex items-center gap-2"><x-dashboard.icon name="plus" class="h-4 w-4" />Add Perk</span></flux:button>
                    </div>

                    <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                        <button type="button" wire:click="closeMembershipLevelModal" class="inline-flex h-10 items-center justify-center rounded-md border border-input bg-background px-4 py-2 text-sm font-medium hover:bg-muted focus-visible:ring-2 focus-visible:ring-ring">Cancel</button>
                        <button type="submit" wire:loading.attr="disabled" wire:target="saveMembershipLevel" class="inline-flex h-10 items-center justify-center gap-2 rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90 focus-visible:ring-2 focus-visible:ring-ring disabled:opacity-50"><x-dashboard.icon name="plus" class="mr-1 h-4 w-4" /><span wire:loading.remove wire:target="saveMembershipLevel">Create Level</span><span wire:loading wire:target="saveMembershipLevel">Creating...</span></button>
                    </div>
                </form>
            </section>
        </div>
        @endteleport
    @endif
</div>
