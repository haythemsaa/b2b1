<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderCreatedNotification extends Notification implements ShouldQueue
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
        return (new MailMessage)
            ->subject('Nouvelle commande créée - ' . $this->order->order_number)
            ->greeting('Bonjour ' . $notifiable->name)
            ->line('Une nouvelle commande a été créée.')
            ->line('Numéro de commande: ' . $this->order->order_number)
            ->line('Montant total: ' . number_format($this->order->total, 3) . ' TND')
            ->action('Voir la commande', url('/orders/' . $this->order->id))
            ->line('Merci de votre confiance!');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'order_created',
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
            'total' => $this->order->total,
            'message' => "Nouvelle commande {$this->order->order_number} créée",
        ];
    }
}
