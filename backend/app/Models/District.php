<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

final class District extends Model
{
    protected $fillable = [
        'ulid',
        'number',
        'owner_id',
    ];

    protected static function booted(): void
    {
        static::creating(static fn (self $district): string => $district->ulid ??= (string) Str::ulid());
    }

    public function getRouteKeyName(): string
    {
        return 'ulid';
    }
}
