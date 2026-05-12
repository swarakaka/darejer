<?php

declare(strict_types=1);

namespace Darejer\Concerns;

/**
 * Adds static helpers to a string-backed enum so it can drive UI surfaces
 * (selects, badges, filters) without hand-rolled value=>label arrays.
 *
 * Cases are expected to expose a `label(): string` instance method. A
 * `color(): string` method is optional — when present, `colors()` returns
 * a `[value => color]` map suitable for darejer's `Column::badge()`.
 */
trait HasOptions
{
    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        $out = [];
        foreach (self::cases() as $case) {
            $out[$case->value] = method_exists($case, 'label')
                ? $case->label()
                : (string) $case->value;
        }

        return $out;
    }

    /**
     * @return array<string, string>
     */
    public static function colors(): array
    {
        $out = [];
        foreach (self::cases() as $case) {
            if (method_exists($case, 'color')) {
                $out[$case->value] = $case->color();
            }
        }

        return $out;
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function selectOptions(): array
    {
        $out = [];
        foreach (self::cases() as $case) {
            $out[] = [
                'value' => (string) $case->value,
                'label' => method_exists($case, 'label') ? $case->label() : (string) $case->value,
            ];
        }

        return $out;
    }
}
