<?php

namespace App\Livewire\Admin;

use App\Models\User;
use App\Support\ProductWarehouse;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.dashboard', ['title' => 'Product Tasks'])]
class AdminTasks extends Component
{
    use WithPagination;

    public string $tab = 'assignments';

    public string $search = '';

    public bool $showAssignmentModal = false;

    /** @var list<int|string> */
    public array $assignmentUserIds = [];

    public int $assignmentStep = 1;

    public string $assignmentSearch = '';

    public string $assignmentSelection = '';

    public bool $addPremium = false;

    public string $premiumProductId = '';

    public string $premiumPrice = '';

    public string $premiumPrice2 = '';

    public string $premiumCommission = '';

    public int $premiumPosition = 1;

    public int $assignmentDay = 1;

    public string $assignmentGroup = 'a';

    public function mount(): void
    {
        abort_unless(Auth::user()?->is_admin, 403);
    }

    public function selectTab(string $tab): void
    {
        abort_unless(in_array($tab, ['assignments', 'history'], true), 422);
        $this->tab = $tab;
        $this->reset('search');
        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function openAssignment(?int $userId = null): void
    {
        abort_unless(Auth::user()?->is_admin, 403);
        $this->resetValidation();
        $this->reset('assignmentUserIds', 'assignmentStep', 'assignmentSearch', 'assignmentSelection', 'assignmentDay', 'assignmentGroup', 'addPremium', 'premiumProductId', 'premiumPrice', 'premiumPrice2', 'premiumCommission', 'premiumPosition');

        if ($userId !== null) {
            $user = User::query()->where('is_admin', false)->findOrFail($userId);
            $this->assignmentUserIds = [(string) $user->id];
            $group = $user->warehouseGroup();
            $this->assignmentDay = $group['day'] ?? 1;
            $this->assignmentGroup = $group['group'] ?? 'a';
            $this->assignmentSelection = $group ? $group['day'].'-'.$group['group'] : '';
        }

        $this->showAssignmentModal = true;
    }

    public function updatedAssignmentUserIds(): void
    {
        $this->reset('assignmentSelection', 'premiumProductId', 'premiumPrice', 'premiumPrice2', 'premiumCommission');
        $this->resetValidation();
    }

    public function updatedAssignmentSelection(): void
    {
        $parts = explode('-', $this->assignmentSelection);
        $this->assignmentDay = (int) ($parts[0] ?? 0);
        $this->assignmentGroup = $parts[1] ?? '';
    }

    private function validateAssignmentUsers(): void
    {
        $this->validate([
            'assignmentUserIds' => ['required', 'array', 'min:1'],
            'assignmentUserIds.*' => ['required', 'integer', 'distinct', Rule::exists('users', 'id')->where('is_admin', 0)],
        ], ['assignmentUserIds.required' => 'Select at least one user.']);

        $levels = User::query()->whereKey($this->assignmentUserIds)->pluck('membership_level')->map(fn (string $level): string => strtolower($level))->unique();

        if ($levels->count() !== 1 || ! ProductWarehouse::isValidLevel($levels->first())) {
            throw ValidationException::withMessages(['assignmentUserIds' => 'Select users with the same warehouse membership level.']);
        }
    }

    public function nextAssignmentStep(): void
    {
        abort_unless(Auth::user()?->is_admin, 403);
        $this->validateAssignmentUsers();

        if ($this->assignmentStep === 2) {
            $this->validateAssignmentGroup();
        }

        $this->assignmentStep = min(3, $this->assignmentStep + 1);
    }

    public function previousAssignmentStep(): void
    {
        $this->assignmentStep = max(1, $this->assignmentStep - 1);
        $this->resetValidation();
    }

    private function validateAssignmentGroup(): void
    {
        $this->validate([
            'assignmentSelection' => ['required'],
            'assignmentDay' => ['required', 'integer', 'between:1,'.ProductWarehouse::DAY_COUNT],
            'assignmentGroup' => ['required', Rule::in(ProductWarehouse::GROUPS)],
        ]);

        foreach (User::query()->whereKey($this->assignmentUserIds)->get() as $user) {
            if ($user->remainingWarehouseProducts($this->assignmentDay, $this->assignmentGroup) === []) {
                throw ValidationException::withMessages(['assignmentSelection' => 'This group is empty or already completed by '.$user->username.'.']);
            }
        }
    }

    /** @return list<array<string, mixed>> */
    private function premiumProducts(): array
    {
        $levels = User::query()->where('is_admin', false)->whereKey($this->assignmentUserIds)->pluck('membership_level')->map(fn (string $level): string => strtolower($level))->unique();

        return $levels->count() === 1 ? ProductWarehouse::exclusiveProducts($levels->first()) : [];
    }

    public function updatedPremiumProductId(): void
    {
        $product = collect($this->premiumProducts())->firstWhere('id', $this->premiumProductId);
        $this->premiumPrice = $product ? (string) $product['price'] : '';
        $this->premiumPrice2 = $product && $product['name2'] ? (string) $product['price2'] : '';
        $this->premiumCommission = $product ? (string) $product['commission'] : '';
        $this->resetValidation();
    }

    public function saveAssignment(): void
    {
        abort_unless(Auth::user()?->is_admin, 403);
        $this->validateAssignmentUsers();
        $this->validateAssignmentGroup();
        $product = null;

        if ($this->addPremium) {
            $products = $this->premiumProducts();
            $product = collect($products)->firstWhere('id', $this->premiumProductId);
            $priceRules = ['required', 'numeric', 'min:0.01', 'max:49999999.99', 'decimal:0,2'];
            $this->validate([
                'premiumProductId' => ['required', Rule::in(array_column($products, 'id'))],
                'premiumPrice' => $priceRules,
                'premiumPrice2' => $product && $product['name2'] ? $priceRules : ['nullable'],
                'premiumCommission' => ['required', 'numeric', 'between:0,100', 'decimal:0,2'],
                'premiumPosition' => ['required', 'integer', 'min:1'],
            ]);
        }

        DB::transaction(function () use ($product): void {
            foreach (User::query()->where('is_admin', false)->whereKey($this->assignmentUserIds)->lockForUpdate()->get() as $user) {
                $remainingCount = count($user->remainingWarehouseProducts($this->assignmentDay, $this->assignmentGroup));
                if ($product && $this->premiumPosition > min($remainingCount + 1, $user->task_pole - $user->tasks_completed)) {
                    throw ValidationException::withMessages(['premiumPosition' => 'Position must fit within every selected user’s remaining tasks.']);
                }
                $user->update(['warehouse_groups' => [
                    ...($user->warehouse_groups ?? []),
                    strtolower($user->membership_level) => ['day' => $this->assignmentDay, 'group' => $this->assignmentGroup],
                ]]);

                if ($product) {
                    $user->tasks()->create([
                        'title' => $product['name'].($product['name2'] ? ' + '.$product['name2'] : ''),
                        'cost' => number_format((float) $this->premiumPrice + ($product['name2'] ? (float) $this->premiumPrice2 : 0), 2, '.', ''),
                        'commission_percentage' => $this->premiumCommission,
                        'available_after_tasks' => $user->tasks_completed + $this->premiumPosition - 1,
                        'task_image_path' => $product['image'] ?? '',
                        'brand' => $product['brand'],
                        'rating' => '0',
                        'rating_id' => substr(bin2hex(random_bytes(7)), 0, 14),
                        'status' => 'queued',
                    ]);
                }
            }
        });

        $this->showAssignmentModal = false;
        $this->dispatch('toast', message: 'Products assigned successfully.');
    }

    public function render(): View
    {
        $query = User::query()->where('is_admin', false)
            ->when($this->search !== '', function (Builder $query): void {
                $query->where(function (Builder $query): void {
                    $query->where('name', 'like', '%'.$this->search.'%')
                        ->orWhere('username', 'like', '%'.$this->search.'%');
                });
            });

        $assignments = collect();
        $members = null;

        if ($this->tab === 'assignments') {
            $assignments = $query->orderBy('id')->get();
        } else {
            $members = $query
                ->withCount(['tasks as submissions_count' => fn (Builder $query) => $query->where('status', 'approved')])
                ->withMax(['tasks as last_submitted_at' => fn (Builder $query) => $query->where('status', 'approved')], 'updated_at')
                ->orderByDesc('last_submitted_at')->orderBy('id')->paginate(20);

            $members->getCollection()->each(function (User $member): void {
                $member->last_submitted_at = $member->last_submitted_at ? Carbon::parse($member->last_submitted_at) : null;
            });
        }

        return view('livewire.admin.admin-tasks', [
            'assignments' => $assignments,
            'realCount' => $assignments->where('is_training', false)->count(),
            'virtualCount' => $assignments->where('is_training', true)->count(),
            'members' => $members,
            'assignmentUsers' => $this->showAssignmentModal
                ? User::query()->where('is_admin', false)->when($this->assignmentSearch !== '', function (Builder $query): void {
                    $query->where(function (Builder $query): void {
                        $query->where('name', 'like', '%'.$this->assignmentSearch.'%')->orWhere('username', 'like', '%'.$this->assignmentSearch.'%')->orWhere('referral_code', 'like', '%'.$this->assignmentSearch.'%');
                    });
                })->orderBy('id')->limit(50)->get()
                : collect(),
            'selectedAssignmentUsers' => $this->showAssignmentModal ? User::query()->where('is_admin', false)->whereKey($this->assignmentUserIds)->get() : collect(),
            'premiumProducts' => $this->showAssignmentModal && $this->addPremium ? $this->premiumProducts() : [],
        ]);
    }
}
