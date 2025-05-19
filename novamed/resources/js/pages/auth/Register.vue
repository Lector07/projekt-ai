<script setup lang="ts">
import { ref } from 'vue';
import axios from 'axios';
import { useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';

import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthBase from '@/layouts/AuthLayout.vue';
import { LoaderCircle } from 'lucide-vue-next';

const router = useRouter();
const authStore = useAuthStore();

const form = ref({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const isLoading = ref(false);
const errors = ref<Record<string, string[]>>({});

axios.defaults.headers.common['Accept'] = 'application/json';
axios.defaults.withCredentials = true;

async function submit() {
    isLoading.value = true;
    errors.value = {};

    try {
        await axios.get('/sanctum/csrf-cookie');

        const registerResponse = await axios.post('/api/v1/register', form.value, {
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            }
        });

        console.log('Rejestracja udana:', registerResponse);

        try {
            const userResponse = await axios.get('/api/v1/user', {
                headers: { 'Accept': 'application/json' }
            });

            if (userResponse.data) {
                authStore.$patch({ user: userResponse.data });
                router.push({ name: 'dashboard' });
                return;
            }
        } catch (authError) {
            console.log('Nie można pobrać danych użytkownika', authError);
        }

        alert('Rejestracja udana! Zaloguj się, aby kontynuować.');
        router.push({ name: 'login' });
    } catch (error: any) {
        if (error.response?.status === 422) {
            errors.value = error.response.data.errors;
        } else {
            console.error('Błąd rejestracji:', error);
            errors.value = { general: ['Wystąpił nieoczekiwany błąd podczas rejestracji.'] };
        }
    } finally {
        isLoading.value = false;
        if (Object.keys(errors.value).length === 0) {
            form.value.password = '';
            form.value.password_confirmation = '';
        }
    }
}
</script>

<template>
    <AuthBase title="Utwórz swoje konto" description="Wpisz dane poniżej aby utworzyć konto">
        <div v-if="errors.general" class="mb-4 text-center text-sm font-medium text-red-600">
            {{ errors.general[0] }}
        </div>

        <form @submit.prevent="submit" class="flex flex-col gap-6">
            <div class="grid gap-6">
                <div class="grid gap-2">
                    <Label for="name">Imie</Label>
                    <Input id="name" type="text" required autofocus :tabindex="1" autocomplete="name" v-model="form.name" placeholder="Imie i nazwisko" />
                    <InputError :message="errors.name ? errors.name[0] : ''" />
                </div>

                <div class="grid gap-2">
                    <Label for="email">Adres Email</Label>
                    <Input id="email" type="email" required :tabindex="2" autocomplete="email" v-model="form.email" placeholder="email@example.com" />
                    <InputError :message="errors.email ? errors.email[0] : ''" />
                </div>

                <div class="grid gap-2">
                    <Label for="password">Hasło</Label>
                    <Input
                        id="password"
                        type="password"
                        required
                        :tabindex="3"
                        autocomplete="new-password"
                        v-model="form.password"
                        placeholder="Hasło"
                    />
                    <InputError :message="errors.password ? errors.password[0] : ''" />
                </div>

                <div class="grid gap-2">
                    <Label for="password_confirmation">Potwierdź hasło</Label>
                    <Input
                        id="password_confirmation"
                        type="password"
                        required
                        :tabindex="4"
                        autocomplete="new-password"
                        v-model="form.password_confirmation"
                        placeholder="Potwierdź hasło"
                    />
                </div>

                <Button type="submit" class="mt-2 w-full bg-nova-dark hover:bg-nova-accent" tabindex="5" :disabled="isLoading">
                    <LoaderCircle v-if="isLoading" class="h-4 w-4 animate-spin" />
                    {{ isLoading ? 'Tworzenie konta...' : 'Utwórz konto' }}
                </Button>
            </div>

            <div class="text-center text-sm text-muted-foreground">
                Masz już konto?
                <router-link :to="{ name: 'login' }" class="underline underline-offset-4 text-nova-accent hover:text-nova-primary" :tabindex="6">Zaloguj się</router-link>
            </div>
        </form>
    </AuthBase>
</template>

<style scoped>
.error-message {
    color: red;
    font-size: 0.8em;
}
</style>
