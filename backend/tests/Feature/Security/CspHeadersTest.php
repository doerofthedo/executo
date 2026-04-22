<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('login page responds with csp header and matching nonce attributes', function (): void {
    $response = $this->get('/login');

    $response->assertOk();

    $csp = (string) $response->headers->get('Content-Security-Policy', '');

    expect($csp)->toContain("default-src 'self'")
        ->and($csp)->toContain("script-src 'self' 'nonce-")
        ->and($csp)->toContain("style-src 'self' 'nonce-");

    preg_match("/script-src 'self' 'nonce-([^']+)'/", $csp, $matches);
    $nonce = $matches[1] ?? null;

    expect($nonce)->not->toBeNull()
        ->and($response->getContent())->toContain(sprintf('nonce="%s"', $nonce));
});
