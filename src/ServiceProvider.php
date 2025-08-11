<?php

namespace Kreatif\Translum;

use Kreatif\Translum\Support\TranslationService;
use Statamic\Providers\AddonServiceProvider;

class ServiceProvider extends AddonServiceProvider
{

    protected $routes = [
        'cp' => __DIR__.'/../routes/cp.php',
    ];

    public function register()
    {
        parent::register();
        $this->mergeConfigFrom(__DIR__.'/../config/statamic/translum.php', 'statamic.translum');
        $this->app->singleton(TranslationService::class);
    }

    public function bootAddon()
    {
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

}
