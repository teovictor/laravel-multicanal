<?php

namespace App\Http\Controllers\Web;

use App\Application\Products\Actions\CreateProduct;
use App\Application\Products\Data\CreateProductData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\StoreProductRequest;
use App\Models\Category;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use InvalidArgumentException;

class ProductController extends Controller
{
    public function create(): View
    {
        return view('products.create', [
            'categories' => Category::query()
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }

    public function store(StoreProductRequest $request, CreateProduct $createProduct): RedirectResponse
    {
        $validated = $request->validated();

        try {
            $createProduct->execute(new CreateProductData(
                categoryId: $validated['category_id'],
                name: $validated['name'],
                description: $validated['description'] ?? null,
                sku: $validated['sku'],
                priceCents: $request->priceCents(),
                stock: $validated['stock'],
                isActive: $request->boolean('is_active'),
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

        return redirect()
            ->route('products.create')
            ->with('status', 'Produto criado com sucesso.');
    }

    private function validationFieldFor(InvalidArgumentException $exception): string
    {
        return match ($exception->getMessage()) {
            'Product SKU is required.' => 'sku',
            'Product price cents cannot be negative.' => 'price',
            'Product stock cannot be negative.' => 'stock',
            default => 'name',
        };
    }
}
