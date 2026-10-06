<?php

use App\Models\Entity;
use App\Models\Record;
use App\Models\RecordType;
use Carbon\CarbonImmutable;

function renewViaForm(Record $old, array $overrides = []): Record
{
    $today = Record::today();

    operator()->post('/records', array_merge([
        'type_name' => $old->type->name,
        'entity_name' => $old->entity->name,
        'issue_date' => $today->toDateString(),
        'expiry_date' => $today->addYear()->toDateString(),
        'issuer' => $old->issuer,
        'responsible_person' => $old->responsible_person,
        'supersedes_id' => $old->id,
    ], $overrides))->assertSessionHasNoErrors()->assertRedirect();

    return Record::query()->latest('id')->firstOrFail();
}

it('creates a renewal linked to the old record', function () {
    $old = recordExpiringIn(-5);

    $new = renewViaForm($old);

    expect($new->supersedes_id)->toBe($old->id)
        ->and($old->fresh()->isSuperseded())->toBeTrue()
        ->and($new->record_type_id)->toBe($old->record_type_id)
        ->and($new->entity_id)->toBe($old->entity_id);
});

it('pre-fills the renew form from the old record and its suggested interval', function () {
    $type = RecordType::factory()->create([
        'name' => 'Boiler service',
        'suggested_interval_months' => 12,
    ]);
    $old = Record::factory()
        ->for($type, 'type')
        ->for(Entity::factory()->create(['name' => 'Unit 14 boiler']), 'entity')
        ->expiringInDays(10)
        ->create(['issuer' => 'B. Hartley Heating', 'responsible_person' => 'Mike Sutton']);

    $expectedExpiry = Record::today()->addMonths(12)->toDateString();

    operator()->get("/records/{$old->id}/renew")
        ->assertOk()
        ->assertSee('Boiler service')
        ->assertSee('Unit 14 boiler')
        ->assertSee('B. Hartley Heating')
        ->assertSee('Mike Sutton')
        ->assertSee($expectedExpiry)
        ->assertSee('suggested renewal interval');
});

it('excludes superseded records from every dashboard bucket', function () {
    $old = recordExpiringIn(-10); // would land in "expired"
    renewViaForm($old, ['expiry_date' => Record::today()->addDays(20)->toDateString()]);

    $response = operator()->get('/');
    $buckets = $response->viewData('buckets');

    foreach (['expired', 'within30', 'within60', 'within90'] as $bucket) {
        expect($buckets[$bucket]->pluck('id'))->not->toContain($old->id);
    }
});

it('hides superseded records from the default register but shows them with the toggle', function () {
    $old = recordExpiringIn(-10, overrides: ['reference_number' => 'OLD-REF-123']);
    renewViaForm($old);

    operator()->get('/records')->assertOk()->assertDontSee('OLD-REF-123');
    operator()->get('/records?superseded=1')->assertOk()->assertSee('OLD-REF-123');
});

it('keeps the superseded record reachable from the detail pages and entity history', function () {
    $old = recordExpiringIn(-10, overrides: ['reference_number' => 'OLD-REF-456']);
    $new = renewViaForm($old);

    // Old record still viewable, pointing forward to its replacement.
    operator()->get("/records/{$old->id}")
        ->assertOk()
        ->assertSee('has been renewed')
        ->assertSee("/records/{$new->id}");

    // New record points back.
    operator()->get("/records/{$new->id}")
        ->assertOk()
        ->assertSee('Renewal history')
        ->assertSee("/records/{$old->id}");

    // Entity history lists both.
    operator()->get("/entities/{$old->entity_id}")
        ->assertOk()
        ->assertSee("/records/{$old->id}")
        ->assertSee("/records/{$new->id}")
        ->assertSee('Renewed');
});

it('renders a chain of three in full on the newest record', function () {
    $first = recordExpiringIn(-400);
    $second = renewViaForm($first, [
        'issue_date' => Record::today()->subDays(395)->toDateString(),
        'expiry_date' => Record::today()->subDays(30)->toDateString(),
    ]);
    $third = renewViaForm($second);

    $response = operator()->get("/records/{$third->id}")->assertOk();

    $response->assertSee('Renewal history')
        ->assertSee("/records/{$first->id}")
        ->assertSee("/records/{$second->id}");

    expect($third->fresh()->renewalChain())
        ->toHaveCount(3)
        ->and(collect($third->fresh()->renewalChain())->pluck('id')->all())
        ->toBe([$first->id, $second->id, $third->id]);
});

it('refuses to renew a record that has already been renewed', function () {
    $old = recordExpiringIn(-10);
    renewViaForm($old);

    // The renew form redirects to the replacement…
    operator()->get("/records/{$old->id}/renew")->assertRedirect();

    // …and a forced POST is rejected.
    $countBefore = Record::count();

    operator()->post('/records', [
        'type_name' => $old->type->name,
        'entity_name' => $old->entity->name,
        'issue_date' => Record::today()->toDateString(),
        'supersedes_id' => $old->id,
    ])->assertSessionHasErrors('supersedes_id');

    expect(Record::count())->toBe($countBefore);
});

it('pre-fills the renew expiry only when the type has a suggested interval', function () {
    $type = RecordType::factory()->create(['suggested_interval_months' => null]);
    $old = Record::factory()->for($type, 'type')->expiringInDays(5)->create();

    $response = operator()->get("/records/{$old->id}/renew")->assertOk();

    expect($response->viewData('prefill')['expiry_date'])->toBeNull();
});
