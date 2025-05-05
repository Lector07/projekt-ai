<?php

namespace Tests\Feature\Api\V1\Admin;

use App\Models\Procedure;
use App\Models\ProcedureCategory;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminProcedureTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $patientUser; // Zmieniono nazwę z regularUser
    protected Role $adminRole;
    protected Role $patientRole;
    protected ProcedureCategory $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminRole = Role::create(['name' => 'Administrator', 'slug' => 'admin']);
        $this->patientRole = Role::create(['name' => 'Pacjent', 'slug' => 'patient']);

        $this->adminUser = User::factory()->create();
        $this->adminUser->roles()->attach($this->adminRole->id);

        $this->patientUser = User::factory()->create(); // Zmieniono nazwę
        $this->patientUser->roles()->attach($this->patientRole->id);

        // Utworzenie kategorii procedur przez fabrykę
        $this->category = ProcedureCategory::factory()->create([
            'name' => 'Kategoria Testowa',
            'slug' => 'kategoria-testowa' // Dodano slug
        ]);
    }

    /** @test */
    public function admin_can_get_list_of_procedures(): void
    {
        Procedure::factory()->count(3)->create([
            'procedure_category_id' => $this->category->id // Poprawiono na procedure_category_id
        ]);

        $response = $this->actingAs($this->adminUser)->getJson('/api/v1/admin/procedures');

        $response->assertOk();
        $response->assertJsonCount(3, 'data');
        $response->assertJsonStructure([
            'data' => [
                '*' => [ // Sprawdź klucze z ProcedureResource
                    'id', 'name', 'description', 'base_price', 'recovery_info', 'category' => ['id', 'name', 'slug']
                ]
            ],
            'links', 'meta'
        ]);
    }

    /** @test */
    public function admin_can_create_a_procedure(): void
    {
        $procedureData = [
            'name' => 'Procedura Testowa Nowa',
            'description' => 'Opis procedury testowej',
            'base_price' => 199.99, // Poprawiono na base_price
            'procedure_category_id' => $this->category->id, // Poprawiono na procedure_category_id
            'recovery_timeline_info' => 'Info o rekonwalescencji.'
        ];

        $response = $this->actingAs($this->adminUser)
            ->postJson('/api/v1/admin/procedures', $procedureData);

        $response->assertCreated();
        $this->assertDatabaseHas('procedures', [
            'name' => 'Procedura Testowa Nowa',
            'base_price' => 199.99, // Poprawiono na base_price
            'procedure_category_id' => $this->category->id // Poprawiono na procedure_category_id
        ]);
    }

    /** @test */
    public function admin_can_view_a_procedure(): void
    {
        $procedure = Procedure::factory()->create([
            'procedure_category_id' => $this->category->id // Poprawiono
        ]);

        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/v1/admin/procedures/' . $procedure->id);

        $response->assertOk();
        $response->assertJsonFragment([
            'id' => $procedure->id,
            'name' => $procedure->name,
        ]);
        $response->assertJsonPath('data.category.id', $this->category->id);
    }

    /** @test */
    public function admin_can_update_a_procedure(): void
    {
        $procedure = Procedure::factory()->create([
            'procedure_category_id' => $this->category->id // Poprawiono
        ]);

        $updateData = [
            'name' => 'Zaktualizowana Nazwa',
            'description' => 'Zaktualizowany opis',
            'base_price' => 299.99, // Poprawiono
        ];

        $response = $this->actingAs($this->adminUser)
            ->putJson('/api/v1/admin/procedures/' . $procedure->id, $updateData);

        $response->assertOk();
        $this->assertDatabaseHas('procedures', [
            'id' => $procedure->id,
            'name' => 'Zaktualizowana Nazwa',
            'base_price' => 299.99, // Poprawiono
        ]);
    }

    /** @test */
    public function admin_can_delete_a_procedure(): void
    {
        $procedure = Procedure::factory()->create([
            'procedure_category_id' => $this->category->id // Poprawiono
        ]);

        $response = $this->actingAs($this->adminUser)
            ->deleteJson('/api/v1/admin/procedures/' . $procedure->id);

        $response->assertNoContent();
        $this->assertDatabaseMissing('procedures', ['id' => $procedure->id]); // Usunięto deleted_at
    }

    /** @test */
    public function admin_receives_validation_errors_when_creating_procedure_with_invalid_data(): void
    {
        $invalidData = [
            'name' => '',
            'base_price' => 'niepoprawnacena', // Poprawiono
            'procedure_category_id' => 999 // Nieistniejąca kategoria
        ];

        $response = $this->actingAs($this->adminUser)
            ->postJson('/api/v1/admin/procedures', $invalidData);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['name', 'base_price', 'procedure_category_id']); // Poprawiono
    }

    /** @test */
    /** @test */
    public function non_admin_cannot_access_admin_procedure_endpoints(): void
    {
        $procedure = Procedure::factory()->create(['procedure_category_id' => $this->category->id]);

        // Test jako pacjent
        $this->actingAs($this->patientUser)->getJson('/api/v1/admin/procedures')->assertForbidden();
        $this->actingAs($this->patientUser)->postJson('/api/v1/admin/procedures', [])->assertForbidden();
        $this->actingAs($this->patientUser)->getJson('/api/v1/admin/procedures/' . $procedure->id)->assertForbidden();
        $this->actingAs($this->patientUser)->putJson('/api/v1/admin/procedures/' . $procedure->id, [])->assertForbidden();
        $this->actingAs($this->patientUser)->deleteJson('/api/v1/admin/procedures/' . $procedure->id)->assertForbidden();

        // Test jako niezalogowany użytkownik - zmieniono oczekiwany kod
        $this->getJson('/api/v1/admin/procedures')->assertForbidden();
    }

    // Test filtrowania można dodać później, jeśli kontroler admina go obsługuje
    // /** @test */
    // public function admin_can_filter_procedures_by_category() { ... }

}
