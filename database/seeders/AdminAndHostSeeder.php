<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;

class AdminAndHostSeeder extends Seeder
{
    public function run(): void
    {
        $adminEmail = (string) ($_ENV['SEED_ADMIN_EMAIL'] ?? 'admin@aparto.online');
        $adminPassword = (string) ($_ENV['SEED_ADMIN_PASSWORD'] ?? 'password');

        $hostEmail = (string) ($_ENV['SEED_HOST_EMAIL'] ?? 'host@aparto.online');
        $hostPassword = (string) ($_ENV['SEED_HOST_PASSWORD'] ?? 'password');
        $targetHosts = max(1, (int) ($_ENV['SEED_HOSTS_COUNT'] ?? 12));

        $admin = User::query()->updateOrCreate(
            ['email' => $adminEmail],
            [
                'name' => 'Admin User',
                'password' => Hash::make($adminPassword),
                'user_type' => User::TYPE_ADMIN,
                'email_verified_at' => now(),
            ]
        );

        $host = User::query()->updateOrCreate(
            ['email' => $hostEmail],
            [
                'name' => 'Host User',
                'password' => Hash::make($hostPassword),
                'user_type' => User::TYPE_HOST,
                'email_verified_at' => now(),
            ]
        );

        if (!Schema::hasTable('roles') || !Schema::hasTable('model_has_roles')) {
            return;
        }

        $superAdminAdmin = Role::findOrCreate('super_admin', 'admin');
        $adminAdmin = Role::findOrCreate('admin', 'admin');
        $hostAdmin = Role::findOrCreate('host', 'admin');

        $admin->syncRoles([$superAdminAdmin, $adminAdmin]);

        $hosts = User::query()
            ->where('user_type', User::TYPE_HOST)
            ->where('email', '!=', $adminEmail)
            ->get();

        $missingHosts = $targetHosts - $hosts->count();

        if ($missingHosts > 0) {
            User::factory()
                ->count($missingHosts)
                ->create([
                    'user_type' => User::TYPE_HOST,
                    'email_verified_at' => now(),
                ]);

            $hosts = User::query()
                ->where('user_type', User::TYPE_HOST)
                ->where('email', '!=', $adminEmail)
                ->get();
        }

        // Keep a dedicated host pool used by apartment ownership.
        $hosts->each(function (User $hostUser) use ($hostAdmin): void {
            $hostUser->syncRoles([$hostAdmin]);
        });

        $host->syncRoles([$hostAdmin]);
    }
}
