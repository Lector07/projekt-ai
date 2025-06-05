<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Doctor;

class DoctorSeeder extends Seeder
{
    public function run(): void
    {
        $doctorUsers = User::where('role', 'doctor')->whereDoesntHave('doctor')->get();

        if ($doctorUsers->isEmpty()) {
            $this->command->info('Nie znaleziono użytkowników z rolą "doctor" bez profilu lekarza do utworzenia.');
            User::factory(8)->create(['role' => 'doctor']);
            $doctorUsers = User::where('role', 'doctor')->whereDoesntHave('doctor')->get();
            if($doctorUsers->isEmpty()){
                $this->command->error('Nadal brak użytkowników-lekarzy po próbie utworzenia.');
                return;
             }
        }

        $count = 0;
        foreach ($doctorUsers as $user) {
            Doctor::factory()->forUser($user)->create();
            $count++;
        }

        if ($count > 0) {
            $this->command->info("Utworzono {$count} profili lekarzy dla istniejących użytkowników z rolą 'doctor'.");
        }
    }
}
