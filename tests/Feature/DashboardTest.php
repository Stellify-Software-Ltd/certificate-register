<?php

use App\Models\Record;

it('buckets records at the fixed 30, 60 and 90 day boundaries', function () {
    $expired = recordExpiringIn(-3);
    $today = recordExpiringIn(0);
    $at30 = recordExpiringIn(30);
    $at31 = recordExpiringIn(31);
    $at60 = recordExpiringIn(60);
    $at61 = recordExpiringIn(61);
    $at90 = recordExpiringIn(90);
    $beyond = recordExpiringIn(91);
    $never = Record::factory()->noExpiry()->create();

    $buckets = operator()->get('/')->assertOk()->viewData('buckets');

    expect($buckets['expired']->pluck('id')->all())->toBe([$expired->id])
        ->and($buckets['within30']->pluck('id')->all())->toBe([$today->id, $at30->id])
        ->and($buckets['within60']->pluck('id')->all())->toBe([$at31->id, $at60->id])
        ->and($buckets['within90']->pluck('id')->all())->toBe([$at61->id, $at90->id]);

    $all = collect($buckets)->flatMap(fn ($bucket) => $bucket->pluck('id'));
    expect($all)->not->toContain($beyond->id)
        ->and($all)->not->toContain($never->id);
});

it('shows days over for expired and days left for upcoming records', function () {
    recordExpiringIn(-7, overrides: ['responsible_person' => 'Karen Doyle']);
    recordExpiringIn(12);

    operator()->get('/')
        ->assertOk()
        ->assertSee('7 over')
        ->assertSee('12 left')
        ->assertSee('Karen Doyle');
});

it('chips a record by its own type threshold even in a wider bucket', function () {
    // 45 days out, type warns at 60: sits in the within60 bucket but is
    // already "Expiring soon" by its own threshold.
    recordExpiringIn(45, warnDays: 60);
    // 45 days out, type warns at 30: same bucket, still "Valid".
    recordExpiringIn(45, warnDays: 30);

    operator()->get('/')
        ->assertOk()
        ->assertSee('Expiring soon')
        ->assertSee('Valid');
});

it('renders the seeded demo data', function () {
    \Illuminate\Support\Facades\Storage::fake('local');
    $this->seed();

    operator()->get('/')
        ->assertOk()
        ->assertSee('Expired')
        ->assertSee('Gas Safety (CP12)');

    operator()->get('/records')
        ->assertOk()
        ->assertSee('Premises licence');
});
