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
        'warehouse_product_id',
        'available_after_tasks',
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
}
