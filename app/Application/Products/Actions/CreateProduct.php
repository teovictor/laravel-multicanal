<?php

namespace App\Application\Products\Actions;

use App\Application\Products\Data\CreateProductData;
use App\Models\Category;
use App\Models\Product;
use InvalidArgumentException;

class CreateProduct
{
    public function execute(CreateProductData $data): Product
    {
        if (trim($data->name) === '') {
            throw new InvalidArgumentException('Product name is required.');
        }

        if (trim($data->sku) === '') {
            throw new InvalidArgumentException('Product SKU is required.');
        }

        if ($data->priceCents < 0) {
            throw new InvalidArgumentException('Product price cents cannot be negative.');
        }

        if ($data->stock < 0) {
            throw new InvalidArgumentException('Product stock cannot be negative.');
        }

        Category::query()->findOrFail($data->categoryId);

        return Product::create([
            'category_id' => $data->categoryId,
            'name' => $data->name,
            'description' => $data->description,
            'sku' => $data->sku,
            'price_cents' => $data->priceCents,
            'stock' => $data->stock,
            'is_active' => $data->isActive,
        ]);
    }
}
