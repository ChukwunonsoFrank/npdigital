<div x-data>
    <div class="animate-fade-in mx-auto max-w-2xl space-y-6">
        <div class="flex items-center gap-3">
            <a href="{{ route('dashboard.wallet') }}" wire:navigate
                class="inline-flex h-9 items-center gap-1.5 whitespace-nowrap rounded-md px-2 text-sm font-medium text-foreground transition-colors hover:bg-muted">
                <x-dashboard.icon name="arrow-left" class="h-4 w-4" />
                Back
            </a>
            <h1 class="font-display text-2xl font-bold text-foreground">Withdraw Crypto</h1>
        </div>

        <div class="card-elevated rounded-lg border border-border bg-card p-6 pt-6">
            <div class="space-y-5">
                <div class="space-y-2">
                    <label class="text-sm font-medium text-foreground" for="cryptocurrency">Select Cryptocurrency</label>
                    <div class="relative">
                        <select id="cryptocurrency" wire:model="cryptocurrency"
                            class="h-10 w-full appearance-none rounded-md border border-input bg-background px-3 pr-9 text-sm text-foreground focus:border-ring focus:outline-none focus:ring-2 focus:ring-ring/30">
                            <option value="">Choose a cryptocurrency</option>
                            @foreach ($cryptocurrencies as $code => $label)
                                <option value="{{ $code }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        <x-dashboard.icon name="chevron-down"
                            class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                    </div>
                    @error('cryptocurrency')
                        <p class="text-xs text-destructive">{{ $message }}</p>
                    @enderror
                </div>

                <div class="space-y-2">
                    <label class="text-sm font-medium text-foreground" for="wallet_id">Select Saved Wallet</label>
                    <div class="relative">
                        <select id="wallet_id" wire:model="wallet_id"
                            class="h-10 w-full appearance-none rounded-md border border-input bg-background px-3 pr-9 text-sm text-foreground focus:border-ring focus:outline-none focus:ring-2 focus:ring-ring/30">
                            <option value="">Choose a saved wallet</option>
                            @foreach ($wallets as $wallet)
                                <option value="{{ $wallet->id }}">{{ $wallet->label }} — {{ $wallet->cryptocurrency }}</option>
                            @endforeach
                        </select>
                        <x-dashboard.icon name="chevron-down"
                            class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                    </div>
                    @error('wallet_id')
                        <p class="text-xs text-destructive">{{ $message }}</p>
                    @enderror
                </div>

                <div class="space-y-2">
                    <label class="text-sm font-medium text-foreground" for="amount">Amount (USD)</label>
                    <input id="amount" type="number" min="10" step="0.01" wire:model="amount"
                        placeholder="Enter amount"
                        class="h-10 w-full rounded-md border border-input bg-background px-3 text-base text-foreground placeholder:text-muted-foreground focus:border-ring focus:outline-none focus:ring-2 focus:ring-ring/30 md:text-sm">
                    <p class="flex flex-wrap items-center gap-1 text-xs text-muted-foreground">
                        <span>Available:</span>
                        <span class="font-bold text-foreground">${{ number_format($availableBalance, 2) }}</span>
                        <span>— Min: $10.00</span>
                    </p>
                    @error('amount')
                        <p class="text-xs text-destructive">{{ $message }}</p>
                    @enderror
                </div>

                <div class="space-y-2">
                    <label class="text-sm font-medium text-foreground" for="withdrawal_password">Withdrawal Password</label>
                    <input id="withdrawal_password" type="password" wire:model="withdrawal_password"
                        placeholder="Enter withdrawal password"
                        class="h-10 w-full rounded-md border border-input bg-background px-3 text-base text-foreground placeholder:text-muted-foreground focus:border-ring focus:outline-none focus:ring-2 focus:ring-ring/30 md:text-sm">
                    @error('withdrawal_password')
                        <p class="text-xs text-destructive">{{ $message }}</p>
                    @enderror
                </div>

                <button type="button" wire:click="submitWithdrawal" wire:loading.attr="disabled"
                    class="inline-flex h-11 w-full items-center justify-center gap-2 whitespace-nowrap rounded-md bg-primary px-8 text-sm font-medium text-primary-foreground transition-colors hover:bg-primary/90 disabled:opacity-60">
                    <x-dashboard.icon name="arrow-up-from-line" class="h-4 w-4" />
                    Submit Withdrawal Request
                </button>
            </div>
        </div>
    </div>
</div>
