<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
           if (env('APP_ENV') === 'production') {
        URL::forceScheme('https');
    }

     ini_set('memory_limit', '1024M');
        ini_set('max_execution_time', '600');
        ini_set('max_input_time', '300');
        ini_set('upload_max_filesize', '200M');
        ini_set('post_max_size', '200M');
    }
}
