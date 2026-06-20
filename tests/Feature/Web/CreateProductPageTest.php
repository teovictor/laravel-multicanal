<?php

namespace Tests\Feature\Web;

use App\Application\Products\Actions\CreateProduct;
use App\Application\Products\Data\CreateProductData;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class CreateProductPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_product_page_responds_successfully(): void
    {
        $this->get(route('products.create'))
            ->assertOk()
            ->assertSee('Criar produto');
    }

    public function test_create_product_form_contains_fields_and_csrf_protection(): void
    {
        $this->get(route('products.create'))
            ->assertOk()
            ->assertSee('method="POST"', false)
            ->assertSee('action="'.route('products.store').'"', false)
            ->assertSee('name="_token"', false)
            ->assertSee('name="category_id"', false)
            ->assertSee('name="name"', false)
            ->assertSee('name="description"', false)
            ->assertSee('name="sku"', false)
            ->assertSee('name="price"', false)
            ->assertSee('type="number"', false)
            ->assertSee('step="0.01"', false)
            ->assertSee('min="0"', false)
            ->assertSee('name="stock"', false)
            ->assertSee('name="is_active"', false)
            ->assertSee('type="hidden"', false)
            ->assertSee('type="checkbox"', false)
            ->assertSee('checked', false);
    }

    public function test_create_product_form_lists_categories_in_alphabetical_order(): void
    {
        Category::factory()->create(['name' => 'Zebra']);
        Category::factory()->create(['name' => 'Alpha']);
        Category::factory()->create(['name' => 'Middle']);

        $this->get(route('products.create'))
            ->assertOk()
            ->assertSeeInOrder(['Alpha', 'Middle', 'Zebra']);
    }

    public function test_it_creates_a_product_with_all_data(): void
    {
        $category = Category::factory()->create();

        $response = $this->post(route('products.store'), $this->validPayload($category));

        $response
            ->assertRedirect(route('products.create'))
            ->assertSessionHas('status', 'Produto criado com sucesso.');

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

    public function test_it_converts_249_90_to_24990_exactly(): void
    {
        $category = Category::factory()->create();

        $this->post(route('products.store'), $this->validPayload($category, [
            'price' => '249.90',
        ]));

        $this->assertDatabaseHas('products', [
            'category_id' => $category->id,
            'sku' => 'KEY-001',
            'price_cents' => 24990,
        ]);
    }

    public function test_it_converts_19_9_to_1990_exactly(): void
    {
        $category = Category::factory()->create();

        $this->post(route('products.store'), $this->validPayload($category, [
            'price' => '19.9',
        ]));

        $this->assertDatabaseHas('products', [
            'category_id' => $category->id,
            'sku' => 'KEY-001',
            'price_cents' => 1990,
        ]);
    }

    public function test_it_accepts_zero_price_and_zero_stock(): void
    {
        $category = Category::factory()->create();

        $this->post(route('products.store'), $this->validPayload($category, [
            'price' => '0',
            'stock' => '0',
        ]));

        $this->assertDatabaseHas('products', [
            'category_id' => $category->id,
            'price_cents' => 0,
            'stock' => 0,
        ]);
    }

    public function test_it_creates_an_active_product(): void
    {
        $category = Category::factory()->create();

        $this->post(route('products.store'), $this->validPayload($category, [
            'is_active' => '1',
        ]));

        $this->assertDatabaseHas('products', [
            'category_id' => $category->id,
            'sku' => 'KEY-001',
            'is_active' => true,
        ]);
    }

    public function test_it_creates_an_inactive_product(): void
    {
        $category = Category::factory()->create();

        $this->post(route('products.store'), $this->validPayload($category, [
            'is_active' => '0',
        ]));

        $this->assertDatabaseHas('products', [
            'category_id' => $category->id,
            'sku' => 'KEY-001',
            'is_active' => false,
        ]);
    }

    public function test_it_creates_a_product_without_description(): void
    {
        $category = Category::factory()->create();

        $this->post(route('products.store'), $this->validPayload($category, [
            'description' => null,
        ]));

        $this->assertDatabaseHas('products', [
            'category_id' => $category->id,
            'sku' => 'KEY-001',
            'description' => null,
        ]);
    }

    public function test_it_rejects_a_missing_category(): void
    {
        $response = $this->from(route('products.create'))->post(route('products.store'), [
            'category_id' => 999,
            'name' => 'Mechanical Keyboard',
            'description' => 'Compact keyboard with brown switches.',
            'sku' => 'KEY-001',
            'price' => '249.90',
            'stock' => '15',
            'is_active' => '1',
        ]);

        $response
            ->assertRedirect(route('products.create'))
            ->assertSessionHasErrors(['category_id']);

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

            $response = $this->from(route('products.create'))->post(route('products.store'), $payload);

            $response
                ->assertRedirect(route('products.create'))
                ->assertSessionHasErrors(['name']);
        }

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

            $response = $this->from(route('products.create'))->post(route('products.store'), $payload);

            $response
                ->assertRedirect(route('products.create'))
                ->assertSessionHasErrors(['sku']);
        }

        $this->assertDatabaseCount('products', 0);
    }

    public function test_it_rejects_invalid_prices(): void
    {
        $category = Category::factory()->create();

        foreach ([null, '-1', 'invalid', '19.999'] as $price) {
            $payload = $this->validPayload($category);

            if ($price === null) {
                unset($payload['price']);
            } else {
                $payload['price'] = $price;
            }

            $response = $this->from(route('products.create'))->post(route('products.store'), $payload);

            $response
                ->assertRedirect(route('products.create'))
                ->assertSessionHasErrors(['price']);
        }

        $this->assertDatabaseCount('products', 0);
    }

    public function test_it_rejects_invalid_stock(): void
    {
        $category = Category::factory()->create();

        foreach ([null, '1.5', '-1'] as $stock) {
            $payload = $this->validPayload($category);

            if ($stock === null) {
                unset($payload['stock']);
            } else {
                $payload['stock'] = $stock;
            }

            $response = $this->from(route('products.create'))->post(route('products.store'), $payload);

            $response
                ->assertRedirect(route('products.create'))
                ->assertSessionHasErrors(['stock']);
        }

        $this->assertDatabaseCount('products', 0);
    }

    public function test_duplicate_sku_returns_to_form_with_sku_error(): void
    {
        $category = Category::factory()->create();

        Product::factory()->create([
            'category_id' => $category->id,
            'sku' => 'DUPLICATE-SKU',
        ]);

        $response = $this->from(route('products.create'))->post(route('products.store'), $this->validPayload($category, [
            'sku' => 'DUPLICATE-SKU',
        ]));

        $response
            ->assertRedirect(route('products.create'))
            ->assertSessionHasErrors(['sku']);

        $this->assertDatabaseCount('products', 1);
    }

    public function test_invalid_data_returns_to_form_with_old_input_and_errors(): void
    {
        $category = Category::factory()->create();

        $response = $this->from(route('products.create'))->post(route('products.store'), [
            'category_id' => $category->id,
            'name' => '',
            'description' => 'Invalid product.',
            'sku' => '',
            'price' => '19.999',
            'stock' => '-1',
            'is_active' => '1',
        ]);

        $response
            ->assertRedirect(route('products.create'))
            ->assertSessionHasErrors(['name', 'sku', 'price', 'stock'])
            ->assertSessionHasInput('category_id', (string) $category->id)
            ->assertSessionHasInput('description', 'Invalid product.')
            ->assertSessionHasInput('price', '19.999')
            ->assertSessionHasInput('stock', '-1');

        $this->followingRedirects()
            ->from(route('products.create'))
            ->post(route('products.store'), [
                'category_id' => $category->id,
                'name' => '',
                'description' => 'Invalid product.',
                'sku' => '',
                'price' => '19.999',
                'stock' => '-1',
                'is_active' => '1',
            ])
            ->assertSee('Revise os campos destacados e tente novamente.')
            ->assertSee('Invalid product.')
            ->assertSee('19.999')
            ->assertSee('-1');
    }

    public function test_no_product_is_persisted_when_validation_fails(): void
    {
        $category = Category::factory()->create();

        $this->from(route('products.create'))->post(route('products.store'), $this->validPayload($category, [
            'price' => 'invalid',
        ]));

        $this->assertDatabaseCount('products', 0);
    }

    public function test_controller_uses_the_shared_create_product_action(): void
    {
        $category = Category::factory()->create();
        $product = Product::factory()->make([
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

        $this->post(route('products.store'), $this->validPayload($category))
            ->assertRedirect(route('products.create'))
            ->assertSessionHas('status', 'Produto criado com sucesso.');
    }

    public function test_price_cents_is_not_accepted_as_a_substitute_for_price(): void
    {
        $category = Category::factory()->create();
        $payload = $this->validPayload($category, [
            'price_cents' => '24990',
        ]);
        unset($payload['price']);

        $response = $this->from(route('products.create'))->post(route('products.store'), $payload);

        $response
            ->assertRedirect(route('products.create'))
            ->assertSessionHasErrors(['price', 'price_cents']);

        $this->assertDatabaseCount('products', 0);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validPayload(Category $category, array $overrides = []): array
    {
        return array_merge([
            'category_id' => (string) $category->id,
            'name' => 'Mechanical Keyboard',
            'description' => 'Compact keyboard with brown switches.',
            'sku' => 'KEY-001',
            'price' => '249.90',
            'stock' => '15',
            'is_active' => '1',
        ], $overrides);
    }
}
