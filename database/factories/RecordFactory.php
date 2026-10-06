<?php

namespace Database\Factories;

use App\Models\Entity;
use App\Models\Record;
use App\Models\RecordType;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Record>
 */
class RecordFactory extends Factory
{
    public function definition(): array
    {
        return [
            'record_type_id' => RecordType::factory(),
            'entity_id' => Entity::factory(),
            'issue_date' => CarbonImmutable::now(config('app.timezone'))->subMonths(2)->toDateString(),
            'expiry_date' => CarbonImmutable::now(config('app.timezone'))->addMonths(10)->toDateString(),
            'issuer' => fake()->company(),
            'reference_number' => strtoupper(fake()->bothify('??-####')),
            'notes' => null,
            'responsible_person' => fake()->name(),
            'supersedes_id' => null,
        ];
    }

    public function expiringInDays(int $days): static
    {
        return $this->state(fn () => [
            'expiry_date' => CarbonImmutable::now(config('app.timezone'))->addDays($days)->toDateString(),
        ]);
    }

    public function expiredDaysAgo(int $days): static
    {
        return $this->state(fn () => [
            'expiry_date' => CarbonImmutable::now(config('app.timezone'))->subDays($days)->toDateString(),
        ]);
    }

    public function noExpiry(): static
    {
        return $this->state(fn () => ['expiry_date' => null]);
    }
}
