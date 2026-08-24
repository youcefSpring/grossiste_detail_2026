<?php

namespace Database\Seeders;

use App\Models\User;
use App\Support\Permissions;
use Illuminate\Database\Seeder;

/**
 * One login per role, so every screen can be tried as the employee who would really use it.
 *
 * Passwords are deliberately obvious — this seeder is for testing and demonstration.
 * Never run it on a live shop.
 */
class UserSeeder extends Seeder
{
    private const PASSWORD = 'password';

    /** name, email, role, language */
    private const USERS = [
        ['المدير العام', 'admin@grossiste.dz', 'owner', 'ar'],
        ['كريم المسيّر', 'manager@grossiste.dz', 'manager', 'ar'],
        ['سمير البائع', 'vendeur@grossiste.dz', 'sales', 'ar'],
        ['ياسين المشتري', 'achat@grossiste.dz', 'purchasing', 'fr'],
        ['نبيل المخزني', 'stock@grossiste.dz', 'warehouse', 'ar'],
        ['سليمة المحاسبة', 'compta@grossiste.dz', 'accountant', 'fr'],
    ];

    public function run(): void
    {
        foreach (self::USERS as [$name, $email, $role, $locale]) {
            $user = User::withTrashed()->firstOrNew(['email' => $email]);

            $user->fill([
                'name' => $name,
                'locale' => $locale,
                'is_active' => true,
            ]);

            // Only set the password on creation, so a changed one is never reset.
            if (! $user->exists) {
                $user->password = self::PASSWORD;
            }

            $user->deleted_at = null;
            $user->save();
            $user->syncRoles([$role]);
        }

        $this->command->newLine();
        $this->command->info('Comptes de test — mot de passe : '.self::PASSWORD);
        $this->command->table(
            ['email', 'rôle', 'peut faire'],
            collect(self::USERS)->map(fn ($row) => [
                $row[1],
                __('user.roles.'.$row[2], [], 'fr'),
                __('user.role_hints.'.$row[2], [], 'fr'),
            ])->all(),
        );
    }
}
