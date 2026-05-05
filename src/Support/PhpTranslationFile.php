<?php

namespace Kreatif\Translum\Support;

use Illuminate\Support\Facades\File;

class PhpTranslationFile
{
    public static function read(string $filePath): array
    {
        if (!File::exists($filePath)) {
            return [];
        }

        self::invalidateOpcache($filePath);

        $data = include $filePath;

        return is_array($data) ? $data : [];
    }

    public static function write(string $filePath, array $data): void
    {
        if (!File::exists(dirname($filePath))) {
            File::makeDirectory(dirname($filePath), 0755, true);
        }

        File::put($filePath, "<?php\n\nreturn " . var_export($data, true) . ";\n");

        self::invalidateOpcache($filePath);
    }

    public static function invalidateOpcache(string $filePath): void
    {
        clearstatcache(true, $filePath);

        if (function_exists('opcache_invalidate')) {
            @opcache_invalidate($filePath, true);
        }
    }
}
