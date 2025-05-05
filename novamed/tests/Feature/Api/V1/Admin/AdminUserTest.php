<?php

namespace Tests\Feature\Api\V1\Admin;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
// Usunięto UploadedFile i Storage - testy zdjęć dodamy później

class AdminUserTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected Role $adminRole;
    protected Role $patientRole;
    protected User $patientUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminRole = Role::create(['name' => 'Administrator', 'slug' => 'admin']);
        $this->patientRole = Role::create(['name' => 'Pacjent', 'slug' => 'patient']);

        $this->adminUser = User::factory()->create(['email' => 'admin@example.com']);
        $this->adminUser->roles()->attach($this->adminRole->id);

        // Tworzenie pacjenta
        $this->patientUser = User::factory()->create();
        $this->patientUser->roles()->attach($this->patientRole->id);
    }

    /** @test */
    public function admin_can_get_list_of_users(): void
    {
        User::factory()->count(3)->create()->each(fn($user) => $user->roles()->attach($this->patientRole->id)); // Twórz pacjentów

        $response = $this->actingAs($this->adminUser)->getJson('/api/v1/admin/users');

        $response->assertOk();
        $response->assertJsonCount(5, 'data'); // admin, patientUser, 3 nowe
        $response->assertJsonStructure([
            'data' => [
                '*' => [ // Sprawdź klucze z UserResource
                    'id', 'name', 'email', 'email_verified_at', 'profile_picture_url', 'created_at', 'updated_at',
                    'roles' => [ '*' => ['id', 'name', 'slug'] ] // Sprawdź strukturę ról
                ]
            ],
            'links', 'meta'
        ]);
    }

    /** @test */
    public function admin_can_create_a_user_with_roles(): void
    {
        $userData = [
            'name' => 'Nowy Pacjent',
            'email' => 'nowy.pacjent@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'roles' => [$this->patientRole->id] // Przekaż tablicę ID ról
        ];

        $response = $this->actingAs($this->adminUser)->postJson('/api/v1/admin/users', $userData);

        $response->assertCreated();
        $this->assertDatabaseHas('users', ['email' => 'nowy.pacjent@example.com']);
        $newUser = User::where('email', 'nowy.pacjent@example.com')->first();
        $this->assertDatabaseHas('role_user', [
            'user_id' => $newUser->id,
            'role_id' => $this->patientRole->id
        ]);
        $response->assertJsonPath('data.roles.0.slug', 'patient'); // Sprawdź rolę w odpowiedzi
    }

    /** @test */
    public function admin_can_view_a_user(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/v1/admin/users/' . $this->patientUser->id);

        $response->assertOk();
        $response->assertJsonFragment(['id' => $this->patientUser->id]);
        $response->assertJsonPath('data.email', $this->patientUser->email);
        $response->assertJsonCount(1, 'data.roles'); // Sprawdź czy rola jest załadowana
        $response->assertJsonPath('data.roles.0.slug', 'patient');
    }

    /** @test */
    public function admin_can_update_a_user_name_and_email(): void
    {
        $updateData = [
            'name' => 'Zmieniony Pacjent',
            'email' => 'zmieniony.pacjent@example.com',
            // Nie wysyłamy 'roles', aby sprawdzić, czy nie są usuwane
        ];

        $response = $this->actingAs($this->adminUser)
            ->putJson('/api/v1/admin/users/' . $this->patientUser->id, $updateData);

        $response->assertOk();
        $this->assertDatabaseHas('users', [
            'id' => $this->patientUser->id,
            'name' => 'Zmieniony Pacjent',
            'email' => 'zmieniony.pacjent@example.com'
        ]);
        // Upewnij się, że stara rola nadal istnieje
        $this->assertDatabaseHas('role_user', [
            'user_id' => $this->patientUser->id,
            'role_id' => $this->patientRole->id
        ]);
        $response->assertJsonPath('data.name', 'Zmieniony Pacjent');
    }

    /** @test */
    public function admin_can_update_user_roles(): void
    {
        // Nadajemy pacjentowi rolę admina (oprócz pacjenta)
        $updateData = [
            'roles' => [$this->adminRole->id, $this->patientRole->id] // Tablica ID ról do przypisania/synchronizacji
        ];

        $response = $this->actingAs($this->adminUser)
            // Wysyłamy PUT na standardowy endpoint update użytkownika
            ->putJson('/api/v1/admin/users/' . $this->patientUser->id, $updateData);

        $response->assertOk();
        // Sprawdź obie role
        $this->assertDatabaseHas('role_user', [
            'user_id' => $this->patientUser->id,
            'role_id' => $this->adminRole->id
        ]);
        $this->assertDatabaseHas('role_user', [
            'user_id' => $this->patientUser->id,
            'role_id' => $this->patientRole->id
        ]);
        $response->assertJsonCount(2, 'data.roles'); // Powinny być dwie role
    }

    /** @test */
    public function admin_cannot_delete_themselves(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->deleteJson('/api/v1/admin/users/' . $this->adminUser->id);

        $response->assertForbidden(); // Oczekujemy 403
        $this->assertDatabaseHas('users', ['id' => $this->adminUser->id]);
    }

    /** @test */
    public function admin_can_delete_another_user(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->deleteJson('/api/v1/admin/users/' . $this->patientUser->id);

        $response->assertNoContent();
        $this->assertDatabaseMissing('users', ['id' => $this->patientUser->id]);
    }

    /** @test */
    public function admin_receives_validation_errors_when_creating_user_with_invalid_data(): void
    {
        $userData = [
            'name' => '',
            'email' => 'niepoprawny-email',
            'password' => 'short',
            'password_confirmation' => 'short',
            'roles' => [999] // Nieistniejące ID roli
        ];

        $response = $this->actingAs($this->adminUser)->postJson('/api/v1/admin/users', $userData);

        $response->assertUnprocessable(); // 422
        $response->assertJsonValidationErrors(['name', 'email', 'password', 'roles.0']);
    }

    /** @test */
    public function non_admin_cannot_access_admin_user_endpoints(): void
    {
        $newUserData = [ 'name' => 'Test', /* ... */ ]; // Skrócono dla czytelności

        $this->getJson('/api/v1/admin/users')->assertUnauthorized(); // Niezalogowany

        $this->actingAs($this->patientUser)->getJson('/api/v1/admin/users')->assertForbidden();
        $this->actingAs($this->patientUser)->postJson('/api/v1/admin/users', $newUserData)->assertForbidden();
        $this->actingAs($this->patientUser)->getJson('/api/v1/admin/users/' . $this->adminUser->id)->assertForbidden();
        $this->actingAs($this->patientUser)->putJson('/api/v1/admin/users/' . $this->adminUser->id, [])->assertForbidden();
        $this->actingAs($this->patientUser)->deleteJson('/api/v1/admin/users/' . $this->adminUser->id)->assertForbidden();
    }

    /** @test */
    public function admin_can_upload_profile_picture_for_user(): void
    {
        $this->markTestSkipped('Upload zdjęcia profilowego nie jest jeszcze zaimplementowany w kontrolerze.');
    }
}
