<?php

use App\Enums\RecordStatus;
use App\Models\Record;
use App\Models\RecordType;
use Carbon\Carbon;

afterEach(fn () => Carbon::setTestNow());

/**
 * 23:30 UTC on 1 July is 00:30 BST on 2 July — the London calendar has
 * already turned over while the server clock (UTC) has not. Expiry is a
 * calendar date in the app timezone, so all derivation must follow the
 * London date, not the UTC one.
 */
function freezeLateUtcEveningDuringBst(): void
{
    config(['app.timezone' => 'Europe/London']);
    Carbon::setTestNow(Carbon::parse('2026-07-01 23:30:00', 'UTC'));
}

it('computes "expires today" against the London date during BST', function () {
    freezeLateUtcEveningDuringBst();

    // 2 July is "today" in London even though UTC still says 1 July.
    $record = Record::factory()
        ->for(RecordType::factory()->state(['warn_days' => 30]), 'type')
        ->create(['expiry_date' => '2026-07-02']);

    expect(Record::today()->toDateString())->toBe('2026-07-02')
        ->and($record->status())->toBe(RecordStatus::ExpiringSoon)
        ->and($record->status())->not->toBe(RecordStatus::Expired)
        ->and($record->daysUntilExpiry())->toBe(0);
});

it('has already expired a record dated yesterday in London, even though UTC is still on that date', function () {
    freezeLateUtcEveningDuringBst();

    // Under a naive UTC "today" this record would still count as live.
    $record = Record::factory()
        ->for(RecordType::factory()->state(['warn_days' => 30]), 'type')
        ->create(['expiry_date' => '2026-07-01']);

    expect($record->status())->toBe(RecordStatus::Expired)
        ->and($record->daysUntilExpiry())->toBe(-1);
});

it('keeps the expiring-today record out of the expired dashboard bucket at 23:30 UTC', function () {
    freezeLateUtcEveningDuringBst();

    $record = Record::factory()
        ->for(RecordType::factory()->state(['warn_days' => 30]), 'type')
        ->create(['expiry_date' => '2026-07-02']);

    $response = operator()->get('/');

    $response->assertOk();
    $buckets = $response->viewData('buckets');

    expect($buckets['expired']->pluck('id'))->not->toContain($record->id)
        ->and($buckets['within30']->pluck('id'))->toContain($record->id);
});
