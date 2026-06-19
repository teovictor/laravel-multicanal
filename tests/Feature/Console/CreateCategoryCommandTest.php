<?php

namespace Tests\Feature\Console;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateCategoryCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_an_active_category_from_options(): void
    {
        $this->artisan('category:create', [
            '--name' => 'Office Supplies',
            '--description' => 'Products used in office routines.',
        ])
            ->expectsOutput('Category created with ID 1: Office Supplies')
            ->assertSuccessful();

        $this->assertDatabaseHas('categories', [
            'name' => 'Office Supplies',
            'description' => 'Products used in office routines.',
            'is_active' => true,
        ]);
    }

    public function test_it_creates_an_inactive_category_from_flag(): void
    {
        $this->artisan('category:create', [
            '--name' => 'Archived Items',
            '--inactive' => true,
        ])
            ->expectsOutput('Category created with ID 1: Archived Items')
            ->assertSuccessful();

        $this->assertDatabaseHas('categories', [
            'name' => 'Archived Items',
            'description' => null,
            'is_active' => false,
        ]);
    }

    public function test_it_creates_a_category_without_description(): void
    {
        $this->artisan('category:create', [
            '--name' => 'Books',
        ])
            ->expectsOutput('Category created with ID 1: Books')
            ->assertSuccessful();

        $this->assertDatabaseHas('categories', [
            'name' => 'Books',
            'description' => null,
            'is_active' => true,
        ]);
    }

    public function test_it_accepts_interactive_input(): void
    {
        $this->artisan('category:create')
            ->expectsQuestion('Category name', 'Office Supplies')
            ->expectsQuestion('Category description', 'Products used in office routines.')
            ->expectsConfirmation('Create as active?', 'yes')
            ->expectsOutput('Category created with ID 1: Office Supplies')
            ->assertSuccessful();

        $this->assertDatabaseHas('categories', [
            'name' => 'Office Supplies',
            'description' => 'Products used in office routines.',
            'is_active' => true,
        ]);
    }

    public function test_it_accepts_empty_interactive_description(): void
    {
        $this->artisan('category:create')
            ->expectsQuestion('Category name', 'Books')
            ->expectsQuestion('Category description', '')
            ->expectsConfirmation('Create as active?', 'yes')
            ->expectsOutput('Category created with ID 1: Books')
            ->assertSuccessful();

        $this->assertDatabaseHas('categories', [
            'name' => 'Books',
            'description' => null,
            'is_active' => true,
        ]);
    }

    public function test_interactive_input_with_inactive_flag_does_not_ask_for_status(): void
    {
        $this->artisan('category:create --inactive')
            ->expectsQuestion('Category name', 'Archived Items')
            ->expectsQuestion('Category description', '')
            ->expectsOutput('Category created with ID 1: Archived Items')
            ->assertSuccessful();

        $this->assertDatabaseHas('categories', [
            'name' => 'Archived Items',
            'description' => null,
            'is_active' => false,
        ]);
    }

    public function test_it_rejects_empty_interactive_name(): void
    {
        $this->artisan('category:create')
            ->expectsQuestion('Category name', null)
            ->expectsQuestion('Category description', '')
            ->expectsConfirmation('Create as active?', 'yes')
            ->expectsOutput('Category name is required.')
            ->assertFailed();

        $this->assertDatabaseCount('categories', 0);
    }

    public function test_it_rejects_empty_name(): void
    {
        $this->artisan('category:create', [
            '--name' => '',
        ])
            ->expectsOutput('Category name is required.')
            ->assertFailed();

        $this->assertDatabaseCount('categories', 0);
    }

    public function test_it_rejects_blank_name(): void
    {
        $this->artisan('category:create', [
            '--name' => '   ',
        ])
            ->expectsOutput('Category name is required.')
            ->assertFailed();

        $this->assertDatabaseCount('categories', 0);
    }

    public function test_it_fails_without_name_when_running_non_interactively(): void
    {
        $this->artisan('category:create --no-interaction')
            ->expectsOutput('The --name option is required when running non-interactively.')
            ->assertFailed();

        $this->assertDatabaseCount('categories', 0);
    }
}
