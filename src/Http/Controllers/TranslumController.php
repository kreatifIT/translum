<?php

namespace Kreatif\Translum\Http\Controllers;

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
        $translationService = TranslationService::getInstance();

        // Pass the full request to the service so it can check for query parameters.
        $blueprint = $translationService->buildBlueprint($request);

        $values = $translationService->getInitialValues();
        $fields = $blueprint->fields()->addValues($values)->preProcess();

        return view('translum::index', [
            'title' => __('translum::labels.translum_translations'),
            'action' => cp_route('translum.update'),
            'blueprint' => $blueprint->toPublishArray(),
            'meta' => $fields->meta(),
            'values' => $fields->values(),
        ]);
    }


    public function update(Request $request)
    {
        $translationService = TranslationService::getInstance();

        // The blueprint must be rebuilt with the same context to ensure validation works correctly.
        $blueprint = $translationService->buildBlueprint($request);

        (new SaveTranslations($request->all(), $blueprint, $translationService->getLocales()))->handle();

        return response()->json(['message' => __('translum::labels.translations_saved_successfully')]);
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
