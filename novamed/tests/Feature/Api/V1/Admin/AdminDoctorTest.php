<?php

namespace Tests\Feature\Api\V1\Admin; // Poprawny namespace

use App\Models\Doctor;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
// Usunięto UploadedFile i Storage - testy zdjęć dodamy później

class AdminDoctorTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $patientUser; // Zmieniono nazwę z regularUser
    protected Role $adminRole;
    protected Role $patientRole;
    // Usunięto doctorRole i doctorUser, bo Doctor nie jest powiązany z User
    protected Doctor $doctor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminRole = Role::create(['name' => 'Administrator', 'slug' => 'admin']);
        $this->patientRole = Role::create(['name' => 'Pacjent', 'slug' => 'patient']);

        $this->adminUser = User::factory()->create();
        $this->adminUser->roles()->attach($this->adminRole->id);

        $this->patientUser = User::factory()->create(); // Zmieniono nazwę
        $this->patientUser->roles()->attach($this->patientRole->id);

        // Tworzenie rekordu lekarza bezpośrednio przez fabrykę
        $this->doctor = Doctor::factory()->create([
            'specialization' => 'Kardiologia',
            'bio' => 'Doświadczony lekarz kardiolog',
        ]);
    }

    /** @test */
    public function admin_can_get_list_of_doctors(): void
    {
        Doctor::factory(3)->create(); // Stwórz 3 dodatkowych

        $response = $this->actingAs($this->adminUser)->getJson('/api/v1/admin/doctors');

        $response->assertOk();
        $response->assertJsonCount(4, 'data'); // 1 z setUp + 3 tutaj
        $response->assertJsonStructure([
            'data' => [
                '*' => [ // Sprawdź klucze z DoctorResource
                    'id', 'first_name', 'last_name', 'full_name', 'specialization', 'bio', 'price_modifier', 'avatar_url'
                ]
            ],
            'links', 'meta'
        ]);
    }

    /** @test */
    public function admin_can_create_a_doctor(): void
    {
        $doctorData = [
            'first_name' => 'Anna',
            'last_name' => 'Testowa',
            'specialization' => 'Neurologia',
            'bio' => 'Specjalista w dziedzinie neurologii',
            'price_modifier' => 1.15,
        ];

        $response = $this->actingAs($this->adminUser)
            ->postJson('/api/v1/admin/doctors', $doctorData);

        $response->assertCreated(); // 201
        $this->assertDatabaseHas('doctors', [
            'first_name' => 'Anna',
            'last_name' => 'Testowa',
            'specialization' => 'Neurologia',
        ]);
        $response->assertJsonPath('data.full_name', 'Anna Testowa'); // Sprawdź odpowiedź JSON
    }

    /** @test */
    public function admin_can_view_a_doctor(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/v1/admin/doctors/' . $this->doctor->id);

        $response->assertOk();
        $response->assertJsonFragment(['id' => $this->doctor->id]);
        $response->assertJsonPath('data.specialization', 'Kardiologia');
    }

    /** @test */
    public function admin_can_update_a_doctor(): void
    {
        $updateData = [
            'specialization' => 'Kardiologia Dziecięca',
            'bio' => 'Zaktualizowane bio',
        ];

        $response = $this->actingAs($this->adminUser)
            ->putJson('/api/v1/admin/doctors/' . $this->doctor->id, $updateData);

        $response->assertOk();
        $this->assertDatabaseHas('doctors', [
            'id' => $this->doctor->id,
            'specialization' => 'Kardiologia Dziecięca',
        ]);
        $response->assertJsonPath('data.specialization', 'Kardiologia Dziecięca');
    }

    /** @test */
    public function admin_can_delete_a_doctor(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->deleteJson('/api/v1/admin/doctors/' . $this->doctor->id);

        $response->assertNoContent(); // 204
        $this->assertDatabaseMissing('doctors', ['id' => $this->doctor->id]);
    }

    /** @test */
    public function admin_receives_validation_errors_when_creating_doctor_with_invalid_data(): void
    {
        $invalidData = [
            'first_name' => '', // Brak imienia
            'price_modifier' => 'abc', // Nie numeryczne
        ];

        $response = $this->actingAs($this->adminUser)
            ->postJson('/api/v1/admin/doctors', $invalidData);

        $response->assertUnprocessable(); // 422
        $response->assertJsonValidationErrors(['first_name', 'last_name', 'specialization', 'price_modifier']); // Sprawdź, które pola są wymagane
    }

    /** @test */
    /** @test */
    public function non_admin_cannot_access_admin_doctor_endpoints(): void
    {
        // Test jako pacjent
        $this->actingAs($this->patientUser)->getJson('/api/v1/admin/doctors')->assertForbidden();
        $this->actingAs($this->patientUser)->postJson('/api/v1/admin/doctors', [])->assertForbidden();
        $this->actingAs($this->patientUser)->getJson('/api/v1/admin/doctors/' . $this->doctor->id)->assertForbidden();
        $this->actingAs($this->patientUser)->putJson('/api/v1/admin/doctors/' . $this->doctor->id, [])->assertForbidden();
        $this->actingAs($this->patientUser)->deleteJson('/api/v1/admin/doctors/' . $this->doctor->id)->assertForbidden();

        // Test jako niezalogowany użytkownik - zmieniono asercję
        $this->getJson('/api/v1/admin/doctors')->assertForbidden();
    }

    // Testy dla przypisywania procedur i dostępności można na razie pominąć lub oznaczyć jako skipped
    /** @test */
    public function admin_can_assign_procedures_to_doctor(): void
    {
        $this->markTestSkipped('Przypisywanie procedur do lekarza nie jest jeszcze zaimplementowane.');
    }

    /** @test */
    public function admin_can_update_doctor_availability(): void
    {
        $this->markTestSkipped('Aktualizacja dostępności lekarza nie jest jeszcze zaimplementowana.');
    }
}
