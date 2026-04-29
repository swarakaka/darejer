<?php

declare(strict_types=1);

namespace Darejer\Support;

use BackedEnum;

/**
 * Normalises enum class strings into the [value => label] / [value => color]
 * arrays expected by Column/Filter/SelectComponent.
 *
 * Backing convention:
 * - The enum is a backed enum.
 * - For labels, the enum exposes a `label(): string` method or a static
 *   `options(): array<string, string>` returning [value => label].
 * - For colors, the enum exposes a `color(): string` method or a static
 *   `colors(): array<string, string>` returning [value => color].
 */
final class EnumOptions
{
    /**
     * @param  class-string<BackedEnum>|array<string, string>  $source
     * @return array<string, string>
     */
    public static function labels(string|array $source): array
    {
        if (is_array($source)) {
            return $source;
        }

        if (! is_subclass_of($source, BackedEnum::class)) {
            return [];
        }

        if (method_exists($source, 'options')) {
            return $source::options();
        }

        $out = [];
        foreach ($source::cases() as $case) {
            $out[(string) $case->value] = method_exists($case, 'label')
                ? $case->label()
                : (string) $case->value;
        }

        return $out;
    }

    /**
     * @param  class-string<BackedEnum>|array<string, string>  $source
     * @return array<string, string>
     */
    public static function colors(string|array $source): array
    {
        if (is_array($source)) {
            return $source;
        }

        if (! is_subclass_of($source, BackedEnum::class)) {
            return [];
        }

        if (method_exists($source, 'colors')) {
            return $source::colors();
        }

        $out = [];
        foreach ($source::cases() as $case) {
            if (method_exists($case, 'color')) {
                $out[(string) $case->value] = $case->color();
            }
        }

        return $out;
    }
}
