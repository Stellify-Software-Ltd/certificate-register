<?php

use App\Models\Entity;
use App\Models\Record;
use App\Models\RecordType;

it('searches across type name, entity name, issuer and reference number', function () {
    $match = Record::factory()
        ->for(RecordType::factory()->create(['name' => 'Gas Safety (CP12)']), 'type')
        ->for(Entity::factory()->create(['name' => 'Unit 14 boiler']), 'entity')
        ->create(['issuer' => 'B. Hartley Heating', 'reference_number' => 'CP12-44721']);

    recordExpiringIn(50, overrides: ['issuer' => 'Someone Else', 'reference_number' => 'ZZ-0000']);

    foreach (['Gas Safety', 'Unit 14', 'Hartley', 'CP12-44721'] as $term) {
        $response = operator()->get('/records?q='.urlencode($term))->assertOk();
        $ids = $response->viewData('records')->pluck('id');

        expect($ids)->toContain($match->id)
            ->and($ids)->toHaveCount(1);
    }
});

it('filters by type, entity and responsible person', function () {
    $record = recordExpiringIn(50, overrides: ['responsible_person' => 'Karen Doyle']);
    $other = recordExpiringIn(50, overrides: ['responsible_person' => 'Mike Sutton']);

    $byType = operator()->get("/records?type={$record->record_type_id}")->assertOk();
    expect($byType->viewData('records')->pluck('id')->all())->toBe([$record->id]);

    $byEntity = operator()->get("/records?entity={$other->entity_id}")->assertOk();
    expect($byEntity->viewData('records')->pluck('id')->all())->toBe([$other->id]);

    $byPerson = operator()->get('/records?responsible=Karen')->assertOk();
    expect($byPerson->viewData('records')->pluck('id')->all())->toBe([$record->id]);
});

it('filters by derived status', function () {
    $expired = recordExpiringIn(-5);
    $soon = recordExpiringIn(10);
    $valid = recordExpiringIn(200);
    $never = Record::factory()->noExpiry()->create();

    $cases = [
        'expired' => $expired,
        'expiring_soon' => $soon,
        'valid' => $valid,
        'no_expiry' => $never,
    ];

    foreach ($cases as $status => $expected) {
        $ids = operator()->get("/records?status={$status}")
            ->assertOk()
            ->viewData('records')
            ->pluck('id')
            ->all();

        expect($ids)->toBe([$expected->id], "status filter {$status}");
    }
});

it('sorts by expiry both ways with no-expiry records always last', function () {
    $late = recordExpiringIn(300);
    $soon = recordExpiringIn(10);
    $never = Record::factory()->noExpiry()->create();

    $asc = operator()->get('/records?sort=expiry_asc')->viewData('records')->pluck('id')->all();
    expect($asc)->toBe([$soon->id, $late->id, $never->id]);

    $desc = operator()->get('/records?sort=expiry_desc')->viewData('records')->pluck('id')->all();
    expect($desc)->toBe([$late->id, $soon->id, $never->id]);
});

it('creates, edits and deletes a record through the forms', function () {
    operator()->post('/records', [
        'type_name' => 'Premises licence',
        'entity_name' => 'Whole site',
        'issue_date' => Record::today()->subYear()->toDateString(),
        'expiry_date' => '',
        'issuer' => 'Local authority licensing',
        'reference_number' => 'PL-0042',
        'notes' => 'No expiry on the licence itself.',
        'responsible_person' => 'Karen Doyle',
    ])->assertRedirect();

    $record = Record::sole();
    expect($record->expiry_date)->toBeNull();

    operator()->put("/records/{$record->id}", [
        'type_name' => 'Premises licence',
        'entity_name' => 'Whole site',
        'issue_date' => $record->issue_date->toDateString(),
        'expiry_date' => Record::today()->addYears(2)->toDateString(),
        'responsible_person' => 'Mike Sutton',
    ])->assertRedirect("/records/{$record->id}");

    $record->refresh();
    expect($record->responsible_person)->toBe('Mike Sutton')
        ->and($record->expiry_date)->not->toBeNull();

    operator()->delete("/records/{$record->id}")->assertRedirect('/records');
    expect(Record::count())->toBe(0);
});

it('rejects an expiry date before the issue date', function () {
    operator()->post('/records', [
        'type_name' => 'PAT testing',
        'entity_name' => 'Bar area',
        'issue_date' => Record::today()->toDateString(),
        'expiry_date' => Record::today()->subDay()->toDateString(),
    ])->assertSessionHasErrors('expiry_date');
});

it('never uses compliance language on the main screens', function () {
    recordExpiringIn(-5);
    recordExpiringIn(10);

    foreach (['/', '/records', '/types', '/records/new'] as $path) {
        $html = operator()->get($path)->assertOk()->getContent();

        expect(mb_strtolower($html))
            ->not->toContain('non-compliant', "found on {$path}")
            ->not->toContain('must be renewed', "found on {$path}")
            ->not->toContain('legally required', "found on {$path}")
            ->not->toContain('statutory', "found on {$path}");
    }
});
