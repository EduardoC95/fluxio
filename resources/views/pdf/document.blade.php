@php
    $companyName = $company?->name ?: config('app.name');
    $issueDate = $document['proposal_date'] ?? $document['order_date'] ?? null;
    $validUntil = $document['valid_until'] ?? null;
    $party = $document['customer'] ?? $document['supplier'] ?? null;
    $lineItems = $document['line_items'] ?? [];
    $totals = $document['totals'] ?? [];
    $logoDataUri = null;

    if ($company?->logo_path && \Illuminate\Support\Facades\Storage::disk('local')->exists($company->logo_path)) {
        $logoPath = \Illuminate\Support\Facades\Storage::disk('local')->path($company->logo_path);
        $logoMime = mime_content_type($logoPath) ?: 'image/png';
        $logoDataUri = 'data:'.$logoMime.';base64,'.base64_encode(file_get_contents($logoPath));
    }
@endphp
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>{{ $documentType }} {{ $document['number'] ?? '' }}</title>
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: DejaVu Sans, sans-serif;
            color: #2f261f;
            background: #f5ecde;
            font-size: 12px;
            line-height: 1.5;
        }

        .page {
            padding: 28px;
        }

        .card {
            background: #fffaf1;
            border: 1px solid #d8c5af;
            border-radius: 18px;
            padding: 20px;
        }

        .header {
            margin-bottom: 18px;
            padding: 18px 20px;
            background: #565867;
            border-radius: 18px;
            color: #fff7ea;
        }

        .header-table,
        .meta-table,
        .party-table,
        .items-table,
        .totals-table {
            width: 100%;
            border-collapse: collapse;
        }

        .brand-title {
            margin: 0;
            font-size: 28px;
            font-weight: 700;
        }

        .brand-subtitle {
            margin: 6px 0 0;
            font-size: 11px;
            opacity: 0.9;
        }

        .logo {
            max-width: 110px;
            max-height: 64px;
            display: block;
            margin-left: auto;
        }

        .section-title {
            margin: 0 0 12px;
            font-size: 18px;
            font-weight: 700;
            color: #44362c;
        }

        .meta-table td {
            width: 50%;
            padding: 6px 0;
            vertical-align: top;
        }

        .meta-label,
        .party-label {
            display: block;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #7b6957;
            margin-bottom: 4px;
        }

        .party-box {
            margin-top: 16px;
            padding: 16px;
            background: #f2e6d5;
            border: 1px solid #dcc8b0;
            border-radius: 14px;
        }

        .items-table {
            margin-top: 18px;
        }

        .items-table th {
            padding: 10px 12px;
            background: #ead8c0;
            color: #44362c;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            border-bottom: 1px solid #d8c5af;
            text-align: left;
        }

        .items-table td {
            padding: 10px 12px;
            border-bottom: 1px solid #eadcc9;
            vertical-align: top;
        }

        .items-table tr:last-child td {
            border-bottom: none;
        }

        .align-right {
            text-align: right;
        }

        .muted {
            color: #7b6957;
        }

        .totals-wrap {
            margin-top: 18px;
        }

        .totals-table {
            margin-left: auto;
            width: 240px;
        }

        .totals-table td {
            padding: 6px 0;
        }

        .totals-table tr.total td {
            padding-top: 10px;
            border-top: 1px solid #d8c5af;
            font-size: 15px;
            font-weight: 700;
        }

        .footer {
            margin-top: 20px;
            color: #7b6957;
            font-size: 10px;
        }
    </style>
</head>
<body>
    <div class="page">
        <div class="header">
            <table class="header-table">
                <tr>
                    <td>
                        <p class="brand-title">{{ $companyName }}</p>
                        <p class="brand-subtitle">
                            {{ trim(collect([$company?->address, $company?->postal_code, $company?->city])->filter()->join(' · ')) }}
                        </p>
                    </td>
                    <td style="width: 130px;">
                        @if ($logoDataUri)
                            <img src="{{ $logoDataUri }}" alt="Logotipo" class="logo">
                        @endif
                    </td>
                </tr>
            </table>
        </div>

        <div class="card">
            <h1 class="section-title">{{ $documentType }} {{ $document['number'] ?? '' }}</h1>

            <table class="meta-table">
                <tr>
                    <td>
                        <span class="meta-label">Data do documento</span>
                        {{ $issueDate ?: 'n/d' }}
                    </td>
                    <td>
                        <span class="meta-label">Validade</span>
                        {{ $validUntil ?: 'n/d' }}
                    </td>
                </tr>
                <tr>
                    <td>
                        <span class="meta-label">Estado</span>
                        {{ ucfirst($document['status'] ?? 'draft') }}
                    </td>
                    <td>
                        <span class="meta-label">NIF empresa</span>
                        {{ $company?->tax_number ?: 'n/d' }}
                    </td>
                </tr>
            </table>

            @if ($party)
                <div class="party-box">
                    <table class="party-table">
                        <tr>
                            <td>
                                <span class="party-label">{{ ($documentType === 'Proposta' || ($document['kind'] ?? null) === 'customer') ? 'Cliente' : 'Entidade' }}</span>
                                <strong>{{ $party['name'] ?? 'n/d' }}</strong><br>
                                @if (!empty($party['nif']))
                                    NIF: {{ $party['nif'] }}<br>
                                @endif
                                @if (!empty($party['address']))
                                    {{ $party['address'] }}<br>
                                @endif
                                {{ trim(collect([$party['postal_code'] ?? null, $party['city'] ?? null, $party['country'] ?? null])->filter()->join(' · ')) }}<br>
                                @if (!empty($party['phone']))
                                    Tel: {{ $party['phone'] }}<br>
                                @endif
                                @if (!empty($party['email']))
                                    {{ $party['email'] }}
                                @endif
                            </td>
                            @if (!empty($document['supplier']))
                                <td>
                                    <span class="party-label">Fornecedor</span>
                                    <strong>{{ $document['supplier']['name'] ?? 'n/d' }}</strong><br>
                                    @if (!empty($document['supplier']['nif']))
                                        NIF: {{ $document['supplier']['nif'] }}<br>
                                    @endif
                                    @if (!empty($document['supplier']['address']))
                                        {{ $document['supplier']['address'] }}<br>
                                    @endif
                                    {{ trim(collect([$document['supplier']['postal_code'] ?? null, $document['supplier']['city'] ?? null, $document['supplier']['country'] ?? null])->filter()->join(' · ')) }}
                                </td>
                            @endif
                        </tr>
                    </table>
                </div>
            @endif

            <table class="items-table">
                <thead>
                    <tr>
                        <th style="width: 16%;">Referência</th>
                        <th style="width: 28%;">Artigo</th>
                        <th style="width: 12%;" class="align-right">Qtd.</th>
                        <th style="width: 14%;" class="align-right">Unitário</th>
                        <th style="width: 10%;" class="align-right">IVA</th>
                        <th style="width: 20%;" class="align-right">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($lineItems as $item)
                        <tr>
                            <td>{{ $item['reference'] ?? 'n/d' }}</td>
                            <td>
                                <strong>{{ $item['name'] ?? 'Sem designação' }}</strong>
                                @if (!empty($item['description']))
                                    <div class="muted">{{ $item['description'] }}</div>
                                @endif
                            </td>
                            <td class="align-right">{{ number_format((float) ($item['quantity'] ?? 0), 2, ',', '.') }}</td>
                            <td class="align-right">€ {{ number_format((float) ($item['unit_price'] ?? 0), 2, ',', '.') }}</td>
                            <td class="align-right">{{ number_format((float) ($item['vat_rate'] ?? 0), 2, ',', '.') }}%</td>
                            <td class="align-right">€ {{ number_format((float) ($item['total'] ?? 0), 2, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="muted">Sem linhas de artigo.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <div class="totals-wrap">
                <table class="totals-table">
                    <tr>
                        <td>Subtotal</td>
                        <td class="align-right">€ {{ number_format((float) ($totals['subtotal'] ?? 0), 2, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td>IVA</td>
                        <td class="align-right">€ {{ number_format((float) ($totals['tax_total'] ?? 0), 2, ',', '.') }}</td>
                    </tr>
                    <tr class="total">
                        <td>Total</td>
                        <td class="align-right">€ {{ number_format((float) ($totals['total'] ?? 0), 2, ',', '.') }}</td>
                    </tr>
                </table>
            </div>

            <div class="footer">
                Documento gerado automaticamente pelo Fluxio.
            </div>
        </div>
    </div>
</body>
</html>
