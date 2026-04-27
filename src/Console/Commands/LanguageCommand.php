<?php

namespace Darejer\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class LanguageCommand extends Command
{
    protected $signature = 'darejer:language
        {action : add or remove}
        {locale : The locale code e.g. de, ar, fr}';

    protected $description = 'Add or remove a language from Darejer configuration.';

    public function handle(): int
    {
        $action = $this->argument('action');
        $locale = strtolower(trim($this->argument('locale')));

        if (! in_array($action, ['add', 'remove'], true)) {
            $this->error('Action must be "add" or "remove".');

            return self::FAILURE;
        }

        $configPath = config_path('darejer.php');

        if (! File::exists($configPath)) {
            $this->error('config/darejer.php not found. Run: php artisan vendor:publish --tag=darejer-config');

            return self::FAILURE;
        }

        $content = File::get($configPath);
        $languages = config('darejer.languages', ['en']);

        if ($action === 'add') {
            if (in_array($locale, $languages, true)) {
                $this->warn("Language '{$locale}' is already configured.");

                return self::SUCCESS;
            }

            $languages[] = $locale;
            $this->info("Adding language: {$locale}");
        }

        if ($action === 'remove') {
            $default = config('darejer.default_language', 'en');

            if ($locale === $default) {
                $this->error("Cannot remove the default language ({$default}).");

                return self::FAILURE;
            }

            if (! in_array($locale, $languages, true)) {
                $this->warn("Language '{$locale}' is not configured.");

                return self::SUCCESS;
            }

            $languages = array_values(array_filter($languages, fn ($l) => $l !== $locale));
            $this->info("Removing language: {$locale}");
        }

        $langArray = "['".implode("', '", $languages)."']";
        $newContent = preg_replace(
            "/('languages'\s*=>\s*)\[.*?\]/s",
            "$1{$langArray}",
            $content,
            1
        );

        File::put($configPath, $newContent);

        $this->info('✓ Languages updated: '.implode(', ', $languages));
        $this->line('Restart your dev server for changes to take effect.');

        return self::SUCCESS;
    }
}
