<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventoryTransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product' => $this->whenLoaded('product', fn () => [
                'id' => $this->product?->id,
                'name' => $this->product?->name,
                'sku' => $this->product?->sku,
            ]),
            'type' => $this->type,
            'type_label' => $this->type_label,
            'badge_class' => $this->badge_class,
            'direction' => $this->direction,
            'quantity' => (int) $this->quantity,
            'signed_quantity' => $this->signedQuantity(),
            'balance_after' => $this->balance_after === null ? null : (int) $this->balance_after,
            'reference_number' => $this->reference_number,
            'notes' => $this->notes,
            'user' => $this->whenLoaded('user', fn () => $this->user?->name ?? 'System'),
            'is_reversed' => (bool) $this->is_reversed,
            'created_at' => $this->created_at?->toIso8601String(),
            'created_at_display' => $this->created_at?->format('M d, Y h:i A'),
            'url' => route('transactions.show', $this),
        ];
    }
}
