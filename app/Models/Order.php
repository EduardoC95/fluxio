<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'number',
        'kind',
        'order_date',
        'valid_until',
        'customer_entity_id',
        'supplier_entity_id',
        'proposal_id',
        'source_order_id',
        'line_items',
        'totals',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'order_date' => 'date',
            'valid_until' => 'date',
            'line_items' => 'encrypted:array',
            'totals' => 'encrypted:array',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Entity::class, 'customer_entity_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Entity::class, 'supplier_entity_id');
    }

    public function proposal(): BelongsTo
    {
        return $this->belongsTo(Proposal::class);
    }

    public function sourceOrder(): BelongsTo
    {
        return $this->belongsTo(self::class, 'source_order_id');
    }
}
