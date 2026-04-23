<?php

declare(strict_types=1);

namespace App\Domain\Localization\Services;

use App\Domain\Localization\Enums\Gender;

final readonly class LatvianNameDeclensionService
{
    public function toVocative(string $name, ?Gender $gender = null): string
    {
        $trimmedName = trim($name);

        if ($trimmedName === '') {
            return '';
        }

        $normalizedName = mb_strtolower($trimmedName);
        $resolvedGender = $gender ?? $this->inferGender($normalizedName);
        $vocative = match ($resolvedGender) {
            Gender::Masculine => $this->toMasculineVocative($normalizedName),
            Gender::Feminine, Gender::Common => $this->toFeminineVocative($normalizedName),
        };

        return $this->matchInputCasing($trimmedName, $vocative);
    }

    private function inferGender(string $name): Gender
    {
        if ($this->endsWith($name, 'a') || $this->endsWith($name, 'e')) {
            return Gender::Feminine;
        }

        return Gender::Masculine;
    }

    private function toMasculineVocative(string $name): string
    {
        if ($this->endsWith($name, 'is')) {
            return $this->buildSecondDeclensionVocative($name);
        }

        if ($this->endsWith($name, 'us')) {
            return $name;
        }

        if (($this->endsWith($name, 's') || $this->endsWith($name, 'š')) && ! $this->shouldKeepTerminalS($name)) {
            return mb_substr($name, 0, -1);
        }

        return $name;
    }

    private function toFeminineVocative(string $name): string
    {
        return $name;
    }

    private function buildSecondDeclensionVocative(string $name): string
    {
        return mb_substr($name, 0, -2) . 'i';
    }

    private function shouldKeepTerminalS(string $name): bool
    {
        return $this->endsWith($name, 'ds')
            || $this->endsWith($name, 'ts')
            || $this->endsWith($name, 'ss')
            || $this->endsWith($name, 'zs')
            || $this->endsWith($name, 'cs');
    }

    private function endsWith(string $value, string $suffix): bool
    {
        return mb_substr($value, -mb_strlen($suffix)) === $suffix;
    }

    private function matchInputCasing(string $input, string $value): string
    {
        $firstCharacter = mb_substr($input, 0, 1);

        if ($firstCharacter !== '' && mb_strtoupper($firstCharacter) === $firstCharacter) {
            return mb_strtoupper(mb_substr($value, 0, 1)) . mb_substr($value, 1);
        }

        return $value;
    }
}
