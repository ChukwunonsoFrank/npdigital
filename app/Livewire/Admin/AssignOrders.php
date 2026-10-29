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

    /** @var list<array{key: string, position: int, commission: string, negative_balance: string, items: list<array<string, mixed>>}> */
    public array $mergeOrders = [];

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

        $items = $this->randomMergeItems($user);

        if ($items === []) {
            $this->addError('mergeOrders', 'No products are available to build a merge order for this level.');

            return;
        }

        $costCents = (int) round(array_sum(array_column($items, 'price')) * 100);

        $this->mergeOrders[] = [
            'key' => substr(bin2hex(random_bytes(7)), 0, 14),
            'position' => $this->nextMergePosition(),
            'commission' => number_format($costCents * self::MERGE_PERCENTAGE / 10000, 2, '.', ''),
            'negative_balance' => number_format(max(0, $costCents - (int) $user->balance) / 100, 2, '.', ''),
            'items' => $items,
        ];

        $this->resetValidation('mergeOrders');
    }

    public function removeMergeOrder(int $index): void
    {
        if (! isset($this->mergeOrders[$index])) {
            return;
        }

        unset($this->mergeOrders[$index]);
        $this->mergeOrders = array_values($this->mergeOrders);
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
            'mergeOrders.*.commission' => ['required', 'numeric', 'min:0', 'max:49999999.99', 'decimal:0,2'],
            'mergeOrders.*.negative_balance' => ['required', 'numeric', 'min:0', 'max:49999999.99', 'decimal:0,2'],
            'mergeOrders.*.items' => ['required', 'array', 'min:1'],
        ], [
            'mergeOrders.*.position.distinct' => 'Each merge order needs a unique order number.',
            'mergeOrders.*.position.max' => 'Merge order numbers must fit inside the total orders.',
            'mergeOrders.*.commission.required' => 'Enter the merge commission.',
            'mergeOrders.*.negative_balance.required' => 'Enter the merge negative balance.',
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
     * @return list<array<string, mixed>>
     */
    private function randomMergeItems(User $user): array
    {
        $used = collect($this->mergeOrders)
            ->flatMap(fn (array $row): array => array_column($row['items'], 'id'))
            ->all();
        $submitted = $user->submitted_warehouse_products ?? [];
        $pool = array_values(array_filter(
            ProductWarehouse::levelProducts(strtolower($user->membership_level)),
            fn (array $product): bool => ! in_array($product['id'], $used, true)
                && ! in_array($product['id'], $submitted, true),
        ));

        if ($pool === []) {
            return [];
        }

        shuffle($pool);

        return array_map(fn (array $product): array => [
            'id' => $product['id'],
            'name' => (string) $product['name'],
            'image' => $product['image'],
            'image_url' => $product['image_url'],
            'brand' => $product['brand'],
            'level' => $product['level'],
            'day' => $product['day'],
            'group' => $product['group'],
            'price' => (float) $product['price'],
        ], array_slice($pool, 0, min(3, count($pool))));
    }

    /**
     * @param  array{key: string, position: int|string, commission: string, negative_balance: string, items: list<array<string, mixed>>}  $mergeOrder
     */
    private function createMergeTask(User $user, array $mergeOrder, int $position, int $availableAfter): void
    {
        $items = array_map(fn (array $item): array => [
            'id' => $item['id'],
            'name' => $item['name'],
            'image' => $item['image'] ?? null,
            'brand' => $item['brand'] ?? null,
            'level' => $item['level'] ?? null,
            'day' => $item['day'] ?? null,
            'group' => $item['group'] ?? null,
            'price' => (float) $item['price'],
        ], $mergeOrder['items']);

        $names = array_column($items, 'name');
        $title = count($names) > 2
            ? $names[0].' + '.$names[1].' + '.(count($names) - 2).' more'
            : implode(' + ', $names);
        $totalCents = (int) round(array_sum(array_column($items, 'price')) * 100);

        $user->tasks()->create([
            'title' => $title !== '' ? $title : 'Merged order',
            'cost' => number_format($totalCents / 100, 2, '.', ''),
            'task_image_path' => (string) ($items[0]['image'] ?? ''),
            'brand' => $items[0]['brand'] ?? null,
            'commission_percentage' => self::MERGE_PERCENTAGE,
            'commission_amount' => (int) round(((float) $mergeOrder['commission']) * 100),
            'available_after_tasks' => $availableAfter,
            'rating' => '0',
            'rating_id' => substr(bin2hex(random_bytes(7)), 0, 14),
            'status' => 'queued',
            'is_merge' => true,
            'merge_items' => $items,
            'merge_position' => $position,
            'merge_negative_balance' => (int) round(((float) $mergeOrder['negative_balance']) * 100),
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
