<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\Incident;
use App\Policies\IncidentPolicy;
use Illuminate\Support\Facades\Gate;

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
        Gate::policy(Incident::class, IncidentPolicy::class);
    }
}
