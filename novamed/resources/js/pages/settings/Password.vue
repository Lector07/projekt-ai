<script setup lang="ts">
import { ref } from 'vue';
import axios from 'axios';

import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import HeadingSmall from '@/components/HeadingSmall.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { BreadcrumbItem } from '@/types';

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'Ustawienia',
         href: '/settings'
    },
    {
        title: 'Hasło',
    },
];

const passwordInput = ref<HTMLInputElement | null>(null);
const currentPasswordInput = ref<HTMLInputElement | null>(null);

const form = ref({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const isLoading = ref(false);
const errors = ref<Record<string, string[]>>({});
const recentlySuccessful = ref(false);
let successTimeout: number | null = null;

async function updatePassword() {
    isLoading.value = true;
    errors.value = {};
    recentlySuccessful.value = false;
    if (successTimeout) clearTimeout(successTimeout);

    try {
        await axios.put('/api/v1/user/password', form.value);

        recentlySuccessful.value = true;
        form.value.password = '';
        form.value.password_confirmation = '';
        form.value.current_password = '';

        successTimeout = window.setTimeout(() => {
            recentlySuccessful.value = false;
        }, 3000);


    } catch (error: any) {
        if (error.response && error.response.status === 422) {
            errors.value = error.response.data.errors;

            if (errors.value.password) {
                passwordInput.value?.focus();
            } else if (errors.value.current_password) {
                currentPasswordInput.value?.focus();
            }
        } else {
            console.error('Błąd aktualizacji hasła:', error);
            errors.value = { general: ['Wystąpił nieoczekiwany błąd.'] };
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
                <HeadingSmall title="Zmień hasło" description="Upewnij się, że Twoje konto używa długiego, losowego hasła, aby pozostać bezpiecznym." />

                <div v-if="errors.general" class="mb-4 text-center text-sm font-medium text-red-600">
                    {{ errors.general[0] }}
                </div>

                <form @submit.prevent="updatePassword" class="space-y-6">
                    <div class="grid gap-2">
                        <Label for="current_password">Aktualne hasło</Label>
                        <Input
                            id="current_password"
                            ref="currentPasswordInput"
                            v-model="form.current_password"
                            type="password"
                            class="mt-1 block w-full"
                            autocomplete="current-password"
                            placeholder="Podaj aktualne hasło"
                            required
                        />
                        <InputError :message="errors.current_password ? errors.current_password[0] : ''" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="password">Nowe hasło</Label>
                        <Input
                            id="password"
                            ref="passwordInput"
                            v-model="form.password"
                            type="password"
                            class="mt-1 block w-full"
                            autocomplete="new-password"
                            placeholder="Nowe hasło"
                            required
                        />
                        <InputError :message="errors.password ? errors.password[0] : ''" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="password_confirmation">Potwierdź nowe hasło</Label>
                        <Input
                            id="password_confirmation"
                            v-model="form.password_confirmation"
                            type="password"
                            class="mt-1 block w-full"
                            autocomplete="new-password"
                            placeholder="Wpisz ponownie nowe hasło"
                            required
                        />
                        <InputError :message="errors.password_confirmation ? errors.password_confirmation[0] : ''" />
                    </div>

                    <div class="flex items-center gap-4">
                        <Button :disabled="isLoading" class="bg-nova-primary hover:bg-nova-accent dark:bg-nova-light dark:hover:bg-nova-accent">
                            {{ isLoading ? 'Aktualizowanie...' : 'Aktualizuj hasło' }}
                        </Button>

                        <Transition
                            enter-active-class="transition ease-in-out"
                            enter-from-class="opacity-0"
                            leave-active-class="transition ease-in-out"
                            leave-to-class="opacity-0"
                        >
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
