<?php

namespace Database\Seeders;

use App\Models\Doctor;
use App\Models\Procedure;
use App\Models\ProcedureCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DoctorProcedureSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Pobierz wszystkich lekarzy i kategorie
        $doctors = Doctor::all();
        $categories = ProcedureCategory::all();

        if ($doctors->isEmpty() || $categories->isEmpty()) {
            $this->command->info('Brak lekarzy lub kategorii procedur w bazie. Seeder przerwany.');
            return;
        }

        // Wyczyść istniejące powiązania
        DB::table('doctor_procedure')->delete();

        // Dla każdego lekarza przypisz procedury z losowych kategorii
        foreach ($doctors as $doctor) {
            // Wybierz 1-3 losowe kategorie dla lekarza
            $randomCategories = $categories->random(rand(1, min(3, $categories->count())));

            foreach ($randomCategories as $category) {
                // Pobierz procedury z danej kategorii
                $procedures = Procedure::where('procedure_category_id', $category->id)->get();

                if ($procedures->isEmpty()) {
                    continue;
                }

                // Przypisz wszystkie lub losowe procedury z kategorii do lekarza
                $proceduresToAttach = $procedures->random(rand(1, $procedures->count()));

                foreach ($proceduresToAttach as $procedure) {
                    DB::table('doctor_procedure')->insert([
                        'doctor_id' => $doctor->id,
                        'procedure_id' => $procedure->id,
                    ]);
                }
            }
        }

        $this->command->info('Przypisano procedury do lekarzy.');
    }
}
