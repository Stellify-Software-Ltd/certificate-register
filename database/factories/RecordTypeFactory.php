<?php

namespace Database\Factories;

use App\Models\RecordType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RecordType>
 */
class RecordTypeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true).' certificate',
            'warn_days' => 30,
            'suggested_interval_months' => null,
        ];
    }
}
