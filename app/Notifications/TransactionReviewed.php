<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TransactionReviewed extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    public array $backoff = [10, 30, 60];

    public function __construct(
        public string $type,
        public int $transactionId,
        public int $amount,
        public string $method,
        public bool $approved,
    ) {
        $this->afterCommit();
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
        $status = $this->approved ? 'approved' : 'declined';
        $amount = '$'.number_format($this->amount / 100, 2);

        return (new MailMessage)
            ->subject($this->type.' '.ucfirst($status).' — Transaction #'.$this->transactionId)
            ->greeting('Hi '.($notifiable->name ?: $notifiable->username).',')
            ->line('Your '.strtolower($this->type).' of '.$amount.' has been '.$status.'.')
            ->line('Transaction: '.$this->type.' #'.$this->transactionId)
            ->line('Payment method: '.$this->method)
            ->lineIf($this->approved && $this->type === 'Deposit', 'The funds have been credited to your account balance.')
            ->lineIf($this->approved && $this->type === 'Withdrawal', 'Your withdrawal has been marked as completed.')
            ->lineIf(! $this->approved && $this->type === 'Deposit', 'No funds have been added to your account. Please contact support if you need help with this deposit.')
            ->lineIf(! $this->approved && $this->type === 'Withdrawal', 'The pending hold has been released. Your account balance has not been deducted.')
            ->action('View Wallet', route('dashboard.wallet'))
            ->markdown('mail.transaction-reviewed');
    }
}
