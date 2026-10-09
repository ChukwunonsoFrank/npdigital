<?php

namespace App\Livewire\Dashboard;

use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.dashboard')]
class TermsAndConditions extends Component
{
    public function render()
    {
        return view('livewire.dashboard.terms-and-conditions');
    }
}
