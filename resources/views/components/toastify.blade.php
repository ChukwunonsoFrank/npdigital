<style>
    @keyframes np-toast-in {
        from {
            transform: translateY(calc(100% + 6rem));
            opacity: 0;
        }

        to {
            transform: translateY(0);
            opacity: 1;
        }
    }

    .toastify {
        transition: transform 300ms ease, opacity 300ms ease;
        animation: np-toast-in 300ms ease;
        will-change: transform, opacity;
    }

    .toastify:not(.on) {
        transform: translateY(calc(100% + 6rem)) !important;
        opacity: 0 !important;
    }

    @media (prefers-reduced-motion: reduce) {
        .toastify {
            animation: none;
            transition: none;
        }
    }
</style>

<script>
    (function () {
        var NativeToastify = window.Toastify;

        if (!NativeToastify || window.__npToastifyPatched) {
            return;
        }

        window.__npToastifyPatched = true;

        var TOAST_CLASS = 'fixed z-30 bottom-[calc(68px_+_env(safe-area-inset-bottom)_+_1rem)]! left-4 right-4 rounded-2xl bg-[#141414] p-4 shadow-xl md:bottom-6! md:left-auto md:right-6 md:w-96 [&>.toast-close]:hidden';

        var ICON = '<span class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-white">' +
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="#000000" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" class="h-3 w-3" aria-hidden="true">' +
            '<path d="M12 5v9"></path><path d="M12 18h.01"></path></svg></span>';

        var escapeHtml = function (value) {
            return String(value)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');
        };

        var extractMessage = function (markup) {
            var raw = typeof markup === 'string' ? markup : '';
            var text = new DOMParser().parseFromString(raw, 'text/html').body.textContent || '';

            return text.replace(/\s+/g, ' ').trim();
        };

        var buildMarkup = function (message) {
            return '<div class="flex items-start gap-3">' + ICON +
                '<p class="flex-1 text-sm font-bold leading-snug text-white">' + escapeHtml(message) + '</p></div>';
        };

        window.Toastify = function (options) {
            options = options || {};

            return NativeToastify(Object.assign({}, options, {
                text: buildMarkup(extractMessage(options.text)),
                className: TOAST_CLASS,
                gravity: 'bottom',
                position: '',
                close: false,
                backgroundColor: '',
                style: {},
                escapeMarkup: false
            }));
        };

        window.Toastify.defaults = NativeToastify.defaults;

        window.showToast = function (message) {
            var toast = NativeToastify({
                text: buildMarkup(message === undefined || message === null ? '' : String(message)),
                className: TOAST_CLASS,
                gravity: 'bottom',
                position: '',
                close: false,
                escapeMarkup: false
            });

            toast.showToast();

            return toast;
        };
    })();
</script>
