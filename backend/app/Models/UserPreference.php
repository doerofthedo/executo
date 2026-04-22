<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class UserPreference extends Model
{
    protected $fillable = [
        'user_id',
        'default_district_id',
        'locale',
        'date_format',
        'decimal_separator',
        'thousand_separator',
        'table_page_size',
    ];

    protected function casts(): array
    {
        return [
            'default_district_id' => 'integer',
            'table_page_size' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<District, $this>
     */
    public function defaultDistrict(): BelongsTo
    {
        return $this->belongsTo(District::class, 'default_district_id');
    }
}
