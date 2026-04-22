<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\Auth\AuthSessionController;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Session\ArraySessionHandler;
use Illuminate\Session\Store;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function (): void {
    Route::post('/_test/web-login', function (LoginRequest $request, AuthSessionController $controller) {
        $session = new Store('test-login', new ArraySessionHandler(120));
        $session->start();
        $session->put('from_test', true);
        $request->setLaravelSession($session);

        $before = $session->getId();
        $response = $controller->store($request);
        $response->headers->set('X-Session-Id-Before', $before);
        $response->headers->set('X-Session-Id-After', $session->getId());

        return $response;
    });

    Route::post('/_test/web-logout', function (Request $request, AuthSessionController $controller) {
        $session = new Store('test-logout', new ArraySessionHandler(120));
        $session->start();
        $session->put('foo', 'bar');
        $request->setLaravelSession($session);

        $before = $session->getId();
        $response = $controller->destroy($request);
        $response->headers->set('X-Session-Id-Before', $before);
        $response->headers->set('X-Session-Id-After', $session->getId());
        $response->headers->set('X-Session-Has-Foo', $session->has('foo') ? '1' : '0');

        return $response;
    });
});

test('login rotates session id when a session is present', function (): void {
    $user = User::query()->create([
        'name' => 'System',
        'surname' => 'User',
        'email' => 'session-login@executo.local',
        'password' => Hash::make('secret123'),
        'disabled' => false,
        'email_verified_at' => now(),
        'mfa_enabled' => false,
    ]);

    $response = $this
        ->postJson('/_test/web-login', [
            'login' => $user->email,
            'password' => 'secret123',
        ]);

    $response->assertOk();

    expect($response->headers->get('X-Session-Id-Before'))
        ->not->toBe($response->headers->get('X-Session-Id-After'));
});

test('logout invalidates the existing session and regenerates its identifier', function (): void {
    $user = User::query()->create([
        'name' => 'System',
        'surname' => 'User',
        'email' => 'session-logout@executo.local',
        'password' => Hash::make('secret123'),
        'disabled' => false,
        'email_verified_at' => now(),
        'mfa_enabled' => false,
    ]);

    $response = $this
        ->actingAs($user)
        ->postJson('/_test/web-logout');

    $response->assertNoContent();

    expect($response->headers->get('X-Session-Id-Before'))
        ->not->toBe($response->headers->get('X-Session-Id-After'))
        ->and($response->headers->get('X-Session-Has-Foo'))->toBe('0');
});
