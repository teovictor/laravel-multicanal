<?php

namespace App\Application\Products\Data;

final readonly class CreateProductData
{
    public function __construct(
        public int $categoryId,
        public string $name,
        public ?string $description,
        public string $sku,
        public int $priceCents,
        public int $stock,
        public bool $isActive = true,
    ) {
    }
}
