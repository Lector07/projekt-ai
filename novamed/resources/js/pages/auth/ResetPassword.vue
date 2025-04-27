<script setup lang="ts">
import { ref, onMounted } from 'vue';
import axios from 'axios';
import { useRouter, useRoute } from 'vue-router'; // Importuj useRoute do odczytu parametrów URL

// Komponenty UI
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthLayout from '@/layouts/AuthLayout.vue'; // Sprawdź ten layout
import { LoaderCircle } from 'lucide-vue-next';

const router = useRouter(); // Router do nawigacji
const route = useRoute(); // useRoute do odczytu parametrów z aktualnej trasy

// Stan formularza
const form = ref({
    token: '', // Pobierzemy z parametrów trasy
    email: '', // Pobierzemy z parametrów trasy (lub query params)
    password: '',
    password_confirmation: '',
});

// Stan ładowania i błędów
const isLoading = ref(false);
const errors = ref<Record<string, string[]>>({});
const status = ref(''); // Do wyświetlania komunikatu sukcesu

// Pobierz token i email z parametrów trasy przy montowaniu komponentu
onMounted(() => {
    // Zakładamy, że token jest parametrem trasy (np. /reset-password/:token)
    form.value.token = route.params.token as string || '';
    // Email może być w query params (np. ?email=...) lub też parametrem trasy
    form.value.email = route.query.email as string || ''; // Przykład dla query param

    if (!form.value.token || !form.value.email) {
        console.error("Brak tokenu lub emaila w parametrach trasy!");
        // Można przekierować lub pokazać błąd
        router.push({ name: 'login' }); // Przekieruj na logowanie, jeśli brak danych
    }
});

// Funkcja wysyłania formularza resetowania hasła
async function submit() {
    isLoading.value = true;
    errors.value = {};
    status.value = ''; // Wyczyść status

    try {
        // 1. (Opcjonalne) Pobierz ciasteczko CSRF
        await axios.get('/sanctum/csrf-cookie');

        // 2. Wyślij żądanie POST do endpointu API resetowania hasła
        // Endpoint musi być zdefiniowany w Laravelu (np. w routes/web.php)
        // i nie powinien wymagać bycia zalogowanym (middleware 'guest')
        const response = await axios.post('/api/v1/reset-password', form.value); // Dostosuj endpoint

        // 3. Sukces - hasło zresetowane
        status.value = response.data.status || 'Hasło zostało pomyślnie zresetowane.'; // Pobierz komunikat z odpowiedzi API
        // Wyczyść formularz (opcjonalnie, bo zaraz przekierujemy)
        form.value.password = '';
        form.value.password_confirmation = '';

        // Przekieruj na stronę logowania po krótkim czasie
        setTimeout(() => {
            router.push({ name: 'login' });
        }, 3000); // Czekaj 3 sekundy

    } catch (error: any) {
        if (error.response && error.response.status === 422) {
            // Błędy walidacji zwrócone przez Laravel
            errors.value = error.response.data.errors;
        } else {
            // Inne błędy
            console.error('Błąd resetowania hasła:', error);
            errors.value = { general: ['Wystąpił nieoczekiwany błąd.'] };
        }
    } finally {
        isLoading.value = false;
    }
}
</script>

<template>
    <AuthLayout title="Reset password" description="Please enter your new password below">

        <!-- Komunikat o sukcesie -->
        <div v-if="status" class="mb-4 text-center text-sm font-medium text-green-600">
            {{ status }}
        </div>

        <!-- Ogólny błąd -->
        <div v-if="errors.general" class="mb-4 text-center text-sm font-medium text-red-600">
            {{ errors.general[0] }}
        </div>

        <!-- Ukryj formularz po sukcesie -->
        <form @submit.prevent="submit" v-if="!status">
            <div class="grid gap-6">
                <div class="grid gap-2">
                    <Label for="email">Email</Label>
                    <!-- Email pobrany z URL, tylko do odczytu -->
                    <Input id="email" type="email" name="email" autocomplete="email" v-model="form.email" class="mt-1 block w-full bg-gray-100 dark:bg-gray-700" readonly />
                    <InputError :message="errors.email ? errors.email[0] : ''" class="mt-2" />
                </div>

                <div class="grid gap-2">
                    <Label for="password">Password</Label>
                    <Input
                        id="password"
                        type="password"
                        name="password"
                        autocomplete="new-password"
                        v-model="form.password"
                        class="mt-1 block w-full"
                        autofocus
                        placeholder="Password"
                        required
                    />
                    <InputError :message="errors.password ? errors.password[0] : ''" />
                </div>

                <div class="grid gap-2">
                    <Label for="password_confirmation"> Confirm Password </Label>
                    <Input
                        id="password_confirmation"
                        type="password"
                        name="password_confirmation"
                        autocomplete="new-password"
                        v-model="form.password_confirmation"
                        class="mt-1 block w-full"
                        placeholder="Confirm password"
                        required
                    />
                    <InputError :message="errors.password_confirmation ? errors.password_confirmation[0] : ''" />
                </div>

                <Button type="submit" class="mt-4 w-full" :disabled="isLoading">
                    <LoaderCircle v-if="isLoading" class="h-4 w-4 animate-spin" />
                    {{ isLoading ? 'Resetting...' : 'Reset password' }}
                </Button>
            </div>
        </form>
    </AuthLayout>
</template>

<style scoped>
.error-message {
    color: red;
    font-size: 0.8em;
}
</style>
