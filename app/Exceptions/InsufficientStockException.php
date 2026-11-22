<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when a stock-out / negative adjustment would drive a product below zero.
 * Throwing inside DB::transaction() guarantees the whole movement is rolled back.
 */
class InsufficientStockException extends RuntimeException
{
    public static function forProduct(string $productName, int $available, int $requested): self
    {
        return new self(
            "Not enough stock. Only {$available} pcs of '{$productName}' are on hand (requested: {$requested})."
        );
    }
}
