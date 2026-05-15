<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\FeatureFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Feature extends Model
{
    /** @use HasFactory<FeatureFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'default_enabled',
    ];

    protected $attributes = [
        'default_enabled' => false,
    ];

    protected function casts(): array
    {
        return [
            'default_enabled' => 'boolean',
        ];
    }

    public function tenants(): BelongsToMany
    {
        return $this->belongsToMany(Tenant::class)
            ->withPivot(['is_enabled', 'expires_at'])
            ->withTimestamps();
    }
}
