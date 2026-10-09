<?php

namespace App\Livewire\Dashboard;

use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.dashboard')]
class Index extends Component
{
    public function render()
    {
        return view('livewire.dashboard.index', [
            'announcements' => [
                'Add products early to get faster approvals!',
                'Diamond members now get priority review!',
                'System maintenance scheduled for April 2nd, 2026',
            ],
            'services' => [
                ['label' => 'Services', 'icon' => 'headphones', 'modal' => 'support'],
                ['label' => 'Activities', 'icon' => 'activity', 'url' => route('dashboard.activities')],
                ['label' => 'Certificate', 'icon' => 'shield-check', 'url' => route('dashboard.certificate')],
                ['label' => 'About', 'icon' => 'info', 'url' => route('dashboard.about-us')],
                ['label' => 'T&C', 'icon' => 'file-text', 'url' => route('dashboard.terms-and-conditions')],
                ['label' => 'FAQ', 'icon' => 'circle-help', 'url' => route('dashboard.faqs')],
            ],
            'reasons' => [
                [
                    'icon' => 'filter',
                    'title' => 'Full-Funnel Solution',
                    'body' => 'Awareness, engagement, and conversion working together across every channel.',
                ],
                [
                    'icon' => 'wifi',
                    'title' => 'Advanced Tech',
                    'body' => 'AI-driven marketing technology and analytics built to uncover growth.',
                ],
                [
                    'icon' => 'puzzle',
                    'title' => 'Extended Team',
                    'body' => 'Dedicated digital specialists who work as a true extension of your team.',
                ],
            ],
            'findUs' => [
                'title' => 'Find Us',
                'description' => 'Visit our office in Las Vegas, Nevada.',
                'mapUrl' => 'https://www.google.com/maps/search/?api=1&query='.urlencode('5940 S Rainbow Blvd, Ste 400, Las Vegas, NV 89118-2507'),
                'embedUrl' => 'https://www.google.com/maps?q='.urlencode('5940 S Rainbow Blvd, Ste 400, Las Vegas, NV 89118-2507').'&output=embed',
            ],
            'mapPoints' => [
                [38, 20, 2.00588, 0.150811],
                [32, 17, 3.33944, 1.82671],
                [36, 24, 2.46197, 1.64959],
                [24, 20, 1.96332, 1.4361],
                [46, 19, 3.31766, 0.0903821],
                [70, 29, 2.1624, 0.044542],
                [78, 26, 2.4581, 2.67234],
                [62, 25, 2.82759, 1.12868],
                [30, 49, 3.33914, 0.329132],
                [32, 51, 1.86815, 1.38234],
                [34, 53, 3.15744, 0.334001],
                [30, 54, 2.22394, 0.156136],
                [46, 52, 2.12927, 2.71346],
                [60, 55, 1.55082, 0.823182],
                [72, 58, 1.57129, 2.86243],
                [52, 58, 1.64223, 0.0800417],
                [44, 60, 2.55704, 1.83331],
                [50, 63, 3.02654, 1.24985],
                [46, 67, 1.81051, 1.48539],
                [52, 70, 3.25269, 1.9303],
                [40, 78, 3.15648, 1.75436],
                [36, 83, 2.76396, 1.56275],
                [52, 80, 2.10985, 2.75426],
                [36, 70, 2.52203, 2.02492],
                [32, 74, 2.13055, 1.24304],
                [76, 85, 2.19701, 0.63098],
            ],
        ]);
    }
}
