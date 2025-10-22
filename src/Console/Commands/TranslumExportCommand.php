<?php

namespace Kreatif\Translum\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Kreatif\Translum\Support\TranslationService;

class TranslumExportCommand extends Command
{
    protected $signature = 'translum:export
                            {--format=json : Export format (json, csv)}
                            {--output= : Output file path}
                            {--file= : Export specific file only}';

    protected $description = 'Export translations to JSON or CSV format';

    public function handle(): int
    {
        $service = TranslationService::getInstance();
        $locales = $service->getLocales();
        $allData = $service->getTranslationData($locales);

        // Filter by specific file if provided
        if ($this->option('file')) {
            $file = $this->option('file');
            if (!isset($allData[$file])) {
                $this->error("File '{$file}' not found.");
                return self::FAILURE;
            }
            $allData = [$file => $allData[$file]];
        }

        $format = $this->option('format');
        $output = $this->option('output') ?? storage_path("app/translum-export.{$format}");

        $this->info("Exporting translations to {$format} format...");

        switch ($format) {
            case 'json':
                $this->exportToJson($allData, $output);
                break;
            case 'csv':
                $this->exportToCsv($allData, $locales, $output);
                break;
            default:
                $this->error("Unsupported format: {$format}");
                return self::FAILURE;
        }

        $this->info("Translations exported successfully to: {$output}");

        return self::SUCCESS;
    }

    protected function exportToJson(array $data, string $output): void
    {
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        File::put($output, $json);
    }

    protected function exportToCsv(array $data, array $locales, string $output): void
    {
        $handle = fopen($output, 'w');

        // Write header
        fputcsv($handle, array_merge(['File', 'Key'], $locales));

        // Write data
        foreach ($data as $file => $keys) {
            foreach ($keys as $key => $values) {
                $row = [$file, $key];
                foreach ($locales as $locale) {
                    $row[] = $values[$locale] ?? '';
                }
                fputcsv($handle, $row);
            }
        }

        fclose($handle);
    }
}