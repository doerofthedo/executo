<?php

declare(strict_types=1);

use App\Domain\Localization\Enums\Gender;
use App\Domain\Localization\Services\LatvianNameDeclensionService;

test('it converts common masculine and feminine first names to vocative', function (string $name, string $vocative): void {
    $service = new LatvianNameDeclensionService();

    expect($service->toVocative($name))->toBe($vocative);
})->with([
    ['Gatis', 'Gati'],
    ['Mārtiņš', 'Mārtiņ'],
    ['Markus', 'Markus'],
    ['Edgars', 'Edgar'],
    ['Adrija', 'Adrija'],
    ['Dace', 'Dace'],
    ['Sirds', 'Sirds'],
]);

test('it keeps everyday first-name vocative forms natural', function (): void {
    $service = new LatvianNameDeclensionService();

    expect($service->toVocative('Tētis'))->toBe('Tēti');
    expect($service->toVocative('Valdis'))->toBe('Valdi');
});

test('it leaves feminine names unchanged in vocative', function (): void {
    $service = new LatvianNameDeclensionService();

    expect($service->toVocative('Anna'))->toBe('Anna');
    expect($service->toVocative('Liene'))->toBe('Liene');
    expect($service->toVocative('Sirds'))->toBe('Sirds');
});

test('it supports an explicit gender override', function (): void {
    $service = new LatvianNameDeclensionService();

    expect($service->toVocative('Ilze', Gender::Masculine))->toBe('Ilze');
    expect($service->toVocative('Māris', Gender::Masculine))->toBe('Māri');
});

test('it trims empty input safely', function (): void {
    $service = new LatvianNameDeclensionService();

    expect($service->toVocative('   '))->toBe('');
});
