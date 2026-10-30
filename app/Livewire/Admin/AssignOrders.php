<?php

namespace App\Livewire\Admin;

use App\Models\CompletedTask;
use App\Models\MembershipLevel;
use App\Models\User;
use App\Support\ProductWarehouse;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('components.layouts.dashboard', ['title' => 'Assign Orders'])]
class AssignOrders extends Component
{
    public const MERGE_PERCENTAGE = 5.0;

    public const MAX_ORDERS = 100;

    #[Url(as: 'user')]
    public ?int $userId = null;

    public string $userSearch = '';

    public int $totalOrders = ProductWarehouse::PRODUCTS_PER_SET;

    /** @var list<array{key: string, position: int, items: list<array<string, mixed>>}> */
    public array $mergeOrders = [];

    public ?int $productPickerIndex = null;

    public string $productSearch = '';

    public bool $clearExisting = true;

    public function mount(): void
    {
        abort_unless(Auth::user()?->is_admin, 403);

        $this->loadAssignment();
    }

    public function selectUser(int $userId): void
    {
        abort_unless(Auth::user()?->is_admin, 403);

        $user = User::query()->where('is_admin', false)->find($userId);

        if (! $user) {
            return;
        }

        $this->userId = $user->id;
        $this->userSearch = '';
        $this->loadAssignment();
    }

    public function changeUser(): void
    {
        $this->userId = null;
        $this->resetAssignment();
    }

    public function addMergeOrder(): void
    {
        abort_unless(Auth::user()?->is_admin, 403);

        $user = $this->selectedUser();

        if (! $user) {
            return;
        }

        if (count($this->mergeOrders) >= $this->totalOrders) {
            $this->addError('mergeOrders', 'Every order in this set is already a merge order. Increase the total orders to add another.');

            return;
        }

        $this->mergeOrders[] = [
            'key' => substr(bin2hex(random_bytes(7)), 0, 14),
            'position' => $this->nextMergePosition(),
            'items' => [],
        ];

        $this->productPickerIndex = array_key_last($this->mergeOrders);
        $this->productSearch = '';
        $this->resetValidation();
    }

    public function toggleProductPicker(int $index): void
    {
        abort_unless(Auth::user()?->is_admin, 403);

        if (! isset($this->mergeOrders[$index])) {
            return;
        }

        $this->productPickerIndex = $this->productPickerIndex === $index ? null : $index;
        $this->productSearch = '';
        $this->resetValidation();
    }

    public function addMergeProduct(int $index, string $productId): void
    {
        abort_unless(Auth::user()?->is_admin, 403);

        $user = $this->selectedUser();

        if (! $user || ! isset($this->mergeOrders[$index])) {
            return;
        }

        if ($this->isMergeProductUsed($productId)) {
            $this->addError('productSearch', 'That product is already in a merge bundle.');

            return;
        }

        if (in_array($productId, $user->submitted_warehouse_products ?? [], true)) {
            $this->addError('productSearch', 'That product has already been submitted by this user.');

            return;
        }

        $product = ProductWarehouse::findProduct(null, $productId);

        if ($product === null) {
            $this->addError('productSearch', 'That product is no longer available in the warehouse.');

            return;
        }

        $this->mergeOrders[$index]['items'][] = $this->mergeItem($product);
        $this->productPickerIndex = $index;
        $this->resetValidation();
        $this->resetErrorBag('productSearch');
    }

    public function removeMergeProduct(int $index, int $itemIndex): void
    {
        if (! isset($this->mergeOrders[$index]['items'][$itemIndex])) {
            return;
        }

        unset($this->mergeOrders[$index]['items'][$itemIndex]);
        $this->mergeOrders[$index]['items'] = array_values($this->mergeOrders[$index]['items']);
        $this->resetValidation();
    }

    public function removeMergeOrder(int $index): void
    {
        if (! isset($this->mergeOrders[$index])) {
            return;
        }

        unset($this->mergeOrders[$index]);
        $this->mergeOrders = array_values($this->mergeOrders);

        if ($this->productPickerIndex === $index) {
            $this->productPickerIndex = null;
            $this->productSearch = '';
        } elseif ($this->productPickerIndex !== null && $this->productPickerIndex > $index) {
            $this->productPickerIndex--;
        }

        $this->resetValidation();
    }

    public function autoAssign(): void
    {
        abort_unless(Auth::user()?->is_admin, 403);

        $user = $this->selectedUser();

        if (! $user) {
            $this->addError('userId', 'Select a user before assigning orders.');

            return;
        }

        $this->validate([
            'totalOrders' => ['required', 'integer', 'min:1', 'max:'.self::MAX_ORDERS],
            'mergeOrders' => ['array'],
            'mergeOrders.*.position' => ['required', 'integer', 'min:1', 'max:'.$this->totalOrders, 'distinct'],
            'mergeOrders.*.items' => ['required', 'array', 'min:1'],
            'mergeOrders.*.items.*.id' => ['required', 'string'],
        ], [
            'mergeOrders.*.position.distinct' => 'Each merge order needs a unique order number.',
            'mergeOrders.*.position.max' => 'Merge order numbers must fit inside the total orders.',
            'mergeOrders.*.items.required' => 'Add at least one product to every merge bundle.',
            'mergeOrders.*.items.min' => 'Add at least one product to every merge bundle.',
        ]);

        $existingOrders = CompletedTask::query()
            ->where('user_id', $user->id)
            ->whereIn('status', ['queued', 'pending'])
            ->exists();

        if ($existingOrders && ! $this->clearExisting) {
            $this->addError('clearExisting', 'This user already has queued or pending orders. Check the box to clear them first.');

            return;
        }

        $mergePositions = array_map(fn (array $row): int => (int) $row['position'], $this->mergeOrders);
        $normalCount = $this->totalOrders - count($mergePositions);
        $reservedIds = collect($this->mergeOrders)
            ->flatMap(fn (array $row): array => array_column($row['items'], 'id'))
            ->all();
        $submittedIds = $user->submitted_warehouse_products ?? [];
        $pool = array_values(array_filter(
            ProductWarehouse::levelProducts(strtolower($user->membership_level)),
            fn (array $product): bool => ! in_array($product['id'], $reservedIds, true)
                && ! in_array($product['id'], $submittedIds, true),
        ));

        if (count($pool) < $normalCount) {
            throw ValidationException::withMessages([
                'totalOrders' => 'The '.ProductWarehouse::label(strtolower($user->membership_level)).' warehouse only has '.count($pool).' products left. Reduce the total orders.',
            ]);
        }

        shuffle($pool);

        DB::transaction(function () use ($user, $pool): void {
            $lockedUser = User::query()->lockForUpdate()->find($user->id);

            if (! $lockedUser) {
                throw ValidationException::withMessages(['userId' => 'The selected user no longer exists.']);
            }

            if ($this->clearExisting) {
                $hadPendingMerge = CompletedTask::query()
                    ->where('user_id', $lockedUser->id)
                    ->where('status', 'pending')
                    ->where('is_merge', true)
                    ->exists();

                CompletedTask::query()
                    ->where('user_id', $lockedUser->id)
                    ->whereIn('status', ['queued', 'pending'])
                    ->delete();

                if ($hadPendingMerge) {
                    $lockedUser->update(['negative_balance' => 0]);
                }
            }

            $mergeByPosition = [];
            foreach ($this->mergeOrders as $mergeOrder) {
                $mergeByPosition[(int) $mergeOrder['position']] = $mergeOrder;
            }

            $startingProgress = (int) $lockedUser->tasks_completed;
            $normalIndex = 0;

            for ($position = 1; $position <= $this->totalOrders; $position++) {
                $availableAfter = $startingProgress + $position - 1;

                if (isset($mergeByPosition[$position])) {
                    $this->createMergeTask($lockedUser, $mergeByPosition[$position], $position, $availableAfter);

                    continue;
                }

                $this->createNormalTask($lockedUser, $pool[$normalIndex], $availableAfter);
                $normalIndex++;
            }

            $lockedUser->update([
                'products_per_set' => $startingProgress + $this->totalOrders,
            ]);
        });

        $this->resetValidation();
        $this->dispatch('toast', message: $this->totalOrders.' orders assigned to '.($user->name ?: $user->username).'.');
    }

    public function removeOrder(int $taskId): void
    {
        abort_unless(Auth::user()?->is_admin, 403);

        $user = $this->selectedUser();

        if (! $user) {
            return;
        }

        $task = CompletedTask::query()
            ->where('user_id', $user->id)
            ->whereIn('status', ['queued', 'pending'])
            ->find($taskId);

        if (! $task) {
            return;
        }

        DB::transaction(function () use ($task, $user): void {
            if ($task->status === 'pending' && $task->is_merge) {
                User::query()->whereKey($user->id)->update(['negative_balance' => 0]);
            }

            $task->delete();
        });

        $this->dispatch('toast', message: 'Order removed.');
    }

    public function render(): View
    {
        $selectedUser = $this->selectedUser();

        $users = $selectedUser ? collect() : User::query()
            ->where('is_admin', false)
            ->when($this->userSearch !== '', function ($query): void {
                $search = '%'.$this->userSearch.'%';

                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', $search)
                        ->orWhere('username', 'like', $search)
                        ->orWhere('email', 'like', $search);
                });
            })
            ->withCount(['tasks as active_orders_count' => fn ($query) => $query->whereIn('status', ['queued', 'pending'])])
            ->orderBy('name')
            ->limit(50)
            ->get();

        $membershipLevel = $selectedUser
            ? MembershipLevel::query()->where('name', $selectedUser->membership_level)->first()
            : null;

        $queue = $selectedUser
            ? CompletedTask::query()
                ->where('user_id', $selectedUser->id)
                ->whereIn('status', ['queued', 'pending'])
                ->orderBy('id')
                ->get()
            : collect();

        return view('livewire.admin.assign-orders', [
            'selectedUser' => $selectedUser,
            'users' => $users,
            'membershipLevel' => $membershipLevel,
            'queue' => $queue,
            'normalCommission' => (float) ($membershipLevel?->percentage ?? 0),
            'mergePercentage' => self::MERGE_PERCENTAGE,
            'maxOrders' => self::MAX_ORDERS,
            'productResults' => $this->productResults(),
        ]);
    }

    private function loadAssignment(): void
    {
        $this->resetAssignment();

        if ($this->userId === null) {
            return;
        }

        $user = $this->selectedUser();

        if (! $user) {
            $this->userId = null;

            return;
        }

        $remainingInSet = $user->productsPerSet() - $user->tasks_completed;

        $this->totalOrders = $remainingInSet > 0
            ? $remainingInSet
            : ProductWarehouse::PRODUCTS_PER_SET;
    }

    private function resetAssignment(): void
    {
        $this->totalOrders = ProductWarehouse::PRODUCTS_PER_SET;
        $this->mergeOrders = [];
        $this->productPickerIndex = null;
        $this->productSearch = '';
        $this->clearExisting = true;
        $this->resetValidation();
    }

    private function selectedUser(): ?User
    {
        return $this->userId === null
            ? null
            : User::query()->where('is_admin', false)->find($this->userId);
    }

    private function nextMergePosition(): int
    {
        $used = array_map(fn (array $row): int => (int) $row['position'], $this->mergeOrders);

        for ($position = 1; $position <= max(1, $this->totalOrders); $position++) {
            if (! in_array($position, $used, true)) {
                return $position;
            }
        }

        return max(1, $this->totalOrders);
    }

    /**
     * Products the currently open bundle picker can offer.
     *
     * @return list<array<string, mixed>>
     */
    private function productResults(): array
    {
        $user = $this->selectedUser();

        if (
            ! $user
            || $this->productPickerIndex === null
            || ! isset($this->mergeOrders[$this->productPickerIndex])
        ) {
            return [];
        }

        $submitted = $user->submitted_warehouse_products ?? [];

        return array_values(array_filter(
            ProductWarehouse::searchProducts(null, $this->productSearch, 60),
            fn (array $product): bool => ! $this->isMergeProductUsed((string) $product['id'])
                && ! in_array($product['id'], $submitted, true),
        ));
    }

    private function isMergeProductUsed(string $productId): bool
    {
        foreach ($this->mergeOrders as $mergeOrder) {
            if (in_array($productId, array_column($mergeOrder['items'], 'id'), true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $product
     * @return array<string, mixed>
     */
    private function mergeItem(array $product): array
    {
        return [
            'id' => (string) $product['id'],
            'name' => (string) $product['name'],
            'image' => $product['image'] ?? null,
            'image_url' => $product['image_url'] ?? null,
            'brand' => $product['brand'] ?? null,
            'level' => $product['level'] ?? null,
            'day' => $product['day'] ?? null,
            'group' => $product['group'] ?? null,
            'price' => (float) $product['price'],
        ];
    }

    /**
     * @param  array{key: string, position: int|string, items: list<array<string, mixed>>}  $mergeOrder
     */
    private function createMergeTask(User $user, array $mergeOrder, int $position, int $availableAfter): void
    {
        $items = [];

        foreach ($mergeOrder['items'] as $item) {
            $product = ProductWarehouse::findProduct(null, (string) $item['id']);

            if ($product === null) {
                throw ValidationException::withMessages([
                    'mergeOrders' => 'A selected bundle product is no longer available. Remove it and choose another.',
                ]);
            }

            $items[] = $this->mergeItem($product);
        }

        $names = array_column($items, 'name');
        $title = count($names) > 2
            ? $names[0].' + '.$names[1].' + '.(count($names) - 2).' more'
            : implode(' + ', $names);
        $totalCents = (int) round(array_sum(array_column($items, 'price')) * 100);
        $commissionCents = (int) round($totalCents * self::MERGE_PERCENTAGE / 100);
        $negativeCents = max(0, $totalCents - (int) $user->balance);

        $user->tasks()->create([
            'title' => $title !== '' ? $title : 'Merged order',
            'cost' => number_format($totalCents / 100, 2, '.', ''),
            'task_image_path' => (string) ($items[0]['image'] ?? ''),
            'brand' => $items[0]['brand'] ?? null,
            'commission_percentage' => self::MERGE_PERCENTAGE,
            'commission_amount' => $commissionCents,
            'available_after_tasks' => $availableAfter,
            'rating' => '0',
            'rating_id' => substr(bin2hex(random_bytes(7)), 0, 14),
            'status' => 'queued',
            'is_merge' => true,
            'merge_items' => $items,
            'merge_position' => $position,
            'merge_negative_balance' => $negativeCents,
        ]);
    }

    /**
     * @param  array<string, mixed>  $product
     */
    private function createNormalTask(User $user, array $product, int $availableAfter): void
    {
        $user->tasks()->create([
            'title' => (string) $product['name'],
            'cost' => number_format((float) $product['price'], 2, '.', ''),
            'task_image_path' => (string) ($product['image'] ?? ''),
            'brand' => $product['brand'] ?? null,
            'warehouse_product_id' => $product['id'],
            'available_after_tasks' => $availableAfter,
            'rating' => '0',
            'rating_id' => substr(bin2hex(random_bytes(7)), 0, 14),
            'status' => 'queued',
        ]);
    }
}
