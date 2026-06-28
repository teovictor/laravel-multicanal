<?php

namespace App\Application\Products\Actions;

use App\Models\Product;

class DeactivateOutOfStockProducts
{
    public function execute(): int
    {
        return Product::query()
            ->where('stock', 0)
            ->where('is_active', true)
            ->update([
                'is_active' => false,
            ]);
    }
}
