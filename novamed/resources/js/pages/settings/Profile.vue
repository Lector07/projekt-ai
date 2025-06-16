<script setup lang="ts">
import {ref, computed, watch} from 'vue';
import {useAuthStore} from '@/stores/auth';
import axios from 'axios';
import DeleteUser from '@/components/DeleteUser.vue';
import HeadingSmall from '@/components/HeadingSmall.vue';
import InputError from '@/components/InputError.vue';
import {Button} from '@/components/ui/button';
import {Input} from '@/components/ui/input';
import {Label} from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import {type BreadcrumbItem, type User} from '@/types/index';
import Icon from '@/components/Icon.vue';
import {
    Card,
    CardHeader,
    CardTitle,
    CardDescription,
    CardContent
} from '@/components/ui/card';
import { useConfirm } from "primevue/useconfirm";
import { useToast } from "primevue/usetoast";
import Toast from 'primevue/toast';
import ConfirmPopup from 'primevue/confirmpopup';


const toast = useToast();
const confirm = useConfirm();

const authStore = useAuthStore();
const user = computed<User | null>(() => authStore.user);

const isAdmin = computed(() => {
    const userValue = user.value;
    return userValue?.role === 'admin' ||
        (Array.isArray(userValue?.roles) && userValue.roles.includes('admin'));
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

const avatarFile = ref<File | null>(null);
const avatarPreview = ref<string | null>(null);
const avatarInput = ref<HTMLInputElement | null>(null);
const avatarErrors = ref<Record<string, string[]>>({});
const avatarProcessing = ref(false);
const avatarRecentlySuccessful = ref(false);
let avatarSuccessTimeout: number | null = null;
const deletingAvatar = ref(false);

// Dodaj te zmienne po istniejących zmiennych stanu
const resendingVerification = ref(false);
const verificationSent = ref(false);
const status = ref<string | null>(null);

// Dodaj tę funkcję przed końcem skryptu
async function resendVerificationEmail() {
    if (resendingVerification.value) return;

    resendingVerification.value = true;

    try {
        await axios.post('/api/v1/email/verification-notification');
        status.value = 'verification-link-sent';
        verificationSent.value = true;

        setTimeout(() => {
            status.value = null;
            verificationSent.value = false;
        }, 5000);
    } catch (error) {
        console.error('Błąd podczas wysyłania linku weryfikacyjnego:', error);
    } finally {
        resendingVerification.value = false;
    }
}

watch(user, (newUser) => {
    if (newUser) {
        form.value.name = newUser.name || '';
        form.value.email = newUser.email || '';
        avatarPreview.value = newUser.avatar || null;
    } else {
        form.value.name = '';
        form.value.email = '';
        avatarPreview.value = null;
    }
}, {immediate: true, deep: true});

async function updateProfile() {
    profileProcessing.value = true;
    profileErrors.value = {};
    profileRecentlySuccessful.value = false;
    if (profileSuccessTimeout) clearTimeout(profileSuccessTimeout);

    try {
        await axios.put('/api/v1/user/profile', {
            name: form.value.name,
            email: form.value.email,
        });
        await authStore.initAuth();
        profileRecentlySuccessful.value = true;
        profileSuccessTimeout = window.setTimeout(() => {
            profileRecentlySuccessful.value = false;
        }, 3000);
    } catch (error: any) {
        if (error.response?.status === 422) {
            profileErrors.value = error.response.data.errors;
        } else {
            profileErrors.value = {general: ['Wystąpił błąd podczas aktualizacji profilu.']};
        }
    } finally {
        profileProcessing.value = false;
    }
}

function handleAvatarChange(event: Event) {
    const target = event.target as HTMLInputElement;
    if (target.files && target.files[0]) {
        const file = target.files[0];
        const allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
        if (!allowedTypes.includes(file.type)) {
            avatarErrors.value = { avatar: ['Nieprawidłowy format pliku. Dozwolone: JPG, PNG, WEBP.'] };
            avatarFile.value = null;
            if (avatarInput.value) avatarInput.value.value = '';
            return;
        }
        if (file.size > 2 * 1024 * 1024) {
            avatarErrors.value = { avatar: ['Plik jest za duży. Maksymalny rozmiar to 2MB.'] };
            avatarFile.value = null;
            if (avatarInput.value) avatarInput.value.value = '';
            return;
        }
        avatarErrors.value = {};
        avatarFile.value = file;
        const reader = new FileReader();
        reader.onload = (e) => {
            avatarPreview.value = e.target?.result as string;
        };
        reader.readAsDataURL(file);
    } else {
        avatarFile.value = null;
        avatarPreview.value = user.value?.avatar || null;
    }
}

async function uploadAvatar() {
    if (!avatarFile.value) {
        avatarErrors.value = { avatar: ['Najpierw wybierz plik avatara.'] };
        return;
    }
    avatarProcessing.value = true;
    avatarErrors.value = {};
    avatarRecentlySuccessful.value = false;
    if (avatarSuccessTimeout) clearTimeout(avatarSuccessTimeout);

    const formData = new FormData();
    formData.append('avatar', avatarFile.value);

    try {
        await axios.post('/api/v1/user/profile/avatar', formData, {
            headers: { 'Content-Type': 'multipart/form-data' },
        });
        await authStore.initAuth();
        avatarRecentlySuccessful.value = true;
        avatarFile.value = null;
        if (avatarInput.value) avatarInput.value.value = '';
        avatarSuccessTimeout = window.setTimeout(() => {
            avatarRecentlySuccessful.value = false;
        }, 3000);
    } catch (error: any) {
        if (error.response?.status === 422) {
            avatarErrors.value = error.response.data.errors;
        } else {
            console.error('Błąd podczas przesyłania avatara:', error);
            avatarErrors.value = { general: ['Wystąpił błąd podczas przesyłania avatara.'] };
        }
    } finally {
        avatarProcessing.value = false;
    }
}

async function deleteAvatarAction() {
    deletingAvatar.value = true;
    avatarErrors.value = {};

    try {
        await axios.post('/api/v1/user/profile/avatar', {
            _method: 'DELETE'
        });
        await authStore.initAuth();
        avatarPreview.value = null;
        avatarFile.value = null;
        if (avatarInput.value) avatarInput.value.value = '';
        toast.add({
            severity: 'success',
            summary: 'Sukces',
            detail: 'Zdjęcie profilowe zostało usunięte.',
            life: 3000,
        });
    } catch (error: any) {
        console.error('Błąd podczas usuwania avatara:', error);
        toast.add({
            severity: 'error',
            summary: 'Błąd',
            detail: 'Nie udało się usunąć zdjęcia profilowego.',
            life: 3000,
        });
    } finally {
        deletingAvatar.value = false;
    }
}

function deleteAvatar() {
    if (!avatarPreview.value) {
        toast.add({
            severity: 'info',
            summary: 'Informacja',
            detail: 'Brak zdjęcia profilowego do usunięcia.',
            life: 3000,
        });
        return;
    }

    confirm.require({
        message: 'Czy na pewno chcesz usunąć swoje zdjęcie profilowe?',
        header: 'Potwierdzenie',
        icon: 'pi pi-exclamation-triangle',
        acceptLabel: 'Tak',
        rejectLabel: 'Nie',
        accept: () => {
            deleteAvatarAction();
        }
    });
}

const getInitials = (name: string | undefined) => {
    if (!name) return '';
    const parts = name.split(' ');
    if (parts.length > 1 && parts[0] && parts[parts.length - 1]) {
        return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
    } else if (parts.length === 1 && parts[0]) {
        return parts[0].substring(0, 2).toUpperCase();
    }
    return '';
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <SettingsLayout>
            <div class="flex flex-col space-y-6">
                <div class="flex flex-col space-y-6">
                    <div v-if="avatarErrors.general" class="text-sm text-red-600 dark:text-red-500 mb-4">
                        {{ avatarErrors.general[0] }}
                    </div>

                    <Card class="border dark:border-gray-700 dark:bg-gray-800">
                        <CardHeader>
                            <CardTitle class="dark:text-gray-200">Zdjęcie profilowe</CardTitle>
                            <CardDescription class="dark:text-gray-400">Twoje zdjęcie widoczne w aplikacji.</CardDescription>
                        </CardHeader>
                        <CardContent class="flex flex-col items-center gap-4">
                            <img
                                v-if="avatarPreview"
                                :src="avatarPreview"
                                :alt="user && user.name ? `Avatar użytkownika ${user.name}` : 'Avatar użytkownika'"
                                class="h-36 w-36 rounded-full border-4 border-white object-cover shadow-lg dark:border-gray-700"
                            />
                            <div
                                v-else
                                class="h-36 w-36 rounded-full bg-muted flex items-center justify-center text-3xl font-semibold text-foreground/70 border-4 border-white shadow-lg dark:border-gray-700"
                            >
                                {{ user ? getInitials(user.name) : '?' }}
                            </div>

                            <input
                                type="file"
                                ref="avatarInput"
                                @change="handleAvatarChange"
                                accept="image/jpeg,image/png,image/webp"
                                class="hidden"
                                id="user-avatar-input"
                            />

                            <Button
                                type="button"
                                variant="outline"
                                @click="avatarInput?.click()"
                                class="w-full dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700"
                            >
                                <Icon name="upload-cloud" class="mr-2 h-4 w-4" />
                                Zmień zdjęcie
                            </Button>

                            <InputError :message="avatarErrors.avatar?.[0]" />
                            <p class="text-center text-xs text-gray-500 dark:text-gray-400">Maks. 2MB. JPG, PNG, WEBP.</p>

                            <div class="w-full space-y-2">
                                <Button
                                    v-if="avatarFile"
                                    type="button"
                                    @click="uploadAvatar"
                                    :disabled="avatarProcessing"
                                    class="w-full bg-green-600 text-white hover:bg-green-700"
                                >
                                    <Icon v-if="avatarProcessing" name="loader-2" class="mr-2 h-4 w-4" />
                                    Zapisz nowy avatar
                                </Button>

                                <Button
                                    v-if="avatarPreview && !avatarFile"
                                    type="button"
                                    @click="deleteAvatar"
                                    :disabled="deletingAvatar"
                                    variant="destructive"
                                    class="w-full"
                                >
                                    <Icon v-if="deletingAvatar" name="loader-2" class="mr-2 h-4 w-4" />
                                    <Icon v-else name="trash-2" class="mr-2 h-4 w-4" />
                                    Usuń zdjęcie
                                </Button>
                            </div>
                        </CardContent>
                    </Card>

                    <HeadingSmall title="Informacje o profilu" description="Zmień swoje dane." />

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

                        <div v-if="user && !user.email_verified_at" class="mt-2 mb-4">
                            <p class="text-sm text-muted-foreground">
                                Twój adres email nie został jeszcze zweryfikowany.
                                <Button
                                    type="button"
                                    variant="link"
                                    class="p-0 h-auto text-foreground underline decoration-neutral-300 underline-offset-4 hover:decoration-current dark:decoration-neutral-500"
                                    @click="resendVerificationEmail"
                                    :disabled="resendingVerification"
                                >
                                    {{ resendingVerification ? 'Wysyłanie...' : 'Kliknij tutaj, aby ponownie wysłać link weryfikacyjny.' }}
                                </Button>
                            </p>

                            <div v-if="status === 'verification-link-sent'" class="mt-2 text-sm font-medium text-green-600">
                                Nowy link weryfikacyjny został wysłany na Twój adres email.
                            </div>
                        </div>


                        <div class="flex items-center gap-4">
                            <Button :disabled="profileProcessing"
                                    class="bg-nova-primary hover:bg-nova-accent dark:bg-nova-light dark:hover:bg-nova-accent">
                                {{ profileProcessing ? 'Zapisywanie...' : 'Zapisz dane profilu' }}
                            </Button>
                            <Transition
                                enter-active-class="transition ease-in-out duration-300"
                                enter-from-class="opacity-0"
                                leave-active-class="transition ease-in-out duration-300"
                                leave-to-class="opacity-0"
                            >
                                <p v-show="profileRecentlySuccessful" class="text-sm text-green-600">Dane zapisane.</p>
                            </Transition>
                        </div>
                    </form>

                    <hr class="my-6" />
                    <DeleteUser v-if="!isAdmin" />
                </div>
            </div>

            <Toast />
            <ConfirmPopup>
                <template #message="slotProps">
                    <div class="flex flex-col items-center w-full gap-4 border-b border-surface-200 dark:border-surface-700 p-4 mb-4">
                        <i :class="slotProps.message.icon" class="text-6xl text-primary-500"></i>
                        <p>{{ slotProps.message.message }}</p>
                    </div>
                </template>
            </ConfirmPopup>
        </SettingsLayout>
    </AppLayout>
</template>

<style>
.p-confirmpopup {
    background: white !important;
    color: #333 !important;
    border-radius: 0.5rem !important;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06) !important;
    padding: 0 !important;
    overflow: hidden !important;
    border: 1px solid rgba(0, 0, 0, 0.1) !important;
}

/* Tryb ciemny */
.dark .p-confirmpopup {
    background: #1f2937 !important;
    color: #f3f4f6 !important;
    border-color: #374151 !important;
}

.p-confirmpopup-content {
    padding: 1rem !important;
}

.p-confirmpopup-icon {
    color: var(--nova-primary) !important;
    font-size: 1.5rem !important;
    margin-right: 0.75rem !important;
}

.dark .p-confirmpopup-icon {
    color: var(--nova-accent) !important;
}

.p-confirmpopup-message {
    font-size: 1rem !important;
    line-height: 1.5 !important;
    color: #374151 !important;
}

.dark .p-confirmpopup-message {
    color: #e5e7eb !important;
}

.p-confirmpopup-footer {
    display: flex !important;
    justify-content: flex-end !important;
    gap: 0.5rem !important;
    padding: 0.75rem 1rem !important;
    background: #f9fafb !important;
    border-top: 1px solid #e5e7eb !important;
}

.dark .p-confirmpopup-footer {
    background: #111827 !important;
    border-color: #374151 !important;
}

.p-confirmpopup-accept-button {
    background: var(--nova-primary, #034078) !important;
    border-color: var(--nova-primary,#034078) !important;
    color: white !important;
    padding: 0.5rem 1rem !important;
    border-radius: 0.25rem !important;
    font-weight: 500 !important;
    transition: background-color 0.2s !important;
}

.p-confirmpopup-accept-button:hover {
    background: var(--nova-accent, #3d98b2) !important;
    border-color: var(--nova-accent, #034078) !important;
}

.dark .p-confirmpopup-accept-button {
    background: var(--nova-light, #034078) !important;
    border-color: var(--nova-light, #034078) !important;
}

.dark .p-confirmpopup-accept-button:hover {
    background: var(--nova-accent, #3d98b2) !important;
    border-color: var(--nova-accent, #034078) !important;
}

.p-confirmpopup-reject-button {
    background: transparent !important;
    border-color: #d1d5db !important;
    color: #6b7280 !important;
    padding: 0.5rem 1rem !important;
    border-radius: 0.25rem !important;
    font-weight: 500 !important;
    transition: background-color 0.2s, border-color 0.2s !important;
}

.p-confirmpopup-reject-button:hover {
    background: #f3f4f6 !important;
    border-color: #9ca3af !important;
    color: #4b5563 !important;
}

.dark .p-confirmpopup-reject-button {
    border-color: #374151 !important;
    color: #9ca3af !important;
}

.dark .p-confirmpopup-reject-button:hover {
    background: #1f2937 !important;
    border-color: #4b5563 !important;
    color: #e5e7eb !important;
}
</style>

