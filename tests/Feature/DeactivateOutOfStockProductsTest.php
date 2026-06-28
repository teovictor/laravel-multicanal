<?php

namespace Tests\Feature;

use App\Application\Products\Actions\DeactivateOutOfStockProducts;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeactivateOutOfStockProductsTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_deactivates_active_out_of_stock_products(): void
    {
        $product = Product::factory()->outOfStock()->create([
            'is_active' => true,
        ]);

        (new DeactivateOutOfStockProducts)->execute();

        $this->assertFalse($product->refresh()->is_active);
    }

    public function test_it_keeps_active_products_with_stock_active(): void
    {
        $product = Product::factory()->create([
            'stock' => 5,
            'is_active' => true,
        ]);

        (new DeactivateOutOfStockProducts)->execute();

        $this->assertTrue($product->refresh()->is_active);
    }

    public function test_it_keeps_inactive_products_inactive(): void
    {
        $product = Product::factory()->inactive()->outOfStock()->create();

        (new DeactivateOutOfStockProducts)->execute();

        $this->assertFalse($product->refresh()->is_active);
    }

    public function test_it_returns_the_number_of_deactivated_products(): void
    {
        Product::factory()->count(2)->outOfStock()->create([
            'is_active' => true,
        ]);

        Product::factory()->create([
            'stock' => 5,
            'is_active' => true,
        ]);

        Product::factory()->inactive()->outOfStock()->create();

        $deactivatedProducts = (new DeactivateOutOfStockProducts)->execute();

        $this->assertSame(2, $deactivatedProducts);
    }
}
