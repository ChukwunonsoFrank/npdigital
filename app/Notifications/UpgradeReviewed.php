<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UpgradeReviewed extends Notification implements ShouldQueue
{
    use Queueable;

    public $tries = 5;

    public $backoff = [10, 30, 60];

    public function __construct(public string $level, public string $status)
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
            ->subject('Membership Upgrade '.ucfirst($this->status))
            ->greeting('Hi '.$notifiable->username.',')
            ->line('Your upgrade request to '.$this->level.' has been '.$this->status.'.')
            ->line($this->status === 'approved'
                ? 'Your new membership level is now active.'
                : 'Your membership level has not changed. Please contact support if you have questions.')
            ->action('View Membership', route('dashboard.activities'));
    }
}
