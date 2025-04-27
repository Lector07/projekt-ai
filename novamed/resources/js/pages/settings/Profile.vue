<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { useAuthStore } from '@/stores/auth';
import axios from 'axios';
import { useRouter } from 'vue-router';

// Komponenty
import DeleteUser from '@/components/DeleteUser.vue';
import HeadingSmall from '@/components/HeadingSmall.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { type BreadcrumbItem } from '@/types';
import { Transition } from 'vue';

const authStore = useAuthStore();
const user = computed(() => authStore.user);
const router = useRouter();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Ustawienia', href: '/settings' },
    { title: 'Profil', href: '/settings/profile' },
];

const form = ref({
    name: '',
    email: '',
});
const profileErrors = ref<Record<string, string[]>>({});
const profileProcessing = ref(false);
const profileRecentlySuccessful = ref(false);
let profileSuccessTimeout: number | null = null;

const verificationStatus = ref('');

watch(user, (newUser) => {
    if (newUser) {
        form.value.name = newUser.name || '';
        form.value.email = newUser.email || '';
    }
}, { immediate: true });

async function updateProfile() {
    profileProcessing.value = true;
    profileErrors.value = {};
    profileRecentlySuccessful.value = false;
    if (profileSuccessTimeout) clearTimeout(profileSuccessTimeout);

    try {
        const response = await axios.put('/api/v1/user/profile', {
            name: form.value.name,
            email: form.value.email,
        });
        // Zmiana: zamiast nieistniejącej metody setUser używamy bezpośredniego przypisania
        authStore.user = response.data;
        profileRecentlySuccessful.value = true;
        profileSuccessTimeout = window.setTimeout(() => {
            profileRecentlySuccessful.value = false;
        }, 3000);
    } catch (error: any) {
        if (error.response?.status === 422) {
            profileErrors.value = error.response.data.errors;
        } else {
            console.error('Błąd podczas aktualizacji profilu:', error);
            profileErrors.value = { general: ['Wystąpił błąd podczas aktualizacji profilu.'] };
        }
    } finally {
        profileProcessing.value = false;
    }
}

async function sendVerificationEmail() {
    verificationStatus.value = 'Wysyłanie...';
    try {
        await axios.post('/api/v1/email/verification-notification');
        verificationStatus.value = 'verification-link-sent';
    } catch (error) {
        console.error('Błąd wysyłania emaila weryfikacyjnego:', error);
        verificationStatus.value = 'Błąd wysyłania.';
    }
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <SettingsLayout>
            <div class="flex flex-col space-y-6">
                <HeadingSmall title="Profile information" description="Update your name and email address" />

                <div v-if="profileErrors.general" class="text-sm text-red-600 dark:text-red-500">
                    {{ profileErrors.general[0] }}
                </div>

                <form @submit.prevent="updateProfile" class="space-y-6">
                    <div class="grid gap-2">
                        <Label for="name">Name</Label>
                        <Input id="name" v-model="form.name" required autocomplete="name" placeholder="Full name" />
                        <InputError :message="profileErrors.name ? profileErrors.name[0] : ''" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="email">Email address</Label>
                        <Input id="email" type="email" v-model="form.email" required autocomplete="username" placeholder="Email address" />
                        <InputError :message="profileErrors.email ? profileErrors.email[0] : ''" />
                    </div>

                    <div v-if="user && user.email_verified_at === null">
                        <p class="-mt-4 text-sm text-muted-foreground">
                            Your email address is unverified.
                            <button type="button" @click="sendVerificationEmail" class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                Click here to resend the verification email.
                            </button>
                        </p>
                        <div v-if="verificationStatus === 'verification-link-sent'" class="mt-2 text-sm font-medium text-green-600">
                            A new verification link has been sent to your email address.
                        </div>
                        <div v-if="verificationStatus === 'Błąd wysyłania.'" class="mt-2 text-sm font-medium text-red-600">
                            {{ verificationStatus }}
                        </div>
                    </div>

                    <div class="flex items-center gap-4">
                        <Button :disabled="profileProcessing">
                            {{ profileProcessing ? 'Saving...' : 'Save' }}
                        </Button>
                        <Transition
                            enter-active-class="transition ease-in-out duration-300"
                            enter-from-class="opacity-0"
                            leave-active-class="transition ease-in-out duration-300"
                            leave-to-class="opacity-0"
                        >
                            <p v-show="profileRecentlySuccessful" class="text-sm text-neutral-600">Saved.</p>
                        </Transition>
                    </div>
                </form>
            </div>

            <hr class="my-6" />

            <DeleteUser />
        </SettingsLayout>
    </AppLayout>
</template>
