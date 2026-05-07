<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\CompanySetting;
use App\Models\SupplierInvoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class IntegrationController extends Controller
{
    public function companyLogo(): StreamedResponse
    {
        $logoPath = CompanySetting::query()->first()?->logo_path;

        abort_unless($logoPath, 404);

        return Storage::disk('local')->response($logoPath);
    }

    public function articlePhoto(Article $article): StreamedResponse
    {
        abort_unless($article->photo_path, 404);

        return Storage::disk('local')->response($article->photo_path);
    }

    public function invoiceDocument(SupplierInvoice $supplierInvoice): StreamedResponse
    {
        abort_unless($supplierInvoice->document_path, 404);

        return Storage::disk('local')->response($supplierInvoice->document_path);
    }

    public function invoicePaymentProof(SupplierInvoice $supplierInvoice): StreamedResponse
    {
        abort_unless($supplierInvoice->payment_proof_path, 404);

        return Storage::disk('local')->response($supplierInvoice->payment_proof_path);
    }

    public function vies(Request $request)
    {
        $validated = $request->validate([
            'country_code' => ['required', 'string', 'size:2'],
            'vat_number' => ['required', 'string', 'max:32'],
        ]);

        $body = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:urn="urn:ec.europa.eu:taxud:vies:services:checkVat:types">
  <soapenv:Header/>
  <soapenv:Body>
    <urn:checkVat>
      <urn:countryCode>{$validated['country_code']}</urn:countryCode>
      <urn:vatNumber>{$validated['vat_number']}</urn:vatNumber>
    </urn:checkVat>
  </soapenv:Body>
</soapenv:Envelope>
XML;

        $response = Http::withBody($body, 'text/xml; charset=utf-8')
            ->accept('text/xml')
            ->timeout(20)
            ->post(config('services.vies.endpoint'));

        if (! $response->successful()) {
            return response()->json([
                'message' => 'O serviço VIES está temporariamente indisponível. Volte a tentar mais tarde.',
            ], 503);
        }

        $xml = new \DOMDocument();
        $xml->loadXML($response->body());
        $xpath = new \DOMXPath($xml);

        $fault = trim((string) $xpath->evaluate("string(//*[local-name()='faultstring'][1])"));

        if ($fault !== '') {
            return response()->json(['message' => $fault], 422);
        }

        return response()->json([
            'country_code' => trim((string) $xpath->evaluate("string(//*[local-name()='checkVatResponse']/*[local-name()='countryCode'][1])")),
            'vat_number' => trim((string) $xpath->evaluate("string(//*[local-name()='checkVatResponse']/*[local-name()='vatNumber'][1])")),
            'request_date' => trim((string) $xpath->evaluate("string(//*[local-name()='checkVatResponse']/*[local-name()='requestDate'][1])")),
            'valid' => trim((string) $xpath->evaluate("string(//*[local-name()='checkVatResponse']/*[local-name()='valid'][1])")) === 'true',
            'name' => trim((string) $xpath->evaluate("string(//*[local-name()='checkVatResponse']/*[local-name()='name'][1])")),
            'address' => trim((string) $xpath->evaluate("string(//*[local-name()='checkVatResponse']/*[local-name()='address'][1])")),
        ]);
    }
}
