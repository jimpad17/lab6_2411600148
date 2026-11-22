<?php

namespace App\Notifications;

use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent when an outbound movement takes a product to exactly zero.
 * Delivered to the admin notification bell and by email; the observer also
 * writes an Activity Log entry so the event appears in the audit trail.
 */
class StockOutNotification extends Notification implements ShouldQueue
{
    use Queueable;

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
            'title' => 'Out of Stock',
            'message' => "{$this->product->name} ({$this->product->sku}) has run out of stock and is now unavailable.",
            'product_id' => $this->product->id,
            'product_name' => $this->product->name,
            'sku' => $this->product->sku,
            'quantity' => 0,
            'reorder_level' => (int) $this->product->reorder_level,
            'suggested_action' => 'Approve or record a purchase order to replenish this product.',
            'url' => route('products.show', $this->product),
            'icon' => 'bi-x-octagon-fill',
        ];
    }

    public function toDatabase(object $notifiable): array
    {
        return $this->toArray($notifiable);
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject("JimTrack Out of Stock: {$this->product->name}")
            ->greeting("Hello {$notifiable->name},")
            ->line("{$this->product->name} (SKU {$this->product->sku}) has reached zero units.")
            ->line('The product has been automatically marked as unavailable.')
            ->line('Suggested action: '.$this->toArray($notifiable)['suggested_action'])
            ->action('Open product', $this->toArray($notifiable)['url'])
            ->line('This alert was generated automatically by JimTrack Inventory Tracking.');
    }
}
