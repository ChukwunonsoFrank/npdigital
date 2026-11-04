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

        $chart = collect($settings->membershipChart())
            ->keyBy(fn (array $row): string => strtolower(trim((string) ($row['name'] ?? ''))));

        $userProductsPerSet = $user->productsPerSet();
        $tasksFor = function (string $name) use ($chart, $userProductsPerSet): int {
            $row = $chart->get(strtolower(trim($name)));

            if ($row === null || ! array_key_exists('tasks', $row) || $row['tasks'] === null || $row['tasks'] === '') {
                return $userProductsPerSet;
            }

            return max(0, (int) $row['tasks']);
        };

        $cards = $levels->map(function (MembershipLevel $level) use ($user, $perks, $currentLevel, $chart, $tasksFor): array {
            $row = $chart->get(strtolower(trim($level->name)));
            $targetCents = $this->parseCurrencyToCents($row['deposit'] ?? null)
                ?? (int) round((float) str_replace(',', '', (string) $level->minimum_balance) * 100);
            $balanceCents = (int) $user->balance;
            $isCurrent = $currentLevel && $currentLevel->id === $level->id;
            $isReached = $balanceCents >= $targetCents;
            $progress = $targetCents > 0 ? min(100, (int) floor($balanceCents / $targetCents * 100)) : 100;

            return [
                'name' => $level->name,
                'percentage' => rtrim(rtrim(number_format((float) $level->percentage, 2, '.', ''), '0'), '.'),
                'minimum' => $targetCents > 0 ? (int) $targetCents : null,
                'tasks' => $tasksFor($level->name),
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
            'productsPerSet' => $currentLevel ? $tasksFor($currentLevel->name) : $userProductsPerSet,
        ]);
    }

    private function parseCurrencyToCents(mixed $value): ?int
    {
        if ($value === null) {
            return null;
        }

        $numeric = preg_replace('/[^0-9.]/', '', (string) $value);

        if ($numeric === null || $numeric === '' || ! is_numeric($numeric)) {
            return null;
        }

        return (int) round((float) $numeric * 100);
    }
}
