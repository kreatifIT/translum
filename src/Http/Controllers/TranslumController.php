<?php

namespace Kreatif\Translum\Http\Controllers;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Kreatif\Translum\Actions\SaveTranslations;
use Kreatif\Translum\Support\TranslationService;
use Statamic\Http\Controllers\CP\CpController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TranslumController extends CpController
{

    public function index(Request $request): \Illuminate\Contracts\View\View
    {
        $this->authorize('edit translum');

        $translationService = TranslationService::getInstance();

        // Pass the full request to the service so it can check for query parameters.
        $blueprint = $translationService->buildBlueprint($request);

        $values = $translationService->getInitialValues($request);
        $fields = $blueprint->fields()->addValues($values)->preProcess();

        // Get pagination and search info
        $paginationEnabled = config('statamic.translum.pagination.enabled', true);
        $perPage = config('statamic.translum.pagination.per_page', 50);
        $currentPage = (int) $request->get('page', 1);
        $searchQuery = $request->get('search', '');
        $totalKeys = $translationService->getTotalTranslationCount();
        $totalPages = $paginationEnabled ? (int) ceil($totalKeys / $perPage) : 1;

        return view('translum::index', [
            'title' => __('translum::labels.translum_translations'),
            'action' => cp_route('translum.update'),
            'blueprint' => $blueprint->toPublishArray(),
            'meta' => $fields->meta(),
            'values' => $fields->values(),
            'pagination' => [
                'enabled' => $paginationEnabled,
                'current_page' => $currentPage,
                'per_page' => $perPage,
                'total_pages' => $totalPages,
                'total_keys' => $totalKeys,
            ],
            'search' => [
                'enabled' => config('statamic.translum.search.enabled', true),
                'query' => $searchQuery,
            ],
        ]);
    }


    public function update(Request $request)
    {
        $this->authorize('edit translum');

        $translationService = TranslationService::getInstance();

        // The blueprint must be rebuilt with the same context to ensure validation works correctly.
        $blueprint = $translationService->buildBlueprint($request);

        (new SaveTranslations($request->all(), $blueprint, $translationService->getLocales()))->handle();

        return response()->json(['message' => __('translum::labels.translations_saved_successfully')]);
    }

    public function search(Request $request)
    {
        $this->authorize('edit translum');

        $translationService = TranslationService::getInstance();
        $locales = $translationService->getLocales();
        $allData = $translationService->getTranslationData($locales);

        $search = $request->get('q', '');
        $file = $request->get('file');

        $results = [];

        foreach ($allData as $filename => $keys) {
            if ($file && $filename !== $file) {
                continue;
            }

            foreach ($keys as $key => $values) {
                $matches = false;

                // Search in key
                if (stripos($key, $search) !== false) {
                    $matches = true;
                }

                // Search in values
                if (!$matches && config('statamic.translum.search.search_in_values', true)) {
                    foreach ($values as $locale => $value) {
                        if (is_string($value) && stripos($value, $search) !== false) {
                            $matches = true;
                            break;
                        }
                    }
                }

                if ($matches) {
                    $results[] = [
                        'file' => $filename,
                        'key' => $key,
                        'values' => $values,
                    ];
                }
            }
        }

        return response()->json([
            'results' => $results,
            'count' => count($results),
        ]);
    }

    public function stats(Request $request)
    {
        $this->authorize('view translum stats');

        $translationService = TranslationService::getInstance();
        $locales = $translationService->getLocales();
        $files = $translationService->getTranslationFiles();
        $totalKeys = $translationService->getTotalTranslationCount();

        return response()->json([
            'locales' => $locales,
            'locales_count' => count($locales),
            'files' => $files,
            'files_count' => count($files),
            'total_keys' => $totalKeys,
            'cache_enabled' => config('statamic.translum.cache.enabled', true),
            'pagination_enabled' => config('statamic.translum.pagination.enabled', true),
            'per_page' => config('statamic.translum.pagination.per_page', 50),
        ]);
    }

    public function update_old(Request $request)
    {
        // 1. Validate new keys if the feature is enabled and data is present.
        if (config('statamic.translum.allow_new_keys') && $request->has('new_keys')) {
            $validator = Validator::make($request->all(), [
                // Validate each new key entry for each file.
                'new_keys.*.*.key' => ['required', 'string', 'regex:' . config('statamic.translum.new_key_validation_regex')],
                'new_keys.*.*.values' => ['required', 'array'],
                'new_keys.*.*.values.*' => ['nullable'], // For each locale's value
            ], [
                'new_keys.*.*.key.required' => 'The new translation key is required.',
                'new_keys.*.*.key.regex' => 'The new translation key has an invalid format.',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }
        }

        // 2. Pass all data to the action for processing.
        $translationService = TranslationService::getInstance();
        $blueprint = $translationService->buildBlueprint();
        (new SaveTranslations($request->all(), $blueprint, $translationService->getLocales()))->handle();

        return response()
            ->json(['message' => __('translum::messages.translations_saved_successfully')]);
    }
}
