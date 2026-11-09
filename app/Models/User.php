<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Support\ProductWarehouse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * One-time welcome credit for newly registered accounts, in cents.
     */
    public const REGISTRATION_BONUS_IN_CENTS = 3000;

    /**
     * Task sets a user completes in a full day of work.
     */
    public const DAILY_TASK_SETS = 3;

    /**
     * Task sets a user may complete in a day before an admin reset is required.
     */
    public const FREE_TASK_SETS = 1;

    /**
     * Available balance a new real account needs for sets after the first one
     * on their first day, in cents.
     */
    public const FIRST_DAY_SET_BALANCE_IN_CENTS = 10000;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'username',
        'profile_image_path',
        'email',
        'password',
        'unhashed_password',
        'mobile_number',
        'balance',
        'negative_balance',
        'training_balance',
        'has_made_first_deposit',
        'tasks_completed',
        'task_batch',
        'task_set_resets',
        'current_day',
        'products_per_set',
        'daily_commission',
        'total_commission',
        'processing_amount',
        'withdrawal_password',
        'unhashed_withdrawal_password',
        'withdrawal_address',
        'withdrawal_address_type',
        'credit_score',
        'membership_level',
        'required_upgrade_level',
        'is_admin',
        'is_training',
        'training_referral_commission_enabled',
        'referral_code',
        'referred_by',
        'account_status',
        'lien_amount',
        'lien_status',
        'timezone',
        'last_reset_at',
        'warehouse_groups',
        'submitted_warehouse_products',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_reset_at' => 'datetime',
            'has_made_first_deposit' => 'boolean',
            'is_training' => 'boolean',
            'training_referral_commission_enabled' => 'boolean',
            'password' => 'hashed',
            'warehouse_groups' => 'array',
            'submitted_warehouse_products' => 'array',
            'negative_balance' => 'integer',
            'current_day' => 'integer',
            'products_per_set' => 'integer',
            'task_batch' => 'integer',
            'task_set_resets' => 'integer',
        ];
    }

    /** @return array{day: int, group: string}|null */
    public function warehouseGroup(): ?array
    {
        return $this->warehouse_groups[strtolower($this->membership_level)] ?? null;
    }

    /**
     * Products that make up one set for the user's membership level.
     */
    public function productsPerSet(): int
    {
        return (int) ($this->products_per_set ?? ProductWarehouse::PRODUCTS_PER_SET);
    }

    /**
     * Determine whether the user has used every task set granted for today.
     *
     * Every user gets one free set a day. An admin reset grants one more set.
     */
    public function hasReachedTaskSetLimit(): bool
    {
        return (int) $this->task_batch >= self::FREE_TASK_SETS + (int) ($this->attributes['task_set_resets'] ?? 0);
    }

    /** @return list<array<string, mixed>> */
    public function remainingWarehouseProducts(int $day, string $group): array
    {
        return array_values(array_filter(
            ProductWarehouse::groupItems(strtolower($this->membership_level), $day, $group),
            fn (array $product): bool => ! in_array($product['id'], $this->submitted_warehouse_products ?? [], true),
        ));
    }

    /**
     * The warehouse day the user is working through, clamped to the available days.
     */
    public function currentDay(): int
    {
        return max(1, min(ProductWarehouse::DAY_COUNT, (int) ($this->current_day ?? 1)));
    }

    /**
     * Determine whether the user is on the first day of their product cycle.
     */
    public function isOnFirstTaskDay(): bool
    {
        return $this->currentDay() === 1;
    }

    /**
     * The next product for the user, walking days and groups in order.
     *
     * Within a day the groups are consumed in sequence (A, B, C), and the
     * current day advances once every group of the day is submitted. After the
     * last day the cycle starts over with a fresh set of products.
     *
     * @return array<string, mixed>|null
     */
    public function nextWarehouseProduct(): ?array
    {
        $product = $this->firstRemainingWarehouseProduct();

        if ($product === null && $this->currentDay() >= ProductWarehouse::DAY_COUNT) {
            $this->startWarehouseCycle();

            return $this->firstRemainingWarehouseProduct();
        }

        return $product;
    }

    /**
     * Move to the next day once every group of the current day is submitted.
     */
    public function advanceWarehouseDayIfComplete(): void
    {
        $day = $this->currentDay();

        foreach (ProductWarehouse::GROUPS as $group) {
            if ($this->remainingWarehouseProducts($day, $group) !== []) {
                return;
            }
        }

        if ($day < ProductWarehouse::DAY_COUNT) {
            $this->update(['current_day' => $day + 1]);

            return;
        }

        $this->startWarehouseCycle();
    }

    /**
     * Start a fresh five-day cycle once the last day is complete.
     */
    private function startWarehouseCycle(): void
    {
        $this->update([
            'current_day' => 1,
            'submitted_warehouse_products' => [],
        ]);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function firstRemainingWarehouseProduct(): ?array
    {
        for ($day = $this->currentDay(); $day <= ProductWarehouse::DAY_COUNT; $day++) {
            foreach (ProductWarehouse::GROUPS as $group) {
                $product = $this->remainingWarehouseProducts($day, $group)[0] ?? null;

                if ($product !== null) {
                    if ($day !== (int) $this->current_day) {
                        $this->update(['current_day' => $day]);
                    }

                    return $product;
                }
            }
        }

        return null;
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->map(fn (string $name) => Str::of($name)->substr(0, 1))
            ->implode('');
    }

    public function isAdmin(): bool
    {
        return true;
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(CompletedTask::class);
    }

    public function alerts(): HasMany
    {
        return $this->hasMany(Alert::class);
    }

    public function wallets(): HasMany
    {
        return $this->hasMany(Wallet::class);
    }

    public function deposits(): HasMany
    {
        return $this->hasMany(Deposit::class);
    }

    public function withdrawals(): HasMany
    {
        return $this->hasMany(Withdrawal::class);
    }

    public function goldenEggs(): HasMany
    {
        return $this->hasMany(GoldenEgg::class);
    }

    public function dailyCheckIns(): HasMany
    {
        return $this->hasMany(DailyCheckIn::class);
    }

    /**
     * Reduce the merge-product shortfall with money the user has just topped up.
     */
    public function applyNegativeBalancePayment(int $amountInCents): void
    {
        if ($amountInCents <= 0 || (int) $this->negative_balance <= 0) {
            return;
        }

        $this->negative_balance = max(0, (int) $this->negative_balance - $amountInCents);
    }

    /**
     * The membership level the user must reach before submitting tasks.
     */
    public function requiredUpgradeLevel(): ?string
    {
        return $this->attributes['required_upgrade_level'] ?? null;
    }

    /**
     * Determine whether the user still owes a required membership upgrade.
     *
     * Levels rank by their commission percentage, so a user on a level at or
     * above the required one has satisfied the requirement.
     */
    public function requiresUpgrade(): bool
    {
        $requiredLevel = $this->requiredUpgradeLevel();

        if (blank($requiredLevel) || $this->membership_level === $requiredLevel) {
            return false;
        }

        $targetPercentage = MembershipLevel::query()
            ->where('name', $requiredLevel)
            ->value('percentage');

        if ($targetPercentage === null) {
            return false;
        }

        $currentPercentage = MembershipLevel::query()
            ->where('name', $this->membership_level)
            ->value('percentage');

        return $currentPercentage === null || (float) $currentPercentage < (float) $targetPercentage;
    }

    public function scopeSearch(Builder $query, string $term): Builder
    {
        return $query->where('username', 'LIKE', '%'.$term.'%');
    }
}
