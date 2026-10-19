<?php

use App\Models\UpgradeRequest;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('components.layouts.dashboard')] #[Title('Upgrades')] class extends Component {
    use WithPagination;

    public string $search = '';

    public string $status = 'pending';

    public function boot(): void
    {
        abort_unless(auth()->user()?->is_admin, 403);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function selectStatus(string $status): void
    {
        if (in_array($status, ['all', 'pending', 'approved', 'rejected'], true)) {
            $this->status = $status;
            $this->resetPage();
            $this->resetValidation();
        }
    }

    public function review(int $id, bool $approve): void
    {
        $this->resetValidation();
        UpgradeRequest::query()->findOrFail($id)->review(auth()->user(), $approve);
        $this->dispatch('notify', message: $approve ? 'Upgrade approved.' : 'Upgrade rejected.', type: 'success');
    }

    public function with(): array
    {
        return [
            'total' => UpgradeRequest::query()->count(),
            'requests' => UpgradeRequest::query()->with(['user', 'membershipLevel'])
                ->when($this->status !== 'all', fn ($query) => $query->where('status', $this->status))
                ->when($this->search !== '', fn ($query) => $query->where(function ($query): void {
                    $query->where('id', 'like', '%'.$this->search.'%')
                        ->orWhereHas('user', fn ($users) => $users
                            ->where('name', 'like', '%'.$this->search.'%')
                            ->orWhere('username', 'like', '%'.$this->search.'%')
                            ->orWhere('id', 'like', '%'.$this->search.'%'));
                }))
                ->latest()->paginate(20),
        ];
    }
}; ?>

<div class="space-y-5 animate-fade-in">
    <div class="flex items-center gap-3">
        <a href="{{ route('admin.dashboard') }}" wire:navigate aria-label="Back to overview"
            class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-md hover:bg-muted">
            <x-dashboard.icon name="arrow-left" class="h-5 w-5" />
        </a>
        <div class="flex items-center gap-2">
            <x-dashboard.icon name="circle-arrow-up" class="h-5 w-5 shrink-0 text-primary" />
            <h1 class="font-display text-xl font-bold">Upgrade Requests</h1>
        </div>
        <span class="ml-auto shrink-0 rounded-full bg-secondary px-2.5 py-0.5 text-xs font-semibold text-secondary-foreground">{{ $total }} Total</span>
    </div>

    <div class="flex flex-wrap gap-3">
        <div class="relative min-w-[200px] flex-1">
            <x-dashboard.icon name="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
            <input wire:model.live.debounce.300ms="search" type="search" aria-label="Search by name or ID" placeholder="Search by name or ID..."
                class="h-10 w-full rounded-md border border-border bg-background py-2 pl-9 pr-3 text-sm placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring" />
        </div>
        <div class="flex flex-wrap gap-1.5" aria-label="Upgrade request status">
            @foreach (['all', 'pending', 'approved', 'rejected'] as $filter)
                <button type="button" wire:click="selectStatus('{{ $filter }}')" aria-pressed="{{ $status === $filter ? 'true' : 'false' }}"
                    @class(['inline-flex h-9 items-center justify-center rounded-md border px-3 text-xs font-medium capitalize transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring',
                        'border-transparent bg-primary text-primary-foreground hover:bg-primary/90' => $status === $filter,
                        'border-border bg-background hover:bg-muted' => $status !== $filter])>{{ $filter }}</button>
            @endforeach
        </div>
    </div>

    @error('upgrade') <p role="alert" class="rounded-md border border-destructive/30 bg-destructive/10 p-3 text-sm text-destructive">{{ $message }}</p> @enderror

    @if ($requests->isEmpty())
        <div class="rounded-xl border border-border bg-card p-12 text-center">
            <x-dashboard.icon name="circle-arrow-up" class="mx-auto mb-3 h-10 w-10 text-muted-foreground/30" />
            <p class="text-sm text-muted-foreground">{{ $status === 'pending' ? 'No pending upgrade requests' : 'No upgrade requests found' }}</p>
        </div>
    @else
        <div class="space-y-3">
            @foreach ($requests as $request)
                <div wire:key="upgrade-{{ $request->id }}" class="flex flex-wrap items-center gap-4 rounded-xl border border-border bg-card p-5">
                    <div class="w-full min-w-0 sm:w-auto sm:flex-1">
                        <p class="font-semibold">{{ $request->user->name ?: $request->user->username }} <span class="text-xs font-normal text-muted-foreground">User #{{ $request->user_id }} · Request #{{ $request->id }}</span></p>
                        <p class="mt-1 text-sm">{{ $request->from_level }} → {{ $request->membershipLevel->name }}</p>
                        <p class="mt-1 text-xs text-muted-foreground">Balance: ${{ number_format($request->user->balance / 100, 2) }} · Required: ${{ number_format($request->membershipLevel->minimum_balance, 2) }}</p>
                        <p class="mt-1 text-xs text-muted-foreground">Requested {{ $request->created_at->format('M j, Y H:i') }}</p>
                    </div>
                    <span class="rounded-full bg-secondary px-2.5 py-1 text-xs font-semibold capitalize text-secondary-foreground">{{ $request->status }}</span>
                    @if ($request->status === 'pending')
                        <div class="flex gap-2">
                            <flux:button size="sm" variant="primary" wire:click="review({{ $request->id }}, true)" wire:confirm="Approve this membership upgrade?" wire:loading.attr="disabled">Approve</flux:button>
                            <flux:button size="sm" variant="danger" wire:click="review({{ $request->id }}, false)" wire:confirm="Reject this membership upgrade?" wire:loading.attr="disabled">Decline</flux:button>
                        </div>
                    @else
                        <p class="text-xs text-muted-foreground">Reviewed {{ $request->reviewed_at?->format('M j, Y H:i') }}</p>
                    @endif
                </div>
            @endforeach
            {{ $requests->links() }}
        </div>
    @endif
</div>
