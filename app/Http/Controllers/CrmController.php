<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\LogsFluxioActivity;
use App\Models\Article;
use App\Models\CalendarEvent;
use App\Models\Contact;
use App\Models\ContactRole;
use App\Models\Country;
use App\Models\Entity;
use App\Models\Order;
use App\Models\Proposal;
use App\Models\SupplierInvoice;
use App\Models\VatRate;
use App\Support\DocumentNumberGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class CrmController extends Controller
{
    use LogsFluxioActivity;

    public function dashboard(): Response
    {
        $proposals = Proposal::query()->with('customer')->latest()->take(5)->get();
        $pendingInvoices = SupplierInvoice::query()->with('supplier')->latest()->take(5)->get();
        $upcomingEvents = CalendarEvent::query()->with(['entity', 'user'])->orderBy('scheduled_for')->take(5)->get();

        return Inertia::render('Dashboard', [
            'stats' => [
                ['label' => 'Clientes', 'value' => Entity::query()->customers()->count()],
                ['label' => 'Fornecedores', 'value' => Entity::query()->suppliers()->count()],
                ['label' => 'Contactos', 'value' => Contact::query()->count()],
                ['label' => 'Artigos', 'value' => Article::query()->count()],
                ['label' => 'Propostas em aberto', 'value' => Proposal::query()->where('status', 'draft')->count()],
                ['label' => 'Encomendas fechadas', 'value' => Order::query()->where('status', 'closed')->count()],
            ],
            'proposalPipeline' => $proposals->map(fn (Proposal $proposal): array => [
                'id' => $proposal->id,
                'number' => $proposal->number,
                'customer' => $proposal->customer?->name,
                'status' => $proposal->status,
                'proposal_date' => optional($proposal->proposal_date)->format('Y-m-d'),
                'valid_until' => optional($proposal->valid_until)->format('Y-m-d'),
                'total' => (float) ($proposal->totals['total'] ?? 0),
            ])->values(),
            'pendingInvoices' => $pendingInvoices->map(fn (SupplierInvoice $invoice): array => [
                'id' => $invoice->id,
                'number' => $invoice->number,
                'supplier' => $invoice->supplier?->name,
                'status' => $invoice->status,
                'due_date' => optional($invoice->due_date)->format('Y-m-d'),
                'total' => (float) $invoice->total,
            ])->values(),
            'upcomingEvents' => $upcomingEvents->map(fn (CalendarEvent $event): array => [
                'id' => $event->id,
                'title' => $event->entity?->name ?: $event->user?->name,
                'scheduled_for' => optional($event->scheduled_for)->format('Y-m-d H:i'),
                'description' => $event->description,
            ])->values(),
        ]);
    }

    public function customers(): Response
    {
        return $this->entitiesPage('customers');
    }

    public function suppliers(): Response
    {
        return $this->entitiesPage('suppliers');
    }

    public function storeCustomer(Request $request): RedirectResponse
    {
        return $this->storeEntity($request, 'customers');
    }

    public function storeSupplier(Request $request): RedirectResponse
    {
        return $this->storeEntity($request, 'suppliers');
    }

    public function updateCustomer(Request $request, Entity $entity): RedirectResponse
    {
        return $this->updateEntity($request, $entity, 'customers');
    }

    public function updateSupplier(Request $request, Entity $entity): RedirectResponse
    {
        return $this->updateEntity($request, $entity, 'suppliers');
    }

    public function destroyCustomer(Request $request, Entity $entity): RedirectResponse
    {
        return $this->destroyEntity($request, $entity, 'customers');
    }

    public function destroySupplier(Request $request, Entity $entity): RedirectResponse
    {
        return $this->destroyEntity($request, $entity, 'suppliers');
    }

    public function contacts(): Response
    {
        $contacts = Contact::query()->with(['entity', 'role'])->latest()->get();

        return Inertia::render('Contacts/Index', [
            'records' => $contacts->map(fn (Contact $contact): array => [
                'id' => $contact->id,
                'number' => $contact->number,
                'first_name' => $contact->first_name,
                'last_name' => $contact->last_name,
                'role_id' => $contact->contact_role_id,
                'role_name' => $contact->role?->name,
                'entity_id' => $contact->entity_id,
                'entity_name' => $contact->entity?->name,
                'phone' => $contact->phone,
                'mobile' => $contact->mobile,
                'email' => $contact->email,
                'gdpr_consent' => $contact->gdpr_consent,
                'notes' => $contact->notes,
                'is_active' => $contact->is_active,
            ])->values(),
            'entities' => Entity::query()->orderBy('number')->get()->map(fn (Entity $entity): array => [
                'id' => $entity->id,
                'label' => sprintf('%s - %s', $entity->number, $entity->name),
            ])->values(),
            'roles' => ContactRole::query()->where('is_active', true)->orderBy('name')->get()->map(fn (ContactRole $role): array => [
                'id' => $role->id,
                'label' => $role->name,
            ])->values(),
            'defaults' => [
                'number' => DocumentNumberGenerator::nextNumeric(Contact::class),
                'gdpr_consent' => false,
                'is_active' => true,
            ],
            'endpoints' => [
                'store' => '/contactos',
                'update' => '/contactos',
                'delete' => '/contactos',
            ],
        ]);
    }

    public function storeContact(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'number' => ['nullable', 'integer', 'min:1'],
            'entity_id' => ['required', 'exists:entities,id'],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'contact_role_id' => ['nullable', 'exists:contact_roles,id'],
            'phone' => ['nullable', 'string', 'max:255'],
            'mobile' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'gdpr_consent' => ['boolean'],
            'notes' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ]);

        $contact = Contact::query()->create([
            ...$validated,
            'number' => $validated['number'] ?? DocumentNumberGenerator::nextNumeric(Contact::class),
            'gdpr_consent' => (bool) ($validated['gdpr_consent'] ?? false),
            'is_active' => (bool) ($validated['is_active'] ?? true),
        ]);

        $this->logFluxioActivity($request, 'Contactos', 'create', $contact);

        return back()->with($this->flashToast('success', 'Contacto criado com sucesso.'));
    }

    public function updateContact(Request $request, Contact $contact): RedirectResponse
    {
        $validated = $request->validate([
            'number' => ['required', 'integer', 'min:1'],
            'entity_id' => ['required', 'exists:entities,id'],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'contact_role_id' => ['nullable', 'exists:contact_roles,id'],
            'phone' => ['nullable', 'string', 'max:255'],
            'mobile' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'gdpr_consent' => ['boolean'],
            'notes' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ]);

        $contact->update([
            ...$validated,
            'gdpr_consent' => (bool) ($validated['gdpr_consent'] ?? false),
            'is_active' => (bool) ($validated['is_active'] ?? true),
        ]);

        $this->logFluxioActivity($request, 'Contactos', 'update', $contact);

        return back()->with($this->flashToast('success', 'Contacto atualizado com sucesso.'));
    }

    public function destroyContact(Request $request, Contact $contact): RedirectResponse
    {
        $contact->delete();

        $this->logFluxioActivity($request, 'Contactos', 'delete');

        return back()->with($this->flashToast('success', 'Contacto removido com sucesso.'));
    }

    public function articles(): Response
    {
        $articles = Article::query()->with('vatRate')->latest()->get();

        return Inertia::render('Articles/Index', [
            'records' => $articles->map(fn (Article $article): array => [
                'id' => $article->id,
                'reference' => $article->reference,
                'name' => $article->name,
                'description' => $article->description,
                'price' => (float) $article->price,
                'vat_rate_id' => $article->vat_rate_id,
                'vat_rate' => $article->vatRate?->rate,
                'notes' => $article->notes,
                'is_active' => $article->is_active,
                'photo_url' => $article->photo_path ? sprintf('/ativos/artigos/%d/foto', $article->id) : null,
            ])->values(),
            'vatRates' => VatRate::query()->where('is_active', true)->orderBy('rate')->get()->map(fn (VatRate $rate): array => [
                'id' => $rate->id,
                'label' => sprintf('%s%% - %s', $rate->rate, $rate->name),
                'value' => $rate->rate,
            ])->values(),
            'defaults' => [
                'is_active' => true,
            ],
            'endpoints' => [
                'store' => '/configuracoes/artigos',
                'update' => '/configuracoes/artigos',
                'delete' => '/configuracoes/artigos',
            ],
        ]);
    }

    public function storeArticle(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'reference' => ['required', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'vat_rate_id' => ['nullable', 'exists:vat_rates,id'],
            'photo' => ['nullable', 'file', 'image', 'max:5120'],
            'notes' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ]);

        $article = new Article([
            'reference' => $validated['reference'],
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'price' => number_format((float) $validated['price'], 2, '.', ''),
            'vat_rate_id' => $validated['vat_rate_id'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'is_active' => (bool) ($validated['is_active'] ?? true),
        ]);

        if ($request->hasFile('photo')) {
            $article->photo_path = $request->file('photo')->store('articles/photos', 'local');
        }

        $article->save();

        $this->logFluxioActivity($request, 'Artigos', 'create', $article);

        return back()->with($this->flashToast('success', 'Artigo criado com sucesso.'));
    }

    public function updateArticle(Request $request, Article $article): RedirectResponse
    {
        $validated = $request->validate([
            'reference' => ['required', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'vat_rate_id' => ['nullable', 'exists:vat_rates,id'],
            'photo' => ['nullable', 'file', 'image', 'max:5120'],
            'notes' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ]);

        $article->fill([
            'reference' => $validated['reference'],
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'price' => number_format((float) $validated['price'], 2, '.', ''),
            'vat_rate_id' => $validated['vat_rate_id'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'is_active' => (bool) ($validated['is_active'] ?? true),
        ]);

        if ($request->hasFile('photo')) {
            if ($article->photo_path) {
                Storage::disk('local')->delete($article->photo_path);
            }

            $article->photo_path = $request->file('photo')->store('articles/photos', 'local');
        }

        $article->save();

        $this->logFluxioActivity($request, 'Artigos', 'update', $article);

        return back()->with($this->flashToast('success', 'Artigo atualizado com sucesso.'));
    }

    public function destroyArticle(Request $request, Article $article): RedirectResponse
    {
        if ($article->photo_path) {
            Storage::disk('local')->delete($article->photo_path);
        }

        $article->delete();

        $this->logFluxioActivity($request, 'Artigos', 'delete');

        return back()->with($this->flashToast('success', 'Artigo removido com sucesso.'));
    }

    private function entitiesPage(string $mode): Response
    {
        $title = $mode === 'customers' ? 'Clientes' : 'Fornecedores';
        $records = Entity::query()
            ->with('country')
            ->when($mode === 'customers', fn ($query) => $query->customers())
            ->when($mode === 'suppliers', fn ($query) => $query->suppliers())
            ->latest()
            ->get();

        return Inertia::render('Entities/Index', [
            'mode' => $mode,
            'title' => $title,
            'records' => $records->map(fn (Entity $entity): array => [
                'id' => $entity->id,
                'number' => $entity->number,
                'nif' => $entity->nif,
                'name' => $entity->name,
                'address' => $entity->address,
                'postal_code' => $entity->postal_code,
                'city' => $entity->city,
                'country_id' => $entity->country_id,
                'country_name' => $entity->country?->name,
                'phone' => $entity->phone,
                'mobile' => $entity->mobile,
                'website' => $entity->website,
                'email' => $entity->email,
                'gdpr_consent' => $entity->gdpr_consent,
                'notes' => $entity->notes,
                'is_active' => $entity->is_active,
                'is_customer' => $entity->is_customer,
                'is_supplier' => $entity->is_supplier,
                'vies_payload' => $entity->vies_payload,
            ])->values(),
            'countries' => Country::query()->where('is_active', true)->orderBy('name')->get()->map(fn (Country $country): array => [
                'id' => $country->id,
                'label' => $country->name,
                'iso_code' => $country->iso_code,
            ])->values(),
            'defaults' => [
                'number' => DocumentNumberGenerator::nextNumeric(Entity::class),
                'gdpr_consent' => false,
                'is_active' => true,
                'is_customer' => $mode === 'customers',
                'is_supplier' => $mode === 'suppliers',
            ],
            'endpoints' => [
                'store' => $mode === 'customers' ? '/clientes' : '/fornecedores',
                'update' => $mode === 'customers' ? '/clientes' : '/fornecedores',
                'delete' => $mode === 'customers' ? '/clientes' : '/fornecedores',
                'vies' => '/integracoes/vies',
            ],
        ]);
    }

    private function storeEntity(Request $request, string $mode): RedirectResponse
    {
        $validated = $this->validateEntity($request);

        $entity = new Entity($validated);
        $entity->number = $validated['number'] ?? DocumentNumberGenerator::nextNumeric(Entity::class);
        $entity->is_customer = (bool) ($validated['is_customer'] ?? $mode === 'customers');
        $entity->is_supplier = (bool) ($validated['is_supplier'] ?? $mode === 'suppliers');
        $entity->gdpr_consent = (bool) ($validated['gdpr_consent'] ?? false);
        $entity->is_active = (bool) ($validated['is_active'] ?? true);
        $entity->save();

        $this->logFluxioActivity($request, $mode === 'customers' ? 'Clientes' : 'Fornecedores', 'create', $entity);

        return back()->with($this->flashToast('success', sprintf('%s criado com sucesso.', $mode === 'customers' ? 'Cliente' : 'Fornecedor')));
    }

    private function updateEntity(Request $request, Entity $entity, string $mode): RedirectResponse
    {
        $validated = $this->validateEntity($request, $entity);

        $entity->fill($validated);
        $entity->is_customer = (bool) ($validated['is_customer'] ?? $entity->is_customer);
        $entity->is_supplier = (bool) ($validated['is_supplier'] ?? $entity->is_supplier);
        $entity->gdpr_consent = (bool) ($validated['gdpr_consent'] ?? false);
        $entity->is_active = (bool) ($validated['is_active'] ?? true);
        $entity->save();

        $this->logFluxioActivity($request, $mode === 'customers' ? 'Clientes' : 'Fornecedores', 'update', $entity);

        return back()->with($this->flashToast('success', sprintf('%s atualizado com sucesso.', $mode === 'customers' ? 'Cliente' : 'Fornecedor')));
    }

    private function destroyEntity(Request $request, Entity $entity, string $mode): RedirectResponse
    {
        $entity->delete();

        $this->logFluxioActivity($request, $mode === 'customers' ? 'Clientes' : 'Fornecedores', 'delete');

        return back()->with($this->flashToast('success', sprintf('%s removido com sucesso.', $mode === 'customers' ? 'Cliente' : 'Fornecedor')));
    }

    private function validateEntity(Request $request, ?Entity $entity = null): array
    {
        $validated = $request->validate([
            'number' => ['nullable', 'integer', 'min:1'],
            'nif' => ['nullable', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string'],
            'postal_code' => ['nullable', 'regex:/^\d{4}-\d{3}$/'],
            'city' => ['nullable', 'string', 'max:255'],
            'country_id' => ['nullable', 'exists:countries,id'],
            'phone' => ['nullable', 'string', 'max:255'],
            'mobile' => ['nullable', 'string', 'max:255'],
            'website' => ['nullable', 'url', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'gdpr_consent' => ['boolean'],
            'notes' => ['nullable', 'string'],
            'is_active' => ['boolean'],
            'is_customer' => ['boolean'],
            'is_supplier' => ['boolean'],
        ]);

        $nif = $validated['nif'] ?? null;

        if ($nif) {
            $hash = \App\Support\SearchHash::make($nif);
            $exists = Entity::query()
                ->where('nif_hash', $hash)
                ->when($entity, fn ($query) => $query->whereKeyNot($entity->id))
                ->exists();

            if ($exists) {
                throw ValidationException::withMessages([
                    'nif' => 'O NIF indicado já existe.',
                ]);
            }
        }

        return $validated;
    }
}
