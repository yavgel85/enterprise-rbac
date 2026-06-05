<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\RoleFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Role extends Model
{
    /** @use HasFactory<RoleFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'parent_id',
        'name',
        'slug',
        'description',
        'is_system',
        'level',
    ];

    protected $attributes = [
        'is_system' => false,
        'level' => 0,
    ];

    protected function casts(): array
    {
        return [
            'is_system' => 'boolean',
            'level' => 'integer',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    /**
     * Returns this role plus every ancestor reachable through parent_id,
     * guarded against cycles and bounded by a sane depth limit.
     *
     * @return Collection<int, Role>
     */
    public function selfAndAncestors(int $maxDepth = 20): Collection
    {
        $chain = new Collection([$this]);
        $seen = [$this->id => true];
        $current = $this;
        $depth = 0;

        while ($current->parent_id !== null && $depth < $maxDepth) {
            if (isset($seen[$current->parent_id])) {
                break;
            }

            $parent = self::query()->withoutGlobalScopes()->find($current->parent_id);
            if ($parent === null) {
                break;
            }

            $chain->push($parent);
            $seen[$parent->id] = true;
            $current = $parent;
            $depth++;
        }

        return $chain;
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot(['assigned_by', 'assigned_at', 'expires_at'])
            ->withTimestamps();
    }
}
