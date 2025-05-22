<?php

namespace App\Http\Requests\Api\V1\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreDoctorRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'specialization' => 'required|string|max:255',
            'bio' => 'nullable|string',
            'price_modifier' => 'nullable|numeric|min:0.5|max:2',
            'user_id' => 'nullable|exists:users,id',
            'email' => 'required_without:user_id|email|unique:users,email',
            'password' => 'required_without:user_id|string|min:8',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
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

            'email.required_without' => 'Email jest wymagany, gdy nie wybrano istniejącego użytkownika.',
            'email.email' => 'Podany adres email jest nieprawidłowy.',
            'email.unique' => 'Ten adres email jest już zajęty.',

            'password.required_without' => 'Hasło jest wymagane, gdy nie wybrano istniejącego użytkownika.',
            'password.string' => 'Hasło musi być tekstem.',
            'password.min' => 'Hasło musi mieć co najmniej 8 znaków.',
        ];
    }
}
