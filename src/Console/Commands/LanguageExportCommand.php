<?php

namespace Darejer\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class LanguageExportCommand extends Command
{
    protected $signature = 'darejer:lang:export
        {--path= : Override the lang directory (defaults to package lang/)}';

    protected $description = 'Export per-locale PHP language files to flat JSON snapshots consumed by the Vue frontend.';

    public function handle(): int
    {
        $base = $this->option('path') ?: dirname(__DIR__, 3).'/lang';

        if (! File::isDirectory($base)) {
            $this->error("Lang directory not found: {$base}");

            return self::FAILURE;
        }

        $written = 0;

        foreach (File::directories($base) as $localeDir) {
            $locale = basename($localeDir);
            $phpFile = $localeDir.'/darejer.php';

            if (! File::exists($phpFile)) {
                $this->warn("Skipping {$locale}: no darejer.php");

                continue;
            }

            $strings = require $phpFile;

            if (! is_array($strings)) {
                $this->warn("Skipping {$locale}: darejer.php did not return an array");

                continue;
            }

            $jsonPath = $base.'/'.$locale.'.json';

            File::put(
                $jsonPath,
                json_encode($strings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES).PHP_EOL,
            );

            $this->line("✓ {$locale} → ".basename($jsonPath));
            $written++;
        }

        $this->info("Exported {$written} locale(s).");

        return self::SUCCESS;
    }
}
