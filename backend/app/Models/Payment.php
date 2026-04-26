<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

final class Payment extends Model
{
    protected $fillable = [
        'ulid',
        'debtor_id',
        'debt_id',
        'amount',
        'date',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:4',
            'date' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::creating(static fn(self $payment): string => $payment->ulid ??= (string) Str::ulid());
    }

    public function getRouteKeyName(): string
    {
        return 'ulid';
    }

    /**
     * @return BelongsTo<Debtor, $this>
     */
    public function debtor(): BelongsTo
    {
        return $this->belongsTo(Debtor::class);
    }

    /**
     * @return BelongsTo<Debt, $this>
     */
    public function debt(): BelongsTo
    {
        return $this->belongsTo(Debt::class);
    }
}
