<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent to the staff member who submitted an order (and to active admins) once
 * an admin approves it. The bell entry and the email both link to the printable
 * receipt that was generated at approval time.
 *
 * Deliberately synchronous (unlike the low-stock alerts) so the receipt appears
 * in the bell immediately after approval without requiring `php artisan queue:work`.
 */
class OrderApprovedNotification extends Notification
{

    public function __construct(public Order $order)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toArray(object $notifiable): array
    {
        $order = $this->order;

        return [
            'title' => 'Order Approved — Receipt Ready',
            'message' => "{$order->type_label} {$order->order_number} was approved. Receipt {$order->receipt_number} is ready to print.",
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'receipt_number' => $order->receipt_number,
            'type' => $order->type,
            'quantity' => (int) $order->quantity,
            'product_name' => $order->product?->name ?? 'Product',
            'sku' => $order->product?->sku ?? '',
            'suggested_action' => 'Open the receipt to view or print it.',
            'url' => route('receipts.show', $order),
            'icon' => 'bi-receipt',
        ];
    }

    public function toDatabase(object $notifiable): array
    {
        return $this->toArray($notifiable);
    }

    public function toMail(object $notifiable): MailMessage
    {
        $data = $this->toArray($notifiable);

        return (new MailMessage())
            ->subject("JimTrack Order Approved: {$data['order_number']}")
            ->greeting("Hello {$notifiable->name},")
            ->line("Your {$this->order->type_label} {$this->order->order_number} has been approved.")
            ->line("Receipt number: {$this->order->receipt_number}.")
            ->line($data['message'])
            ->action('View / Print Receipt', $data['url'])
            ->line('Thank you for using JimTrack Inventory Tracking.');
    }
}
