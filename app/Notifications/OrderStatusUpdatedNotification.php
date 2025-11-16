<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderStatusUpdatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Order $order,
        public string $oldStatus,
        public string $newStatus
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
        $statusLabel = $this->order->getStatusLabel();

        $message = (new MailMessage)
            ->subject('Mise à jour de votre commande - ' . $this->order->order_number)
            ->greeting('Bonjour ' . $notifiable->name)
            ->line('Le statut de votre commande a été mis à jour.')
            ->line('Numéro de commande: ' . $this->order->order_number)
            ->line('Nouveau statut: ' . $statusLabel);

        if ($this->newStatus === 'shipped' && $this->order->admin_notes) {
            $message->line('Informations d\'expédition: ' . $this->order->admin_notes);
        }

        $message->action('Voir la commande', url('/orders/' . $this->order->id))
            ->line('Merci de votre confiance!');

        return $message;
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'order_status_updated',
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
            'old_status' => $this->oldStatus,
            'new_status' => $this->newStatus,
            'status_label' => $this->order->getStatusLabel(),
            'message' => "Commande {$this->order->order_number} - {$this->order->getStatusLabel()}",
        ];
    }
}
