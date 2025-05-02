<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Role;
use Faker\Factory as Faker;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {


        $this->call(RoleSeeder::class);

        $adminRole = Role::where('slug', 'admin')->first();
        $patientRole = Role::where('slug', 'patient')->first();

        $this->call(ProcedureCategorySeeder::class);

        $adminUser = User::firstOrCreate(
            ['email' => 'admin@novamed.test'],
            [
                'name' => 'Admin NovaMed',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        if ($adminRole) {
            $adminUser->roles()->syncWithoutDetaching([$adminRole->id]);
        }

        if ($patientRole) {
            User::factory(30)->create()->each(function ($user) use ($patientRole) {
                $user->roles()->attach($patientRole->id);
            });
        } else {
            User::factory(30)->create();
            \Log::warning('Patient role not found during seeding.');
        }

        $this->call(DoctorSeeder::class);

        $this->call(ProcedureSeeder::class);

        $this->call(AppointmentSeeder::class);

        $this->command->info('Database seeded successfully!');
        $this->command->info('Admin Email: admin@novamed.test');
        $this->command->info('Admin/Patient Password: password');
    }
}
