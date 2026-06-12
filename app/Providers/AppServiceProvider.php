<?php

namespace App\Providers;

use App\Support\SiteContent;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // One SiteContent per request - every consumer shares the loaded rows.
        $this->app->singleton(SiteContent::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // CMS content accessor ($cms) for every view - lazy, so no query runs
        // until a template actually reads a section (see SiteContent).
        View::share('cms', $this->app->make(SiteContent::class));
    }
}
