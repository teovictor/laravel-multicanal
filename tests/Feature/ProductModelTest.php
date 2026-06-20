<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_can_be_persisted(): void
    {
        $category = Category::factory()->create();

        $product = Product::factory()->create([
            'category_id' => $category->id,
            'name' => 'Mechanical Keyboard',
            'description' => 'Compact keyboard with brown switches.',
            'sku' => 'KEY-001',
            'price_cents' => 24990,
            'stock' => 15,
            'is_active' => true,
        ]);

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

    public function test_product_belongs_to_a_category(): void
    {
        $category = Category::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id]);

        $this->assertTrue($product->category->is($category));
    }

    public function test_category_has_many_products(): void
    {
        $category = Category::factory()->create();
        $products = Product::factory()->count(2)->create(['category_id' => $category->id]);

        $this->assertCount(2, $category->products);
        $this->assertTrue($category->products->contains($products[0]));
        $this->assertTrue($category->products->contains($products[1]));
    }

    public function test_factory_creates_a_category_automatically(): void
    {
        $product = Product::factory()->create();

        $this->assertNotNull($product->category_id);
        $this->assertDatabaseHas('categories', [
            'id' => $product->category_id,
        ]);
    }

    public function test_duplicate_sku_is_rejected_by_the_database(): void
    {
        Product::factory()->create(['sku' => 'DUPLICATE-SKU']);

        $this->expectException(QueryException::class);

        Product::factory()->create(['sku' => 'DUPLICATE-SKU']);
    }

    public function test_negative_price_cents_is_rejected_by_the_database(): void
    {
        $this->expectException(QueryException::class);

        try {
            Product::factory()->create(['price_cents' => -1]);
        } finally {
            $this->assertDatabaseMissing('products', [
                'price_cents' => -1,
            ]);
        }
    }

    public function test_negative_stock_is_rejected_by_the_database(): void
    {
        $this->expectException(QueryException::class);

        try {
            Product::factory()->create(['stock' => -1]);
        } finally {
            $this->assertDatabaseMissing('products', [
                'stock' => -1,
            ]);
        }
    }

    public function test_zero_price_cents_and_zero_stock_are_allowed(): void
    {
        $product = Product::factory()->create([
            'price_cents' => 0,
            'stock' => 0,
        ]);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'price_cents' => 0,
            'stock' => 0,
        ]);
    }

    public function test_product_is_active_by_default_when_created_without_explicit_status(): void
    {
        $product = Product::create([
            'category_id' => Category::factory()->create()->id,
            'name' => 'Notebook Stand',
            'description' => null,
            'sku' => 'STAND-001',
            'price_cents' => 8990,
            'stock' => 7,
        ]);

        $this->assertTrue($product->refresh()->is_active);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'is_active' => true,
        ]);
    }

    public function test_product_fillable_attributes_can_be_mass_assigned(): void
    {
        $category = Category::factory()->create();

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'USB-C Hub',
            'description' => 'Seven port adapter.',
            'sku' => 'HUB-001',
            'price_cents' => 12999,
            'stock' => 11,
            'is_active' => false,
        ]);

        $this->assertSame($category->id, $product->category_id);
        $this->assertSame('USB-C Hub', $product->name);
        $this->assertSame('Seven port adapter.', $product->description);
        $this->assertSame('HUB-001', $product->sku);
        $this->assertSame(12999, $product->price_cents);
        $this->assertSame(11, $product->stock);
        $this->assertFalse($product->is_active);
    }

    public function test_price_cents_stock_and_is_active_are_cast_correctly(): void
    {
        $product = Product::factory()->create([
            'price_cents' => '24990',
            'stock' => '8',
            'is_active' => 0,
        ]);

        $product->refresh();

        $this->assertIsInt($product->price_cents);
        $this->assertSame(24990, $product->price_cents);
        $this->assertIsInt($product->stock);
        $this->assertSame(8, $product->stock);
        $this->assertIsBool($product->is_active);
        $this->assertFalse($product->is_active);
    }

    public function test_category_with_products_cannot_be_deleted(): void
    {
        $category = Category::factory()->create();
        Product::factory()->create(['category_id' => $category->id]);

        $this->expectException(QueryException::class);

        $category->delete();
    }

    public function test_inactive_factory_state_works(): void
    {
        $product = Product::factory()->inactive()->create();

        $this->assertFalse($product->is_active);
    }

    public function test_out_of_stock_factory_state_works(): void
    {
        $product = Product::factory()->outOfStock()->create();

        $this->assertSame(0, $product->stock);
    }
}
