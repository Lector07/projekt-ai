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
        <HeadingSmall title="Delete account" description="Delete your account and all of its resources" />
        <div class="space-y-4 rounded-lg border border-red-100 bg-red-50 p-4 dark:border-red-200/10 dark:bg-red-700/10">
            <div class="relative space-y-0.5 text-red-600 dark:text-red-100">
                <p class="font-medium">Warning</p>
                <p class="text-sm">Please proceed with caution, this cannot be undone.</p>
            </div>
            <Dialog v-model:open="isDialogOpen">
                <DialogTrigger as-child>
                    <Button variant="destructive" @click="isDialogOpen = true">Delete account</Button>
                </DialogTrigger>
                <DialogContent>
                    <form class="space-y-6" @submit.prevent="deleteUser">
                        <DialogHeader class="space-y-3">
                            <DialogTitle>Are you sure you want to delete your account?</DialogTitle>
                            <DialogDescription>
                                Once your account is deleted, all of its resources and data will also be permanently deleted. Please enter your
                                password to confirm you would like to permanently delete your account.
                            </DialogDescription>
                        </DialogHeader>

                        <div class="grid gap-2">
                            <Label for="password" class="sr-only">Password</Label>
                            <Input id="password" type="password" name="password" ref="passwordInput" v-model="password" placeholder="Password" />
                            <InputError :message="errors.password ? errors.password[0] : ''" />
                        </div>

                        <DialogFooter class="gap-2">
                            <DialogClose as-child>
                                <Button variant="secondary" type="button" @click="closeModal"> Cancel </Button>
                            </DialogClose>

                            <!-- Użyj stanu isLoading do wyłączania przycisku -->
                            <Button variant="destructive" :disabled="isLoading" type="submit">
                                {{ isLoading ? 'Deleting...' : 'Delete account' }}
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
