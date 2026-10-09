<?php

namespace App\Livewire\Dashboard;

use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.dashboard')]
class Certificate extends Component
{
    public function render()
    {
        return view('livewire.dashboard.certificate');
    }
}
