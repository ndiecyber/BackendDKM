<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
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
        // Super-admin bypasses all permission checks
        Gate::before(function ($user, $ability) {
            return $user->hasRole('super-admin') ? true : null;
        });

        // Allow access to Scramble API Docs based on environment
        Gate::define('viewApiDocs', function (?User $user) {
            return config('app.env') === 'local' || filter_var(config('app.enable_api_docs'), FILTER_VALIDATE_BOOLEAN);
        });
    }
}
