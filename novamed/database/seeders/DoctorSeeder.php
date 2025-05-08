<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
// Usunięto import Role
use App\Models\Doctor;

class DoctorSeeder extends Seeder
{
    public function run(): void
    {
        User::factory(8)->create(['role' => 'doctor'])
        ->each(function ($user) {
            Doctor::factory()->create([
                'user_id' => $user->id,
            ]);
        });

        $this->command->info('Stworzono 8 lekarzy powiązanych z użytkownikami (rola: doctor).');
    }
}
