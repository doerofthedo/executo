export function formatLatvianVocativeFirstName(name: string): string {
    const trimmedName = name.trim();

    if (trimmedName === '') {
        return '';
    }

    const normalizedName = trimmedName.toLocaleLowerCase('lv-LV');
    const vocative = toVocative(normalizedName);

    return matchInputCasing(trimmedName, vocative);
}

function toVocative(name: string): string {
    if (name.endsWith('is')) {
        return buildSecondDeclensionVocative(name);
    }

    if (name.endsWith('us')) {
        return name;
    }

    if ((name.endsWith('s') || name.endsWith('š')) && !shouldKeepTerminalS(name)) {
        return name.slice(0, -1);
    }

    return name;
}

function buildSecondDeclensionVocative(name: string): string {
    return `${name.slice(0, -2)}i`;
}

function shouldKeepTerminalS(name: string): boolean {
    return name.endsWith('ds')
        || name.endsWith('ts')
        || name.endsWith('ss')
        || name.endsWith('zs')
        || name.endsWith('cs');
}

function matchInputCasing(input: string, value: string): string {
    const firstCharacter = input.slice(0, 1);

    if (firstCharacter !== '' && firstCharacter === firstCharacter.toLocaleUpperCase('lv-LV')) {
        return `${value.slice(0, 1).toLocaleUpperCase('lv-LV')}${value.slice(1)}`;
    }

    return value;
}
