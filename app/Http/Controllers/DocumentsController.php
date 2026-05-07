<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\LogsFluxioActivity;
use App\Mail\SupplierPaymentProofMail;
use App\Models\Article;
use App\Models\CompanySetting;
use App\Models\Entity;
use App\Models\Order;
use App\Models\Proposal;
use App\Models\SupplierInvoice;
use App\Models\VatRate;
use App\Support\DocumentNumberGenerator;
use App\Support\LineItemManager;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class DocumentsController extends Controller
{
    use LogsFluxioActivity;

    public function proposals(): Response
    {
        $proposals = Proposal::query()->with('customer')->latest()->get();

        return Inertia::render('Proposals/Index', [
            'records' => $proposals->map(fn (Proposal $proposal): array => $this->transformProposal($proposal))->values(),
            'customers' => Entity::query()->customers()->orderBy('number')->get()->map(fn (Entity $entity): array => [
                'id' => $entity->id,
                'label' => sprintf('%s - %s', $entity->number, $entity->name),
            ])->values(),
            'suppliers' => $this->supplierOptions(),
            'articles' => $this->articleOptions(),
            'defaults' => [
                'number' => DocumentNumberGenerator::nextDocument(Proposal::class, 'PROP'),
                'proposal_date' => now()->format('Y-m-d'),
                'valid_until' => now()->addDays(30)->format('Y-m-d'),
                'status' => 'draft',
                'line_items' => [],
            ],
            'endpoints' => [
                'store' => '/propostas',
                'update' => '/propostas',
                'delete' => '/propostas',
                'convert' => '/propostas',
                'pdf' => '/propostas',
            ],
        ]);
    }

    public function storeProposal(Request $request): RedirectResponse
    {
        $payload = $this->validatedProposalPayload($request);

        $proposal = Proposal::query()->create($payload);

        $this->logFluxioActivity($request, 'Propostas', 'create', $proposal);

        return back()->with($this->flashToast('success', 'Proposta criada com sucesso.'));
    }

    public function updateProposal(Request $request, Proposal $proposal): RedirectResponse
    {
        $proposal->update($this->validatedProposalPayload($request, $proposal));

        $this->logFluxioActivity($request, 'Propostas', 'update', $proposal);

        return back()->with($this->flashToast('success', 'Proposta atualizada com sucesso.'));
    }

    public function destroyProposal(Request $request, Proposal $proposal): RedirectResponse
    {
        $proposal->delete();

        $this->logFluxioActivity($request, 'Propostas', 'delete');

        return back()->with($this->flashToast('success', 'Proposta removida com sucesso.'));
    }

    public function convertProposal(Request $request, Proposal $proposal): RedirectResponse
    {
        $order = Order::query()->create([
            'number' => DocumentNumberGenerator::nextDocument(Order::class, 'ENCC'),
            'kind' => 'customer',
            'order_date' => now()->format('Y-m-d'),
            'valid_until' => now()->addDays(30)->format('Y-m-d'),
            'customer_entity_id' => $proposal->entity_id,
            'proposal_id' => $proposal->id,
            'line_items' => $proposal->line_items ?? [],
            'totals' => $proposal->totals ?? [],
            'status' => 'draft',
        ]);

        $this->logFluxioActivity($request, 'Propostas', 'convert', $proposal, [
            'target_order' => $order->number,
        ]);

        return back()->with($this->flashToast('success', sprintf('Proposta convertida em encomenda %s.', $order->number)));
    }

    public function downloadProposalPdf(Proposal $proposal)
    {
        $proposal->loadMissing(['customer.country']);
        $company = CompanySetting::query()->first();

        return Pdf::loadView('pdf.document', [
            'documentType' => 'Proposta',
            'document' => $this->transformProposal($proposal),
            'company' => $company,
        ])->download(sprintf('%s.pdf', $proposal->number));
    }

    public function customerOrders(): Response
    {
        return $this->ordersPage('customer');
    }

    public function supplierOrders(): Response
    {
        return $this->ordersPage('supplier');
    }

    public function storeCustomerOrder(Request $request): RedirectResponse
    {
        return $this->storeOrder($request, 'customer');
    }

    public function storeSupplierOrder(Request $request): RedirectResponse
    {
        return $this->storeOrder($request, 'supplier');
    }

    public function updateCustomerOrder(Request $request, Order $order): RedirectResponse
    {
        return $this->updateOrder($request, $order, 'customer');
    }

    public function updateSupplierOrder(Request $request, Order $order): RedirectResponse
    {
        return $this->updateOrder($request, $order, 'supplier');
    }

    public function destroyOrder(Request $request, Order $order): RedirectResponse
    {
        $order->delete();

        $this->logFluxioActivity($request, 'Encomendas', 'delete');

        return back()->with($this->flashToast('success', 'Encomenda removida com sucesso.'));
    }

    public function convertToSupplierOrders(Request $request, Order $order): RedirectResponse
    {
        $items = $order->line_items ?? [];
        $supplierIds = collect($items)->pluck('supplier_entity_id')->filter()->unique()->values();

        foreach ($supplierIds as $supplierId) {
            $supplierItems = LineItemManager::forSupplier($items, (int) $supplierId);

            if ($supplierItems === []) {
                continue;
            }

            Order::query()->create([
                'number' => DocumentNumberGenerator::nextDocument(Order::class, 'ENCF'),
                'kind' => 'supplier',
                'order_date' => now()->format('Y-m-d'),
                'valid_until' => now()->addDays(30)->format('Y-m-d'),
                'customer_entity_id' => $order->customer_entity_id,
                'supplier_entity_id' => $supplierId,
                'source_order_id' => $order->id,
                'line_items' => $supplierItems,
                'totals' => LineItemManager::totals($supplierItems),
                'status' => 'draft',
            ]);
        }

        $this->logFluxioActivity($request, 'Encomendas', 'convert', $order);

        return back()->with($this->flashToast('success', 'Encomendas de fornecedor criadas com sucesso.'));
    }

    public function downloadOrderPdf(Order $order)
    {
        $order->loadMissing(['customer.country', 'supplier.country']);
        $company = CompanySetting::query()->first();

        return Pdf::loadView('pdf.document', [
            'documentType' => 'Encomenda',
            'document' => $this->transformOrder($order->load(['customer', 'supplier'])),
            'company' => $company,
        ])->download(sprintf('%s.pdf', $order->number));
    }

    public function supplierInvoices(): Response
    {
        $invoices = SupplierInvoice::query()->with(['supplier', 'supplierOrder'])->latest()->get();

        return Inertia::render('SupplierInvoices/Index', [
            'records' => $invoices->map(fn (SupplierInvoice $invoice): array => [
                'id' => $invoice->id,
                'number' => $invoice->number,
                'invoice_date' => optional($invoice->invoice_date)->format('Y-m-d'),
                'due_date' => optional($invoice->due_date)->format('Y-m-d'),
                'supplier_entity_id' => $invoice->supplier_entity_id,
                'supplier_name' => $invoice->supplier?->name,
                'supplier_order_id' => $invoice->supplier_order_id,
                'supplier_order_number' => $invoice->supplierOrder?->number,
                'total' => (float) $invoice->total,
                'status' => $invoice->status,
                'document_url' => $invoice->document_path ? sprintf('/ativos/faturas/%d/documento', $invoice->id) : null,
                'payment_proof_url' => $invoice->payment_proof_path ? sprintf('/ativos/faturas/%d/comprovativo', $invoice->id) : null,
            ])->values(),
            'suppliers' => $this->supplierOptions(),
            'supplierOrders' => Order::query()->where('kind', 'supplier')->with('supplier')->latest()->get()->map(fn (Order $order): array => [
                'id' => $order->id,
                'label' => sprintf('%s - %s', $order->number, $order->supplier?->name ?: 'Sem fornecedor'),
                'supplier_entity_id' => $order->supplier_entity_id,
            ])->values(),
            'defaults' => [
                'number' => DocumentNumberGenerator::nextDocument(SupplierInvoice::class, 'FF'),
                'invoice_date' => now()->format('Y-m-d'),
                'due_date' => now()->addDays(30)->format('Y-m-d'),
                'status' => 'pending',
            ],
            'endpoints' => [
                'store' => '/financeiro/faturas-fornecedor',
                'update' => '/financeiro/faturas-fornecedor',
                'delete' => '/financeiro/faturas-fornecedor',
            ],
        ]);
    }

    public function storeSupplierInvoice(Request $request): RedirectResponse
    {
        $invoice = new SupplierInvoice($this->validatedSupplierInvoice($request));

        if ($request->hasFile('document')) {
            $invoice->document_path = $request->file('document')->store('supplier-invoices/documents', 'local');
        }

        if ($request->hasFile('payment_proof')) {
            $invoice->payment_proof_path = $request->file('payment_proof')->store('supplier-invoices/payment-proofs', 'local');
        }

        $invoice->save();

        $this->sendPaymentProofIfNeeded($invoice);
        $this->logFluxioActivity($request, 'Faturas Fornecedor', 'create', $invoice);

        return back()->with($this->flashToast('success', 'Fatura de fornecedor criada com sucesso.'));
    }

    public function updateSupplierInvoice(Request $request, SupplierInvoice $supplierInvoice): RedirectResponse
    {
        $supplierInvoice->fill($this->validatedSupplierInvoice($request));

        if ($request->hasFile('document')) {
            if ($supplierInvoice->document_path) {
                Storage::disk('local')->delete($supplierInvoice->document_path);
            }

            $supplierInvoice->document_path = $request->file('document')->store('supplier-invoices/documents', 'local');
        }

        if ($request->hasFile('payment_proof')) {
            if ($supplierInvoice->payment_proof_path) {
                Storage::disk('local')->delete($supplierInvoice->payment_proof_path);
            }

            $supplierInvoice->payment_proof_path = $request->file('payment_proof')->store('supplier-invoices/payment-proofs', 'local');
        }

        $supplierInvoice->save();

        $this->sendPaymentProofIfNeeded($supplierInvoice);
        $this->logFluxioActivity($request, 'Faturas Fornecedor', 'update', $supplierInvoice);

        return back()->with($this->flashToast('success', 'Fatura de fornecedor atualizada com sucesso.'));
    }

    public function destroySupplierInvoice(Request $request, SupplierInvoice $supplierInvoice): RedirectResponse
    {
        if ($supplierInvoice->document_path) {
            Storage::disk('local')->delete($supplierInvoice->document_path);
        }

        if ($supplierInvoice->payment_proof_path) {
            Storage::disk('local')->delete($supplierInvoice->payment_proof_path);
        }

        $supplierInvoice->delete();

        $this->logFluxioActivity($request, 'Faturas Fornecedor', 'delete');

        return back()->with($this->flashToast('success', 'Fatura de fornecedor removida com sucesso.'));
    }

    private function ordersPage(string $kind): Response
    {
        $orders = Order::query()
            ->with(['customer', 'supplier'])
            ->where('kind', $kind)
            ->latest()
            ->get();

        return Inertia::render('Orders/Index', [
            'mode' => $kind,
            'records' => $orders->map(fn (Order $order): array => $this->transformOrder($order))->values(),
            'customers' => Entity::query()->customers()->orderBy('number')->get()->map(fn (Entity $entity): array => [
                'id' => $entity->id,
                'label' => sprintf('%s - %s', $entity->number, $entity->name),
            ])->values(),
            'suppliers' => $this->supplierOptions(),
            'articles' => $this->articleOptions(),
            'defaults' => [
                'number' => DocumentNumberGenerator::nextDocument(Order::class, $kind === 'customer' ? 'ENCC' : 'ENCF'),
                'order_date' => now()->format('Y-m-d'),
                'valid_until' => now()->addDays(30)->format('Y-m-d'),
                'status' => 'draft',
                'line_items' => [],
            ],
            'endpoints' => [
                'store' => $kind === 'customer' ? '/encomendas-clientes' : '/encomendas-fornecedores',
                'update' => $kind === 'customer' ? '/encomendas-clientes' : '/encomendas-fornecedores',
                'delete' => '/encomendas',
                'convert' => '/encomendas-clientes',
                'pdf' => '/encomendas',
            ],
        ]);
    }

    private function storeOrder(Request $request, string $kind): RedirectResponse
    {
        $order = Order::query()->create($this->validatedOrderPayload($request, $kind));

        $this->logFluxioActivity($request, $kind === 'customer' ? 'Encomendas Clientes' : 'Encomendas Fornecedores', 'create', $order);

        return back()->with($this->flashToast('success', 'Encomenda criada com sucesso.'));
    }

    private function updateOrder(Request $request, Order $order, string $kind): RedirectResponse
    {
        $order->update($this->validatedOrderPayload($request, $kind, $order));

        $this->logFluxioActivity($request, $kind === 'customer' ? 'Encomendas Clientes' : 'Encomendas Fornecedores', 'update', $order);

        return back()->with($this->flashToast('success', 'Encomenda atualizada com sucesso.'));
    }

    private function validatedProposalPayload(Request $request, ?Proposal $proposal = null): array
    {
        $validated = $request->validate([
            'number' => ['nullable', 'string', 'max:255'],
            'proposal_date' => ['required', 'date'],
            'valid_until' => ['required', 'date', 'after_or_equal:proposal_date'],
            'entity_id' => ['required', Rule::exists('entities', 'id')->where('is_customer', true)],
            'status' => ['required', Rule::in(['draft', 'closed'])],
            'line_items' => ['required', 'array', 'min:1'],
            'line_items.*.article_id' => ['nullable', 'exists:articles,id'],
            'line_items.*.supplier_entity_id' => ['nullable', Rule::exists('entities', 'id')->where('is_supplier', true)],
            'line_items.*.reference' => ['nullable', 'string', 'max:255'],
            'line_items.*.name' => ['required', 'string', 'max:255'],
            'line_items.*.description' => ['nullable', 'string'],
            'line_items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'line_items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'line_items.*.cost_price' => ['nullable', 'numeric', 'min:0'],
            'line_items.*.vat_rate' => ['nullable', 'numeric', 'min:0'],
        ]);

        $items = LineItemManager::normalise($validated['line_items']);

        return [
            'number' => $validated['number'] ?: ($proposal?->number ?? DocumentNumberGenerator::nextDocument(Proposal::class, 'PROP')),
            'proposal_date' => $validated['proposal_date'],
            'valid_until' => $validated['valid_until'],
            'entity_id' => $validated['entity_id'],
            'status' => $validated['status'],
            'line_items' => $items,
            'totals' => LineItemManager::totals($items),
        ];
    }

    private function validatedOrderPayload(Request $request, string $kind, ?Order $order = null): array
    {
        $validated = $request->validate([
            'number' => ['nullable', 'string', 'max:255'],
            'order_date' => ['required', 'date'],
            'valid_until' => ['nullable', 'date', 'after_or_equal:order_date'],
            'customer_entity_id' => [$kind === 'customer' ? 'required' : 'nullable', Rule::exists('entities', 'id')->where('is_customer', true)],
            'supplier_entity_id' => [$kind === 'supplier' ? 'required' : 'nullable', Rule::exists('entities', 'id')->where('is_supplier', true)],
            'status' => ['required', Rule::in(['draft', 'closed'])],
            'line_items' => ['required', 'array', 'min:1'],
            'line_items.*.article_id' => ['nullable', 'exists:articles,id'],
            'line_items.*.supplier_entity_id' => ['nullable', Rule::exists('entities', 'id')->where('is_supplier', true)],
            'line_items.*.reference' => ['nullable', 'string', 'max:255'],
            'line_items.*.name' => ['required', 'string', 'max:255'],
            'line_items.*.description' => ['nullable', 'string'],
            'line_items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'line_items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'line_items.*.cost_price' => ['nullable', 'numeric', 'min:0'],
            'line_items.*.vat_rate' => ['nullable', 'numeric', 'min:0'],
        ]);

        $items = LineItemManager::normalise($validated['line_items']);

        return [
            'number' => $validated['number'] ?: ($order?->number ?? DocumentNumberGenerator::nextDocument(Order::class, $kind === 'customer' ? 'ENCC' : 'ENCF')),
            'kind' => $kind,
            'order_date' => $validated['order_date'],
            'valid_until' => $validated['valid_until'] ?? null,
            'customer_entity_id' => $validated['customer_entity_id'] ?? null,
            'supplier_entity_id' => $validated['supplier_entity_id'] ?? null,
            'status' => $validated['status'],
            'line_items' => $items,
            'totals' => LineItemManager::totals($items),
        ];
    }

    private function validatedSupplierInvoice(Request $request): array
    {
        $validated = $request->validate([
            'number' => ['nullable', 'string', 'max:255'],
            'invoice_date' => ['required', 'date'],
            'due_date' => ['required', 'date', 'after_or_equal:invoice_date'],
            'supplier_entity_id' => ['required', Rule::exists('entities', 'id')->where('is_supplier', true)],
            'supplier_order_id' => ['nullable', 'exists:orders,id'],
            'total' => ['required', 'numeric', 'min:0'],
            'status' => ['required', Rule::in(['pending', 'paid'])],
            'document' => ['nullable', 'file', 'max:10240'],
            'payment_proof' => ['nullable', 'file', 'max:10240'],
        ]);

        return [
            'number' => $validated['number'] ?: DocumentNumberGenerator::nextDocument(SupplierInvoice::class, 'FF'),
            'invoice_date' => $validated['invoice_date'],
            'due_date' => $validated['due_date'],
            'supplier_entity_id' => $validated['supplier_entity_id'],
            'supplier_order_id' => $validated['supplier_order_id'] ?? null,
            'total' => number_format((float) $validated['total'], 2, '.', ''),
            'status' => $validated['status'],
        ];
    }

    private function sendPaymentProofIfNeeded(SupplierInvoice $invoice): void
    {
        if ($invoice->status !== 'paid' || ! $invoice->payment_proof_path) {
            return;
        }

        $supplier = $invoice->supplier()->first();

        if (! $supplier?->email) {
            return;
        }

        Mail::to($supplier->email)->send(new SupplierPaymentProofMail(
            invoice: $invoice->load('supplier'),
            company: CompanySetting::query()->first(),
            attachmentPath: $invoice->payment_proof_path,
        ));
    }

    private function transformProposal(Proposal $proposal): array
    {
        return [
            'id' => $proposal->id,
            'number' => $proposal->number,
            'proposal_date' => optional($proposal->proposal_date)->format('Y-m-d'),
            'valid_until' => optional($proposal->valid_until)->format('Y-m-d'),
            'entity_id' => $proposal->entity_id,
            'customer_name' => $proposal->customer?->name,
            'customer' => $this->entitySnapshot($proposal->customer),
            'status' => $proposal->status,
            'line_items' => $proposal->line_items ?? [],
            'totals' => $proposal->totals ?? [],
        ];
    }

    private function transformOrder(Order $order): array
    {
        return [
            'id' => $order->id,
            'number' => $order->number,
            'kind' => $order->kind,
            'order_date' => optional($order->order_date)->format('Y-m-d'),
            'valid_until' => optional($order->valid_until)->format('Y-m-d'),
            'customer_entity_id' => $order->customer_entity_id,
            'customer_name' => $order->customer?->name,
            'customer' => $this->entitySnapshot($order->customer),
            'supplier_entity_id' => $order->supplier_entity_id,
            'supplier_name' => $order->supplier?->name,
            'supplier' => $this->entitySnapshot($order->supplier),
            'status' => $order->status,
            'line_items' => $order->line_items ?? [],
            'totals' => $order->totals ?? [],
        ];
    }

    private function entitySnapshot(?Entity $entity): ?array
    {
        if (! $entity) {
            return null;
        }

        return [
            'name' => $entity->name,
            'nif' => $entity->nif,
            'address' => $entity->address,
            'postal_code' => $entity->postal_code,
            'city' => $entity->city,
            'country' => $entity->country?->name,
            'phone' => $entity->phone,
            'email' => $entity->email,
        ];
    }

    private function supplierOptions()
    {
        return Entity::query()->suppliers()->orderBy('number')->get()->map(fn (Entity $entity): array => [
            'id' => $entity->id,
            'label' => sprintf('%s - %s', $entity->number, $entity->name),
        ])->values();
    }

    private function articleOptions()
    {
        return Article::query()->with('vatRate')->where('is_active', true)->orderByDesc('id')->get()->map(fn (Article $article): array => [
            'id' => $article->id,
            'reference' => $article->reference,
            'name' => $article->name,
            'description' => $article->description,
            'price' => (float) $article->price,
            'vat_rate' => $article->vatRate?->rate ?? VatRate::query()->min('rate') ?? 0,
        ])->values();
    }
}
