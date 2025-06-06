<?php

namespace App\Http\Requests\Api\V1\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDoctorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'specialization' => 'required|string|max:255',
            'bio' => 'nullable|string',
            'price_modifier' => 'nullable|numeric|min:0.5|max:2',
            'user_id' => 'nullable|integer|exists:users,id',
            'procedure_ids' => 'nullable|array',
            'procedure_ids.*' => 'integer|exists:procedures,id',
        ];

        if (!$this->input('user_id')) {
            $rules['email'] = 'required|email|max:255|unique:users,email';
            $rules['password'] = 'required|string|min:8';
        } else {
            $rules['email'] = 'sometimes|nullable|email|max:255';
            $rules['password'] = 'sometimes|nullable|string|min:8';
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'first_name.required' => 'Imię jest wymagane.',
            'first_name.string' => 'Imię musi być tekstem.',
            'first_name.max' => 'Imię nie może być dłuższe niż 255 znaków.',

            'last_name.required' => 'Nazwisko jest wymagane.',
            'last_name.string' => 'Nazwisko musi być tekstem.',
            'last_name.max' => 'Nazwisko nie może być dłuższe niż 255 znaków.',

            'specialization.required' => 'Specjalizacja jest wymagana.',
            'specialization.string' => 'Specjalizacja musi być tekstem.',
            'specialization.max' => 'Specjalizacja nie może być dłuższa niż 255 znaków.',

            'bio.string' => 'Biografia musi być tekstem.',

            'price_modifier.numeric' => 'Modyfikator ceny musi być liczbą.',
            'price_modifier.min' => 'Modyfikator ceny musi być większy lub równy 0.5.',
            'price_modifier.max' => 'Modyfikator ceny musi być mniejszy lub równy 2.',

            'user_id.exists' => 'Wybrany użytkownik nie istnieje.',

            'email.required' => 'Email jest wymagany, gdy tworzysz nowego użytkownika dla lekarza.',
            'email.email' => 'Podany adres email jest nieprawidłowy.',
            'email.unique' => 'Ten adres email jest już zajęty.',
            'email.prohibited' => 'Nie można podać email, gdy wybrano istniejącego użytkownika.',

            'password.required' => 'Hasło jest wymagane, gdy tworzysz nowego użytkownika dla lekarza.',
            'password.string' => 'Hasło musi być tekstem.',
            'password.min' => 'Hasło musi mieć co najmniej 8 znaków.',
            'password.prohibited' => 'Nie można podać hasła, gdy wybrano istniejącego użytkownika.',

            'procedure_ids.array' => 'Lista zabiegów musi być tablicą.',
            'procedure_ids.*.exists' => 'Jeden z wybranych zabiegów nie istnieje.',
        ];
    }
}
