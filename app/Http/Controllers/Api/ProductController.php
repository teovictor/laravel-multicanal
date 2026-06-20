<?php

namespace App\Http\Controllers\Api;

use App\Application\Products\Actions\CreateProduct;
use App\Application\Products\Data\CreateProductData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreProductRequest;
use App\Http\Resources\ProductResource;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class ProductController extends Controller
{
    public function store(StoreProductRequest $request, CreateProduct $createProduct): JsonResponse
    {
        $validated = $request->validated();

        try {
            $product = $createProduct->execute(new CreateProductData(
                categoryId: $validated['category_id'],
                name: $validated['name'],
                description: $validated['description'] ?? null,
                sku: $validated['sku'],
                priceCents: $validated['price_cents'],
                stock: $validated['stock'],
                isActive: $validated['is_active'] ?? true,
            ));
        } catch (InvalidArgumentException $exception) {
            throw ValidationException::withMessages([
                $this->validationFieldFor($exception) => [$exception->getMessage()],
            ]);
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages([
                'sku' => ['The SKU has already been taken.'],
            ]);
        }

        return ProductResource::make($product)
            ->response()
            ->setStatusCode(201);
    }

    private function validationFieldFor(InvalidArgumentException $exception): string
    {
        return match ($exception->getMessage()) {
            'Product SKU is required.' => 'sku',
            'Product price cents cannot be negative.' => 'price_cents',
            'Product stock cannot be negative.' => 'stock',
            default => 'name',
        };
    }
}
