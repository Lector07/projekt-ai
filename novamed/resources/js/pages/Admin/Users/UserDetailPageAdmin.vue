<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type User as UserType } from '@/types'; // Zmień nazwę User na UserType, aby uniknąć konfliktu
import { ref, computed, onMounted, watch } from 'vue';
import axios from 'axios';
import { useRoute, useRouter } from 'vue-router';
import { Separator } from '@/components/ui/separator';
import { Button } from '@/components/ui/button';
import { Skeleton } from '@/components/ui/skeleton';
import Card from 'primevue/card';
import Icon from '@/components/Icon.vue'; // Zaimportuj komponent ikon
import { useToast } from 'primevue/usetoast';
import InputError from '@/components/InputError.vue'; // Jeśli potrzebujesz dla błędów avatara

const route = useRoute();
const router = useRouter();
const toast = useToast();

// Zmień nazwę interfejsu, aby uniknąć konfliktu z importowanym typem User z @/types
interface PatientData extends UserType {
    // Możesz tu dodać specyficzne pola, jeśli UserType jest zbyt ogólny
    // profile_picture_url jest już w UserType (jako avatar lub profile_picture_url)
}

const patient = ref<PatientData | null>(null);
const loading = ref(true);
const error = ref<string | null>(null);

// --- Logika dla Avatara Pacjenta ---
const avatarFile = ref<File | null>(null);
const avatarPreview = ref<string | null>(null);
const avatarInput = ref<HTMLInputElement | null>(null);
const avatarErrors = ref<Record<string, string[]>>({});
const avatarProcessing = ref(false);
const avatarRecentlySuccessful = ref(false);
let avatarSuccessTimeout: number | null = null;

const fetchPatient = async () => {
    try {
        loading.value = true;
        error.value = null;
        const patientId = route.params.id;
        const response = await axios.get(`/api/v1/admin/users/${patientId}`);
        patient.value = response.data.data;
        // Ustawienie początkowego podglądu avatara
        avatarPreview.value = patient.value?.avatar || patient.value?.profile_picture_url || null; // Użyj spójnej nazwy
    } catch (err) {
        console.error('Błąd podczas pobierania danych pacjenta:', err);
        error.value = 'Nie udało się pobrać danych pacjenta';
    } finally {
        loading.value = false;
    }
};

// Obserwuj zmiany w pacjencie, aby zaktualizować podgląd, jeśli dane są ładowane asynchronicznie
watch(patient, (newPatient) => {
    if (newPatient) {
        avatarPreview.value = newPatient.avatar || newPatient.profile_picture_url || null; // Użyj spójnej nazwy
    }
}, { deep: true });


const formatCreatedAt = (dateString?: string): string => { /* ... bez zmian ... */ };
const translateRole = (role?: string): string => { /* ... bez zmian ... */ };

const getInitials = (name: string | undefined) => {
    if (!name) return '?';
    const parts = name.split(' ');
    if (parts.length > 1 && parts[0] && parts[parts.length - 1]) {
        return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
    } else if (parts.length === 1 && parts[0]) {
        return parts[0].substring(0, 2).toUpperCase();
    }
    return name.substring(0,1).toUpperCase() || '?';
};

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
        avatarPreview.value = patient.value?.avatar || patient.value?.profile_picture_url || null; // Użyj spójnej nazwy
    }
}

async function uploadAvatar() {
    if (!avatarFile.value || !patient.value) {
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
        const response = await axios.post(`/api/v1/admin/users/${patient.value.id}/avatar`, formData, {
            headers: { 'Content-Type': 'multipart/form-data' },
        });

        // Zaktualizuj dane pacjenta w ref, w tym nowy URL avatara
        if (patient.value && response.data.data) {
            // Użyj spójnej nazwy pola dla avatara
            const newAvatarUrl = response.data.data.avatar || response.data.data.profile_picture_url;
            patient.value = { ...patient.value, avatar: newAvatarUrl, profile_picture_url: newAvatarUrl };
            avatarPreview.value = newAvatarUrl; // Bezpośrednia aktualizacja podglądu
        }

        toast.add({ severity: 'success', summary: 'Sukces', detail: 'Avatar pacjenta został zaktualizowany.', life: 3000 });
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
            toast.add({ severity: 'error', summary: 'Błąd', detail: error.response?.data?.message || 'Wystąpił błąd podczas przesyłania avatara.', life: 5000 });
            console.error('Błąd podczas przesyłania avatara pacjenta:', error);
            avatarErrors.value = { general: ['Wystąpił błąd podczas przesyłania avatara.'] };
        }
    } finally {
        avatarProcessing.value = false;
    }
}


onMounted(() => {
    fetchPatient();
});

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    {
        title: 'Zarządzanie Użytkownikami', // Lub inna odpowiednia nazwa, np. 'Pacjenci'
        href: '/admin/users',
    },
    {
        title: patient.value ? patient.value.name : 'Szczegóły pacjenta',
    },
]);
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Toast />
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <div class="mb-4 flex items-center ml-2">
                <h1 class="text-3xl font-bold">Profil Pacjenta</h1>
            </div>

            <div v-if="loading">
                <Skeleton class="h-[70vh] rounded-md border border-sidebar-border/70 dark:border-sidebar-border" />
            </div>

            <div v-else-if="error" class="p-4 text-center text-red-500">
                {{ error }}
            </div>

            <div v-else-if="patient" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Lewa kolumna ze zdjęciem i opcją zmiany -->
                <div class="lg:col-span-1">
                    <Card class="border border-gray-200 dark:border-gray-700 shadow-md p-4">
                        <template #content>
                            <div class="flex flex-col items-center gap-4">
                                <div class="relative">
                                    <img
                                        v-if="avatarPreview"
                                        :src="avatarPreview"
                                        :alt="patient.name"
                                        class="w-40 h-40 rounded-full object-cover border-2 border-muted"
                                    />
                                    <div v-else class="w-40 h-40 rounded-full bg-muted flex items-center justify-center text-5xl font-semibold text-foreground/70 border-2 border-muted">
                                        {{ getInitials(patient.name) }}
                                    </div>
                                    <Button
                                        variant="outline"
                                        size="icon"
                                        class="absolute bottom-2 right-2 rounded-full bg-background hover:bg-muted p-1.5"
                                        @click="avatarInput?.click()"
                                        title="Zmień avatar pacjenta"
                                    >
                                        <Icon name="camera" class="h-5 w-5" />
                                    </Button>
                                </div>

                                <input
                                    id="patient-avatar-upload"
                                    ref="avatarInput"
                                    type="file"
                                    class="hidden"
                                    accept="image/jpeg,image/png,image/webp"
                                    @change="handleAvatarChange"
                                />
                                <InputError :message="avatarErrors.avatar ? avatarErrors.avatar[0] : ''" />
                                <p class="text-xs text-muted-foreground text-center">JPG, PNG, WEBP. Maks. 2MB.</p>

                                <div v-if="avatarErrors.general" class="text-sm text-red-600 dark:text-red-500 w-full text-center">
                                    {{ avatarErrors.general[0] }}
                                </div>

                                <div class="flex items-center gap-4 mt-2" v-if="avatarFile">
                                    <Button
                                        @click="uploadAvatar"
                                        :disabled="avatarProcessing || !avatarFile"
                                        class="bg-nova-primary hover:bg-nova-accent dark:bg-nova-accent dark:hover:bg-nova-primary"
                                    >
                                        <Icon v-if="avatarProcessing" name="loader-circle" class="mr-2 h-4 w-4 animate-spin" />
                                        {{ avatarProcessing ? 'Przesyłanie...' : 'Zapisz Avatar' }}
                                    </Button>
                                </div>
                                <p v-show="avatarRecentlySuccessful" class="text-sm text-green-600 mt-2">Avatar zapisany.</p>
                            </div>
                        </template>
                    </Card>
                </div>

                <!-- Prawa kolumna z danymi -->
                <div class="lg:col-span-2">
                    <Card class="border border-gray-200 dark:border-gray-700 shadow-md" style="--p-card-border-radius: 0.75rem;">
                        <template #title>
                            <h2 class="text-2xl font-bold ml-3 p-4">{{ patient.name }}</h2>
                        </template>
                        <template #subtitle>
                            <p v-if="patient.email" class="text-xl dark:text-gray-300 ml-7">
                                {{ patient.email }}
                            </p>
                        </template>
                        <template #content>
                            <Separator class="my-4" />
                            <div class="space-y-4 p-2">
                                <h3 class="text-lg font-semibold ml-7">Dane pacjenta</h3>
                                <div class="ml-7 space-y-2">
                                    <p v-if="patient.email" class="dark:text-gray-300">
                                        <span class="font-medium">Email:</span> {{ patient.email }}
                                    </p>
                                    <p v-if="patient?.role" class="dark:text-gray-300">
                                        <span class="font-medium">Rola:</span> {{ translateRole(patient.role) }}
                                    </p>
                                    <p v-if="patient?.created_at" class="dark:text-gray-300">
                                        <span class="font-medium">Data rejestracji:</span> {{ formatCreatedAt(patient.created_at) }}
                                    </p>
                                </div>
                            </div>
                            <div class="flex justify-end gap-4 mt-6 p-6">
                                <Button
                                    variant="outline"
                                    class="bg-nova-primary hover:bg-nova-accent dark:bg-nova-accent hover:dark:bg-nova-dark text-nova-light"
                                    @click="router.push('/admin/users')"
                                >
                                    Powrót do listy
                                </Button>
                            </div>
                        </template>
                    </Card>
                </div>
            </div>
            <div v-else class="p-4 text-center text-gray-500">
                Nie znaleziono danych pacjenta.
            </div>
        </div>
    </AppLayout>
</template>
