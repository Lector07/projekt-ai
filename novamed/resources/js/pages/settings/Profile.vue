<script setup lang="ts">
import {ref, computed, watch} from 'vue';
import {useAuthStore} from '@/stores/auth';
import axios from 'axios';
import DeleteUser from '@/components/DeleteUser.vue';
import HeadingSmall from '@/components/HeadingSmall.vue';
import InputError from '@/components/InputError.vue';
import {Button} from '@/components/ui/button';
import {Input} from '@/components/ui/input'; // Zakładam, że to Twój komponent Input z UI
import {Label} from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import {type BreadcrumbItem, type User} from '@/types/index'; // Upewnij się, że User jest poprawnie importowany
import Icon from '@/components/Icon.vue';

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

// --- Logika dla Avatara ---
const avatarFile = ref<File | null>(null);
const avatarPreview = ref<string | null>(null);
const avatarInput = ref<HTMLInputElement | null>(null);
const avatarErrors = ref<Record<string, string[]>>({});
const avatarProcessing = ref(false);
const avatarRecentlySuccessful = ref(false);
let avatarSuccessTimeout: number | null = null;

watch(user, (newUser) => {
    console.log('[Profile.vue Watcher] New user data from store:', JSON.parse(JSON.stringify(newUser)));
    if (newUser) {
        form.value.name = newUser.name || '';
        form.value.email = newUser.email || '';
        // UŻYJ 'avatar' jeśli tak zwraca UserResource i tak jest w typie User
        // LUB 'profile_picture_url' jeśli tak jest spójnie
        avatarPreview.value = newUser.avatar || null;
        console.log('[Profile.vue Watcher] avatarPreview set to:', avatarPreview.value);
    } else {
        form.value.name = '';
        form.value.email = '';
        avatarPreview.value = null;
        console.log('[Profile.vue Watcher] avatarPreview set to null because newUser is null.');
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
            console.error('Błąd podczas aktualizacji profilu:', error);
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
        if (file.size > 2 * 1024 * 1024) { // 2MB
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
        // Przywróć oryginalny avatar ze store, jeśli anulowano wybór
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
        await authStore.initAuth(); // To odświeży user.value, co z kolei zaktualizuje avatarPreview przez watch
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
            <!-- Sekcja Avatara -->
            <div class="flex flex-col space-y-6">
                <HeadingSmall title="Zdjęcie profilowe" description="Zmień swoje zdjęcie profilowe." />

                <div v-if="avatarErrors.general" class="text-sm text-red-600 dark:text-red-500 mb-4">
                    {{ avatarErrors.general[0] }}
                </div>

                <div class="flex flex-col items-start gap-4">
                    <div class="relative">
                        <img
                            v-if="avatarPreview"
                            :src="avatarPreview"
                            :alt="user && user.name ? `Avatar użytkownika ${user.name}` : 'Avatar użytkownika'"
                            class="h-32 w-32 rounded-full object-cover border-2 border-muted"
                        />
                        <div v-else class="h-32 w-32 rounded-full bg-muted flex items-center justify-center text-4xl font-semibold text-foreground/70 border-2 border-muted">
                            {{ user ? getInitials(user.name) : '?' }}
                        </div>
                        <Button
                            variant="outline"
                            size="icon"
                            class="absolute bottom-0 right-0 rounded-full bg-background hover:bg-muted p-1.5"
                            @click="avatarInput?.click()"
                            title="Zmień avatar"
                        >
                            <Icon name="camera" class="h-5 w-5" />
                        </Button>
                    </div>

                    <input
                        id="avatar-upload"
                        ref="avatarInput"
                        type="file"
                        class="hidden"
                        accept="image/jpeg,image/png,image/webp"
                        @change="handleAvatarChange"
                    />
                    <InputError :message="avatarErrors.avatar ? avatarErrors.avatar[0] : ''" />
                    <p class="text-xs text-muted-foreground">JPG, PNG, WEBP. Maks. 2MB.</p>

                    <div class="flex items-center gap-4" v-if="avatarFile">
                        <Button
                            @click="uploadAvatar"
                            :disabled="avatarProcessing || !avatarFile"
                            class="bg-nova-primary hover:bg-nova-accent dark:bg-nova-light dark:hover:bg-nova-accent"
                        >
                            <Icon v-if="avatarProcessing" name="loader-circle" class="mr-2 h-4 w-4 animate-spin" />
                            {{ avatarProcessing ? 'Przesyłanie...' : 'Zapisz Avatar' }}
                        </Button>
                        <Transition
                            enter-active-class="transition ease-in-out duration-300"
                            enter-from-class="opacity-0"
                            leave-active-class="transition ease-in-out duration-300"
                            leave-to-class="opacity-0"
                        >
                            <p v-show="avatarRecentlySuccessful" class="text-sm text-green-600">Avatar zapisany.</p>
                        </Transition>
                    </div>
                </div>
            </div>

            <hr class="my-8"/>

            <!-- Sekcja Informacji o Profilu -->
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

                    <!-- Sekcja weryfikacji emaila usunięta dla uproszczenia, można ją dodać z powrotem jeśli potrzebna -->

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
            </div>
            <hr class="my-8"/>
            <DeleteUser v-if="!isAdmin"/>
        </SettingsLayout>
    </AppLayout>
</template>
