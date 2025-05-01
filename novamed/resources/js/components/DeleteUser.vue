<script setup lang="ts">
import { ref } from 'vue';
import axios from 'axios';

import HeadingSmall from '@/components/HeadingSmall.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useRouter } from 'vue-router';

const router = useRouter();
const passwordInput = ref<HTMLInputElement | null>(null);

const password = ref('');

const isLoading = ref(false);

const errors = ref<{ password?: string[] }>({});

const isDialogOpen = ref(false);

async function deleteUser(e?: Event) {
    if (e) e.preventDefault();

    isLoading.value = true;
    errors.value = {};

    try {

        await axios.delete('/api/v1/user/profile', {
            data: {
                password: password.value,
            }
        });

        closeModal(); // Zamknij dialog
        alert('Twoje konto zostało usunięte.');
        window.location.href = '/';

    } catch (error: any) {
        if (error.response && error.response.status === 422) {
            errors.value = error.response.data.errors;
            passwordInput.value?.focus();
        } else {
            console.error('Błąd usuwania konta:', error);
            alert('Wystąpił błąd podczas usuwania konta.');
        }
    } finally {
        isLoading.value = false;
    }
}

const closeModal = () => {
    isDialogOpen.value = false;
    password.value = '';
    errors.value = {};
    isLoading.value = false;
};
</script>

<template>
    <div class="space-y-6">
        <HeadingSmall title="Usuń konto" description="Usuń swoje konto i wszystkie powiązane z nim zasoby" />
        <div class="space-y-4 rounded-lg border border-red-100 bg-red-50 p-4 dark:border-red-200/10 dark:bg-red-700/10">
            <div class="relative space-y-0.5 text-red-600 dark:text-red-100">
                <p class="font-medium">Uwaga</p>
                <p class="text-sm">Proszę postępować ostrożnie, tej operacji nie można cofnąć.</p>
            </div>
            <Dialog v-model:open="isDialogOpen">
                <DialogTrigger as-child>
                    <Button variant="destructive" @click="isDialogOpen = true">Usuń konto</Button>
                </DialogTrigger>
                <DialogContent>
                    <form class="space-y-6" @submit.prevent="deleteUser">
                        <DialogHeader class="space-y-3">
                            <DialogTitle>Czy na pewno chcesz usunąć swoje konto?</DialogTitle>
                            <DialogDescription>
                                Po usunięciu konta wszystkie powiązane z nim zasoby i dane zostaną trwale usunięte. Wprowadź swoje
                                hasło, aby potwierdzić, że chcesz trwale usunąć swoje konto.
                            </DialogDescription>
                        </DialogHeader>

                        <div class="grid gap-2">
                            <Label for="password" class="sr-only">Hasło</Label>
                            <Input id="password" type="password" name="password" ref="passwordInput" v-model="password" placeholder="Wpisz hasło" />
                            <InputError :message="errors.password ? errors.password[0] : ''" />
                        </div>

                        <DialogFooter class="gap-2">
                            <DialogClose as-child>
                                <Button variant="secondary" type="button" @click="closeModal"> Anuluj </Button>
                            </DialogClose>

                            <!-- Użyj stanu isLoading do wyłączania przycisku -->
                            <Button variant="destructive" :disabled="isLoading" type="submit">
                                {{ isLoading ? 'Usuwanie...' : 'Usuń konto' }}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </div>
    </div>
</template>

<style scoped>
/* Dodaj style dla .error-message, jeśli InputError go wymaga */
.error-message {
    color: red;
    font-size: 0.8em;
}
</style>
