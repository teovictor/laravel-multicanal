<?php

namespace App\Jobs;

use App\Application\Products\Actions\CreateProduct;
use App\Application\Products\Data\CreateProductData;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class CreateProductJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly int $categoryId,
        public readonly string $name,
        public readonly ?string $description,
        public readonly string $sku,
        public readonly int $priceCents,
        public readonly int $stock,
        public readonly bool $isActive = true,
    ) {}

    public function handle(CreateProduct $createProduct): void
    {
        $createProduct->execute(new CreateProductData(
            categoryId: $this->categoryId,
            name: $this->name,
            description: $this->description,
            sku: $this->sku,
            priceCents: $this->priceCents,
            stock: $this->stock,
            isActive: $this->isActive,
        ));
    }
}
