<?php

namespace App\Providers;

use App\Events\AppointmentScheduled;
use App\Listeners\SendAppointmentScheduledNotification;
use App\Models\Doctor;
use App\Observers\DoctorObserver;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Illuminate\Auth\Notifications\ResetPassword;

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
        ResetPassword::createUrlUsing(function ($user, string $token) {
            return env('FRONTEND_URL', 'http://localhost:5173') .
                '/reset-password/' . $token .
                '?email=' . urlencode($user->getEmailForPasswordReset());
        });

        $this->app->singleton(\Faker\Generator::class, function ($app) {
            return \Faker\Factory::create(config('faker.locale', 'pl_PL'));
        });

        Event::listen(
            AppointmentScheduled::class,
            SendAppointmentScheduledNotification::class
        );
    }

    protected $observers = [
        Doctor::class => [DoctorObserver::class],
    ];
}
