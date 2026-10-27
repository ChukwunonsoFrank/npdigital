<?php

namespace App\Livewire\Admin;

use App\Models\CompletedTask;
use App\Models\User;
use App\Support\ProductWarehouse;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('components.layouts.dashboard', ['title' => 'Merge Products'])]
class MergeProducts extends Component
{
    public const COMMISSION_PERCENTAGE = 5.0;

    #[Url(as: 'user')]
    public ?int $userId = null;

    public string $search = '';

    public bool $showModal = false;

    public string $userSearch = '';

    public ?int $selectedUserId = null;

    public int $position = 1;

    public string $productSearch = '';

    public string $productLevel = '';

    /** @var list<array<string, mixed>> */
    public array $selectedProducts = [];

    public function mount(): void
    {
        abort_unless(Auth::user()?->is_admin, 403);

        if ($this->userId === null) {
            return;
        }

        $user = User::query()->where('is_admin', false)->find($this->userId);

        if ($user) {
            $this->openModal($user->id);
        }
    }

    public function openModal(?int $userId = null): void
    {
        abort_unless(Auth::user()?->is_admin, 403);

        $this->reset('search', 'userSearch', 'selectedUserId', 'position', 'productSearch', 'productLevel', 'selectedProducts');
        $this->resetValidation();

        if ($userId !== null) {
            $user = User::query()->where('is_admin', false)->find($userId);

            if ($user) {
                $this->selectedUserId = $user->id;
                $this->productLevel = '';
                $this->position = 1;
            }
        }

        $this->showModal = true;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetValidation();
        $this->reset('selectedProducts', 'productSearch', 'userSearch');
    }

    public function updatedSelectedUserId(): void
    {
        $this->reset('selectedProducts');
        $this->resetValidation();

        $this->productLevel = '';
        $this->position = 1;
    }

    public function selectUser(int $userId): void
    {
        $this->selectedUserId = $userId;
        $this->updatedSelectedUserId();
    }

    public function updatedSearch(): void
    {
        $this->resetValidation();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function productResults(): array
    {
        return ProductWarehouse::searchProducts(
            $this->productLevel !== '' ? $this->productLevel : null,
            $this->productSearch,
            24,
        );
    }

    public function addProduct(string $productId): void
    {
        abort_unless(Auth::user()?->is_admin, 403);

        if (collect($this->selectedProducts)->contains('id', $productId)) {
            return;
        }

        $product = collect($this->productResults())->firstWhere('id', $productId);

        if ($product === null) {
            $this->addError('productSearch', 'That product is no longer available.');

            return;
        }

        $this->selectedProducts[] = [
            'id' => $product['id'],
            'name' => $product['name'],
            'image' => $product['image'],
            'image_url' => $product['image_url'],
            'brand' => $product['brand'],
            'level' => $product['level'],
            'day' => $product['day'],
            'group' => $product['group'],
            'price' => number_format((float) $product['price'], 2, '.', ''),
        ];

        $this->resetErrorBag('selectedProducts');
    }

    public function removeProduct(int $index): void
    {
        if (! isset($this->selectedProducts[$index])) {
            return;
        }

        unset($this->selectedProducts[$index]);
        $this->selectedProducts = array_values($this->selectedProducts);
    }

    public function save(): void
    {
        abort_unless(Auth::user()?->is_admin, 403);

        $this->validate([
            'selectedUserId' => ['required', 'integer', Rule::exists('users', 'id')->where('is_admin', 0)],
            'position' => ['required', 'integer', 'min:1', 'max:'.ProductWarehouse::PRODUCTS_PER_SET],
            'selectedProducts' => ['required', 'array', 'min:1'],
            'selectedProducts.*.price' => ['required', 'numeric', 'min:0.01', 'max:49999999.99', 'decimal:0,2'],
        ], [
            'selectedUserId.required' => 'Select the user who should receive this merge product.',
            'selectedProducts.required' => 'Add at least one product to the merge.',
            'selectedProducts.min' => 'Add at least one product to the merge.',
        ]);

        $user = $this->selectedUser();

        if (! $user) {
            $this->addError('selectedUserId', 'Select a valid user.');

            return;
        }

        $remainingInSet = max(0, $user->productsPerSet() - $user->tasks_completed);

        if ($this->position > $remainingInSet) {
            $this->addError('position', 'Position must fit inside the user\'s remaining '.$remainingInSet.' products for this set.');

            return;
        }

        $totalCents = (int) round(collect($this->selectedProducts)->sum(fn (array $product): float => (float) $product['price']) * 100);

        if ($totalCents <= (int) $user->balance) {
            throw ValidationException::withMessages([
                'selectedProducts' => 'The combined cost must be greater than '.$user->username.'\'s current balance of $'.number_format($user->balance / 100, 2).'.',
            ]);
        }

        $items = array_map(fn (array $product): array => [
            'id' => $product['id'],
            'name' => $product['name'],
            'image' => $product['image'],
            'brand' => $product['brand'],
            'level' => $product['level'],
            'day' => $product['day'],
            'group' => $product['group'],
            'price' => (float) $product['price'],
        ], $this->selectedProducts);

        $names = array_column($items, 'name');
        $title = count($names) > 2
            ? $names[0].' + '.$names[1].' + '.(count($names) - 2).' more'
            : implode(' + ', $names);

        CompletedTask::create([
            'user_id' => $user->id,
            'title' => $title,
            'cost' => number_format($totalCents / 100, 2, '.', ''),
            'task_image_path' => (string) ($items[0]['image'] ?? ''),
            'brand' => $items[0]['brand'] ?? null,
            'commission_percentage' => self::COMMISSION_PERCENTAGE,
            'available_after_tasks' => $user->tasks_completed + $this->position - 1,
            'rating' => '0',
            'rating_id' => substr(bin2hex(random_bytes(7)), 0, 14),
            'status' => 'queued',
            'is_merge' => true,
            'merge_items' => $items,
            'merge_position' => $this->position,
        ]);

        $this->showModal = false;
        $this->reset('selectedProducts', 'selectedUserId', 'productSearch', 'userSearch');
        $this->dispatch('toast', message: 'Merge product assigned to '.($user->name ?: $user->username).'.');
    }

    public function removeAssignment(int $taskId): void
    {
        abort_unless(Auth::user()?->is_admin, 403);

        $task = CompletedTask::query()
            ->where('is_merge', true)
            ->whereIn('status', ['queued', 'pending'])
            ->find($taskId);

        if (! $task) {
            return;
        }

        DB::transaction(function () use ($task): void {
            if ($task->status === 'pending') {
                User::query()
                    ->whereKey($task->user_id)
                    ->update(['negative_balance' => 0]);
            }

            $task->delete();
        });

        $this->dispatch('toast', message: 'Merge product removed.');
    }

    public function render(): View
    {
        $assignments = CompletedTask::query()
            ->with('user')
            ->where('is_merge', true)
            ->whereIn('status', ['queued', 'pending'])
            ->when($this->search !== '', function ($query): void {
                $query->whereHas('user', function ($query): void {
                    $query->where('name', 'like', '%'.$this->search.'%')
                        ->orWhere('username', 'like', '%'.$this->search.'%');
                });
            })
            ->latest()
            ->get();

        return view('livewire.admin.merge-products', [
            'assignments' => $assignments,
            'selectedUser' => $this->selectedUser(),
            'productResults' => $this->showModal ? $this->productResults() : [],
            'users' => $this->showModal
                ? User::query()
                    ->where('is_admin', false)
                    ->when($this->userSearch !== '', function ($query): void {
                        $query->where(function ($query): void {
                            $query->where('name', 'like', '%'.$this->userSearch.'%')
                                ->orWhere('username', 'like', '%'.$this->userSearch.'%');
                        });
                    })
                    ->orderBy('name')
                    ->limit(50)
                    ->get()
                : collect(),
            'levels' => ProductWarehouse::levels(),
            'commissionPercentage' => self::COMMISSION_PERCENTAGE,
        ]);
    }

    private function selectedUser(): ?User
    {
        return $this->selectedUserId === null
            ? null
            : User::query()->where('is_admin', false)->find($this->selectedUserId);
    }
}
