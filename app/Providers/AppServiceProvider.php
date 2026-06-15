<?php

namespace App\Providers;

use App\Support\SiteContent;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\View as ViewInstance;

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

        // The signed-in account's pending contract requests for the shared
        // "Szerződés hozzárendelése" card (used on both the dashboard and the
        // Szerződéseim page). Only COMPLETE requests (awaiting approval) are
        // listed - the incomplete "Adatok megadására vár" rows (e.g. the empty
        // row from a data-less registration) are noise and stay hidden; the form
        // completes them. Scoped to the partial so no query runs elsewhere.
        View::composer('partials._add-contract', static function (ViewInstance $view): void {
            $user = Auth::guard('customer')->user();

            $view->with('pendingRequests', $user
                ? $user->contractRequests()->where('status', 'pending')->orderByDesc('id')->get()
                    ->filter->isComplete()->values()
                : collect());
        });
    }
}
