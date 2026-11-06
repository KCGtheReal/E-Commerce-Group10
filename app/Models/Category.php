<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'name_km',
        'slug',
        'description',
        'description_km',
        'image_url',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function getLocalizedNameAttribute(): string
    {
        if (app()->getLocale() === 'km' && ! empty($this->name_km)) {
            return $this->name_km;
        }

        return $this->name;
    }

    public function getLocalizedDescriptionAttribute(): ?string
    {
        if (app()->getLocale() === 'km' && ! empty($this->description_km)) {
            return $this->description_km;
        }

        return $this->description;
    }
}
