<?php

declare(strict_types=1);

namespace App\Domain\Localization\Enums;

enum Gender: string
{
    case Masculine = 'masculine';
    case Feminine = 'feminine';
    case Common = 'common';
}
