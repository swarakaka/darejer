<?php

declare(strict_types=1);

namespace Darejer\Documents;

use InvalidArgumentException;

/**
 * Static registry mapping a `document_type` key to its human label, its
 * `DocumentTemplateData` provider class, and a default paper size.
 *
 * Host apps populate this (typically from a service provider) so the admin
 * Document Templates screen knows which types exist and the starter-`.docx`
 * generator can read each type's token catalog. Rendering itself does not go
 * through the registry — the calling controller constructs the provider with
 * its already-loaded model.
 *
 *   DocumentTemplateRegistry::register('sales_invoice', [
 *       'label' => __('Sales Invoice'),
 *       'provider' => SalesInvoiceTemplateData::class,
 *       'paper' => 'A4',
 *   ]);
 */
class DocumentTemplateRegistry
{
    /**
     * @var array<string, array{label: string, provider: class-string<DocumentTemplateData>, paper: ?string}>
     */
    protected static array $types = [];

    /**
     * @param  array{label?: string, provider: class-string<DocumentTemplateData>, paper?: ?string}  $definition
     */
    public static function register(string $documentType, array $definition): void
    {
        if (! isset($definition['provider']) || ! is_subclass_of($definition['provider'], DocumentTemplateData::class)) {
            throw new InvalidArgumentException(
                "Document type [{$documentType}] must register a 'provider' implementing ".DocumentTemplateData::class.'.'
            );
        }

        static::$types[$documentType] = [
            'label' => $definition['label'] ?? $documentType,
            'provider' => $definition['provider'],
            'paper' => $definition['paper'] ?? null,
        ];
    }

    public static function has(string $documentType): bool
    {
        return isset(static::$types[$documentType]);
    }

    /**
     * @return array{label: string, provider: class-string<DocumentTemplateData>, paper: ?string}|null
     */
    public static function get(string $documentType): ?array
    {
        return static::$types[$documentType] ?? null;
    }

    /** @return class-string<DocumentTemplateData>|null */
    public static function provider(string $documentType): ?string
    {
        return static::$types[$documentType]['provider'] ?? null;
    }

    public static function label(string $documentType): string
    {
        return static::$types[$documentType]['label'] ?? $documentType;
    }

    public static function paper(string $documentType): ?string
    {
        return static::$types[$documentType]['paper'] ?? null;
    }

    /**
     * `[type => label]`, handy as Select / Filter options.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        return array_map(fn (array $def): string => $def['label'], static::$types);
    }

    /**
     * @return array<string, array{label: string, provider: class-string<DocumentTemplateData>, paper: ?string}>
     */
    public static function all(): array
    {
        return static::$types;
    }

    public static function flush(): void
    {
        static::$types = [];
    }
}
