<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Admin-created or admin-edited premium combo in the exclusive products section.
 *
 * Generated premium combos are not stored; only the changes layered on top of
 * them are, keyed by the generated combo id. Rows flagged as custom are combos
 * the admin added to a membership level.
 */
class WarehouseExclusiveProduct extends Model
{
    /** @use HasFactory<\Database\Factories\WarehouseExclusiveProductFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'exclusive_id',
        'level',
        'name',
        'image_url',
        'price',
        'name2',
        'image_url2',
        'price2',
        'commission',
        'description',
        'is_custom',
        'is_deleted',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => 'float',
            'price2' => 'float',
            'commission' => 'float',
            'is_custom' => 'boolean',
            'is_deleted' => 'boolean',
        ];
    }
}
