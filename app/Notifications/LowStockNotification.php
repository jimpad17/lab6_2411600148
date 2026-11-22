<?php

namespace App\Notifications;

use App\Models\Product;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Low-stock alert. Delivery channels: database (bell dropdown) + email.
 *
 * Synchronous so the bell updates immediately after a stock movement; the
 * observer catches any mail failure so it can never roll back stock.
 */
class LowStockNotification extends Notification
{

    public function __construct(public Product $product)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Low Stock Alert',
            'message' => "{$this->product->name} ({$this->product->sku}) is down to {$this->product->quantity} pcs — reorder level is {$this->product->reorder_level} pcs.",
            'product_id' => $this->product->id,
            'product_name' => $this->product->name,
            'sku' => $this->product->sku,
            'quantity' => (int) $this->product->quantity,
            'reorder_level' => (int) $this->product->reorder_level,
            'suggested_action' => 'Create a purchase order / stock-in transaction for this product.',
            'url' => route('products.show', $this->product),
            'icon' => 'bi-exclamation-triangle-fill',
        ];
    }

    public function toDatabase(object $notifiable): array
    {
        return $this->toArray($notifiable);
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject("JimTrack Low Stock Alert: {$this->product->name}")
            ->greeting("Hello {$notifiable->name},")
            ->line("{$this->product->name} (SKU {$this->product->sku}) now has {$this->product->quantity} pcs on hand.")
            ->line("Reorder level: {$this->product->reorder_level} pcs.")
            ->line('Suggested action: '.$this->toArray($notifiable)['suggested_action'])
            ->action('Open product', $this->toArray($notifiable)['url'])
            ->line('This alert was generated automatically by JimTrack Inventory Tracking.');
    }
}
