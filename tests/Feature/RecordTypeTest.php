<?php

use App\Enums\RecordStatus;
use App\Models\Record;
use App\Models\RecordType;

it('creates an unknown type with default settings when a record is saved', function () {
    operator()->post('/records', [
        'type_name' => 'Boiler pressure vessel inspection',
        'entity_name' => 'Pool plant room',
        'issue_date' => Record::today()->toDateString(),
        'expiry_date' => Record::today()->addYear()->toDateString(),
    ])->assertRedirect();

    $type = RecordType::where('name', 'Boiler pressure vessel inspection')->first();

    expect($type)->not->toBeNull()
        ->and($type->warn_days)->toBe(30)
        ->and($type->suggested_interval_months)->toBeNull();
});

it('reuses an existing type case-insensitively instead of creating a twin', function () {
    RecordType::factory()->create(['name' => 'PAT testing']);

    operator()->post('/records', [
        'type_name' => 'pat testing',
        'entity_name' => 'Bar area',
        'issue_date' => Record::today()->toDateString(),
    ])->assertRedirect();

    expect(RecordType::count())->toBe(1)
        ->and(Record::first()->type->name)->toBe('PAT testing');
});

it('reuses an existing entity by name too', function () {
    $record = recordExpiringIn(10);
    $entityCount = \App\Models\Entity::count();

    operator()->post('/records', [
        'type_name' => $record->type->name,
        'entity_name' => '  '.$record->entity->name.'  ',
        'issue_date' => Record::today()->toDateString(),
    ])->assertRedirect();

    expect(\App\Models\Entity::count())->toBe($entityCount);
});

it('changes derived statuses when a threshold is edited on the types screen', function () {
    $type = RecordType::factory()->create(['name' => 'Fire door check', 'warn_days' => 30]);
    $record = Record::factory()->for($type, 'type')->expiringInDays(45)->create();

    expect($record->status())->toBe(RecordStatus::Valid);

    operator()->put("/types/{$type->id}", [
        'name' => 'Fire door check',
        'warn_days' => 60,
        'suggested_interval_months' => 12,
    ])->assertRedirect();

    expect($record->fresh()->status())->toBe(RecordStatus::ExpiringSoon);
});

it('lists types with their record counts and allows adding one', function () {
    $type = RecordType::factory()->create(['name' => 'Legionella risk assessment']);
    Record::factory()->count(2)->for($type, 'type')->create();

    operator()->get('/types')
        ->assertOk()
        ->assertSee('Legionella risk assessment')
        ->assertSee('Suggested renewal interval');

    operator()->post('/types', [
        'name' => 'Sprinkler inspection',
        'warn_days' => 45,
        'suggested_interval_months' => 6,
    ])->assertRedirect();

    expect(RecordType::where('name', 'Sprinkler inspection')->exists())->toBeTrue();
});

it('rejects a duplicate type name on rename', function () {
    RecordType::factory()->create(['name' => 'F-Gas']);
    $type = RecordType::factory()->create(['name' => 'Calibration']);

    operator()->put("/types/{$type->id}", [
        'name' => 'F-Gas',
        'warn_days' => 30,
    ])->assertSessionHasErrors('name');
});
