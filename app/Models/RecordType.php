<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RecordType extends Model
{
    /** @use HasFactory<\Database\Factories\RecordTypeFactory> */
    use HasFactory;

    protected $fillable = ['name', 'warn_days', 'suggested_interval_months'];

    protected function casts(): array
    {
        return [
            'warn_days' => 'integer',
            'suggested_interval_months' => 'integer',
        ];
    }

    /**
     * @return HasMany<Record, $this>
     */
    public function records(): HasMany
    {
        return $this->hasMany(Record::class);
    }

    /**
     * Find a type by (trimmed) name or create it with default settings.
     * Lookup is case-insensitive so "pat testing" doesn't spawn a twin.
     */
    public static function resolveByName(string $name): self
    {
        $name = trim($name);

        $existing = static::query()->whereRaw('lower(name) = ?', [mb_strtolower($name)])->first();

        return $existing ?? static::create(['name' => $name, 'warn_days' => 30]);
    }
}
