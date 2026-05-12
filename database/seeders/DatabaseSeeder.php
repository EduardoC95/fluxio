<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(FluxioSeeder::class);

        $user = User::query()->firstOrCreate(
            ['email' => 'admin@fluxio.test'],
            [
                'name' => 'Administrador Fluxio',
                'password' => Hash::make('Fluxio123!admin'),
                'is_active' => true,
            ],
        );

        if (class_exists(Role::class)) {
            $adminRole = Role::query()->where('name', 'Administrador')->first();

            if ($adminRole) {
                $user->assignRole($adminRole);
            }
        }
    }
}
