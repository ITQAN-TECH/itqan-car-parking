<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Notification;

class TicketRequestedNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public $ticket)
    {
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toDatabase(object $notifiable)
    {
        return [
            'ar_message' => "طلب العميل استلام السيارة {$this->ticket->car->car_number} {$this->ticket->car->car_letter} للتذكرة رقم {$this->ticket->ticket_number}",
            'en_message' => "Client requested car pickup {$this->ticket->car->car_number} {$this->ticket->car->car_letter} for ticket number {$this->ticket->ticket_number}",
            'type' => 'ticket_requested',
            'body' => [
                'ticket_id' => $this->ticket->id,
            ]
        ];
    }
}
