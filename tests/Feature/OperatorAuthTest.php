<?php

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

it('locks every screen behind the operator session', function (string $path) {
    $record = recordExpiringIn(10);
    $path = str_replace(
        ['{record}', '{entity}'],
        [(string) $record->id, (string) $record->entity_id],
        $path,
    );

    $this->get($path)->assertRedirect('/login');
})->with([
    '/',
    '/records',
    '/records/new',
    '/types',
    '/records/{record}',
    '/records/{record}/edit',
    '/records/{record}/renew',
    '/entities/{entity}',
]);

it('locks the write actions behind the operator session too', function () {
    $record = recordExpiringIn(10);

    $this->post('/records', [])->assertRedirect('/login');
    $this->put("/records/{$record->id}", [])->assertRedirect('/login');
    $this->delete("/records/{$record->id}")->assertRedirect('/login');
    $this->post("/records/{$record->id}/documents", [])->assertRedirect('/login');
    $this->post('/types', [])->assertRedirect('/login');
});

it('signs the operator in with the key and out again', function () {
    // Wrong key is rejected and logged.
    Log::shouldReceive('warning')->once()->withArgs(fn ($msg) => str_contains($msg, 'Rejected operator key'));
    $this->post('/login', ['key' => 'wrong'])->assertSessionHasErrors('key');

    // Right key opens the register.
    $this->post('/login', ['key' => 'test-operator-key'])->assertRedirect('/');
    $this->get('/')->assertOk()->assertSee('Dashboard');

    // Sign out ends it.
    $this->post('/logout')->assertRedirect('/login');
    $this->get('/')->assertRedirect('/login');
});

it('refuses to sign anyone in while no operator key is configured', function () {
    config(['register.operator_key' => '']);

    $this->post('/login', ['key' => ''])->assertSessionHasErrors('key');
    $this->post('/login', ['key' => 'anything'])->assertSessionHasErrors('key');
    $this->get('/')->assertRedirect('/login');
});

it('rate limits sign-in attempts per ip', function () {
    foreach (range(1, 5) as $i) {
        $this->post('/login', ['key' => 'wrong'])->assertSessionHasErrors('key');
    }

    $this->post('/login', ['key' => 'wrong'])->assertStatus(429);

    // Even the right key is refused while limited.
    $this->post('/login', ['key' => 'test-operator-key'])->assertStatus(429);

    RateLimiter::clear('operator-login:127.0.0.1');

    $this->post('/login', ['key' => 'test-operator-key'])->assertRedirect('/');
});

it('clears the limiter on a successful sign-in', function () {
    foreach (range(1, 4) as $i) {
        $this->post('/login', ['key' => 'wrong']);
    }

    $this->post('/login', ['key' => 'test-operator-key'])->assertRedirect('/');

    expect(RateLimiter::attempts('operator-login:127.0.0.1'))->toBe(0);
});
