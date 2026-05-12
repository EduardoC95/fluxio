<?php

namespace App\Http\Middleware;

use App\Models\CompanySetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $company = $this->resolveCompany();
        $user = $request->user();

        if ($user) {
            $user->loadMissing(['roles', 'permissions']);
        }

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'company' => $company ? [
                'name' => $company->name ?: config('app.name'),
                'address' => $company->address,
                'postal_code' => $company->postal_code,
                'city' => $company->city,
                'tax_number' => $company->tax_number,
                'logo_url' => route('company.logo'),
            ] : null,
            'auth' => [
                'user' => $user ? [
                    ...$user->toArray(),
                    'roles' => $user->getRoleNames()->values()->all(),
                    'permissions' => $user->getAllPermissions()->pluck('name')->sort()->values()->all(),
                ] : null,
            ],
            'flash' => [
                'toast' => fn () => $request->session()->get('toast'),
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }

    private function resolveCompany(): ?CompanySetting
    {
        if (! Schema::hasTable('company_settings')) {
            return null;
        }

        return CompanySetting::query()->first();
    }
}
