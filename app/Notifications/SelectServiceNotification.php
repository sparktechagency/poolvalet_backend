<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

class SelectServiceNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function via($notifiable)
    {
        return ['database']; // optional: 'mail', 'broadcast', etc.
    }

    public function toDatabase($notifiable)
    {
        return [
            'provider_id' => $this->data['provider_id'],
            'provider_name' => $this->data['provider_name'],
            'provider_avatar' => $this->data['provider_avatar'],
            'message' => 'selected for your service.',
        ];
    }
}
