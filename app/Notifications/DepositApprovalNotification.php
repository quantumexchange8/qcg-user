<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DepositApprovalNotification extends Notification
{
    protected $transaction;
    protected $user;

    public function __construct($transaction, $user) {
        $this->transaction = $transaction;
        $this->user = $user;
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        // NOTE: NEED to change to transaction table usage, this is still based on payment table (outdated)
        $token = md5($this->user->email . $this->transaction->transaction_number);
        return (new MailMessage)
            ->subject('Deposit Approval - ' . $this->transaction->transaction_number)
            ->greeting('Deposit Approval - ' . $this->transaction->transaction_number)
            ->line('Platform: QCG User')
            ->line('Email: ' . $this->user->email)
            ->line('Name: ' . $this->user->first_name)
            ->line('Account No: ' . $this->transaction->to_meta_login)
            ->line('Deposit Amount: ' . $this->transaction->amount)
            ->line('TxHash: ' . $this->transaction->txn_hash)
            ->line('Click the button to proceed with approval')
            ->action('View', route('approval', [
                'token' => $token,
            ]))
            ->line('Thank you for using our application!');
    }

    public function toArray($notifiable): array
    {
        return [];
    }
}
