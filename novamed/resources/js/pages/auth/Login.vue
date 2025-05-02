<script setup lang="ts">
import { ref } from 'vue';
import axios from 'axios';
import { useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';

import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthLayout from '@/layouts/AuthLayout.vue';
import { LoaderCircle } from 'lucide-vue-next';

const router = useRouter();
const authStore = useAuthStore();

const form = ref({
    email: '',
    password: '',
    remember: false,
});

const isLoading = ref(false);
const errors = ref<{ [key: string]: string[] }>({});

async function submit() {
    isLoading.value = true;
    errors.value = {};

    try {
        // Pobierz CSRF token
        await axios.get('/sanctum/csrf-cookie');

        const response = await axios.post('/api/v1/login', form.value, {
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            }
        });

        // Pobierz dane użytkownika
        try {
            await authStore.initAuth();
        } catch (authError) {
            console.error('Błąd podczas inicjalizacji auth:', authError);
        }

        const redirectPath = (router.currentRoute.value.query.redirect as string) || '/dashboard';
        router.push(redirectPath);
    } catch (error: any) {
        if (error.response?.status === 422) {
            errors.value = error.response.data.errors;
        } else {
            console.error('Błąd logowania:', error);
            errors.value = { general: ['Wystąpił nieoczekiwany błąd.'] };
        }
    } finally {
        isLoading.value = false;
    }
}
</script>

<template>
    <AuthLayout title="Zaloguj się na swoje konto" description="Wpisz adres email i hasło aby sie zalogować">
        <div v-if="errors.general" class="mb-4 text-center text-sm font-medium text-red-600">
            {{ errors.general[0] }}
        </div>

        <form @submit.prevent="submit" class="flex flex-col gap-6">
            <div class="grid gap-6">
                <div class="grid gap-2">
                    <Label for="email">Adres Email</Label>
                    <Input
                        id="email"
                        type="email"
                        required
                        autofocus
                        :tabindex="1"
                        autocomplete="email"
                        v-model="form.email"
                        placeholder="email@example.com"
                    />
                    <InputError :message="errors.email ? errors.email[0] : ''" />
                </div>

                <div class="grid gap-2">
                    <div class="flex items-center justify-between">
                        <Label for="password">Hasło</Label>
                    </div>
                    <Input
                        id="password"
                        type="password"
                        required
                        :tabindex="2"
                        autocomplete="current-password"
                        v-model="form.password"
                        placeholder="Password"
                    />
                    <InputError :message="errors.password ? errors.password[0] : ''" />
                </div>

                <div class="flex items-center justify-between">
                    <Label for="remember" class="flex items-center space-x-3">
                        <Checkbox id="remember" v-model="form.remember" :tabindex="3" />
                        <span>Zapamiętaj hasło</span>
                    </Label>
                    <router-link :to="{ name: 'forgot-password' }" :tabindex="4" class="text-sm text-nova-accent hover:text-nova-primary dark:text-nova-accent hover:dark:text-nova-light">Nie pamiętasz hasła?</router-link>
                </div>


                <Button type="submit" class="mt-4 w-full bg-nova-dark hover:bg-nova-accent dark:bg-nova-light hover:dark:bg-nova-accent" :tabindex="5" :disabled="isLoading">
                    <LoaderCircle v-if="isLoading" class="h-4 w-4 animate-spin" />
                    {{ isLoading ? 'Logowanie...' : 'Zaloguj się' }}
                </Button>
            </div>

            <div class="text-center text-sm text-muted-foreground">
                Nie masz konta?
                <router-link :to="{ name: 'register' }" :tabindex="6" class="text-nova-accent hover:text-nova-primary dark:text-nova-light hover:dark:text-nova-accent underline underline-offset-4">Zarejestruj się</router-link>
            </div>
        </form>
    </AuthLayout>
</template>
