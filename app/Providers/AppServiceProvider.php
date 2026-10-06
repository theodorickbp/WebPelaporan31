<?php

namespace App\Providers;

use App\Models\Report;
use App\Policies\ReportPolicy;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Aplikasi ini satu bahasa: Indonesia.
        $this->app->setLocale('id');
        Carbon::setLocale('id');

        Gate::policy(Report::class, ReportPolicy::class);

        Paginator::defaultView('vendor.pagination.sipasar');
        Paginator::defaultSimpleView('vendor.pagination.sipasar');

        if ($this->app->isProduction()) {
            URL::forceScheme('https');
        }
    }
}
