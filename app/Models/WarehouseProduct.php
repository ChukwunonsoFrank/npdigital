<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Admin-created or admin-edited warehouse product.
 *
 * Generated warehouse products are not stored; only the changes layered on top
 * of them are, keyed by the generated product id. Rows flagged as custom are
 * products the admin added to a day group.
 */
class WarehouseProduct extends Model
{
    /** @use HasFactory<\Database\Factories\WarehouseProductFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'product_id',
        'level',
        'day',
        'group_key',
        'name',
        'image_url',
        'description',
        'price',
        'commission',
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
            'day' => 'integer',
            'price' => 'float',
            'commission' => 'float',
            'is_custom' => 'boolean',
            'is_deleted' => 'boolean',
        ];
    }
}
