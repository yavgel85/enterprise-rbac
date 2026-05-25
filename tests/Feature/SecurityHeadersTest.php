<?php

declare(strict_types=1);

it('adds baseline security headers to every web response', function () {
    $response = $this->get('/login');

    $response->assertSuccessful();
    $response->assertHeader('X-Content-Type-Options', 'nosniff');
    $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
    $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');

    expect($response->headers->get('Permissions-Policy'))
        ->toContain('camera=()')
        ->toContain('microphone=()')
        ->toContain('geolocation=()');
});

it('does not set HSTS over plain HTTP in non-production', function () {
    $response = $this->get('/login');

    expect($response->headers->has('Strict-Transport-Security'))->toBeFalse();
});
