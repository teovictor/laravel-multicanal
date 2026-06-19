<?php

namespace Tests\Feature;

use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_category_can_be_persisted(): void
    {
        $category = Category::factory()->create([
            'name' => 'Office Supplies',
            'description' => 'Products used in office routines.',
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'name' => 'Office Supplies',
            'description' => 'Products used in office routines.',
            'is_active' => true,
        ]);
    }

    public function test_category_is_active_by_default_when_created_without_explicit_status(): void
    {
        $category = Category::create([
            'name' => 'Books',
            'description' => null,
        ]);

        $this->assertTrue($category->refresh()->is_active);

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'name' => 'Books',
            'description' => null,
            'is_active' => true,
        ]);
    }

    public function test_category_fillable_attributes_can_be_mass_assigned(): void
    {
        $category = Category::create([
            'name' => 'Electronics',
            'description' => 'Devices and accessories.',
            'is_active' => false,
        ]);

        $this->assertSame('Electronics', $category->name);
        $this->assertSame('Devices and accessories.', $category->description);
        $this->assertFalse($category->is_active);
    }

    public function test_is_active_is_cast_to_boolean(): void
    {
        $activeCategory = Category::factory()->create(['is_active' => 1]);
        $inactiveCategory = Category::factory()->inactive()->create();

        $this->assertIsBool($activeCategory->is_active);
        $this->assertTrue($activeCategory->is_active);
        $this->assertIsBool($inactiveCategory->is_active);
        $this->assertFalse($inactiveCategory->is_active);
    }
}
