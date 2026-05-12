<?php

namespace Database\Seeders;

use App\Models\CalendarAction;
use App\Models\CalendarType;
use App\Models\CompanySetting;
use App\Models\ContactRole;
use App\Models\Country;
use App\Models\VatRate;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class FluxioSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach ([
            ['iso_code' => 'PT', 'name' => 'Portugal', 'phone_prefix' => '+351'],
            ['iso_code' => 'ES', 'name' => 'Espanha', 'phone_prefix' => '+34'],
            ['iso_code' => 'FR', 'name' => 'França', 'phone_prefix' => '+33'],
            ['iso_code' => 'DE', 'name' => 'Alemanha', 'phone_prefix' => '+49'],
            ['iso_code' => 'IT', 'name' => 'Itália', 'phone_prefix' => '+39'],
        ] as $country) {
            Country::query()->updateOrCreate(
                ['iso_code' => $country['iso_code']],
                array_merge($country, ['is_active' => true]),
            );
        }

        foreach (['Comercial', 'Financeiro', 'Direção', 'Operações'] as $role) {
            ContactRole::query()->updateOrCreate(
                ['name' => $role],
                ['description' => $role, 'is_active' => true],
            );
        }

        foreach ([
            ['name' => 'Normal', 'rate' => 23],
            ['name' => 'Intermédio', 'rate' => 13],
            ['name' => 'Reduzido', 'rate' => 6],
        ] as $vatRate) {
            VatRate::query()->updateOrCreate(
                ['name' => $vatRate['name']],
                ['rate' => $vatRate['rate'], 'is_active' => true],
            );
        }

        foreach ([
            ['name' => 'Visita', 'color' => '#b08968'],
            ['name' => 'Reunião', 'color' => '#7c6f64'],
            ['name' => 'Entrega', 'color' => '#d4a373'],
        ] as $type) {
            CalendarType::query()->updateOrCreate(
                ['name' => $type['name']],
                ['color' => $type['color'], 'is_active' => true],
            );
        }

        foreach ([
            ['name' => 'Contacto', 'color' => '#355070'],
            ['name' => 'Follow-up', 'color' => '#6d597a'],
            ['name' => 'Fecho', 'color' => '#9c6644'],
        ] as $action) {
            CalendarAction::query()->updateOrCreate(
                ['name' => $action['name']],
                ['color' => $action['color'], 'is_active' => true],
            );
        }

        CompanySetting::query()->firstOrCreate([], [
            'name' => 'Fluxio',
            'address' => 'Rua da Operação 42',
            'postal_code' => '1000-100',
            'city' => 'Lisboa',
            'tax_number' => '509999999',
        ]);

        $catalogue = [
            'dashboard' => ['read'],
            'clientes' => ['create', 'read', 'update', 'delete'],
            'fornecedores' => ['create', 'read', 'update', 'delete'],
            'contactos' => ['create', 'read', 'update', 'delete'],
            'propostas' => ['create', 'read', 'update', 'delete'],
            'encomendas-clientes' => ['create', 'read', 'update', 'delete'],
            'encomendas-fornecedores' => ['create', 'read', 'update', 'delete'],
            'faturas-fornecedores' => ['create', 'read', 'update', 'delete'],
            'ordens-trabalho' => ['create', 'read', 'update', 'delete'],
            'financeiro' => ['read'],
            'contas-bancarias' => ['create', 'read', 'update', 'delete'],
            'conta-corrente-clientes' => ['read'],
            'arquivo-digital' => ['create', 'read', 'update', 'delete'],
            'utilizadores' => ['create', 'read', 'update', 'delete'],
            'permissoes' => ['create', 'read', 'update', 'delete'],
            'configuracoes' => ['create', 'read', 'update', 'delete'],
            'workspace' => ['read'],
            'artigos' => ['create', 'read', 'update', 'delete'],
            'empresa' => ['create', 'read', 'update', 'delete'],
            'calendario' => ['create', 'read', 'update', 'delete'],
            'logs' => ['read'],
        ];

        foreach ($catalogue as $menu => $abilities) {
            foreach ($abilities as $ability) {
                Permission::findOrCreate(sprintf('%s.%s', $menu, $ability), 'web');
            }
        }

        $admin = Role::query()->firstOrCreate(
            ['name' => 'Administrador', 'guard_name' => 'web'],
            ['is_active' => true],
        );

        $admin->syncPermissions(Permission::query()->pluck('name')->all());

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
