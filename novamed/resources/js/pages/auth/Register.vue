<script setup lang="ts">
import { ref } from 'vue';
import axios from 'axios';

import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthBase from '@/layouts/auth/AuthSplitRsideLogoLayout.vue';
import { LoaderCircle } from 'lucide-vue-next';
import { useToast } from "primevue/usetoast";
import Toast from "primevue/toast";


const toast = useToast();

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

        toast.add({
            severity: 'success',
            summary: 'Sukces',
            detail: 'Konto zostało utworzone pomyślnie. Zaloguj się, aby kontynuować.',
            life: 3000
        });

        console.log('Rejestracja udana:', registerResponse);

    } catch (error: any) {
        if (error.response?.status === 422) {
            errors.value = error.response.data.errors;
            toast.add({
                severity: 'error',
                summary: 'Błąd walidacji',
                detail: 'Sprawdź poprawność wprowadzonych danych',
                life: 5000
            });
        } else {
            console.error('Błąd rejestracji:', error);
            errors.value = { general: ['Wystąpił nieoczekiwany błąd podczas rejestracji.'] };
            toast.add({
                severity: 'error',
                summary: 'Błąd',
                detail: 'Wystąpił nieoczekiwany błąd podczas rejestracji',
                life: 5000
            });
        }
    } finally {
        isLoading.value = false;
    }
}
</script>

<template>
    <AuthBase title="Utwórz swoje konto" description="Wpisz dane poniżej aby utworzyć konto">
        <Toast />
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
