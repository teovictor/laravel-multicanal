<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateCategoryEndpointTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_a_category(): void
    {
        $response = $this->postJson('/api/categories', [
            'name' => 'Office Supplies',
            'description' => 'Products used in office routines.',
            'is_active' => true,
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.name', 'Office Supplies')
            ->assertJsonPath('data.description', 'Products used in office routines.')
            ->assertJsonPath('data.is_active', true)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'description',
                    'is_active',
                    'created_at',
                    'updated_at',
                ],
            ]);

        $this->assertIsBool($response->json('data.is_active'));

        $this->assertDatabaseHas('categories', [
            'name' => 'Office Supplies',
            'description' => 'Products used in office routines.',
            'is_active' => true,
        ]);
    }

    public function test_it_creates_an_active_category_when_status_is_not_sent(): void
    {
        $response = $this->postJson('/api/categories', [
            'name' => 'Books',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.name', 'Books')
            ->assertJsonPath('data.description', null)
            ->assertJsonPath('data.is_active', true);

        $this->assertDatabaseHas('categories', [
            'name' => 'Books',
            'description' => null,
            'is_active' => true,
        ]);
    }

    public function test_it_creates_an_inactive_category(): void
    {
        $response = $this->postJson('/api/categories', [
            'name' => 'Archived Items',
            'is_active' => false,
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.name', 'Archived Items')
            ->assertJsonPath('data.is_active', false);

        $this->assertIsBool($response->json('data.is_active'));

        $this->assertDatabaseHas('categories', [
            'name' => 'Archived Items',
            'is_active' => false,
        ]);
    }

    public function test_it_returns_validation_errors_for_invalid_data(): void
    {
        $response = $this->postJson('/api/categories', [
            'name' => '',
            'description' => ['invalid'],
            'is_active' => 'invalid',
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'description', 'is_active']);

        $this->assertDatabaseCount('categories', 0);
    }

    public function test_it_returns_validation_error_when_name_is_blank(): void
    {
        $response = $this->postJson('/api/categories', [
            'name' => '   ',
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);

        $this->assertDatabaseCount('categories', 0);
    }

    public function test_it_rejects_names_longer_than_255_characters(): void
    {
        $response = $this->postJson('/api/categories', [
            'name' => str_repeat('a', 256),
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);

        $this->assertDatabaseCount('categories', 0);
    }
}
