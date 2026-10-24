<?php

use App\Models\MembershipLevel;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;

new #[Layout('components.layouts.dashboard')]
#[Title('Training')]
class extends Component {
    #[Locked]
    public ?int $editingUserId = null;

    public bool $showForm = false;

    #[Locked]
    public int $step = 1;

    public string $name = '';

    public string $username = '';

    public string $email = '';

    public string $password = '';

    public string $transactionPassword = '';

    public string $membershipLevel = '';

    public string $balance = '0.00';

    public string $referredBy = '';

    public bool $referralCommissionEnabled = false;

    public function boot(): void
    {
        abort_unless(auth()->user()?->is_admin, 403);
    }

    public function createAccount(): void
    {
        $this->closeForm();
        $this->membershipLevel = MembershipLevel::query()->orderBy('percentage')->value('name') ?? '';
        $this->showForm = true;
    }

    public function editAccount(int $id): void
    {
        $user = $this->trainingAccounts()->findOrFail($id);
        $this->closeForm();
        $this->editingUserId = $user->id;
        $this->name = (string) $user->name;
        $this->username = $user->username;
        $this->email = (string) $user->email;
        $this->transactionPassword = (string) $user->unhashed_withdrawal_password;
        $this->membershipLevel = $user->membership_level;
        $this->balance = number_format($user->balance / 100, 2, '.', '');
        $this->referredBy = (string) $user->referred_by;
        $this->referralCommissionEnabled = $user->training_referral_commission_enabled;
        $this->showForm = true;
    }

    public function nextStep(): void
    {
        $this->name = trim($this->name);
        $this->username = trim($this->username);
        $this->validate($this->credentialRules());
        $this->step = 2;
    }

    public function previousStep(): void
    {
        $this->step = 1;
        $this->resetValidation();
    }

    public function closeForm(): void
    {
        $this->reset('editingUserId', 'showForm', 'step', 'name', 'username', 'email', 'password', 'transactionPassword', 'membershipLevel', 'balance', 'referredBy', 'referralCommissionEnabled');
        $this->resetValidation();
    }

    private function credentialRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:64', 'regex:/^[A-Za-z0-9_]+$/', Rule::unique('users', 'username')->ignore($this->editingUserId)],
            'password' => [$this->editingUserId ? 'nullable' : 'required', 'string', 'min:8', 'max:255'],
            'transactionPassword' => [$this->editingUserId ? 'nullable' : 'required', 'string', 'min:4', 'max:255'],
        ];
    }

    protected function messages(): array
    {
        return [
            'username.regex' => 'Use only letters, numbers, and underscores for the username.',
            'referredBy.exists' => 'Enter an existing referral code.',
            'referredBy.required' => 'Enter a referral code to enable referral commission.',
            'referredBy.not_in' => 'An account cannot refer itself.',
        ];
    }

    public function saveAccount(): void
    {
        $user = $this->editingUserId === null ? null : $this->trainingAccounts()->findOrFail($this->editingUserId);
        $this->name = trim($this->name);
        $this->username = trim($this->username);
        $this->email = trim($this->email);
        $this->referredBy = trim($this->referredBy);

        $this->validate($this->credentialRules() + [
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user)],
            'membershipLevel' => ['required', Rule::exists('membership_levels', 'name')],
            'balance' => ['required', 'numeric', 'min:0', 'max:999999999.99', 'decimal:0,2'],
            'referredBy' => [Rule::requiredIf($this->referralCommissionEnabled), 'nullable', Rule::exists('users', 'referral_code'), Rule::notIn([$user?->referral_code])],
            'referralCommissionEnabled' => ['boolean'],
        ]);

        $email = $this->email !== '' ? $this->email : strtolower($this->username).'@training.local';
        if (User::query()->where('email', $email)->when($user, fn (Builder $query) => $query->whereKeyNot($user->id))->exists()) {
            $this->addError('username', 'The training email for this username is already in use.');

            return;
        }

        $attributes = [
            'name' => $this->name,
            'username' => $this->username,
            'email' => $email,
            'membership_level' => $this->membershipLevel,
            'balance' => (int) round((float) $this->balance * 100),
            'referred_by' => $this->referredBy ?: null,
            'training_referral_commission_enabled' => $this->referralCommissionEnabled,
        ];

        if ($this->password !== '') {
            $attributes['password'] = $this->password;
            $attributes['unhashed_password'] = $this->password;
        }

        if ($this->transactionPassword !== '') {
            $attributes['withdrawal_password'] = Hash::make($this->transactionPassword);
            $attributes['unhashed_withdrawal_password'] = $this->transactionPassword;
        }

        if ($user) {
            $user->update($attributes);
        } else {
            do {
                $referralCode = 'SH'.Str::upper(Str::random(6));
            } while (User::query()->where('referral_code', $referralCode)->exists());

            User::query()->create($attributes + [
                'is_training' => true,
                'is_admin' => false,
                'mobile_number' => '',
                'training_balance' => 0,
                'has_made_first_deposit' => false,
                'tasks_completed' => 0,
                'task_batch' => 0,
                'daily_commission' => 0,
                'total_commission' => 0,
                'processing_amount' => 0,
                'credit_score' => 100,
                'account_status' => 'active',
                'referral_code' => $referralCode,
                'timezone' => 'UTC',
            ]);
        }

        $this->closeForm();
        $this->dispatch('toast', message: $user ? 'Training account updated successfully.' : 'Training account created successfully.');
    }

    public function deleteAccount(int $id): void
    {
        $this->deleteTrainingAccount($this->trainingAccounts()->findOrFail($id));
        $this->closeForm();
        $this->dispatch('toast', message: 'Training account deleted successfully.');
    }

    public function deleteAll(): void
    {
        foreach ($this->trainingAccounts()->get() as $user) {
            $this->deleteTrainingAccount($user);
        }

        $this->closeForm();
        $this->dispatch('toast', message: 'All training accounts deleted successfully.');
    }

    private function deleteTrainingAccount(User $user): void
    {
        DB::transaction(function () use ($user): void {
            $user->tasks()->delete();
            $user->alerts()->delete();
            $user->withdrawals()->delete();
            $user->wallets()->delete();
            $user->deposits()->delete();
            DB::table('referrals')->where('user_id', $user->id)->delete();
            $user->delete();
        });

        if ($user->profile_image_path) {
            Storage::disk('public')->delete($user->profile_image_path);
        }
    }

    /** @return Builder<User> */
    private function trainingAccounts(): Builder
    {
        return User::query()->where('is_training', true)->where('is_admin', false);
    }

    public function with(): array
    {
        return [
            'accounts' => $this->trainingAccounts()->latest()->get(),
            'levels' => MembershipLevel::query()->orderBy('percentage')->get(),
        ];
    }
}; ?>

<div class="space-y-5 animate-fade-in">
    <div class="flex items-center gap-3">
        <a href="{{ route('admin.dashboard') }}" wire:navigate aria-label="Back to overview" class="inline-flex h-10 w-10 items-center justify-center rounded-md text-foreground hover:bg-muted focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
            <x-dashboard.icon name="arrow-left" class="h-5 w-5" />
        </a>
        <h1 class="font-display text-xl font-bold text-foreground">Training Accounts</h1>
    </div>

    <p class="text-sm text-muted-foreground">Create virtual accounts for training purposes. These accounts can log in to the user panel and perform tasks like normal users.</p>

    <div class="flex flex-wrap items-center gap-2">
        <button type="button" wire:click="createAccount" class="inline-flex min-h-10 items-center justify-center gap-2 rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground transition-colors hover:bg-primary/90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
            <x-dashboard.icon name="plus" class="h-4 w-4 shrink-0" /> Create Training/Virtual Account
        </button>
        @if ($accounts->isNotEmpty())
            <button type="button" wire:click="deleteAll" wire:confirm="Delete all training accounts and their related records? This cannot be undone." wire:loading.attr="disabled" class="inline-flex h-10 items-center justify-center gap-2 rounded-md bg-destructive px-4 py-2 text-sm font-medium text-destructive-foreground transition-colors hover:bg-destructive/90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring disabled:opacity-50">
                <x-dashboard.icon name="trash-2" class="h-4 w-4" /> Delete All ({{ $accounts->count() }})
            </button>
        @endif
    </div>

    <div class="space-y-3">
        @forelse ($accounts as $account)
            @php
                $membershipColor = match (strtolower($account->membership_level)) {
                    'diamond' => 'bg-blue-500 text-blue-950',
                    'gold' => 'bg-amber-500 text-amber-950',
                    'platinum' => 'bg-slate-500 text-white',
                    default => 'bg-muted text-muted-foreground',
                };
            @endphp
            <div wire:key="training-{{ $account->id }}" class="flex flex-col gap-3 rounded-xl border border-border bg-card p-4 lg:flex-row lg:items-center">
                <div class="flex min-w-0 flex-1 items-center gap-3">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-muted">
                        <x-dashboard.icon name="user-cog" class="h-5 w-5 text-muted-foreground" />
                    </div>
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="truncate text-sm font-semibold text-foreground">{{ $account->name ?: $account->username }}</span>
                            <span class="rounded-full border border-transparent px-2.5 py-0.5 text-[10px] font-semibold capitalize {{ $membershipColor }}">{{ $account->membership_level }}</span>
                            <span class="rounded-full bg-secondary px-2.5 py-0.5 text-[10px] font-semibold text-secondary-foreground">Training</span>
                        </div>
                        <p class="truncate text-xs text-muted-foreground">{{ '@'.$account->username }} · {{ $account->email }}</p>
                        <div class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-[11px] text-muted-foreground">
                            <span class="flex items-center gap-1"><x-dashboard.icon name="hash" class="h-3 w-3" /> Code: <span class="font-mono font-medium text-foreground">{{ $account->referral_code }}</span></span>
                            @if ($account->referred_by)
                                <span class="flex items-center gap-1"><x-dashboard.icon name="users" class="h-3 w-3" /> Referred by: <span class="text-foreground">{{ $account->referred_by }}</span></span>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="flex shrink-0 flex-wrap items-center gap-4 text-xs text-muted-foreground">
                    <div class="text-center"><p class="text-sm font-bold text-foreground">${{ number_format($account->balance / 100, 2, '.', '') }}</p><p>Balance</p></div>
                    <div class="flex gap-1">
                        <button type="button" wire:click="editAccount({{ $account->id }})" aria-label="Edit {{ $account->username }}" class="inline-flex h-8 items-center justify-center gap-1 rounded-md border border-input bg-background px-3 text-xs font-medium hover:bg-muted focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"><x-dashboard.icon name="square-pen" class="h-3.5 w-3.5" /> Edit</button>
                        <button type="button" wire:click="deleteAccount({{ $account->id }})" wire:confirm="Delete this training account and its related records? This cannot be undone." wire:loading.attr="disabled" aria-label="Delete {{ $account->username }}" class="inline-flex h-8 items-center justify-center gap-1 rounded-md bg-destructive px-3 text-xs font-medium text-destructive-foreground hover:bg-destructive/90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring disabled:opacity-50"><x-dashboard.icon name="trash-2" class="h-3.5 w-3.5" /> Delete</button>
                    </div>
                </div>
            </div>
        @empty
            <div class="flex min-h-44 flex-col items-center justify-center gap-3 rounded-xl border border-border bg-card p-6 text-center text-muted-foreground">
                <x-dashboard.icon name="graduation-cap" class="h-10 w-10 opacity-40" />
                <p class="text-sm">No training accounts yet. Create one to get started.</p>
            </div>
        @endforelse
    </div>

    @if ($showForm)
        @teleport('body')
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 p-4" x-data x-trap.inert.noscroll="true" @keydown.escape.window="$wire.closeForm()" wire:key="training-form-{{ $editingUserId ?? 'new' }}">
            <div class="absolute inset-0" wire:click="closeForm"></div>
            <section role="dialog" aria-modal="true" aria-labelledby="training-form-title" aria-describedby="training-form-description" @class(['relative max-h-[90dvh] w-full max-w-sm overflow-y-auto rounded-lg border border-border bg-background p-6 shadow-lg', '[&_[data-flux-field]]:gap-y-0 [&_[data-flux-label]]:text-xs [&_[data-flux-label]]:font-normal [&_[data-flux-label]]:mb-1! [&_[data-flux-control]]:bg-background [&_[data-flux-control]]:shadow-none' => $editingUserId])>
                <button type="button" wire:click="closeForm" aria-label="Close training account form" class="absolute right-4 top-4 rounded-sm p-1 text-muted-foreground hover:text-foreground focus-visible:ring-2 focus-visible:ring-ring"><x-dashboard.icon name="x" class="h-4 w-4" /></button>
                <h2 id="training-form-title" class="pr-6 text-lg font-semibold text-foreground">{{ $editingUserId ? 'Edit Training Account' : 'Create Training/Virtual Account' }}</h2>
                <p id="training-form-description" class="mt-2 text-sm text-muted-foreground">{{ $editingUserId ? 'Update training account details.' : ($step === 1 ? 'Step 1 of 2 — Enter login credentials.' : 'Step 2 of 2 — Set account details.') }}</p>

                <form wire:submit="{{ ! $editingUserId && $step === 1 ? 'nextStep' : 'saveAccount' }}" @class(['mt-6 space-y-4' => $editingUserId, 'mt-5 space-y-3' => ! $editingUserId]) wire:key="training-fields-{{ $editingUserId ?? $step }}">
                    @if ($editingUserId || $step === 1)
                        <flux:input wire:model="name" label="Full Name" required />
                        <flux:input wire:model="username" label="Username" autocomplete="off" required />
                        @if ($editingUserId)
                            <flux:input wire:model="email" type="email" label="Email" />
                        @endif
                        <flux:input wire:model="password" type="password" :label="$editingUserId ? 'New Password (leave empty to keep current)' : 'Password'" :placeholder="$editingUserId ? '••••••••' : null" autocomplete="new-password" viewable />
                        <flux:input wire:model="transactionPassword" :type="$editingUserId ? 'text' : 'password'" label="Transaction/Withdrawal Password" placeholder="e.g. 000000" autocomplete="new-password" />
                    @endif
                    @if ($editingUserId || $step === 2)
                        @if (! $editingUserId)
                            <flux:input wire:model="email" type="email" label="Email (optional)" />
                        @endif
                        <div class="grid grid-cols-2 gap-3">
                            <flux:select wire:model="membershipLevel" :label="$editingUserId ? 'Membership' : 'Membership Level'" required>
                                <option value="">Select a level</option>
                                @foreach ($levels as $level)
                                    <option value="{{ $level->name }}">{{ $level->name }}</option>
                                @endforeach
                            </flux:select>
                            <flux:input wire:model="balance" type="number" :label="$editingUserId ? 'Balance ($)' : 'Starting Balance ($)'" min="0" max="999999999.99" step="0.01" required />
                        </div>
                        @if ($editingUserId)
                            <flux:input label="Invitation Code" :value="$accounts->firstWhere('id', $editingUserId)?->referral_code" readonly class:input="bg-muted!" />
                        @endif
                        <div class="space-y-1">
                            <flux:input wire:model="referredBy" :label="$editingUserId ? 'Referral Code (referrer)' : 'Invitation/Referral Code (optional)'" placeholder="Enter referrer's invitation code" />
                            @if (! $editingUserId)
                                <p class="text-[10px] text-muted-foreground">The referrer earns 20% commission on profit from this account's tasks.</p>
                            @endif
                        </div>
                        <flux:checkbox wire:model="referralCommissionEnabled" label="Enable 20% referral commission for referrer" />
                    @endif
                    <div class="flex justify-end gap-2 pt-2">
                        @if (! $editingUserId && $step === 1)
                            <flux:button type="button" wire:click="closeForm">Cancel</flux:button>
                        @elseif (! $editingUserId)
                            <flux:button type="button" wire:click="previousStep">Back</flux:button>
                        @endif
                        <button type="submit" wire:loading.attr="disabled" @class(['inline-flex h-10 items-center justify-center rounded-md bg-primary px-4 text-sm font-medium text-primary-foreground hover:bg-primary/90 focus-visible:ring-2 focus-visible:ring-ring disabled:opacity-50', 'w-full' => $editingUserId])>
                            <span wire:loading.remove wire:target="nextStep,saveAccount">{{ $editingUserId ? 'Save Changes' : ($step === 1 ? 'Next' : 'Create Account') }}</span>
                            <span wire:loading wire:target="nextStep,saveAccount">{{ $step === 1 && ! $editingUserId ? 'Next...' : 'Saving...' }}</span>
                        </button>
                    </div>
                </form>
            </section>
        </div>
        @endteleport
    @endif
</div>
