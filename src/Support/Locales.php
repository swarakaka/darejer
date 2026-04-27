<?php

namespace Darejer\Support;

/**
 * Locale metadata helpers used by the locale middleware + Inertia share.
 *
 * Keeps an authoritative list of right-to-left scripts so host apps don't
 * need to configure RTL themselves — adding `ckb` / `ar` / `he` etc. to
 * `config/darejer.php` is enough.
 */
final class Locales
{
    /**
     * Primary-language codes that render right-to-left.
     *
     * Source: Unicode CLDR `characterOrder` + common usage.
     */
    private const RTL = [
        'ar',  // Arabic
        'arc', // Aramaic
        'ckb', // Central Kurdish (Sorani)
        'dv',  // Divehi / Maldivian
        'fa',  // Persian
        'ha',  // Hausa (Ajami script)
        'he',  // Hebrew
        'khw', // Khowar
        'ks',  // Kashmiri
        'ps',  // Pashto
        'sd',  // Sindhi
        'ug',  // Uyghur
        'ur',  // Urdu
        'yi',  // Yiddish
    ];

    public static function isRtl(?string $locale): bool
    {
        if ($locale === null || $locale === '') {
            return false;
        }

        // BCP-47 tags may include a region (e.g. `ar-EG`, `ckb-IQ`).
        $primary = strtolower(explode('-', explode('_', $locale)[0])[0]);

        return in_array($primary, self::RTL, true);
    }

    public static function direction(?string $locale): string
    {
        return self::isRtl($locale) ? 'rtl' : 'ltr';
    }

    /**
     * Human-readable label shown in the language switcher (e.g. `AR`).
     */
    public static function label(string $locale): string
    {
        return strtoupper(explode('-', explode('_', $locale)[0])[0]);
    }
}
