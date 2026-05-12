<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\LogsFluxioActivity;
use App\Models\CalendarAction;
use App\Models\CalendarType;
use App\Models\CompanySetting;
use App\Models\ContactRole;
use App\Models\Country;
use App\Models\VatRate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Activitylog\Models\Activity;

class AdministrationController extends Controller
{
    use LogsFluxioActivity;

    public function lookups(string $tab = 'countries'): Response
    {
        abort_unless(array_key_exists($tab, $this->lookupMap()), 404);

        return Inertia::render('settings/Lookups', [
            'activeTab' => $tab,
            'tabs' => [
                ['key' => 'countries', 'label' => 'Entidades - Países'],
                ['key' => 'contact-roles', 'label' => 'Contactos - Funções'],
                ['key' => 'vat-rates', 'label' => 'Financeiro - IVA'],
                ['key' => 'calendar-types', 'label' => 'Calendário - Tipos'],
                ['key' => 'calendar-actions', 'label' => 'Calendário - Acções'],
            ],
            'datasets' => [
                $tab => $this->lookupDataset($tab),
            ],
            'endpoints' => [
                'store' => '/configuracoes/listas',
                'update' => '/configuracoes/listas',
                'delete' => '/configuracoes/listas',
            ],
        ]);
    }

    public function storeLookup(Request $request, string $tab): RedirectResponse
    {
        [$modelClass, $rules] = $this->resolveLookupConfig($tab);
        $validated = $request->validate($rules);

        /** @var Model $record */
        $record = $modelClass::query()->create($validated);

        $this->logFluxioActivity($request, 'Configurações', 'create', $record, ['tab' => $tab]);

        return back()->with($this->flashToast('success', 'Registo de configuração criado com sucesso.'));
    }

    public function updateLookup(Request $request, string $tab, int $recordId): RedirectResponse
    {
        [$modelClass, $rules] = $this->resolveLookupConfig($tab, $recordId);

        /** @var Model $record */
        $record = $modelClass::query()->findOrFail($recordId);
        $record->update($request->validate($rules));

        $this->logFluxioActivity($request, 'Configurações', 'update', $record, ['tab' => $tab]);

        return back()->with($this->flashToast('success', 'Registo de configuração atualizado com sucesso.'));
    }

    public function destroyLookup(Request $request, string $tab, int $recordId): RedirectResponse
    {
        [$modelClass] = $this->resolveLookupConfig($tab);

        /** @var Model $record */
        $record = $modelClass::query()->findOrFail($recordId);
        $record->delete();

        $this->logFluxioActivity($request, 'Configurações', 'delete', null, ['tab' => $tab]);

        return back()->with($this->flashToast('success', 'Registo de configuração removido com sucesso.'));
    }

    public function company(): Response
    {
        $company = CompanySetting::query()->firstOrCreate([]);

        return Inertia::render('settings/Company', [
            'record' => [
                'name' => $company->name,
                'address' => $company->address,
                'postal_code' => $company->postal_code,
                'city' => $company->city,
                'tax_number' => $company->tax_number,
                'logo_url' => $company->logo_path ? '/empresa/logo' : null,
            ],
            'endpoints' => [
                'update' => '/configuracoes/empresa',
            ],
        ]);
    }

    public function updateCompany(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string'],
            'postal_code' => ['nullable', 'regex:/^\d{4}-\d{3}$/'],
            'city' => ['nullable', 'string', 'max:255'],
            'tax_number' => ['nullable', 'string', 'max:255'],
            'logo' => ['nullable', 'file', 'image', 'max:5120'],
        ]);

        $company = CompanySetting::query()->firstOrCreate([]);
        $companyData = $validated;
        unset($companyData['logo']);

        $company->fill($companyData);

        if ($request->hasFile('logo')) {
            if ($company->logo_path) {
                Storage::disk('local')->delete($company->logo_path);
            }

            $company->logo_path = $request->file('logo')->store('company', 'local');
        }

        $company->save();

        $this->logFluxioActivity($request, 'Empresa', 'update', $company);

        return back()->with($this->flashToast('success', 'Dados da empresa atualizados com sucesso.'));
    }

    public function logs(): Response
    {
        $activities = Activity::query()->with('causer')->latest()->take(200)->get();

        return Inertia::render('Logs/Index', [
            'records' => $activities->map(fn (Activity $activity): array => [
                'id' => $activity->id,
                'date' => optional($activity->created_at)->format('Y-m-d'),
                'time' => optional($activity->created_at)->format('H:i:s'),
                'user' => $activity->causer?->name,
                'menu' => $activity->properties['menu'] ?? $activity->log_name,
                'action' => $activity->properties['action'] ?? $activity->description,
                'device' => $activity->properties['device'] ?? 'Desktop',
                'ip' => $this->maskIp($activity->properties['ip'] ?? null),
            ])->values(),
        ]);
    }

    public function placeholder(string $module): Response
    {
        return Inertia::render('Workspace/Placeholder', [
            'module' => str_replace('-', ' ', $module),
            'summary' => 'Este módulo ficou preparado na navegação e pode ser expandido sobre a mesma base visual e de segurança do Fluxio.',
        ]);
    }

    private function lookupMap(): array
    {
        return [
            'countries' => Country::class,
            'contact-roles' => ContactRole::class,
            'vat-rates' => VatRate::class,
            'calendar-types' => CalendarType::class,
            'calendar-actions' => CalendarAction::class,
        ];
    }

    private function lookupDataset(string $tab)
    {
        return match ($tab) {
            'countries' => Country::query()->orderBy('name')->get()->map(fn (Country $country): array => [
                'id' => $country->id,
                'name' => $country->name,
                'iso_code' => $country->iso_code,
                'phone_prefix' => $country->phone_prefix,
                'is_active' => $country->is_active,
            ])->values(),
            'contact-roles' => ContactRole::query()->orderBy('name')->get()->map(fn (ContactRole $role): array => [
                'id' => $role->id,
                'name' => $role->name,
                'description' => $role->description,
                'is_active' => $role->is_active,
            ])->values(),
            'vat-rates' => VatRate::query()->orderBy('rate')->get()->map(fn (VatRate $rate): array => [
                'id' => $rate->id,
                'name' => $rate->name,
                'rate' => $rate->rate,
                'is_active' => $rate->is_active,
            ])->values(),
            'calendar-types' => CalendarType::query()->orderBy('name')->get()->map(fn (CalendarType $type): array => [
                'id' => $type->id,
                'name' => $type->name,
                'color' => $type->color,
                'is_active' => $type->is_active,
            ])->values(),
            'calendar-actions' => CalendarAction::query()->orderBy('name')->get()->map(fn (CalendarAction $action): array => [
                'id' => $action->id,
                'name' => $action->name,
                'color' => $action->color,
                'is_active' => $action->is_active,
            ])->values(),
            default => collect(),
        };
    }

    private function maskIp(?string $ip): string
    {
        if (! $ip) {
            return 'n/a';
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return preg_replace('/\.\d+$/', '.xxx', $ip) ?? 'n/a';
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $parts = explode(':', $ip);

            return implode(':', array_slice($parts, 0, 4)).':xxxx:xxxx:xxxx:xxxx';
        }

        return 'n/a';
    }

    private function resolveLookupConfig(string $tab, ?int $recordId = null): array
    {
        return match ($tab) {
            'countries' => [Country::class, [
                'name' => ['required', 'string', 'max:255'],
                'iso_code' => ['required', 'string', 'size:2', Rule::unique('countries', 'iso_code')->ignore($recordId)],
                'phone_prefix' => ['nullable', 'string', 'max:255'],
                'is_active' => ['boolean'],
            ]],
            'contact-roles' => [ContactRole::class, [
                'name' => ['required', 'string', 'max:255', Rule::unique('contact_roles', 'name')->ignore($recordId)],
                'description' => ['nullable', 'string', 'max:255'],
                'is_active' => ['boolean'],
            ]],
            'vat-rates' => [VatRate::class, [
                'name' => ['required', 'string', 'max:255'],
                'rate' => ['required', 'numeric', 'min:0'],
                'is_active' => ['boolean'],
            ]],
            'calendar-types' => [CalendarType::class, [
                'name' => ['required', 'string', 'max:255', Rule::unique('calendar_types', 'name')->ignore($recordId)],
                'color' => ['nullable', 'string', 'max:255'],
                'is_active' => ['boolean'],
            ]],
            'calendar-actions' => [CalendarAction::class, [
                'name' => ['required', 'string', 'max:255', Rule::unique('calendar_actions', 'name')->ignore($recordId)],
                'color' => ['nullable', 'string', 'max:255'],
                'is_active' => ['boolean'],
            ]],
            default => abort(404),
        };
    }
}
