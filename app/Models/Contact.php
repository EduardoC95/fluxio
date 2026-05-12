<?php

namespace App\Models;

use App\Support\SearchHash;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Contact extends Model
{
    use HasFactory;

    protected $fillable = [
        'number',
        'entity_id',
        'first_name',
        'last_name',
        'contact_role_id',
        'phone',
        'mobile',
        'email',
        'gdpr_consent',
        'notes',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'first_name' => 'encrypted',
            'last_name' => 'encrypted',
            'phone' => 'encrypted',
            'mobile' => 'encrypted',
            'email' => 'encrypted',
            'notes' => 'encrypted',
            'gdpr_consent' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $contact): void {
            $contact->email_hash = SearchHash::make($contact->email);
        });
    }

    public function entity(): BelongsTo
    {
        return $this->belongsTo(Entity::class);
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(ContactRole::class, 'contact_role_id');
    }
}
