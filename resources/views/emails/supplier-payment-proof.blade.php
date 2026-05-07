@php
    $companyName = $company?->name ?: config('app.name');
    $supplierName = $invoice->supplier?->name ?: 'Fornecedor';
    $embeddedLogo = null;

    if (isset($message) && $company?->logo_path && \Illuminate\Support\Facades\Storage::disk('local')->exists($company->logo_path)) {
        $embeddedLogo = $message->embed(\Illuminate\Support\Facades\Storage::disk('local')->path($company->logo_path));
    }
@endphp
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Comprovativo de Pagamento</title>
</head>
<body style="margin:0; background:#f5ecde; color:#2f261f; font-family:Arial, Helvetica, sans-serif;">
    <div style="max-width:640px; margin:0 auto; padding:32px 20px;">
        <div style="background:#565867; border-radius:20px 20px 0 0; padding:24px; color:#fff7ea;">
            @if ($embeddedLogo)
                <img src="{{ $embeddedLogo }}" alt="Logotipo" style="max-width:110px; max-height:56px; display:block; margin-bottom:16px;">
            @endif
            <div style="font-size:28px; font-weight:700;">{{ $companyName }}</div>
            <div style="font-size:13px; opacity:0.92; margin-top:8px;">Comprovativo de pagamento de fornecedor</div>
        </div>

        <div style="background:#fffaf1; border:1px solid #d8c5af; border-top:none; border-radius:0 0 20px 20px; padding:28px;">
            <p style="margin:0 0 16px;">Estimado(a) {{ $supplierName }},</p>
            <p style="margin:0 0 16px;">
                Enviamos em anexo o comprovativo de pagamento da fatura "<strong>{{ $invoice->number }}</strong>".
            </p>
            <p style="margin:0 0 24px;">
                Qualquer questão, entre em contacto connosco.
            </p>
            <p style="margin:0;">
                Cumprimentos,<br>
                <strong>{{ $companyName }}</strong>
            </p>
        </div>
    </div>
</body>
</html>
