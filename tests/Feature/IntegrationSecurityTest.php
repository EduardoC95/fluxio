<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\CompanySetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class IntegrationSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_company_logo_requires_authentication(): void
    {
        $this->get(route('company.logo'))->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_read_company_logo(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('company/logo.png', 'logo');
        CompanySetting::query()->create(['logo_path' => 'company/logo.png']);

        $this->actingAs(User::factory()->create())
            ->get(route('company.logo'))
            ->assertOk();
    }

    public function test_vies_payload_escapes_xml_values(): void
    {
        $capturedBody = null;

        Http::fake(function ($request) use (&$capturedBody) {
            $capturedBody = $request->body();

            return Http::response(<<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
  <soap:Body>
    <checkVatResponse>
      <countryCode>PT</countryCode>
      <vatNumber>123</vatNumber>
      <requestDate>2026-05-12</requestDate>
      <valid>true</valid>
      <name>Teste</name>
      <address>Rua</address>
    </checkVatResponse>
  </soap:Body>
</soap:Envelope>
XML);
        });

        $this->actingAs(User::factory()->create())
            ->postJson(route('integrations.vies'), [
                'country_code' => 'PT',
                'vat_number' => '1&<x/>',
            ])
            ->assertOk();

        $this->assertStringContainsString('1&amp;&lt;x/&gt;', $capturedBody);
        $this->assertStringNotContainsString('<x/>', $capturedBody);
    }

    public function test_article_asset_requires_read_permission(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('articles/photos/test.png', 'image');
        $article = Article::query()->create([
            'reference' => 'A-1',
            'name' => 'Artigo',
            'price' => 10,
            'photo_path' => 'articles/photos/test.png',
            'is_active' => true,
        ]);

        $this->actingAs(User::factory()->create())
            ->get(route('assets.article-photo', $article))
            ->assertForbidden();

        $authorized = User::factory()->create();
        Permission::findOrCreate('artigos.read', 'web');
        $authorized->givePermissionTo('artigos.read');

        $this->actingAs($authorized)
            ->get(route('assets.article-photo', $article))
            ->assertOk();
    }
}
