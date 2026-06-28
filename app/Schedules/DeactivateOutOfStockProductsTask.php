<?php

namespace App\Schedules;

use App\Application\Products\Actions\DeactivateOutOfStockProducts;

class DeactivateOutOfStockProductsTask
{
    public function __construct(
        private readonly DeactivateOutOfStockProducts $deactivateOutOfStockProducts,
    ) {}

    public function __invoke(): int
    {
        return $this->deactivateOutOfStockProducts->execute();
    }
}
