<?php

namespace App\Livewire\Dashboard;

use App\Models\Alert as AlertModel;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.dashboard')]
class Alert extends Component
{
    public function markAsRead(int $alertId): void
    {
        $this->ownedAlert($alertId)?->update(['read_status' => 'read']);
    }

    public function delete(int $alertId): void
    {
        $this->ownedAlert($alertId)?->delete();
    }

    public function render()
    {
        $notifications = AlertModel::query()
            ->where('user_id', auth()->id())
            ->latest()
            ->get();

        return view('livewire.dashboard.alert', [
            'notifications' => $notifications,
            'unreadCount' => $notifications->where('read_status', 'unread')->count(),
        ]);
    }

    private function ownedAlert(int $alertId): ?AlertModel
    {
        return AlertModel::query()
            ->where('id', $alertId)
            ->where('user_id', auth()->id())
            ->first();
    }
}
