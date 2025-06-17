<script setup lang="ts">
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import AuthLayout from '@/layouts/AuthLayout.vue';
import { LoaderCircle } from 'lucide-vue-next';
import axios from 'axios';
import { useRouter } from 'vue-router';

defineProps<{
    status?: string;
}>();

const loading = ref(false);
const message = ref('');
const router = useRouter();

const resendVerification = async () => {
    loading.value = true;
    message.value = '';

    try {
        await axios.post('/api/email/verification-notification');
        message.value = 'Link weryfikacyjny został wysłany na Twój adres email.';
    } catch (error: any) {
        message.value = `Błąd: ${error.response?.data?.message || 'Nie udało się wysłać linku weryfikacyjnego'}`;
    } finally {
        loading.value = false;
    }
};

const logout = async () => {
    try {
        await axios.post('/api/v1/auth/logout');
        localStorage.removeItem('token');
        router.push('/login');
    } catch (error) {
        console.error('Błąd podczas wylogowywania', error);
    }
};
</script>

<template>
    <AuthLayout title="Weryfikacja email" description="Prosimy o weryfikację adresu email poprzez kliknięcie w link, który właśnie wysłaliśmy na Twój adres email.">

        <div v-if="status === 'verification-link-sent'" class="mb-4 text-center text-sm font-medium text-green-600">
            Nowy link weryfikacyjny został wysłany na adres email podany podczas rejestracji.
        </div>

        <div v-if="message" class="mb-4 text-center text-sm font-medium"
             :class="message.includes('Błąd') ? 'text-red-600' : 'text-green-600'">
            {{ message }}
        </div>

        <form @submit.prevent="resendVerification" class="space-y-6 text-center">
            <Button :disabled="loading" variant="secondary">
                <LoaderCircle v-if="loading" class="mr-2 h-4 w-4 animate-spin" />
                Wyślij ponownie email weryfikacyjny
            </Button>

            <button @click="logout" type="button" class="mx-auto block text-sm text-gray-600 hover:text-gray-900">
                Wyloguj się
            </button>
        </form>
    </AuthLayout>
</template>
