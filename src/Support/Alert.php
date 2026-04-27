<?php

declare(strict_types=1);

namespace Darejer\Support;

use Darejer\Events\AlertCreated;
use Darejer\Models\Alert as AlertModel;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Static API for creating per-user notifications.
 *
 *   Alert::success('Invoice approved');
 *   Alert::error('Payment failed', link: route('invoices.show', $invoice));
 *   Alert::info(['en' => 'Saved', 'ar' => 'تم الحفظ'], to: $user);
 *
 * Messages can be:
 *  - a plain string (auto-stored against the current locale + the
 *    package default locale, so the alert renders in every UI);
 *  - a translation key (`darejer::alerts.invoice_approved`) — resolved
 *    against every configured language so each user reads it in their own;
 *  - an explicit array of `['locale' => 'text', …]` translations.
 *
 * Each create dispatches `AlertCreated` which broadcasts on the private
 * `darejer.alerts.{userId}` channel — the topbar bell turns red in real
 * time without any host-side wiring.
 */
final class Alert
{
    public const LEVEL_SUCCESS = 'success';

    public const LEVEL_ERROR = 'error';

    public const LEVEL_WARNING = 'warning';

    public const LEVEL_INFO = 'info';

    public static function success(
        string|array $message,
        ?string $link = null,
        Authenticatable|int|null $to = null,
        array $data = [],
    ): AlertModel {
        return self::create(self::LEVEL_SUCCESS, $message, $link, $to, $data);
    }

    public static function error(
        string|array $message,
        ?string $link = null,
        Authenticatable|int|null $to = null,
        array $data = [],
    ): AlertModel {
        return self::create(self::LEVEL_ERROR, $message, $link, $to, $data);
    }

    public static function warning(
        string|array $message,
        ?string $link = null,
        Authenticatable|int|null $to = null,
        array $data = [],
    ): AlertModel {
        return self::create(self::LEVEL_WARNING, $message, $link, $to, $data);
    }

    public static function info(
        string|array $message,
        ?string $link = null,
        Authenticatable|int|null $to = null,
        array $data = [],
    ): AlertModel {
        return self::create(self::LEVEL_INFO, $message, $link, $to, $data);
    }

    /**
     * Create an alert at any level.
     *
     * @param  array<string, mixed>  $data
     */
    public static function create(
        string $level,
        string|array $message,
        ?string $link = null,
        Authenticatable|int|null $to = null,
        array $data = [],
    ): AlertModel {
        $userId = self::resolveUserId($to);
        $translations = self::resolveTranslations($message);

        $alert = AlertModel::query()->create([
            'user_id' => $userId,
            'level' => $level,
            'message' => $translations,
            'link' => $link,
            'data' => $data !== [] ? $data : null,
        ]);

        AlertCreated::dispatch($alert);

        return $alert;
    }

    private static function resolveUserId(Authenticatable|int|null $to): int
    {
        if ($to instanceof Authenticatable) {
            return (int) $to->getAuthIdentifier();
        }

        if (is_int($to)) {
            return $to;
        }

        $user = auth()->user();
        if ($user === null) {
            throw new \LogicException('Alert::* requires either an authenticated user or an explicit recipient ($to).');
        }

        return (int) $user->getAuthIdentifier();
    }

    /**
     * Build the translations bag stored on the model.
     *
     * @param  string|array<string, string>  $message
     * @return array<string, string>
     */
    private static function resolveTranslations(string|array $message): array
    {
        $languages = config('darejer.languages', ['en']);
        $default = config('darejer.default_language', 'en');

        // Explicit per-locale array — accept as-is, fill any missing
        // configured languages from the default-locale entry so reads in
        // those locales don't return empty strings.
        if (is_array($message)) {
            $fallback = $message[$default] ?? $message[array_key_first($message)] ?? '';
            $bag = [];
            foreach ($languages as $locale) {
                $bag[$locale] = $message[$locale] ?? $fallback;
            }

            return $bag;
        }

        // Looks like a translation key (`darejer::alerts.invoice_approved`,
        // `messages.saved`)? Resolve once per configured language so each
        // user reads it in their own locale at display time.
        if (self::looksLikeTranslationKey($message)) {
            $bag = [];
            foreach ($languages as $locale) {
                $translated = trans($message, [], $locale);
                $bag[$locale] = is_string($translated) ? $translated : $message;
            }

            return $bag;
        }

        // Plain string — store against the current locale and the default,
        // so any locale a viewer requests still has *something* to render.
        $current = app()->getLocale();
        $bag = [$default => $message];
        if ($current !== $default) {
            $bag[$current] = $message;
        }

        return $bag;
    }

    private static function looksLikeTranslationKey(string $message): bool
    {
        return preg_match('/^[A-Za-z0-9_\-]+(::[A-Za-z0-9_\-\.]+|\.[A-Za-z0-9_\-\.]+)$/', $message) === 1;
    }
}
