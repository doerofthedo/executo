<?php

declare(strict_types=1);

use App\Domain\Debt\Services\InterestCalculatorService;
use App\Models\Debtor;
use App\Models\District;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed();
});

test('district 69 import contains twelve debtors', function (): void {
    $district = District::query()
        ->where('number', 69)
        ->firstOrFail();

    expect($district->debtors()->count())->toBe(12);
});

test('Birkenfelde Ilze total debt on 2024-05-15 matches the legacy calculation', function (): void {
    $district = District::query()
        ->where('number', 69)
        ->firstOrFail();

    $debtor = Debtor::query()
        ->where('district_id', $district->id)
        ->where('name', 'Birkenfelde Ilze')
        ->firstOrFail();

    $debt = $debtor->debts()
        ->with('payments')
        ->firstOrFail();

    $schedule = app(InterestCalculatorService::class)->calculateSchedule(
        $debt,
        $debt->payments->sortBy('date')->values(),
        CarbonImmutable::parse('2024-05-15'),
    );

    expect($schedule->totalRow->totalDebt)->toBe('4799.25');
});
