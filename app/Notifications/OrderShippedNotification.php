<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderShippedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Order $order
    ) {}

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject('Votre commande a été expédiée - ' . $this->order->order_number)
            ->greeting('Bonjour ' . $notifiable->name)
            ->line('Bonne nouvelle! Votre commande a été expédiée.')
            ->line('Numéro de commande: ' . $this->order->order_number)
            ->line('Date d\'expédition: ' . $this->order->shipped_at->format('d/m/Y H:i'));

        if ($this->order->admin_notes) {
            $message->line('Informations: ' . $this->order->admin_notes);
        }

        $message->action('Suivre la commande', url('/orders/' . $this->order->id))
            ->line('Merci de votre confiance!');

        return $message;
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'order_shipped',
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
            'shipped_at' => $this->order->shipped_at,
            'message' => "Votre commande {$this->order->order_number} a été expédiée",
        ];
    }
}
