<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Proposal extends Model
{
    use HasFactory;

    protected $fillable = [
        'number',
        'proposal_date',
        'valid_until',
        'entity_id',
        'line_items',
        'totals',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'proposal_date' => 'date',
            'valid_until' => 'date',
            'line_items' => 'encrypted:array',
            'totals' => 'encrypted:array',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Entity::class, 'entity_id');
    }
}
