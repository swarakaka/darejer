<?php

declare(strict_types=1);

namespace Darejer\Documents;

use Darejer\Models\DocumentTemplate;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Settings;
use PhpOffice\PhpWord\TemplateProcessor;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Fills an uploaded `.docx` template with voucher data (PHPWord
 * `TemplateProcessor`) and, optionally, converts the result to PDF using a
 * pure-PHP renderer (mpdf) — no LibreOffice / headless Chrome required.
 *
 * Fidelity note: the `.docx` output is faithful to the user's Word design.
 * The PDF path goes `.docx → HTML → PDF` inside PHPWord and loses advanced
 * Word formatting; it is best-effort, with mpdf chosen for its RTL/Arabic
 * support.
 */
class DocumentRenderer
{
    public const FORMAT_DOCX = 'docx';

    public const FORMAT_PDF = 'pdf';

    /**
     * Fill the template and return the absolute path to a temporary `.docx`.
     * Caller owns the file (delete after sending).
     */
    public function fill(DocumentTemplate $template, DocumentTemplateData $data): string
    {
        $source = Storage::disk($template->disk())->path($template->file_path);

        if (! is_file($source)) {
            throw new RuntimeException("Document template file not found: {$template->file_path}");
        }

        $processor = new TemplateProcessor($source);

        foreach ($data->scalars() as $token => $value) {
            $processor->setValue($token, $this->stringify($value));
        }

        $groupCatalog = $data::catalog()['groups'] ?? [];

        foreach ($data->rowGroups() as $group => $rows) {
            $this->fillRowGroup($processor, $group, $rows, $groupCatalog[$group] ?? []);
        }

        $target = $this->tempFile('docx');
        $processor->saveAs($target);

        return $target;
    }

    /**
     * Fill, then render to PDF. Returns the absolute path to a temporary PDF.
     */
    public function renderPdf(DocumentTemplate $template, DocumentTemplateData $data): string
    {
        $docx = $this->fill($template, $data);

        $this->configurePdfRenderer();

        $phpWord = IOFactory::load($docx);
        $pdf = $this->tempFile('pdf');
        IOFactory::createWriter($phpWord, 'PDF')->save($pdf);

        @unlink($docx);

        return $pdf;
    }

    /**
     * Stream the rendered document as a download in the requested format.
     */
    public function download(
        DocumentTemplate $template,
        DocumentTemplateData $data,
        string $format,
        string $downloadName,
    ): StreamedResponse {
        if ($format === self::FORMAT_PDF) {
            $path = $this->renderPdf($template, $data);
            $mime = 'application/pdf';
            $name = "{$downloadName}.pdf";
        } else {
            $path = $this->fill($template, $data);
            $mime = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
            $name = "{$downloadName}.docx";
        }

        return response()->streamDownload(function () use ($path): void {
            $stream = fopen($path, 'rb');
            fpassthru($stream);
            fclose($stream);
            @unlink($path);
        }, $name, ['Content-Type' => $mime]);
    }

    /**
     * Clone a repeating table row once per data row. The row "anchor" — the
     * macro PHPWord searches for to find the row to clone — is the first token
     * declared in the group's catalog (falling back to the first key present
     * in the data). When there are no rows, the anchor token and its siblings
     * are blanked so no `${…}` placeholders leak into the output.
     *
     * @param  array<int, array<string, string>>  $rows
     * @param  array<string, string>  $catalogTokens  token => label for the group
     */
    protected function fillRowGroup(TemplateProcessor $processor, string $group, array $rows, array $catalogTokens): void
    {
        $anchor = array_key_first($catalogTokens)
            ?? ($rows !== [] ? array_key_first($rows[0]) : null);

        if ($anchor === null) {
            return;
        }

        if ($rows === []) {
            foreach (array_keys($catalogTokens) as $token) {
                $processor->setValue($token, '');
            }

            return;
        }

        $normalized = array_map(
            fn (array $row): array => array_map([$this, 'stringify'], $row),
            array_values($rows),
        );

        $processor->cloneRowAndSetValues($anchor, $normalized);
    }

    protected function configurePdfRenderer(): void
    {
        Settings::setPdfRendererName(Settings::PDF_RENDERER_MPDF);
        Settings::setPdfRendererPath(base_path('vendor/mpdf/mpdf'));
    }

    protected function tempFile(string $extension): string
    {
        $path = tempnam(sys_get_temp_dir(), 'darejer_doc_');

        if ($path === false) {
            throw new RuntimeException('Unable to allocate a temporary file for document rendering.');
        }

        $withExt = $path.'.'.$extension;
        rename($path, $withExt);

        return $withExt;
    }

    protected function stringify(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        return is_scalar($value) ? (string) $value : '';
    }
}
