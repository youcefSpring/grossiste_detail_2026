<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_sent_to_the_login_page(): void
    {
        $this->get('/')->assertRedirect(route('login'));
    }

    public function test_a_user_can_log_in(): void
    {
        $user = User::factory()->create(['password' => 'password', 'is_active' => true]);

        $this->post(route('login'), ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->fresh()->last_login_at);
    }

    public function test_a_disabled_account_cannot_log_in(): void
    {
        $user = User::factory()->create(['password' => 'password', 'is_active' => false]);

        $this->post(route('login'), ['email' => $user->email, 'password' => 'password'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_the_language_can_be_switched_and_flips_direction(): void
    {
        $user = User::factory()->create(['is_active' => true, 'locale' => 'ar']);

        $this->actingAs($user)->get('/')->assertSee('dir="rtl"', false);

        $this->actingAs($user)->post(route('locale'), ['locale' => 'fr']);

        $this->assertSame('fr', $user->fresh()->locale);
        $this->actingAs($user)->get('/')->assertSee('dir="ltr"', false)->assertSee('Tableau de bord');
    }
}
