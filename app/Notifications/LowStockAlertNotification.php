<?php

namespace App\Notifications;

use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LowStockAlertNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Product $product
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
            ->subject('Alerte Stock Bas - ' . $this->product->sku)
            ->greeting('Bonjour,')
            ->line('Le stock d\'un produit est faible.')
            ->line('Produit: ' . $this->product->name_fr)
            ->line('SKU: ' . $this->product->sku)
            ->line('Stock actuel: ' . $this->product->stock_quantity)
            ->line('Seuil d\'alerte: ' . $this->product->low_stock_threshold)
            ->action('Gérer le stock', url('/admin/products/' . $this->product->id))
            ->line('Veuillez réapprovisionner ce produit.');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'low_stock_alert',
            'product_id' => $this->product->id,
            'product_sku' => $this->product->sku,
            'product_name' => $this->product->name_fr,
            'current_stock' => $this->product->stock_quantity,
            'threshold' => $this->product->low_stock_threshold,
            'message' => "Stock bas pour {$this->product->sku} ({$this->product->stock_quantity} unités)",
        ];
    }
}
