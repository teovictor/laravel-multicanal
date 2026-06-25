<?php

namespace App\Console\Commands;

use App\Application\Products\Actions\CreateProduct;
use App\Application\Products\Data\CreateProductData;
use App\Models\Category;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\UniqueConstraintViolationException;
use InvalidArgumentException;

use function Laravel\Prompts\select;

class CreateProductCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'product:create
        {--category= : Product category ID}
        {--name= : Product name}
        {--description= : Product description}
        {--sku= : Product SKU}
        {--price= : Product price}
        {--stock= : Product stock}
        {--inactive : Create the product as inactive}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a product';

    /**
     * Execute the console command.
     */
    public function handle(CreateProduct $createProduct): int
    {
        $categoryId = $this->categoryId();

        if ($categoryId === null) {
            return self::FAILURE;
        }

        $name = $this->optionValue('name', 'Product name');
        $description = $this->option('description');
        $sku = $this->optionValue('sku', 'Product SKU');
        $price = $this->optionValue('price', 'Product price');
        $stock = $this->optionValue('stock', 'Product stock');
        $isActive = ! $this->option('inactive');

        if ($name === null || $sku === null || $price === null || $stock === null) {
            return self::FAILURE;
        }

        if ($description === null && $this->input->isInteractive()) {
            $description = $this->ask('Product description');
        }

        if (! $this->option('inactive') && $this->input->isInteractive()) {
            $isActive = $this->confirm('Create as active?', true);
        }

        if ($description === '') {
            $description = null;
        }

        $categoryId = $this->parseCategoryId((string) $categoryId);
        $priceCents = $this->parsePriceCents((string) $price);
        $stock = $this->parseStock((string) $stock);

        if ($categoryId === null || $priceCents === null || $stock === null) {
            return self::FAILURE;
        }

        try {
            $product = $createProduct->execute(new CreateProductData(
                categoryId: $categoryId,
                name: (string) $name,
                description: $description,
                sku: (string) $sku,
                priceCents: $priceCents,
                stock: $stock,
                isActive: $isActive,
            ));
        } catch (InvalidArgumentException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        } catch (ModelNotFoundException) {
            $this->error('The selected category does not exist.');

            return self::FAILURE;
        } catch (UniqueConstraintViolationException) {
            $this->error('The SKU has already been taken.');

            return self::FAILURE;
        }

        $this->info("Product created with ID {$product->id}: {$product->name}");

        return self::SUCCESS;
    }

    private function optionValue(string $option, string $question): mixed
    {
        $value = $this->option($option);

        if ($value !== null) {
            return $value;
        }

        if (! $this->input->isInteractive()) {
            $this->error("The --{$option} option is required when running non-interactively.");

            return null;
        }

        return (string) $this->ask($question);
    }

    private function categoryId(): mixed
    {
        $categoryId = $this->option('category');

        if ($categoryId !== null) {
            return $categoryId;
        }

        if (! $this->input->isInteractive()) {
            $this->error('The --category option is required when running non-interactively.');

            return null;
        }

        $categories = Category::query()
            ->orderBy('name')
            ->get(['id', 'name']);

        if ($categories->isEmpty()) {
            $this->error('No categories are available.');

            return null;
        }

        return (int) select(
            label: 'Select the product category',
            options: $categories->mapWithKeys(fn (Category $category): array => [
                $category->id => "[{$category->id}] {$category->name}",
            ]),
            scroll: 10,
        );
    }

    private function parseCategoryId(string $categoryId): ?int
    {
        if (! preg_match('/^\d+$/', $categoryId)) {
            $this->error('Product category ID must be an integer.');

            return null;
        }

        return (int) $categoryId;
    }

    private function parsePriceCents(string $price): ?int
    {
        if (! preg_match('/^\d+(\.\d{1,2})?$/', $price)) {
            $this->error('Product price must be a non-negative decimal with at most two decimal places.');

            return null;
        }

        [$whole, $cents] = array_pad(explode('.', $price, 2), 2, '');

        return (int) ($whole.str_pad($cents, 2, '0'));
    }

    private function parseStock(string $stock): ?int
    {
        if (! preg_match('/^\d+$/', $stock)) {
            $this->error('Product stock must be a non-negative integer.');

            return null;
        }

        return (int) $stock;
    }
}
