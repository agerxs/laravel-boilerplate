<?php

namespace App\Providers;

use Illuminate\Support\Facades\Vite;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use App\Models\User;
use App\Models\PaymentRate;

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
        Vite::prefetch(concurrency: 3);
        Gate::before(function (User $user, string $ability) {
            return $user->isSuperAdmin() ? true: null;
        });
        
        // Observateur pour PaymentRate
        PaymentRate::creating(function ($paymentRate) {
            // Si ce taux est actif, désactiver les autres taux pour ce rôle
            if ($paymentRate->is_active) {
                PaymentRate::where('role', $paymentRate->role)
                    ->where('is_active', true)
                    ->update(['is_active' => false]);
            }
        });
        
        PaymentRate::updating(function ($paymentRate) {
            // Si ce taux est actif, désactiver les autres taux pour ce rôle
            if ($paymentRate->is_active && $paymentRate->isDirty('is_active')) {
                PaymentRate::where('role', $paymentRate->role)
                    ->where('id', '!=', $paymentRate->id)
                    ->where('is_active', true)
                    ->update(['is_active' => false]);
            }
        });
    }
}
