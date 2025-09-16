<script setup lang="ts">
import Icon from '@/components/Icon.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import { Skeleton } from '@/components/ui/skeleton';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types/index';
import axios from 'axios';
import { useToast } from 'primevue/usetoast';
import { computed, onMounted, ref, watch } from 'vue';
import Toast from 'primevue/toast';
import ConfirmPopup from 'primevue/confirmpopup';
import { useConfirm } from "primevue/useconfirm";

const confirm = useConfirm();
interface ProcedureListItem {
    id: number;
    name: string;
    description?: string;
}

interface DoctorProfileData {
    id?: number;
    first_name?: string;
    last_name?: string;
    email?: string;
    specialization?: string;
    bio?: string | null;
    profile_picture_url?: string | null;
    procedure_ids?: number[];
    user?: { email?: string };
}

const toast = useToast();

const loadingProfile = ref(true);
const savingProfile = ref(false);
const savingAvatar = ref(false);
const deletingAvatar = ref(false);

const doctorProfile = ref<Partial<DoctorProfileData>>({ procedure_ids: [] });
const allProcedures = ref<ProcedureListItem[]>([]);
const loadingProcedures = ref(true);

const profileErrors = ref<Record<string, string[]>>({});

const avatarFile = ref<File | null>(null);
const avatarPreview = ref<string | null>(null);
const avatarInputRef = ref<HTMLInputElement | null>(null);
const avatarErrors = ref<Record<string, string[]>>({});

const selectedProcedures = ref<number[]>([]);
const procedureSelections = ref<Record<number, boolean>>({});

const formBio = computed({
    get: () => doctorProfile.value.bio ?? '',
    set: (val: string) => {
        if (doctorProfile.value) doctorProfile.value.bio = val === '' ? null : val;
    },
});

const formSpecialization = computed({
    get: () => doctorProfile.value.specialization ?? '',
    set: (val: string) => {
        if (doctorProfile.value) doctorProfile.value.specialization = val;
    },
});

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Panel Lekarza',
        href: '/doctor/dashboard',
    },
    { title: 'Ustawienia Profilu' },
];

async function fetchDoctorProfile() {
    loadingProfile.value = true;
    profileErrors.value = {};
    try {
        const response = await axios.get('/api/v1/doctor/profile');
        const profile = response.data.data;
        doctorProfile.value = {
            id: profile.id,
            first_name: profile.first_name,
            last_name: profile.last_name,
            email: profile.user?.email || '',
            specialization: profile.specialization,
            bio: profile.bio || null,
            profile_picture_url: profile.profile_picture_url || null,
            procedure_ids: profile.procedure_ids || [],
        };
        avatarPreview.value = profile.profile_picture_url || null;
    } catch (_: any) {
        toast.add({ severity: 'error', summary: 'Błąd', detail: 'Nie udało się pobrać danych profilu.', life: 3000 });
    } finally {
        loadingProfile.value = false;
    }
}

async function fetchAllProcedures() {
    loadingProcedures.value = true;
    try {
        const response = await axios.get('/api/v1/procedures', { params: { per_page: 999 } });
        allProcedures.value = (response.data.data || []).map((p: any) => ({
            id: p.id,
            name: p.name,
            description: p.description || 'Brak opisu dla tego zabiegu.',
        }));
    } catch (_) {
        toast.add({ severity: 'error', summary: 'Błąd', detail: 'Nie udało się pobrać listy zabiegów.', life: 3000 });
    } finally {
        loadingProcedures.value = false;
    }
}

function handleAvatarInputChange(event: Event) {
    const target = event.target as HTMLInputElement;
    if (target.files && target.files[0]) {
        const file = target.files[0];
        if (file.size > 2 * 1024 * 1024) {
            avatarErrors.value = { avatar: ['Plik jest za duży (maks. 2MB).'] };
            return;
        }
        const allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
        if (!allowedTypes.includes(file.type)) {
            avatarErrors.value = { avatar: ['Niepoprawny format pliku (JPG, PNG, WEBP).'] };
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
        avatarPreview.value = doctorProfile.value.profile_picture_url || null;
    }
}

async function submitAvatar() {
    if (!avatarFile.value) {
        avatarErrors.value = { avatar: ['Wybierz plik.'] };
        return;
    }
    savingAvatar.value = true;
    avatarErrors.value = {};
    const formData = new FormData();
    formData.append('avatar', avatarFile.value);
    try {
        const response = await axios.post('/api/v1/doctor/profile/avatar', formData, {
            headers: { 'Content-Type': 'multipart/form-data' },
        });
        if (doctorProfile.value) {
            doctorProfile.value.profile_picture_url = response.data.data.profile_picture_url;
        }
        avatarPreview.value = response.data.data.profile_picture_url;
        avatarFile.value = null;
        if (avatarInputRef.value) avatarInputRef.value.value = '';
        toast.add({ severity: 'success', summary: 'Sukces', detail: 'Avatar został zaktualizowany.', life: 3000 });
    } catch (_: any) {
        if (_.response?.status === 422) avatarErrors.value = _.response.data.errors;
        else
            toast.add({
                severity: 'error',
                summary: 'Błąd',
                detail: 'Nie udało się zaktualizować avatara.',
                life: 3000,
            });
    } finally {
        savingAvatar.value = false;
    }
}

async function deleteAvatarAction() {
    deletingAvatar.value = true;
    try {
        await axios.post('/api/v1/doctor/profile/avatar', {
            _method: 'DELETE'
        });
        if (doctorProfile.value) doctorProfile.value.profile_picture_url = null;
        avatarPreview.value = null;
        avatarFile.value = null;
        if (avatarInputRef.value) avatarInputRef.value.value = '';
        toast.add({
            severity: 'success',
            summary: 'Sukces',
            detail: 'Zdjęcie profilowe zostało usunięte.',
            life: 3000,
        });
    } catch (error: any) {
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
    if (!doctorProfile.value?.profile_picture_url) {
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

async function saveProfileChanges() {
    savingProfile.value = true;
    profileErrors.value = {};
    try {
        const payload = {
            bio: doctorProfile.value.bio || null,
            specialization: doctorProfile.value.specialization,
            procedure_ids: selectedProcedures.value,
        };
        const response = await axios.put('/api/v1/doctor/profile', payload);
        const updatedProfile = response.data.data;
        doctorProfile.value = {
            ...doctorProfile.value,
            bio: updatedProfile.bio,
            specialization: updatedProfile.specialization,
            procedure_ids: updatedProfile.procedure_ids || [],
            profile_picture_url: updatedProfile.profile_picture_url || doctorProfile.value.profile_picture_url,
        };
        toast.add({ severity: 'success', summary: 'Sukces', detail: 'Profil został zaktualizowany.', life: 3000 });
    } catch (_: any) {
        if (_.response?.status === 422) profileErrors.value = _.response.data.errors;
        else
            toast.add({
                severity: 'error',
                summary: 'Błąd',
                detail: 'Nie udało się zaktualizować profilu.',
                life: 3000,
            });
    } finally {
        savingProfile.value = false;
    }
}

function submitProfileChanges() {
    confirm.require({
        message: 'Czy na pewno chcesz zapisać zmiany w profilu?',
        header: 'Potwierdzenie zapisu',
        icon: 'pi pi-info-circle',
        acceptLabel: 'Tak',
        rejectLabel: 'Nie',
        acceptClass: 'p-button-success',
        accept: () => {
            saveProfileChanges();
        }
    });
}

onMounted(async () => {
    await fetchDoctorProfile();

    if (doctorProfile.value.procedure_ids && doctorProfile.value.procedure_ids.length > 0) {
        selectedProcedures.value = [...doctorProfile.value.procedure_ids];
    }

    await fetchAllProcedures();
    initializeProcedureSelections();
});

function initializeProcedureSelections() {
    allProcedures.value.forEach((proc) => {
        procedureSelections.value[proc.id] = selectedProcedures.value.includes(proc.id);
    });
}

watch(
    () => doctorProfile.value.procedure_ids,
    (newValue) => {
        if (newValue && newValue.length > 0) {
            selectedProcedures.value = [...newValue];
        }
    },
    { deep: true },
);

watch(
    procedureSelections,
    (newSelections) => {
        selectedProcedures.value = Object.entries(newSelections)
            .filter(([_, isSelected]) => isSelected)
            .map(([id]) => parseInt(id, 10));
    },
    { deep: true },
);

watch(allProcedures, () => {
    initializeProcedureSelections();
});
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="container mx-auto px-4 py-6 sm:px-6 sm:py-8 lg:px-8">
            <h1 class="mb-6 text-2xl font-bold text-gray-900 dark:text-gray-100 sm:mb-8 sm:text-3xl">Ustawienia Profilu Lekarza</h1>

            <div v-if="loadingProfile || loadingProcedures" class="space-y-4 sm:space-y-6">
                <Skeleton class="h-40 w-full dark:bg-gray-700 sm:h-48" />
                <Skeleton class="h-48 w-full dark:bg-gray-700 sm:h-64" />
            </div>
            <div v-else-if="!doctorProfile.id && !loadingProfile" class="py-8 text-center text-gray-500 dark:text-gray-400 sm:py-10">
                <p class="text-sm sm:text-base">Nie udało się załadować profilu lekarza lub profil nie istnieje.</p>
                <Button @click="fetchDoctorProfile" variant="link" class="mt-2">Spróbuj ponownie</Button>
            </div>
            <div v-else class="space-y-6 lg:space-y-8">
                <!-- Główny layout - na dużych ekranach grid, na małych stack -->
                <div class="grid grid-cols-1 gap-6 lg:grid-cols-3 lg:gap-8">
                    <!-- Sekcja avatara - na małych ekranach na górze -->
                    <div class="lg:col-span-1">
                        <Card class="border dark:border-gray-700 dark:bg-gray-800">
                            <CardHeader class="pb-4 sm:pb-6">
                                <CardTitle class="text-lg dark:text-gray-200 sm:text-xl">Zdjęcie Profilu Lekarza</CardTitle>
                                <CardDescription class="text-sm dark:text-gray-400 sm:text-base">Twoje zdjęcie widoczne dla pacjentów.</CardDescription>
                            </CardHeader>
                            <CardContent class="flex flex-col items-center gap-4 pb-6">
                                <img
                                    :src="
                                        avatarPreview ||
                                        doctorProfile.profile_picture_url ||
                                        `https://ui-avatars.com/api/?name=${encodeURIComponent(doctorProfile.first_name || 'L')}+${encodeURIComponent(doctorProfile.last_name || 'P')}&background=random&color=fff&size=128`
                                    "
                                    alt="Avatar lekarza"
                                    class="h-28 w-28 rounded-full border-4 border-white object-cover shadow-lg dark:border-gray-700 sm:h-32 sm:w-32 lg:h-36 lg:w-36"
                                />
                                <input
                                    type="file"
                                    ref="avatarInputRef"
                                    @change="handleAvatarInputChange"
                                    accept="image/jpeg,image/png,image/webp"
                                    class="hidden"
                                    id="doctor-avatar-input"
                                />
                                <Button
                                    type="button"
                                    variant="outline"
                                    @click="avatarInputRef?.click()"
                                    class="w-full text-sm dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700 sm:text-base"
                                >
                                    <Icon name="upload-cloud" class="mr-2 h-4 w-4" />
                                    Zmień zdjęcie
                                </Button>
                                <InputError :message="avatarErrors.avatar?.[0]" />
                                <p class="text-center text-xs text-gray-500 dark:text-gray-400 sm:text-sm">Maks. 2MB. JPG, PNG, WEBP.</p>
                                <div class="w-full space-y-2">
                                    <Button
                                        v-if="avatarFile"
                                        type="button"
                                        @click="submitAvatar"
                                        :disabled="savingAvatar"
                                        class="w-full bg-green-600 text-sm text-white hover:bg-green-700 sm:text-base"
                                    >
                                        <Icon v-if="savingAvatar" name="loader-2" class="mr-2 h-4 w-4 animate-spin" />
                                        Zapisz nowy avatar
                                    </Button>
                                    <Button
                                        v-if="doctorProfile.profile_picture_url && !avatarFile"
                                        type="button"
                                        @click="deleteAvatar"
                                        :disabled="deletingAvatar"
                                        variant="destructive"
                                        class="w-full text-sm sm:text-base"
                                    >
                                        <Icon v-if="deletingAvatar" name="loader-2" class="mr-2 h-4 w-4 animate-spin" />
                                        <Icon v-else name="trash-2" class="mr-2 h-4 w-4" />
                                        Usuń zdjęcie
                                    </Button>
                                </div>
                            </CardContent>
                        </Card>
                    </div>

                    <!-- Główna zawartość formularza -->
                    <div class="space-y-6 lg:col-span-2 lg:space-y-8">
                        <!-- Informacje podstawowe -->
                        <Card class="border dark:border-gray-700 dark:bg-gray-800">
                            <CardHeader class="pb-4 sm:pb-6">
                                <CardTitle class="text-lg dark:text-gray-200 sm:text-xl">Informacje Podstawowe</CardTitle>
                            </CardHeader>
                            <CardContent class="space-y-4 sm:space-y-6">
                                <div>
                                    <Label for="docSpec" class="text-sm font-medium dark:text-gray-300 sm:text-base">Specjalizacja</Label>
                                    <Input
                                        id="docSpec"
                                        v-model="formSpecialization"
                                        placeholder="np. Kardiolog"
                                        class="modal-input mt-1 text-sm sm:text-base"
                                    />
                                    <InputError v-if="profileErrors.specialization" :message="profileErrors.specialization[0]" class="mt-1" />
                                </div>
                                <div>
                                    <Label for="docBio" class="text-sm font-medium dark:text-gray-300 sm:text-base">O mnie (Bio)</Label>
                                    <Textarea
                                        id="docBio"
                                        v-model="formBio"
                                        placeholder="Opisz swoje doświadczenie, podejście do pacjenta itp."
                                        rows="4"
                                        class="modal-input mt-1 min-h-[80px] text-sm sm:min-h-[100px] sm:text-base"
                                    />
                                    <InputError v-if="profileErrors.bio" :message="profileErrors.bio[0]" class="mt-1" />
                                </div>
                            </CardContent>
                        </Card>

                        <!-- Wykonywane zabiegi -->
                        <Card class="border dark:border-gray-700 dark:bg-gray-800">
                            <CardHeader class="pb-4 sm:pb-6">
                                <CardTitle class="text-lg dark:text-gray-200 sm:text-xl">Wykonywane Zabiegi</CardTitle>
                                <CardDescription class="text-sm dark:text-gray-400 sm:text-base">Zaznacz zabiegi, które wykonujesz.</CardDescription>
                            </CardHeader>
                            <CardContent class="space-y-4">
                                <div v-if="loadingProcedures && !allProcedures.length" class="p-3 text-sm text-gray-500 dark:text-gray-400">
                                    Ładowanie listy zabiegów...
                                </div>
                                <div v-else-if="allProcedures.length === 0" class="p-3 text-sm text-gray-500 dark:text-gray-400">
                                    Brak zdefiniowanych zabiegów w systemie. Możesz je dodać w panelu administratora.
                                </div>
                                <div v-else class="max-h-64 space-y-1 overflow-y-auto pr-2 sm:max-h-80">
                                    <div
                                        v-for="procedure in allProcedures"
                                        :key="`prof-proc-switch-${procedure.id}`"
                                        class="rounded-md border bg-white p-3 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-700/30 dark:hover:bg-gray-700/50 sm:p-4"
                                    >
                                        <div class="flex items-start space-x-3">
                                            <Checkbox
                                                :id="`checkbox-proc-${procedure.id}`"
                                                v-model="procedureSelections[procedure.id]"
                                                class="mt-0.5 data-[state=checked]:bg-nova-primary dark:data-[state=checked]:bg-nova-accent"
                                            />
                                            <div class="flex-1 min-w-0">
                                                <Label
                                                    :for="`checkbox-proc-${procedure.id}`"
                                                    class="cursor-pointer font-medium text-gray-800 dark:text-gray-100 text-sm sm:text-base"
                                                >
                                                    {{ procedure.name }}
                                                </Label>
                                                <Separator class="my-2 sm:my-3" />
                                                <div class="text-xs text-gray-600 dark:text-gray-400 sm:text-sm">
                                                    {{ procedure.description || 'Brak opisu dla tego zabiegu.' }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <InputError v-if="profileErrors.procedure_ids" :message="profileErrors.procedure_ids[0]" class="mt-2 text-sm" />
                            </CardContent>
                        </Card>
                    </div>
                </div>

                <!-- Przycisk zapisu - responsywny -->
                <div class="flex flex-col items-stretch space-y-3 sm:flex-row sm:items-center sm:justify-end sm:space-x-4 sm:space-y-0">
                    <Button
                        type="submit"
                        :disabled="savingProfile"
                        @click="submitProfileChanges"
                        class="w-full bg-nova-primary text-sm hover:bg-nova-accent dark:bg-nova-light dark:hover:bg-nova-accent sm:w-auto sm:text-base"
                    >
                        <Icon v-if="savingProfile" name="loader-2" class="mr-2 h-4 w-4 animate-spin" />
                        Zapisz zmiany profilu
                    </Button>
                </div>
            </div>
        </div>
        <Toast />
        <ConfirmPopup>
            <template #message="slotProps">
                <div class="flex flex-col items-center w-full gap-3 border-b border-surface-200 dark:border-surface-700 p-3 mb-3 sm:gap-4 sm:p-4 sm:mb-4">
                    <i :class="slotProps.message.icon" class="text-4xl text-primary-500 sm:text-6xl"></i>
                    <p class="text-sm text-center sm:text-base">{{ slotProps.message.message }}</p>
                </div>
            </template>
        </ConfirmPopup>
    </AppLayout>
</template>

<style>
/* Definicje zmiennych CSS dla motywu Nova */
:root {
    --nova-primary: #034078;
    --nova-accent: #3d98b2;
    --nova-light: #5bc0de;
}

/* Responsywne style dla ConfirmPopup */
.p-confirmpopup {
    background: white !important;
    color: #333 !important;
    border-radius: 0.5rem !important;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06) !important;
    padding: 0 !important;
    overflow: hidden !important;
    border: 1px solid rgba(0, 0, 0, 0.1) !important;
    max-width: calc(100vw - 2rem) !important;
    width: 100% !important;
}

@media (min-width: 640px) {
    .p-confirmpopup {
        width: auto !important;
        min-width: 320px !important;
        max-width: 500px !important;
    }
}

.dark .p-confirmpopup {
    background: #1f2937 !important;
    color: #f3f4f6 !important;
    border-color: #374151 !important;
}

.p-confirmpopup-content {
    padding: 0.75rem !important;
}

@media (min-width: 640px) {
    .p-confirmpopup-content {
        padding: 1rem !important;
    }
}

.p-confirmpopup-icon {
    color: var(--nova-primary) !important;
    font-size: 1.25rem !important;
    margin-right: 0.5rem !important;
}

@media (min-width: 640px) {
    .p-confirmpopup-icon {
        font-size: 1.5rem !important;
        margin-right: 0.75rem !important;
    }
}

.dark .p-confirmpopup-icon {
    color: var(--nova-accent) !important;
}

.p-confirmpopup-message {
    font-size: 0.875rem !important;
    line-height: 1.5 !important;
    color: #374151 !important;
}

@media (min-width: 640px) {
    .p-confirmpopup-message {
        font-size: 1rem !important;
    }
}

.dark .p-confirmpopup-message {
    color: #e5e7eb !important;
}

.p-confirmpopup-footer {
    display: flex !important;
    flex-direction: column !important;
    gap: 0.5rem !important;
    padding: 0.75rem !important;
    background: #f9fafb !important;
    border-top: 1px solid #e5e7eb !important;
}

@media (min-width: 640px) {
    .p-confirmpopup-footer {
        flex-direction: row !important;
        justify-content: flex-end !important;
        padding: 0.75rem 1rem !important;
    }
}

.dark .p-confirmpopup-footer {
    background: #111827 !important;
    border-color: #374151 !important;
}

.p-confirmpopup-accept-button {
    background: var(--nova-primary) !important;
    border-color: var(--nova-primary) !important;
    color: white !important;
    padding: 0.5rem 1rem !important;
    border-radius: 0.25rem !important;
    font-weight: 500 !important;
    transition: background-color 0.2s !important;
    width: 100% !important;
    font-size: 0.875rem !important;
}

@media (min-width: 640px) {
    .p-confirmpopup-accept-button {
        width: auto !important;
        font-size: 1rem !important;
    }
}

.p-confirmpopup-accept-button:hover {
    background: var(--nova-accent) !important;
    border-color: var(--nova-accent) !important;
}

.dark .p-confirmpopup-accept-button {
    background: var(--nova-light) !important;
    border-color: var(--nova-light) !important;
}

.dark .p-confirmpopup-accept-button:hover {
    background: var(--nova-accent) !important;
    border-color: var(--nova-accent) !important;
}

.p-confirmpopup-reject-button {
    background: transparent !important;
    border-color: #d1d5db !important;
    color: #6b7280 !important;
    padding: 0.5rem 1rem !important;
    border-radius: 0.25rem !important;
    font-weight: 500 !important;
    transition: background-color 0.2s, border-color 0.2s !important;
    width: 100% !important;
    font-size: 0.875rem !important;
}

@media (min-width: 640px) {
    .p-confirmpopup-reject-button {
        width: auto !important;
        font-size: 1rem !important;
    }
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

/* Dodatkowe style responsywne dla inputów */
@media (max-width: 640px) {
    .modal-input {
        font-size: 16px !important; /* Zapobiega zoom na iOS */
    }
}

/* Responsywne klasy utility */
@media (max-width: 640px) {
    .responsive-grid {
        display: block !important;
    }

    .responsive-card {
        margin-bottom: 1.5rem !important;
    }
}

/* Dodatkowe media queries dla bardzo małych ekranów */
@media (max-width: 480px) {
    .container {
        padding-left: 1rem !important;
        padding-right: 1rem !important;
    }

    .card-padding {
        padding: 1rem !important;
    }

    h1 {
        font-size: 1.5rem !important;
        line-height: 2rem !important;
    }
}
</style>
