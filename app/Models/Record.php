<?php

namespace App\Models;

use App\Enums\RecordStatus;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Record extends Model
{
    /** @use HasFactory<\Database\Factories\RecordFactory> */
    use HasFactory;

    protected $fillable = [
        'record_type_id',
        'entity_id',
        'issue_date',
        'expiry_date',
        'issuer',
        'reference_number',
        'notes',
        'responsible_person',
        'supersedes_id',
    ];

    protected function casts(): array
    {
        return [
            'issue_date' => 'immutable_date',
            'expiry_date' => 'immutable_date',
        ];
    }

    /**
     * @return BelongsTo<RecordType, $this>
     */
    public function type(): BelongsTo
    {
        return $this->belongsTo(RecordType::class, 'record_type_id');
    }

    /**
     * @return BelongsTo<Entity, $this>
     */
    public function entity(): BelongsTo
    {
        return $this->belongsTo(Entity::class);
    }

    /**
     * The older record this one renews.
     *
     * @return BelongsTo<Record, $this>
     */
    public function supersedes(): BelongsTo
    {
        return $this->belongsTo(self::class, 'supersedes_id');
    }

    /**
     * The newer record that renewed this one, when there is one.
     *
     * @return HasOne<Record, $this>
     */
    public function supersededBy(): HasOne
    {
        return $this->hasOne(self::class, 'supersedes_id');
    }

    /**
     * @return HasMany<Document, $this>
     */
    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    /**
     * Records that have not been replaced by a renewal. Only these appear
     * on the dashboard and in the default register listing.
     *
     * @param  Builder<Record>  $query
     */
    public function scopeCurrent(Builder $query): void
    {
        $query->whereDoesntHave('supersededBy');
    }

    public function isSuperseded(): bool
    {
        return $this->supersededBy()->exists();
    }

    /**
     * Today as a calendar date in the application timezone. Expiry is a
     * date, not an instant: a certificate that expires today is not
     * expired yet, no matter what time the server clock shows in UTC.
     */
    public static function today(): CarbonImmutable
    {
        return CarbonImmutable::now(config('app.timezone'))->startOfDay();
    }

    /**
     * Derived, never stored. Computed from expiry_date against today's
     * date, with "expiring soon" controlled by the type's warn_days.
     */
    public function status(): RecordStatus
    {
        if ($this->expiry_date === null) {
            return RecordStatus::NoExpiry;
        }

        $today = self::today()->toDateString();
        $expiry = $this->expiry_date->toDateString();

        if ($expiry < $today) {
            return RecordStatus::Expired;
        }

        $warnDays = (int) ($this->type?->warn_days ?? 30);

        if ($expiry <= self::today()->addDays($warnDays)->toDateString()) {
            return RecordStatus::ExpiringSoon;
        }

        return RecordStatus::Valid;
    }

    /**
     * Whole days from today until expiry: positive = days left,
     * negative = days overdue, null = no expiry date.
     */
    public function daysUntilExpiry(): ?int
    {
        if ($this->expiry_date === null) {
            return null;
        }

        $expiry = CarbonImmutable::parse($this->expiry_date->toDateString(), config('app.timezone'));

        return (int) round(self::today()->diffInDays($expiry, absolute: false));
    }

    /**
     * The full renewal chain this record belongs to, oldest first.
     *
     * @return array<int, Record>
     */
    public function renewalChain(): array
    {
        $oldest = $this;

        while ($oldest->supersedes) {
            $oldest = $oldest->supersedes;
        }

        $chain = [];
        $current = $oldest;

        while ($current) {
            $chain[] = $current;
            $current = $current->supersededBy;
        }

        return $chain;
    }
}
