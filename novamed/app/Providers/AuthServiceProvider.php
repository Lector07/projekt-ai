<?php

namespace App\Providers;

use App\Models\Appointment;
use App\Models\ProcedureCategory;
use App\Policies\AppointmentPolicy;
use App\Models\Doctor;
use App\Policies\DoctorPolicy;
use App\Models\Procedure;
use App\Policies\ProcedurePolicy;
use App\Policies\ProcedureCategoryPolicy;
use App\Models\User;
use App\Policies\UserPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\File;



class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        User::class => UserPolicy::class,
        Doctor::class => DoctorPolicy::class,
        Procedure::class => ProcedurePolicy::class,
        Appointment::class => AppointmentPolicy::class,
        ProcedureCategory::class => ProcedureCategoryPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();
    }
}
