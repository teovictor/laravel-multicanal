<?php

namespace Tests\Feature\Web;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateCategoryPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_category_page_responds_successfully(): void
    {
        $this->get(route('categories.create'))
            ->assertOk()
            ->assertSee('Criar categoria');
    }

    public function test_create_category_form_contains_fields_and_csrf_protection(): void
    {
        $this->get(route('categories.create'))
            ->assertOk()
            ->assertSee('method="POST"', false)
            ->assertSee('action="'.route('categories.store').'"', false)
            ->assertSee('name="_token"', false)
            ->assertSee('name="name"', false)
            ->assertSee('name="description"', false)
            ->assertSee('name="is_active"', false)
            ->assertSee('type="hidden"', false)
            ->assertSee('type="checkbox"', false)
            ->assertSee('checked', false);
    }

    public function test_it_creates_an_active_category(): void
    {
        $response = $this->post(route('categories.store'), [
            'name' => 'Office Supplies',
            'description' => 'Products used in office routines.',
            'is_active' => '1',
        ]);

        $response
            ->assertRedirect(route('categories.create'))
            ->assertSessionHas('status', 'Categoria criada com sucesso.');

        $this->assertDatabaseHas('categories', [
            'name' => 'Office Supplies',
            'description' => 'Products used in office routines.',
            'is_active' => true,
        ]);
    }

    public function test_it_creates_an_inactive_category(): void
    {
        $response = $this->post(route('categories.store'), [
            'name' => 'Archived Items',
            'description' => null,
            'is_active' => '0',
        ]);

        $response
            ->assertRedirect(route('categories.create'))
            ->assertSessionHas('status', 'Categoria criada com sucesso.');

        $this->assertDatabaseHas('categories', [
            'name' => 'Archived Items',
            'description' => null,
            'is_active' => false,
        ]);
    }

    public function test_invalid_data_returns_to_form_with_errors(): void
    {
        $response = $this->from(route('categories.create'))->post(route('categories.store'), [
            'name' => '',
            'description' => ['invalid'],
            'is_active' => 'invalid',
        ]);

        $response
            ->assertRedirect(route('categories.create'))
            ->assertSessionHasErrors(['name', 'description', 'is_active'])
            ->assertSessionHasInput('name', '')
            ->assertSessionHasInput('is_active', 'invalid');
    }

    public function test_no_category_is_persisted_when_validation_fails(): void
    {
        $this->from(route('categories.create'))->post(route('categories.store'), [
            'name' => '',
            'description' => 'Invalid category.',
            'is_active' => '1',
        ]);

        $this->assertDatabaseCount('categories', 0);
    }

    public function test_blank_name_returns_to_form_with_errors_and_does_not_persist(): void
    {
        $response = $this->from(route('categories.create'))->post(route('categories.store'), [
            'name' => '   ',
            'description' => 'Invalid category.',
            'is_active' => '1',
        ]);

        $response
            ->assertRedirect(route('categories.create'))
            ->assertSessionHasErrors(['name'])
            ->assertSessionHasInput('name', '');

        $this->assertDatabaseCount('categories', 0);
    }
}
