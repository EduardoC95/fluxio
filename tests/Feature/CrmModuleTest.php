<?php

namespace Tests\Feature;

use App\Models\Contact;
use App\Models\ContactRole;
use App\Models\Entity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class CrmModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_contacts_require_authentication(): void
    {
        $this->get(route('contacts.index'))->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_list_contacts(): void
    {
        $user = User::factory()->create();
        $this->givePermission($user, 'contactos.read');

        $this->actingAs($user)->get(route('contacts.index'))->assertOk();
    }

    public function test_contact_can_be_created_with_valid_data(): void
    {
        $user = User::factory()->create();
        $this->givePermission($user, 'contactos.create');
        $entity = Entity::query()->create([
            'number' => 1001,
            'name' => 'Cliente Teste',
            'is_customer' => true,
            'is_active' => true,
        ]);
        $role = ContactRole::query()->create(['name' => 'Compras', 'is_active' => true]);

        $this->actingAs($user)
            ->from(route('contacts.index'))
            ->post(route('contacts.store'), [
                'entity_id' => $entity->id,
                'first_name' => 'Ana',
                'last_name' => 'Silva',
                'contact_role_id' => $role->id,
                'email' => 'ana@example.test',
                'gdpr_consent' => true,
                'is_active' => true,
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('contacts.index'));

        $this->assertSame('Ana', Contact::query()->first()?->first_name);
    }

    public function test_entity_requires_valid_postal_code(): void
    {
        $user = User::factory()->create();
        $this->givePermission($user, 'clientes.create');

        $this->actingAs($user)
            ->from(route('customers.index'))
            ->post(route('customers.store'), [
                'name' => 'Cliente inválido',
                'postal_code' => '1000',
                'is_customer' => true,
            ])
            ->assertSessionHasErrors('postal_code');
    }

    private function givePermission(User $user, string $permission): void
    {
        Permission::findOrCreate($permission, 'web');
        $user->givePermissionTo($permission);
    }
}
