<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Entity extends Model
{
    /** @use HasFactory<\Database\Factories\EntityFactory> */
    use HasFactory;

    protected $fillable = ['name', 'kind'];

    /**
     * @return HasMany<Record, $this>
     */
    public function records(): HasMany
    {
        return $this->hasMany(Record::class);
    }

    /**
     * Find an entity by (trimmed) name or create it, case-insensitively.
     */
    public static function resolveByName(string $name): self
    {
        $name = trim($name);

        $existing = static::query()->whereRaw('lower(name) = ?', [mb_strtolower($name)])->first();

        return $existing ?? static::create(['name' => $name]);
    }
}
