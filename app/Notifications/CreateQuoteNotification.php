<?php

namespace App\Notifications;

use App\Models\Quote;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CreateQuoteNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $quote;

    public function __construct(Quote $quote)
    {
        $this->quote = $quote;
    }

    public function via($notifiable)
    {
        return ['database']; // optional: 'mail', 'broadcast', etc.
    }

    public function toDatabase($notifiable)
    {
        return [
            'quote_id' => $this->quote->id,
            'user_id' => $this->quote->user_id,
            'sender_name' => User::where('id', $this->quote->user_id)->first()->full_name,
            'message' => 'add a new quote.',
            'redirect' => 'quote_id'
        ];
    }
}
