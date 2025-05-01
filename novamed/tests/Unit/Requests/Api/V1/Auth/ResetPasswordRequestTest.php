<?php

namespace Tests\Unit\Requests\Api\V1\Auth;

use App\Http\Requests\Api\V1\Auth\ResetPasswordRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class ResetPasswordRequestTest extends TestCase
{
    /**
     * Test sprawdzający czy autoryzacja zawsze zwraca true.
     */
    public function test_authorize_returns_true()
    {
        $request = new ResetPasswordRequest();
        $this->assertTrue($request->authorize());
    }

    /**
     * Test sprawdzający poprawną walidację z poprawnymi danymi.
     */
    public function test_validation_passes_with_valid_data()
    {
        $rules = (new ResetPasswordRequest())->rules();

        $data = [
            'token' => 'valid-token',
            'email' => 'test@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!'
        ];

        $validator = Validator::make($data, $rules);

        $this->assertFalse($validator->fails());
    }

    /**
     * Test sprawdzający błąd walidacji dla brakującego tokena.
     */
    public function test_validation_fails_when_token_missing()
    {
        $rules = (new ResetPasswordRequest())->rules();

        $data = [
            'email' => 'test@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!'
        ];

        $validator = Validator::make($data, $rules);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('token', $validator->errors()->toArray());
    }

    /**
     * Test sprawdzający błąd walidacji dla niepoprawnego adresu email.
     */
    public function test_validation_fails_with_invalid_email()
    {
        $rules = (new ResetPasswordRequest())->rules();

        $data = [
            'token' => 'valid-token',
            'email' => 'invalid-email',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!'
        ];

        $validator = Validator::make($data, $rules);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('email', $validator->errors()->toArray());
    }

    /**
     * Test sprawdzający błąd walidacji dla niepasujących haseł.
     */
    public function test_validation_fails_with_unconfirmed_password()
    {
        $rules = (new ResetPasswordRequest())->rules();

        $data = [
            'token' => 'valid-token',
            'email' => 'test@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'DifferentPassword123!'
        ];

        $validator = Validator::make($data, $rules);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('password', $validator->errors()->toArray());
    }

    /**
     * Test sprawdzający błąd walidacji dla zbyt słabego hasła.
     */
    public function test_validation_fails_with_weak_password()
    {
        $rules = (new ResetPasswordRequest())->rules();

        $data = [
            'token' => 'valid-token',
            'email' => 'test@example.com',
            'password' => '123',
            'password_confirmation' => '123'
        ];

        $validator = Validator::make($data, $rules);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('password', $validator->errors()->toArray());
    }
}
