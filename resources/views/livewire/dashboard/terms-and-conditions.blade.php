@php
    $sections = [
        [
            'title' => 'I. Drive Traffic',
            'items' => [
                '1.1) Reset requires a minimum deposit of $99',
                '1.2) Once all data traffic has been done, users should apply for a full withdrawal and receive the withdrawal amount before they can request to reset their account',
            ],
        ],
        [
            'title' => 'II. Withdrawal',
            'items' => [
                '2.1) Please DO NOT use the same cryptocurrency address information to register multiple user accounts on the platform, if gets detected by the system the account will be frozen',
                '2.2) After completing all products, users may apply for a full withdrawal',
                '2.3) Withdrawal or refund is not available in the middle of the drive traffic process',
                '2.4) Users are required to submit withdrawal requests from the platform to receive payment',
            ],
        ],
        [
            'title' => 'III. Funds',
            'items' => [
                "3.1) All funds of the user will be safely kept in the user's account and can be requested to withdraw the total amount once products have been completed.",
                '3.2) To avoid any loss of funds, all funds will be processed by the system and not manually',
            ],
        ],
        [
            'title' => 'IV. Account Security',
            'items' => [
                '4.1) Please do not disclose any passwords to others, if it causes any loss, the platform will not be responsible for any loss',
                '4.2) Only one (1) account registration is allowed per mobile number',
            ],
        ],
    ];
@endphp

<div class="animate-fade-in">
    <div class="mx-auto w-full max-w-3xl space-y-6">
        <div class="flex items-center gap-3">
            <a href="{{ route('dashboard') }}" wire:navigate aria-label="Back to dashboard"
                class="inline-flex h-10 w-10 items-center justify-center rounded-md transition-colors hover:bg-muted">
                <x-dashboard.icon name="arrow-left" class="h-5 w-5" />
            </a>
            <h1 class="font-display text-2xl font-bold">Terms and Conditions</h1>
        </div>

        <div class="space-y-6 rounded-xl border border-border bg-card p-4 sm:p-6">
            @foreach ($sections as $section)
                <div>
                    <h2 class="mb-3 font-display text-base font-bold text-primary">{{ $section['title'] }}</h2>
                    <ul class="space-y-2">
                        @foreach ($section['items'] as $item)
                            <li class="pl-2 text-sm leading-relaxed text-foreground/80">{{ $item }}</li>
                        @endforeach
                    </ul>
                </div>
            @endforeach
        </div>
    </div>
</div>
