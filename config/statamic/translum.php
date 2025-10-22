<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Field type Configuration
    |--------------------------------------------------------------------------
    |
    | Define the default fieldtype for translation inputs and its specific
    | configuration. You can use 'text' or 'bard', or other field types
    |
    */
    'field_type' => 'bard', // Or 'bard' for a rich text editor.
    'field_config' => [
        "bard" => [
            "buttons" => [
                "bold",
                "italic",
                "link",
                "anchor",
                "removeformat",
            ],
            "type" => "bard",
            "remove_empty_nodes" => false,
            "smart_typography" => false,
            "reading_time" => true,
            "word_count" => true,
            "save_html" => false,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Strip Wrapping <p> Tag
    |--------------------------------------------------------------------------
    |
    | When using the Bard fieldtype, the output is often wrapped in a <p>
    | tag. Set this to true to remove the wrapping <p> tag from the
    | final HTML output for single-line content.
    |
    */
    'strip_wrapping_p' => true,

    /*
    |--------------------------------------------------------------------------
    | Allow Adding New Keys
    |--------------------------------------------------------------------------
    |
    | Set this to true to allow users to add new translation keys directly
    | from the Translum control panel interface.
    |
    */
    'allow_new_keys' => false, // doesn't work yet, but will be implemented in the future.

    /*
    |--------------------------------------------------------------------------
    | New Key Validation
    |--------------------------------------------------------------------------
    |
    | Define the regex pattern used to validate new translation keys.
    | The default pattern allows lowercase letters, numbers, underscores,
    | and dots.
    |
    */
    'new_key_validation_regex' => '/^[a-z0-9_.]+$/',

    /*
    |--------------------------------------------------------------------------
    | Pagination
    |--------------------------------------------------------------------------
    |
    | Enable pagination to load translations in chunks for better performance.
    | Recommended when working with large translation files.
    |
    */
    'pagination' => [
        'enabled' => true,
        'per_page' => 50, // Number of translation keys per page
    ],

    /*
    |--------------------------------------------------------------------------
    | Search
    |--------------------------------------------------------------------------
    |
    | Enable search functionality to filter translations by key or value.
    |
    */
    'search' => [
        'enabled' => true,
        'search_in_values' => true, // Search in translation values, not just keys
        'case_sensitive' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | File Filtering
    |--------------------------------------------------------------------------
    |
    | Control which translation files to load in the control panel.
    | You can use 'all', 'include', or 'exclude' mode.
    |
    | Modes:
    |   - 'all': Load all translation files (default)
    |   - 'include': Only load specified files
    |   - 'exclude': Load all except specified files
    |
    | Patterns support wildcards (*) for matching multiple files.
    | Examples: 'messages', 'validation', 'auth', 'custom/*'
    |
    */
    'file_filter' => [
        'mode' => 'all', // Options: 'all', 'include', 'exclude'
        'patterns' => [
            // Examples:
            // 'messages',
            // 'validation',
            // 'auth',
            // 'custom/*',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Vendor Translations
    |--------------------------------------------------------------------------
    |
    | Enable loading translations from vendor packages.
    | Vendor translations are typically located in lang/vendor/{package}
    |
    */
    'vendor_translations' => [
        'enabled' => false,
        'packages' => [
            // Examples:
            // 'statamic',
            // 'laravel-backup',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Caching
    |--------------------------------------------------------------------------
    |
    | Cache translation structure for improved performance.
    | Cache is automatically cleared when translations are saved.
    |
    */
    'cache' => [
        'enabled' => true,
        'ttl' => 3600, // Cache duration in seconds (1 hour)
        'key_prefix' => 'translum',
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance
    |--------------------------------------------------------------------------
    |
    | Performance-related settings for handling large translation files.
    |
    */
    'performance' => [
        'lazy_load_tabs' => true, // Load tab content only when clicked
        'chunk_size' => 100, // Number of keys to process at once
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Clearing on Save
    |--------------------------------------------------------------------------
    |
    | Configure which caches should be cleared when translations are saved.
    | This ensures that updated translations appear immediately in your views.
    |
    */
    'clear_caches_on_save' => [
        'view_cache' => true,        // Clear compiled views (recommended for production)
        'translator_cache' => true,  // Clear Laravel translator cache
        'application_cache' => false, // Clear application cache (use with caution in production)
        'config_cache' => false,     // Clear config cache (usually not needed)
    ],

];
