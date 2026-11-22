<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductStockResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'sku' => $this->sku,
            'quantity' => (int) $this->quantity,
            'reorder_level' => (int) $this->reorder_level,
            'status' => $this->stock_status,
            'status_color' => $this->stock_status_color,
            'unit_price' => (float) $this->unit_price,
            'stock_value' => round((float) $this->stock_value, 2),
            'category' => $this->whenLoaded('category', fn () => $this->category?->name),
            'brand' => $this->whenLoaded('brand', fn () => $this->brand?->name),
            'url' => route('products.show', $this),
        ];
    }
}
