<?php

namespace Tests\Feature;

use App\Application\Categories\Actions\CreateCategory;
use App\Application\Categories\Data\CreateCategoryData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class CreateCategoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_a_category(): void
    {
        $category = (new CreateCategory())->execute(new CreateCategoryData(
            name: 'Office Supplies',
            description: 'Products used in office routines.',
        ));

        $this->assertSame('Office Supplies', $category->name);
        $this->assertSame('Products used in office routines.', $category->description);
        $this->assertTrue($category->is_active);

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'name' => 'Office Supplies',
            'description' => 'Products used in office routines.',
            'is_active' => true,
        ]);
    }

    public function test_it_creates_an_active_category_by_default(): void
    {
        $category = (new CreateCategory())->execute(new CreateCategoryData(
            name: 'Books',
        ));

        $this->assertTrue($category->is_active);
    }

    public function test_it_creates_an_inactive_category(): void
    {
        $category = (new CreateCategory())->execute(new CreateCategoryData(
            name: 'Archived Items',
            description: 'Items that are no longer available.',
            isActive: false,
        ));

        $this->assertFalse($category->is_active);

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'is_active' => false,
        ]);
    }

    public function test_it_creates_a_category_without_description(): void
    {
        $category = (new CreateCategory())->execute(new CreateCategoryData(
            name: 'Books',
            description: null,
        ));

        $this->assertNull($category->description);

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'description' => null,
        ]);
    }

    public function test_it_rejects_an_empty_name_before_persisting(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Category name is required.');

        try {
            (new CreateCategory())->execute(new CreateCategoryData(
                name: '',
            ));
        } finally {
            $this->assertDatabaseCount('categories', 0);
        }
    }

    public function test_it_rejects_a_blank_name_before_persisting(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Category name is required.');

        try {
            (new CreateCategory())->execute(new CreateCategoryData(
                name: '   ',
            ));
        } finally {
            $this->assertDatabaseCount('categories', 0);
        }
    }

    public function test_it_does_not_normalize_category_name(): void
    {
        $category = (new CreateCategory())->execute(new CreateCategoryData(
            name: '  Mixed Case Name  ',
        ));

        $this->assertSame('  Mixed Case Name  ', $category->name);
    }
}
