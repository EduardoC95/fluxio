<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompanySetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'address',
        'postal_code',
        'city',
        'tax_number',
        'logo_path',
    ];

    protected function casts(): array
    {
        return [
            'name' => 'encrypted',
            'address' => 'encrypted',
            'postal_code' => 'encrypted',
            'city' => 'encrypted',
            'tax_number' => 'encrypted',
            'logo_path' => 'encrypted',
        ];
    }
}
