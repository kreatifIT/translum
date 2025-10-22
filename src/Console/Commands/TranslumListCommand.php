<?php

namespace Kreatif\Translum\Console\Commands;

use Illuminate\Console\Command;
use Kreatif\Translum\Support\TranslationService;

class TranslumListCommand extends Command
{
    protected $signature = 'translum:list
                            {--locale= : Filter by specific locale}
                            {--file= : Filter by specific file}
                            {--search= : Search for specific key or value}';

    protected $description = 'List all translation files and keys';

    public function handle(): int
    {
        $service = TranslationService::getInstance();
        $locales = $service->getLocales();
        $files = $service->getTranslationFiles();

        $this->info('Translation Files:');
        $this->info('================');

        $totalKeys = 0;

        foreach ($files as $file) {
            if ($this->option('file') && $file !== $this->option('file')) {
                continue;
            }

            $this->line('');
            $this->info("File: {$file}");
            $this->line(str_repeat('-', 50));

            // Get translation data for this file
            $allData = $service->getTranslationData($locales);
            $fileData = $allData[$file] ?? [];

            $keyCount = count($fileData);
            $totalKeys += $keyCount;

            $this->line("Keys: {$keyCount}");

            if ($this->option('locale')) {
                $locale = $this->option('locale');
                $this->line("Locale: {$locale}");
            }

            if ($this->option('search')) {
                $search = $this->option('search');
                $this->line("Searching for: {$search}");

                foreach ($fileData as $key => $values) {
                    if (stripos($key, $search) !== false) {
                        $this->line("  - {$key}");
                    }
                }
            }
        }

        $this->line('');
        $this->info("Total Files: " . count($files));
        $this->info("Total Keys: {$totalKeys}");

        return self::SUCCESS;
    }
}