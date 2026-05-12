<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SupplierInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'number' => ['nullable', 'string', 'max:255'],
            'invoice_date' => ['required', 'date'],
            'due_date' => ['required', 'date', 'after_or_equal:invoice_date'],
            'supplier_entity_id' => ['required', Rule::exists('entities', 'id')->where('is_supplier', true)],
            'supplier_order_id' => ['nullable', 'exists:orders,id'],
            'total' => ['required', 'numeric', 'min:0'],
            'status' => ['required', Rule::in(['pending', 'paid'])],
            'document' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'],
            'payment_proof' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'],
        ];
    }

    public function payload(string $defaultNumber): array
    {
        $validated = $this->validated();

        return [
            'number' => $validated['number'] ?: $defaultNumber,
            'invoice_date' => $validated['invoice_date'],
            'due_date' => $validated['due_date'],
            'supplier_entity_id' => $validated['supplier_entity_id'],
            'supplier_order_id' => $validated['supplier_order_id'] ?? null,
            'total' => number_format((float) $validated['total'], 2, '.', ''),
            'status' => $validated['status'],
        ];
    }
}
