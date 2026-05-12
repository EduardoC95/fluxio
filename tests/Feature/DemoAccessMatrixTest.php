<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\FluxioDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DemoAccessMatrixTest extends TestCase
{
    use RefreshDatabase;

    public function test_financeiro_demo_user_has_financial_access_only(): void
    {
        $this->seed(FluxioDemoSeeder::class);

        $user = User::query()->where('email', 'financeiro@fluxio.test')->firstOrFail();

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('auth.user.roles')
                ->where('auth.user.roles.0', 'Financeiro')
                ->where('auth.user.permissions', fn (Collection $permissions): bool => $permissions->contains('financeiro.read')
                    && $permissions->contains('faturas-fornecedores.read')
                    && $permissions->contains('propostas.read')
                    && ! $permissions->contains('clientes.read')));

        $this->actingAs($user)->get(route('finance.index'))->assertOk();
        $this->actingAs($user)->get(route('supplier-invoices.index'))->assertOk();
        $this->actingAs($user)->get(route('customers.index'))->assertForbidden();
    }

    public function test_operacoes_demo_user_has_operational_access_without_financial_admin_access(): void
    {
        $this->seed(FluxioDemoSeeder::class);

        $user = User::query()->where('email', 'operacoes@fluxio.test')->firstOrFail();

        $this->actingAs($user)->get(route('customers.index'))->assertOk();
        $this->actingAs($user)->get(route('orders.customers.index'))->assertOk();
        $this->actingAs($user)->get(route('work-orders.index'))->assertOk();
        $this->actingAs($user)->get(route('finance.index'))->assertForbidden();
        $this->actingAs($user)->get(route('access.users.index'))->assertForbidden();
    }

    public function test_admin_demo_user_has_full_access(): void
    {
        $this->seed(FluxioDemoSeeder::class);

        $user = User::query()->where('email', 'admin@fluxio.test')->firstOrFail();

        $this->actingAs($user)->get(route('finance.index'))->assertOk();
        $this->actingAs($user)->get(route('customers.index'))->assertOk();
        $this->actingAs($user)->get(route('access.users.index'))->assertOk();
        $this->actingAs($user)->get(route('lookups.index', 'countries'))->assertOk();
    }
}
