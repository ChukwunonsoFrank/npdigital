@if (config('services.recaptcha.enabled'))
    <div wire:ignore class="g-recaptcha overflow-x-auto" data-recaptcha-widget
        data-sitekey="{{ config('services.recaptcha.key') }}" data-callback="onRecaptchaSuccess"
        data-expired-callback="onRecaptchaExpired"></div>
@endif
