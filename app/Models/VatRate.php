<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VatRate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'rate',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'rate' => 'float',
            'is_active' => 'boolean',
        ];
    }

    public function articles(): HasMany
    {
        return $this->hasMany(Article::class);
    }
}
