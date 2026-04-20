<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

final class District extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'ulid',
        'number',
        'bailiff_name',
        'bailiff_surname',
        'court',
        'address',
        'disabled',
        'owner_id',
    ];

    protected function casts(): array
    {
        return [
            'disabled' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(static fn (self $district): string => $district->ulid ??= (string) Str::ulid());

        static::updated(static function (self $district): void {
            if (! $district->wasChanged('disabled') || ! $district->disabled) {
                return;
            }

            /** @var Collection<int, User> $users */
            $users = $district->users()
                ->with('roles')
                ->get()
                ->when(
                    $district->owner()->exists(),
                    static fn (Collection $collection): Collection => $collection->push($district->owner)
                )
                ->unique('id');

            $users
                ->reject(static fn (User $user): bool => $user->hasRole('app.admin'))
                ->each(static function (User $user): void {
                    if ($user->disabled) {
                        return;
                    }

                    $user->forceFill(['disabled' => true])->save();
                });
        });
    }

    public function getRouteKeyName(): string
    {
        return 'ulid';
    }

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot('role_id')
            ->withTimestamps();
    }

    public function setting(): HasOne
    {
        return $this->hasOne(DistrictSetting::class);
    }
}
