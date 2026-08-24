<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolesSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_seeder_creates_one_working_login_per_role(): void
    {
        $this->seed(RolesSeeder::class);
        $this->seed(UserSeeder::class);

        $expected = [
            'admin@grossiste.dz' => 'owner',
            'manager@grossiste.dz' => 'manager',
            'vendeur@grossiste.dz' => 'sales',
            'achat@grossiste.dz' => 'purchasing',
            'stock@grossiste.dz' => 'warehouse',
            'compta@grossiste.dz' => 'accountant',
        ];

        foreach ($expected as $email => $role) {
            $user = User::where('email', $email)->sole();

            $this->assertTrue($user->hasRole($role), "{$email} should be {$role}");
            $this->assertTrue($user->is_active);
            $this->assertTrue(Hash::check('password', $user->password));

            $this->post(route('login'), ['email' => $email, 'password' => 'password'])
                ->assertRedirect(route('dashboard'));

            $this->assertAuthenticatedAs($user);
            auth()->logout();
        }
    }

    public function test_running_the_seeder_twice_does_not_reset_a_changed_password(): void
    {
        $this->seed(RolesSeeder::class);
        $this->seed(UserSeeder::class);

        $user = User::where('email', 'vendeur@grossiste.dz')->sole();
        $user->update(['password' => 'un-autre-mot-de-passe']);

        $this->seed(UserSeeder::class);

        $this->assertTrue(Hash::check('un-autre-mot-de-passe', $user->fresh()->password));
    }
}
