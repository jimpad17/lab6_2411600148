<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventoryStatResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'total_products' => (int) $this['total_products'],
            'in_stock' => (int) $this['in_stock'],
            'low_stock' => (int) $this['low_stock'],
            'out_of_stock' => (int) $this['out_of_stock'],
            'inventory_value' => round((float) $this['inventory_value'], 2),
            'formatted_inventory_value' => '₱'.number_format((float) $this['inventory_value'], 2),
            'total_movements' => (int) $this['total_movements'],
            'movements_today' => (int) $this['movements_today'],
            'generated_at' => $this['generated_at']->toIso8601String(),
        ];
    }
}
