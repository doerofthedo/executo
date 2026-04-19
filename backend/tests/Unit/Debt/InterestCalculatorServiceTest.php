<?php

declare(strict_types=1);

use App\Domain\Debt\Services\InterestCalculatorService;

test('simple interest calculation returns four decimal precision', function (): void {
    $service = new InterestCalculatorService();

    $result = $service->calculateSimple('1000.0000', '6.0000', 30);

    expect($result->interest)->toBe('4.9315');
    expect($result->total)->toBe('1004.9315');
});
