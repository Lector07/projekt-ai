<script setup lang="ts">
import { ref } from 'vue';
import axios from 'axios';
import { useRouter } from 'vue-router';
// Załóżmy, że masz store autoryzacji
import { useAuthStore } from '@/stores/auth';

// Komponenty UI (zakładamy, że są OK)
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue'; // Upewnij się, że używa <router-link>
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthBase from '@/layouts/AuthLayout.vue'; // Sprawdź ten layout
import { LoaderCircle } from 'lucide-vue-next';

const router = useRouter();
const authStore = useAuthStore();

// Stan formularza
const form = ref({
    name: '',
    email: '',
    password: '',
    password_confirmation: '', // Pole wymagane przez walidację 'confirmed'
});

// Stan ładowania i błędów
const isLoading = ref(false);
const errors = ref<Record<string, string[]>>({});

// Funkcja rejestracji
async function submit() {
    isLoading.value = true;
    errors.value = {};

    try {
        // 1. (Opcjonalnie, ale zalecane dla Sanctum SPA) Pobierz ciasteczko CSRF
        await axios.get('/sanctum/csrf-cookie');

        // 2. Wyślij żądanie rejestracji do endpointu API
        // Endpoint zdefiniowany w routes/web.php, używa middleware 'guest'
        await axios.post('/api/v1/register', form.value);

        // 3. Rejestracja udana - teraz można pobrać dane użytkownika
        //    (bo API rejestracji mogło go od razu zalogować lub nie)
        //    lub po prostu przekierować na logowanie lub dashboard
        await authStore.fetchUser(); // Spróbuj pobrać dane, jeśli został zalogowany

        // 4. Przekieruj na dashboard (jeśli został zalogowany) lub na logowanie
        if (authStore.isLoggedIn) {
            router.push({ name: 'dashboard' });
        } else {
            // Możesz pokazać komunikat "Rejestracja udana, zaloguj się" i przekierować
            alert('Rejestracja udana! Zaloguj się, aby kontynuować.');
            router.push({ name: 'login' });
        }

    } catch (error: any) {
        if (error.response && error.response.status === 422) {
            // Błędy walidacji zwrócone przez RegisterRequest
            errors.value = error.response.data.errors;
        } else {
            // Inne błędy
            console.error('Błąd rejestracji:', error);
            errors.value = { general: ['Wystąpił nieoczekiwany błąd podczas rejestracji.'] };
        }
    } finally {
        isLoading.value = false;
        // Nie resetujemy hasła automatycznie po błędzie, aby użytkownik mógł poprawić inne pola
        if (Object.keys(errors.value).length === 0) {
            // Resetuj tylko po sukcesie (chociaż następuje przekierowanie)
            form.value.password = '';
            form.value.password_confirmation = '';
        }
    }
}
</script>

<template>
    <AuthBase title="Create an account" description="Enter your details below to create your account">

        <!-- Wyświetl ogólny błąd, jeśli wystąpił -->
        <div v-if="errors.general" class="mb-4 text-center text-sm font-medium text-red-600">
            {{ errors.general[0] }}
        </div>

        <form @submit.prevent="submit" class="flex flex-col gap-6">
            <div class="grid gap-6">
                <div class="grid gap-2">
                    <Label for="name">Name</Label>
                    <Input id="name" type="text" required autofocus :tabindex="1" autocomplete="name" v-model="form.name" placeholder="Full name" />
                    <InputError :message="errors.name ? errors.name[0] : ''" />
                </div>

                <div class="grid gap-2">
                    <Label for="email">Email address</Label>
                    <Input id="email" type="email" required :tabindex="2" autocomplete="email" v-model="form.email" placeholder="email@example.com" />
                    <InputError :message="errors.email ? errors.email[0] : ''" />
                </div>

                <div class="grid gap-2">
                    <Label for="password">Password</Label>
                    <Input
                        id="password"
                        type="password"
                        required
                        :tabindex="3"
                        autocomplete="new-password"
                        v-model="form.password"
                        placeholder="Password"
                    />
                    <InputError :message="errors.password ? errors.password[0] : ''" />
                </div>

                <div class="grid gap-2">
                    <Label for="password_confirmation">Confirm password</Label>
                    <Input
                        id="password_confirmation"
                        type="password"
                        required
                        :tabindex="4"
                        autocomplete="new-password"
                        v-model="form.password_confirmation"
                        placeholder="Confirm password"
                    />
                    <!-- Błąd dla password_confirmation jest często zawarty w błędzie dla 'password' (reguła 'confirmed') -->
                    <!-- Można też dodać <InputError :message="errors.password_confirmation ? errors.password_confirmation[0] : ''" /> jeśli backend go zwraca osobno -->
                </div>

                <Button type="submit" class="mt-2 w-full" tabindex="5" :disabled="isLoading">
                    <LoaderCircle v-if="isLoading" class="h-4 w-4 animate-spin" />
                    {{ isLoading ? 'Creating Account...' : 'Create account' }}
                </Button>
            </div>

            <div class="text-center text-sm text-muted-foreground">
                Already have an account?
                <!-- Zmień na router-link -->
                <router-link :to="{ name: 'login' }" class="underline underline-offset-4" :tabindex="6">Log in</router-link>
            </div>
        </form>
    </AuthBase>
</template>

<style scoped>
/* Dodaj style dla .error-message, jeśli InputError go wymaga */
.error-message {
    color: red;
    font-size: 0.8em;
}
</style>
