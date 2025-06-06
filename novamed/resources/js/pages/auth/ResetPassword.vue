<script setup lang="ts">
import { ref, onMounted } from 'vue';
import axios from 'axios';
import { useRouter, useRoute } from 'vue-router';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthLayout from '@/layouts/AuthLayout.vue';
import { LoaderCircle } from 'lucide-vue-next';

const router = useRouter();
const route = useRoute();

// Stan formularza
const form = ref({
    token: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const isLoading = ref(false);
const errors = ref<Record<string, string[]>>({});
const status = ref('');

onMounted(() => {
    form.value.token = route.params.token as string || '';
    form.value.email = route.query.email as string || '';

    if (!form.value.token || !form.value.email) {
        console.error("Brak tokenu lub emaila w parametrach trasy!");
        router.push({ name: 'login' });
    }
});

async function submit() {
    isLoading.value = true;
    errors.value = {};
    status.value = '';

    try {
        await axios.get('/sanctum/csrf-cookie');
        const response = await axios.post('/api/v1/reset-password', form.value);

        status.value = response.data.status || 'Hasło zostało pomyślnie zresetowane.';
        form.value.password = '';
        form.value.password_confirmation = '';

        setTimeout(() => {
            router.push({ name: 'login' });
        }, 3000);

    } catch (error: any) {
        if (error.response && error.response.status === 422) {
            errors.value = error.response.data.errors;
        } else {
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

        <div v-if="status" class="mb-4 text-center text-sm font-medium text-green-600">
            {{ status }}
        </div>

        <div v-if="errors.general" class="mb-4 text-center text-sm font-medium text-red-600">
            {{ errors.general[0] }}
        </div>

        <form @submit.prevent="submit" v-if="!status">
            <div class="grid gap-6">
                <div class="grid gap-2">
                    <Label for="email">Email</Label>
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
