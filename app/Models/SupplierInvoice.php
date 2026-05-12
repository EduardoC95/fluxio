<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierInvoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'number',
        'invoice_date',
        'due_date',
        'supplier_entity_id',
        'supplier_order_id',
        'total',
        'document_path',
        'payment_proof_path',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'invoice_date' => 'date',
            'due_date' => 'date',
            'total' => 'encrypted',
            'document_path' => 'encrypted',
            'payment_proof_path' => 'encrypted',
        ];
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Entity::class, 'supplier_entity_id');
    }

    public function supplierOrder(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'supplier_order_id');
    }
}
