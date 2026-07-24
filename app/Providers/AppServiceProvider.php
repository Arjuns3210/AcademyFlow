<?php

namespace App\Providers;

use App\Models\Student;
use App\Observers\StudentObserver;
use App\Services\ModuleAccessService;
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

        // Same reasoning - see ModuleAccessService docblock.
        $this->app->singleton(ModuleAccessService::class);
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

        Blade::if('canAccessModule', function (string $moduleKey) {
            $user = auth()->user();

            return $user && $user->canAccessModule($moduleKey);
        });

        View::composer('layouts.tenant', TenantSidebarComposer::class);

        Student::observe(StudentObserver::class);
    }
}
