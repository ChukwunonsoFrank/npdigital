<?php

namespace App\Livewire\Dashboard;

use App\Models\MembershipLevel;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('components.layouts.dashboard')]
class Account extends Component
{
    use WithFileUploads;

    public $avatar;

    public ?string $editingField = null;

    public string $editValue = '';

    public function updatedAvatar(): void
    {
        $this->validate([
            'avatar' => ['required', 'image', 'max:2048'],
        ]);

        $user = auth()->user();
        $path = $this->avatar->store('avatars', 'public');

        if (($user->profile_image_path ?? null) && Storage::disk('public')->exists($user->profile_image_path)) {
            Storage::disk('public')->delete($user->profile_image_path);
        }

        $user->update(['profile_image_path' => $path]);

        $this->reset('avatar');
        $this->dispatch('toast', message: 'Profile photo updated.');
    }

    public function startEdit(string $field): void
    {
        $user = auth()->user();

        $this->editingField = $field;
        $this->editValue = match ($field) {
            'login_password' => (string) ($user->unhashed_password ?? ''),
            'transaction_password' => (string) ($user->unhashed_withdrawal_password ?? ''),
            default => (string) ($user->{$field} ?? ''),
        };
        $this->resetValidation('editValue');
    }

    public function cancelEdit(): void
    {
        $this->reset('editingField', 'editValue');
        $this->resetValidation('editValue');
    }

    public function saveField(): void
    {
        $user = auth()->user();

        $rules = match ($this->editingField) {
            'name' => [
                'editValue' => ['required', 'string', 'max:255'],
            ],
            'username' => [
                'editValue' => ['required', 'string', 'max:255', 'regex:/^\S+$/', Rule::unique(User::class, 'username')->ignore($user->id)],
            ],
            'email' => [
                'editValue' => ['required', 'string', 'email', 'max:255', Rule::unique(User::class, 'email')->ignore($user->id)],
            ],
            default => [],
        };

        if ($rules === []) {
            $this->cancelEdit();

            return;
        }

        $this->validate($rules, [
            'editValue.regex' => 'The username must not contain spaces.',
            'editValue.unique' => 'This value is already taken.',
        ]);

        $user->update([$this->editingField => $this->editValue]);

        $this->reset('editingField', 'editValue');
        $this->dispatch('toast', message: 'Account details updated.');
    }

    public function saveLoginPassword(): void
    {
        $this->validate([
            'editValue' => ['required', 'string', 'min:6'],
        ]);

        auth()->user()->update([
            'password' => Hash::make($this->editValue),
            'unhashed_password' => $this->editValue,
        ]);

        $this->reset('editingField', 'editValue');
        $this->dispatch('toast', message: 'Login password updated.');
    }

    public function saveTransactionPassword(): void
    {
        $this->validate([
            'editValue' => ['required', 'string', 'min:6'],
        ]);

        auth()->user()->update([
            'withdrawal_password' => Hash::make($this->editValue),
            'unhashed_withdrawal_password' => $this->editValue,
        ]);

        $this->reset('editingField', 'editValue');
        $this->dispatch('toast', message: 'Transaction password updated.');
    }

    public function render()
    {
        $user = auth()->user();

        $isOnHold = ($user->lien_status ?? 'off_hold') === 'on_hold' && $user->lien_amount !== null;

        $balance = $isOnHold
            ? -((int) $user->lien_amount) / 100
            : ((int) ($user->balance ?? 0)) / 100;

        return view('livewire.dashboard.account', [
            'membershipLevels' => MembershipLevel::all(),
            'userLevel' => $user->membership_level,
            'user' => $user,
            'balance' => $balance,
            'todaysCommission' => ((int) ($user->daily_commission ?? 0)) / 100,
            'creditScore' => $user->credit_score,
        ]);
    }
}
