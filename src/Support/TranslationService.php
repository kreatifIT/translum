<?php

namespace Kreatif\Translum\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
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

    public function buildBlueprint(?Request $request = null): Blueprint
    {
        $locales = $this->getLocales();
        $translationData = $this->getTranslationData($locales);

        // Apply search filter if provided
        if ($request && $request->has('search')) {
            $translationData = $this->filterTranslations($translationData, $request->get('search'));
        }

        // Apply pagination if enabled
        if ($request && config('statamic.translum.pagination.enabled', true)) {
            $translationData = $this->paginateTranslations($translationData, $request);
        }

        $tabs = $this->buildTabs($translationData, $locales);

        return Blueprint::make()->setContents(['tabs' => $tabs]);
    }

    public function getInitialValues(?Request $request = null): array
    {
        $locales = $this->getLocales();
        $translationData = $this->getTranslationData($locales);

        // Apply search filter if provided
        if ($request && $request->has('search')) {
            $translationData = $this->filterTranslations($translationData, $request->get('search'));
        }

        // Apply pagination if enabled
        if ($request && config('statamic.translum.pagination.enabled', true)) {
            $translationData = $this->paginateTranslations($translationData, $request);
        }

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

    protected function buildTabs(array $translationData, array $locales): array
    {
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

            // Add a Replicator field for new keys
            $newKeyFields = [];
            if (config('statamic.translum.allow_new_keys', false)) {
                $newKeyFields = $this->buildNewKeyFields($filename, $locales, $fieldType, $fieldConfig);
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

        return $tabs;
    }

    protected function buildNewKeyFields(string $filename, array $locales, string $fieldType, array $fieldConfig): array
    {
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

        return [[
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
        ]];
    }

    public function getTranslationData(array $locales): array
    {
        $cacheEnabled = config('statamic.translum.cache.enabled', true);
        $cacheKey = config('statamic.translum.cache.key_prefix', 'translum') . '.translation_data';

        if ($cacheEnabled && Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $translations = [];
        $translationPaths = $this->getTranslationPaths();

        foreach ($translationPaths as $path) {
            if (File::exists($path)) {
                $this->loadTranslationsFromPath($translations, $path, $locales);
            }
        }

        // Load vendor translations if enabled
        if (config('statamic.translum.vendor_translations.enabled', false)) {
            $this->loadVendorTranslations($translations, $locales);
        }

        // Apply file filtering
        $translations = $this->applyFileFiltering($translations);

        if ($cacheEnabled) {
            $ttl = config('statamic.translum.cache.ttl', 3600);
            Cache::put($cacheKey, $translations, $ttl);
        }

        return $translations;
    }

    protected function getTranslationPaths(): array
    {
        return [
            resource_path('lang'),
            base_path('lang')
        ];
    }

    protected function loadTranslationsFromPath(array &$translations, string $path, array $locales): void
    {
        foreach (File::directories($path) as $localePath) {
            $locale = basename($localePath);

            // Skip vendor directory at this level
            if ($locale === 'vendor') {
                continue;
            }

            if (in_array($locale, $locales)) {
                foreach (File::files($localePath) as $file) {
                    if (pathinfo($file, PATHINFO_EXTENSION) !== 'php') {
                        continue;
                    }

                    $filename = pathinfo($file, PATHINFO_FILENAME);
                    $data = include $file;

                    if (is_array($data)) {
                        $this->flattenAndStoreTranslations($translations, $filename, $locale, $data);
                    }
                }
            }
        }
    }

    protected function loadVendorTranslations(array &$translations, array $locales): void
    {
        $vendorPackages = config('statamic.translum.vendor_translations.packages', []);
        $translationPaths = $this->getTranslationPaths();

        foreach ($translationPaths as $basePath) {
            $vendorPath = $basePath . '/vendor';

            if (!File::exists($vendorPath)) {
                continue;
            }

            foreach (File::directories($vendorPath) as $packagePath) {
                $packageName = basename($packagePath);

                // If specific packages are defined, only load those
                if (!empty($vendorPackages) && !in_array($packageName, $vendorPackages)) {
                    continue;
                }

                foreach (File::directories($packagePath) as $localePath) {
                    $locale = basename($localePath);

                    if (!in_array($locale, $locales)) {
                        continue;
                    }

                    foreach (File::files($localePath) as $file) {
                        if (pathinfo($file, PATHINFO_EXTENSION) !== 'php') {
                            continue;
                        }

                        $filename = "vendor/{$packageName}/" . pathinfo($file, PATHINFO_FILENAME);
                        $data = include $file;

                        if (is_array($data)) {
                            $this->flattenAndStoreTranslations($translations, $filename, $locale, $data);
                        }
                    }
                }
            }
        }
    }

    protected function applyFileFiltering(array $translations): array
    {
        $mode = config('statamic.translum.file_filter.mode', 'all');
        $patterns = config('statamic.translum.file_filter.patterns', []);

        if ($mode === 'all' || empty($patterns)) {
            return $translations;
        }

        $filtered = [];

        foreach ($translations as $filename => $data) {
            $shouldInclude = false;

            foreach ($patterns as $pattern) {
                if ($this->matchesPattern($filename, $pattern)) {
                    $shouldInclude = true;
                    break;
                }
            }

            if ($mode === 'include' && $shouldInclude) {
                $filtered[$filename] = $data;
            } elseif ($mode === 'exclude' && !$shouldInclude) {
                $filtered[$filename] = $data;
            }
        }

        return $filtered;
    }

    protected function matchesPattern(string $filename, string $pattern): bool
    {
        // Convert wildcard pattern to regex
        $regex = '/^' . str_replace(['*', '/'], ['.*', '\/'], $pattern) . '$/';
        return (bool) preg_match($regex, $filename);
    }

    protected function filterTranslations(array $translations, string $search): array
    {
        if (empty($search)) {
            return $translations;
        }

        $searchInValues = config('statamic.translum.search.search_in_values', true);
        $caseSensitive = config('statamic.translum.search.case_sensitive', false);
        $filtered = [];

        foreach ($translations as $filename => $keys) {
            foreach ($keys as $key => $localeValues) {
                $keyMatches = $caseSensitive
                    ? str_contains($key, $search)
                    : str_contains(strtolower($key), strtolower($search));

                $valueMatches = false;
                if ($searchInValues) {
                    foreach ($localeValues as $value) {
                        if (is_string($value)) {
                            $valueMatches = $caseSensitive
                                ? str_contains($value, $search)
                                : str_contains(strtolower($value), strtolower($search));

                            if ($valueMatches) {
                                break;
                            }
                        }
                    }
                }

                if ($keyMatches || $valueMatches) {
                    if (!isset($filtered[$filename])) {
                        $filtered[$filename] = [];
                    }
                    $filtered[$filename][$key] = $localeValues;
                }
            }
        }

        return $filtered;
    }

    protected function paginateTranslations(array $translations, Request $request): array
    {
        $perPage = config('statamic.translum.pagination.per_page', 50);
        $page = (int) $request->get('page', 1);
        $paginated = [];

        foreach ($translations as $filename => $keys) {
            $keysArray = array_keys($keys);
            $totalKeys = count($keysArray);
            $offset = ($page - 1) * $perPage;

            $paginatedKeys = array_slice($keysArray, $offset, $perPage, true);

            if (!empty($paginatedKeys)) {
                $paginated[$filename] = array_intersect_key($keys, array_flip($paginatedKeys));
            }
        }

        return $paginated;
    }

    protected function flattenAndStoreTranslations(array &$target, string $filename, string $locale, array $data, string $prefix = ''): void
    {
        if (!isset($target[$filename])) {
            $target[$filename] = [];
        }

        foreach ($data as $key => $value) {
            $fullKey = $prefix ? "{$prefix}.{$key}" : $key;

            if (is_array($value) && !empty($value)) {
                $this->flattenAndStoreTranslations($target, $filename, $locale, $value, $fullKey);
            } else {
                if (!isset($target[$filename][$fullKey])) {
                    $target[$filename][$fullKey] = [];
                }
                $target[$filename][$fullKey][$locale] = $value;
            }
        }
    }

    public function clearCache(): void
    {
        $cacheKey = config('statamic.translum.cache.key_prefix', 'translum') . '.translation_data';
        Cache::forget($cacheKey);
    }

    public function getTotalTranslationCount(): int
    {
        $locales = $this->getLocales();
        $translations = $this->getTranslationData($locales);
        $count = 0;

        foreach ($translations as $keys) {
            $count += count($keys);
        }

        return $count;
    }

    public function getTranslationFiles(): array
    {
        $locales = $this->getLocales();
        $translations = $this->getTranslationData($locales);
        return array_keys($translations);
    }
}
