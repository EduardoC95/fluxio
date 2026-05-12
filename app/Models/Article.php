<?php

namespace App\Models;

use App\Support\SearchHash;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Article extends Model
{
    use HasFactory;

    protected $fillable = [
        'reference',
        'name',
        'description',
        'price',
        'vat_rate_id',
        'photo_path',
        'notes',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'reference' => 'encrypted',
            'name' => 'encrypted',
            'description' => 'encrypted',
            'price' => 'encrypted',
            'photo_path' => 'encrypted',
            'notes' => 'encrypted',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $article): void {
            $article->reference_hash = SearchHash::make($article->reference);
        });
    }

    public function vatRate(): BelongsTo
    {
        return $this->belongsTo(VatRate::class);
    }
}
