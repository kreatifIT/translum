<?php

namespace Kreatif\Translum\Console\Commands;

use Illuminate\Console\Command;
use Kreatif\Translum\Support\TranslationService;

class TranslumClearCacheCommand extends Command
{
    protected $signature = 'translum:clear-cache';

    protected $description = 'Clear the Translum translation cache';

    public function handle(): int
    {
        $this->info('Clearing Translum cache...');

        TranslationService::getInstance()->clearCache();

        $this->info('Translum cache cleared successfully!');

        return self::SUCCESS;
    }
}