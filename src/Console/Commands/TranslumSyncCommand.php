<?php

namespace Kreatif\Translum\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Kreatif\Translum\Support\TranslationService;

class TranslumSyncCommand extends Command
{
    protected $signature = 'translum:sync
                            {--locale= : Sync specific locale only}
                            {--dry-run : Show what would be synced without making changes}';

    protected $description = 'Sync translation keys across all locales (adds missing keys)';

    public function handle(): int
    {
        $service = TranslationService::getInstance();
        $locales = $service->getLocales();

        if ($this->option('locale')) {
            $targetLocale = $this->option('locale');
            if (!in_array($targetLocale, $locales)) {
                $this->error("Locale '{$targetLocale}' is not configured.");
                return self::FAILURE;
            }
            $locales = [$targetLocale];
        }

        $allData = $service->getTranslationData($service->getLocales());
        $isDryRun = $this->option('dry-run');

        if ($isDryRun) {
            $this->warn('DRY RUN MODE - No files will be modified');
            $this->line('');
        }

        $totalAdded = 0;

        foreach ($allData as $file => $keys) {
            foreach ($keys as $key => $values) {
                foreach ($locales as $locale) {
                    if (!isset($values[$locale]) || $values[$locale] === null) {
                        $totalAdded++;

                        $this->line("Missing: {$file}.{$key} for locale '{$locale}'");

                        if (!$isDryRun) {
                            $this->addMissingKey($file, $locale, $key);
                        }
                    }
                }
            }
        }

        $this->line('');

        if ($totalAdded > 0) {
            if ($isDryRun) {
                $this->info("Would add {$totalAdded} missing translation keys.");
            } else {
                $this->info("Added {$totalAdded} missing translation keys.");
                $service->clearCache();
                $this->info('Cache cleared.');
            }
        } else {
            $this->info('All translation keys are synchronized.');
        }

        return self::SUCCESS;
    }

    protected function addMissingKey(string $file, string $locale, string $key): void
    {
        // Skip vendor files for sync
        if (str_starts_with($file, 'vendor/')) {
            return;
        }

        $filePath = resource_path("lang/{$locale}/{$file}.php");

        if (!File::exists($filePath)) {
            File::makeDirectory(dirname($filePath), 0755, true);
            File::put($filePath, "<?php\n\nreturn [];\n");
        }

        $translations = include $filePath;

        // Set the key with empty value
        $keys = explode('.', $key);
        $current = &$translations;

        foreach ($keys as $keyPart) {
            if (!isset($current[$keyPart])) {
                $current[$keyPart] = [];
            }
            $current = &$current[$keyPart];
        }

        // Set to empty string instead of array
        $current = '';

        // Save back to file
        $content = "<?php\n\nreturn " . var_export($translations, true) . ";\n";
        File::put($filePath, $content);
    }
}