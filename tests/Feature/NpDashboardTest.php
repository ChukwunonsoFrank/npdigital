<?php

declare(strict_types=1);

use App\Livewire\Admin\Dashboard as AdminDashboard;
use App\Livewire\Dashboard\AboutUs as DashboardAbout;
use App\Livewire\Dashboard\Activities as DashboardActivities;
use App\Livewire\Dashboard\Certificate as DashboardCertificate;
use App\Livewire\Dashboard\Event as DashboardEvent;
use App\Livewire\Dashboard\Faqs as DashboardFaqs;
use App\Livewire\Dashboard\Index as DashboardIndex;
use App\Livewire\Dashboard\Record as DashboardRecord;
use App\Livewire\Dashboard\Start as DashboardStart;
use App\Livewire\Dashboard\TermsAndConditions as DashboardTerms;
use App\Models\Event;
use App\Models\User;
use App\Support\Navigation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\HtmlString;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('renders the user home page content', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(DashboardIndex::class)
        ->assertSuccessful()
        ->assertSee('Welcome back')
        ->assertSee('Announcements')
        ->assertSee('assets/np/world-map.jpg')
        ->assertSee('glitter-dot')
        ->assertSee('Three Reasons to Choose Us')
        ->assertSee('Full-Funnel Solution')
        ->assertSee('Advanced Tech')
        ->assertSee('Extended Team')
        ->assertSee('Find Us')
        ->assertSee('Support Services')
        ->assertSee('Live Chat')
        ->assertSee('Chat with our support team')
        ->assertSee('https://code.jivosite.com/chatpage/itW0erCyp0')
        ->assertSee(route('dashboard.activities'));
});

it('renders the redesigned start page content', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(DashboardStart::class)
        ->assertSuccessful()
        ->assertSee('Starting')
        ->assertSee('VIP')
        ->assertSee('Balance')
        ->assertSee('On Hold')
        ->assertSee('Top Rated & Best Selling Products')
        ->assertSee('product-carousel-track')
        ->assertSee('START 0/35')
        ->assertSee('Operating Hours')
        ->assertSee('Users may optimize the Product during the daily operation hours')
        ->assertSee('from 10:00 to 22:59')
        ->assertSee('For further explanation');
});

it('renders the redesigned submission records page content', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(DashboardRecord::class)
        ->assertSuccessful()
        ->assertSee('Submission Records')
        ->assertSee('View all your product submissions')
        ->assertSee('Total Submitted')
        ->assertSee('Total Earned')
        ->assertSee('No submissions yet. Start submitting tasks!')
        ->assertSee('card-elevated');
});

it('renders the activities page content', function () {
    $this->seed(\Database\Seeders\MembershipLevelSeeder::class);
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(DashboardActivities::class)
        ->assertSuccessful()
        ->assertSee('Activities')
        ->assertSee('Earnings & Membership Information')
        ->assertSee('Check In Program')
        ->assertSee('Membership Level Chart')
        ->assertSee('Membership Levels')
        ->assertSee('Terms & Conditions')
        ->assertSee('$500')
        ->assertSee('$1,000')
        ->assertSee('$1,500')
        ->assertSee('$2,000')
        ->assertSee('Day 30')
        ->assertSee('Every user is required to complete 2 sets a day to be entitled for this program.')
        ->assertSee('Platinum')
        ->assertSee('Diamond')
        ->assertSee('$7,999')
        ->assertSeeInOrder(['$7,999', '50', '1.50%'])
        ->assertSee('1.50%')
        ->assertSee('Deposit')
        ->assertSee('Bonuses!')
        ->assertSee('The more you deposit, the more you earn!')
        ->assertSee('10,000+')
        ->assertSee('12%')
        ->assertSee('Bigger Rewards!')
        ->assertSee('Do not hesitate to contact our online support for information.')
        ->assertSee('Normal users are assigned general usage access to data collection')
        ->assertSee('Profits of 1.50% per Product - 50 products per set')
        ->assertSee('assets/img/normal.png')
        ->assertSee('assets/img/silver.png')
        ->assertSee('assets/img/gold.png')
        ->assertSee('assets/img/platinum.png')
        ->assertSee('assets/img/diamond.png')
        ->assertSee('$0 / $399')
        ->assertSee('Need $399.00 more')
        ->assertSee('Need $7,999.00 more')
        ->assertDontSee('Unlocked');
});

it('shows membership level progress based on the user balance', function () {
    $this->seed(\Database\Seeders\MembershipLevelSeeder::class);
    $user = User::factory()->create(['balance' => 50000]);

    Livewire::actingAs($user)
        ->test(DashboardActivities::class)
        ->assertSuccessful()
        ->assertSee('$500 / $999')
        ->assertSee('50%')
        ->assertSee('Need $499.00 more')
        ->assertSee('Request upgrade')
        ->assertDontSee('Need $7,999.00 more');
});

it('authenticated users can view the activities page', function () {
    $this->actingAs(User::factory()->create([
        'is_admin' => false,
        'is_banned' => false,
    ]))
        ->get(route('dashboard.activities'))
        ->assertOk()
        ->assertSee('Activities')
        ->assertSee('Earnings &amp; Membership Information', false);
});

it('renders the daily events page content', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(DashboardEvent::class)
        ->assertSuccessful()
        ->assertSee('Daily Events')
        ->assertSee('Check in, spin the wheel, and open mystery boxes')
        ->assertSee('No events available at the moment.');
});

it('displays event posters when events exist', function () {
    $user = User::factory()->create();
    $event = Event::create(['poster_image_path' => 'events/flyer.jpg']);

    Livewire::actingAs($user)
        ->test(DashboardEvent::class)
        ->assertSuccessful()
        ->assertSee(route('storage.file', $event->poster_image_path))
        ->assertDontSee('No events available at the moment.');
});

it('authenticated users can view the latest event page', function () {
    $this->actingAs(User::factory()->create([
        'is_admin' => false,
        'is_banned' => false,
    ]))
        ->get(route('dashboard.event'))
        ->assertOk()
        ->assertSee('Daily Events');
});

it('renders the certificate page content', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(DashboardCertificate::class)
        ->assertSuccessful()
        ->assertSee('Business Licenses & Certificates')
        ->assertSee('Official business documentation and certifications')
        ->assertSee('No business licenses available yet');
});

it('authenticated users can view the certificate page', function () {
    $this->actingAs(User::factory()->create([
        'is_admin' => false,
        'is_banned' => false,
    ]))
        ->get(route('dashboard.certificate'))
        ->assertOk()
        ->assertSee('Business Licenses &amp; Certificates', false)
        ->assertSee('No business licenses available yet');
});

it('renders the about page content', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(DashboardAbout::class)
        ->assertSuccessful()
        ->assertSee('About Our Growth Agency')
        ->assertSee('Strategic Growth that Drives Sales-Qualified Leads (SQLs)')
        ->assertSee('assets/np/about/business-meeting.jpg')
        ->assertSee('Why Marketing Leaders Trust NP Digital')
        ->assertSee('Acquisition-Focused Growth')
        ->assertSee('20+ Years of Industry-Leading Experience')
        ->assertSee("Let's Chat About Accelerating Your Customer Acquisition", false)
        ->assertSee('Schedule your strategy call.')
        ->assertSee('Meet Our Team')
        ->assertSee('assets/np/about/team-grid-photo.png')
        ->assertSee('Some of our awards')
        ->assertSee('assets/np/about/award-cambridge.png')
        ->assertSee('assets/np/about/award-cfo-of-the-year.png')
        ->assertSee('Recent Articles &amp; Thought Leadership', false)
        ->assertSee('CMSWIRE')
        ->assertSee('text-[#2bab8b]')
        ->assertSee('4 Marketing Lessons Learned Over 4 Decades')
        ->assertSee('Giving Back')
        ->assertSee('We support')
        ->assertSee('One Warm Coat')
        ->assertSee('Schedule a FREE Strategy Meeting')
        ->assertSee('Our Services')
        ->assertSee('Comprehensive digital solutions to grow your business.')
        ->assertSee('SEO & GEO')
        ->assertSee('Paid Media')
        ->assertSee('Content & PR')
        ->assertSee('Amazon')
        ->assertSee('Talk To Our Team')
        ->assertSee('Ready to get started?')
        ->assertSee('Support Services')
        ->assertSee('Live Chat')
        ->assertSee('Chat with our support team')
        ->assertSee('https://code.jivosite.com/chatpage/itW0erCyp0');
});

it('authenticated users can view the about page', function () {
    $this->actingAs(User::factory()->create([
        'is_admin' => false,
        'is_banned' => false,
    ]))
        ->get(route('dashboard.about-us'))
        ->assertOk()
        ->assertSee('About Our Growth Agency')
        ->assertSee('Some of our awards');
});

it('renders the terms and conditions page content', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(DashboardTerms::class)
        ->assertSuccessful()
        ->assertSee('Terms and Conditions')
        ->assertSee('I. Drive Traffic')
        ->assertSee('1.1) Reset requires a minimum deposit of $99')
        ->assertSee('II. Withdrawal')
        ->assertSee('2.3) Withdrawal or refund is not available in the middle of the drive traffic process')
        ->assertSee('III. Funds')
        ->assertSee('3.2) To avoid any loss of funds, all funds will be processed by the system and not manually')
        ->assertSee('IV. Account Security')
        ->assertSee('4.2) Only one (1) account registration is allowed per mobile number')
        ->assertSee(route('dashboard'));
});

it('authenticated users can view the terms and conditions page', function () {
    $this->actingAs(User::factory()->create([
        'is_admin' => false,
        'is_banned' => false,
    ]))
        ->get(route('dashboard.terms-and-conditions'))
        ->assertOk()
        ->assertSee('Terms and Conditions')
        ->assertSee('I. Drive Traffic');
});

it('renders the faq page content', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(DashboardFaqs::class)
        ->assertSuccessful()
        ->assertSee('FAQ')
        ->assertSee('What is FAQ?')
        ->assertSee('FAQ means frequently asked questions')
        ->assertSee('1. To deposit funds')
        ->assertSee('Contact customer service to confirm the deposit address before each deposit.')
        ->assertSee('Provide the transaction receipt after a successful deposit.')
        ->assertSee('2. About Products')
        ->assertSee('The value of the Product is adjusted according to the market value.')
        ->assertSee('Products are randomly assigned according to the total balance on the account.')
        ->assertSee('3. Withdrawal')
        ->assertSee('Withdrawal time is from 10:00 to 22:59 daily.')
        ->assertSee('A 100% credit score is required before any withdrawal can be processed.')
        ->assertSee('lucide-circle-help')
        ->assertSee('lucide-chevron-down')
        ->assertSee('For further explanation, please click "Support" on the platform to contact our online customer service.', false);
});

it('authenticated users can view the faq page', function () {
    $this->actingAs(User::factory()->create([
        'is_admin' => false,
        'is_banned' => false,
    ]))
        ->get(route('dashboard.faqs'))
        ->assertOk()
        ->assertSee('FAQ')
        ->assertSee('1. To deposit funds')
        ->assertSee('3. Withdrawal');
});

it('renders the admin overview page content', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(AdminDashboard::class)
        ->assertSuccessful()
        ->assertSee('Total Users')
        ->assertSee('Total Products')
        ->assertSee('No new messages')
        ->assertSee('No pending upgrade requests')
        ->assertSee('No pending transactions');
});

it('renders the shared dashboard shell with the user navigation', function () {
    $this->actingAs(User::factory()->create(['username' => 'Lina', 'membership_level' => 'Diamond']));

    $html = view('components.layouts.dashboard', [
        'slot' => new HtmlString('<p>page content</p>'),
    ])->render();

    expect($html)
        ->toContain('NP Digital')
        ->toContain('Lina')
        ->toContain('Diamond')
        ->toContain('Logout')
        ->toContain('Start Tasks')
        ->toContain('page content')
        ->toContain('data-sidebar="mobile-nav"')
        // footer
        ->toContain('Global')
        ->toContain('Ubersuggest')
        ->toContain('Premier Partner')
        ->toContain('All rights reserved');
});

it('links the header bell icon to the notifications page', function () {
    $this->actingAs(User::factory()->create(['username' => 'Lina', 'membership_level' => 'Diamond']));

    $html = view('components.layouts.dashboard', [
        'slot' => new HtmlString('<p>page content</p>'),
    ])->render();

    expect($html)
        ->toContain('<a href="'.route('dashboard.notifications').'"')
        ->toContain('aria-label="Notifications"')
        ->toContain('lucide-bell');
});

it('renders the mobile bottom navigation with the primary user links', function () {
    $this->actingAs(User::factory()->create());

    $html = view('components.dashboard.mobile-nav', [
        'nav' => Navigation::user(),
    ])->render();

    expect($html)
        ->toContain('data-sidebar="mobile-nav"')
        ->toContain('md:hidden')
        ->toContain('Home')
        ->toContain('Start Tasks')
        ->toContain('Records')
        ->toContain('Wallet')
        ->toContain('Profile')
        ->toContain('lucide-layout-dashboard')
        ->toContain('lucide-send')
        ->toContain('lucide-circle-user-round')
        ->not->toContain('Notifications');
});

it('renders the floating support bubble on the user dashboard shell', function () {
    $this->actingAs(User::factory()->create());

    $html = view('components.layouts.dashboard', [
        'slot' => new HtmlString('<p>page content</p>'),
    ])->render();

    expect($html)
        ->toContain('aria-label="Customer Support"')
        ->toContain('bottom-[calc(68px+env(safe-area-inset-bottom)+1rem)]')
        ->toContain('md:bottom-6 md:right-6')
        ->toContain('Support Services')
        ->toContain('Chat with our support team')
        ->toContain('https://code.jivosite.com/chatpage/itW0erCyp0');
});

it('hides the sidebar toggle on mobile for the user shell but keeps it for admin', function () {
    $userHtml = view('components.dashboard.header', ['variant' => 'user'])->render();
    $adminHtml = view('components.dashboard.header', ['variant' => 'admin'])->render();

    expect($userHtml)
        ->toContain('data-sidebar="trigger"')
        ->toContain('hidden md:inline-flex');

    expect($adminHtml)
        ->toContain('data-sidebar="trigger"')
        ->toContain('inline-flex')
        ->not->toContain('hidden md:inline-flex');
});

it('renders the admin sidebar navigation', function () {
    $html = view('components.dashboard.sidebar', [
        'nav' => Navigation::admin(),
        'variant' => 'admin',
        'profile' => null,
    ])->render();

    expect($html)
        ->toContain('User Management')
        ->toContain('Product Warehouse')
        ->toContain('Manage Admins')
        ->toContain('Announcements')
        ->not->toContain('Live Chat');
});
