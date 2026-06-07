<?php

declare(strict_types=1);

namespace Darejer\Documents;

/**
 * The data shape every printable voucher must expose so the generic
 * `DocumentRenderer` can fill an uploaded `.docx` template — without the
 * package ever knowing what an "invoice" is.
 *
 * Host apps implement one class per document type (constructed with the
 * concrete model) and register it via `DocumentTemplateRegistry`.
 *
 *   class SalesInvoiceTemplateData implements DocumentTemplateData { … }
 *
 * Token naming: scalars map to `${token}` macros in the Word document; each
 * row in a group maps to one cloned table row whose macros are the row's
 * array keys. PHPWord's macro syntax is `${name}`.
 */
interface DocumentTemplateData
{
    /**
     * Flat, single-value tokens, e.g. `['voucher_no' => 'INV-1001']`.
     *
     * @return array<string, string>
     */
    public function scalars(): array;

    /**
     * Repeating row groups. Each group is a list of associative rows sharing
     * the same keys, e.g.
     * `['lines' => [['item' => 'Widget', 'amount' => '10.00'], …]]`.
     *
     * @return array<string, array<int, array<string, string>>>
     */
    public function rowGroups(): array;

    /**
     * Human-readable catalog of every token this type can emit, used to build
     * the "available fields" reference and a starter `.docx`. Static so it can
     * be listed without a concrete record.
     *
     * @return array{
     *     scalars: array<string, string>,
     *     groups: array<string, array<string, string>>
     * }
     */
    public static function catalog(): array;
}
