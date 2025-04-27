<script setup lang="ts">
import { ref } from 'vue';
import axios from 'axios'; // Importuj Axios

// Komponenty UI i Layouty (zakładamy, że są oczyszczone lub niezależne)
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue'; // Upewnij się, że ten layout jest OK
import HeadingSmall from '@/components/HeadingSmall.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { BreadcrumbItem } from '@/types';
import { Transition } from 'vue'; // Importuj Transition

const breadcrumbItems: BreadcrumbItem[] = [
    // Breadcrumbs mogą wymagać dostosowania, jeśli używały nazw tras Ziggy
    {
        title: 'Ustawienia',
         href: '/settings' // lub routeName: 'settings.index'
    },
    {
        title: 'Hasło',
         href: '/settings/password' // lub routeName: 'settings.password'
    },
];

// Referencje do inputów dla focusa
const passwordInput = ref<HTMLInputElement | null>(null);
const currentPasswordInput = ref<HTMLInputElement | null>(null);

// Stan formularza używając ref
const form = ref({
    current_password: '',
    password: '',
    password_confirmation: '',
});

// Stan ładowania, błędów i sukcesu
const isLoading = ref(false);
const errors = ref<Record<string, string[]>>({});
const recentlySuccessful = ref(false);
let successTimeout: number | null = null; // Do ukrywania komunikatu sukcesu

// Funkcja aktualizacji hasła
async function updatePassword() {
    isLoading.value = true;
    errors.value = {};
    recentlySuccessful.value = false;
    if (successTimeout) clearTimeout(successTimeout); // Wyczyść poprzedni timeout

    try {
        // 1. Pobierz CSRF cookie (jeśli endpoint jest w web.php)
        // await axios.get('/sanctum/csrf-cookie'); // Odkomentuj, jeśli endpoint jest w web.php

        // 2. Wyślij żądanie PUT do endpointu API zmiany hasła
        // Musisz stworzyć ten endpoint w Laravelu, np. w UserProfileController
        await axios.put('/api/v1/user/password', form.value); // Endpoint API

        // 3. Sukces
        recentlySuccessful.value = true;
        // Wyczyść pola formularza
        form.value.password = '';
        form.value.password_confirmation = '';
        form.value.current_password = ''; // Wyczyść też bieżące hasło

        // Ukryj komunikat sukcesu po kilku sekundach
        successTimeout = window.setTimeout(() => {
            recentlySuccessful.value = false;
        }, 3000);

        alert('Hasło zostało pomyślnie zaktualizowane.'); // Lub użyj ładniejszego powiadomienia

    } catch (error: any) {
        if (error.response && error.response.status === 422) {
            // Błędy walidacji (np. obecne hasło nieprawidłowe, nowe za słabe, potwierdzenie się nie zgadza)
            errors.value = error.response.data.errors;

            // Ustaw focus na odpowiednim polu
            if (errors.value.password) {
                passwordInput.value?.focus();
            } else if (errors.value.current_password) {
                currentPasswordInput.value?.focus();
            }
        } else {
            // Inne błędy
            console.error('Błąd aktualizacji hasła:', error);
            errors.value = { general: ['Wystąpił nieoczekiwany błąd.'] }; // Ogólny błąd
            alert('Wystąpił błąd podczas aktualizacji hasła.');
        }
    } finally {
        isLoading.value = false;
    }
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <SettingsLayout>
            <div class="space-y-6">
                <HeadingSmall title="Update password" description="Ensure your account is using a long, random password to stay secure" />

                <!-- Wyświetl ogólny błąd, jeśli wystąpił -->
                <div v-if="errors.general" class="mb-4 text-center text-sm font-medium text-red-600">
                    {{ errors.general[0] }}
                </div>

                <form @submit.prevent="updatePassword" class="space-y-6">
                    <div class="grid gap-2">
                        <Label for="current_password">Current password</Label>
                        <Input
                            id="current_password"
                            ref="currentPasswordInput"
                            v-model="form.current_password"
                            type="password"
                            class="mt-1 block w-full"
                            autocomplete="current-password"
                            placeholder="Current password"
                            required
                        />
                        <!-- Poprawione wyświetlanie błędów -->
                        <InputError :message="errors.current_password ? errors.current_password[0] : ''" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="password">New password</Label>
                        <Input
                            id="password"
                            ref="passwordInput"
                            v-model="form.password"
                            type="password"
                            class="mt-1 block w-full"
                            autocomplete="new-password"
                            placeholder="New password"
                            required
                        />
                        <InputError :message="errors.password ? errors.password[0] : ''" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="password_confirmation">Confirm password</Label>
                        <Input
                            id="password_confirmation"
                            v-model="form.password_confirmation"
                            type="password"
                            class="mt-1 block w-full"
                            autocomplete="new-password"
                            placeholder="Confirm password"
                            required
                        />
                        <InputError :message="errors.password_confirmation ? errors.password_confirmation[0] : ''" />
                    </div>

                    <div class="flex items-center gap-4">
                        <!-- Zmieniono :disabled -->
                        <Button :disabled="isLoading">
                            {{ isLoading ? 'Saving...' : 'Save password' }}
                        </Button>

                        <Transition
                            enter-active-class="transition ease-in-out"
                            enter-from-class="opacity-0"
                            leave-active-class="transition ease-in-out"
                            leave-to-class="opacity-0"
                        >
                            <!-- Zmieniono v-show -->
                            <p v-show="recentlySuccessful" class="text-sm text-neutral-600 dark:text-neutral-400">Saved.</p>
                        </Transition>
                    </div>
                </form>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>

<style scoped>
.error-message {
    color: red;
    font-size: 0.8em;
}
</style>
