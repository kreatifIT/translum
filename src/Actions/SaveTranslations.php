<?php

namespace Kreatif\Translum\Actions;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Statamic\Facades\Stache;
use Statamic\Fields\Blueprint;
use Statamic\Fieldtypes\Bard as BardFieldtype;
use Statamic\Fieldtypes\Bard\Augmentor;

class SaveTranslations
{
    protected array $submittedData;
    protected Blueprint $blueprint;
    protected array $locales;

    public function __construct(array $submittedData, Blueprint $blueprint, array $locales)
    {
        $this->submittedData = $submittedData;
        $this->blueprint = $blueprint;
        $this->locales = $locales;
    }

    public function handle(): void
    {
        $fields = $this->blueprint->fields()->addValues($this->submittedData);
        $fields->validate();
        $processedValues = $fields->process()->values()->all();

        $newKeysData = [];
        foreach ($processedValues as $key => $value) {
            if (str_starts_with($key, 'new_keys_')) {
                $newKeysData[$key] = Arr::pull($processedValues, $key);
            }
        }

        $finalValues = $this->processBardValues($processedValues);
        $translations = $this->unflattenSubmittedData($finalValues);
        $this->saveTranslationsToFiles($translations);
        // $this->saveNewKeys($newKeysData);

        // Clear all relevant caches
        $this->clearAllCaches();
    }

    /**
     * Clear all caches that might contain translation data
     */
    protected function clearAllCaches(): void
    {
        // 1. Always clear Statamic Stache
        Stache::clear();

        // 2. Always clear Translum cache
        \Kreatif\Translum\Support\TranslationService::getInstance()->clearCache();

        // 3. Clear Laravel translation cache if enabled
        if (config('statamic.translum.clear_caches_on_save.translator_cache', true)) {
            if (function_exists('app') && app()->has('translator')) {
                app('translator')->setLoaded([]);
            }
        }

        // 4. Clear view cache if enabled (CRITICAL for fixing cached translations in views)
        if (config('statamic.translum.clear_caches_on_save.view_cache', true)) {
            try {
                Artisan::call('view:clear');
            } catch (\Exception $e) {
                // Silently fail if view:clear is not available
            }
        }

        // 5. Clear general application cache if enabled
        if (config('statamic.translum.clear_caches_on_save.application_cache', false)) {
            try {
                Cache::flush();
            } catch (\Exception $e) {
                // Silently fail if cache flush fails
            }
        }

        // 6. Clear config cache if enabled and exists
        if (config('statamic.translum.clear_caches_on_save.config_cache', false)) {
            if (file_exists(app()->getCachedConfigPath())) {
                try {
                    Artisan::call('config:clear');
                } catch (\Exception $e) {
                    // Silently fail
                }
            }
        }
    }

    private function saveNewKeys(array $newKeysData): void
    {
        foreach ($newKeysData as $replicatorHandle => $sets) {
            $filename = str_replace('new_keys_', '', $replicatorHandle);
            // THE FIX: Get the $index of the set from the loop.
            foreach ($sets as $index => $set) {
                $newKey = $set['key'];
                foreach ($this->locales as $locale) {
                    if (!isset($set[$locale]) || $set[$locale] === null) continue;
                    $filename = str_replace('new_keys_', '', $replicatorHandle);

                    $filePath = resource_path("lang/{$locale}/{$filename}.php");
                    $translations = File::exists($filePath) ? include $filePath : [];
                    // Use the correct $index to build the handle.
                    $fieldHandle = "{$filename}.$newKey.{$index}.type.{$locale}";
                    $processedValue = $this->processSingleValue($set[$locale], $fieldHandle);

                    Arr::set($translations, $newKey, $processedValue);
                    $this->writePhpArrayToFile($filePath, $translations);
                }
            }
        }
    }

    private function processBardValues(array $values): array
    {
        $final = [];
        foreach ($values as $handle => $value) {
            $final[$handle] = $this->processSingleValue($value, $handle);
        }
        return $final;
    }

    private function processSingleValue($value, $handle)
    {
        $field = $this->blueprint->field($handle);
        if ($field && $field->fieldtype() instanceof BardFieldtype && is_array($value)) {
            $html = (new Augmentor($field->fieldtype()))->convertToHtml($value);
            if (config('statamic.translum.strip_wrapping_p', true)) {
                $html = preg_replace('/^<p>(.*?)<\/p>$/s', '$1', $html, 1);
            }
            return $html;
        }

        return $value;
    }

    private function unflattenSubmittedData(array $processedValues): array
    {
        $translations = [];
        foreach ($processedValues as $key => $value) {
            $parts = explode('.', $key);
            if (count($parts) < 3) continue;
            $locale = array_pop($parts);
            $filename = array_shift($parts);
            $translationKey = implode('.', $parts);
            if (!in_array($locale, $this->locales)) continue;
            if ($value !== null) {
                Arr::set($translations[$filename][$locale], $translationKey, $value);
            }
        }
        return $translations;
    }

    private function saveTranslationsToFiles(array $translations): void
    {
        foreach ($translations as $filename => $localesData) {
            foreach ($localesData as $locale => $data) {
                $filePath = resource_path("lang/{$locale}/{$filename}.php");
                $existing = File::exists($filePath) ? include $filePath : [];
                $final = array_replace_recursive($existing, $data);
                $this->writePhpArrayToFile($filePath, $final);
            }
        }
    }

    private function writePhpArrayToFile(string $filePath, array $data): void
    {
        if (!File::exists(dirname($filePath))) {
            File::makeDirectory(dirname($filePath), 0755, true);
        }
        $content = "<?php\n\nreturn " . var_export($data, true) . ";\n";
        File::put($filePath, $content);
    }
}
