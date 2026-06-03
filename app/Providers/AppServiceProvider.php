<?php

namespace App\Providers;

use App\Support\Currency;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;
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
        Blade::directive('lkr', function ($expression) {
            return "<?php echo \\App\\Support\\Currency::formatLkr($expression); ?>";
        });

        Gate::before(function ($user, $ability) {
            return $user->isAdmin() ? true : null;
        });

        foreach (config('cheque.permissions', []) as $permission) {
            Gate::define($permission, function ($user) use ($permission) {
                return $user->hasPermission($permission);
            });
        }
    }
}
