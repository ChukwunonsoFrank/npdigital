<?php

namespace App\Livewire\Dashboard;

use App\Models\PlatformSetting;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.dashboard')]
class Certificate extends Component
{
    public function render(): View
    {
        return view('livewire.dashboard.certificate', [
            'businessLicenses' => PlatformSetting::current()->business_licenses ?? [],
        ]);
    }
}
