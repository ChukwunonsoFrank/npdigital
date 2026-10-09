<?php

namespace App\Livewire\Dashboard;

use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.dashboard')]
class Faqs extends Component
{
    public function render()
    {
        return view('livewire.dashboard.faqs', [
            'faqs' => [
                [
                    'question' => 'What is FAQ?',
                    'answer' => 'FAQ means frequently asked questions',
                ],
                [
                    'question' => '1. To deposit funds',
                    'answer' => [
                        'Contact customer service to confirm the deposit address before each deposit.',
                        'Provide the transaction receipt after a successful deposit.',
                    ],
                ],
                [
                    'question' => '2. About Products',
                    'answer' => [
                        'The value of the Product is adjusted according to the market value.',
                        'Products are randomly assigned according to the total balance on the account.',
                    ],
                ],
                [
                    'question' => '3. Withdrawal',
                    'answer' => [
                        'Withdrawal time is from 10:00 to 22:59 daily.',
                        'A 100% credit score is required before any withdrawal can be processed.',
                    ],
                ],
            ],
        ]);
    }
}
