<?php

namespace Tests\Feature;

use App\Models\Entity;
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
}
