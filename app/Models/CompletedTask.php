<?php

namespace App\Models;

use App\Support\ProductBrand;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompletedTask extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'title',
        'cost',
        'task_image_path',
        'brand',
        'rating',
        'rating_id',
        'status',
        'review',
        'commission_percentage',
        'commission_amount',
        'warehouse_product_id',
        'available_after_tasks',
        'is_merge',
        'merge_items',
        'merge_position',
        'merge_negative_balance',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $task): void {
            $task->brand ??= ProductBrand::random();
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Commission owed for this task, preferring a fixed amount when one was set.
     */
    public function profitInCents(?float $fallbackPercentage = null): int
    {
        if ($this->commission_amount !== null) {
            return (int) $this->commission_amount;
        }

        $percentage = (float) ($this->commission_percentage ?? $fallbackPercentage ?? 0);

        return (int) round((float) $this->cost * ($percentage / 100) * 100);
    }

    public function profit(?float $fallbackPercentage = null): float
    {
        return $this->profitInCents($fallbackPercentage) / 100;
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_merge' => 'boolean',
            'merge_items' => 'array',
        ];
    }
}
