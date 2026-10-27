<?php

namespace App\Livewire\Dashboard;

use App\Models\MembershipLevel;
use App\Models\PlatformSetting;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.dashboard', ['title' => 'VIP Levels'])]
class VipLevels extends Component
{
    public function render(): View
    {
        $user = auth()->user();
        $settings = PlatformSetting::current();
        $perks = $settings->membershipPerks();
        $levels = MembershipLevel::query()->orderBy('percentage')->get();
        $currentLevel = $levels->first(fn (MembershipLevel $level): bool => strcasecmp($level->name, (string) $user->membership_level) === 0);

        $cards = $levels->map(function (MembershipLevel $level) use ($user, $perks, $currentLevel): array {
            $targetCents = (int) round((float) str_replace(',', '', (string) $level->minimum_balance) * 100);
            $balanceCents = (int) $user->balance;
            $isCurrent = $currentLevel && $currentLevel->id === $level->id;
            $isReached = $balanceCents >= $targetCents;
            $progress = $targetCents > 0 ? min(100, (int) floor($balanceCents / $targetCents * 100)) : 100;

            return [
                'name' => $level->name,
                'percentage' => rtrim(rtrim(number_format((float) $level->percentage, 2, '.', ''), '0'), '.'),
                'minimum' => $targetCents > 0 ? (int) $targetCents : null,
                'perks' => $perks[$level->name] ?? [],
                'is_current' => $isCurrent,
                'is_reached' => $isReached,
                'progress' => $progress,
                'next' => $currentLevel && (float) $level->percentage > (float) $currentLevel->percentage,
            ];
        });

        return view('livewire.dashboard.vip-levels', [
            'cards' => $cards,
            'currentLevel' => $currentLevel,
            'balance' => (int) $user->balance,
            'productsPerSet' => $user->productsPerSet(),
        ]);
    }
}
