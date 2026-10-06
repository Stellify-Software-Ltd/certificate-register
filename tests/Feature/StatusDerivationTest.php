<?php

use App\Enums\RecordStatus;
use App\Models\Record;
use App\Models\RecordType;
use Illuminate\Support\Facades\Schema;

it('treats a record expiring today as expiring soon, never expired', function () {
    $record = recordExpiringIn(0);

    expect($record->status())->toBe(RecordStatus::ExpiringSoon)
        ->and($record->status())->not->toBe(RecordStatus::Expired)
        ->and($record->daysUntilExpiry())->toBe(0);
});

it('still does not call a record expired on its expiry day even with a zero threshold', function () {
    $record = recordExpiringIn(0, warnDays: 0);

    expect($record->status())->toBe(RecordStatus::ExpiringSoon);
});

it('marks a record expired the day after its expiry date', function () {
    $record = recordExpiringIn(-1);

    expect($record->status())->toBe(RecordStatus::Expired)
        ->and($record->daysUntilExpiry())->toBe(-1);
});

it('flags expiring soon exactly on the threshold day', function () {
    $record = recordExpiringIn(30, warnDays: 30);

    expect($record->status())->toBe(RecordStatus::ExpiringSoon);
});

it('stays valid one day past the threshold', function () {
    $record = recordExpiringIn(31, warnDays: 30);

    expect($record->status())->toBe(RecordStatus::Valid);
});

it('respects the per-type threshold', function () {
    $longWarning = recordExpiringIn(45, warnDays: 60);
    $shortWarning = recordExpiringIn(45, warnDays: 30);

    expect($longWarning->status())->toBe(RecordStatus::ExpiringSoon)
        ->and($shortWarning->status())->toBe(RecordStatus::Valid);
});

it('reports no expiry when there is no expiry date', function () {
    $record = Record::factory()->noExpiry()->create();

    expect($record->status())->toBe(RecordStatus::NoExpiry)
        ->and($record->daysUntilExpiry())->toBeNull();
});

it('never stores a status column', function () {
    $record = recordExpiringIn(10);

    expect(array_keys($record->getAttributes()))->not->toContain('status');
    expect(Schema::getColumnListing('records'))->not->toContain('status');
});

it('re-derives status when the type threshold changes', function () {
    $type = RecordType::factory()->create(['warn_days' => 30]);
    $record = Record::factory()->for($type, 'type')->expiringInDays(45)->create();

    expect($record->status())->toBe(RecordStatus::Valid);

    $type->update(['warn_days' => 60]);

    expect($record->fresh()->status())->toBe(RecordStatus::ExpiringSoon);
});
