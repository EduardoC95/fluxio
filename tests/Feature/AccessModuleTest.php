<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AccessModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_area_requires_authentication(): void
    {
        $this->get(route('access.users.index'))->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_list_users(): void
    {
        $user = User::factory()->create();
        $this->givePermission($user, 'utilizadores.read');

        $this->actingAs($user)
            ->get(route('access.users.index'))
            ->assertOk();
    }

    public function test_user_creation_hashes_password_explicitly(): void
    {
        $user = User::factory()->create();
        $this->givePermission($user, 'utilizadores.create');

        $this->actingAs($user)
            ->from(route('access.users.index'))
            ->post(route('access.users.store'), [
                'name' => 'Novo Utilizador',
                'email' => 'novo@example.test',
                'password' => 'UmaPasswordSegura123',
                'is_active' => true,
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('access.users.index'));

        $created = User::query()->where('email', 'novo@example.test')->firstOrFail();

        $this->assertNotSame('UmaPasswordSegura123', $created->password);
        $this->assertTrue(Hash::check('UmaPasswordSegura123', $created->password));
    }

    public function test_permission_group_rejects_invalid_permissions(): void
    {
        $user = User::factory()->create();
        $this->givePermission($user, 'permissoes.create');

        $this->actingAs($user)
            ->from(route('access.permissions.index'))
            ->post(route('access.permissions.store'), [
                'name' => 'Grupo Teste',
                'is_active' => true,
                'permissions' => ['permissao.invalida'],
            ])
            ->assertSessionHasErrors('permissions.0');
    }

    public function test_permission_group_update_rejects_invalid_permissions(): void
    {
        $user = User::factory()->create();
        $this->givePermission($user, 'permissoes.update');
        $role = Role::query()->create(['name' => 'Grupo Teste', 'guard_name' => 'web']);

        $this->actingAs($user)
            ->from(route('access.permissions.index'))
            ->patch(route('access.permissions.update', $role), [
                'name' => 'Grupo Teste',
                'is_active' => true,
                'permissions' => ['clientes.read', 'permissao.invalida'],
            ])
            ->assertSessionHasErrors('permissions.1');
    }

    public function test_admin_cannot_deactivate_their_own_account(): void
    {
        $adminRole = $this->adminRole();
        $admin = User::factory()->create(['is_active' => true]);
        $admin->assignRole($adminRole);

        $this->actingAs($admin)
            ->from(route('access.users.index'))
            ->patch(route('access.users.update', $admin), $this->userPayload($admin, $adminRole->id, false))
            ->assertSessionHasErrors('is_active');
    }

    public function test_admin_cannot_remove_their_own_admin_role(): void
    {
        $adminRole = $this->adminRole();
        $regularRole = Role::query()->create(['name' => 'Comercial', 'guard_name' => 'web']);
        $admin = User::factory()->create(['is_active' => true]);
        $admin->assignRole($adminRole);

        $this->actingAs($admin)
            ->from(route('access.users.index'))
            ->patch(route('access.users.update', $admin), $this->userPayload($admin, $regularRole->id, true))
            ->assertSessionHasErrors('role_id');
    }

    public function test_last_active_admin_cannot_be_deactivated_or_deleted(): void
    {
        $adminRole = $this->adminRole();
        $admin = User::factory()->create(['is_active' => true]);
        $admin->assignRole($adminRole);

        $manager = User::factory()->create(['is_active' => true]);
        $this->givePermission($manager, 'utilizadores.update');
        $this->givePermission($manager, 'utilizadores.delete');

        $this->actingAs($manager)
            ->from(route('access.users.index'))
            ->patch(route('access.users.update', $admin), $this->userPayload($admin, $adminRole->id, false))
            ->assertSessionHasErrors('role_id');

        $this->actingAs($manager)
            ->from(route('access.users.index'))
            ->delete(route('access.users.destroy', $admin))
            ->assertSessionHasErrors('user');
    }

    public function test_one_admin_can_be_changed_when_another_active_admin_remains(): void
    {
        $adminRole = $this->adminRole();
        $firstAdmin = User::factory()->create(['is_active' => true]);
        $firstAdmin->assignRole($adminRole);
        $secondAdmin = User::factory()->create(['is_active' => true]);
        $secondAdmin->assignRole($adminRole);

        $this->actingAs($firstAdmin)
            ->from(route('access.users.index'))
            ->patch(route('access.users.update', $secondAdmin), $this->userPayload($secondAdmin, null, false))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('access.users.index'));

        $this->assertFalse($secondAdmin->fresh()->is_active);
        $this->assertSame(1, User::role('Administrador')->where('is_active', true)->count());
    }

    private function givePermission(User $user, string $permission): void
    {
        Permission::findOrCreate($permission, 'web');
        $user->givePermissionTo($permission);
    }

    private function adminRole(): Role
    {
        return Role::findOrCreate('Administrador', 'web');
    }

    private function userPayload(User $user, ?int $roleId, bool $isActive): array
    {
        return [
            'name' => $user->name,
            'email' => $user->email,
            'mobile' => $user->mobile,
            'role_id' => $roleId,
            'is_active' => $isActive,
        ];
    }
}
