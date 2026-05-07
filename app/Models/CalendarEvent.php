<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CalendarEvent extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'scheduled_for' => 'datetime',
            'description' => 'encrypted',
            'shared' => 'boolean',
            'knowledge' => 'boolean',
            'duration_minutes' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function entity(): BelongsTo
    {
        return $this->belongsTo(Entity::class);
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(CalendarType::class, 'calendar_type_id');
    }

    public function action(): BelongsTo
    {
        return $this->belongsTo(CalendarAction::class, 'calendar_action_id');
    }
}
