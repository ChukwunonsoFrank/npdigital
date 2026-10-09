@php
    $details = [
        [
            'field' => 'name',
            'label' => 'Full Name',
            'value' => ($user->name ?? null) ?: $user->username,
            'icon' => 'circle-user',
            'color' => 'text-blue-500',
            'editable' => true,
        ],
        [
            'field' => 'username',
            'label' => 'Username',
            'value' => $user->username,
            'icon' => 'circle-user',
            'color' => 'text-violet-500',
            'editable' => true,
        ],
        [
            'field' => 'email',
            'label' => 'Email',
            'value' => ($user->email ?? null) ?: 'Not set',
            'icon' => 'mail',
            'color' => 'text-green-500',
            'editable' => true,
        ],
        [
            'field' => 'login_password',
            'label' => 'Login Password',
            'value' => '••••••••',
            'icon' => 'lock',
            'color' => 'text-red-500',
            'editable' => true,
            'saveAction' => 'saveLoginPassword',
        ],
        [
            'field' => 'transaction_password',
            'label' => 'Transaction Password',
            'value' => ($user->withdrawal_password ?? null) ? '••••••••' : 'Not set',
            'icon' => 'key-round',
            'color' => 'text-amber-500',
            'editable' => true,
            'saveAction' => 'saveTransactionPassword',
        ],
        [
            'field' => 'date_joined',
            'label' => 'Date Joined',
            'value' => $user->created_at?->format('Y-m-d') ?? '—',
            'icon' => 'calendar',
            'color' => 'text-cyan-500',
            'editable' => false,
        ],
        [
            'field' => 'membership_level',
            'label' => 'Membership Level',
            'value' => $user->membership_level ?: 'Normal',
            'icon' => 'star',
            'color' => 'text-yellow-500',
            'editable' => false,
        ],
        [
            'field' => 'credit_score',
            'label' => 'Credit Score',
            'value' => $creditScore,
            'icon' => 'credit-card',
            'color' => 'text-purple-500',
            'editable' => false,
        ],
    ];

    $menu = [
        ['label' => 'Deposit', 'icon' => 'arrow-down-to-line', 'url' => route('dashboard.deposit'), 'color' => 'text-green-500'],
        ['label' => 'Withdraw', 'icon' => 'arrow-up-from-line', 'url' => route('dashboard.withdraw'), 'color' => 'text-orange-500'],
        ['label' => 'Activities', 'icon' => 'activity', 'url' => route('dashboard.activities'), 'color' => 'text-violet-500'],
        ['label' => 'Certification', 'icon' => 'award', 'url' => route('dashboard.certificate'), 'color' => 'text-cyan-500'],
        ['label' => 'FAQ', 'icon' => 'circle-help', 'url' => route('dashboard.faqs'), 'color' => 'text-amber-500'],
        ['label' => 'About Us', 'icon' => 'file-text', 'url' => route('dashboard.about-us'), 'color' => 'text-blue-500'],
    ];
@endphp

<div x-data="{ copied: false, detailsOpen: false }" class="animate-fade-in">
    <div class="mx-auto w-full max-w-2xl space-y-4 pb-24">
        <div class="overflow-hidden rounded-xl border-2 border-border bg-card text-card-foreground shadow-sm">
            <div class="relative h-20 bg-gradient-to-r from-primary/20 via-primary/10 to-accent/20"></div>

            <div class="-mt-10 px-4 pb-4">
                <div class="flex items-end gap-3">
                    <div class="relative">
                        <input id="account-avatar" type="file" accept="image/*" class="hidden"
                            wire:model="avatar">

                        <label for="account-avatar" class="block cursor-pointer">
                            <span
                                class="flex h-16 w-16 items-center justify-center overflow-hidden rounded-full border-[3px] border-background bg-primary/10 shadow-lg">
                                @if ($user->profile_image_path ?? null)
                                    <img src="{{ route('storage.file', $user->profile_image_path) }}" alt="Profile photo"
                                        class="h-full w-full object-cover">
                                @else
                                    <x-dashboard.icon name="circle-user" class="h-8 w-8 text-primary" />
                                @endif
                            </span>
                        </label>

                        <label for="account-avatar" aria-label="Change profile photo"
                            class="absolute -bottom-1 -right-1 flex h-6 w-6 cursor-pointer items-center justify-center rounded-full bg-primary text-primary-foreground shadow-md transition-transform hover:scale-110">
                            <x-dashboard.icon name="camera" class="h-3 w-3" />
                        </label>

                        <span wire:loading wire:target="avatar"
                            class="absolute inset-0 flex items-center justify-center rounded-full bg-background/70 text-primary">
                            <x-dashboard.icon name="refresh-cw" class="h-5 w-5 animate-spin" />
                        </span>
                    </div>

                    <div class="min-w-0 flex-1 pb-1">
                        <h2 class="truncate font-display text-lg font-bold text-foreground">
                            {{ ($user->name ?? null) ?: $user->username }}</h2>
                        <div class="mt-0.5 flex items-center gap-2">
                            <span
                                class="inline-flex items-center rounded-full bg-gray-500 px-2 py-0.5 text-[10px] font-bold text-white">{{ $user->membership_level ?: 'Normal' }}</span>
                            <span class="truncate text-xs text-muted-foreground">&#64;{{ $user->username }}</span>
                        </div>
                    </div>
                </div>

                <div class="mt-4 grid grid-cols-3 gap-2">
                    <div class="rounded-lg border border-border bg-muted/50 p-2 text-center">
                        <p class="text-[10px] font-medium text-muted-foreground">Balance</p>
                        <p class="truncate font-display text-base font-bold text-green-500">
                            ${{ number_format($balance, 2) }}</p>
                    </div>

                    <div class="rounded-lg border-2 border-green-500/20 bg-gradient-to-br from-green-500/5 to-green-500/15 p-2 text-center">
                        <div class="mb-0.5 flex items-center justify-center gap-1">
                            <x-dashboard.icon name="dollar-sign" class="h-3 w-3 text-green-600" />
                            <p class="text-[10px] font-bold uppercase leading-tight text-green-600">Today's Earnings</p>
                        </div>
                        <p class="truncate font-display text-base font-bold text-green-500">
                            ${{ number_format($todaysCommission, 2) }}</p>
                    </div>

                    <div class="rounded-lg border border-border bg-muted/50 p-2 text-center">
                        <p class="text-[10px] font-medium text-muted-foreground">Member Since</p>
                        <p class="truncate font-display text-xs font-bold text-foreground">
                            {{ $user->created_at?->format('Y-m-d') ?? '—' }}</p>
                    </div>
                </div>

                <div class="mt-3 flex items-center justify-between rounded-lg border border-primary/20 bg-primary/5 p-2.5">
                    <div class="flex items-center gap-2">
                        <x-dashboard.icon name="shield" class="h-4 w-4 text-primary" />
                        <div>
                            <p class="text-[10px] font-medium text-muted-foreground">Referral Code</p>
                            <p class="font-mono text-sm font-bold text-foreground">{{ $user->referral_code }}</p>
                        </div>
                    </div>
                    <button type="button" aria-label="Copy referral code"
                        @click="navigator.clipboard.writeText('{{ $user->referral_code }}'); copied = true; if (window.showToast) window.showToast('Referral code copied.'); setTimeout(() => copied = false, 2000)"
                        class="inline-flex h-7 items-center justify-center rounded-md px-2 text-primary transition-colors hover:bg-accent hover:text-accent-foreground">
                        <x-dashboard.icon x-show="!copied" name="copy" class="h-3.5 w-3.5" />
                        <x-dashboard.icon x-show="copied" x-cloak name="check" class="h-3.5 w-3.5" />
                    </button>
                </div>

                <div class="mt-3 flex items-center justify-between rounded-lg border border-border bg-muted/50 p-2.5">
                    <div class="flex items-center gap-2">
                        <x-dashboard.icon name="credit-card" class="h-4 w-4 text-purple-500" />
                        <div>
                            <p class="text-[10px] font-medium text-muted-foreground">Credit Score</p>
                            <p class="font-display text-sm font-bold text-primary">{{ $creditScore }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <a href="{{ route('dashboard.event') }}" wire:navigate
            class="block cursor-pointer rounded-xl border-2 border-border bg-card text-card-foreground shadow-sm transition-colors hover:bg-accent/50">
            <div class="flex items-center gap-3 p-4">
                <x-dashboard.icon name="calendar-check" class="h-5 w-5 text-primary" />
                <span class="flex-1 font-display text-sm font-bold text-foreground">Daily Events</span>
                <x-dashboard.icon name="chevron-right" class="h-4 w-4 text-muted-foreground" />
            </div>
        </a>

        <div class="rounded-xl border-2 border-border bg-card text-card-foreground shadow-sm">
            <div class="p-4">
                <button type="button" @click="detailsOpen = !detailsOpen"
                    class="flex w-full items-center justify-between">
                    <h3 class="font-display text-sm font-bold text-foreground">Account Details</h3>
                    <x-dashboard.icon name="chevron-down" class="h-4 w-4 text-muted-foreground transition-transform"
                        x-bind:class="detailsOpen ? 'rotate-180' : ''" />
                </button>

                <div x-show="detailsOpen" x-cloak x-transition:enter="transition duration-200 ease-out"
                    x-transition:enter-start="opacity-0 -translate-y-1"
                    x-transition:enter-end="opacity-100 translate-y-0" class="mt-2">
                    <div class="mb-1 h-px w-full bg-border"></div>

                    @foreach ($details as $detail)
                        <div wire:key="detail-{{ $detail['field'] }}"
                            class="flex items-center justify-between px-1 py-3">
                            <div class="flex min-w-0 flex-1 items-center gap-3">
                                <x-dashboard.icon :name="$detail['icon']"
                                    class="h-4 w-4 shrink-0 {{ $detail['color'] }}" />
                                <div class="min-w-0 flex-1">
                                    <p class="text-[10px] font-medium uppercase tracking-wider text-muted-foreground">
                                        {{ $detail['label'] }}</p>

                                    @if ($detail['editable'] && $editingField === $detail['field'])
                                        @if (in_array($detail['field'], ['login_password', 'transaction_password']))
                                            <div x-data="{ show: false }" class="mt-0.5 flex items-center gap-1.5">
                                                <input :type="show ? 'text' : 'password'" wire:model="editValue"
                                                    class="h-8 w-full rounded-md border border-input bg-background px-2 text-sm font-semibold text-foreground focus:border-ring focus:outline-none focus:ring-2 focus:ring-ring/30">
                                                <button type="button" @click="show = !show"
                                                    aria-label="Toggle password visibility"
                                                    class="shrink-0 text-muted-foreground transition-colors hover:text-foreground">
                                                    <x-dashboard.icon name="eye" class="h-4 w-4" x-show="!show" />
                                                    <x-dashboard.icon name="eye-off" class="h-4 w-4" x-show="show"
                                                        x-cloak />
                                                </button>
                                            </div>
                                            @error('editValue')
                                                <p class="mt-0.5 text-[10px] text-destructive">{{ $message }}</p>
                                            @enderror
                                        @else
                                            <input type="text" wire:model="editValue" wire:keydown.enter="saveField"
                                                class="mt-0.5 h-8 w-full rounded-md border border-input bg-background px-2 text-sm font-semibold text-foreground focus:border-ring focus:outline-none focus:ring-2 focus:ring-ring/30">
                                            @error('editValue')
                                                <p class="mt-0.5 text-[10px] text-destructive">{{ $message }}</p>
                                            @enderror
                                        @endif
                                    @else
                                        <p class="truncate text-sm font-semibold text-foreground">{{ $detail['value'] }}
                                        </p>
                                    @endif
                                </div>
                            </div>

                            <div
                                class="ml-2 flex shrink-0 items-center gap-1 {{ $editingField === $detail['field'] ? 'mt-[17px]' : '' }}">
                                @if ($detail['editable'])
                                    @if ($editingField === $detail['field'])
                                        <button type="button"
                                            wire:click="{{ $detail['saveAction'] ?? 'saveField' }}" aria-label="Save"
                                            class="text-emerald-600 transition-colors hover:text-emerald-500">
                                            <x-dashboard.icon name="check" class="h-4 w-4" />
                                        </button>
                                        <button type="button" wire:click="cancelEdit" aria-label="Cancel"
                                            class="text-muted-foreground transition-colors hover:text-foreground">
                                            <x-dashboard.icon name="x" class="h-4 w-4" />
                                        </button>
                                    @else
                                        <button type="button" wire:click="startEdit('{{ $detail['field'] }}')"
                                            aria-label="Edit {{ $detail['label'] }}"
                                            class="text-primary transition-colors hover:text-primary/80">
                                            <x-dashboard.icon name="pencil" class="h-3.5 w-3.5" />
                                        </button>
                                    @endif
                                @endif
                            </div>
                        </div>

                        @if (!$loop->last)
                            <div class="h-px w-full bg-border"></div>
                        @endif
                    @endforeach
                </div>
            </div>
        </div>

        <div class="rounded-xl border-2 border-border bg-card text-card-foreground shadow-sm">
            <div class="p-2">
                @foreach ($menu as $item)
                    <div wire:key="menu-{{ $item['label'] }}">
                        <a href="{{ $item['url'] }}" wire:navigate
                            class="flex w-full items-center gap-3 rounded-lg px-3 py-3 transition-colors hover:bg-muted/50">
                            <x-dashboard.icon :name="$item['icon']" class="h-[18px] w-[18px] {{ $item['color'] }}" />
                            <span class="text-sm font-semibold text-foreground">{{ $item['label'] }}</span>
                        </a>
                        @if (!$loop->last)
                            <div class="h-px w-full bg-border"></div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>

        <div class="rounded-xl border-2 border-destructive/20 bg-card text-card-foreground shadow-sm">
            <form method="POST" action="{{ route('logout') }}" class="p-2">
                @csrf
                <button type="submit"
                    class="flex w-full items-center justify-center gap-2 rounded-lg py-3 text-destructive transition-colors hover:bg-destructive/5">
                    <x-dashboard.icon name="log-out" class="h-4 w-4" />
                    <span class="text-sm font-bold">Logout</span>
                </button>
            </form>
        </div>
    </div>
</div>

@script
    <script>
        $wire.on('toast', (event) => {
            if (window.showToast) {
                window.showToast(event.message);
            }
        });
    </script>
@endscript
