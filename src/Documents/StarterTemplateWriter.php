<?php

declare(strict_types=1);

namespace Darejer\Documents;

use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Generates a downloadable "starter" `.docx` for a document type: a Word file
 * pre-populated with every available `${token}` placeholder (and an example
 * repeating table per row group). Users open it in Word, restyle it freely as
 * long as they keep the placeholders, and upload it back as a template.
 *
 * This is the package's equivalent of Odoo/Jmix's "available fields" helper —
 * concrete and editable rather than an on-screen field list.
 */
class StarterTemplateWriter
{
    public function download(string $documentType, string $title): StreamedResponse
    {
        $provider = DocumentTemplateRegistry::provider($documentType);

        if ($provider === null) {
            throw new RuntimeException("Unknown document type: {$documentType}");
        }

        $catalog = $provider::catalog();
        $path = $this->build($title, $catalog['scalars'] ?? [], $catalog['groups'] ?? []);

        return response()->streamDownload(function () use ($path): void {
            $stream = fopen($path, 'rb');
            fpassthru($stream);
            fclose($stream);
            @unlink($path);
        }, "starter-{$documentType}.docx", [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ]);
    }

    /**
     * @param  array<string, string>  $scalars  token => label
     * @param  array<string, array<string, string>>  $groups  group => (token => label)
     */
    protected function build(string $title, array $scalars, array $groups): string
    {
        $phpWord = new PhpWord;
        $section = $phpWord->addSection();

        $section->addText($title, ['bold' => true, 'size' => 16]);
        $section->addText(
            __darejer('Edit the design freely — keep the ${placeholders} intact. They are replaced with live data on print.'),
            ['italic' => true, 'size' => 9, 'color' => '666666']
        );
        $section->addTextBreak(1);

        if ($scalars !== []) {
            $section->addText(__darejer('Fields'), ['bold' => true, 'size' => 12]);
            foreach ($scalars as $token => $label) {
                $section->addText($label.': ${'.$token.'}');
            }
            $section->addTextBreak(1);
        }

        foreach ($groups as $group => $tokens) {
            if ($tokens === []) {
                continue;
            }

            $section->addText(__darejer('Table').': '.$group, ['bold' => true, 'size' => 12]);
            $table = $section->addTable(['borderSize' => 6, 'borderColor' => '999999', 'cellMargin' => 60]);

            $table->addRow();
            foreach ($tokens as $label) {
                $table->addCell(2200)->addText((string) $label, ['bold' => true]);
            }

            // One placeholder row — the engine clones it per data row.
            $table->addRow();
            foreach (array_keys($tokens) as $token) {
                $table->addCell(2200)->addText('${'.$token.'}');
            }

            $section->addTextBreak(1);
        }

        $path = tempnam(sys_get_temp_dir(), 'darejer_starter_');
        if ($path === false) {
            throw new RuntimeException('Unable to allocate a temporary file for the starter template.');
        }
        $withExt = $path.'.docx';
        rename($path, $withExt);

        IOFactory::createWriter($phpWord, 'Word2007')->save($withExt);

        return $withExt;
    }
}
