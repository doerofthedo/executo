<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

final class Payment extends Model
{
    protected $fillable = [
        'ulid',
        'debt_id',
        'amount',
        'payment_date',
        'reference',
        'recorded_by',
    ];

    protected static function booted(): void
    {
        static::creating(static fn (self $payment): string => $payment->ulid ??= (string) Str::ulid());
    }

    public function getRouteKeyName(): string
    {
        return 'ulid';
    }
}
