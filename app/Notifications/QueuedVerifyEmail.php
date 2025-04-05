<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Auth\Notifications\VerifyEmail as BaseVerifyEmail;

class QueuedVerifyEmail extends BaseVerifyEmail implements ShouldQueue
{
    use Queueable, InteractsWithQueue;

    public $connection = 'database'; 
    public $queue = 'verify_email';      

  
}
