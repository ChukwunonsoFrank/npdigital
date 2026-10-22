<?php

namespace App\Models;

use App\Support\ProductBrand;
use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'title',
        'cost',
        'task_image_path',
        'brand',
        'task_level',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $task): void {
            $task->brand ??= ProductBrand::random();
        });
    }
}
