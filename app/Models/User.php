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
        'training_balance',
        'has_made_first_deposit',
        'tasks_completed',
        'task_pole',
        'task_batch',
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
        ];
    }

    /** @return array{day: int, group: string}|null */
    public function warehouseGroup(): ?array
    {
        return $this->warehouse_groups[strtolower($this->membership_level)] ?? null;
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
