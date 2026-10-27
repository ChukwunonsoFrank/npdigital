<?php

namespace App\Livewire\Dashboard;

use App\Models\MembershipLevel;
use App\Models\PlatformSetting;
use App\Models\UpgradeRequest;
use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.dashboard')]
class Activities extends Component
{
    public function requestUpgrade(string $levelName): void
    {
        $this->resetValidation();
        UpgradeRequest::submit(auth()->user(), $levelName);
        $this->dispatch('notify', message: 'Upgrade request submitted. An admin will review it shortly.', type: 'success');
    }

    public function render()
    {
        $currentCents = (int) (auth()->user()->balance ?? 0);
        $settings = PlatformSetting::current();
        $checkInProgram = $settings->checkInProgram();

        return view('livewire.dashboard.activities', [
            'checkInMilestones' => $checkInProgram['milestones'],
            'checkInTerms' => preg_split('/\R+/', $checkInProgram['terms']),
            'membershipChart' => $settings->membershipChart(),
            'membershipPerks' => $settings->membershipPerks(),
            'chartTerms' => [
                'Every user is required to complete '.User::DAILY_TASK_SETS.' sets a day to be entitled for this program.',
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
            ['name' => 'Silver', 'image' => 'assets/img/silver.png', 'border' => 'hsl(0 0% 40%)', 'percentage' => '0.75', 'tasks' => 30, 'target' => 399, 'accent' => 'hsl(0 0% 35%)', 'accent_border' => 'hsl(0 0% 50%)'],
            ['name' => 'Gold', 'image' => 'assets/img/gold.png', 'border' => 'hsl(35 80% 45%)', 'percentage' => '1.00', 'tasks' => 30, 'target' => 999, 'accent' => 'hsl(35 85% 45%)', 'accent_border' => 'hsl(35 85% 55%)'],
            ['name' => 'Platinum', 'image' => 'assets/img/platinum.png', 'border' => 'hsl(145 40% 35%)', 'percentage' => '1.25', 'tasks' => 30, 'target' => 3999, 'accent' => 'hsl(145 55% 38%)', 'accent_border' => 'hsl(145 55% 48%)'],
            ['name' => 'Diamond', 'image' => 'assets/img/diamond.png', 'border' => 'hsl(210 70% 45%)', 'percentage' => '1.50', 'tasks' => 30, 'target' => 7999, 'accent' => 'hsl(210 75% 45%)', 'accent_border' => 'hsl(210 75% 55%)'],
        ];

        $configuredLevels = MembershipLevel::query()->get()->keyBy('name');

        $palette = [
            ['border' => 'hsl(270 60% 45%)', 'accent' => 'hsl(270 65% 45%)', 'accent_border' => 'hsl(270 65% 55%)'],
            ['border' => 'hsl(340 65% 45%)', 'accent' => 'hsl(340 70% 45%)', 'accent_border' => 'hsl(340 70% 55%)'],
            ['border' => 'hsl(160 55% 35%)', 'accent' => 'hsl(160 60% 35%)', 'accent_border' => 'hsl(160 60% 45%)'],
            ['border' => 'hsl(25 75% 45%)', 'accent' => 'hsl(25 80% 45%)', 'accent_border' => 'hsl(25 80% 55%)'],
        ];

        foreach ($configuredLevels as $configured) {
            if (collect($levels)->contains('name', $configured->name)) {
                continue;
            }

            $levels[] = [
                'name' => $configured->name,
                'image' => null,
                'border' => $palette[count($levels) % count($palette)]['border'],
                'percentage' => (string) $configured->percentage,
                'tasks' => 0,
                'target' => (float) $configured->minimum_balance > 0 ? (float) $configured->minimum_balance : null,
                'accent' => $palette[count($levels) % count($palette)]['accent'],
                'accent_border' => $palette[count($levels) % count($palette)]['accent_border'],
            ];
        }

        $currentPercentage = (float) ($configuredLevels->get(auth()->user()->membership_level)?->percentage ?? 0);
        $pendingLevelId = UpgradeRequest::query()->where('user_id', auth()->id())->where('status', 'pending')->value('membership_level_id');

        return array_map(function (array $level) use ($currentCents, $configuredLevels, $currentPercentage, $pendingLevelId): array {
            if ($level['target'] === null || (float) $level['target'] <= 0) {
                return $level + ['progress' => null];
            }

            $configured = $configuredLevels->get($level['name']);
            $targetCents = (int) round((float) ($configured?->minimum_balance ?? $level['target']) * 100);
            $isHigher = $configured && (float) $configured->percentage > $currentPercentage;
            $canRequest = $isHigher && $pendingLevelId === null && $currentCents >= $targetCents;
            $percent = $targetCents > 0 ? max(0, min(100, (int) floor($currentCents / $targetCents * 100))) : 100;
            $button = match (true) {
                ! $configured => 'Unavailable',
                ! $isHigher => 'Current level or below',
                $pendingLevelId === $configured->id => 'Upgrade pending',
                $pendingLevelId !== null => 'Another upgrade pending',
                $canRequest => 'Request upgrade',
                default => 'Need $'.number_format(($targetCents - $currentCents) / 100, 2).' more',
            };

            return $level + [
                'progress' => [
                    'current' => '$'.number_format($currentCents / 100, 0),
                    'target' => '$'.number_format($targetCents / 100, 2),
                    'percent' => $percent,
                    'can_request' => $canRequest,
                    'button' => $button,
                ],
            ];
        }, $levels);
    }
}
