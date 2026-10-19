<?php

use App\Models\User;
use App\Notifications\BroadcastSent;
use App\Notifications\TokenRequested;
use App\Notifications\TransactionReviewed;
use App\Notifications\UpgradeReviewed;
use App\Notifications\UserRegistered;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Notification as BaseNotification;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;

test('outgoing emails share the branded template without losing their content', function (BaseNotification $notification, string $content, ?string $action) {
    $user = User::factory()->make(['id' => 42, 'name' => 'Alex', 'username' => 'alex', 'email' => 'alex@example.com']);

    Notification::sendNow($user, $notification, ['mail']);

    $email = Mail::mailer()->getSymfonyTransport()->messages()->last()->getOriginalMessage();
    $html = $email->getHtmlBody();
    $text = $email->getTextBody();
    $document = new DOMDocument;
    @$document->loadHTML($html);
    $xpath = new DOMXPath($document);

    expect($html)
        ->toContain(asset('assets/npdigital-logo.png'), 'Keep your account secure', 'mailto:support@npdigital.work', 'Strategy. Performance. Growth.')
        ->not->toContain('voldexcustomersservice', 'app.voldexglobal.com', 'npdigital-work.com/assets/', 'fridayudeme960@gmail.com', 'Your next chapter of growth starts here.');
    expect(html_entity_decode(strip_tags($html)))->toContain($content);
    expect($text)->toContain($content, 'The NP Digital Team', 'support@npdigital.work');
    expect($xpath->query('//td[contains(@class,"hero")]'))->toHaveCount(0);
    expect($xpath->query('//title')->item(0)->textContent)
        ->toBe($notification instanceof TokenRequested ? 'Your NP Digital account' : $email->getSubject());

    $buttons = $xpath->query('//a[contains(concat(" ",normalize-space(@class)," ")," button ")]');

    if ($action === null) {
        expect($buttons)->toHaveCount(0);
        expect($html)->not->toContain('copy and paste the URL');
    } else {
        expect($buttons)->toHaveCount(1);
        $url = $buttons->item(0)->getAttribute('href');
        expect($url)->toContain($action);
        expect($text)->toContain($url);
        expect($xpath->query('//a[@href="'.$url.'"]')->length)->toBeGreaterThanOrEqual(2);
    }
})->with([
    'password reset' => [new ResetPassword('reset-token-abc'), 'You are receiving this email because we received a password reset request for your account.', '/reset-password/reset-token-abc'],
    'email verification' => [new VerifyEmail, 'Please click the button below to verify your email address.', '/verify-email/42/'],
    'code without a subject or action' => [new TokenRequested('Alex', '839201'), 'Your token is 839201.', null],
    'admin alert without an action' => [new UserRegistered('new-user@example.com'), 'new-user@example.com just signed up.', null],
    'broadcast' => [new BroadcastSent('Alex', 'Service update', 'Your scheduled maintenance is complete.'), 'Your scheduled maintenance is complete.', null],
    'approved deposit' => [new TransactionReviewed('Deposit', 731, 12345, 'USDT', true), 'Your deposit of $123.45 has been approved.', '/wallet'],
    'declined deposit' => [new TransactionReviewed('Deposit', 732, 25800, 'BTC', false), 'No funds have been added to your account.', '/wallet'],
    'approved withdrawal' => [new TransactionReviewed('Withdrawal', 733, 45678, 'USDT', true), 'Your withdrawal has been marked as completed.', '/wallet'],
    'declined withdrawal' => [new TransactionReviewed('Withdrawal', 734, 90210, 'ETH', false), 'The pending hold has been released.', '/wallet'],
    'membership' => [new UpgradeReviewed('Gold', 'approved'), 'Your upgrade request to Gold has been approved.', '/activities'],
]);

test('email subject and greeting are escaped in the shared template', function () {
    $user = User::factory()->make(['email' => 'alex@example.com']);

    Notification::sendNow($user, new BroadcastSent('<script>alert(1)</script>', '<img src=x onerror=alert(1)>', 'Account update'), ['mail']);

    $html = Mail::mailer()->getSymfonyTransport()->messages()->last()->getOriginalMessage()->getHtmlBody();

    expect($html)->toContain('&lt;script&gt;', '&lt;img')
        ->not->toContain('<script>', '<img src=x');
});
