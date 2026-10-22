<!doctype html>
<html lang="en">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover" />
    <title>{{ $title ?? 'Account' }} — NP Digital</title>
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
    <link rel="icon" href="/favicon.ico" type="image/x-icon">
    <link rel="shortcut icon" href="/favicon.ico" type="image/x-icon">
    <link rel="preconnect" href="https://rsms.me/">
    <link rel="stylesheet" href="https://rsms.me/inter/inter.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/flag-icons/7.5.0/css/flag-icons.min.css">
    <meta http-equiv="Content-Security-Policy" content="upgrade-insecure-requests">
    @if (config('services.recaptcha.enabled') && ! request()->routeIs('login'))
        <script src="https://www.google.com/recaptcha/api.js" async defer></script>
        <script>
            function onRecaptchaSuccess(token) {
                const widget = document.querySelector('[data-recaptcha-widget]');
                const component = widget?.closest('[wire\\:id]');

                if (component) {
                    Livewire.find(component.getAttribute('wire:id')).set('gRecaptchaResponse', token);
                }
            }

            function onRecaptchaExpired() {
                const widget = document.querySelector('[data-recaptcha-widget]');
                const component = widget?.closest('[wire\\:id]');

                if (component) {
                    Livewire.find(component.getAttribute('wire:id')).set('gRecaptchaResponse', null);
                }
            }
        </script>
    @endif
    @livewireStyles
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        [x-cloak] {
            display: none !important;
        }

        a.gt_switcher-popup span { display: none; }
        a.gt_switcher-popup { padding: 0 !important; }
        a.gt_switcher-popup img { width: 24px; height: 24px; border-radius: 50%; object-fit: cover; box-shadow: 0 1px 3px #0002; }
        .auth-card:not(.auth-card-large) label { font-size: 12px; }
        .auth-card-large .auth-input { height: 48px; border-width: 2px; padding-left: 44px; font-size: 16px; font-weight: 600; }
        .auth-card-large .auth-input::placeholder { font-weight: 400; }
        .auth-card-large input[type=password], .auth-card-large input[autocomplete=new-password], .auth-card-large input[autocomplete=current-password] { padding-right: 44px; }
        .auth-card-large .auth-field-icon { left: 14px; width: 20px; height: 20px; stroke-width: 2.5; }
        .auth-card-large .auth-password-toggle { right: 14px; }
        .auth-card-large .auth-password-toggle svg { width: 20px; height: 20px; stroke-width: 2.5; }
        .auth-submit { background: var(--color-primary) !important; color: var(--color-primary-foreground) !important; }
        .auth-submit:hover { filter: brightness(.95); }
        @layer utilities {
            .auth-shell .toastify { bottom: max(1rem, env(safe-area-inset-bottom)) !important; }
            @media (min-width: 768px) {
                .auth-shell .toastify { bottom: 1.5rem !important; }
            }
        }
        .auth-shell:has(.toastify.on) button[aria-label="Customer Support"] { margin-bottom: 1rem; }
        @media (min-width: 768px) {
            .auth-shell .toastify { right: 6rem; }
        }
    </style>
</head>

<body class="np-shell auth-shell bg-background text-foreground antialiased">

    {{ $slot }}

    <x-dashboard.support-bubble />

    <!-- ========= JS Files =========  -->
    <!-- Toastify -->
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    <x-toastify />
    <script>
        document.addEventListener('livewire:init', () => {
            Livewire.on('notify', ({ message }) => window.showToast(message));
        });
        @if (session('status'))
            window.showToast(@js(session('status')));
        @endif
        window.gtranslateSettings = {
            default_language: 'en', detect_browser_language: true,
            wrapper_selector: '.gtranslate_wrapper', flag_size: 24, flag_style: '3d'
        };
    </script>
    <script src="https://cdn.gtranslate.net/widgets/latest/popup.js" defer></script>
    @fluxScripts
    @livewireScripts
    @if (config('services.recaptcha.enabled') && ! request()->routeIs('login'))
        <script>
            document.addEventListener('livewire:init', () => {
                Livewire.on('recaptcha-reset', () => window.grecaptcha?.reset());
            });
        </script>
    @endif
</body>

</html>
