<?php

namespace App\Models;

use App\Notifications\UpgradeRequested;
use App\Notifications\UpgradeReviewed;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

class UpgradeRequest extends Model
{
    /** @use HasFactory<\Database\Factories\UpgradeRequestFactory> */
    use HasFactory;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['reviewed_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function membershipLevel(): BelongsTo
    {
        return $this->belongsTo(MembershipLevel::class);
    }

    public static function submit(User $user, string $levelName): self
    {
        return $user->getConnection()->transaction(function () use ($user, $levelName): self {
            $user = User::query()->lockForUpdate()->findOrFail($user->id);
            abort_if($user->is_admin, 403);
            $level = self::eligibleLevel($user, $levelName);

            if (self::query()->where('user_id', $user->id)->where('status', 'pending')->exists()) {
                throw ValidationException::withMessages(['upgrade' => 'You already have a pending upgrade request.']);
            }

            $request = self::query()->create([
                'user_id' => $user->id,
                'membership_level_id' => $level->id,
                'from_level' => $user->membership_level,
            ]);

            Notification::route('mail', config('mail.support_address'))
                ->notify(new UpgradeRequested($user->username, $level->name, $request->id));

            return $request;
        }, 3);
    }

    public function review(User $admin, bool $approve): void
    {
        abort_unless($admin->is_admin, 403);

        $this->getConnection()->transaction(function () use ($admin, $approve): void {
            $user = User::query()->lockForUpdate()->findOrFail($this->user_id);
            $request = self::query()->lockForUpdate()->findOrFail($this->id);

            if ($request->status !== 'pending') {
                throw ValidationException::withMessages(['upgrade' => 'This request has already been reviewed.']);
            }

            if ($approve) {
                $level = self::eligibleLevel($user, $request->membershipLevel->name);
                $user->membership_level = $level->name;

                if (! $user->requiresUpgrade()) {
                    $user->required_upgrade_level = null;
                }

                $user->save();
            }

            $request->update([
                'status' => $approve ? 'approved' : 'rejected',
                'reviewed_by' => $admin->id,
                'reviewed_at' => now(),
            ]);

            if (filled($user->email)) {
                $user->notify(new UpgradeReviewed($request->membershipLevel->name, $request->status));
            }
        }, 3);
    }

    private static function eligibleLevel(User $user, string $levelName): MembershipLevel
    {
        $level = MembershipLevel::query()->where('name', $levelName)->first();
        $currentPercentage = MembershipLevel::query()->where('name', $user->membership_level)->value('percentage');

        if (! $level || (float) $level->percentage <= (float) $currentPercentage) {
            throw ValidationException::withMessages(['upgrade' => 'Please select a higher membership level.']);
        }

        if ($user->balance < (int) round((float) $level->minimum_balance * 100)) {
            throw ValidationException::withMessages(['upgrade' => 'Insufficient balance for this membership level.']);
        }

        return $level;
    }
}
