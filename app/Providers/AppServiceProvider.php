<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Illuminate\Routing\Route;
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

        // Konfigurasi Scramble
        Scramble::routes(function (Route $route) {
            $uri = $route->uri();
            return Str::startsWith($uri, 'v1/') && !Str::is('v1/migrate', $uri);
        });

        Scramble::extendOpenApi(function (OpenApi $openApi) {
            // Sort tags alphabetically
            $tags = $openApi->tags;
            usort($tags, function ($a, $b) {
                return strcmp($a->name, $b->name);
            });
            $openApi->tags = $tags;
        });
    }
}
