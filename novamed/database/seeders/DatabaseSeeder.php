<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'admin@novamed.test'],
            [
                'name' => 'Admin NovaMed',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'role' => 'admin',
            ]
        );

        User::factory(20)->create();

        User::factory(8)->create(['role' => 'doctor']);

        $this->call([
            ProcedureCategorySeeder::class,
            ProcedureSeeder::class,
            DoctorSeeder::class,
            DoctorProcedureSeeder::class,
            AppointmentSeeder::class,
        ]);

        $this->command->info('Database seeded successfully!');
        $this->command->info('Admin Email: admin@novamed.test');
        $this->command->info('Admin/Patient/Doctor Password: password');
    }
}
