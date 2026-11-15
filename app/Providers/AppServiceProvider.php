<?php

namespace App\Providers;

use App\Models\User;
use App\Modules\Booking\Models\Booking;
use App\Policies\BookingPolicy;
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
        Gate::define('supervisor', fn (User $user) => $user->isSupervisor());
        Gate::policy(Booking::class, BookingPolicy::class);
    }
}
