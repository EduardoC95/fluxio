<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        $catalogue = [
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
    }

    public function down(): void
    {
        //
    }
};
