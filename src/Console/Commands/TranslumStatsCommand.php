<?php

namespace Kreatif\Translum\Console\Commands;

use Illuminate\Console\Command;
use Kreatif\Translum\Support\TranslationService;

class TranslumStatsCommand extends Command
{
    protected $signature = 'translum:stats';

    protected $description = 'Display translation statistics';

    public function handle(): int
    {
        $service = TranslationService::getInstance();
        $locales = $service->getLocales();
        $files = $service->getTranslationFiles();
        $allData = $service->getTranslationData($locales);

        $this->info('Translation Statistics');
        $this->info('======================');
        $this->line('');

        // Locales
        $this->info('Configured Locales: ' . implode(', ', $locales));
        $this->info('Total Locales: ' . count($locales));
        $this->line('');

        // Files
        $this->info('Translation Files: ' . count($files));
        $this->line('');

        // Keys per file
        $headers = ['File', 'Keys', 'Missing Translations'];
        $rows = [];

        foreach ($files as $file) {
            $fileData = $allData[$file] ?? [];
            $keyCount = count($fileData);
            $missingCount = 0;

            foreach ($fileData as $key => $values) {
                foreach ($locales as $locale) {
                    if (!isset($values[$locale]) || empty($values[$locale])) {
                        $missingCount++;
                    }
                }
            }

            $rows[] = [
                $file,
                $keyCount,
                $missingCount > 0 ? "<fg=red>{$missingCount}</>" : '<fg=green>0</>',
            ];
        }

        $this->table($headers, $rows);

        // Total stats
        $totalKeys = $service->getTotalTranslationCount();
        $this->line('');
        $this->info("Total Translation Keys: {$totalKeys}");

        // Cache status
        $this->line('');
        $cacheEnabled = config('statamic.translum.cache.enabled', true);
        $cacheStatus = $cacheEnabled ? '<fg=green>Enabled</>' : '<fg=red>Disabled</>';
        $this->info("Cache Status: {$cacheStatus}");

        return self::SUCCESS;
    }
}