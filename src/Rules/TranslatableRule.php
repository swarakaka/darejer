<?php

namespace Darejer\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\ValidatorAwareRule;
use Illuminate\Validation\Validator;

/**
 * Validates a translatable field value.
 *
 * The field should arrive as an array keyed by locale, e.g.
 *   ['en' => 'Chair', 'de' => 'Stuhl']
 * or a JSON string of the same shape.
 *
 * Per-locale errors (default-locale required, type, max length) are
 * attached to {field}.{locale} so the frontend can map them to the
 * matching language tab. Structural errors (not an array, unknown
 * locale key) are attached to the parent field.
 *
 * Usage:
 *   'name' => ['required', new TranslatableRule(required: true, max: 255)],
 *   'description' => ['nullable', new TranslatableRule(max: 5000)],
 */
class TranslatableRule implements ValidationRule, ValidatorAwareRule
{
    protected Validator $validator;

    /** @var array<int, string> */
    protected array $languages;

    /**
     * @param  array<int, string>|null  $languages
     */
    public function __construct(
        protected bool $required = false,
        protected ?int $max = null,
        ?array $languages = null,
    ) {
        $this->languages = $languages ?? config('darejer.languages', ['en']);
    }

    public function setValidator(Validator $validator): static
    {
        $this->validator = $validator;

        return $this;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $fail("The {$attribute} must be a valid translatable JSON object.");

                return;
            }
            $value = $decoded;
        }

        if (! is_array($value)) {
            $fail("The {$attribute} must be a translatable object with language keys.");

            return;
        }

        $defaultLang = config('darejer.default_language', 'en');
        $errors = $this->validator->errors();

        if ($this->required) {
            $defaultValue = $value[$defaultLang] ?? '';
            if (trim((string) $defaultValue) === '') {
                $errors->add(
                    "{$attribute}.{$defaultLang}",
                    "The {$attribute} ({$defaultLang}) field is required.",
                );
            }
        }

        foreach ($value as $locale => $translation) {
            if (! in_array($locale, $this->languages, true)) {
                $fail("The {$attribute} contains an unsupported language: {$locale}.");

                return;
            }

            if ($translation === null) {
                continue;
            }

            if (! is_string($translation)) {
                $errors->add(
                    "{$attribute}.{$locale}",
                    "The {$attribute}.{$locale} must be a string.",
                );

                continue;
            }

            if ($this->max !== null && mb_strlen($translation) > $this->max) {
                $errors->add(
                    "{$attribute}.{$locale}",
                    "The {$attribute}.{$locale} must not be greater than {$this->max} characters.",
                );
            }
        }
    }
}
