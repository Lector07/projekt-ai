<?php

namespace Tests\Feature\Api\V1\Admin;

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Procedure;
use App\Models\ProcedureCategory;
use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAppointmentTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $patientUser;
    protected Doctor $doctor;
    protected Procedure $procedure;
    protected Appointment $appointment;
    protected Role $adminRole;
    protected Role $patientRole;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminRole = Role::create(['name' => 'Administrator', 'slug' => 'admin']);
        $this->patientRole = Role::create(['name' => 'Pacjent', 'slug' => 'patient']);

        $this->adminUser = User::factory()->create(['email' => 'admin@example.com']);
        $this->adminUser->roles()->attach($this->adminRole->id);

        $this->patientUser = User::factory()->create(['email' => 'pacjent@example.com']);
        $this->patientUser->roles()->attach($this->patientRole->id);

        $this->doctor = Doctor::factory()->create();
        $category = ProcedureCategory::factory()->create();
        $this->procedure = Procedure::factory()->create(['procedure_category_id' => $category->id]);

        $this->appointment = Appointment::factory()->create([
            'patient_id' => $this->patientUser->id,
            'doctor_id' => $this->doctor->id,
            'procedure_id' => $this->procedure->id,
            'appointment_datetime' => Carbon::tomorrow()->setHour(10)->setMinute(0),
            'status' => 'booked',
            'patient_notes' => 'Notatka testowa'
        ]);
    }

    /** @test */
    public function admin_can_get_list_of_appointments(): void
    {
        Appointment::factory()->count(2)->create([
            'patient_id' => $this->patientUser->id,
            'doctor_id' => $this->doctor->id,
            'procedure_id' => $this->procedure->id,
            'appointment_datetime' => Carbon::tomorrow()->addHours(2),
            'status' => 'confirmed'
        ]);

        $response = $this->actingAs($this->adminUser)->getJson('/api/v1/admin/appointments');

        $response->assertOk();
        $response->assertJsonCount(3, 'data'); // 1 z setUp + 2 tutaj
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id', 'status', 'appointment_datetime',
                    'patient', 'doctor', 'procedure'
                ]
            ],
            'links',
            'meta'
        ]);
    }

    /** @test */
    public function admin_can_filter_appointments_by_doctor(): void
    {
        // Usuwamy wszystkie istniejące wizyty
        Appointment::query()->delete();

        // Tworzymy wizytę dla konkretnego lekarza
        $appointment = Appointment::factory()->create([
            'patient_id' => $this->patientUser->id,
            'doctor_id' => $this->doctor->id,
            'procedure_id' => $this->procedure->id,
            'appointment_datetime' => now()->addDay(1)
        ]);

        // Tworzymy wizytę dla innego lekarza
        $anotherDoctor = Doctor::factory()->create();
        Appointment::factory()->create([
            'patient_id' => $this->patientUser->id,
            'doctor_id' => $anotherDoctor->id,
            'procedure_id' => $this->procedure->id,
            'appointment_datetime' => now()->addDay(2)
        ]);

        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/v1/admin/appointments?doctor_id=' . $this->doctor->id);

        $response->assertOk();

        // Sprawdzamy tylko, czy w odpowiedzi jest rekord z właściwym ID lekarza
        $responseData = $response->json('data');
        $doctorFound = false;
        foreach ($responseData as $item) {
            if ($item['doctor']['id'] === $this->doctor->id) {
                $doctorFound = true;
                break;
            }
        }

        $this->assertTrue($doctorFound, 'Nie znaleziono wizyty dla wybranego lekarza w odpowiedzi API');
    }

    /** @test */
    public function admin_can_filter_appointments_by_patient(): void
    {
        // Usuwamy wszystkie istniejące wizyty
        Appointment::query()->delete();

        // Tworzymy wizytę dla naszego pacjenta
        $appointment = Appointment::factory()->create([
            'patient_id' => $this->patientUser->id,
            'doctor_id' => $this->doctor->id,
            'procedure_id' => $this->procedure->id,
            'appointment_datetime' => now()->addDay(1)
        ]);

        // Tworzymy wizytę dla innego pacjenta
        $anotherPatient = User::factory()->create();
        $anotherPatient->roles()->attach($this->patientRole->id);
        Appointment::factory()->create([
            'patient_id' => $anotherPatient->id,
            'doctor_id' => $this->doctor->id,
            'procedure_id' => $this->procedure->id,
            'appointment_datetime' => now()->addDay(2)
        ]);

        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/v1/admin/appointments?patient_id=' . $this->patientUser->id);

        $response->assertOk();

        // Sprawdzamy tylko, czy w odpowiedzi jest rekord z właściwym ID pacjenta
        $responseData = $response->json('data');
        $patientFound = false;
        foreach ($responseData as $item) {
            if ($item['patient']['id'] === $this->patientUser->id) {
                $patientFound = true;
                break;
            }
        }

        $this->assertTrue($patientFound, 'Nie znaleziono wizyty dla wybranego pacjenta w odpowiedzi API');
    }

    /** @test */
    public function admin_can_filter_appointments_by_status(): void
    {
        // Usuwamy wszystkie istniejące wizyty
        Appointment::query()->delete();

        // Tworzymy wizytę ze statusem "booked"
        Appointment::factory()->create([
            'patient_id' => $this->patientUser->id,
            'doctor_id' => $this->doctor->id,
            'procedure_id' => $this->procedure->id,
            'status' => 'booked',
            'appointment_datetime' => now()->addDay(1)
        ]);

        // Tworzymy wizytę ze statusem "confirmed"
        $confirmedAppointment = Appointment::factory()->create([
            'patient_id' => $this->patientUser->id,
            'doctor_id' => $this->doctor->id,
            'procedure_id' => $this->procedure->id,
            'status' => 'confirmed',
            'appointment_datetime' => now()->addDay(2)
        ]);

        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/v1/admin/appointments?status=confirmed');

        $response->assertOk();
        // Sprawdzamy, czy w odpowiedzi jest wizyta o statusie "confirmed"
        $response->assertJsonPath('data.0.status', 'confirmed');
    }
}
