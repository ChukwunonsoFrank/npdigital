<div x-data="{
    open: false,
    errorsVisible: false,
    editingId: null,
    label: '',
    crypto: '',
    address: '',
}" x-on:close-wallet-modal.window="open = false">
    <div class="animate-fade-in space-y-4">
        <div>
            <h1 class="font-display text-xl font-bold text-foreground">Wallet</h1>
            <p class="mt-0.5 text-xs text-muted-foreground">Manage your crypto deposits and withdrawals</p>
        </div>

        <div class="grid grid-cols-2 gap-3">
            <div
                class="relative min-w-0 overflow-hidden rounded-2xl border-[2.5px] border-primary/30 bg-gradient-to-br from-primary/10 via-primary/5 to-background p-3 shadow-lg shadow-primary/20 sm:p-5">
                <div class="pointer-events-none absolute -right-12 -top-12 h-32 w-32 rounded-full bg-primary/20 blur-2xl">
                </div>
                <div class="pointer-events-none absolute inset-0 opacity-[0.04]"
                    style="background-image: radial-gradient(var(--color-foreground) 1px, transparent 1px); background-size: 14px 14px;">
                </div>

                <div class="relative flex min-w-0 items-start gap-2 sm:gap-3">
                    <div
                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-primary to-primary/70 shadow-lg shadow-primary/30 ring-2 ring-primary/20 sm:h-12 sm:w-12">
                        <x-dashboard.icon name="wallet" class="h-6 w-6 text-primary-foreground" />
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-1.5">
                            <span class="inline-block h-1.5 w-1.5 animate-pulse rounded-full bg-emerald-500"></span>
                            <p class="text-[10px] font-bold uppercase tracking-[0.12em] text-muted-foreground">
                                Available Balance</p>
                        </div>
                        <p
                            class="mt-1 flex min-w-0 items-start font-display text-xl font-bold leading-none tracking-tight text-foreground sm:text-2xl">
                            <span class="mr-0.5 shrink-0 align-top text-xs text-muted-foreground sm:text-base">USD</span>
                            <span
                                class="inline-block min-w-0 max-w-full flex-1 overflow-hidden whitespace-nowrap leading-none">${{ number_format($availableBalance, 2) }}</span>
                        </p>
                    </div>
                </div>
            </div>

            <div class="relative min-w-0 overflow-hidden rounded-2xl border-[2.5px] p-3 sm:p-5"
                style="border-color: hsl(var(--membership-gold) / 0.4); background: linear-gradient(135deg, hsl(var(--membership-gold) / 0.18), hsl(var(--membership-gold) / 0.05) 60%, var(--color-background)); box-shadow: 0 8px 24px -8px hsl(var(--membership-gold) / 0.35), inset 0 1px 0 0 hsl(var(--membership-gold) / 0.3);">
                <div class="pointer-events-none absolute -right-12 -top-12 h-32 w-32 rounded-full blur-2xl"
                    style="background-color: hsl(var(--membership-gold) / 0.25);"></div>
                <div class="pointer-events-none absolute inset-0 opacity-[0.04]"
                    style="background-image: radial-gradient(var(--color-foreground) 1px, transparent 1px); background-size: 14px 14px;">
                </div>

                <div class="relative flex min-w-0 items-start gap-2 sm:gap-3">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl sm:h-12 sm:w-12"
                        style="background: linear-gradient(135deg, hsl(var(--membership-gold)), hsl(var(--membership-gold) / 0.7)); box-shadow: 0 6px 16px -4px hsl(var(--membership-gold) / 0.5);">
                        <x-dashboard.icon name="clock" class="h-6 w-6 text-white" />
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-1.5">
                            <span class="inline-block h-1.5 w-1.5 animate-pulse rounded-full"
                                style="background-color: hsl(var(--membership-gold));"></span>
                            <p class="text-[10px] font-bold uppercase tracking-[0.12em]"
                                style="color: hsl(var(--membership-gold));">On Hold Balance</p>
                        </div>
                        <p class="mt-1 flex min-w-0 items-start font-display text-xl font-bold leading-none tracking-tight sm:text-2xl"
                            style="color: hsl(var(--membership-gold));">
                            <span class="mr-0.5 shrink-0 align-top text-xs opacity-70 sm:text-base">USD</span>
                            <span
                                class="inline-block min-w-0 max-w-full flex-1 overflow-hidden whitespace-nowrap leading-none">${{ number_format($onHoldBalance, 2) }}</span>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-3">
            <a href="{{ route('dashboard.deposit') }}" wire:navigate
                class="inline-flex h-12 items-center justify-center gap-2 whitespace-nowrap rounded-xl border-2 border-primary bg-primary px-4 text-sm font-bold text-primary-foreground transition-colors hover:bg-primary/90">
                <x-dashboard.icon name="arrow-down-to-line" class="h-4 w-4" />
                Deposit
            </a>
            <a href="{{ route('dashboard.withdraw') }}" wire:navigate
                class="inline-flex h-12 items-center justify-center gap-2 whitespace-nowrap rounded-xl border-2 border-border bg-background px-4 text-sm font-bold text-foreground transition-colors hover:bg-muted">
                <x-dashboard.icon name="arrow-up-from-line" class="h-4 w-4" />
                Withdraw
            </a>
        </div>

        <div class="rounded-xl border-2 border-border p-4">
            <div class="mb-3 flex items-center justify-between">
                <h2 class="font-display text-sm font-bold text-foreground">Saved Wallets</h2>
                <button type="button"
                    @click="open = true; errorsVisible = false; editingId = null; label = ''; crypto = ''; address = ''"
                    class="inline-flex h-8 items-center gap-1 whitespace-nowrap rounded-lg border-2 border-border bg-background px-3 text-xs font-bold text-foreground transition-colors hover:bg-muted">
                    <x-dashboard.icon name="plus" class="h-3.5 w-3.5" />
                    Add Wallet
                </button>
            </div>

            @if ($wallets->isEmpty())
                <p class="py-4 text-center text-xs text-muted-foreground">No saved wallets yet.</p>
            @else
                <div class="space-y-2">
                    @foreach ($wallets as $wallet)
                        <div wire:key="wallet-{{ $wallet->id }}"
                            class="flex items-center gap-3 rounded-lg border border-border bg-muted/40 p-3">
                            <span
                                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary">
                                <x-dashboard.icon name="wallet" class="h-4 w-4" />
                            </span>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-bold text-foreground">{{ $wallet->label }}</p>
                                <p class="truncate font-mono text-[11px] text-muted-foreground">{{ $wallet->address }}
                                </p>
                            </div>
                            <span
                                class="shrink-0 rounded-full bg-primary/10 px-2 py-0.5 text-[10px] font-bold uppercase text-primary">{{ $wallet->cryptocurrency }}</span>
                            <button type="button"
                                data-wallet-id="{{ $wallet->id }}"
                                data-wallet-label="{{ $wallet->label }}"
                                data-wallet-crypto="{{ $wallet->cryptocurrency }}"
                                data-wallet-address="{{ $wallet->address }}"
                                @click="open = true; errorsVisible = false; editingId = $el.dataset.walletId; label = $el.dataset.walletLabel; crypto = $el.dataset.walletCrypto; address = $el.dataset.walletAddress"
                                aria-label="Edit {{ $wallet->label }}"
                                class="shrink-0 text-primary transition-colors hover:text-primary/80">
                                <x-dashboard.icon name="pencil" class="h-3.5 w-3.5" />
                            </button>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="rounded-xl border-2 border-border p-4">
            <h2 class="mb-3 font-display text-sm font-bold text-foreground">Transaction History</h2>

            @if ($transactions->isEmpty())
                <p class="py-4 text-center text-xs text-muted-foreground">No transactions yet.</p>
            @else
                <div class="divide-y divide-border">
                    @foreach ($transactions as $index => $transaction)
                        <div wire:key="transaction-{{ $index }}"
                            class="flex items-center gap-3 py-3 first:pt-0 last:pb-0">
                            <span
                                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg {{ $transaction['type'] === 'Deposit' ? 'bg-primary/10 text-primary' : 'bg-muted text-muted-foreground' }}">
                                <x-dashboard.icon
                                    :name="$transaction['type'] === 'Deposit' ? 'arrow-down-to-line' : 'arrow-up-from-line'"
                                    class="h-4 w-4" />
                            </span>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-bold text-foreground">{{ $transaction['type'] }}</p>
                                <p class="truncate text-[11px] text-muted-foreground">
                                    {{ $cryptocurrencies[$transaction['cryptocurrency']] ?? (string) $transaction['cryptocurrency'] }}
                                    &middot; {{ $transaction['date']?->format('M d, Y h:i A') }}
                                </p>
                            </div>
                            <div class="shrink-0 text-right">
                                <p class="text-sm font-bold text-foreground">
                                    ${{ number_format($transaction['amount'] / 100, 2) }}</p>
                                <span
                                    class="text-[10px] font-bold uppercase {{ $transaction['status'] === 'pending' ? 'text-amber-500' : ($transaction['status'] === 'declined' ? 'text-destructive' : 'text-emerald-600') }}">{{ $transaction['status'] }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <div x-cloak x-show="open" x-transition.opacity.duration.200ms
        class="fixed inset-0 z-50 bg-black/50" @click="open = false"></div>

    <div x-cloak x-show="open" role="dialog" aria-modal="true" aria-label="Wallet"
        x-transition:enter="transition duration-300 ease-out" x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition duration-200 ease-in"
        x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
        class="fixed left-1/2 top-1/2 z-50 w-[calc(100%_-_2rem)] max-w-lg -translate-x-1/2 -translate-y-1/2 overflow-hidden rounded-2xl bg-card p-6 shadow-xl">
        <button type="button" @click="open = false" aria-label="Close"
            class="absolute right-4 top-4 text-muted-foreground transition-colors hover:text-foreground">
            <x-dashboard.icon name="x" class="h-4 w-4" />
        </button>

        <h2 class="pr-6 text-center font-display text-lg font-semibold text-foreground sm:text-left">
            <span x-text="editingId ? 'Edit Wallet' : 'Add New Wallet'">Add New Wallet</span>
        </h2>

        <div class="mt-4 space-y-4">
            <div class="space-y-2">
                <label class="text-sm font-medium text-foreground" for="wallet_label">Wallet Label</label>
                <input id="wallet_label" type="text" x-model="label" placeholder="e.g. My BTC Wallet"
                    class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm text-foreground placeholder:text-muted-foreground focus:border-ring focus:outline-none focus:ring-2 focus:ring-ring/30">
                @error('wallet_label')
                    <p x-show="errorsVisible" class="text-xs text-destructive">{{ $message }}</p>
                @enderror
            </div>

            <div class="space-y-2">
                <label class="text-sm font-medium text-foreground" for="wallet_cryptocurrency">Cryptocurrency</label>
                <div class="relative">
                    <select id="wallet_cryptocurrency" x-model="crypto"
                        class="h-10 w-full appearance-none rounded-md border border-input bg-background px-3 pr-9 text-sm text-foreground focus:border-ring focus:outline-none focus:ring-2 focus:ring-ring/30">
                        <option value="">Select crypto</option>
                        @foreach ($cryptocurrencies as $code => $label)
                            <option value="{{ $code }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <x-dashboard.icon name="chevron-down"
                        class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                </div>
                @error('wallet_cryptocurrency')
                    <p x-show="errorsVisible" class="text-xs text-destructive">{{ $message }}</p>
                @enderror
            </div>

            <div class="space-y-2">
                <label class="text-sm font-medium text-foreground" for="wallet_address">Wallet Address</label>
                <input id="wallet_address" type="text" x-model="address"
                    placeholder="Enter wallet address"
                    class="h-10 w-full rounded-md border border-input bg-background px-3 font-mono text-xs text-foreground placeholder:text-muted-foreground focus:border-ring focus:outline-none focus:ring-2 focus:ring-ring/30">
                @error('wallet_address')
                    <p x-show="errorsVisible" class="text-xs text-destructive">{{ $message }}</p>
                @enderror
            </div>

            <button type="button"
                x-on:click="errorsVisible = true; editingId ? $wire.updateWallet(editingId, label, crypto, address) : $wire.addWallet(label, crypto, address)"
                wire:loading.attr="disabled" wire:target="addWallet,updateWallet"
                class="h-10 w-full rounded-md bg-primary px-4 text-sm font-medium text-primary-foreground transition-colors hover:bg-primary/90 disabled:opacity-60">
                <span wire:loading.remove wire:target="addWallet,updateWallet">Save Wallet</span>
                <span wire:loading wire:target="addWallet,updateWallet">Saving...</span>
            </button>
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
