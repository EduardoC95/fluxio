<?php

namespace Tests\Feature\Settings;

use App\Models\CompanySetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class CompanySettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_company_settings_can_be_updated_with_a_logo(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $user->givePermissionTo(Permission::findOrCreate('empresa.update'));

        $response = $this
            ->actingAs($user)
            ->from(route('company.edit'))
            ->post(route('company.update'), [
                'name' => 'Fluxio Demo',
                'address' => 'Rua Central 10',
                'postal_code' => '1000-100',
                'city' => 'Lisboa',
                'tax_number' => '123456789',
                'logo' => UploadedFile::fake()->image('logo.png'),
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('company.edit'));

        $company = CompanySetting::query()->first();

        $this->assertNotNull($company);
        $this->assertSame('Fluxio Demo', $company->name);
        $this->assertSame('Rua Central 10', $company->address);
        $this->assertSame('1000-100', $company->postal_code);
        $this->assertSame('Lisboa', $company->city);
        $this->assertSame('123456789', $company->tax_number);
        $this->assertNotNull($company->logo_path);

        Storage::disk('local')->assertExists($company->logo_path);
    }
}
