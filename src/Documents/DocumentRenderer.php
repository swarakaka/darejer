<?php

declare(strict_types=1);

namespace Darejer\Documents;

use Darejer\Models\DocumentTemplate;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\TemplateProcessor;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Fills an uploaded `.docx` template with voucher data (PHPWord
 * `TemplateProcessor`), then converts it to PDF with headless LibreOffice
 * (`soffice --convert-to pdf`). LibreOffice renders the `.docx` natively, so
 * alignment, column widths, fonts, borders and RTL (Arabic/Kurdish) match Word
 * exactly. PDF rendering requires a LibreOffice binary; when none is found
 * `renderPdf()` throws (the faithful `.docx` is always available via
 * `download(..., 'docx')`).
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
     * Fill, then render to PDF with LibreOffice. Returns the absolute path to a
     * temporary PDF. Throws when no LibreOffice binary is available.
     */
    public function renderPdf(DocumentTemplate $template, DocumentTemplateData $data): string
    {
        $docx = $this->fill($template, $data);

        try {
            $binary = $this->libreOfficeBinary();

            if ($binary === null) {
                throw new RuntimeException(
                    'PDF rendering requires LibreOffice. Install libreoffice-writer or set '.
                    'LIBREOFFICE_BINARY. The .docx output is available via the docx format.'
                );
            }

            return $this->convertWithLibreOffice($binary, $docx);
        } finally {
            @unlink($docx);
        }
    }

    /**
     * Convert a `.docx` to PDF with headless LibreOffice. Returns the PDF path;
     * throws on conversion failure.
     */
    protected function convertWithLibreOffice(string $binary, string $docx): string
    {
        $outDir = $this->tempDir();
        $profileDir = $this->tempDir();

        $command = implode(' ', [
            escapeshellarg($binary),
            '--headless', '--nologo', '--nofirststartwizard', '--norestore',
            '--convert-to', escapeshellarg('pdf:writer_pdf_Export'),
            '--outdir', escapeshellarg($outDir),
            '-env:UserInstallation=file://'.$profileDir,
            escapeshellarg($docx),
            '2>&1',
        ]);

        @exec($command, $output, $exitCode);

        $produced = $outDir.'/'.pathinfo($docx, PATHINFO_FILENAME).'.pdf';

        try {
            if ($exitCode === 0 && is_file($produced)) {
                $pdf = $this->tempFile('pdf');
                rename($produced, $pdf);

                return $pdf;
            }

            throw new RuntimeException('LibreOffice failed to convert the document to PDF: '.implode("\n", (array) $output));
        } finally {
            $this->removeDir($outDir);
            $this->removeDir($profileDir);
        }
    }

    /** Locate a usable LibreOffice binary, or null. Configurable via env. */
    protected function libreOfficeBinary(): ?string
    {
        $candidates = array_filter([
            config('darejer.libreoffice.binary'),
            'soffice',
            'libreoffice',
            '/usr/bin/soffice',
            '/usr/bin/libreoffice',
            '/opt/libreoffice/program/soffice',
            '/Applications/LibreOffice.app/Contents/MacOS/soffice',
        ]);

        foreach ($candidates as $candidate) {
            if (str_contains((string) $candidate, '/')) {
                if (is_executable($candidate)) {
                    return $candidate;
                }

                continue;
            }

            $resolved = trim((string) @shell_exec('command -v '.escapeshellarg($candidate).' 2>/dev/null'));
            if ($resolved !== '') {
                return $resolved;
            }
        }

        return null;
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

    /** Create a unique temporary directory. */
    protected function tempDir(): string
    {
        $path = tempnam(sys_get_temp_dir(), 'darejer_lo_');

        if ($path === false) {
            throw new RuntimeException('Unable to allocate a temporary directory for document rendering.');
        }

        @unlink($path);
        @mkdir($path, 0775, true);

        return $path;
    }

    /** Recursively remove a temporary directory. */
    protected function removeDir(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }

        foreach (scandir($dir) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $path = $dir.'/'.$entry;
            is_dir($path) ? $this->removeDir($path) : @unlink($path);
        }

        @rmdir($dir);
    }

    protected function stringify(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        return is_scalar($value) ? (string) $value : '';
    }
}
