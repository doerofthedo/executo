<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

final class Customer extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'district_id',
        'ulid',
        'name',
        'case_number',
        'type',
        'email',
        'phone',
        'first_name',
        'last_name',
        'personal_code',
        'date_of_birth',
        'company_name',
        'registration_number',
        'contact_person',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::creating(static fn (self $customer): string => $customer->ulid ??= (string) Str::ulid());
    }

    public function getRouteKeyName(): string
    {
        return 'ulid';
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }

    public function debts(): HasMany
    {
        return $this->hasMany(Debt::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }
}
