<?php

declare(strict_types=1);

namespace Darejer\Enums;

/**
 * Generic boolean flag for darejer DataTable / Display badges, filters, and
 * selects.
 *
 * Backed by the strings `'true'` and `'false'` to match the JSON form of
 * Eloquent boolean-cast attributes — the frontend looks up badge color and
 * label with `String(value)`, which yields `"true"`/`"false"` for JSON
 * booleans. Using these backings means `Column::badge(YesNo::class)`
 * resolves the variant and the translated label without any extra coercion.
 */
enum YesNo: string
{
    case Yes = 'true';
    case No = 'false';

    public function label(): string
    {
        return match ($this) {
            self::Yes => __('Yes'),
            self::No => __('No'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Yes => 'success',
            self::No => 'muted',
        };
    }
}
