<?php

namespace Tests\Feature\Console;

use App\Application\Products\Actions\CreateProduct;
use App\Application\Products\Data\CreateProductData;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class CreateProductCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_an_active_product_from_options(): void
    {
        $category = Category::factory()->create();

        $this->artisan('product:create', $this->validOptions($category))
            ->expectsConfirmation('Create as active?', 'yes')
            ->expectsOutput('Product created with ID 1: Mechanical Keyboard')
            ->assertSuccessful();

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

    public function test_it_creates_an_inactive_product_from_flag(): void
    {
        $category = Category::factory()->create();

        $this->artisan('product:create', $this->validOptions($category, [
            '--description' => null,
            '--inactive' => true,
        ]))
            ->expectsQuestion('Product description', '')
            ->expectsOutput('Product created with ID 1: Mechanical Keyboard')
            ->assertSuccessful();

        $this->assertDatabaseHas('products', [
            'category_id' => $category->id,
            'sku' => 'KEY-001',
            'description' => null,
            'is_active' => false,
        ]);
    }

    public function test_it_creates_a_product_without_description(): void
    {
        $category = Category::factory()->create();

        $this->artisan('product:create', $this->validOptions($category, [
            '--description' => null,
        ]))
            ->expectsQuestion('Product description', '')
            ->expectsConfirmation('Create as active?', 'yes')
            ->expectsOutput('Product created with ID 1: Mechanical Keyboard')
            ->assertSuccessful();

        $this->assertDatabaseHas('products', [
            'category_id' => $category->id,
            'sku' => 'KEY-001',
            'description' => null,
        ]);
    }

    public function test_it_accepts_zero_price_and_zero_stock(): void
    {
        $category = Category::factory()->create();

        $this->artisan('product:create', $this->validOptions($category, [
            '--price' => '0',
            '--stock' => '0',
        ]))
            ->expectsConfirmation('Create as active?', 'yes')
            ->expectsOutput('Product created with ID 1: Mechanical Keyboard')
            ->assertSuccessful();

        $this->assertDatabaseHas('products', [
            'category_id' => $category->id,
            'price_cents' => 0,
            'stock' => 0,
        ]);
    }

    public function test_it_converts_249_90_to_24990_exactly(): void
    {
        $category = Category::factory()->create();

        $this->artisan('product:create', $this->validOptions($category, [
            '--price' => '249.90',
        ]))
            ->expectsConfirmation('Create as active?', 'yes')
            ->expectsOutput('Product created with ID 1: Mechanical Keyboard')
            ->assertSuccessful();

        $this->assertDatabaseHas('products', [
            'category_id' => $category->id,
            'sku' => 'KEY-001',
            'price_cents' => 24990,
        ]);
    }

    public function test_it_converts_19_9_to_1990_exactly(): void
    {
        $category = Category::factory()->create();

        $this->artisan('product:create', $this->validOptions($category, [
            '--price' => '19.9',
        ]))
            ->expectsConfirmation('Create as active?', 'yes')
            ->expectsOutput('Product created with ID 1: Mechanical Keyboard')
            ->assertSuccessful();

        $this->assertDatabaseHas('products', [
            'category_id' => $category->id,
            'sku' => 'KEY-001',
            'price_cents' => 1990,
        ]);
    }

    public function test_it_accepts_interactive_input(): void
    {
        $category = Category::factory()->create();

        $this->artisan('product:create')
            ->expectsChoice('Select the product category', (string) $category->id, [
                $category->id => "[{$category->id}] {$category->name}",
            ], strict: true)
            ->expectsQuestion('Product name', 'Mechanical Keyboard')
            ->expectsQuestion('Product SKU', 'KEY-001')
            ->expectsQuestion('Product price', '249.90')
            ->expectsQuestion('Product stock', '15')
            ->expectsQuestion('Product description', 'Compact keyboard with brown switches.')
            ->expectsConfirmation('Create as active?', 'yes')
            ->expectsOutput('Product created with ID 1: Mechanical Keyboard')
            ->assertSuccessful();

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

    public function test_interactive_input_without_category_shows_category_selector_with_id_and_name_options_in_alphabetical_order(): void
    {
        $zebra = Category::factory()->create(['name' => 'Zebra']);
        $alpha = Category::factory()->create(['name' => 'Alpha']);
        $middle = Category::factory()->create(['name' => 'Middle']);

        $this->artisan('product:create', [
            '--name' => 'Mechanical Keyboard',
            '--description' => 'Compact keyboard with brown switches.',
            '--sku' => 'KEY-001',
            '--price' => '249.90',
            '--stock' => '15',
        ])
            ->expectsChoice('Select the product category', (string) $middle->id, [
                $alpha->id => "[{$alpha->id}] Alpha",
                $middle->id => "[{$middle->id}] Middle",
                $zebra->id => "[{$zebra->id}] Zebra",
            ], strict: true)
            ->expectsConfirmation('Create as active?', 'yes')
            ->expectsOutput('Product created with ID 1: Mechanical Keyboard')
            ->assertSuccessful();

        $this->assertDatabaseHas('products', [
            'category_id' => $middle->id,
            'sku' => 'KEY-001',
        ]);
    }

    public function test_interactive_category_selector_sends_selected_id_to_create_product_data(): void
    {
        $first = Category::factory()->create(['name' => 'Alpha']);
        $second = Category::factory()->create(['name' => 'Beta']);
        $product = Product::factory()->make([
            'id' => 123,
            'category_id' => $second->id,
            'name' => 'Mechanical Keyboard',
            'description' => 'Compact keyboard with brown switches.',
            'sku' => 'KEY-001',
            'price_cents' => 24990,
            'stock' => 15,
            'is_active' => true,
        ]);

        $this->mock(CreateProduct::class, function ($mock) use ($second, $product) {
            $mock->shouldReceive('execute')
                ->once()
                ->with(Mockery::on(fn (CreateProductData $data): bool => $data->categoryId === $second->id))
                ->andReturn($product);
        });

        $this->artisan('product:create', [
            '--name' => 'Mechanical Keyboard',
            '--description' => 'Compact keyboard with brown switches.',
            '--sku' => 'KEY-001',
            '--price' => '249.90',
            '--stock' => '15',
        ])
            ->expectsChoice('Select the product category', (string) $second->id, [
                $first->id => "[{$first->id}] Alpha",
                $second->id => "[{$second->id}] Beta",
            ], strict: true)
            ->expectsConfirmation('Create as active?', 'yes')
            ->expectsOutput('Product created with ID 123: Mechanical Keyboard')
            ->assertSuccessful();

        $this->assertDatabaseHas('categories', [
            'id' => $first->id,
        ]);
    }

    public function test_interactive_input_with_inactive_flag_does_not_ask_for_status(): void
    {
        $category = Category::factory()->create();

        $this->artisan('product:create --inactive')
            ->expectsChoice('Select the product category', (string) $category->id, [
                $category->id => "[{$category->id}] {$category->name}",
            ], strict: true)
            ->expectsQuestion('Product name', 'Mechanical Keyboard')
            ->expectsQuestion('Product SKU', 'KEY-001')
            ->expectsQuestion('Product price', '249.90')
            ->expectsQuestion('Product stock', '15')
            ->expectsQuestion('Product description', '')
            ->expectsOutput('Product created with ID 1: Mechanical Keyboard')
            ->assertSuccessful();

        $this->assertDatabaseHas('products', [
            'category_id' => $category->id,
            'sku' => 'KEY-001',
            'description' => null,
            'is_active' => false,
        ]);
    }

    public function test_category_option_does_not_open_category_selector(): void
    {
        $category = Category::factory()->create(['name' => 'Alpha']);

        $this->artisan('product:create', $this->validOptions($category))
            ->expectsConfirmation('Create as active?', 'yes')
            ->expectsOutput('Product created with ID 1: Mechanical Keyboard')
            ->assertSuccessful();

        $this->assertDatabaseHas('products', [
            'category_id' => $category->id,
            'sku' => 'KEY-001',
        ]);
    }

    public function test_it_asks_for_description_when_required_options_are_provided_but_description_is_missing(): void
    {
        $category = Category::factory()->create();

        $this->artisan('product:create', $this->validOptions($category, [
            '--description' => null,
        ]))
            ->expectsQuestion('Product description', 'Interactive description.')
            ->expectsConfirmation('Create as active?', 'yes')
            ->expectsOutput('Product created with ID 1: Mechanical Keyboard')
            ->assertSuccessful();

        $this->assertDatabaseHas('products', [
            'category_id' => $category->id,
            'sku' => 'KEY-001',
            'description' => 'Interactive description.',
            'is_active' => true,
        ]);
    }

    public function test_it_asks_for_status_when_required_options_are_provided_without_inactive_flag(): void
    {
        $category = Category::factory()->create();

        $this->artisan('product:create', $this->validOptions($category))
            ->expectsConfirmation('Create as active?', 'no')
            ->expectsOutput('Product created with ID 1: Mechanical Keyboard')
            ->assertSuccessful();

        $this->assertDatabaseHas('products', [
            'category_id' => $category->id,
            'sku' => 'KEY-001',
            'is_active' => false,
        ]);
    }

    public function test_it_does_not_ask_for_status_when_required_options_are_provided_with_inactive_flag(): void
    {
        $category = Category::factory()->create();

        $this->artisan('product:create', $this->validOptions($category, [
            '--inactive' => true,
        ]))
            ->expectsOutput('Product created with ID 1: Mechanical Keyboard')
            ->assertSuccessful();

        $this->assertDatabaseHas('products', [
            'category_id' => $category->id,
            'sku' => 'KEY-001',
            'is_active' => false,
        ]);
    }

    public function test_empty_interactive_description_becomes_null(): void
    {
        $category = Category::factory()->create();

        $this->artisan('product:create', $this->validOptions($category, [
            '--description' => null,
        ]))
            ->expectsQuestion('Product description', '')
            ->expectsConfirmation('Create as active?', 'yes')
            ->expectsOutput('Product created with ID 1: Mechanical Keyboard')
            ->assertSuccessful();

        $this->assertDatabaseHas('products', [
            'category_id' => $category->id,
            'sku' => 'KEY-001',
            'description' => null,
        ]);
    }

    public function test_empty_interactive_name_fails_with_message(): void
    {
        $category = Category::factory()->create();

        $this->artisan('product:create', [
            '--category' => (string) $category->id,
            '--sku' => 'KEY-001',
            '--price' => '249.90',
            '--stock' => '15',
            '--description' => '',
        ])
            ->expectsQuestion('Product name', null)
            ->expectsConfirmation('Create as active?', 'yes')
            ->expectsOutput('Product name is required.')
            ->assertFailed();

        $this->assertDatabaseCount('products', 0);
    }

    public function test_empty_interactive_sku_fails_with_message(): void
    {
        $category = Category::factory()->create();

        $this->artisan('product:create', [
            '--category' => (string) $category->id,
            '--name' => 'Mechanical Keyboard',
            '--price' => '249.90',
            '--stock' => '15',
            '--description' => '',
        ])
            ->expectsQuestion('Product SKU', null)
            ->expectsConfirmation('Create as active?', 'yes')
            ->expectsOutput('Product SKU is required.')
            ->assertFailed();

        $this->assertDatabaseCount('products', 0);
    }

    public function test_interactive_input_without_categories_fails_before_other_questions(): void
    {
        $this->artisan('product:create', [
            '--name' => 'Mechanical Keyboard',
            '--sku' => 'KEY-001',
            '--price' => '249.90',
            '--stock' => '15',
            '--description' => '',
        ])
            ->expectsOutput('No categories are available.')
            ->assertFailed();

        $this->assertDatabaseCount('products', 0);
    }

    public function test_interactive_input_without_categories_does_not_start_other_questions(): void
    {
        $this->artisan('product:create')
            ->expectsOutput('No categories are available.')
            ->assertFailed();

        $this->assertDatabaseCount('products', 0);
    }

    public function test_it_fails_without_category_when_running_non_interactively(): void
    {
        $this->artisan('product:create', [
            '--no-interaction' => true,
            '--name' => 'Mechanical Keyboard',
            '--sku' => 'KEY-001',
            '--price' => '249.90',
            '--stock' => '15',
        ])
            ->expectsOutput('The --category option is required when running non-interactively.')
            ->assertFailed();

        $this->assertDatabaseCount('products', 0);
    }

    public function test_empty_interactive_price_fails_with_message(): void
    {
        $category = Category::factory()->create();

        $this->artisan('product:create', [
            '--category' => (string) $category->id,
            '--name' => 'Mechanical Keyboard',
            '--sku' => 'KEY-001',
            '--stock' => '15',
            '--description' => '',
        ])
            ->expectsQuestion('Product price', null)
            ->expectsConfirmation('Create as active?', 'yes')
            ->expectsOutput('Product price must be a non-negative decimal with at most two decimal places.')
            ->assertFailed();

        $this->assertDatabaseCount('products', 0);
    }

    public function test_empty_interactive_stock_fails_with_message(): void
    {
        $category = Category::factory()->create();

        $this->artisan('product:create', [
            '--category' => (string) $category->id,
            '--name' => 'Mechanical Keyboard',
            '--sku' => 'KEY-001',
            '--price' => '249.90',
            '--description' => '',
        ])
            ->expectsQuestion('Product stock', null)
            ->expectsConfirmation('Create as active?', 'yes')
            ->expectsOutput('Product stock must be a non-negative integer.')
            ->assertFailed();

        $this->assertDatabaseCount('products', 0);
    }

    public function test_it_rejects_a_missing_category(): void
    {
        $this->artisan('product:create', [
            '--category' => '999',
            '--name' => 'Mechanical Keyboard',
            '--sku' => 'KEY-001',
            '--price' => '249.90',
            '--stock' => '15',
            '--description' => '',
        ])
            ->expectsConfirmation('Create as active?', 'yes')
            ->expectsOutput('The selected category does not exist.')
            ->assertFailed();

        $this->assertDatabaseCount('products', 0);
    }

    public function test_it_rejects_invalid_names(): void
    {
        $category = Category::factory()->create();

        foreach (['', '   '] as $name) {
            $this->artisan('product:create', $this->validOptions($category, [
                '--name' => $name,
            ]))
                ->expectsConfirmation('Create as active?', 'yes')
                ->expectsOutput('Product name is required.')
                ->assertFailed();
        }

        $this->artisan('product:create', [
            '--no-interaction' => true,
            '--category' => (string) $category->id,
            '--sku' => 'KEY-001',
            '--price' => '249.90',
            '--stock' => '15',
        ])
            ->expectsOutput('The --name option is required when running non-interactively.')
            ->assertFailed();

        $this->assertDatabaseCount('products', 0);
    }

    public function test_it_rejects_invalid_skus(): void
    {
        $category = Category::factory()->create();

        foreach (['', '   '] as $sku) {
            $this->artisan('product:create', $this->validOptions($category, [
                '--sku' => $sku,
            ]))
                ->expectsConfirmation('Create as active?', 'yes')
                ->expectsOutput('Product SKU is required.')
                ->assertFailed();
        }

        $this->artisan('product:create', [
            '--no-interaction' => true,
            '--category' => (string) $category->id,
            '--name' => 'Mechanical Keyboard',
            '--price' => '249.90',
            '--stock' => '15',
        ])
            ->expectsOutput('The --sku option is required when running non-interactively.')
            ->assertFailed();

        $this->assertDatabaseCount('products', 0);
    }

    public function test_it_rejects_invalid_prices(): void
    {
        $category = Category::factory()->create();

        foreach (['-1', 'invalid', '19.999'] as $price) {
            $this->artisan('product:create', $this->validOptions($category, [
                '--price' => $price,
            ]))
                ->expectsConfirmation('Create as active?', 'yes')
                ->expectsOutput('Product price must be a non-negative decimal with at most two decimal places.')
                ->assertFailed();
        }

        $this->artisan('product:create', [
            '--no-interaction' => true,
            '--category' => (string) $category->id,
            '--name' => 'Mechanical Keyboard',
            '--sku' => 'KEY-001',
            '--stock' => '15',
        ])
            ->expectsOutput('The --price option is required when running non-interactively.')
            ->assertFailed();

        $this->assertDatabaseCount('products', 0);
    }

    public function test_it_rejects_invalid_stock(): void
    {
        $category = Category::factory()->create();

        foreach (['1.5', '-1'] as $stock) {
            $this->artisan('product:create', $this->validOptions($category, [
                '--stock' => $stock,
            ]))
                ->expectsConfirmation('Create as active?', 'yes')
                ->expectsOutput('Product stock must be a non-negative integer.')
                ->assertFailed();
        }

        $this->artisan('product:create', [
            '--no-interaction' => true,
            '--category' => (string) $category->id,
            '--name' => 'Mechanical Keyboard',
            '--sku' => 'KEY-001',
            '--price' => '249.90',
        ])
            ->expectsOutput('The --stock option is required when running non-interactively.')
            ->assertFailed();

        $this->assertDatabaseCount('products', 0);
    }

    public function test_it_rejects_duplicate_sku_with_failure_code(): void
    {
        $category = Category::factory()->create();

        Product::factory()->create([
            'category_id' => $category->id,
            'sku' => 'DUPLICATE-SKU',
        ]);

        $this->artisan('product:create', $this->validOptions($category, [
            '--sku' => 'DUPLICATE-SKU',
        ]))
            ->expectsConfirmation('Create as active?', 'yes')
            ->expectsOutput('The SKU has already been taken.')
            ->assertFailed();

        $this->assertDatabaseCount('products', 1);
    }

    public function test_invalid_data_is_not_persisted(): void
    {
        $category = Category::factory()->create();

        $this->artisan('product:create', $this->validOptions($category, [
            '--price' => 'invalid',
        ]))
            ->expectsConfirmation('Create as active?', 'yes')
            ->expectsOutput('Product price must be a non-negative decimal with at most two decimal places.')
            ->assertFailed();

        $this->assertDatabaseCount('products', 0);
    }

    public function test_it_uses_the_shared_create_product_action(): void
    {
        $category = Category::factory()->create();
        $product = Product::factory()->make([
            'id' => 123,
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

        $this->artisan('product:create', $this->validOptions($category))
            ->expectsConfirmation('Create as active?', 'yes')
            ->expectsOutput('Product created with ID 123: Mechanical Keyboard')
            ->assertSuccessful();
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validOptions(Category $category, array $overrides = []): array
    {
        return array_merge([
            '--category' => (string) $category->id,
            '--name' => 'Mechanical Keyboard',
            '--description' => 'Compact keyboard with brown switches.',
            '--sku' => 'KEY-001',
            '--price' => '249.90',
            '--stock' => '15',
        ], $overrides);
    }
}
