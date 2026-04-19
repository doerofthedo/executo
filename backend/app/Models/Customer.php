<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

final class Customer extends Model
{
    protected $fillable = [
        'ulid',
        'personal_code',
        'name',
        'surname',
        'email',
        'phone',
    ];

    protected static function booted(): void
    {
        static::creating(static fn (self $customer): string => $customer->ulid ??= (string) Str::ulid());
    }

    public function getRouteKeyName(): string
    {
        return 'ulid';
    }
}
