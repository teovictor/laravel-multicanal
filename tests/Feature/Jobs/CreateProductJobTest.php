<?php

namespace Tests\Feature\Jobs;

use App\Application\Products\Actions\CreateProduct;
use App\Application\Products\Data\CreateProductData;
use App\Jobs\CreateProductJob;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Mockery;
use ReflectionClass;
use Tests\TestCase;

class CreateProductJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_job_implements_should_queue(): void
    {
        $job = new CreateProductJob(
            categoryId: 1,
            name: 'Mechanical Keyboard',
            description: 'Compact keyboard with brown switches.',
            sku: 'KEY-001',
            priceCents: 24990,
            stock: 15,
        );

        $this->assertInstanceOf(ShouldQueue::class, $job);
    }

    public function test_constructor_preserves_all_received_values(): void
    {
        $job = new CreateProductJob(
            categoryId: 10,
            name: 'Mechanical Keyboard',
            description: 'Compact keyboard with brown switches.',
            sku: 'KEY-001',
            priceCents: 24990,
            stock: 15,
            isActive: false,
        );

        $this->assertSame(10, $job->categoryId);
        $this->assertSame('Mechanical Keyboard', $job->name);
        $this->assertSame('Compact keyboard with brown switches.', $job->description);
        $this->assertSame('KEY-001', $job->sku);
        $this->assertSame(24990, $job->priceCents);
        $this->assertSame(15, $job->stock);
        $this->assertFalse($job->isActive);
    }

    public function test_is_active_defaults_to_true(): void
    {
        $job = new CreateProductJob(
            categoryId: 1,
            name: 'Mechanical Keyboard',
            description: null,
            sku: 'KEY-001',
            priceCents: 24990,
            stock: 15,
        );

        $this->assertTrue($job->isActive);
    }

    public function test_constructor_uses_only_public_readonly_serializable_values(): void
    {
        $reflection = new ReflectionClass(CreateProductJob::class);
        $constructor = $reflection->getConstructor();

        $this->assertNotNull($constructor);

        $expectedParameters = [
            'categoryId' => 'int',
            'name' => 'string',
            'description' => '?string',
            'sku' => 'string',
            'priceCents' => 'int',
            'stock' => 'int',
            'isActive' => 'bool',
        ];

        foreach ($constructor->getParameters() as $parameter) {
            $property = $reflection->getProperty($parameter->getName());
            $type = $parameter->getType();
            $typeName = $type?->getName();
            $expectedType = $expectedParameters[$parameter->getName()] ?? null;

            if ($type?->allowsNull() && $typeName !== 'null') {
                $typeName = '?'.$typeName;
            }

            $this->assertSame($expectedType, $typeName);
            $this->assertTrue($property->isPublic());
            $this->assertTrue($property->isReadOnly());
        }

        $this->assertSame(array_keys($expectedParameters), array_map(
            fn ($parameter): string => $parameter->getName(),
            $constructor->getParameters(),
        ));
    }

    public function test_job_accepts_inactive_product(): void
    {
        $category = Category::factory()->create();

        CreateProductJob::dispatchSync(
            categoryId: $category->id,
            name: 'Archived Keyboard',
            description: 'Legacy keyboard.',
            sku: 'KEY-ARCHIVED',
            priceCents: 12990,
            stock: 4,
            isActive: false,
        );

        $this->assertDatabaseHas('products', [
            'category_id' => $category->id,
            'name' => 'Archived Keyboard',
            'description' => 'Legacy keyboard.',
            'sku' => 'KEY-ARCHIVED',
            'price_cents' => 12990,
            'stock' => 4,
            'is_active' => false,
        ]);
    }

    public function test_job_accepts_null_description(): void
    {
        $category = Category::factory()->create();

        CreateProductJob::dispatchSync(
            categoryId: $category->id,
            name: 'Mechanical Keyboard',
            description: null,
            sku: 'KEY-001',
            priceCents: 24990,
            stock: 15,
        );

        $this->assertDatabaseHas('products', [
            'category_id' => $category->id,
            'name' => 'Mechanical Keyboard',
            'description' => null,
            'sku' => 'KEY-001',
            'price_cents' => 24990,
            'stock' => 15,
            'is_active' => true,
        ]);
    }

    public function test_job_accepts_zero_price_cents_and_zero_stock(): void
    {
        $category = Category::factory()->create();

        CreateProductJob::dispatchSync(
            categoryId: $category->id,
            name: 'Free Sample',
            description: 'No stock sample.',
            sku: 'SAMPLE-001',
            priceCents: 0,
            stock: 0,
        );

        $this->assertDatabaseHas('products', [
            'category_id' => $category->id,
            'name' => 'Free Sample',
            'description' => 'No stock sample.',
            'sku' => 'SAMPLE-001',
            'price_cents' => 0,
            'stock' => 0,
            'is_active' => true,
        ]);
    }

    public function test_handle_calls_create_product_once_with_expected_data(): void
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

        $job = new CreateProductJob(
            categoryId: $category->id,
            name: 'Mechanical Keyboard',
            description: 'Compact keyboard with brown switches.',
            sku: 'KEY-001',
            priceCents: 24990,
            stock: 15,
        );

        $this->app->call([$job, 'handle']);
    }

    public function test_job_persists_valid_product_when_handled(): void
    {
        $category = Category::factory()->create();

        CreateProductJob::dispatchSync(
            categoryId: $category->id,
            name: 'Mechanical Keyboard',
            description: 'Compact keyboard with brown switches.',
            sku: 'KEY-001',
            priceCents: 24990,
            stock: 15,
        );

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

    public function test_job_associates_product_with_correct_category(): void
    {
        $first = Category::factory()->create();
        $second = Category::factory()->create();

        CreateProductJob::dispatchSync(
            categoryId: $second->id,
            name: 'Mechanical Keyboard',
            description: 'Compact keyboard with brown switches.',
            sku: 'KEY-001',
            priceCents: 24990,
            stock: 15,
        );

        $this->assertDatabaseMissing('products', [
            'category_id' => $first->id,
            'sku' => 'KEY-001',
        ]);

        $this->assertDatabaseHas('products', [
            'category_id' => $second->id,
            'sku' => 'KEY-001',
        ]);
    }

    public function test_job_does_not_convert_monetary_values(): void
    {
        $category = Category::factory()->create();

        CreateProductJob::dispatchSync(
            categoryId: $category->id,
            name: 'Cent Preserved Product',
            description: null,
            sku: 'CENT-001',
            priceCents: 199,
            stock: 1,
        );

        $this->assertDatabaseHas('products', [
            'category_id' => $category->id,
            'sku' => 'CENT-001',
            'price_cents' => 199,
        ]);
    }

    public function test_missing_category_exception_propagates(): void
    {
        $this->expectException(ModelNotFoundException::class);

        try {
            CreateProductJob::dispatchSync(
                categoryId: 999,
                name: 'Mechanical Keyboard',
                description: 'Compact keyboard with brown switches.',
                sku: 'KEY-001',
                priceCents: 24990,
                stock: 15,
            );
        } finally {
            $this->assertDatabaseCount('products', 0);
        }
    }

    public function test_invalid_data_exception_propagates(): void
    {
        $category = Category::factory()->create();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Product name is required.');

        try {
            CreateProductJob::dispatchSync(
                categoryId: $category->id,
                name: '',
                description: 'Compact keyboard with brown switches.',
                sku: 'KEY-001',
                priceCents: 24990,
                stock: 15,
            );
        } finally {
            $this->assertDatabaseCount('products', 0);
        }
    }

    public function test_duplicate_sku_database_exception_propagates(): void
    {
        $category = Category::factory()->create();

        Product::factory()->create([
            'category_id' => $category->id,
            'sku' => 'DUPLICATE-SKU',
        ]);

        $this->expectException(UniqueConstraintViolationException::class);

        CreateProductJob::dispatchSync(
            categoryId: $category->id,
            name: 'Another Product',
            description: null,
            sku: 'DUPLICATE-SKU',
            priceCents: 1000,
            stock: 3,
        );
    }
}
