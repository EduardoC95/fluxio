<?php

namespace App\Models;

use App\Support\SearchHash;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Entity extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'nif' => 'encrypted',
            'name' => 'encrypted',
            'address' => 'encrypted',
            'postal_code' => 'encrypted',
            'city' => 'encrypted',
            'phone' => 'encrypted',
            'mobile' => 'encrypted',
            'website' => 'encrypted',
            'email' => 'encrypted',
            'notes' => 'encrypted',
            'vies_payload' => 'encrypted:array',
            'gdpr_consent' => 'boolean',
            'is_active' => 'boolean',
            'is_customer' => 'boolean',
            'is_supplier' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $entity): void {
            $entity->nif_hash = SearchHash::make($entity->nif);
            $entity->email_hash = SearchHash::make($entity->email);
        });
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(Contact::class);
    }

    public function proposals(): HasMany
    {
        return $this->hasMany(Proposal::class);
    }

    public function customerOrders(): HasMany
    {
        return $this->hasMany(Order::class, 'customer_entity_id');
    }

    public function supplierOrders(): HasMany
    {
        return $this->hasMany(Order::class, 'supplier_entity_id');
    }

    public function supplierInvoices(): HasMany
    {
        return $this->hasMany(SupplierInvoice::class, 'supplier_entity_id');
    }

    public function scopeCustomers($query)
    {
        return $query->where('is_customer', true);
    }

    public function scopeSuppliers($query)
    {
        return $query->where('is_supplier', true);
    }
}
