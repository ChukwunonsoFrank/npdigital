<?php

namespace App\Livewire\Dashboard;

use App\Models\GoldenEgg as GoldenEggModel;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class GoldenEgg extends Component
{
    public int $claimedAmount = 0;

    public bool $showCelebration = false;

    public function claim(): void
    {
        abort_if(session()->has('view_as_admin_id'), 403);

        $user = auth()->user();
        $claimedEgg = null;

        DB::transaction(function () use ($user, &$claimedEgg): void {
            $egg = GoldenEggModel::query()
                ->where('user_id', $user->id)
                ->pending()
                ->orderBy('id')
                ->lockForUpdate()
                ->first();

            if (! $egg) {
                return;
            }

            $egg->update([
                'status' => 'claimed',
                'claimed_at' => now(),
            ]);

            User::query()->whereKey($user->id)->lockForUpdate()->increment('balance', $egg->amount);

            $claimedEgg = $egg;
        });

        if (! $claimedEgg) {
            $this->dispatch('toast', message: 'There is no golden egg waiting right now.');

            return;
        }

        $this->claimedAmount = (int) $claimedEgg->amount;
        $this->showCelebration = true;
    }

    public function dismiss(): void
    {
        $this->showCelebration = false;
        $this->claimedAmount = 0;
    }

    public function render(): View
    {
        $pendingEgg = auth()->check()
            ? GoldenEggModel::query()->where('user_id', auth()->id())->pending()->orderBy('id')->first()
            : null;

        return view('livewire.dashboard.golden-egg', [
            'pendingEgg' => $pendingEgg,
        ]);
    }
}
