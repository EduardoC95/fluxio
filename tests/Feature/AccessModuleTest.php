<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
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

    private function givePermission(User $user, string $permission): void
    {
        Permission::findOrCreate($permission, 'web');
        $user->givePermissionTo($permission);
    }
}
