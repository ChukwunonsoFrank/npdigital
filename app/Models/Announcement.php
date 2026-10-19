<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Announcement extends Model
{
    /** @use HasFactory<\Database\Factories\AnnouncementFactory> */
    use HasFactory;

    protected $fillable = ['type', 'title', 'body', 'is_active', 'show_as_popup', 'target_audience'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'show_as_popup' => 'boolean'];
    }

    public function recipients(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }
}
