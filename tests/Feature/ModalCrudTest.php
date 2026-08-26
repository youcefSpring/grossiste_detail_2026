<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use App\Models\Warehouse;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** The X-Modal header swaps the chrome for a bare form and the redirect for JSON. */
class ModalCrudTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsManager(): User
    {
        $this->seed(RolesSeeder::class);
        Warehouse::create(['name' => 'Main', 'is_default' => true]);

        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('manager');
        $this->actingAs($user);

        return $user;
    }

    public function test_a_form_opened_in_a_modal_comes_back_without_the_app_chrome(): void
    {
        $this->actingAsManager();

        $response = $this->withHeader('X-Modal', '1')->get(route('products.create'));

        $response->assertOk();
        $response->assertSee('data-modal-content', false);
        $response->assertDontSee('<aside', false);
    }

    public function test_the_same_form_still_renders_a_full_page_without_the_header(): void
    {
        $this->actingAsManager();

        $this->get(route('products.create'))
            ->assertOk()
            ->assertDontSee('data-modal-content', false);
    }

    public function test_storing_from_a_modal_answers_with_json(): void
    {
        $this->actingAsManager();

        $response = $this->withHeaders(['X-Modal' => '1', 'Accept' => 'application/json'])
            ->post(route('products.store'), [
                'name' => 'Eau 1.5L',
                'unit' => 'piece',
                'cost_price' => 30,
                'retail_price' => 50,
                'wholesale_price' => 40,
                'stock' => 0,
                'min_stock' => 0,
                'is_active' => 1,
            ]);

        $response->assertOk();
        $response->assertJson(['ok' => true]);
        $this->assertDatabaseHas('products', ['name' => 'Eau 1.5L']);
    }

    public function test_a_modal_submit_reports_validation_errors_as_422(): void
    {
        $this->actingAsManager();

        $this->withHeaders(['X-Modal' => '1', 'Accept' => 'application/json'])
            ->post(route('products.store'), ['name' => ''])
            ->assertStatus(422)
            ->assertJsonValidationErrors('name');
    }

    public function test_deleting_from_a_modal_answers_with_json(): void
    {
        $this->actingAsManager();
        $product = Product::create([
            'name' => 'Vieux', 'unit' => 'piece',
            'cost_price' => 100, 'retail_price' => 200, 'wholesale_price' => 150, 'is_active' => true,
        ]);

        $this->withHeaders(['X-Modal' => '1', 'Accept' => 'application/json'])
            ->delete(route('products.destroy', $product))
            ->assertOk()
            ->assertJson(['ok' => true]);

        $this->assertSoftDeleted('products', ['id' => $product->id]);
    }

    public function test_per_page_is_clamped_to_the_offered_sizes(): void
    {
        $this->actingAsManager();

        $this->get(route('products.index', ['per_page' => 999999]))->assertOk();
        $this->get(route('products.index', ['per_page' => 100]))->assertOk();
    }
}
