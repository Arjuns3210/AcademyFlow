<?php

use App\Http\Controllers\SuperAdmin\CouponController;
use App\Http\Controllers\SuperAdmin\InstituteController;
use App\Http\Controllers\SuperAdmin\InstituteTypeController;
use App\Http\Controllers\SuperAdmin\ModuleController;
use App\Http\Controllers\SuperAdmin\PlanController;
use App\Http\Controllers\SuperAdmin\SubscriptionController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'super_admin'])
    ->prefix('super-admin')
    ->name('super-admin.')
    ->group(function () {
        Route::patch('modules/{module}/toggle-status', [ModuleController::class, 'toggleStatus'])
            ->name('modules.toggle-status');
        Route::resource('modules', ModuleController::class)->except(['show']);

        Route::resource('institute-types', InstituteTypeController::class)->except(['show']);

        Route::patch('institutes/{institute}/toggle-status', [InstituteController::class, 'toggleStatus'])
            ->name('institutes.toggle-status');
        Route::post('institutes/{institute}/reset-admin-password', [InstituteController::class, 'resetAdminPassword'])
            ->name('institutes.reset-admin-password');
        Route::resource('institutes', InstituteController::class)->except(['destroy']);

        Route::patch('plans/{plan}/toggle-status', [PlanController::class, 'toggleStatus'])
            ->name('plans.toggle-status');
        Route::resource('plans', PlanController::class)->except(['show']);

        Route::patch('coupons/{coupon}/toggle-status', [CouponController::class, 'toggleStatus'])
            ->name('coupons.toggle-status');
        Route::resource('coupons', CouponController::class)->except(['show']);

        // No create/store/show/destroy — a subscription is auto-created
        // once per institute (Step 7A) and never manually deleted, just
        // activated/renewed or cancelled.
        Route::get('subscriptions', [SubscriptionController::class, 'index'])->name('subscriptions.index');
        Route::get('subscriptions/{subscription}/edit', [SubscriptionController::class, 'edit'])->name('subscriptions.edit');
        Route::put('subscriptions/{subscription}', [SubscriptionController::class, 'update'])->name('subscriptions.update');
        Route::patch('subscriptions/{subscription}/cancel', [SubscriptionController::class, 'cancel'])->name('subscriptions.cancel');
    });
