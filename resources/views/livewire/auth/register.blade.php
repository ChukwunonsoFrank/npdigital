<div x-data x-init="$wire.set('timezone', Intl.DateTimeFormat().resolvedOptions().timeZone)"
    @signup-error="window.showToast($event.detail.message)" @recaptcha-reset="window.grecaptcha?.reset()">
    <x-auth.card heading="Sign Up" compact>
        <form wire:submit="register" class="space-y-3">
            <div class="grid grid-cols-2 gap-3">
                <x-auth.field name="name" label="Full Name" icon="user" placeholder="John Doe" autocomplete="name" />
                <x-auth.field name="username" label="Username" icon="user" placeholder="johndoe" autocomplete="username" />
            </div>
            <x-auth.field name="email" label="Email" type="email" placeholder="you@example.com" autocomplete="email" />
            <div class="grid grid-cols-2 gap-3">
                <x-auth.field name="password" label="Password" icon="lock" type="password" placeholder="Password" autocomplete="new-password" />
                <x-auth.field name="password_confirmation" label="Confirm Password" icon="lock" type="password" placeholder="Confirm" autocomplete="new-password" />
            </div>
            <div class="space-y-1">
                <label for="mobile_number" class="block text-sm font-extrabold">Phone Number <span class="font-normal text-muted-foreground">(optional)</span></label>
                <div class="flex gap-2" x-data="{
                                open: false,
                                search: '',
                                selectedIso: 'us',
                                countries: [
                                    { code: '+93', iso: 'af', name: 'Afghanistan' },
                                    { code: '+355', iso: 'al', name: 'Albania' },
                                    { code: '+213', iso: 'dz', name: 'Algeria' },
                                    { code: '+376', iso: 'ad', name: 'Andorra' },
                                    { code: '+244', iso: 'ao', name: 'Angola' },
                                    { code: '+1', iso: 'ag', name: 'Antigua & Barbuda' },
                                    { code: '+54', iso: 'ar', name: 'Argentina' },
                                    { code: '+374', iso: 'am', name: 'Armenia' },
                                    { code: '+61', iso: 'au', name: 'Australia' },
                                    { code: '+43', iso: 'at', name: 'Austria' },
                                    { code: '+994', iso: 'az', name: 'Azerbaijan' },
                                    { code: '+1', iso: 'bs', name: 'Bahamas' },
                                    { code: '+973', iso: 'bh', name: 'Bahrain' },
                                    { code: '+880', iso: 'bd', name: 'Bangladesh' },
                                    { code: '+1', iso: 'bb', name: 'Barbados' },
                                    { code: '+375', iso: 'by', name: 'Belarus' },
                                    { code: '+32', iso: 'be', name: 'Belgium' },
                                    { code: '+501', iso: 'bz', name: 'Belize' },
                                    { code: '+229', iso: 'bj', name: 'Benin' },
                                    { code: '+975', iso: 'bt', name: 'Bhutan' },
                                    { code: '+591', iso: 'bo', name: 'Bolivia' },
                                    { code: '+387', iso: 'ba', name: 'Bosnia & Herzegovina' },
                                    { code: '+267', iso: 'bw', name: 'Botswana' },
                                    { code: '+55', iso: 'br', name: 'Brazil' },
                                    { code: '+673', iso: 'bn', name: 'Brunei' },
                                    { code: '+359', iso: 'bg', name: 'Bulgaria' },
                                    { code: '+226', iso: 'bf', name: 'Burkina Faso' },
                                    { code: '+257', iso: 'bi', name: 'Burundi' },
                                    { code: '+855', iso: 'kh', name: 'Cambodia' },
                                    { code: '+237', iso: 'cm', name: 'Cameroon' },
                                    { code: '+1', iso: 'ca', name: 'Canada' },
                                    { code: '+238', iso: 'cv', name: 'Cape Verde' },
                                    { code: '+236', iso: 'cf', name: 'Central African Republic' },
                                    { code: '+235', iso: 'td', name: 'Chad' },
                                    { code: '+56', iso: 'cl', name: 'Chile' },
                                    { code: '+86', iso: 'cn', name: 'China' },
                                    { code: '+57', iso: 'co', name: 'Colombia' },
                                    { code: '+269', iso: 'km', name: 'Comoros' },
                                    { code: '+242', iso: 'cg', name: 'Congo' },
                                    { code: '+243', iso: 'cd', name: 'Congo (DRC)' },
                                    { code: '+506', iso: 'cr', name: 'Costa Rica' },
                                    { code: '+225', iso: 'ci', name: 'Ivory Coast' },
                                    { code: '+385', iso: 'hr', name: 'Croatia' },
                                    { code: '+53', iso: 'cu', name: 'Cuba' },
                                    { code: '+357', iso: 'cy', name: 'Cyprus' },
                                    { code: '+420', iso: 'cz', name: 'Czech Republic' },
                                    { code: '+45', iso: 'dk', name: 'Denmark' },
                                    { code: '+253', iso: 'dj', name: 'Djibouti' },
                                    { code: '+1', iso: 'dm', name: 'Dominica' },
                                    { code: '+1', iso: 'do', name: 'Dominican Republic' },
                                    { code: '+593', iso: 'ec', name: 'Ecuador' },
                                    { code: '+20', iso: 'eg', name: 'Egypt' },
                                    { code: '+503', iso: 'sv', name: 'El Salvador' },
                                    { code: '+240', iso: 'gq', name: 'Equatorial Guinea' },
                                    { code: '+291', iso: 'er', name: 'Eritrea' },
                                    { code: '+372', iso: 'ee', name: 'Estonia' },
                                    { code: '+268', iso: 'sz', name: 'Eswatini' },
                                    { code: '+251', iso: 'et', name: 'Ethiopia' },
                                    { code: '+679', iso: 'fj', name: 'Fiji' },
                                    { code: '+358', iso: 'fi', name: 'Finland' },
                                    { code: '+33', iso: 'fr', name: 'France' },
                                    { code: '+241', iso: 'ga', name: 'Gabon' },
                                    { code: '+220', iso: 'gm', name: 'Gambia' },
                                    { code: '+995', iso: 'ge', name: 'Georgia' },
                                    { code: '+49', iso: 'de', name: 'Germany' },
                                    { code: '+233', iso: 'gh', name: 'Ghana' },
                                    { code: '+30', iso: 'gr', name: 'Greece' },
                                    { code: '+1', iso: 'gd', name: 'Grenada' },
                                    { code: '+502', iso: 'gt', name: 'Guatemala' },
                                    { code: '+224', iso: 'gn', name: 'Guinea' },
                                    { code: '+245', iso: 'gw', name: 'Guinea-Bissau' },
                                    { code: '+592', iso: 'gy', name: 'Guyana' },
                                    { code: '+509', iso: 'ht', name: 'Haiti' },
                                    { code: '+504', iso: 'hn', name: 'Honduras' },
                                    { code: '+852', iso: 'hk', name: 'Hong Kong' },
                                    { code: '+36', iso: 'hu', name: 'Hungary' },
                                    { code: '+354', iso: 'is', name: 'Iceland' },
                                    { code: '+91', iso: 'in', name: 'India' },
                                    { code: '+62', iso: 'id', name: 'Indonesia' },
                                    { code: '+98', iso: 'ir', name: 'Iran' },
                                    { code: '+964', iso: 'iq', name: 'Iraq' },
                                    { code: '+353', iso: 'ie', name: 'Ireland' },
                                    { code: '+972', iso: 'il', name: 'Israel' },
                                    { code: '+39', iso: 'it', name: 'Italy' },
                                    { code: '+1', iso: 'jm', name: 'Jamaica' },
                                    { code: '+81', iso: 'jp', name: 'Japan' },
                                    { code: '+962', iso: 'jo', name: 'Jordan' },
                                    { code: '+7', iso: 'kz', name: 'Kazakhstan' },
                                    { code: '+254', iso: 'ke', name: 'Kenya' },
                                    { code: '+686', iso: 'ki', name: 'Kiribati' },
                                    { code: '+965', iso: 'kw', name: 'Kuwait' },
                                    { code: '+996', iso: 'kg', name: 'Kyrgyzstan' },
                                    { code: '+856', iso: 'la', name: 'Laos' },
                                    { code: '+371', iso: 'lv', name: 'Latvia' },
                                    { code: '+961', iso: 'lb', name: 'Lebanon' },
                                    { code: '+266', iso: 'ls', name: 'Lesotho' },
                                    { code: '+231', iso: 'lr', name: 'Liberia' },
                                    { code: '+218', iso: 'ly', name: 'Libya' },
                                    { code: '+423', iso: 'li', name: 'Liechtenstein' },
                                    { code: '+370', iso: 'lt', name: 'Lithuania' },
                                    { code: '+352', iso: 'lu', name: 'Luxembourg' },
                                    { code: '+853', iso: 'mo', name: 'Macau' },
                                    { code: '+261', iso: 'mg', name: 'Madagascar' },
                                    { code: '+265', iso: 'mw', name: 'Malawi' },
                                    { code: '+60', iso: 'my', name: 'Malaysia' },
                                    { code: '+960', iso: 'mv', name: 'Maldives' },
                                    { code: '+223', iso: 'ml', name: 'Mali' },
                                    { code: '+356', iso: 'mt', name: 'Malta' },
                                    { code: '+692', iso: 'mh', name: 'Marshall Islands' },
                                    { code: '+222', iso: 'mr', name: 'Mauritania' },
                                    { code: '+230', iso: 'mu', name: 'Mauritius' },
                                    { code: '+52', iso: 'mx', name: 'Mexico' },
                                    { code: '+691', iso: 'fm', name: 'Micronesia' },
                                    { code: '+373', iso: 'md', name: 'Moldova' },
                                    { code: '+377', iso: 'mc', name: 'Monaco' },
                                    { code: '+976', iso: 'mn', name: 'Mongolia' },
                                    { code: '+382', iso: 'me', name: 'Montenegro' },
                                    { code: '+212', iso: 'ma', name: 'Morocco' },
                                    { code: '+258', iso: 'mz', name: 'Mozambique' },
                                    { code: '+95', iso: 'mm', name: 'Myanmar' },
                                    { code: '+264', iso: 'na', name: 'Namibia' },
                                    { code: '+674', iso: 'nr', name: 'Nauru' },
                                    { code: '+977', iso: 'np', name: 'Nepal' },
                                    { code: '+31', iso: 'nl', name: 'Netherlands' },
                                    { code: '+64', iso: 'nz', name: 'New Zealand' },
                                    { code: '+505', iso: 'ni', name: 'Nicaragua' },
                                    { code: '+227', iso: 'ne', name: 'Niger' },
                                    { code: '+234', iso: 'ng', name: 'Nigeria' },
                                    { code: '+850', iso: 'kp', name: 'North Korea' },
                                    { code: '+389', iso: 'mk', name: 'North Macedonia' },
                                    { code: '+47', iso: 'no', name: 'Norway' },
                                    { code: '+968', iso: 'om', name: 'Oman' },
                                    { code: '+92', iso: 'pk', name: 'Pakistan' },
                                    { code: '+680', iso: 'pw', name: 'Palau' },
                                    { code: '+970', iso: 'ps', name: 'Palestine' },
                                    { code: '+507', iso: 'pa', name: 'Panama' },
                                    { code: '+675', iso: 'pg', name: 'Papua New Guinea' },
                                    { code: '+595', iso: 'py', name: 'Paraguay' },
                                    { code: '+51', iso: 'pe', name: 'Peru' },
                                    { code: '+63', iso: 'ph', name: 'Philippines' },
                                    { code: '+48', iso: 'pl', name: 'Poland' },
                                    { code: '+351', iso: 'pt', name: 'Portugal' },
                                    { code: '+974', iso: 'qa', name: 'Qatar' },
                                    { code: '+40', iso: 'ro', name: 'Romania' },
                                    { code: '+7', iso: 'ru', name: 'Russia' },
                                    { code: '+250', iso: 'rw', name: 'Rwanda' },
                                    { code: '+1', iso: 'kn', name: 'Saint Kitts & Nevis' },
                                    { code: '+1', iso: 'lc', name: 'Saint Lucia' },
                                    { code: '+1', iso: 'vc', name: 'Saint Vincent' },
                                    { code: '+685', iso: 'ws', name: 'Samoa' },
                                    { code: '+378', iso: 'sm', name: 'San Marino' },
                                    { code: '+239', iso: 'st', name: 'São Tomé & Príncipe' },
                                    { code: '+966', iso: 'sa', name: 'Saudi Arabia' },
                                    { code: '+221', iso: 'sn', name: 'Senegal' },
                                    { code: '+381', iso: 'rs', name: 'Serbia' },
                                    { code: '+248', iso: 'sc', name: 'Seychelles' },
                                    { code: '+232', iso: 'sl', name: 'Sierra Leone' },
                                    { code: '+65', iso: 'sg', name: 'Singapore' },
                                    { code: '+421', iso: 'sk', name: 'Slovakia' },
                                    { code: '+386', iso: 'si', name: 'Slovenia' },
                                    { code: '+677', iso: 'sb', name: 'Solomon Islands' },
                                    { code: '+252', iso: 'so', name: 'Somalia' },
                                    { code: '+27', iso: 'za', name: 'South Africa' },
                                    { code: '+82', iso: 'kr', name: 'South Korea' },
                                    { code: '+211', iso: 'ss', name: 'South Sudan' },
                                    { code: '+34', iso: 'es', name: 'Spain' },
                                    { code: '+94', iso: 'lk', name: 'Sri Lanka' },
                                    { code: '+249', iso: 'sd', name: 'Sudan' },
                                    { code: '+597', iso: 'sr', name: 'Suriname' },
                                    { code: '+46', iso: 'se', name: 'Sweden' },
                                    { code: '+41', iso: 'ch', name: 'Switzerland' },
                                    { code: '+963', iso: 'sy', name: 'Syria' },
                                    { code: '+886', iso: 'tw', name: 'Taiwan' },
                                    { code: '+992', iso: 'tj', name: 'Tajikistan' },
                                    { code: '+255', iso: 'tz', name: 'Tanzania' },
                                    { code: '+66', iso: 'th', name: 'Thailand' },
                                    { code: '+670', iso: 'tl', name: 'Timor-Leste' },
                                    { code: '+228', iso: 'tg', name: 'Togo' },
                                    { code: '+676', iso: 'to', name: 'Tonga' },
                                    { code: '+1', iso: 'tt', name: 'Trinidad & Tobago' },
                                    { code: '+216', iso: 'tn', name: 'Tunisia' },
                                    { code: '+90', iso: 'tr', name: 'Turkey' },
                                    { code: '+993', iso: 'tm', name: 'Turkmenistan' },
                                    { code: '+688', iso: 'tv', name: 'Tuvalu' },
                                    { code: '+256', iso: 'ug', name: 'Uganda' },
                                    { code: '+380', iso: 'ua', name: 'Ukraine' },
                                    { code: '+971', iso: 'ae', name: 'UAE' },
                                    { code: '+44', iso: 'gb', name: 'United Kingdom' },
                                    { code: '+1', iso: 'us', name: 'United States' },
                                    { code: '+598', iso: 'uy', name: 'Uruguay' },
                                    { code: '+998', iso: 'uz', name: 'Uzbekistan' },
                                    { code: '+678', iso: 'vu', name: 'Vanuatu' },
                                    { code: '+379', iso: 'va', name: 'Vatican City' },
                                    { code: '+58', iso: 've', name: 'Venezuela' },
                                    { code: '+84', iso: 'vn', name: 'Vietnam' },
                                    { code: '+967', iso: 'ye', name: 'Yemen' },
                                    { code: '+260', iso: 'zm', name: 'Zambia' },
                                    { code: '+263', iso: 'zw', name: 'Zimbabwe' },
                                ],
                                get selected() {
                                    return this.countries.find(c => c.iso === this.selectedIso) || this.countries[0];
                                },
                                get filtered() {
                                    if (!this.search) return this.countries;
                                    const s = this.search.toLowerCase();
                                    return this.countries.filter(c => c.name.toLowerCase().includes(s) || c.code.includes(s));
                                },
                                select(country) {
                                    $wire.country_code = country.code;
                                    this.selectedIso = country.iso;
                                    this.open = false;
                                    this.search = '';
                                }
                            }"
                                @click.outside="open = false; search = '';" @keydown.escape="open = false">
                                <div class="relative w-[140px] max-w-[44%] shrink-0">
                                    <button type="button" class="flex h-10 w-full items-center gap-2 rounded-xl border border-border bg-background px-3 text-xs font-semibold hover:bg-muted focus-visible:outline-2 focus-visible:outline-primary" @click="open = !open; if (open) $nextTick(() => $refs.searchInput.focus())"
                                        aria-label="Phone country code" :aria-expanded="open" aria-controls="country-options">
                                        <span class="fi fis" :class="'fi-' + selected.iso"
                                            style="width: 20px; height: 15px; display: inline-block; border-radius: 2px;"></span>
                                        <span x-text="selected.iso.toUpperCase() + ' ' + selected.code" class="truncate"></span>
                                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12"
                                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                            stroke-linecap="round" stroke-linejoin="round" style="margin-left: auto;">
                                            <polyline points="6 9 12 15 18 9"></polyline>
                                        </svg>
                                    </button>
                                    <div id="country-options" x-show="open" x-cloak x-transition class="absolute left-0 top-full z-50 mt-1 w-60 overflow-hidden rounded-xl border border-border bg-card shadow-xl">
                                        <div style="padding: 6px;">
                                            <input type="search" x-model="search" placeholder="Search countries…" aria-label="Search countries" @click.stop
                                                style="width: 100%; border: 1px solid #ddd; border-radius: 6px; padding: 6px 8px; font-size: 13px; outline: none;"
                                                x-ref="searchInput" />
                                        </div>
                                        <div style="max-height: 192px; overflow-y: auto;">
                                            <template x-for="country in filtered" :key="country.iso">
                                                <button type="button" @click="select(country)" class="flex w-full items-center gap-2 px-3 py-2 text-left text-sm hover:bg-muted focus:bg-muted">
                                                    <span class="fi fis" :class="'fi-' + country.iso"
                                                        style="width: 20px; height: 15px; display: inline-block; border-radius: 2px;"></span>
                                                    <span x-text="country.name"
                                                        style="flex: 1; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"></span>
                                                    <span x-text="country.code" style="color: #888;"></span>
                                                </button>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                                <div class="relative min-w-0 flex-1">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6A19.79 19.79 0 0 1 2.12 4.2 2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.12.96.35 1.9.69 2.79a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.89.34 1.83.57 2.79.69A2 2 0 0 1 22 16.92z"/></svg>
                                    <input wire:model="mobile_number" type="tel" id="mobile_number" autocomplete="tel-national" placeholder="Phone number"
                                        class="h-10 w-full min-w-0 rounded-xl border border-border bg-background py-2 pl-9 pr-3 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 placeholder:text-muted-foreground/60">
                                </div>
                            </div>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <x-auth.field name="withdrawal_password" label="Transaction Password" icon="key-round" type="password" placeholder="Withdrawal PIN" />
                <x-auth.field name="withdrawal_password_confirmation" label="Confirm Password" icon="key-round" type="password" placeholder="Confirm" />
            </div>
            <x-auth.field name="ref_code" label="Invitation Code" icon="user" placeholder="SH123456" :required="false" />
            <label class="flex items-start gap-2 text-xs leading-tight text-muted-foreground">
                <input wire:model="termsAndPrivacyPolicyAccepted" type="checkbox" required class="mt-0.5 h-4 w-4 shrink-0 appearance-none rounded-full border border-primary checked:bg-primary checked:shadow-[inset_0_0_0_3px_white] focus-visible:outline-2 focus-visible:outline-primary">
                <span>I agree to the Terms and Conditions</span>
            </label>
            <x-auth.recaptcha />
            <flux:button type="submit" variant="primary" class="auth-submit h-10! w-full rounded-xl! text-sm! font-black! tracking-wide shadow-lg" wire:loading.attr="disabled" wire:target="register">
                <span wire:loading.remove wire:target="register">Sign Up</span>
                <span wire:loading wire:target="register">Creating Account…</span>
            </flux:button>
        </form>
        <p class="mt-3 text-center text-xs font-bold text-muted-foreground">Already have an account? <a href="{{ route('login') }}" class="font-extrabold text-primary hover:underline">Sign In</a></p>
    </x-auth.card>
</div>
