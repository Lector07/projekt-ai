<script setup lang="ts">
import {ref, computed, watch} from 'vue';
import {useAuthStore} from '@/stores/auth';
import axios from 'axios';
import {useRouter} from 'vue-router';
import DeleteUser from '@/components/DeleteUser.vue';
import HeadingSmall from '@/components/HeadingSmall.vue';
import InputError from '@/components/InputError.vue';
import {Button} from '@/components/ui/button';
import {Input} from '@/components/ui/input';
import {Label} from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import {type BreadcrumbItem} from '@/types';

const authStore = useAuthStore();
const user = computed(() => authStore.user);
const router = useRouter();

const isAdmin = computed(() => {
    const userValue = user.value;
    const result = userValue?.roles?.includes('admin') ||
        userValue?.role === 'admin' ||
        (typeof userValue?.hasRole === 'function' && userValue.hasRole('admin'));
    return result;
});

const breadcrumbs: BreadcrumbItem[] = [
    {title: 'Ustawienia', href: '/settings'},
    {title: 'Profil', href: '/settings/profile'},
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
}, {immediate: true});

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
            profileErrors.value = {general: ['Wystąpił błąd podczas aktualizacji profilu.']};
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
                <HeadingSmall title="Informacje o profilu" description="Zmień swoje dane."/>

                <div v-if="profileErrors.general" class="text-sm text-red-600 dark:text-red-500">
                    {{ profileErrors.general[0] }}
                </div>

                <form @submit.prevent="updateProfile" class="space-y-6">
                    <div class="grid gap-2">
                        <Label for="name">Imię</Label>
                        <Input id="name" v-model="form.name" required autocomplete="name"
                               placeholder="Podaj pełne imie"/>
                        <InputError :message="profileErrors.name ? profileErrors.name[0] : ''"/>
                    </div>

                    <div class="grid gap-2">
                        <Label for="email">Adres email</Label>
                        <Input id="email" type="email" v-model="form.email" required autocomplete="username"
                               placeholder="Podaj adres email"/>
                        <InputError :message="profileErrors.email ? profileErrors.email[0] : ''"/>
                    </div>

                    <div v-if="user && user.email_verified_at === null">
                        <p class="-mt-4 text-sm text-muted-foreground">
                            Twój adres email nie został zweryfikowany.
                            <button type="button" @click="sendVerificationEmail"
                                    class="underline text-sm text-gray-500 hover:text-nova-accent rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                Kliknij tutaj.
                            </button>
                        </p>
                        <div v-if="verificationStatus === 'verification-link-sent'"
                             class="mt-2 text-sm font-medium text-green-600">
                            Nowy link weryfikacyjny został wysłany na Twój adres email.
                        </div>
                        <div v-if="verificationStatus === 'Błąd wysyłania.'"
                             class="mt-2 text-sm font-medium text-red-600">
                            {{ verificationStatus }}
                        </div>
                    </div>

                    <div class="flex items-center gap-4">
                        <Button :disabled="profileProcessing"
                                class="bg-nova-primary hover:bg-nova-accent dark:bg-nova-light dark:hover:bg-nova-accent">
                            {{ profileProcessing ? 'Zapisywanie...' : 'Zapisz' }}
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
            <hr class="my-6"/>
            <DeleteUser v-if="!isAdmin"/>
        </SettingsLayout>
    </AppLayout>
</template>
