<?php

declare(strict_types=1);

namespace App\Models;

use App\Notifications\Auth\VerifyEmailNotification;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

final class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens;
    use HasRoles;
    use Notifiable;
    use SoftDeletes;

    protected $fillable = [
        'ulid',
        'name',
        'surname',
        'email',
        'disabled',
        'email_verified_at',
        'password',
        'mfa_secret',
        'mfa_enabled',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'mfa_secret',
    ];

    protected function casts(): array
    {
        return [
            'disabled' => 'boolean',
            'email_verified_at' => 'datetime',
            'mfa_enabled' => 'boolean',
            'mfa_secret' => 'encrypted',
            'password' => 'hashed',
        ];
    }

    protected static function booted(): void
    {
        static::creating(static fn (self $user): string => $user->ulid ??= (string) Str::ulid());
    }

    public function getRouteKeyName(): string
    {
        return 'ulid';
    }

    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new VerifyEmailNotification());
    }

    public function preference(): HasOne
    {
        return $this->hasOne(UserPreference::class);
    }
}
