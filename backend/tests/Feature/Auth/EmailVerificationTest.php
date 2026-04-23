<?php

declare(strict_types=1);

use App\Models\User;
use App\Notifications\Auth\VerifyEmailNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('verification email uses opaque frontend token link', function (): void {
    URL::forceRootUrl('http://executo.local');

    $user = User::query()->create([
        'name' => 'Krisjanis',
        'surname' => 'Feldmans',
        'email' => 'verify-link-test@executo.local',
        'password' => bcrypt('password123'),
        'disabled' => false,
        'email_verified_at' => null,
        'mfa_enabled' => false,
    ]);

    $mailMessage = (new VerifyEmailNotification())->toMail($user);
    $actionUrl = (string) $mailMessage->actionUrl;

    expect($actionUrl)->toStartWith('http://executo.local/verify-email?token=')
        ->and($actionUrl)->not->toContain('/api/');
});

test('verification token endpoint marks user email as verified', function (): void {
    URL::forceRootUrl('http://executo.local');

    $user = User::query()->create([
        'name' => 'Krisjanis',
        'surname' => 'Feldmans',
        'email' => 'verify-token-test@executo.local',
        'password' => bcrypt('password123'),
        'disabled' => false,
        'email_verified_at' => null,
        'mfa_enabled' => false,
    ]);

    $mailMessage = (new VerifyEmailNotification())->toMail($user);
    $actionUrl = (string) $mailMessage->actionUrl;
    parse_str((string) parse_url($actionUrl, PHP_URL_QUERY), $query);

    $response = $this->postJson('/api/v1/auth/email/verify-token', [
        'token' => $query['token'] ?? '',
    ]);

    $response
        ->assertOk()
        ->assertJsonPath('verified', true);

    expect($user->fresh()?->hasVerifiedEmail())->toBeTrue();
});
