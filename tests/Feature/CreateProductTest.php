<?php

namespace Tests\Feature;

use App\Application\Products\Actions\CreateProduct;
use App\Application\Products\Data\CreateProductData;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class CreateProductTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_a_product_with_all_data(): void
    {
        $category = Category::factory()->create();

        $product = (new CreateProduct())->execute(new CreateProductData(
            categoryId: $category->id,
            name: 'Mechanical Keyboard',
            description: 'Compact keyboard with brown switches.',
            sku: 'KEY-001',
            priceCents: 24990,
            stock: 15,
        ));

        $this->assertTrue($product->exists);
        $this->assertSame($category->id, $product->category_id);
        $this->assertSame('Mechanical Keyboard', $product->name);
        $this->assertSame('Compact keyboard with brown switches.', $product->description);
        $this->assertSame('KEY-001', $product->sku);
        $this->assertSame(24990, $product->price_cents);
        $this->assertSame(15, $product->stock);
        $this->assertTrue($product->is_active);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'category_id' => $category->id,
            'name' => 'Mechanical Keyboard',
            'description' => 'Compact keyboard with brown switches.',
            'sku' => 'KEY-001',
            'price_cents' => 24990,
            'stock' => 15,
            'is_active' => true,
        ]);
    }

    public function test_it_creates_an_active_product_by_default(): void
    {
        $product = (new CreateProduct())->execute($this->validData());

        $this->assertTrue($product->is_active);
    }

    public function test_it_creates_an_inactive_product(): void
    {
        $product = (new CreateProduct())->execute($this->validData(isActive: false));

        $this->assertFalse($product->is_active);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'is_active' => false,
        ]);
    }

    public function test_it_creates_a_product_without_description(): void
    {
        $product = (new CreateProduct())->execute($this->validData(description: null));

        $this->assertNull($product->description);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'description' => null,
        ]);
    }

    public function test_it_creates_a_product_with_zero_price_cents_and_zero_stock(): void
    {
        $product = (new CreateProduct())->execute($this->validData(
            priceCents: 0,
            stock: 0,
        ));

        $this->assertSame(0, $product->price_cents);
        $this->assertSame(0, $product->stock);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'price_cents' => 0,
            'stock' => 0,
        ]);
    }

    public function test_it_preserves_text_values_without_normalization(): void
    {
        $product = (new CreateProduct())->execute($this->validData(
            name: '  Mixed Case Product  ',
            description: '  Description with intentional spacing.  ',
            sku: '  SKU-Mixed-001  ',
        ));

        $this->assertSame('  Mixed Case Product  ', $product->name);
        $this->assertSame('  Description with intentional spacing.  ', $product->description);
        $this->assertSame('  SKU-Mixed-001  ', $product->sku);
    }

    public function test_it_rejects_an_empty_name_before_persisting(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Product name is required.');

        try {
            (new CreateProduct())->execute($this->validData(name: ''));
        } finally {
            $this->assertDatabaseCount('products', 0);
        }
    }

    public function test_it_rejects_a_blank_name_before_persisting(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Product name is required.');

        try {
            (new CreateProduct())->execute($this->validData(name: '   '));
        } finally {
            $this->assertDatabaseCount('products', 0);
        }
    }

    public function test_it_rejects_an_empty_sku_before_persisting(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Product SKU is required.');

        try {
            (new CreateProduct())->execute($this->validData(sku: ''));
        } finally {
            $this->assertDatabaseCount('products', 0);
        }
    }

    public function test_it_rejects_a_blank_sku_before_persisting(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Product SKU is required.');

        try {
            (new CreateProduct())->execute($this->validData(sku: '   '));
        } finally {
            $this->assertDatabaseCount('products', 0);
        }
    }

    public function test_it_rejects_negative_price_cents_before_persisting(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Product price cents cannot be negative.');

        try {
            (new CreateProduct())->execute($this->validData(priceCents: -1));
        } finally {
            $this->assertDatabaseCount('products', 0);
        }
    }

    public function test_it_rejects_negative_stock_before_persisting(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Product stock cannot be negative.');

        try {
            (new CreateProduct())->execute($this->validData(stock: -1));
        } finally {
            $this->assertDatabaseCount('products', 0);
        }
    }

    public function test_it_rejects_a_missing_category_before_persisting(): void
    {
        $this->expectException(ModelNotFoundException::class);

        try {
            (new CreateProduct())->execute(new CreateProductData(
                categoryId: 999,
                name: 'Mechanical Keyboard',
                description: 'Compact keyboard with brown switches.',
                sku: 'KEY-001',
                priceCents: 24990,
                stock: 15,
            ));
        } finally {
            $this->assertDatabaseCount('products', 0);
        }
    }

    public function test_duplicate_sku_is_rejected_by_the_database(): void
    {
        $category = Category::factory()->create();

        Product::factory()->create([
            'category_id' => $category->id,
            'sku' => 'DUPLICATE-SKU',
        ]);

        $this->expectException(QueryException::class);

        (new CreateProduct())->execute(new CreateProductData(
            categoryId: $category->id,
            name: 'Another Product',
            description: null,
            sku: 'DUPLICATE-SKU',
            priceCents: 1000,
            stock: 3,
        ));
    }

    private function validData(
        ?int $categoryId = null,
        string $name = 'Mechanical Keyboard',
        ?string $description = 'Compact keyboard with brown switches.',
        string $sku = 'KEY-001',
        int $priceCents = 24990,
        int $stock = 15,
        bool $isActive = true,
    ): CreateProductData {
        return new CreateProductData(
            categoryId: $categoryId ?? Category::factory()->create()->id,
            name: $name,
            description: $description,
            sku: $sku,
            priceCents: $priceCents,
            stock: $stock,
            isActive: $isActive,
        );
    }
}
