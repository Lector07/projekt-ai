<script setup lang="ts">
import Icon from '@/components/Icon.vue';
import {Button} from '@/components/ui/button';
import {
    ContextMenu,
    ContextMenuContent,
    ContextMenuItem,
    ContextMenuSeparator,
    ContextMenuTrigger
} from '@/components/ui/context-menu';
import {Input} from '@/components/ui/input';
import {Label} from '@/components/ui/label';
import {
    Pagination,
    PaginationEllipsis,
    PaginationFirst,
    PaginationLast,
    PaginationNext,
    PaginationPrevious
} from '@/components/ui/pagination';
import {ScrollArea} from '@/components/ui/scroll-area';
import {Skeleton} from '@/components/ui/skeleton';
import {Table, TableBody, TableCell, TableHead, TableHeader, TableRow} from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import {Card, CardContent, CardDescription, CardHeader, CardTitle} from '@/components/ui/card';
import type {BreadcrumbItem} from '@/types';
import axios from 'axios';
import InputNumber from 'primevue/inputnumber';
import Toast from 'primevue/toast';
import {useToast} from 'primevue/usetoast';
import {PaginationList, PaginationListItem} from 'reka-ui';
import {computed, h, onMounted, ref, watch} from 'vue';
import Checkbox from 'primevue/checkbox';

import {Separator} from '@/components/ui/separator';

import ReportGenerator from '@/components/ReportGenerator.vue';
import {PrinterCheck} from "lucide-vue-next";
import {Tooltip, TooltipContent, TooltipTrigger} from "@/components/ui/tooltip";

const InputError = (props: { message?: string }) => {
    return props.message ? h('p', {class: 'text-xs text-red-500 mt-1'}, props.message) : null;
};

interface Doctor {
    id: number;
    first_name: string;
    last_name: string;
    full_name?: string;
    specialization: string;
    bio?: string | null;
    user_id?: number | null;
    user?: {
        id: number;
        name: string;
        email: string;
    } | null;
    created_at: string;
    profile_picture_url?: string;
    procedure_ids?: number[];
}

interface ProcedureListItem {
    id: number;
    name: string;
}

interface DoctorForm {
    id?: number;
    first_name: string;
    last_name: string;
    specialization: string;
    bio?: string | null;
    user_id?: number | null;
    user?: {
        id: number;
        name: number;
        email: string;
    } | null;
    email?: string;
    password?: string;
    password_confirmation?: string;
    procedure_ids?: number[];
}

const query = ref({
    page: 1,
    per_page: 10,
    search: '',
    specialization: '',
});

const doctors = ref<Doctor[]>([]);
const loading = ref(true);
const error = ref<string | null>(null);
const totalPages = ref(0);
const totalItems = ref(0);
const currentPage = computed(() => query.value.page);
const itemsPerPage = computed(() => query.value.per_page);

const showAddDoctorForm = ref(false);
const showEditDoctorForm = ref(false);
const showAvatarUploadModal = ref(false);
const selectedDoctorForAvatar = ref<Doctor | null>(null);
const avatarFile = ref<File | null>(null);
const avatarPreview = ref<string | null>(null);
const avatarUploadLoading = ref(false);
const avatarUploadErrors = ref<Record<string, string[]>>({});

const selectedDoctor = ref<DoctorForm | null>(null);
const doctorFormLoading = ref(false);
const doctorFormErrors = ref<Record<string, string[]>>({});

const allProcedures = ref<ProcedureListItem[]>([]);
const loadingProcedures = ref(false);

const newDoctor = ref<DoctorForm>({
    first_name: '',
    last_name: '',
    specialization: '',
    bio: null,
    user_id: undefined,
    procedure_ids: [],
});

const specializations = ref<string[]>(['Chirurg Plastyczny', 'Medycyna Estetyczna', 'Dermatolog', 'Fleobolog']);

const availableUsers = ref<Array<{ id: number; name: string; email: string }>>([]);

const toast = useToast();

const breadcrumbs: BreadcrumbItem[] = [{title: 'Zarządzanie Lekarzami'}];

async function fetchAllProcedures() {
    loadingProcedures.value = true;
    try {
        const response = await axios.get('/api/v1/admin/procedures', {params: {per_page: 999}});
        allProcedures.value = (response.data.data || []).map((p: any) => ({id: p.id, name: p.name}));
    } catch (error) {
        console.error("Błąd podczas pobierania listy wszystkich procedur:", error);
        showErrorToast('Błąd', 'Nie udało się pobrać listy zabiegów.');
    } finally {
        loadingProcedures.value = false;
    }
}

const loadDoctors = async () => {
    loading.value = true;
    error.value = null;
    try {
        const params = new URLSearchParams();
        params.append('page', query.value.page.toString());
        params.append('per_page', query.value.per_page.toString());

        if (query.value.search) params.append('search', query.value.search);
        if (query.value.specialization) params.append('specialization', query.value.specialization);

        params.append('include', 'user');
        params.append('include', 'user,procedures');

        const response = await axios.get(`/api/v1/admin/doctors?${params.toString()}`);
        doctors.value = (response.data.data || []).map((item: any) => ({
            ...item,
            created_at: item.created_at || null,
            profile_picture_url: item.profile_picture_url || null,
        }));
        totalPages.value = response.data.meta?.last_page || 1;
        totalItems.value = response.data.meta?.total || 0;
    } catch (error) {
        error.value = error.response?.data?.message || 'Wystąpił błąd podczas ładowania danych.';
    } finally {
        loading.value = false;
    }
};

const loadAvailableUsers = async () => {
    try {
        const response = await axios.get('/api/v1/admin/users?role=patient&per_page=1000');
        availableUsers.value = response.data.data || [];
    } catch (err) {
        showErrorToast('Błąd', 'Nie udało się pobrać listy użytkowników');
        availableUsers.value = [];
    }
};

const resetPagination = () => {
    query.value.page = 1;
};
const goToPage = (page: number) => {
    query.value.page = page;
};

const showSuccessToast = (summary: string, detail: string) =>
    toast.add({
        severity: 'success',
        summary,
        detail,
        life: 3000,
    });
const showErrorToast = (summary: string, detail: string) =>
    toast.add({
        severity: 'error',
        summary,
        detail,
        life: 3000,
    });

const openAddForm = () => {
    resetDoctorForm();
    loadAvailableUsers();
    fetchAllProcedures();
    showAddDoctorForm.value = true;
};

const bioValue = computed({
    get: () => selectedDoctor.value?.bio || '',
    set: (val: string) => {
        if (selectedDoctor.value) selectedDoctor.value.bio = val;
    },
});

const newBioValue = computed({
    get: () => newDoctor.value.bio || '',
    set: (val: string) => {
        newDoctor.value.bio = val;
    },
});

const openEditForm = async (doctor: Doctor) => {
    if (allProcedures.value.length === 0) {
        await fetchAllProcedures();
    }

    selectedDoctor.value = {
        ...doctor,
        user_id: doctor.user?.id || undefined,
        procedure_ids: doctor.procedure_ids || []
    };
    loadAvailableUsers();
    showEditDoctorForm.value = true;
};

const resetDoctorForm = () => {
    newDoctor.value = {
        first_name: '',
        last_name: '',
        specialization: '',
        bio: null,
        user_id: undefined,
        procedure_ids: [],
    };
    doctorFormErrors.value = {};
};


const isReportGeneratorOpen = ref(false);
const reportConfig = ref(null);
const reportData = ref([]);
const reportFilters = ref({});

const generateReport = () => {
    // Przekazujemy aktualne filtry do komponentu ReportGenerator
    reportFilters.value = {
        search: query.value.search,
        specialization: query.value.specialization,
    };
    // Otwieramy bezpośrednio komponent ReportGenerator
    isReportGeneratorOpen.value = true;
};


const validateDoctorForm = (doctorData: DoctorForm) => {
    const errors: Record<string, string[]> = {};
    if (!doctorData.first_name) errors.first_name = ['Imię jest wymagane'];
    if (!doctorData.last_name) errors.last_name = ['Nazwisko jest wymagane'];
    if (!doctorData.specialization) errors.specialization = ['Specjalizacja jest wymagana'];

    if (!doctorData.user_id) {
        if (!doctorData.email) {
            errors.email = ['Email jest wymagany'];
        } else {
            const emailRegex = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
            if (!emailRegex.test(doctorData.email)) {
                errors.email = ['Podany adres email jest nieprawidłowy'];
            }
        }

        if (!doctorData.password) errors.password = ['Hasło jest wymagane'];
        if (!doctorData.password_confirmation) errors.password_confirmation = ['Potwierdzenie hasła jest wymagane'];

        if (doctorData.password && doctorData.password_confirmation &&
            doctorData.password !== doctorData.password_confirmation) {
            errors.password = ['Hasło i potwierdzenie hasła muszą być identyczne'];
        }
    }

    doctorFormErrors.value = errors;
    return Object.keys(errors).length === 0;
};

const addDoctor = async () => {
    if (!validateDoctorForm(newDoctor.value)) return;
    doctorFormLoading.value = true;
    try {
        const doctorData = {...newDoctor.value};
        await axios.post('/api/v1/admin/doctors', doctorData);
        showAddDoctorForm.value = false;
        resetDoctorForm();
        loadDoctors();
        showSuccessToast('Sukces', 'Lekarz został dodany pomyślnie.');
    } catch (error: any) {
        if (error.response?.status === 422) {
            doctorFormErrors.value = error.response.data.errors;
        } else {
            showErrorToast('Błąd', error.response?.data?.message || 'Wystąpił błąd podczas dodawania lekarza.');
        }
    } finally {
        doctorFormLoading.value = false;
    }
};

const updateDoctor = async () => {
    if (!selectedDoctor.value || !validateDoctorForm(selectedDoctor.value)) return;
    doctorFormLoading.value = true;
    try {
        const {user, ...dataToUpdate} = selectedDoctor.value;
        await axios.put(`/api/v1/admin/doctors/${selectedDoctor.value.id}`, dataToUpdate);
        showEditDoctorForm.value = false;
        selectedDoctor.value = null;
        loadDoctors();
        showSuccessToast('Sukces', 'Dane lekarza zostały zaktualizowane.');
    } catch (err: any) {
        if (err.response?.status === 422) {
            doctorFormErrors.value = err.response.data.errors;
        } else {
            showErrorToast('Błąd', err.response?.data?.message || 'Wystąpił błąd podczas aktualizacji lekarza.');
        }
    } finally {
        doctorFormLoading.value = false;
    }
};

const deleteDoctor = async (id: number) => {
    if (!confirm('Czy na pewno chcesz usunąć tego lekarza? Tej operacji nie można cofnąć.')) {
        return;
    }
    try {
        await axios.delete(`/api/v1/admin/doctors/${id}`);
        showSuccessToast('Sukces', 'Lekarz został usunięty.');
        loadDoctors();
    } catch (err: any) {
        showErrorToast('Błąd', err.response?.data?.message || 'Wystąpił błąd podczas usuwania lekarza.');
    }
};

const formatDate = (dateString?: string | null) => {
    if (!dateString) return 'Brak daty';
    try {
        const date = new Date(dateString);
        if (isNaN(date.getTime())) return 'Nieprawidłowa data';
        return new Intl.DateTimeFormat('pl-PL', {year: 'numeric', month: '2-digit', day: '2-digit'}).format(date);
    } catch (e) {
        return 'Błąd formatu';
    }
};

const populateFormFromUser = (userId: number) => {
    if (!userId) return;
    const selectedUser = availableUsers.value.find((user) => user.id === userId);
    if (selectedUser) {
        const nameParts = selectedUser.name.split(' ');
        newDoctor.value.first_name = nameParts[0] || '';
        newDoctor.value.last_name = nameParts.slice(1).join(' ') || '';
        (newDoctor.value as any).email = selectedUser.email;
    }
};

const truncateText = (text: string | null | undefined, maxLength: number): string => {
    if (!text) return 'Brak';
    if (text.length <= maxLength) return text;
    return text.substring(0, maxLength) + '...';
};

// --- Logika zmiany avatara ---
const openAvatarModal = (doctor: Doctor) => {
    selectedDoctorForAvatar.value = doctor;
    avatarFile.value = null;
    avatarPreview.value = doctor.profile_picture_url || null;
    avatarUploadErrors.value = {};
    showAvatarUploadModal.value = true;
};

const handleAvatarChange = (event: Event) => {
    const target = event.target as HTMLInputElement;
    if (target.files && target.files[0]) {
        const file = target.files[0];
        avatarFile.value = file;

        const allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
        if (!allowedTypes.includes(file.type)) {
            avatarUploadErrors.value = {avatar: ['Nieprawidłowy format pliku. Dozwolone: JPG, PNG, WEBP.']};
            avatarFile.value = null;
            return;
        }
        if (file.size > 2 * 1024 * 1024) {
            avatarUploadErrors.value = {avatar: ['Plik jest za duży. Maksymalny rozmiar to 2MB.']};
            avatarFile.value = null;
            return;
        }
        avatarUploadErrors.value = {};

        const reader = new FileReader();
        reader.onload = (e) => {
            avatarPreview.value = e.target?.result as string;
        };
        reader.readAsDataURL(file);
    }
};

const uploadAvatar = async () => {
    if (!avatarFile.value || !selectedDoctorForAvatar.value) return;

    avatarUploadLoading.value = true;
    avatarUploadErrors.value = {};
    const formData = new FormData();
    formData.append('avatar', avatarFile.value);

    try {
        const response = await axios.post(`/api/v1/admin/doctors/${selectedDoctorForAvatar.value.id}/avatar`, formData, {
            headers: {'Content-Type': 'multipart/form-data'},
        });
        showAvatarUploadModal.value = false;
        showSuccessToast('Sukces', 'Avatar lekarza został zaktualizowany.');
        const doctorIndex = doctors.value.findIndex((d) => d.id === selectedDoctorForAvatar.value!.id);
        if (doctorIndex !== -1) {
            doctors.value[doctorIndex].profile_picture_url = response.data.data.profile_picture_url;
        }
    } catch (err: any) {
        if (err.response?.status === 422) {
            avatarUploadErrors.value = err.response.data.errors;
        } else {
            showErrorToast('Błąd', err.response?.data?.message || 'Wystąpił błąd podczas przesyłania avatara.');
        }
    } finally {
        avatarUploadLoading.value = false;
    }
};

const deleteAvatar = async (doctorId: number) => {
    if (!confirm('Czy na pewno chcesz usunąć avatar tego lekarza?')) {
        return;
    }
    try {
        await axios.delete(`/api/v1/admin/doctors/${doctorId}/avatar`);
        const doctorIndex = doctors.value.findIndex((d) => d.id === doctorId);
        if (doctorIndex !== -1) {
            doctors.value[doctorIndex].profile_picture_url = '';
        }
        showSuccessToast('Sukces', 'Avatar lekarza został usunięty.');

        if (showAvatarUploadModal.value && selectedDoctorForAvatar.value?.id === doctorId) {
            showAvatarUploadModal.value = false;
        }
    } catch (error: any) {
        showErrorToast('Błąd', error.response?.data?.message || 'Wystąpił błąd podczas usuwania avatara.');
    }
};

const openReportGenerator = () => {
    generateReport();
};

watch(
    () => newDoctor.value.user_id,
    (newValue) => {
        if (newValue) populateFormFromUser(newValue);
    },
);

watch(query, loadDoctors, {deep: true, immediate: false});

onMounted(() => {
    loadDoctors();
    resetDoctorForm();
    fetchAllProcedures();
});
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Toast position="top-right"/>
        <div class="flex flex-col gap-5 p-6">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <h1 class="text-nova-darkest dark:text-nova-light text-2xl font-bold">Zarządzanie Lekarzami</h1>

                <Button
                    variant="default"
                    class="bg-nova-primary hover:bg-nova-accent dark:bg-nova-accent hover:dark:bg-nova-primary dark:text-nova-light flex items-center gap-2"
                    @click="openAddForm"
                >
                    <Icon name="userPlus" size="18"/>
                    <span>Dodaj Nowego Lekarza</span>
                </Button>
            </div>

            <div
                class="grid grid-cols-1 gap-4 rounded-xl border p-4 md:grid-cols-2 lg:grid-cols-3 dark:border-gray-700">
                <div class="space-y-2">
                    <Label for="search">Wyszukiwanie</Label>
                    <div class="relative">
                        <Input
                            id="search"
                            v-model="query.search"
                            placeholder="Szukaj po imieniu, nazwisku..."
                            @keyup.enter="
                                resetPagination();
                                loadDoctors();
                            "
                            class="dark:bg-background pr-10"
                        />
                        <div
                            class="absolute inset-y-0 right-0 flex cursor-pointer items-center pr-3"
                            @click="
                                resetPagination();
                                loadDoctors();
                            "
                        >
                            <Icon name="search" size="16" class="text-gray-400"/>
                        </div>
                    </div>
                </div>
                <div class="space-y-2">
                    <Label for="specFilter">Filtruj po specjalizacji</Label>
                    <select
                        id="specFilter"
                        v-model="query.specialization"
                        class="border-input bg-background w-full rounded-md border px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-800"
                    >
                        <option value="">Wszystkie specjalizacje</option>
                        <option v-for="spec in specializations" :key="spec" :value="spec">{{ spec }}</option>
                    </select>
                </div>
                <div class="space-y-2 mt-4 md:mt-0 flex items-end justify-end">
                    <Tooltip>
                        <TooltipTrigger as-child>
                            <Button @click="generateReport" class="bg-nova-primary hover:bg-nova-accent h-9 w-9 "
                                    aria-label="Generator raportów">
                                <PrinterCheck/>
                            </Button>
                        </TooltipTrigger>
                        <TooltipContent align="start" side="left" hideWhenDetached >Generator raportów</TooltipContent>
                    </Tooltip>
                </div>
            </div>

            <Separator class="my-1"/>
            <div v-if="loading" class="rounded-xl bg-white p-4 shadow-sm dark:bg-gray-800">
                <div v-for="i in 5" :key="i" class="mb-3">
                    <Skeleton class="h-12 w-full"/>
                </div>
            </div>
            <div
                v-else-if="error"
                class="rounded-xl border border-red-100 bg-red-50 p-6 text-red-500 dark:border-red-700 dark:bg-red-900/20 dark:text-red-400"
            >
                {{ error }}
            </div>
            <div v-if="!loading && !error && doctors.length === 0"
                 class="rounded-xl bg-white p-8 text-center text-gray-500 dark:bg-gray-800">
                Nie znaleziono lekarzy.
            </div>

            <div v-else-if="!loading && !error" class="overflow-hidden rounded-xl bg-white shadow-sm dark:bg-gray-900">
                <ScrollArea class="h-[auto] w-full">
                    <Table>
                        <TableHeader class="dark:bg-gray-800">
                            <TableRow class="dark:border-gray-700">
                                <TableHead class="dark:text-gray-200">ID</TableHead>
                                <TableHead class="dark:text-gray-200">Avatar</TableHead>
                                <TableHead class="dark:text-gray-200">Imię i nazwisko</TableHead>
                                <TableHead class="dark:text-gray-200">Email</TableHead>
                                <TableHead class="dark:text-gray-200">Specjalizacja</TableHead>
                                <TableHead class="dark:text-gray-200">Bio</TableHead>
                                <TableHead class="dark:text-gray-200">Data dodania</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow
                                v-for="doctor in doctors"
                                :key="doctor.id"
                                class="border-b hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-700/50"
                            >
                                <ContextMenu>
                                    <ContextMenuTrigger :asChild="true">
                                        <tr class="contents cursor-context-menu">
                                            <TableCell class="dark:text-gray-300">{{ doctor.id }}</TableCell>
                                            <TableCell>
                                                <img
                                                    :src="
                                                        doctor.profile_picture_url ||
                                                        `https://ui-avatars.com/api/?name=${doctor.first_name}+${doctor.last_name}&background=random&color=fff`
                                                    "
                                                    alt="Avatar"
                                                    class="h-10 w-10 cursor-pointer rounded-full object-cover"
                                                    @click="openAvatarModal(doctor)"
                                                    loading="lazy"
                                                />
                                            </TableCell>
                                            <TableCell>
                                                <router-link
                                                    :to="{ name: 'admin-doctor-details', params: { id: doctor.id } }"
                                                    class="text-blue-600 hover:underline dark:text-blue-400 dark:hover:text-blue-300"
                                                >
                                                    {{ doctor.first_name }} {{ doctor.last_name }}
                                                </router-link>
                                            </TableCell>
                                            <TableCell class="dark:text-gray-300">{{
                                                    doctor.user?.email || 'Brak'
                                                }}
                                            </TableCell>
                                            <TableCell class="dark:text-gray-300">{{
                                                    doctor.specialization
                                                }}
                                            </TableCell>
                                            <TableCell class="max-w-[200px] dark:text-gray-300">
                                                <div class="truncate" :title="doctor.bio || ''">
                                                    {{ truncateText(doctor.bio, 40) }}
                                                </div>
                                            </TableCell>
                                            <TableCell class="dark:text-gray-300">
                                                {{ formatDate(doctor.created_at) }}
                                            </TableCell>
                                        </tr>
                                    </ContextMenuTrigger>
                                    <ContextMenuContent>
                                        <ContextMenuItem @click="openEditForm(doctor)">
                                            <Icon name="pencil" size="14" class="mr-2"/>
                                            Edytuj dane
                                        </ContextMenuItem>
                                        <ContextMenuItem @click="openAvatarModal(doctor)">
                                            <Icon name="image" size="14" class="mr-2"/>
                                            Zmień zdjęcie
                                        </ContextMenuItem>
                                        <ContextMenuItem @click="deleteAvatar(doctor.id)" class="text-amber-600">
                                            <Icon name="trash" size="14" class="mr-2"/>
                                            Usuń zdjęcie
                                        </ContextMenuItem>
                                        <ContextMenuSeparator/>
                                        <ContextMenuItem @click="deleteDoctor(doctor.id)" class="text-red-600">
                                            <Icon name="trash" size="14" class="mr-2"/>
                                            Usuń lekarza
                                        </ContextMenuItem>
                                    </ContextMenuContent>
                                </ContextMenu>
                            </TableRow>
                        </TableBody>
                    </Table>
                </ScrollArea>
            </div>
            <div class="flex items-center justify-center border-t border-gray-200 px-4 py-3 dark:border-gray-700">
                <div class="mt-4 flex items-center justify-center">
                    <Pagination
                        v-if="totalPages > 1"
                        :items-per-page="itemsPerPage"
                        :total="totalItems"
                        :sibling-count="1"
                        show-edges
                        :default-page="currentPage"
                        @update:page="goToPage"
                    >
                        <PaginationList v-slot="{ items }" class="flex items-center gap-1">
                            <PaginationFirst @click="goToPage(1)"
                                             class="dark:border-gray-600 dark:bg-gray-700 dark:text-white"/>
                            <PaginationPrevious
                                @click="goToPage(Math.max(1, currentPage - 1))"
                                class="dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                            />
                            <template v-for="(item, index) in items" :key="index">
                                <PaginationListItem v-if="item.type === 'page'" :value="item.value" as-child>
                                    <Button
                                        :variant="currentPage === item.value ? 'default' : 'outline'"
                                        :class="
                                            currentPage === item.value
                                                ? 'bg-nova-primary hover:bg-nova-accent text-white'
                                                : 'dark:border-gray-600 dark:bg-gray-700 dark:text-white'
                                        "
                                        size="sm"
                                    >
                                        {{ item.value }}
                                    </Button>
                                </PaginationListItem>
                                <PaginationEllipsis v-else :index="index" class="dark:text-gray-400"/>
                            </template>
                            <PaginationNext
                                @click="goToPage(Math.min(totalPages, currentPage + 1))"
                                class="dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                            />
                            <PaginationLast @click="goToPage(totalPages)"
                                            class="dark:border-gray-600 dark:bg-gray-700 dark:text-white"/>
                        </PaginationList>
                    </Pagination>
                    <p class="ml-4 text-sm text-gray-500 dark:text-gray-400">Kliknij PPM dla więcej opcji</p>
                </div>
            </div>
        </div>

        <div v-if="showAddDoctorForm" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
            <div
                class="mx-auto max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-xl bg-white p-6 shadow-lg dark:bg-gray-800">
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-lg font-medium dark:text-gray-200">Dodaj Nowego Lekarza</h3>
                    <Button variant="ghost" class="h-8 w-8 p-0 dark:text-gray-200 dark:hover:bg-gray-700"
                            @click="showAddDoctorForm = false">
                        <Icon name="x" size="18"/>
                    </Button>
                </div>
                <div class="space-y-4">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div class="space-y-1">
                            <Label for="new-first_name" class="dark:text-gray-300">Imię</Label>
                            <Input
                                id="new-first_name"
                                v-model="newDoctor.first_name"
                                :class="{ 'border-red-500': doctorFormErrors.first_name }"
                                class="border-nova-primary focus:shadow-nova-accent border dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200"
                                placeholder="Wpisz imię"
                            />
                            <InputError :message="doctorFormErrors.first_name?.[0]"/>
                        </div>
                        <div class="space-y-1">
                            <Label for="new-last_name" class="dark:text-gray-300">Nazwisko</Label>
                            <Input
                                id="new-last_name"
                                v-model="newDoctor.last_name"
                                :class="{ 'border-red-500': doctorFormErrors.last_name }"
                                class="dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200"
                                placeholder="Wpisz nazwisko"
                            />
                            <InputError :message="doctorFormErrors.last_name?.[0]"/>
                        </div>
                    </div>
                    <div class="space-y-1">
                        <Label for="new-specialization" class="dark:text-gray-300">Specjalizacja</Label
                        ><Input
                        id="new-specialization"
                        v-model="newDoctor.specialization"
                        :class="{ 'border-red-500': doctorFormErrors.specialization }"
                        class="dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200"
                        placeholder="Wpisz specjalizację lekarza"
                    />
                        <InputError :message="doctorFormErrors.specialization?.[0]"/>
                    </div>

                    <div class="space-y-1">
                        <Label for="new-bio" class="dark:text-gray-300">Bio (opcjonalnie)</Label>
                        <Input
                            id="new-bio"
                            v-model="newBioValue"
                            :class="{ 'border-red-500': doctorFormErrors.bio }"
                            class="dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200"
                            placeholder="Dodaj opis lekarza"
                        />
                        <InputError :message="doctorFormErrors.bio?.[0]"/>
                    </div>
                    <div class="space-y-1">
                        <Label for="new-user_id" class="dark:text-gray-300">Powiąż z Użytkownikiem (opcjonalnie)</Label>
                        <select
                            id="new-user_id"
                            v-model.number="newDoctor.user_id"
                            class="border-input bg-background w-full rounded-md border px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200"
                        >
                            <option :value="undefined">Nie przypisuj / Stwórz nowego użytkownika</option>
                            <option v-for="user in availableUsers" :key="user.id" :value="user.id">{{ user.name }}
                                ({{ user.email }})
                            </option>
                        </select>
                        <InputError :message="doctorFormErrors.user_id?.[0]"/>
                        <p class="text-muted-foreground mt-1 text-xs dark:text-gray-400">
                            Jeśli nie wybierzesz użytkownika, a podasz poniżej email i hasło, zostanie utworzone nowe
                            konto użytkownika z rolą
                            'doctor'.
                        </p>
                    </div>
                    <div v-if="!newDoctor.user_id" class="rounded-md border p-3 dark:border-gray-600">
                        <p class="mb-2 text-sm font-medium dark:text-gray-300">Dane dla nowego konta
                            użytkownika-lekarza:</p>
                        <div class="mt-2 space-y-1">
                            <Label for="new-doctor-email" class="dark:text-gray-300">Email</Label
                            ><Input
                            id="new-doctor-email"
                            type="email"
                            v-model="(newDoctor as any).email"
                            class="dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200"
                            :class="{ 'border-red-500': doctorFormErrors.email }"
                        />
                            <InputError :message="doctorFormErrors.email?.[0]"/>
                        </div>
                        <div class="mt-2 space-y-1">
                            <Label for="new-doctor-password" class="dark:text-gray-300">Hasło</Label
                            ><Input
                            id="new-doctor-password"
                            type="password"
                            v-model="(newDoctor as any).password"
                            class="dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200"
                            :class="{ 'border-red-500': doctorFormErrors.password }"
                        />
                            <InputError :message="doctorFormErrors.password?.[0]"/>
                        </div>
                        <div class="mt-2 space-y-1">
                            <Label for="new-doctor-password-confirm" class="dark:text-gray-300">Potwierdź hasło</Label
                            ><Input
                            id="new-doctor-password-confirm"
                            type="password"
                            v-model="(newDoctor as any).password_confirmation"
                            class="dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200"
                            :class="{ 'border-red-500': doctorFormErrors.password_confirmation }"
                            placeholder="Wpisz ponownie hasło"
                        />
                            <InputError :message="doctorFormErrors.password_confirmation?.[0]"/>
                        </div>
                    </div>
                    <div class="space-y-1">
                        <Label class="dark:text-gray-300">Wykonywane Zabiegi</Label>
                        <div v-if="loadingProcedures" class="text-sm text-gray-500 dark:text-gray-400">Ładowanie listy
                            zabiegów...
                        </div>
                        <div v-else-if="allProcedures.length === 0" class="text-sm text-gray-500 dark:text-gray-400">
                            Brak zdefiniowanych zabiegów w systemie.
                        </div>
                        <div v-else
                             class="max-h-48 overflow-y-auto space-y-2 rounded-md border p-3 dark:border-gray-600">
                            <div v-for="procedure in allProcedures" :key="procedure.id" class="flex items-center">
                                <div class="flex items-center space-x-2">
                                    <Checkbox
                                        :inputId="`proc-new-${procedure.id}`"
                                        :value="procedure.id"
                                        v-model="newDoctor.procedure_ids"
                                        :binary="false"
                                    />
                                    <Label :for="`proc-new-${procedure.id}`"
                                           class="text-sm font-normal dark:text-gray-300">{{ procedure.name }}</Label>
                                </div>
                            </div>
                        </div>
                        <InputError :message="doctorFormErrors.procedure_ids?.[0]"/>
                    </div>
                    <div class="flex justify-end gap-3 pt-4">
                        <Button
                            type="button"
                            variant="outline"
                            @click="showAddDoctorForm = false"
                            class="dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600"
                        >
                            Anuluj
                        </Button>
                        <Button
                            @click="addDoctor"
                            :disabled="doctorFormLoading"
                            class="bg-nova-primary hover:bg-nova-accent dark:text-nova-light dark:hover:bg-nova-primary dark:bg-nova-accent flex items-center gap-2"
                        >
                            <Icon v-if="doctorFormLoading" name="loader2" class="animate-spin" size="16"/>
                            <span>Zapisz Lekarza</span></Button
                        >
                    </div>
                </div>
            </div>
        </div>

        <div v-if="showEditDoctorForm && selectedDoctor"
             class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
            <div
                class="mx-auto max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-xl bg-white p-6 shadow-lg dark:bg-gray-800">
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-lg font-medium dark:text-gray-200">Edytuj Dane Lekarza</h3>
                    <Button variant="ghost" class="h-8 w-8 p-0 dark:text-gray-200 dark:hover:bg-gray-700"
                            @click="showEditDoctorForm = false">
                        <Icon name="x" size="18"/>
                    </Button>
                </div>
                <div class="space-y-4">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div class="space-y-1">
                            <Label for="edit-first_name" class="dark:text-gray-300">Imię</Label
                            ><Input
                            id="edit-first_name"
                            v-model="selectedDoctor.first_name"
                            class="dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200"
                            :class="{ 'border-red-500': doctorFormErrors.first_name }"
                        />
                            <InputError :message="doctorFormErrors.first_name?.[0]"/>
                        </div>
                        <div class="space-y-1">
                            <Label for="edit-last_name" class="dark:text-gray-300">Nazwisko</Label
                            ><Input
                            id="edit-last_name"
                            v-model="selectedDoctor.last_name"
                            class="dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200"
                            :class="{ 'border-red-500': doctorFormErrors.last_name }"
                        />
                            <InputError :message="doctorFormErrors.last_name?.[0]"/>
                        </div>
                    </div>
                    <div class="space-y-1">
                        <Label for="edit-specialization" class="dark:text-gray-300">Specjalizacja</Label
                        ><Input
                        id="edit-specialization"
                        v-model="selectedDoctor.specialization"
                        class="dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200"
                        :class="{ 'border-red-500': doctorFormErrors.specialization }"
                    />
                        <InputError :message="doctorFormErrors.specialization?.[0]"/>
                    </div>
                    <div class="space-y-1">
                        <Label for="edit-bio" class="dark:text-gray-300">Bio (opcjonalnie)</Label>
                        <Input
                            id="edit-bio"
                            v-model="bioValue"
                            class="dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200"
                            :class="{ 'border-red-500': doctorFormErrors.bio }"
                        />
                        <InputError :message="doctorFormErrors.bio?.[0]"/>
                    </div>
                    <div class="space-y-1">
                        <Label class="dark:text-gray-300">Wykonywane Zabiegi</Label>
                        <div v-if="loadingProcedures" class="text-sm text-gray-500 dark:text-gray-400">Ładowanie listy
                            zabiegów...
                        </div>
                        <div v-else-if="allProcedures.length === 0" class="text-sm text-gray-500 dark:text-gray-400">
                            Brak zdefiniowanych zabiegów w systemie.
                        </div>
                        <div v-else
                             class="max-h-48 overflow-y-auto space-y-2 rounded-md border p-3 dark:border-gray-600">
                            <div v-for="procedure in allProcedures" :key="procedure.id" class="flex items-center">
                                <div class="flex items-center space-x-2">
                                    <Checkbox
                                        :inputId="`proc-edit-${procedure.id}`"
                                        :value="procedure.id"
                                        v-model="selectedDoctor.procedure_ids"
                                        :binary="false"
                                    />
                                    <Label :for="`proc-edit-${procedure.id}`"
                                           class="text-sm font-normal dark:text-gray-300">{{ procedure.name }}</Label>
                                </div>
                            </div>
                        </div>
                        <InputError :message="doctorFormErrors.procedure_ids?.[0]"/>
                    </div>
                    <div class="flex justify-end gap-3 pt-4">
                        <Button
                            type="button"
                            variant="outline"
                            @click="showEditDoctorForm = false"
                            class="dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600"
                        >
                            Anuluj
                        </Button>
                        <Button
                            @click="updateDoctor"
                            :disabled="doctorFormLoading"
                            class="bg-nova-primary hover:bg-nova-accent dark:bg-nova-accent dark:hover:bg-nova-primary dark:text-nova-light flex items-center gap-2"
                        >
                            <Icon v-if="doctorFormLoading" name="loader2" class="animate-spin" size="16"/>
                            <span>Aktualizuj Lekarza</span></Button
                        >
                    </div>
                </div>
            </div>
        </div>

        <div v-if="showAvatarUploadModal && selectedDoctorForAvatar"
             class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
            <Card class="mx-auto w-full max-w-md border dark:border-gray-700 dark:bg-gray-800">
                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                    <div>
                        <CardTitle class="text-lg font-medium dark:text-gray-200">Zdjęcie Lekarza</CardTitle>
                        <CardDescription class="dark:text-gray-400">Zdjęcie profilowe widoczne dla pacjentów
                        </CardDescription>
                    </div>
                    <Button variant="ghost" class="h-8 w-8 p-0 dark:text-gray-200 dark:hover:bg-gray-700"
                            @click="showAvatarUploadModal = false">
                        <Icon name="x" size="18"/>
                    </Button>
                </CardHeader>
                <CardContent class="flex flex-col items-center gap-4">
                    <img
                        :src="
                avatarPreview ||
                `https://ui-avatars.com/api/?name=${selectedDoctorForAvatar.first_name}+${selectedDoctorForAvatar.last_name}&background=random&color=fff&size=128`
            "
                        alt="Podgląd avatara"
                        class="h-36 w-36 rounded-full border-4 border-white object-cover shadow-lg dark:border-gray-700"
                    />
                    <input
                        type="file"
                        ref="avatarInputRef"
                        @change="handleAvatarChange"
                        accept="image/jpeg,image/png,image/webp"
                        class="hidden"
                        id="doctor-avatar-input"
                    />
                    <Button
                        type="button"
                        variant="outline"
                        @click="$refs.avatarInputRef?.click()"
                        class="w-full dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700"
                    >
                        <Icon name="upload-cloud" class="mr-2 h-4 w-4"/>
                        Wybierz nowe zdjęcie
                    </Button>
                    <InputError :message="avatarUploadErrors.avatar?.[0]"/>
                    <p class="text-center text-xs text-gray-500 dark:text-gray-400">Maks. 2MB. Dozwolone formaty: JPG,
                        PNG, WEBP.</p>
                    <div class="w-full space-y-2">
                        <Button
                            v-if="avatarFile"
                            type="button"
                            @click="uploadAvatar"
                            :disabled="avatarUploadLoading"
                            class="w-full bg-green-600 text-white hover:bg-green-700"
                        >
                            <Icon v-if="avatarUploadLoading" name="loader2" class="mr-2 h-4 w-4 animate-spin"/>
                            Zapisz nowy avatar
                        </Button>
                        <Button
                            v-if="selectedDoctorForAvatar.profile_picture_url && !avatarFile"
                            type="button"
                            @click="deleteAvatar(selectedDoctorForAvatar.id)"
                            variant="destructive"
                            class="w-full"
                        >
                            <Icon name="trash-2" class="mr-2 h-4 w-4"/>
                            Usuń zdjęcie
                        </Button>
                    </div>
                    <div class="flex w-full justify-end pt-2">
                        <Button
                            type="button"
                            variant="outline"
                            @click="showAvatarUploadModal = false"
                            class="dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600"
                        >
                            Anuluj
                        </Button>
                    </div>
                </CardContent>
            </Card>
        </div>
        <ReportGenerator
            v-model="isReportGeneratorOpen"
            :activeFilters="reportFilters"
            reportType="doctors"
            :config="reportConfig"
        />

    </AppLayout>
</template>

<style scoped>
table {
    border-collapse: separate;
    border-spacing: 0;
    width: 100%;
}

th:first-child {
    border-top-left-radius: 0.5rem;
}

th:last-child {
    border-top-right-radius: 0.5rem;
}

tr:last-child td:first-child {
    border-bottom-left-radius: 0.5rem;
}

tr:last-child td:last-child {
    border-bottom-right-radius: 0.5rem;
}

:deep(thead th) {
    background-color: #f9fafb;
    color: #374151;
    font-weight: 600;
    text-align: left;
    padding: 0.75rem 1rem;
    font-size: 0.875rem;
}

.dark :deep(thead th) {
    background-color: #1f2937;
    color: #d1d5db;
}

:deep(tbody td) {
    padding: 1rem !important;
    vertical-align: middle;
}

.cursor-context-menu {
    cursor: context-menu;
}

:deep(.context-menu-content) {
    z-index: 50;
}

.custom-toast {
    --p-toast-width: 350px;
    --p-toast-border-radius: 8px;
    --p-toast-transition-duration: 0.3s;
}

:deep(.p-toast) {
    font-family: 'Inter', sans-serif;
}
</style>
