# Translum - Advanced Translation Manager for Statamic

A powerful and feature-rich translation management package for Statamic CMS that allows clients to edit file-based translations directly from the Control Panel.

## Features

- **Control Panel Interface** - User-friendly interface for managing translations
- **Pagination** - Load translations in chunks for better performance with large files
- **Search & Filter** - Real-time search across translation keys and values
- **File Filtering** - Include/exclude specific translation files with wildcard support
- **Vendor Translations** - Support for vendor package translations
- **Caching** - Intelligent caching layer for improved performance
- **Artisan Commands** - Powerful CLI tools for translation management
- **Multiple Field Types** - Support for text, Bard, and other field types
- **Multi-locale Support** - Edit translations for all configured Statamic locales

## Installation

Install via Composer:

```bash
composer require kreatif/translum
```

Publish the configuration file:

```bash
php artisan vendor:publish --tag=translum-config
```

## Configuration

The configuration file is published to `config/statamic/translum.php`.

### Basic Configuration

```php
return [
    // Field type for translation inputs
    'field_type' => 'bard', // or 'text'

    // Pagination settings
    'pagination' => [
        'enabled' => true,
        'per_page' => 50,
    ],

    // Search functionality
    'search' => [
        'enabled' => true,
        'search_in_values' => true,
        'case_sensitive' => false,
    ],
];
```

### File Filtering

Control which translation files appear in the Control Panel:

```php
'file_filter' => [
    'mode' => 'include', // Options: 'all', 'include', 'exclude'
    'patterns' => [
        'messages',      // Exact match
        'validation',    // Exact match
        'custom/*',      // Wildcard match
    ],
],
```

**Modes:**
- `all` - Load all translation files (default)
- `include` - Only load specified files
- `exclude` - Load all except specified files

### Vendor Translations

Enable editing of vendor package translations:

```php
'vendor_translations' => [
    'enabled' => true,
    'packages' => [
        'statamic',
        'laravel-backup',
        // Add more vendor packages
    ],
],
```

Leave `packages` empty to load all vendor translations.

### Performance Settings

```php
'cache' => [
    'enabled' => true,
    'ttl' => 3600, // Cache for 1 hour
    'key_prefix' => 'translum',
],

'performance' => [
    'lazy_load_tabs' => true,
    'chunk_size' => 100,
],
```

### Cache Clearing on Save

Control which caches are cleared when translations are saved (fixes cached views issue):

```php
'clear_caches_on_save' => [
    'view_cache' => true,        // Clear compiled views (fixes cached translations in views)
    'translator_cache' => true,  // Clear Laravel translator cache
    'application_cache' => false, // Clear application cache (optional)
    'config_cache' => false,     // Clear config cache (usually not needed)
],
```

**Important for Production/Staging:**
- `view_cache` should be `true` to ensure translation changes appear immediately
- `translator_cache` should be `true` to refresh Laravel's translation loader
- `application_cache` can be `false` to avoid clearing other cached data

## Permissions

Translum includes granular permission controls for the Statamic Control Panel.

### Available Permissions

- **Edit Translations** - Main permission to access and edit translations
  - View Translation Statistics - View stats and information
  - Export Translations - Export to JSON/CSV
  - Clear Translation Cache - Clear the cache

### Setting Up Permissions

1. Go to **Users > User Groups** in your Statamic CP
2. Edit a user group or create a new one
3. Under permissions, find "Translum" section
4. Check "Edit Translations" to grant access
5. Optionally grant sub-permissions for stats, export, and cache clearing

Only users with the "Edit Translations" permission will see the Translations menu item and can access the translation editor.

## Usage

### Control Panel

After installation, a "Translations" menu item will appear in your Statamic Control Panel navigation (if you have permission).

1. Click "Translations" in the CP nav
2. Select a file tab to view translations
3. Edit translations for each locale
4. Use the search bar to filter translations
5. Navigate through pages if pagination is enabled
6. Click "Save" to persist changes

### Query Parameters

You can use URL query parameters to filter the translation view:

- `?search=welcome` - Search for translations containing "welcome"
- `?page=2` - Go to page 2 (when pagination is enabled)
- `?search=password&page=1` - Combine search and pagination

## Artisan Commands

### List Translations

Display all translation files and statistics:

```bash
php artisan translum:list
```

Filter by file:

```bash
php artisan translum:list --file=messages
```

Search for specific keys:

```bash
php artisan translum:list --search=password
```

### Translation Statistics

View comprehensive statistics about your translations:

```bash
php artisan translum:stats
```

Shows:
- Number of locales and files
- Translation keys per file
- Missing translations highlighted
- Cache status

### Sync Translations

Add missing translation keys across all locales:

```bash
php artisan translum:sync
```

This command finds keys that exist in one locale but are missing in others and creates them with empty values.

Options:
```bash
# Sync specific locale only
php artisan translum:sync --locale=de

# Preview changes without making them
php artisan translum:sync --dry-run
```

### Export Translations

Export translations to JSON or CSV format:

```bash
# Export to JSON
php artisan translum:export --format=json

# Export to CSV
php artisan translum:export --format=csv

# Export specific file
php artisan translum:export --file=messages --format=json

# Custom output path
php artisan translum:export --format=json --output=/path/to/export.json
```

### Clear Cache

Clear the Translum translation cache:

```bash
php artisan translum:clear-cache
```

Cache is automatically cleared when translations are saved through the Control Panel.

## API Endpoints

### Search Endpoint

Search translations via AJAX:

```
GET /cp/translum/search?q=password&file=messages
```

Response:
```json
{
    "results": [
        {
            "file": "messages",
            "key": "reset_password",
            "values": {
                "en": "Reset Password",
                "de": "Passwort zurücksetzen"
            }
        }
    ],
    "count": 1
}
```

### Stats Endpoint

Get translation statistics:

```
GET /cp/translum/stats
```

Response:
```json
{
    "locales": ["en", "de", "fr"],
    "locales_count": 3,
    "files": ["messages", "validation", "auth"],
    "files_count": 3,
    "total_keys": 150,
    "cache_enabled": true,
    "pagination_enabled": true,
    "per_page": 50
}
```

## Performance Optimization

### For Large Translation Files

If you have many translations and experience slow page loads:

1. **Enable Pagination** (recommended):
```php
'pagination' => [
    'enabled' => true,
    'per_page' => 50, // Adjust based on your needs
],
```

2. **Enable Caching**:
```php
'cache' => [
    'enabled' => true,
    'ttl' => 3600,
],
```

3. **Use File Filtering** to only load necessary files:
```php
'file_filter' => [
    'mode' => 'include',
    'patterns' => ['messages', 'custom/*'],
],
```

4. **Switch to Text Field** instead of Bard for simple translations:
```php
'field_type' => 'text',
```

## Bard Field Configuration

When using Bard for rich text translations:

```php
'field_type' => 'bard',
'field_config' => [
    'bard' => [
        'buttons' => [
            'bold',
            'italic',
            'link',
            'anchor',
            'removeformat',
        ],
        'remove_empty_nodes' => false,
        'smart_typography' => false,
        'save_html' => false,
    ],
],
'strip_wrapping_p' => true, // Remove wrapping <p> tags
```

## Translation File Structure

Translum works with standard Laravel translation files:

```
resources/lang/
├── en/
│   ├── messages.php
│   ├── validation.php
│   └── auth.php
├── de/
│   ├── messages.php
│   ├── validation.php
│   └── auth.php
└── vendor/
    └── statamic/
        ├── en/
        │   └── messages.php
        └── de/
            └── messages.php
```

Nested arrays are automatically flattened:

```php
// Original: resources/lang/en/messages.php
return [
    'user' => [
        'profile' => [
            'title' => 'Profile'
        ]
    ]
];

// Displayed in CP as:
// user.profile.title => "Profile"
```

## Troubleshooting

### Translations Not Updating in Views (Cached Views Issue)

**Problem:** You update translations in the CP, they're saved to the file, but your website still shows old translations.

**Cause:** Laravel compiles Blade views and caches them. The cached views contain the old translation strings.

**Solution:**

1. **Automatic (Recommended):** Enable view cache clearing in config:
```php
'clear_caches_on_save' => [
    'view_cache' => true,        // Enable this!
    'translator_cache' => true,  // Enable this too!
],
```

2. **Manual:** Clear caches manually after saving:
```bash
php artisan view:clear
php artisan cache:clear
php artisan translum:clear-cache
```

3. **For Production/Staging Servers:**
   - Ensure `view_cache` is set to `true` in config
   - Add a deployment script to clear caches:
   ```bash
   php artisan optimize:clear
   php artisan translum:clear-cache
   ```

### Cache Not Clearing

If changes still don't appear immediately after enabling cache clearing:

```bash
# Clear everything manually
php artisan optimize:clear
php artisan translum:clear-cache
php artisan statamic:stache:clear
```

### Missing Translations

Run the sync command to find and add missing keys:

```bash
php artisan translum:sync --dry-run
```

### Performance Issues

1. Enable pagination
2. Reduce `per_page` value
3. Use file filtering to load fewer files
4. Switch from Bard to text field type
5. Enable caching

### File Permissions

Ensure translation files are writable:

```bash
chmod -R 775 resources/lang
```

### Permission Denied

If users can't access the Translations panel:

1. Check user has "Edit Translations" permission
2. Go to Users > User Groups in CP
3. Edit the user's group
4. Enable "Edit Translations" under Translum permissions

## Security

Translation files can contain sensitive strings. Be mindful of:

- **Who has access**: Use Statamic's permission system to control access
- **What translations you expose**: Use file filtering to hide sensitive files
- **Sensitive data**: Don't store API keys or secrets in translation files

### Recommended Permission Setup

- **Admin Role**: Full "Edit Translations" permission
- **Editor Role**: "Edit Translations" + "View Translation Statistics"
- **Content Manager**: "Edit Translations" (without export or cache clearing)
- **Developer Role**: All permissions including export and cache clearing

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for recent changes.

## License

MIT License. See [LICENSE](LICENSE) for details.

## Credits

Developed by [Kreatif](https://github.com/kreatif)

## Support

For issues, questions, or contributions:
- GitHub Issues: [Create an issue](https://github.com/kreatif/translum/issues)
- Documentation: [Full Documentation](https://github.com/kreatif/translum/wiki)
