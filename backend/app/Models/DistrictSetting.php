<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class DistrictSetting extends Model
{
    protected $fillable = [
        'district_id',
        'locale',
        'date_format',
        'decimal_separator',
        'thousand_separator',
    ];

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }
}
