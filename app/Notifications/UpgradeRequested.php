<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UpgradeRequested extends Notification implements ShouldQueue
{
    use Queueable;

    public $tries = 5;

    public $backoff = [10, 30, 60];

    public function __construct(public string $username, public string $level, public int $requestId)
    {
        $this->afterCommit();
        $this->onQueue('notifications');
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('New Membership Upgrade Request')
            ->greeting('Hi Admin,')
            ->line($this->username.' has requested an upgrade to '.$this->level.'.')
            ->line('Request #'.$this->requestId.' is awaiting your review.')
            ->action('Review Upgrade Requests', route('admin.upgrades'));
    }
}
