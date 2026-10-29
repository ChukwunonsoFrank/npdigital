<?php

namespace App\Livewire\Admin;

use App\Models\CompletedTask;
use App\Models\MembershipLevel;
use App\Models\User;
use App\Support\CompletesTasks;
use App\Support\ProductWarehouse;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.dashboard')]
class Users extends Component
{
    use CompletesTasks;
    use WithPagination;

    #[Url(as: 'search', keep: true)]
    public string $query = '';

    #[Locked]
    public bool $taskSummary = false;

    public ?int $selectedUserId = null;

    public ?int $editingUserId = null;

    public string $editName = '';

    public string $editUsername = '';

    public string $editEmail = '';

    public string $editMobileNumber = '';

    public string $editPassword = '';

    public string $editTransactionPassword = '';

    public string $editCreditScore = '';

    public ?int $upgradeTargetUserId = null;

    public string $upgradeTargetLevel = '';

    public bool $showUpgradeMembership = false;

    public string $upgradeMembershipLevel = '';

    public bool $showDowngradeMembership = false;

    public string $downgradeMembershipLevel = '';

    public ?int $manageFundsUserId = null;

    public string $fundsAction = 'add_funds';

    public string $fundsAmount = '';

    public bool $showAdjustNegative = false;

    public string $negativeBalanceAmount = '';

    public bool $showGoldenEgg = false;

    public string $goldenEggAmount = '';

    public bool $showSetProducts = false;

    public function boot(): void
    {
        if ($this->taskSummary) {
            abort_unless(Auth::user()?->is_admin, 403);
        }
    }

    #[On('show-task-user')]
    public function showTaskUser(int $userId): void
    {
        abort_unless(Auth::user()?->is_admin, 403);

        if (! $this->taskSummary) {
            return;
        }

        $this->closeUserDetails();
        $this->closeUpgradeTarget();
        $this->closeUpgradeMembership();
        $this->showUserDetails($userId);
    }

    public function openSetProducts(): void
    {
        abort_unless(Auth::user()?->is_admin, 403);

        if (! User::query()->where('is_admin', false)->find($this->selectedUserId)) {
            return;
        }

        $this->resetValidation();
        $this->showSetProducts = true;
    }

    public function closeSetProducts(): void
    {
        $this->showSetProducts = false;
        $this->resetValidation();
    }

    public function assignWarehouseGroup(int $day, string $group): void
    {
        abort_unless(Auth::user()?->is_admin, 403);
        abort_unless($day >= 1 && $day <= ProductWarehouse::DAY_COUNT && in_array($group, ProductWarehouse::GROUPS, true), 422);

        $user = User::query()->where('is_admin', false)->findOrFail($this->selectedUserId);
        $level = strtolower($user->membership_level);
        abort_unless(ProductWarehouse::isValidLevel($level), 422);

        if ($user->remainingWarehouseProducts($day, $group) === []) {
            $this->addError('warehouseGroup', 'This group is empty or the user has already submitted all its products.');

            return;
        }

        $user->update([
            'warehouse_groups' => [
                ...($user->warehouse_groups ?? []),
                $level => ['day' => $day, 'group' => $group],
            ],
        ]);

        $this->closeUserDetails();
        $this->dispatch('toast', message: 'Warehouse group assigned. It will apply after any current task.');
    }

    public function openAssignOrders(): void
    {
        abort_unless(Auth::user()?->is_admin, 403);

        $user = User::query()->where('is_admin', false)->find($this->selectedUserId);

        if (! $user) {
            return;
        }

        $this->redirect(route('admin.dashboard.assign-orders', ['user' => $user->id]));
    }

    public function openAdjustNegative(): void
    {
        abort_unless(Auth::user()?->is_admin, 403);

        $user = User::query()->where('is_admin', false)->find($this->selectedUserId);

        if (! $user) {
            return;
        }

        $this->negativeBalanceAmount = number_format((int) $user->negative_balance / 100, 2, '.', '');
        $this->resetValidation();
        $this->showAdjustNegative = true;
    }

    public function closeAdjustNegative(): void
    {
        $this->showAdjustNegative = false;
        $this->reset('negativeBalanceAmount');
        $this->resetValidation();
    }

    public function saveNegativeBalance(): void
    {
        abort_unless(Auth::user()?->is_admin, 403);

        $user = User::query()->where('is_admin', false)->find($this->selectedUserId);

        if (! $user) {
            $this->closeAdjustNegative();

            return;
        }

        $validated = $this->validate([
            'negativeBalanceAmount' => ['required', 'numeric', 'min:0', 'max:99999999.99', 'decimal:0,2'],
        ], [
            'negativeBalanceAmount.required' => 'Enter the negative balance amount.',
        ]);

        $user->update([
            'negative_balance' => (int) round(((float) $validated['negativeBalanceAmount']) * 100),
        ]);

        $this->closeAdjustNegative();
        $this->dispatch('toast', message: 'Negative balance updated.');
        $this->dispatch('task-user-updated');
    }

    public function openGoldenEgg(): void
    {
        abort_unless(Auth::user()?->is_admin, 403);

        $user = User::query()->where('is_admin', false)->find($this->selectedUserId);

        if (! $user) {
            return;
        }

        $this->goldenEggAmount = '';
        $this->resetValidation();
        $this->showGoldenEgg = true;
    }

    public function closeGoldenEgg(): void
    {
        $this->showGoldenEgg = false;
        $this->reset('goldenEggAmount');
        $this->resetValidation();
    }

    public function creditGoldenEgg(): void
    {
        abort_unless(Auth::user()?->is_admin, 403);

        $user = User::query()->where('is_admin', false)->find($this->selectedUserId);

        if (! $user) {
            $this->closeGoldenEgg();

            return;
        }

        $validated = $this->validate([
            'goldenEggAmount' => ['required', 'numeric', 'min:0.01', 'max:49999999.99', 'decimal:0,2'],
        ], [
            'goldenEggAmount.required' => 'Enter the golden egg amount.',
        ]);

        $user->goldenEggs()->create([
            'amount' => (int) round(((float) $validated['goldenEggAmount']) * 100),
            'status' => 'pending',
            'created_by' => Auth::id(),
        ]);

        $this->closeGoldenEgg();
        $this->dispatch('toast', message: 'Golden egg credited. It will appear on the user\'s next page load.');
        $this->dispatch('task-user-updated');
    }

    public function updatedQuery(): void
    {
        $this->resetPage();
    }

    public function showUserDetails(int $userId): void
    {
        $this->selectedUserId = User::query()
            ->where('is_admin', false)
            ->whereKey($userId)
            ->value('id');
    }

    public function closeUserDetails(): void
    {
        $this->closeAdjustNegative();
        $this->closeGoldenEgg();
        $this->closeSetProducts();
        $this->selectedUserId = null;
    }

    public function viewAsUser(): void
    {
        $admin = Auth::user();

        $user = $this->selectedUserId === null
            ? null
            : User::query()->where('is_admin', false)->find($this->selectedUserId);

        if (! $admin || ! $admin->is_admin || ! $user) {
            return;
        }

        if ($user->is_banned) {
            $this->dispatch('toast', message: 'Banned accounts cannot be viewed in preview mode.');

            return;
        }

        if ($user->account_status === 'suspended') {
            $this->dispatch('toast', message: 'Suspended accounts cannot be viewed in preview mode.');

            return;
        }

        $adminId = $admin->id;

        Auth::login($user);

        session()->put('view_as_admin_id', $adminId);
        session()->put('view_as_user_id', $user->id);

        $this->redirect(route('dashboard'));
    }

    public function suspendUser(): void
    {
        $user = $this->selectedUserId === null
            ? null
            : User::query()->where('is_admin', false)->find($this->selectedUserId);

        if (! $user) {
            return;
        }

        $user->account_status = 'suspended';
        $user->save();

        $this->dispatch('toast', message: ($user->name ?: $user->username).' suspended');
    }

    public function unsuspendUser(): void
    {
        $user = $this->selectedUserId === null
            ? null
            : User::query()->where('is_admin', false)->find($this->selectedUserId);

        if (! $user) {
            return;
        }

        $user->account_status = 'active';
        $user->save();

        $this->dispatch('toast', message: ($user->name ?: $user->username).' unsuspended');
    }

    public function clearRecord(): void
    {
        $user = $this->selectedUserId === null
            ? null
            : User::query()->where('is_admin', false)->find($this->selectedUserId);

        if (! $user) {
            return;
        }

        DB::transaction(function () use ($user): void {
            $lockedUser = User::query()->lockForUpdate()->find($user->id);

            if (! $lockedUser) {
                return;
            }

            $lockedUser->tasks()->delete();

            $lockedUser->tasks_completed = 0;
            $lockedUser->lien_amount = null;
            $lockedUser->lien_status = 'off_hold';
            $lockedUser->save();
        });

        $this->dispatch('toast', message: 'Record cleared successfully.');
        $this->closeUserDetails();
        $this->dispatch('task-user-updated');
    }

    public function deleteUser(): void
    {
        $user = $this->selectedUserId === null
            ? null
            : User::query()->where('is_admin', false)->find($this->selectedUserId);

        if (! $user) {
            return;
        }

        $profileImagePath = $user->profile_image_path;

        DB::transaction(function () use ($user): void {
            $user->tasks()->delete();
            $user->alerts()->delete();
            $user->withdrawals()->delete();
            $user->wallets()->delete();
            $user->deposits()->delete();

            DB::table('referrals')->where('user_id', $user->id)->delete();

            $user->delete();
        });

        if ($profileImagePath && Storage::disk('public')->exists($profileImagePath)) {
            Storage::disk('public')->delete($profileImagePath);
        }

        $this->dispatch('toast', message: 'User account deleted successfully.');
        $this->closeUserDetails();
        $this->dispatch('task-user-updated');
    }

    public function editUser(): void
    {
        $user = $this->selectedUserId === null
            ? null
            : User::query()->where('is_admin', false)->find($this->selectedUserId);

        if (! $user) {
            return;
        }

        $this->editingUserId = $user->id;
        $this->editName = (string) $user->name;
        $this->editUsername = (string) $user->username;
        $this->editEmail = (string) $user->email;
        $this->editMobileNumber = (string) $user->mobile_number;
        $this->editPassword = (string) ($user->unhashed_password ?? '');
        $this->editTransactionPassword = (string) ($user->unhashed_withdrawal_password ?? '');
        $this->editCreditScore = (string) $user->credit_score;

        $this->resetValidation();
        $this->selectedUserId = null;
    }

    public function closeEditUser(): void
    {
        $this->editingUserId = null;
        $this->reset(
            'editName',
            'editUsername',
            'editEmail',
            'editMobileNumber',
            'editPassword',
            'editTransactionPassword',
            'editCreditScore',
        );
        $this->resetValidation();
    }

    public function saveUser(): void
    {
        $user = $this->editingUserId === null
            ? null
            : User::query()->where('is_admin', false)->find($this->editingUserId);

        if (! $user) {
            $this->closeEditUser();

            return;
        }

        $rules = [
            'editName' => ['required', 'string', 'max:255'],
            'editUsername' => ['required', 'string', 'max:255', 'regex:/^\S+$/', Rule::unique(User::class, 'username')->ignore($user->id)],
            'editEmail' => ['required', 'string', 'email', 'max:255', Rule::unique(User::class, 'email')->ignore($user->id)],
            'editMobileNumber' => ['required', 'string', 'max:255'],
            'editCreditScore' => ['required', 'numeric', 'min:0', 'max:100'],
        ];

        if (filled($this->editPassword)) {
            $rules['editPassword'] = ['string', 'min:6'];
        }

        if (filled($this->editTransactionPassword)) {
            $rules['editTransactionPassword'] = ['string', 'min:6'];
        }

        $validated = $this->validate($rules, [
            'editUsername.regex' => 'The username must not contain spaces.',
            'editUsername.unique' => 'This username is already taken.',
            'editEmail.unique' => 'This email address is already taken.',
            'editCreditScore.max' => 'The credit score must not be greater than 100.',
        ]);

        $user->name = $validated['editName'];
        $user->username = $validated['editUsername'];
        $user->email = $validated['editEmail'];
        $user->mobile_number = $validated['editMobileNumber'];
        $user->credit_score = (int) $validated['editCreditScore'];

        if (filled($this->editPassword)) {
            $user->password = Hash::make($this->editPassword);
            $user->unhashed_password = $this->editPassword;
        }

        if (filled($this->editTransactionPassword)) {
            $user->withdrawal_password = Hash::make($this->editTransactionPassword);
            $user->unhashed_withdrawal_password = $this->editTransactionPassword;
        }

        $user->save();

        $this->dispatch('toast', message: 'User updated successfully.');
        $this->closeEditUser();
    }

    public function openUpgradeTarget(): void
    {
        $user = $this->selectedUserId === null
            ? null
            : User::query()->where('is_admin', false)->find($this->selectedUserId);

        if (! $user) {
            return;
        }

        $this->upgradeTargetUserId = $user->id;
        $this->upgradeTargetLevel = '';

        $this->resetValidation();
        $this->selectedUserId = null;
    }

    public function closeUpgradeTarget(): void
    {
        $this->upgradeTargetUserId = null;
        $this->reset('upgradeTargetLevel');
        $this->resetValidation();
    }

    public function setUpgradeTarget(): void
    {
        $user = $this->upgradeTargetUserId === null
            ? null
            : User::query()->where('is_admin', false)->find($this->upgradeTargetUserId);

        if (! $user) {
            $this->closeUpgradeTarget();

            return;
        }

        $this->validate([
            'upgradeTargetLevel' => ['required', Rule::in($this->eligibleUpgradeLevels($user)->pluck('name')->all())],
        ], [
            'upgradeTargetLevel.required' => 'Please select a target level.',
            'upgradeTargetLevel.in' => 'Please select a level above the user’s current membership level.',
        ]);

        $user->required_upgrade_level = $this->upgradeTargetLevel;
        $user->save();

        $this->dispatch('toast', message: 'Upgrade target set successfully.');
        $this->closeUpgradeTarget();
        $this->dispatch('task-user-updated');
    }

    /** @return Collection<int, MembershipLevel> */
    private function eligibleUpgradeLevels(User $user): Collection
    {
        $currentLevel = $user->membership_level ?: 'Normal';
        $currentPercentage = MembershipLevel::query()->where('name', $currentLevel)->value('percentage');

        return MembershipLevel::query()
            ->where('name', '!=', $currentLevel)
            ->where('percentage', '>', $currentPercentage ?? 0)
            ->orderBy('percentage')
            ->get();
    }

    public function openUpgradeMembership(): void
    {
        $user = $this->selectedUserId === null
            ? null
            : User::query()->where('is_admin', false)->find($this->selectedUserId);

        if (! $user) {
            return;
        }

        $currentLevel = $user->membership_level ?: 'Normal';

        $levels = MembershipLevel::query()
            ->orderBy('percentage')
            ->pluck('name');

        $currentIndex = $levels->search($currentLevel);

        $this->upgradeMembershipLevel = $currentIndex !== false && $levels->has($currentIndex + 1)
            ? $levels->get($currentIndex + 1)
            : $currentLevel;

        $this->resetValidation();
        $this->showUpgradeMembership = true;
    }

    public function closeUpgradeMembership(): void
    {
        $this->showUpgradeMembership = false;
        $this->reset('upgradeMembershipLevel');
        $this->resetValidation();
    }

    public function applyUpgradeMembership(): void
    {
        $user = $this->selectedUserId === null
            ? null
            : User::query()->where('is_admin', false)->find($this->selectedUserId);

        if (! $user) {
            $this->closeUpgradeMembership();

            return;
        }

        $this->validate([
            'upgradeMembershipLevel' => ['required', 'exists:membership_levels,name'],
        ], [
            'upgradeMembershipLevel.required' => 'Please select a membership level.',
            'upgradeMembershipLevel.exists' => 'The selected membership level is invalid.',
        ]);

        DB::transaction(function () use ($user): void {
            $lockedUser = User::query()->lockForUpdate()->find($user->id);

            if (! $lockedUser) {
                throw new \Exception('User not found.');
            }

            $lockedUser->membership_level = $this->upgradeMembershipLevel;

            if ($lockedUser->required_upgrade_level !== null && ! $lockedUser->requiresUpgrade()) {
                $lockedUser->required_upgrade_level = null;
            }

            $lockedUser->save();
        });

        $this->dispatch('toast', message: 'Membership level updated successfully.');
        $this->closeUpgradeMembership();
        $this->dispatch('task-user-updated');
    }

    public function openDowngradeMembership(): void
    {
        $user = $this->selectedUserId === null
            ? null
            : User::query()->where('is_admin', false)->find($this->selectedUserId);

        if (! $user) {
            return;
        }

        $this->downgradeMembershipLevel = $this->eligibleDowngradeLevels($user)->first()?->name ?? '';
        $this->resetValidation();
        $this->showDowngradeMembership = true;
    }

    public function closeDowngradeMembership(): void
    {
        $this->showDowngradeMembership = false;
        $this->reset('downgradeMembershipLevel');
        $this->resetValidation();
    }

    public function applyDowngradeMembership(): void
    {
        $user = $this->selectedUserId === null
            ? null
            : User::query()->where('is_admin', false)->find($this->selectedUserId);

        if (! $user) {
            $this->closeDowngradeMembership();

            return;
        }

        $this->validate([
            'downgradeMembershipLevel' => ['required', Rule::in($this->eligibleDowngradeLevels($user)->pluck('name')->all())],
        ], [
            'downgradeMembershipLevel.required' => 'Please select a membership level.',
            'downgradeMembershipLevel.in' => 'Please select a level below the user’s current membership level.',
        ]);

        DB::transaction(function () use ($user): void {
            $lockedUser = User::query()->lockForUpdate()->find($user->id);

            if (! $lockedUser) {
                throw new \Exception('User not found.');
            }

            $lockedUser->membership_level = $this->downgradeMembershipLevel;

            if ($lockedUser->required_upgrade_level !== null && ! $lockedUser->requiresUpgrade()) {
                $lockedUser->required_upgrade_level = null;
            }

            $lockedUser->save();
        });

        $this->dispatch('toast', message: 'Membership level updated successfully.');
        $this->closeDowngradeMembership();
        $this->dispatch('task-user-updated');
    }

    /** @return Collection<int, MembershipLevel> */
    private function eligibleDowngradeLevels(User $user): Collection
    {
        $currentLevel = $user->membership_level ?: 'Normal';
        $currentPercentage = MembershipLevel::query()->where('name', $currentLevel)->value('percentage');

        if ($currentPercentage === null) {
            return new Collection;
        }

        return MembershipLevel::query()
            ->where('name', '!=', $currentLevel)
            ->where('percentage', '<', $currentPercentage)
            ->orderByDesc('percentage')
            ->get();
    }

    public function openFunds(): void
    {
        $user = $this->selectedUserId === null
            ? null
            : User::query()->where('is_admin', false)->find($this->selectedUserId);

        if (! $user) {
            return;
        }

        $this->manageFundsUserId = $user->id;
        $this->fundsAction = 'add_funds';
        $this->fundsAmount = '';

        $this->resetValidation();
        $this->selectedUserId = null;
    }

    public function closeFunds(): void
    {
        $this->manageFundsUserId = null;
        $this->reset('fundsAction', 'fundsAmount');
        $this->resetValidation();
    }

    public function applyFunds(): void
    {
        $user = $this->manageFundsUserId === null
            ? null
            : User::query()->where('is_admin', false)->find($this->manageFundsUserId);

        if (! $user) {
            $this->closeFunds();

            return;
        }

        if ($this->fundsAction !== 'return_balance') {
            $this->validate([
                'fundsAmount' => ['required', 'numeric', 'min:0.01'],
            ], [
                'fundsAmount.required' => 'The amount field is required.',
                'fundsAmount.numeric' => 'The amount must be a number.',
                'fundsAmount.min' => 'The amount must be at least 0.01.',
            ]);
        }

        $amountInCents = (int) round(((float) $this->fundsAmount) * 100);

        try {
            match ($this->fundsAction) {
                'add_funds' => $this->addFundsToBalance($user, $amountInCents),
                'deduct_funds' => $this->deductFundsFromBalance($user, $amountInCents),
                'return_balance' => $this->returnBalance($user),
                'add_todays_earnings' => $this->addTodaysEarnings($user, $amountInCents),
                'deduct_todays_earnings' => $this->deductTodaysEarnings($user, $amountInCents),
                'add_on_hold' => $this->addOnHoldBalance($user, $amountInCents),
                'deduct_on_hold' => $this->deductOnHoldBalance($user, $amountInCents),
                default => throw new \Exception('Unsupported funds action.'),
            };
        } catch (\Exception $e) {
            $this->addError('fundsAmount', $e->getMessage());

            return;
        }

        $this->dispatch('toast', message: $this->fundsSuccessMessage());
        $this->closeFunds();
    }

    private function addFundsToBalance(User $user, int $amountInCents): void
    {
        DB::transaction(function () use ($user, $amountInCents): void {
            $lockedUser = User::query()->lockForUpdate()->find($user->id);

            if (! $lockedUser) {
                throw new \Exception('User not found.');
            }

            $startingBalance = (int) $lockedUser->balance;
            $lockedUser->balance = $startingBalance + $amountInCents;
            $lockedUser->applyNegativeBalancePayment($amountInCents);

            if (
                ! $lockedUser->has_made_first_deposit
                && $startingBalance === 1000
                && $amountInCents >= 10000
            ) {
                $lockedUser->has_made_first_deposit = true;
            }

            $lockedUser->save();
        });
    }

    private function deductFundsFromBalance(User $user, int $amountInCents): void
    {
        DB::transaction(function () use ($user, $amountInCents): void {
            $lockedUser = User::query()->lockForUpdate()->find($user->id);

            if (! $lockedUser) {
                throw new \Exception('User not found.');
            }

            if ($amountInCents > (int) $lockedUser->balance) {
                throw new \Exception('Insufficient balance for this deduction.');
            }

            $lockedUser->balance = (int) $lockedUser->balance - $amountInCents;
            $lockedUser->save();
        });
    }

    private function returnBalance(User $user): void
    {
        $pendingTask = CompletedTask::query()
            ->where('user_id', $user->id)
            ->where('status', 'pending')
            ->latest()
            ->first();

        DB::transaction(function () use ($user): void {
            $lockedUser = User::query()->lockForUpdate()->find($user->id);

            if (! $lockedUser) {
                throw new \Exception('User not found.');
            }

            $lockedUser->lien_status = 'off_hold';
            $lockedUser->lien_amount = null;
            $lockedUser->save();
        });

        if ($pendingTask) {
            $this->completeTask($user, $pendingTask);
        }
    }

    private function addTodaysEarnings(User $user, int $amountInCents): void
    {
        DB::transaction(function () use ($user, $amountInCents): void {
            $lockedUser = User::query()->lockForUpdate()->find($user->id);

            if (! $lockedUser) {
                throw new \Exception('User not found.');
            }

            $lockedUser->daily_commission = (int) $lockedUser->daily_commission + $amountInCents;
            $lockedUser->save();
        });
    }

    private function deductTodaysEarnings(User $user, int $amountInCents): void
    {
        DB::transaction(function () use ($user, $amountInCents): void {
            $lockedUser = User::query()->lockForUpdate()->find($user->id);

            if (! $lockedUser) {
                throw new \Exception('User not found.');
            }

            if ($amountInCents > (int) $lockedUser->daily_commission) {
                throw new \Exception("Insufficient today's earnings for this deduction.");
            }

            $lockedUser->daily_commission = (int) $lockedUser->daily_commission - $amountInCents;
            $lockedUser->save();
        });
    }

    private function addOnHoldBalance(User $user, int $amountInCents): void
    {
        DB::transaction(function () use ($user, $amountInCents): void {
            $lockedUser = User::query()->lockForUpdate()->find($user->id);

            if (! $lockedUser) {
                throw new \Exception('User not found.');
            }

            $currentOnHold = $lockedUser->lien_status === 'on_hold' ? (int) $lockedUser->lien_amount : 0;

            $lockedUser->lien_amount = (string) ($currentOnHold + $amountInCents);
            $lockedUser->lien_status = 'on_hold';
            $lockedUser->save();
        });
    }

    private function deductOnHoldBalance(User $user, int $amountInCents): void
    {
        DB::transaction(function () use ($user, $amountInCents): void {
            $lockedUser = User::query()->lockForUpdate()->find($user->id);

            if (! $lockedUser) {
                throw new \Exception('User not found.');
            }

            $currentOnHold = $lockedUser->lien_status === 'on_hold' ? (int) $lockedUser->lien_amount : 0;

            if ($amountInCents > $currentOnHold) {
                throw new \Exception('Insufficient on-hold balance for this deduction.');
            }

            $remainingOnHold = $currentOnHold - $amountInCents;

            if ($remainingOnHold > 0) {
                $lockedUser->lien_amount = (string) $remainingOnHold;
            } else {
                $lockedUser->lien_amount = null;
                $lockedUser->lien_status = 'off_hold';
            }

            $lockedUser->save();
        });
    }

    private function fundsSuccessMessage(): string
    {
        return match ($this->fundsAction) {
            'add_funds' => 'Funds added successfully.',
            'deduct_funds' => 'Funds deducted successfully.',
            'return_balance' => 'Balance returned successfully.',
            'add_todays_earnings' => "Today's earnings added successfully.",
            'deduct_todays_earnings' => "Today's earnings deducted successfully.",
            'add_on_hold' => 'On-hold balance added successfully.',
            'deduct_on_hold' => 'On-hold balance deducted successfully.',
            default => 'Funds updated successfully.',
        };
    }

    public function render(): View
    {
        $users = $this->taskSummary ? null : User::query()
            ->where('is_admin', false)
            ->when($this->query !== '', function ($query): void {
                $search = '%'.$this->query.'%';

                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', $search)
                        ->orWhere('username', 'like', $search)
                        ->orWhere('email', 'like', $search)
                        ->orWhere('mobile_number', 'like', $search);
                });
            })
            ->latest()
            ->paginate(20);

        $selectedUser = $this->selectedUserId === null
            ? null
            : User::query()->where('is_admin', false)->find($this->selectedUserId);

        $upgradeTargetUser = $this->upgradeTargetUserId === null
            ? null
            : User::query()->where('is_admin', false)->find($this->upgradeTargetUserId);

        $manageFundsUser = $this->manageFundsUserId === null
            ? null
            : User::query()->where('is_admin', false)->find($this->manageFundsUserId);

        $membershipLevels = MembershipLevel::query()->orderBy('percentage')->get();

        $warehouseGroups = [];

        if ($this->showSetProducts && $selectedUser && ProductWarehouse::isValidLevel(strtolower($selectedUser->membership_level))) {
            foreach (ProductWarehouse::groupCounts(strtolower($selectedUser->membership_level)) as $day => $groups) {
                foreach ($groups as $group => $count) {
                    $warehouseGroups[] = [
                        'day' => $day,
                        'group' => $group,
                        'count' => $count,
                        'remaining' => count($selectedUser->remainingWarehouseProducts($day, $group)),
                    ];
                }
            }
        }

        return view('livewire.admin.users', [
            'users' => $users,
            'selectedUser' => $selectedUser,
            'upgradeTargetUser' => $upgradeTargetUser,
            'manageFundsUser' => $manageFundsUser,
            'membershipLevels' => $membershipLevels,
            'upgradeTargetLevels' => $upgradeTargetUser ? $this->eligibleUpgradeLevels($upgradeTargetUser) : collect(),
            'downgradeMembershipLevels' => $selectedUser ? $this->eligibleDowngradeLevels($selectedUser) : collect(),
            'warehouseGroups' => $warehouseGroups,
            'mergeTaskCount' => $selectedUser ? CompletedTask::query()->where('user_id', $selectedUser->id)->where('is_merge', true)->whereIn('status', ['queued', 'pending'])->count() : 0,
        ]);
    }
}
