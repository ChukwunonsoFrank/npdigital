<?php

use App\Models\User;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('components.layouts.dashboard')] #[Title('Manage Admins')] class extends Component {
    use WithPagination;

    #[Locked]
    public ?int $editingId = null;

    public bool $showPromote = false;

    public string $name = '';

    public string $username = '';

    public string $email = '';

    public string $password = '';

    public string $search = '';

    public function boot(): void
    {
        abort_unless(auth()->user()?->is_admin, 403);
    }

    public function editAdmin(int $id): void
    {
        $admin = User::query()->where('is_admin', true)->findOrFail($id);
        $this->closeModal();
        $this->editingId = $admin->id;
        $this->name = $admin->name ?? '';
        $this->username = $admin->username;
        $this->email = $admin->email ?? '';
    }

    public function saveAdmin(): void
    {
        $admin = User::query()->where('is_admin', true)->findOrFail($this->editingId);
        $data = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', 'regex:/^[a-zA-Z0-9_-]+$/', Rule::unique('users', 'username')->ignore($admin)],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($admin)],
            'password' => ['nullable', 'string', 'min:8', 'max:255'],
        ]);

        if ($this->password === '') {
            unset($data['password']);
        } else {
            $data['unhashed_password'] = $this->password;
            $admin->setRememberToken(\Illuminate\Support\Str::random(60));
        }

        $admin->fill($data)->save();
        $this->closeModal();
        $this->dispatch('notify', message: 'Admin details updated.', type: 'success');
    }

    public function openPromote(): void
    {
        $this->closeModal();
        $this->showPromote = true;
    }

    public function promote(int $id): void
    {
        $user = User::query()->where('is_admin', false)->findOrFail($id);
        $user->update(['is_admin' => true]);
        $this->resetPage();
        $this->dispatch('notify', message: 'User promoted to admin.', type: 'success');
    }

    public function unpromote(int $id): void
    {
        $admin = User::query()->where('is_admin', true)->findOrFail($id);
        abort_if($admin->id === User::query()->where('is_admin', true)->orderBy('id')->value('id'), 403);

        $admin->update(['is_admin' => false]);
        $this->closeModal();

        if ($admin->id === auth()->id()) {
            $this->redirectRoute('dashboard');

            return;
        }

        $this->dispatch('notify', message: 'Admin returned to a regular user.', type: 'success');
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function closeModal(): void
    {
        $this->reset('editingId', 'showPromote', 'name', 'username', 'email', 'password', 'search');
        $this->resetPage();
        $this->resetValidation();
    }

    public function with(): array
    {
        return [
            'admins' => User::query()->where('is_admin', true)->orderBy('id')->get(),
            'candidates' => $this->showPromote ? User::query()->where('is_admin', false)
                ->when($this->search !== '', fn ($query) => $query->where(function ($query): void {
                    $query->where('name', 'like', '%'.$this->search.'%')
                        ->orWhere('username', 'like', '%'.$this->search.'%')
                        ->orWhere('email', 'like', '%'.$this->search.'%');
                }))->orderBy('id')->paginate(20) : null,
        ];
    }
}; ?>

<div class="space-y-5 animate-fade-in">
    <div class="flex items-center gap-3">
        <a href="{{ route('admin.dashboard') }}" wire:navigate aria-label="Back to overview" class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-md hover:bg-muted">
            <x-dashboard.icon name="arrow-left" class="h-5 w-5" />
        </a>
        <h1 class="font-display text-xl font-bold">Manage Admins</h1>
    </div>
    <p class="text-sm text-muted-foreground">Manage admin accounts and credentials. All admins share the same capabilities.</p>

    @foreach ($admins as $admin)
        @php($isSuperAdmin = $loop->first)
        <article wire:key="admin-{{ $admin->id }}" class="overflow-hidden rounded-xl border border-border bg-card shadow-sm">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-border bg-primary/5 px-5 py-3">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="flex items-center gap-1 rounded-full bg-primary/10 px-2 py-1 text-xs font-semibold text-primary">
                        <x-dashboard.icon :name="$isSuperAdmin ? 'crown' : 'shield-check'" class="h-3 w-3" />
                        {{ $isSuperAdmin ? 'Super Admin' : 'Admin' }}
                    </span>
                    @if ($isSuperAdmin)<x-dashboard.icon name="lock" class="h-3.5 w-3.5 text-muted-foreground" />@endif
                    <span class="rounded-full border border-border px-2.5 py-0.5 text-xs font-semibold uppercase">{{ $admin->membership_level }}</span>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" wire:click="editAdmin({{ $admin->id }})" aria-label="Edit {{ $admin->name ?: $admin->username }}" class="inline-flex h-9 items-center justify-center gap-1 rounded-md border border-input bg-background px-3 text-xs font-medium hover:bg-muted focus-visible:ring-2 focus-visible:ring-ring">
                        <x-dashboard.icon name="pencil" class="h-3 w-3" /> Edit
                    </button>
                    @if (! $isSuperAdmin)
                        <button type="button" wire:click="unpromote({{ $admin->id }})" wire:confirm="Remove admin access for {{ $admin->name ?: $admin->username }}? Their user account will be kept." wire:loading.attr="disabled" aria-label="Unpromote {{ $admin->name ?: $admin->username }}" class="inline-flex h-9 items-center justify-center rounded-md border border-destructive/30 bg-background px-3 text-xs font-medium text-destructive hover:bg-destructive/10 focus-visible:ring-2 focus-visible:ring-ring disabled:opacity-50">Unpromote</button>
                    @endif
                </div>
            </div>
            <div class="space-y-4 p-5">
                <div class="flex items-center gap-4">
                    <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-full bg-primary text-lg font-bold text-primary-foreground">{{ mb_substr($admin->initials() ?: $admin->username, 0, 2) }}</div>
                    <div class="min-w-0">
                        <p class="break-words text-lg font-bold">{{ $admin->name ?: $admin->username }}</p>
                        <p class="text-sm text-muted-foreground">{{ $isSuperAdmin ? 'Super Administrator — Cannot be deleted' : 'Administrator' }}</p>
                    </div>
                </div>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    @foreach ([['Username', 'user', $admin->username, true], ['Email', 'mail', $admin->email ?: 'Not provided', filled($admin->email)], ['Location', 'map-pin', 'Not provided', false], ['Invitation Code', 'hash', $admin->referral_code ?: 'Not provided', filled($admin->referral_code)], ['Member Since', null, $admin->created_at->format('n/j/Y'), false], ['Balance', null, '$'.number_format($admin->balance / 100, 2), false]] as [$label, $icon, $value, $copyable])
                        <div class="min-w-0 space-y-1">
                            <p class="flex items-center gap-1 text-xs text-muted-foreground">@if ($icon)<x-dashboard.icon :name="$icon" class="h-3 w-3" />@endif {{ $label }}</p>
                            <div class="flex items-center gap-2">
                                <p @class(['min-w-0 break-words text-sm font-medium', 'font-mono' => $label === 'Invitation Code'])>{{ $value }}</p>
                                @if ($copyable)
                                    <button type="button" x-data="{ copied: false }" @click="navigator.clipboard.writeText(@js($value)).then(() => { copied = true; setTimeout(() => copied = false, 2000) }).catch(() => $dispatch('notify', { message: 'Could not copy. Please copy the text manually.', type: 'error' }))" aria-label="Copy {{ strtolower($label) }} for {{ $admin->username }}" class="shrink-0 text-muted-foreground hover:text-foreground focus-visible:ring-2 focus-visible:ring-ring">
                                        <span x-show="!copied"><x-dashboard.icon name="copy" class="h-3 w-3" /></span>
                                        <span x-cloak x-show="copied" role="status"><x-dashboard.icon name="check" class="h-3 w-3" /><span class="sr-only">Copied</span></span>
                                    </button>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </article>
    @endforeach

    <button type="button" wire:click="openPromote" class="inline-flex h-10 items-center justify-center gap-2 rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90 focus-visible:ring-2 focus-visible:ring-ring">
        <x-dashboard.icon name="users" class="h-4 w-4" /> Promote User to Admin
    </button>

    @if ($editingId || $showPromote)
        @teleport('body')
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 p-4" x-data x-trap.inert.noscroll="true" @keydown.escape.window="$wire.closeModal()" wire:key="admins-modal-{{ $editingId ?? 'promote' }}">
            <div class="absolute inset-0" wire:click="closeModal"></div>
            <section role="dialog" aria-modal="true" aria-labelledby="admins-modal-title" class="relative max-h-[80dvh] w-full max-w-md overflow-y-auto rounded-lg border border-border bg-background p-6 shadow-lg">
                <button type="button" wire:click="closeModal" aria-label="Close dialog" class="absolute right-4 top-4 rounded-sm text-muted-foreground hover:text-foreground focus-visible:ring-2 focus-visible:ring-ring"><x-dashboard.icon name="x" class="h-4 w-4" /></button>
                <h2 id="admins-modal-title" class="pr-6 text-lg font-semibold tracking-tight">{{ $editingId ? ($editingId === $admins->first()?->id ? 'Edit Super Admin' : 'Edit Admin') : 'Promote User to Admin' }}</h2>
                @if ($editingId)
                    <form wire:submit="saveAdmin" class="mt-5 space-y-4">
                        <flux:input wire:model="name" label="Full Name" required autofocus />
                        <flux:input wire:model="username" label="Username" required />
                        <flux:input wire:model="email" type="email" label="Email" required />
                        <flux:input wire:model="password" type="password" label="New Password (leave blank to keep current)" placeholder="Enter new password" autocomplete="new-password" />
                        <div class="flex flex-wrap justify-end gap-2">
                            <flux:button type="button" wire:click="closeModal" size="sm">Cancel</flux:button>
                            <button type="submit" wire:loading.attr="disabled" class="inline-flex h-9 items-center justify-center gap-2 rounded-md bg-primary px-3 text-sm font-medium text-primary-foreground hover:bg-primary/90 disabled:opacity-50"><x-dashboard.icon name="save" class="h-4 w-4" /><span wire:loading.remove wire:target="saveAdmin">Save Changes</span><span wire:loading wire:target="saveAdmin">Saving...</span></button>
                        </div>
                    </form>
                @else
                    <div class="mt-4 space-y-4">
                        <flux:input wire:model.live.debounce.300ms="search" type="search" aria-label="Search users" placeholder="Search users..." autofocus />
                        <div class="max-h-[50dvh] space-y-2 overflow-y-auto" aria-label="Users available for promotion">
                            @forelse ($candidates as $candidate)
                                <div wire:key="candidate-{{ $candidate->id }}" class="flex items-center justify-between gap-3 rounded-lg border border-border bg-card p-3">
                                    <div class="min-w-0">
                                        <p class="break-words text-sm font-medium">{{ $candidate->name ?: $candidate->username }}</p>
                                        <p class="break-words text-xs text-muted-foreground">{{ $candidate->email ?: $candidate->username }}</p>
                                    </div>
                                    <button type="button" wire:click="promote({{ $candidate->id }})" wire:loading.attr="disabled" aria-label="Promote {{ $candidate->name ?: $candidate->username }}" class="inline-flex h-9 shrink-0 items-center justify-center gap-1 rounded-md border border-input bg-background px-3 text-xs font-medium hover:bg-muted disabled:opacity-50"><x-dashboard.icon name="shield-check" class="h-3 w-3" /> Promote</button>
                                </div>
                            @empty
                                <p class="py-6 text-center text-sm text-muted-foreground">No users found.</p>
                            @endforelse
                            {{ $candidates->links() }}
                        </div>
                    </div>
                @endif
            </section>
        </div>
        @endteleport
    @endif
</div>
