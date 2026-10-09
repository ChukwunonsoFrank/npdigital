<?php

namespace App\Livewire\Dashboard;

use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.dashboard')]
class Activities extends Component
{
    public function render()
    {
        $currentCents = (int) (auth()->user()->balance ?? 0);

        return view('livewire.dashboard.activities', [
            'checkInMilestones' => [
                ['day' => 3, 'reward' => '$500'],
                ['day' => 5, 'reward' => '$1,000'],
                ['day' => 15, 'reward' => '$1,500'],
                ['day' => 30, 'reward' => '$2,000'],
            ],
            'checkInTerms' => [
                'Every user is required to complete 2 sets a day to be entitled for this program.',
                'Withdrawable incentives are to be credited to your account consecutively 3, 5, 15 & 30 days.',
            ],
            'membershipChart' => [
                ['name' => 'Normal', 'deposit' => '$99', 'tasks' => '*30*', 'commission' => '0.50%'],
                ['name' => 'Silver', 'deposit' => '$399', 'tasks' => '*35*', 'commission' => '0.75%'],
                ['name' => 'Gold', 'deposit' => '$999', 'tasks' => '*40*', 'commission' => '1.00%'],
                ['name' => 'Platinum', 'deposit' => '$3,999', 'tasks' => '*45*', 'commission' => '1.25%'],
                ['name' => 'Diamond', 'deposit' => '$7,999', 'tasks' => '*50*', 'commission' => '1.50%'],
            ],
            'chartTerms' => [
                'Every user is required to complete 2 sets a day to be entitled for this program.',
                'Withdrawable incentives are to be credited to your account consecutively 3, 5, 15 & 30 days.',
                'Do not hesitate to contact our online support for information.',
            ],
            'depositBonuses' => [
                ['icon' => 'coins', 'label' => 'Deposit of', 'range' => '500 - 1499', 'bonus' => '5%'],
                ['icon' => 'banknote', 'label' => 'Deposits between', 'range' => '1500 - 4999', 'bonus' => '7%'],
                ['icon' => 'wallet', 'label' => 'Deposits between', 'range' => '5000 - 9999', 'bonus' => '9%'],
                ['icon' => 'vault', 'label' => 'Deposits over', 'range' => '10,000+', 'bonus' => '12%'],
            ],
            'membershipLevelCards' => $this->membershipLevelCards($currentCents),
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function membershipLevelCards(int $currentCents): array
    {
        $levels = [
            ['name' => 'Normal', 'image' => 'assets/img/normal.png', 'border' => 'hsl(210 50% 30%)', 'percentage' => '0.50', 'tasks' => 30, 'target' => null],
            ['name' => 'Silver', 'image' => 'assets/img/silver.png', 'border' => 'hsl(0 0% 40%)', 'percentage' => '0.75', 'tasks' => 35, 'target' => 399, 'accent' => 'hsl(0 0% 35%)', 'accent_border' => 'hsl(0 0% 50%)'],
            ['name' => 'Gold', 'image' => 'assets/img/gold.png', 'border' => 'hsl(35 80% 45%)', 'percentage' => '1.00', 'tasks' => 40, 'target' => 999, 'accent' => 'hsl(35 85% 45%)', 'accent_border' => 'hsl(35 85% 55%)'],
            ['name' => 'Platinum', 'image' => 'assets/img/platinum.png', 'border' => 'hsl(145 40% 35%)', 'percentage' => '1.25', 'tasks' => 45, 'target' => 3999, 'accent' => 'hsl(145 55% 38%)', 'accent_border' => 'hsl(145 55% 48%)'],
            ['name' => 'Diamond', 'image' => 'assets/img/diamond.png', 'border' => 'hsl(210 70% 45%)', 'percentage' => '1.50', 'tasks' => 50, 'target' => 7999, 'accent' => 'hsl(210 75% 45%)', 'accent_border' => 'hsl(210 75% 55%)'],
        ];

        return array_map(function (array $level) use ($currentCents): array {
            if ($level['target'] === null) {
                return $level + ['progress' => null];
            }

            $targetCents = $level['target'] * 100;
            $percent = min(100, (int) floor($currentCents / $targetCents * 100));

            return $level + [
                'progress' => [
                    'current' => '$'.number_format($currentCents / 100, 0),
                    'target' => '$'.number_format($level['target'], 0, '.', ''),
                    'percent' => $percent,
                    'button' => $currentCents >= $targetCents
                        ? 'Unlocked'
                        : 'Need $'.number_format(($targetCents - $currentCents) / 100, 0, '.', '').' more',
                ],
            ];
        }, $levels);
    }
}
