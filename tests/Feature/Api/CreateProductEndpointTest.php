<?php

namespace Tests\Feature\Api;

use App\Application\Products\Actions\CreateProduct;
use App\Application\Products\Data\CreateProductData;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class CreateProductEndpointTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_a_product_with_all_fields(): void
    {
        $category = Category::factory()->create();

        $response = $this->postJson('/api/products', [
            'category_id' => $category->id,
            'name' => 'Mechanical Keyboard',
            'description' => 'Compact keyboard with brown switches.',
            'sku' => 'KEY-001',
            'price_cents' => 24990,
            'stock' => 15,
            'is_active' => true,
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.category_id', $category->id)
            ->assertJsonPath('data.name', 'Mechanical Keyboard')
            ->assertJsonPath('data.description', 'Compact keyboard with brown switches.')
            ->assertJsonPath('data.sku', 'KEY-001')
            ->assertJsonPath('data.price_cents', 24990)
            ->assertJsonPath('data.stock', 15)
            ->assertJsonPath('data.is_active', true)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'category_id',
                    'name',
                    'description',
                    'sku',
                    'price_cents',
                    'stock',
                    'is_active',
                    'created_at',
                    'updated_at',
                ],
            ]);

        $this->assertIsInt($response->json('data.price_cents'));
        $this->assertIsInt($response->json('data.stock'));
        $this->assertIsBool($response->json('data.is_active'));

        $this->assertDatabaseHas('products', [
            'category_id' => $category->id,
            'name' => 'Mechanical Keyboard',
            'description' => 'Compact keyboard with brown switches.',
            'sku' => 'KEY-001',
            'price_cents' => 24990,
            'stock' => 15,
            'is_active' => true,
        ]);
    }

    public function test_it_creates_an_active_product_when_status_is_not_sent(): void
    {
        $category = Category::factory()->create();

        $response = $this->postJson('/api/products', $this->validPayload($category));

        $response
            ->assertCreated()
            ->assertJsonPath('data.is_active', true);

        $this->assertDatabaseHas('products', [
            'category_id' => $category->id,
            'is_active' => true,
        ]);
    }

    public function test_it_creates_an_inactive_product(): void
    {
        $category = Category::factory()->create();

        $response = $this->postJson('/api/products', $this->validPayload($category, [
            'is_active' => false,
        ]));

        $response
            ->assertCreated()
            ->assertJsonPath('data.is_active', false);

        $this->assertIsBool($response->json('data.is_active'));

        $this->assertDatabaseHas('products', [
            'category_id' => $category->id,
            'is_active' => false,
        ]);
    }

    public function test_it_creates_a_product_without_description(): void
    {
        $category = Category::factory()->create();

        $response = $this->postJson('/api/products', $this->validPayload($category, [
            'description' => null,
        ]));

        $response
            ->assertCreated()
            ->assertJsonPath('data.description', null);

        $this->assertDatabaseHas('products', [
            'category_id' => $category->id,
            'description' => null,
        ]);
    }

    public function test_it_accepts_zero_price_cents_and_zero_stock(): void
    {
        $category = Category::factory()->create();

        $response = $this->postJson('/api/products', $this->validPayload($category, [
            'price_cents' => 0,
            'stock' => 0,
        ]));

        $response
            ->assertCreated()
            ->assertJsonPath('data.price_cents', 0)
            ->assertJsonPath('data.stock', 0);

        $this->assertDatabaseHas('products', [
            'category_id' => $category->id,
            'price_cents' => 0,
            'stock' => 0,
        ]);
    }

    public function test_it_returns_validation_error_for_missing_category(): void
    {
        $response = $this->postJson('/api/products', [
            'category_id' => 999,
            'name' => 'Mechanical Keyboard',
            'description' => 'Compact keyboard with brown switches.',
            'sku' => 'KEY-001',
            'price_cents' => 24990,
            'stock' => 15,
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['category_id']);

        $this->assertDatabaseCount('products', 0);
    }

    public function test_it_rejects_invalid_names(): void
    {
        $category = Category::factory()->create();

        foreach ([null, '', str_repeat('a', 256)] as $name) {
            $payload = $this->validPayload($category);

            if ($name === null) {
                unset($payload['name']);
            } else {
                $payload['name'] = $name;
            }

            $response = $this->postJson('/api/products', $payload);

            $response
                ->assertUnprocessable()
                ->assertJsonValidationErrors(['name']);
        }

        $this->assertDatabaseCount('products', 0);
    }

    public function test_it_rejects_blank_name_before_persisting(): void
    {
        $category = Category::factory()->create();

        $response = $this->postJson('/api/products', $this->validPayload($category, [
            'name' => '   ',
        ]));

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);

        $this->assertDatabaseCount('products', 0);
    }

    public function test_it_rejects_invalid_skus(): void
    {
        $category = Category::factory()->create();

        foreach ([null, '', str_repeat('a', 256)] as $sku) {
            $payload = $this->validPayload($category);

            if ($sku === null) {
                unset($payload['sku']);
            } else {
                $payload['sku'] = $sku;
            }

            $response = $this->postJson('/api/products', $payload);

            $response
                ->assertUnprocessable()
                ->assertJsonValidationErrors(['sku']);
        }

        $this->assertDatabaseCount('products', 0);
    }

    public function test_it_rejects_blank_sku_before_persisting(): void
    {
        $category = Category::factory()->create();

        $response = $this->postJson('/api/products', $this->validPayload($category, [
            'sku' => '   ',
        ]));

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['sku']);

        $this->assertDatabaseCount('products', 0);
    }

    public function test_it_rejects_invalid_price_cents(): void
    {
        $category = Category::factory()->create();

        foreach ([null, '24990', -1] as $priceCents) {
            $payload = $this->validPayload($category);

            if ($priceCents === null) {
                unset($payload['price_cents']);
            } else {
                $payload['price_cents'] = $priceCents;
            }

            $response = $this->postJson('/api/products', $payload);

            $response
                ->assertUnprocessable()
                ->assertJsonValidationErrors(['price_cents']);
        }

        $this->assertDatabaseCount('products', 0);
    }

    public function test_it_rejects_invalid_stock(): void
    {
        $category = Category::factory()->create();

        foreach ([null, '15', -1] as $stock) {
            $payload = $this->validPayload($category);

            if ($stock === null) {
                unset($payload['stock']);
            } else {
                $payload['stock'] = $stock;
            }

            $response = $this->postJson('/api/products', $payload);

            $response
                ->assertUnprocessable()
                ->assertJsonValidationErrors(['stock']);
        }

        $this->assertDatabaseCount('products', 0);
    }

    public function test_it_rejects_invalid_is_active(): void
    {
        $category = Category::factory()->create();

        $response = $this->postJson('/api/products', $this->validPayload($category, [
            'is_active' => 'invalid',
        ]));

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['is_active']);

        $this->assertDatabaseCount('products', 0);
    }

    public function test_it_rejects_invalid_description(): void
    {
        $category = Category::factory()->create();

        $response = $this->postJson('/api/products', $this->validPayload($category, [
            'description' => ['invalid'],
        ]));

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['description']);

        $this->assertDatabaseCount('products', 0);
    }

    public function test_it_rejects_price_as_a_substitute_for_price_cents(): void
    {
        $category = Category::factory()->create();
        $payload = $this->validPayload($category, [
            'price' => '249.90',
        ]);
        unset($payload['price_cents']);

        $response = $this->postJson('/api/products', $payload);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['price_cents', 'price']);

        $this->assertDatabaseCount('products', 0);
    }

    public function test_it_returns_validation_error_for_duplicate_sku(): void
    {
        $category = Category::factory()->create();

        Product::factory()->create([
            'category_id' => $category->id,
            'sku' => 'DUPLICATE-SKU',
        ]);

        $response = $this->postJson('/api/products', $this->validPayload($category, [
            'sku' => 'DUPLICATE-SKU',
        ]));

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['sku']);

        $this->assertDatabaseCount('products', 1);
        $this->assertDatabaseHas('products', [
            'category_id' => $category->id,
            'sku' => 'DUPLICATE-SKU',
        ]);
    }

    public function test_it_uses_the_shared_create_product_action(): void
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

        $this->mock(CreateProduct::class, function ($mock) use ($category, $product) {
            $mock->shouldReceive('execute')
                ->once()
                ->with(Mockery::on(fn (CreateProductData $data): bool => $data->categoryId === $category->id
                    && $data->name === 'Mechanical Keyboard'
                    && $data->description === 'Compact keyboard with brown switches.'
                    && $data->sku === 'KEY-001'
                    && $data->priceCents === 24990
                    && $data->stock === 15
                    && $data->isActive === true))
                ->andReturn($product);
        });

        $response = $this->postJson('/api/products', $this->validPayload($category));

        $response
            ->assertCreated()
            ->assertJsonPath('data.id', $product->id);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validPayload(Category $category, array $overrides = []): array
    {
        return array_merge([
            'category_id' => $category->id,
            'name' => 'Mechanical Keyboard',
            'description' => 'Compact keyboard with brown switches.',
            'sku' => 'KEY-001',
            'price_cents' => 24990,
            'stock' => 15,
        ], $overrides);
    }
}
