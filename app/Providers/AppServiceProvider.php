<?php

namespace App\Providers;

use App\Models\Student;
use App\Observers\StudentObserver;
use App\Services\PermissionService;
use App\View\Composers\TenantSidebarComposer;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Singleton so its internal per-tenant/per-role lookup caches are
        // shared across an entire request (see PermissionService docblock).
        $this->app->singleton(PermissionService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Blade::if('canModule', function (string $moduleSlug, string $action) {
            $user = auth()->user();

            return $user && $user->canModule($moduleSlug, $action);
        });

        View::composer('layouts.tenant', TenantSidebarComposer::class);

        Student::observe(StudentObserver::class);
    }
}
