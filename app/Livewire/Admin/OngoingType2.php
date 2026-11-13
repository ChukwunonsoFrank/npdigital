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

#[Layout('components.layouts.dashboard', ['title' => 'Ongoing Type 2'])]
class OngoingType2 extends Component
{
    public const TYPE_TWO_MULTIPLIER = 5.0;

    #[Url(as: 'user')]
    public ?int $userId = null;

    public string $userSearch = '';

    /** @var array<int, list<array<string, mixed>>> */
    public array $mergeProducts = [];

    public ?int $productPickerTaskId = null;

    public string $productSearch = '';

    /** @var array<int, string> */
    public array $mergeCommission = [];

    /** @var array<int, string> */
    public array $negativeBalance = [];

    public function mount(): void
    {
        abort_unless(Auth::user()?->is_admin, 403);

        if ($this->userId !== null) {
            $this->userId = $this->selectedUser()?->id;
        }

        $this->seedInputs();
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
        $this->mergeProducts = [];
        $this->productPickerTaskId = null;
        $this->productSearch = '';
        $this->mergeCommission = [];
        $this->negativeBalance = [];
        $this->resetValidation();
        $this->seedInputs();
    }

    public function changeUser(): void
    {
        $this->userId = null;
        $this->mergeProducts = [];
        $this->productPickerTaskId = null;
        $this->productSearch = '';
        $this->mergeCommission = [];
        $this->negativeBalance = [];
        $this->resetValidation();
    }

    public function toggleProductPicker(int $taskId): void
    {
        abort_unless(Auth::user()?->is_admin, 403);

        if (! $this->assignableTask($taskId)) {
            return;
        }

        $this->productPickerTaskId = $this->productPickerTaskId === $taskId ? null : $taskId;
        $this->productSearch = '';
        $this->resetValidation();
    }

    public function addMergeProduct(int $taskId, string $productId): void
    {
        abort_unless(Auth::user()?->is_admin, 403);

        $user = $this->selectedUser();

        if (! $user || ! $this->assignableTask($taskId)) {
            return;
        }

        $this->productPickerTaskId = $taskId;
        $this->resetErrorBag('productSearch');

        if ($this->isProductInMergeBundle($user, $productId)) {
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

        $previousTotal = $this->bundleTotalCents($this->mergeProducts[$taskId] ?? []);
        $this->mergeProducts[$taskId][] = $this->mergeItem($product);
        $this->syncAutoAmounts($taskId, $previousTotal, $this->bundleTotalCents($this->mergeProducts[$taskId]), $user);

        $this->productPickerTaskId = $taskId;
        $this->resetValidation();
        $this->resetErrorBag('productSearch');
    }

    public function removeMergeProduct(int $taskId, int $itemIndex): void
    {
        abort_unless(Auth::user()?->is_admin, 403);

        $user = $this->selectedUser();

        if (! $user || ! $this->assignableTask($taskId) || ! isset($this->mergeProducts[$taskId][$itemIndex])) {
            return;
        }

        $previousTotal = $this->bundleTotalCents($this->mergeProducts[$taskId]);
        unset($this->mergeProducts[$taskId][$itemIndex]);
        $this->mergeProducts[$taskId] = array_values($this->mergeProducts[$taskId]);
        $this->syncAutoAmounts($taskId, $previousTotal, $this->bundleTotalCents($this->mergeProducts[$taskId]), $user);
        $this->resetValidation();
    }

    public function assignTypeTwo(int $taskId): void
    {
        abort_unless(Auth::user()?->is_admin, 403);

        $user = $this->selectedUser();

        if (! $user) {
            return;
        }

        if (! $this->assignableTask($taskId)) {
            throw ValidationException::withMessages([
                'typeTwo.'.$taskId => 'This order can no longer be assigned as Type 2.',
            ]);
        }

        $validated = $this->validate([
            'mergeProducts.'.$taskId => ['required', 'array', 'min:1'],
            'mergeProducts.'.$taskId.'.*.id' => ['required', 'string'],
            'mergeCommission.'.$taskId => ['required', 'numeric', 'min:0.01', 'max:99999999.99', 'decimal:0,2'],
            'negativeBalance.'.$taskId => ['required', 'numeric', 'min:0', 'max:99999999.99', 'decimal:0,2'],
        ], [
            'mergeProducts.'.$taskId.'.required' => 'Add at least one product to this merge bundle.',
            'mergeProducts.'.$taskId.'.array' => 'Choose the products for this merge bundle.',
            'mergeProducts.'.$taskId.'.min' => 'Add at least one product to this merge bundle.',
            'mergeProducts.'.$taskId.'.*.id.required' => 'One of the selected products is invalid. Remove it and choose another.',
            'mergeCommission.'.$taskId.'.required' => 'Enter the merge commission.',
            'mergeCommission.'.$taskId.'.numeric' => 'The merge commission must be a number.',
            'mergeCommission.'.$taskId.'.min' => 'The merge commission must be at least 0.01.',
            'negativeBalance.'.$taskId.'.required' => 'Enter the negative balance.',
            'negativeBalance.'.$taskId.'.numeric' => 'The negative balance must be a number.',
            'negativeBalance.'.$taskId.'.min' => 'The negative balance cannot be negative.',
        ]);

        $commissionCents = (int) round(((float) $validated['mergeCommission'][$taskId]) * 100);
        $negativeCents = (int) round(((float) $validated['negativeBalance'][$taskId]) * 100);
        $productValueCents = (int) round($commissionCents / (self::TYPE_TWO_MULTIPLIER / 100));

        DB::transaction(function () use ($user, $taskId, $commissionCents, $negativeCents, $productValueCents): void {
            $task = CompletedTask::query()
                ->where('user_id', $user->id)
                ->whereKey($taskId)
                ->lockForUpdate()
                ->first();

            if (! $task || ! in_array($task->status, ['queued', 'pending'], true) || $task->is_merge) {
                throw ValidationException::withMessages([
                    'typeTwo.'.$taskId => 'This order can no longer be assigned as Type 2.',
                ]);
            }

            $bundle = [];

            foreach ($this->mergeProducts[$taskId] ?? [] as $selectedItem) {
                $product = ProductWarehouse::findProduct(null, (string) ($selectedItem['id'] ?? ''));

                if ($product === null) {
                    throw ValidationException::withMessages([
                        'mergeProducts.'.$taskId => 'One of the selected products is no longer available. Remove it and choose another.',
                    ]);
                }

                $bundle[] = $this->mergeItem($product);
            }

            $names = array_column($bundle, 'name');

            $task->update([
                'title' => count($names) > 2
                    ? $names[0].' + '.$names[1].' + '.(count($names) - 2).' more'
                    : implode(' + ', $names),
                'cost' => number_format($productValueCents / 100, 2, '.', ''),
                'task_image_path' => (string) ($bundle[0]['image'] ?? ''),
                'brand' => $bundle[0]['brand'] ?? null,
                'commission_percentage' => self::TYPE_TWO_MULTIPLIER,
                'commission_amount' => $commissionCents,
                'is_merge' => true,
                'merge_items' => $bundle,
                'merge_position' => $this->queuePosition($task, $user),
                'merge_negative_balance' => $negativeCents,
            ]);

            if ($task->status === 'pending') {
                User::query()->whereKey($user->id)->update(['negative_balance' => $negativeCents]);
            }
        });

        unset(
            $this->mergeProducts[$taskId],
            $this->mergeCommission[$taskId],
            $this->negativeBalance[$taskId],
        );

        if ($this->productPickerTaskId === $taskId) {
            $this->productPickerTaskId = null;
            $this->productSearch = '';
        }

        $this->resetValidation();
        $this->dispatch('toast', message: 'Order assigned as Type 2.');
    }

    public function render(): View
    {
        $selectedUser = $this->selectedUser();

        $users = $selectedUser ? collect() : User::query()
            ->where('is_admin', false)
            ->whereHas('tasks', fn ($query) => $query->whereIn('status', ['queued', 'pending']))
            ->when($this->userSearch !== '', function ($query): void {
                $search = '%'.$this->userSearch.'%';

                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', $search)
                        ->orWhere('username', 'like', $search)
                        ->orWhere('email', 'like', $search);
                });
            })
            ->withCount(['tasks as ongoing_orders_count' => fn ($query) => $query->whereIn('status', ['queued', 'pending'])])
            ->orderBy('name')
            ->limit(50)
            ->get();

        $membershipLevel = $selectedUser
            ? MembershipLevel::query()->where('name', $selectedUser->membership_level)->first()
            : null;

        $orders = $selectedUser
            ? CompletedTask::query()
                ->where('user_id', $selectedUser->id)
                ->whereIn('status', ['queued', 'pending'])
                ->orderByRaw('available_after_tasks IS NULL')
                ->orderBy('available_after_tasks')
                ->orderBy('id')
                ->get()
            : collect();

        return view('livewire.admin.ongoing-type2', [
            'selectedUser' => $selectedUser,
            'users' => $users,
            'orders' => $orders,
            'normalCommission' => (float) ($membershipLevel?->percentage ?? 0),
            'typeTwoMultiplier' => self::TYPE_TWO_MULTIPLIER,
            'productResults' => $this->productResults(),
        ]);
    }

    private function seedInputs(): void
    {
        $user = $this->selectedUser();

        if (! $user) {
            return;
        }

        $orders = CompletedTask::query()
            ->where('user_id', $user->id)
            ->whereIn('status', ['queued', 'pending'])
            ->where('is_merge', false)
            ->get(['id']);

        foreach ($orders as $order) {
            $this->mergeProducts[$order->id] ??= [];
            $this->mergeCommission[$order->id] ??= '0.00';
            $this->negativeBalance[$order->id] ??= '0.00';
        }
    }

    private function selectedUser(): ?User
    {
        return $this->userId === null
            ? null
            : User::query()->where('is_admin', false)->find($this->userId);
    }

    private function assignableTask(int $taskId): ?CompletedTask
    {
        $user = $this->selectedUser();

        if (! $user) {
            return null;
        }

        return CompletedTask::query()
            ->where('user_id', $user->id)
            ->whereKey($taskId)
            ->whereIn('status', ['queued', 'pending'])
            ->where('is_merge', false)
            ->first();
    }

    /**
     * Products the currently open Type 2 bundle picker can offer.
     *
     * @return list<array<string, mixed>>
     */
    private function productResults(): array
    {
        $user = $this->selectedUser();

        if (
            ! $user
            || $this->productPickerTaskId === null
            || ! $this->assignableTask($this->productPickerTaskId)
        ) {
            return [];
        }

        $blockedIds = $this->blockedProductIds($user);

        return array_values(array_filter(
            ProductWarehouse::searchProducts(null, $this->productSearch, 60),
            fn (array $product): bool => ! in_array((string) $product['id'], $blockedIds, true),
        ));
    }

    /**
     * @return list<string>
     */
    private function blockedProductIds(User $user): array
    {
        return array_values(array_unique([
            ...($user->submitted_warehouse_products ?? []),
            ...$this->selectedProductIds(),
            ...$this->openMergeProductIds($user),
        ]));
    }

    private function isProductInMergeBundle(User $user, string $productId): bool
    {
        return in_array($productId, [
            ...$this->selectedProductIds(),
            ...$this->openMergeProductIds($user),
        ], true);
    }

    /**
     * @return list<string>
     */
    private function selectedProductIds(): array
    {
        return collect($this->mergeProducts)
            ->flatMap(fn (array $items): array => array_column($items, 'id'))
            ->all();
    }

    /**
     * @return list<string>
     */
    private function openMergeProductIds(User $user): array
    {
        return CompletedTask::query()
            ->where('user_id', $user->id)
            ->whereIn('status', ['queued', 'pending'])
            ->where('is_merge', true)
            ->get(['merge_items'])
            ->flatMap(fn (CompletedTask $merge): array => array_column($merge->merge_items ?? [], 'id'))
            ->all();
    }

    private function syncAutoAmounts(int $taskId, int $previousTotalCents, int $newTotalCents, User $user): void
    {
        $previousCommission = $this->autoCommissionCents($previousTotalCents);
        $previousNegative = $this->autoNegativeCents($previousTotalCents, $user);

        if ($this->inputMatchesCents($this->mergeCommission[$taskId] ?? null, $previousCommission)) {
            $this->mergeCommission[$taskId] = $this->money($this->autoCommissionCents($newTotalCents));
        }

        if ($this->inputMatchesCents($this->negativeBalance[$taskId] ?? null, $previousNegative)) {
            $this->negativeBalance[$taskId] = $this->money($this->autoNegativeCents($newTotalCents, $user));
        }
    }

    /**
     * @param  list<array<string, mixed>>  $items
     */
    private function bundleTotalCents(array $items): int
    {
        return (int) round(array_sum(array_map(fn (array $item): float => (float) ($item['price'] ?? 0), $items)) * 100);
    }

    private function autoCommissionCents(int $totalCents): int
    {
        return (int) round($totalCents * self::TYPE_TWO_MULTIPLIER / 100);
    }

    private function autoNegativeCents(int $totalCents, User $user): int
    {
        return max(0, $totalCents - (int) $user->balance);
    }

    private function inputMatchesCents(mixed $value, int $cents): bool
    {
        return is_numeric($value) && (int) round(((float) $value) * 100) === $cents;
    }

    private function money(int $cents): string
    {
        return number_format($cents / 100, 2, '.', '');
    }

    private function queuePosition(CompletedTask $task, User $user): int
    {
        if ($task->available_after_tasks !== null) {
            return max(1, (int) $task->available_after_tasks - (int) $user->tasks_completed + 1);
        }

        return max(1, (int) $user->tasks_completed + 1);
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
}
