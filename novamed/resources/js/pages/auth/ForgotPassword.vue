<script setup lang="ts">
import { ref } from 'vue';
import axios from 'axios';
import { useRouter } from 'vue-router';
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthLayout from '@/layouts/AuthLayout.vue';
import { LoaderCircle } from 'lucide-vue-next';

const router = useRouter();
const isLoading = ref(false);
const status = ref('');
const errors = ref<{ [key: string]: string[] }>({});

const form = ref({
    email: '',
});

async function submit() {
    isLoading.value = true;
    errors.value = {};

    try {
        await axios.get('/sanctum/csrf-cookie');

        const response = await axios.post('/api/v1/forgot-password', form.value, {
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            }
        });

        status.value = response.data.message || 'Wysłano link do resetowania hasła.';
        form.value.email = '';
    } catch (error: any) {
        if (error.response?.status === 422) {
            errors.value = error.response.data.errors;
        } else {
            console.error('Błąd przy wysyłaniu linku:', error);
            errors.value = { email: ['Wystąpił nieoczekiwany błąd.'] };
        }
    } finally {
        isLoading.value = false;
    }
}
</script>

<template>
    <AuthLayout title="Zapomniałeś hasła?" description="Wpisz swój adres e-mail, aby otrzymać link do resetowania hasła.">

        <div v-if="status" class="mb-4 text-center text-sm font-medium text-green-600">
            {{ status }}
        </div>

        <div class="space-y-6">
            <form @submit.prevent="submit">
                <div class="grid gap-2">
                    <Label for="email">Adres email</Label>
                    <Input id="email" type="email" name="email" autocomplete="off" v-model="form.email" autofocus placeholder="email@example.com" />
                    <InputError :message="errors.email ? errors.email[0] : ''" />
                </div>

                <div class="my-6 flex items-center justify-start">
                    <Button class="w-full bg-nova-primary hover:bg-nova-accent" :disabled="isLoading">
                        <LoaderCircle v-if="isLoading" class="h-4 w-4 animate-spin" />
                        Link do resetowania hasła
                    </Button>
                </div>
            </form>

            <div class="space-x-1 text-center text-sm text-muted-foreground">
                <span>Powrót?</span>
                <router-link :to="{ name: 'login' }" class="font-medium text-nova-accent underline underline-offset-4">Zaloguj się</router-link>
            </div>
        </div>
    </AuthLayout>
</template>
