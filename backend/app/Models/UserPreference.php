<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class UserPreference extends Model
{
    protected $fillable = [
        'user_id',
        'locale',
        'date_format',
        'decimal_separator',
        'thousand_separator',
        'table_page_size',
    ];

    protected function casts(): array
    {
        return [
            'table_page_size' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
