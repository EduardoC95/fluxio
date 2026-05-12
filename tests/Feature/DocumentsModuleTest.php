<?php

namespace Tests\Feature;

use App\Models\Entity;
use App\Models\Order;
use App\Models\Proposal;
use App\Models\SupplierInvoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class DocumentsModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_proposals_require_authentication(): void
    {
        $this->get(route('proposals.index'))->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_list_proposals(): void
    {
        $user = User::factory()->create();
        Permission::findOrCreate('propostas.read', 'web');
        $user->givePermissionTo('propostas.read');

        $this->actingAs($user)
            ->get(route('proposals.index'))
            ->assertOk();
    }

    public function test_supplier_invoice_rejects_invalid_document_mime(): void
    {
        Storage::fake('local');
        $supplier = Entity::query()->create([
            'number' => 2001,
            'name' => 'Fornecedor Teste',
            'is_supplier' => true,
            'is_active' => true,
        ]);

        $user = User::factory()->create();
        Permission::findOrCreate('faturas-fornecedores.create', 'web');
        $user->givePermissionTo('faturas-fornecedores.create');

        $this->actingAs($user)
            ->from(route('supplier-invoices.index'))
            ->post(route('supplier-invoices.store'), [
                'invoice_date' => now()->toDateString(),
                'due_date' => now()->addDay()->toDateString(),
                'supplier_entity_id' => $supplier->id,
                'total' => 100,
                'status' => 'pending',
                'document' => UploadedFile::fake()->create('payload.exe', 12, 'application/x-msdownload'),
            ])
            ->assertSessionHasErrors('document');
    }

    public function test_supplier_invoice_rejects_customer_order_as_supplier_order(): void
    {
        $supplier = $this->createSupplier();
        $customerOrder = $this->createOrder('customer');
        $user = User::factory()->create();
        $this->givePermission($user, 'faturas-fornecedores.create');

        $this->actingAs($user)
            ->from(route('supplier-invoices.index'))
            ->post(route('supplier-invoices.store'), $this->supplierInvoicePayload($supplier->id, $customerOrder->id))
            ->assertSessionHasErrors('supplier_order_id');
    }

    public function test_supplier_invoice_rejects_supplier_order_from_another_supplier(): void
    {
        $supplier = $this->createSupplier();
        $anotherSupplier = $this->createSupplier();
        $supplierOrder = $this->createOrder('supplier', $anotherSupplier);
        $user = User::factory()->create();
        $this->givePermission($user, 'faturas-fornecedores.create');

        $this->actingAs($user)
            ->from(route('supplier-invoices.index'))
            ->post(route('supplier-invoices.store'), $this->supplierInvoicePayload($supplier->id, $supplierOrder->id))
            ->assertSessionHasErrors('supplier_order_id');
    }

    public function test_supplier_invoice_accepts_matching_supplier_order_and_nullable_order(): void
    {
        $supplier = $this->createSupplier();
        $supplierOrder = $this->createOrder('supplier', $supplier);
        $user = User::factory()->create();
        $this->givePermission($user, 'faturas-fornecedores.create');

        $this->actingAs($user)
            ->from(route('supplier-invoices.index'))
            ->post(route('supplier-invoices.store'), $this->supplierInvoicePayload($supplier->id, $supplierOrder->id))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('supplier-invoices.index'));

        $this->actingAs($user)
            ->from(route('supplier-invoices.index'))
            ->post(route('supplier-invoices.store'), $this->supplierInvoicePayload($supplier->id, null, 'FF-2026-0002'))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('supplier-invoices.index'));
    }

    public function test_proposal_pdf_requires_record_authorization(): void
    {
        $customer = Entity::query()->create([
            'number' => 1001,
            'name' => 'Cliente Teste',
            'is_customer' => true,
            'is_active' => true,
        ]);
        $proposal = Proposal::query()->create([
            'number' => 'PROP-2026-0001',
            'proposal_date' => now()->toDateString(),
            'valid_until' => now()->addDay()->toDateString(),
            'entity_id' => $customer->id,
            'line_items' => [['name' => 'Item', 'quantity' => 1, 'unit_price' => 10]],
            'totals' => ['total' => 10],
            'status' => 'draft',
        ]);

        $this->actingAs(User::factory()->create())
            ->get(route('proposals.pdf', $proposal))
            ->assertForbidden();

        $authorized = User::factory()->create();
        Permission::findOrCreate('propostas.read', 'web');
        $authorized->givePermissionTo('propostas.read');

        $this->actingAs($authorized)
            ->get(route('proposals.pdf', $proposal))
            ->assertOk();
    }

    public function test_order_pdf_requires_authentication(): void
    {
        $order = $this->createOrder('customer');

        $this->get(route('orders.pdf', $order))
            ->assertRedirect(route('login'));
    }

    public function test_order_pdf_requires_matching_permission(): void
    {
        $customerOrder = $this->createOrder('customer');

        $this->actingAs(User::factory()->create())
            ->get(route('orders.pdf', $customerOrder))
            ->assertForbidden();
    }

    public function test_customer_order_pdf_can_be_downloaded_with_customer_order_permission(): void
    {
        $customerOrder = $this->createOrder('customer');
        $user = User::factory()->create();
        $this->givePermission($user, 'encomendas-clientes.read');

        $this->actingAs($user)
            ->get(route('orders.pdf', $customerOrder))
            ->assertOk();
    }

    public function test_order_pdf_authorization_distinguishes_customer_and_supplier_orders(): void
    {
        $customerOrder = $this->createOrder('customer');
        $supplierOrder = $this->createOrder('supplier');

        $customerReader = User::factory()->create();
        $this->givePermission($customerReader, 'encomendas-clientes.read');

        $supplierReader = User::factory()->create();
        $this->givePermission($supplierReader, 'encomendas-fornecedores.read');

        $this->actingAs($customerReader)
            ->get(route('orders.pdf', $supplierOrder))
            ->assertForbidden();

        $this->actingAs($supplierReader)
            ->get(route('orders.pdf', $customerOrder))
            ->assertForbidden();

        $this->actingAs($supplierReader)
            ->get(route('orders.pdf', $supplierOrder))
            ->assertOk();
    }

    public function test_repeated_supplier_order_conversion_does_not_create_duplicates(): void
    {
        $supplier = $this->createSupplier();
        $order = $this->createOrder('customer', null, [
            'status' => 'closed',
            'line_items' => [[
                'name' => 'Item',
                'quantity' => 1,
                'unit_price' => 10,
                'supplier_entity_id' => $supplier->id,
            ]],
        ]);
        $user = User::factory()->create();
        $this->givePermission($user, 'encomendas-clientes.update');

        $this->actingAs($user)
            ->from(route('orders.customers.index'))
            ->post(route('orders.customers.convert-suppliers', $order))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('orders.customers.index'));

        $this->actingAs($user)
            ->from(route('orders.customers.index'))
            ->post(route('orders.customers.convert-suppliers', $order))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('orders.customers.index'));

        $this->assertSame(1, Order::query()
            ->where('kind', 'supplier')
            ->where('source_order_id', $order->id)
            ->where('supplier_entity_id', $supplier->id)
            ->count());
    }

    public function test_invoice_assets_require_read_permission(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('supplier-invoices/documents/test.pdf', 'pdf');

        $supplier = Entity::query()->create([
            'number' => 2002,
            'name' => 'Fornecedor',
            'is_supplier' => true,
            'is_active' => true,
        ]);
        $invoice = SupplierInvoice::query()->create([
            'number' => 'FF-2026-0001',
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDay()->toDateString(),
            'supplier_entity_id' => $supplier->id,
            'total' => 10,
            'document_path' => 'supplier-invoices/documents/test.pdf',
            'status' => 'pending',
        ]);

        $this->actingAs(User::factory()->create())
            ->get(route('assets.invoice-document', $invoice))
            ->assertForbidden();
    }

    private function createOrder(string $kind, ?Entity $supplier = null, array $overrides = []): Order
    {
        $nextNumber = ((int) Entity::query()->max('number')) + 1;

        $customer = Entity::query()->create([
            'number' => $nextNumber,
            'name' => 'Cliente Teste',
            'is_customer' => true,
            'is_active' => true,
        ]);

        $supplier ??= Entity::query()->create([
            'number' => $nextNumber + 1,
            'name' => 'Fornecedor Teste',
            'is_supplier' => true,
            'is_active' => true,
        ]);

        return Order::query()->create(array_merge([
            'number' => $kind === 'customer' ? 'ENCC-2026-0001' : 'ENCF-2026-0001',
            'kind' => $kind,
            'order_date' => now()->toDateString(),
            'valid_until' => now()->addDay()->toDateString(),
            'customer_entity_id' => $kind === 'customer' ? $customer->id : null,
            'supplier_entity_id' => $kind === 'supplier' ? $supplier->id : null,
            'line_items' => [['name' => 'Item', 'quantity' => 1, 'unit_price' => 10]],
            'totals' => ['total' => 10],
            'status' => 'draft',
        ], $overrides));
    }

    private function createSupplier(): Entity
    {
        return Entity::query()->create([
            'number' => ((int) Entity::query()->max('number')) + 1,
            'name' => 'Fornecedor Teste',
            'is_supplier' => true,
            'is_active' => true,
        ]);
    }

    private function supplierInvoicePayload(int $supplierId, ?int $orderId, string $number = 'FF-2026-0001'): array
    {
        return [
            'number' => $number,
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDay()->toDateString(),
            'supplier_entity_id' => $supplierId,
            'supplier_order_id' => $orderId,
            'total' => 100,
            'status' => 'pending',
        ];
    }

    private function givePermission(User $user, string $permission): void
    {
        Permission::findOrCreate($permission, 'web');
        $user->givePermissionTo($permission);
    }
}
