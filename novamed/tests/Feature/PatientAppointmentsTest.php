<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Procedure;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Str;

class PatientAppointmentsTest extends TestCase
{
    use RefreshDatabase;

    protected $patientUser;
    protected $doctor;
    protected $patientRole;
    protected $doctorRole; // Zakładamy, że rola doctor jest tu potrzebna

    protected function setUp(): void
    {
        parent::setUp();

        // Tworzenie ról - dodaj pole 'slug'
        $this->patientRole = Role::create([
            'name' => 'Pacjent', // Możesz użyć polskiej nazwy
            'slug' => 'patient' // <<< DODAJ SLUG
        ]);
        $this->doctorRole = Role::create([
            'name' => 'Lekarz',
            'slug' => 'doctor' // <<< DODAJ SLUG
        ]);

        // Tworzenie użytkownika-pacjenta
        $this->patientUser = User::factory()->create([
            'email' => 'test_patient@example.com',
        ]);
        $this->patientUser->roles()->attach($this->patientRole->id); // Użyj ID stworzonej roli

        // Tworzenie lekarza (bez powiązania z User, zgodnie z migracją)
        $this->doctor = Doctor::factory()->create();
        // Usuń tworzenie doctorUser i powiązanie z nim, jeśli Doctor nie ma user_id

        // Upewnij się, że istnieje kategoria i procedura
        $category = \App\Models\ProcedureCategory::factory()->create();
        Procedure::factory()->create(['id' => 1, 'procedure_category_id' => $category->id]);
    }

    public function test_patient_can_view_their_appointments()
    {
        // Tworzenie wizyt dla pacjenta
        Appointment::factory()->count(3)->create([
            'patient_id' => $this->patientUser->id,
            'doctor_id' => $this->doctor->id
        ]);

        $response = $this->actingAs($this->patientUser)
            ->getJson('/api/v1/patient/appointments');

        $response->assertStatus(200);
    }

    public function test_patient_can_create_appointment()
    {
        $appointmentData = [
            'doctor_id' => $this->doctor->id,
            'procedure_id' => 1,
            'appointment_datetime' => now()->addDays(5)->format('Y-m-d H:i:00'),
            'patient_notes' => 'Test notes'
        ];

        $response = $this->actingAs($this->patientUser)
            ->postJson('/api/v1/patient/appointments', $appointmentData);

        if ($response->status() !== 201) {
            echo "\nRESPONSE: " . $response->content() . "\n";
        }

        $response->assertStatus(201);
    }

    public function test_patient_cannot_see_other_patients_appointments()
    {
        // Tworzenie innego pacjenta
        $otherUser = User::factory()->create();
        $otherUser->roles()->attach($this->patientRole->id);

        // Tworzenie wizyty dla innego pacjenta
        $appointment = Appointment::factory()->create([
            'patient_id' => $otherUser->id,
            'doctor_id' => $this->doctor->id
        ]);

        $response = $this->actingAs($this->patientUser)
            ->getJson('/api/v1/patient/appointments/' . $appointment->id);

        $response->assertForbidden();
    }
}
