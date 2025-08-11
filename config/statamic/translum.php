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


];
