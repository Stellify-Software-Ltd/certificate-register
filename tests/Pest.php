<?php

use App\Models\Record;
use App\Models\RecordType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/**
 * Act as a signed-in operator (the register uses a session flag, set by
 * the operator-key sign-in).
 */
function operator(): TestCase
{
    return test()->withSession(['operator_authenticated' => true]);
}

/**
 * A record expiring in $days (negative = already expired), on a type
 * with the given warn_days threshold.
 */
function recordExpiringIn(int $days, int $warnDays = 30, array $overrides = []): Record
{
    return Record::factory()
        ->for(RecordType::factory()->state(['warn_days' => $warnDays]), 'type')
        ->expiringInDays($days)
        ->create($overrides);
}
