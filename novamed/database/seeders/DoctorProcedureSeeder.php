<?php

namespace Database\Seeders;

use App\Models\Doctor;
use App\Models\Procedure;
use App\Models\ProcedureCategory;
use App\Models\DoctorProcedure; // Dodaj import
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DoctorProcedureSeeder extends Seeder
{
    public function run(): void
    {
        $doctors = Doctor::all();
        $categories = ProcedureCategory::all();

        if ($doctors->isEmpty()) {
            $this->command->error('Brak lekarzy w bazie. Uruchom DoctorSeeder jako pierwszy.');
            return;
        }
        if ($categories->isEmpty()) {
            $this->command->error('Brak kategorii procedur w bazie. Uruchom ProcedureCategorySeeder jako pierwszy.');
            return;
        }


        DB::table('doctor_procedure')->delete();


        foreach ($doctors as $doctor) {
            $randomCategoriesCount = rand(1, min(3, $categories->count()));
            if ($randomCategoriesCount === 0) continue;

            $randomCategories = $categories->random($randomCategoriesCount);
            if (!($randomCategories instanceof \Illuminate\Database\Eloquent\Collection)) {
                $randomCategories = collect([$randomCategories]);
            }


            foreach ($randomCategories as $category) {
                $procedures = Procedure::where('procedure_category_id', $category->id)->get();

                if ($procedures->isEmpty()) {
                    continue;
                }

                $proceduresToAttachCount = rand(1, $procedures->count());
                if ($proceduresToAttachCount === 0) continue;

                $proceduresToAttach = $procedures->random($proceduresToAttachCount);
                if (!($proceduresToAttach instanceof \Illuminate\Database\Eloquent\Collection)) {
                    $proceduresToAttach = collect([$proceduresToAttach]);
                }

                foreach ($proceduresToAttach as $procedure) {
                    $exists = DoctorProcedure::where('doctor_id', $doctor->id)
                        ->where('procedure_id', $procedure->id)
                        ->exists();
                    if (!$exists) {
                        DoctorProcedure::factory()
                            ->forDoctor($doctor)
                            ->forProcedure($procedure)
                            ->create();
                    }
                }
            }
        }

        $this->command->info('Przypisano procedury do lekarzy za pomocą fabryki.');
    }
}
