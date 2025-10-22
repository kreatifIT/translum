<?php

namespace Kreatif\Translum;

use Kreatif\Translum\Support\TranslationService;
use Kreatif\Translum\Console\Commands\TranslumClearCacheCommand;
use Kreatif\Translum\Console\Commands\TranslumExportCommand;
use Kreatif\Translum\Console\Commands\TranslumListCommand;
use Kreatif\Translum\Console\Commands\TranslumStatsCommand;
use Kreatif\Translum\Console\Commands\TranslumSyncCommand;
use Statamic\Providers\AddonServiceProvider;
use Statamic\Facades\CP\Nav;
use Statamic\Facades\Permission;

class ServiceProvider extends AddonServiceProvider
{

    protected $routes = [
        'cp' => __DIR__.'/../routes/cp.php',
    ];

    protected $commands = [
        TranslumClearCacheCommand::class,
        TranslumExportCommand::class,
        TranslumListCommand::class,
        TranslumStatsCommand::class,
        TranslumSyncCommand::class,
    ];

    public function register()
    {
        parent::register();
        $this->mergeConfigFrom(__DIR__.'/../config/statamic/translum.php', 'statamic.translum');
        $this->app->singleton(TranslationService::class);
    }

    public function bootAddon()
    {
        // Register permissions
        $this->registerPermissions();

        Nav::extend(function ($nav) {
            $nav->content('Translations')
                ->route('translum.index')
                ->icon('dictionary')
                ->can('edit translum');
        });

        // $this->loadRoutesFrom(__DIR__.'/../routes/cp.php');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'translum');
        $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'translum');


        $this->publishes([
            __DIR__.'/../config/statamic/translum.php' => config_path('statamic/translum.php'),
        ], 'translum-config');
        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/translum'),
        ], 'translum-views');
        $this->publishes([
            __DIR__.'/../resources/lang' => resource_path('lang/vendor/translum'),
        ], 'translum-lang');
    }

    protected function registerPermissions(): void
    {
        Permission::extend(function () {
            Permission::register('edit translum', function ($permission) {
                $permission
                    ->label('Edit Translations')
                    ->description('Allows editing of translation files through the Control Panel')
                    ->children([
                        Permission::make('view translum stats')
                            ->label('View Translation Statistics')
                            ->description('View translation statistics and information'),
                        Permission::make('export translum')
                            ->label('Export Translations')
                            ->description('Export translations to JSON or CSV'),
                        Permission::make('clear translum cache')
                            ->label('Clear Translation Cache')
                            ->description('Clear the translation cache'),
                    ]);
            });
        });
    }

}
