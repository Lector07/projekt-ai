<?php

namespace Database\Seeders;

use App\Models\User;
// use App\Models\Role; // <<< USUŃ IMPORT ROLE
// use Faker\Factory as Faker; // Nie jest tu potrzebny
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // $this->call(RoleSeeder::class); // <<< USUŃ WYWOŁANIE RoleSeeder

        // Usunięto pobieranie $adminRole i $patientRole

        $this->call(ProcedureCategorySeeder::class); // Wywołaj inne potrzebne seedery

        // Stwórz administratora z rolą 'admin'
        $adminUser = User::firstOrCreate(
            ['email' => 'admin@novamed.test'],
            [
                'name' => 'Admin NovaMed',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'role' => 'admin', // <<< Ustaw rolę bezpośrednio
            ]
        );
        // Usunięto syncWithoutDetaching

        // Stwórz pacjentów (fabryka domyślnie ustawi rolę 'patient')
        User::factory(30)->create();
        // Usunięto pętlę each i sprawdzanie $patientRole


        // Wywołaj seeder dla lekarzy (on ustawi rolę 'doctor' dla userów)
        $this->call(DoctorSeeder::class);

        // Wywołaj pozostałe seedery
        $this->call([
            ProcedureSeeder::class,
            AppointmentSeeder::class,
        ]);

        // ... komunikaty ...
        $this->command->info('Database seeded successfully!');
        $this->command->info('Admin Email: admin@novamed.test');
        $this->command->info('Admin/Patient Password: password');
    }
}
