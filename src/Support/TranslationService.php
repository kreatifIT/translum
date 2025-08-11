<?php

namespace Kreatif\Translum\Support;

use Illuminate\Support\Facades\File;
use Statamic\Facades\Site;
use Statamic\Fields\Blueprint;

class TranslationService
{
    private static ?TranslationService $instance = null;

    public static function getInstance(): TranslationService
    {
        if (self::$instance === null) {
            self::$instance = app(TranslationService::class);
        }
        return self::$instance;
    }

    public function getLocales(): array
    {
        return Site::all()->map->handle()->all();
    }

    public function buildBlueprint(): Blueprint
    {
        $locales = $this->getLocales();
        $translationData = $this->getTranslationData($locales);
        $tabs = [];

        $fieldType = config('statamic.translum.field_type', 'text');
        $fieldConfig = config("statamic.translum.field_config.{$fieldType}", []);

        foreach (array_keys($translationData) as $filename) {
            $existingFields = [];

            // Build fields for existing translations
            foreach ($translationData[$filename] as $key => $localeValues) {
                foreach ($locales as $locale) {
                    $handle = "{$filename}.{$key}.$locale";
                    $existingFields[] = [
                        'handle' => $handle,
                        'field' => array_merge([
                            'display' => "$key ($locale)",
                            'type' => $fieldType,
                            'localizable' => false,
                            'validate' => ['nullable'],
                            'width' => round(100 / count($locales), 0)
                        ], $fieldConfig),
                    ];
                }
            }

            // **THE FIX**: Add a Replicator field for new keys.
            $newKeyFields = [];
            if (config('statamic.translum.allow_new_keys', false)) {

                $replicatorSetFields = [];
                $replicatorSetFields[] = [
                    'handle' => 'key',
                    'field' => [
                        'display' => 'New Key Name',
                        'type' => 'text',
                        'validate' => ['required', 'regex:' . config('statamic.translum.new_key_validation_regex', '/^[a-z0-9_.]+$/')],
                        'width' => 100,
                    ]
                ];

                foreach ($locales as $locale) {
                    $replicatorSetFields[] = [
                        'handle' => $locale,
                        'field' => array_merge([
                            'display' => "Value ({$locale})",
                            'type' => $fieldType,
                            'width' => round(100 / count($locales), 0)
                        ], $fieldConfig)
                    ];
                }

                $newKeyFields[] = [
                    'handle' => "new_keys_{$filename}",
                    'field' => [
                        'type' => 'replicator',
                        'display' => 'Add New Translations',
                        'button' => 'Add New Translation',
                        'sets' => [
                            'new_translation' => [
                                'display' => 'New Translation',
                                'fields' => $replicatorSetFields,
                            ]
                        ]
                    ]
                ];
            }

            $tabs[$filename] = [
                'display' => ucfirst($filename),
                'handle' => $filename,
                'sections' => [
                    [ 'display' => 'Existing Translations', 'fields' => $existingFields ],
                    [ 'display' => 'New Translations', 'fields' => $newKeyFields ],
                ]
            ];
        }

        return Blueprint::make()->setContents(['tabs' => $tabs]);
    }

    public function getInitialValues(): array
    {
        $locales = $this->getLocales();
        $translationData = $this->getTranslationData($locales);
        $values = [];

        foreach ($translationData as $filename => $keys) {
            foreach ($keys as $key => $localeValues) {
                foreach ($locales as $locale) {
                    $handle = "{$filename}.{$key}.$locale";
                    $values[$handle] = $localeValues[$locale] ?? null;
                }
            }
        }
        return $values;
    }

    protected function getTranslationData(array $locales): array
    {
        $translations = [];
        $translationPaths = [ resource_path('lang'), base_path('lang') ];
        foreach ($translationPaths as $path) {
            if (File::exists($path)) {
                foreach (File::directories($path) as $localePath) {
                    $locale = basename($localePath);
                    if (in_array($locale, $locales)) {
                        foreach (File::files($localePath) as $file) {
                            $filename = pathinfo($file, PATHINFO_FILENAME);
                            if ($filename === 'vendor') continue;
                            $data = include $file;
                            $this->flattenAndStoreTranslations($translations, $filename, $locale, $data);
                        }
                    }
                }
            }
        }
        return $translations;
    }

    protected function flattenAndStoreTranslations(&$target, $filename, $locale, $data, $prefix = ''): void
    {
        if (!isset($target[$filename])) $target[$filename] = [];
        foreach ($data as $key => $value) {
            $fullKey = $prefix ? "{$prefix}.{$key}" : $key;
            if (is_array($value) && !empty($value)) {
                $this->flattenAndStoreTranslations($target, $filename, $locale, $value, $fullKey);
            } else {
                if (!isset($target[$filename][$fullKey])) $target[$filename][$fullKey] = [];
                $target[$filename][$fullKey][$locale] = $value;
            }
        }
    }
}
