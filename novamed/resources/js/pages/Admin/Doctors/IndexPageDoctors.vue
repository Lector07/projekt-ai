<script setup lang="ts" xmlns="http://www.w3.org/1999/html">
import {ref, onMounted, watch, computed, h} from 'vue';
import {useRouter} from 'vue-router';
import axios from 'axios';
import AppLayout from '@/layouts/AppLayout.vue';
import {Button} from '@/components/ui/button';
import {Input} from '@/components/ui/input';
import {Label} from '@/components/ui/label';
import {Skeleton} from '@/components/ui/skeleton';
import InputNumber from 'primevue/inputnumber';
import Icon from '@/components/Icon.vue';
import type {BreadcrumbItem} from '@/types';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from "@/components/ui/table";
import {
    Pagination,
    PaginationEllipsis,
    PaginationFirst,
    PaginationPrevious,
    PaginationLast,
    PaginationNext,
} from '@/components/ui/pagination';
import {PaginationList, PaginationListItem} from 'reka-ui';
import Toast from 'primevue/toast';
import {useToast} from 'primevue/usetoast';
import {
    ContextMenu,
    ContextMenuContent,
    ContextMenuItem,
    ContextMenuSeparator,
    ContextMenuTrigger,
} from '@/components/ui/context-menu';
import {useAuthStore} from '@/stores/auth';
import {
    ScrollArea,
    ScrollBar
} from "@/components/ui/scroll-area";

import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from "@/components/ui/tooltip";
import {Separator} from "@/components/ui/separator";

// Komponent do wyświetlania błędów formularza
const InputError = (props: { message?: string }) => {
    return props.message ? h('p', {class: 'text-xs text-red-500 mt-1'}, props.message) : null;
};

// Poprawiony interfejs dla danych Lekarza
interface Doctor {
    id: number;
    first_name: string;
    last_name: string;
    full_name?: string;
    specialization: string;
    bio?: string | null;
    price_modifier?: number | null;
    user_id?: number | null;
    user?: {
        id: number;
        name: string;
        email: string;
    } | null;
    created_at: string;
}

interface DoctorForm {
    id?: number;
    first_name: string;
    last_name: string;
    specialization: string;
    bio?: string | null;
    price_modifier?: number | null;
    user_id?: number | null;
    user?: {
        id: number;
        name: string;
        email: string;
    } | null;
}

// Parametry zapytania
const query = ref({
    page: 1,
    per_page: 10,
    search: '',
    specialization: '',
});

// Zmienne reaktywne
const doctors = ref<Doctor[]>([]);
const loading = ref(true);
const error = ref<string | null>(null);
const totalPages = ref(0);
const totalItems = ref(0);
const currentPage = computed(() => query.value.page);
const itemsPerPage = computed(() => query.value.per_page);

// Stan dla formularzy
const showAddDoctorForm = ref(false);
const showEditDoctorForm = ref(false);
const selectedDoctor = ref<DoctorForm | null>(null);
const doctorFormLoading = ref(false);
const doctorFormErrors = ref<Record<string, string[]>>({});

// Formularz nowego lekarza
const newDoctor = ref<DoctorForm>({
    first_name: '',
    last_name: '',
    specialization: '',
    bio: null,
    price_modifier: 1.00,
    user_id: undefined,
});

// Opcje dla selecta specjalizacji
const specializations = ref<string[]>([
    'Chirurg Plastyczny',
    'Medycyna Estetyczna',
    'Dermatolog',
    'Fleobolog',
]);

// Lista użytkowników do wyboru
const availableUsers = ref<Array<{ id: number; name: string; email: string }>>([]);

const router = useRouter();
const toast = useToast();
const authStore = useAuthStore();

const breadcrumbs: BreadcrumbItem[] = [
    {title: 'Zarządzanie Lekarzami'},
];

// Funkcja do ładowania lekarzy
const loadDoctors = async () => {
    loading.value = true;
    error.value = null;
    try {
        const params = new URLSearchParams();
        params.append('page', query.value.page.toString());
        params.append('per_page', query.value.per_page.toString());
        if (query.value.search) params.append('search', query.value.search);
        if (query.value.specialization) params.append('specialization', query.value.specialization);

        // Dodanie parametru include, aby dołączyć relację user
        params.append('include', 'user');

        const response = await axios.get(`/api/v1/admin/doctors?${params.toString()}`);
        console.log('Odpowiedź API:', response.data);

        doctors.value = response.data.data.map((item: any) => {
            console.log('Pojedynczy rekord:', item);
            return {
                ...item,
                created_at: item.created_at || null
            };
        });

        totalPages.value = response.data.meta.last_page;
        totalItems.value = response.data.meta.total;
    } catch (err: any) {
        console.error('Błąd podczas pobierania lekarzy:', err);
        error.value = err.response?.data?.message || 'Wystąpił błąd podczas ładowania danych.';
    } finally {
        loading.value = false;
    }
};

// Funkcja do ładowania użytkowników
const loadAvailableUsers = async () => {
    try {
        const response = await axios.get('/api/v1/admin/users?role=patient');
        availableUsers.value = response.data.data || [];
    } catch (err) {
        showErrorToast('Błąd', 'Nie udało się pobrać listy użytkowników');
        availableUsers.value = []; // Resetujemy na pustą tablicę w przypadku błędu
    }
};


const resetPagination = () => {
    query.value.page = 1;
};
const goToPage = (page: number) => {
    query.value.page = page;
};

// Toast Functions
const showSuccessToast = (summary: string, detail: string) => toast.add({
    severity: 'success',
    summary,
    detail,
    life: 3000
});
const showErrorToast = (summary: string, detail: string) => toast.add({
    severity: 'error',
    summary,
    detail,
    life: 3000
});

const openAddForm = () => {
    resetDoctorForm();
    loadAvailableUsers();
    showAddDoctorForm.value = true;
};

const bioValue = computed({
    get: () => selectedDoctor.value?.bio || '',
    set: (val: string) => {
        if (selectedDoctor.value) selectedDoctor.value.bio = val;
    }
});

const newBioValue = computed({
    get: () => newDoctor.value.bio || '',
    set: (val: string) => {
        newDoctor.value.bio = val;
    }
});

const openEditForm = (doctor: Doctor) => {
    selectedDoctor.value = {...doctor, user_id: doctor.user?.id || undefined};
    loadAvailableUsers();
    showEditDoctorForm.value = true;
};

const resetDoctorForm = () => {
    newDoctor.value = {
        first_name: '',
        last_name: '',
        specialization: '',
        bio: null,
        price_modifier: 1.00,
        user_id: undefined
    };
    doctorFormErrors.value = {};
};

const validateDoctorForm = (doctorData: DoctorForm) => {
    const errors: Record<string, string[]> = {};
    if (!doctorData.first_name) errors.first_name = ['Imię jest wymagane'];
    if (!doctorData.last_name) errors.last_name = ['Nazwisko jest wymagane'];
    if (!doctorData.specialization) errors.specialization = ['Specjalizacja jest wymagana'];
    doctorFormErrors.value = errors;
    return Object.keys(errors).length === 0;
};

const addDoctor = async () => {
    if (!validateDoctorForm(newDoctor.value)) return;
    doctorFormLoading.value = true;
    try {
        const doctorData = { ...newDoctor.value };

        // Zapewnij prawidłowe przesłanie user_id
        if (doctorData.user_id) {
            console.log('Wybrano user_id:', doctorData.user_id);
            const user = availableUsers.value.find(u => u.id === doctorData.user_id);
            if (user) {
                console.log('Znaleziono użytkownika:', user);
                doctorData.user = {
                    id: user.id,
                    name: user.name,
                    email: user.email
                };
            }
        } else {
            console.log('Brak wybranego user_id');
        }

        console.log('Dane wysyłane do API:', doctorData);

        await axios.post('/api/v1/admin/doctors', doctorData);
        showAddDoctorForm.value = false;
        resetDoctorForm();
        loadDoctors();
        showSuccessToast('Sukces', 'Lekarz został dodany pomyślnie.');
    } catch (err: any) {
        console.error('Błąd podczas dodawania lekarza:', err);
        if (err.response?.status === 422) {
            doctorFormErrors.value = err.response.data.errors;
            console.log('Błędy walidacji:', err.response.data.errors);
        } else {
            showErrorToast('Błąd', err.response?.data?.message || 'Wystąpił błąd podczas dodawania lekarza.');
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
        console.error('Błąd podczas usuwania lekarza:', err);
        showErrorToast('Błąd', err.response?.data?.message || 'Wystąpił błąd podczas usuwania lekarza.');
    }
};

const formatDate = (dateString?: string | null) => {
    // Dodaj debugowanie
    console.log('Formatowanie daty:', dateString);

    // Jeśli wartość jest pusta
    if (dateString === undefined || dateString === null || dateString === '') {
        return 'Brak daty';
    }

    try {
        const date = new Date(dateString);

        // Sprawdź czy data jest poprawna
        if (isNaN(date.getTime())) {
            console.warn('Nieprawidłowy format daty:', dateString);
            return 'Nieprawidłowa data';
        }

        // Formatowanie poprawnej daty
        return new Intl.DateTimeFormat('pl-PL', {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit'
        }).format(date);
    } catch (e) {
        console.error('Błąd formatowania daty:', e);
        return 'Błąd formatu';
    }
};

const populateFormFromUser = (userId: number) => {
    if (!userId) {
        return;
    }

    const selectedUser = availableUsers.value.find(user => user.id === userId);
    if (selectedUser) {
        // Rozdziel pełne imię na części (zakładając format "Imię Nazwisko")
        const nameParts = selectedUser.name.split(' ');
        if (nameParts.length >= 2) {
            newDoctor.value.first_name = nameParts[0];
            newDoctor.value.last_name = nameParts.slice(1).join(' ');
        } else {
            newDoctor.value.first_name = selectedUser.name;
            newDoctor.value.last_name = '';
        }

        // Dodanie emaila do formularza
        (newDoctor.value as any).email = selectedUser.email;
    }
};

const truncateText = (text: string, maxLength: number): string => {
    if (!text || text.length <= maxLength) return text;
    return text.substring(0, maxLength) + '...';
};

watch(() => newDoctor.value.user_id, (newValue) => {
    if (newValue) {
        populateFormFromUser(newValue);
    }
}, {immediate: true});

watch(() => query.value.search, resetPagination);
watch(() => query.value.specialization, resetPagination);
watch(query, loadDoctors, {deep: true});

onMounted(() => {
    loadDoctors();
    resetDoctorForm();
});
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Toast position="top-right"/>
        <div class="flex flex-col gap-5 p-6">
            <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4">
                <h1 class="text-2xl font-bold text-nova-darkest dark:text-nova-light">Zarządzanie Lekarzami</h1>
                <Button variant="default"
                        class="flex bg-nova-primary hover:bg-nova-accent dark:bg-nova-accent hover:dark:bg-nova-primary dark:text-nova-light items-center gap-2"
                        @click="openAddForm">
                    <Icon name="userPlus" size="18"/>
                    <span>Dodaj Nowego Lekarza</span>
                </Button>
            </div>

            <div
                class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 bg-white dark:bg-gray-900 p-4 border rounded-xl">
                <div class="space-y-2">
                    <Label for="search">Wyszukiwanie</Label>
                    <div class="relative">
                        <Input
                            id="search"
                            v-model="query.search"
                            placeholder="Szukaj po imieniu, nazwisku..."
                            @keyup.enter="resetPagination(); loadDoctors()"
                            class="dark:bg-background pr-10"
                        />
                        <div class="absolute inset-y-0 right-0 flex items-center pr-3 cursor-pointer"
                             @click="resetPagination(); loadDoctors()">
                            <Icon name="search" size="16" class="text-gray-400"/>
                        </div>
                    </div>
                </div>
                <div class="space-y-2">
                    <Label for="specFilter">Filtruj po specjalizacji</Label>
                    <select id="specFilter" v-model="query.specialization"
                            class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                        <option value="">Wszystkie specjalizacje</option>
                        <option v-for="spec in specializations" :key="spec" :value="spec">{{ spec }}</option>
                    </select>
                </div>
            </div>

            <Separator class="my-1"/>
            <div v-if="loading" class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-4">
                <div v-for="i in 5" :key="i" class="mb-3">
                    <Skeleton class="h-12 w-full"/>
                </div>
            </div>
            <div v-else-if="error" class="p-6 bg-red-50 text-red-500 rounded-xl border border-red-100">{{ error }}</div>
            <div v-if="!loading && !error && doctors.length === 0"
                 class="p-8 text-center text-gray-500 bg-white dark:bg-gray-800 rounded-xl">
                Nie znaleziono lekarzy.
            </div>

            <div v-else-if="!loading && !error"
                 class="bg-white  dark:bg-gray-800 rounded-xl shadow-sm overflow-hidden">
                <ScrollArea class="w-full h-[clamp(250px,calc(100vh-400px),500px)]">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>ID</TableHead>
                                <TableHead>Imię i nazwisko</TableHead>
                                <TableHead>Email</TableHead>
                                <TableHead>Specjalizacja</TableHead>
                                <TableHead>Bio</TableHead>
                                <TableHead>Modyfikator ceny</TableHead>
                                <TableHead>Data dodania</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="doctor in doctors" :key="doctor.id"
                                      class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                <ContextMenu>
                                    <ContextMenuTrigger :asChild="true">
                                        <tr class="contents cursor-context-menu">
                                            <TableCell>{{ doctor.id }}</TableCell>
                                            <TableCell>{{ doctor.first_name }} {{ doctor.last_name }}</TableCell>
                                            <TableCell>{{ doctor.user?.email || 'Brak' }}</TableCell>
                                            <TableCell>{{ doctor.specialization }}</TableCell>
                                            <TableCell class="max-w-[200px]">
                                                <div v-if="doctor.bio" class="truncate">
                                                    {{ truncateText(doctor.bio, 40) }}
                                                </div>
                                                <div v-else class="text-gray-400">Brak</div>
                                            </TableCell>
                                            <TableCell>{{ doctor.price_modifier || '1.00' }}</TableCell>
                                            <TableCell>
                                                {{ doctor.created_at ? formatDate(doctor.created_at) : 'Brak daty' }}
                                                <span v-if="doctor.created_at" class="hidden">{{
                                                        doctor.created_at
                                                    }}</span>
                                            </TableCell>
                                        </tr>
                                    </ContextMenuTrigger>
                                    <ContextMenuContent>
                                        <ContextMenuItem @click="openEditForm(doctor)">
                                            <Icon name="pencil" size="14" class="mr-2"/>
                                            Edytuj
                                        </ContextMenuItem>
                                        <ContextMenuSeparator/>
                                        <ContextMenuItem @click="deleteDoctor(doctor.id)" class="text-red-600">
                                            <Icon name="trash" size="14" class="mr-2"/>
                                            Usuń
                                        </ContextMenuItem>
                                    </ContextMenuContent>
                                </ContextMenu>
                            </TableRow>
                        </TableBody>
                    </Table>
                </ScrollArea>
            </div>
            <div
                class="flex justify-center items-center px-4 py-3  border-t border-gray-200">
                <div class="mt-4 flex justify-center">
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
                            <PaginationFirst @click="goToPage(1)"/>
                            <PaginationPrevious @click="goToPage(Math.max(1, currentPage - 1))"/>
                            <template v-for="(item, index) in items" :key="index">
                                <PaginationListItem v-if="item.type === 'page'" :value="item.value" as-child>
                                    <Button
                                        :variant="currentPage === item.value ? 'default' : 'outline'"
                                        :class="currentPage === item.value ? 'bg-nova-primary hover:bg-nova-accent text-white' : ''"
                                        size="sm"
                                    >
                                        {{ item.value }}
                                    </Button>
                                </PaginationListItem>
                                <PaginationEllipsis v-else :index="index"/>
                            </template>
                            <PaginationNext @click="goToPage(Math.min(totalPages, currentPage + 1))"/>
                            <PaginationLast @click="goToPage(totalPages)"/>
                        </PaginationList>
                    </Pagination>
                    <p class="text-sm justify-end text-gray-400">Kliknij PPM aby usunąć lub edytować</p>
                </div>
            </div>
        </div>
        <!-- Modal Dodawania Lekarza -->
        <div v-if="showAddDoctorForm" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
            <div
                class="bg-white dark:bg-gray-800 rounded-xl p-6 max-w-lg w-full mx-auto shadow-lg overflow-y-auto max-h-[90vh]">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-medium">Dodaj Nowego Lekarza</h3>
                    <Button variant="ghost" class="h-8 w-8 p-0" @click="showAddDoctorForm = false">
                        <Icon name="x" size="18"/>
                    </Button>
                </div>
                <div class="space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="space-y-1"><Label for="new-first_name">Imię</Label>
                            <Input id="new-first_name"
                                   v-model="newDoctor.first_name"
                                   :class="{'border-red-500': doctorFormErrors.first_name}"
                                   class="border border-nova-primary focus:shadow-nova-accent"
                                   placeholder="Wpisz imię"/>
                            <InputError :message="doctorFormErrors.first_name?.[0]"/>
                        </div>
                        <div class="space-y-1"><Label for="new-last_name">Nazwisko</Label>
                            <Input id="new-last_name" v-model="newDoctor.last_name"
                                   :class="{'border-red-500': doctorFormErrors.last_name}"
                                   placeholder="Wpisz nazwisko"/>
                            <InputError :message="doctorFormErrors.last_name?.[0]"/>
                        </div>
                    </div>
                    <div class="space-y-1"><Label for="new-specialization">Specjalizacja</Label><Input
                        id="new-specialization" v-model="newDoctor.specialization"
                        :class="{'border-red-500': doctorFormErrors.specialization}"
                        placeholder="Wpisz specjalizację lekarza"/>
                        <InputError :message="doctorFormErrors.specialization?.[0]"/>
                    </div>
                    <div v-if="newDoctor.user_id" class="space-y-1 mt-2">
                        <Label>Email wybranego użytkownika</Label>
                        <div
                            class="px-3 py-2 border rounded-md bg-gray-50 dark:bg-gray-700 text-gray-600 dark:text-gray-300">
                            {{ availableUsers.find(u => u.id === newDoctor.user_id)?.email || 'Brak emaila' }}
                        </div>
                        <p class="text-xs text-muted-foreground mt-1">Email zostanie automatycznie używany dla konta
                            lekarza.</p>
                    </div>
                    <div class="space-y-1">
                        <Label for="new-bio">Bio (opcjonalnie)</Label>
                        <Input id="new-bio"
                               v-model="newBioValue"
                               :class="{'border-red-500': doctorFormErrors.bio}"
                               placeholder="Dodaj opis lekarza"/>
                        <div v-if="doctorFormErrors.bio" class="text-sm text-red-500">
                            {{ doctorFormErrors.bio[0] }}
                        </div>
                    </div>
                    <div class="space-y-1"><Label for="new-price_modifier">Modyfikator ceny</Label>
                        <InputNumber
                            id="new-price_modifier"
                            v-model="newDoctor.price_modifier"
                            :min="0.5"
                            :max="2.0"
                            :step="0.05"
                            showButtons
                            buttonLayout="horizontal"
                            inputClass="w-full rounded-md border border-input px-3 py-2 text-sm"
                            class="w-full rounded-md border border-input px-3 py-2 text-sm"
                            :class="{'p-invalid': doctorFormErrors.price_modifier}"
                        >
                            <template #incrementbuttonicon>
                                <Icon name="plus" size="14"/>
                            </template>
                            <template #decrementbuttonicon class="rounded-xl">
                                <Icon name="minus" size="14" class="mr-2"/>
                            </template>
                        </InputNumber>
                        <p class="text-xs text-muted-foreground mt-1">Wartość 1.0 oznacza standardową cenę, 1.1 to
                            +10%</p>
                        <InputError :message="doctorFormErrors.price_modifier?.[0]"/>
                    </div>
                    <div class="space-y-1">
                        <Label for="new-user_id">Powiąż z Użytkownikiem (opcjonalnie)</Label>
                        <select id="new-user_id" v-model.number="newDoctor.user_id"
                                class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                            <option :value="undefined">Nie przypisuj / Stwórz nowego użytkownika</option>
                            <option v-for="user in availableUsers" :key="user.id" :value="user.id">{{ user.name }}
                                ({{ user.email }})
                            </option>
                        </select>
                        <InputError :message="doctorFormErrors.user_id?.[0]"/>
                        <p class="text-xs text-muted-foreground mt-1">Jeśli nie wybierzesz użytkownika, a podasz poniżej
                            email i hasło, zostanie utworzone nowe konto użytkownika z rolą 'doctor'.</p>
                    </div>
                    <div v-if="!newDoctor.user_id">
                        <div class="space-y-1 mt-2"><Label for="new-doctor-email">Email dla nowego konta lekarza</Label><Input
                            id="new-doctor-email" type="email" v-model="(newDoctor as any).email"
                            :class="{'border-red-500': doctorFormErrors.email}"/>
                            <InputError :message="doctorFormErrors.email?.[0]"/>
                        </div>
                        <div class="space-y-1 mt-2"><Label for="new-doctor-password">Hasło dla nowego konta
                            lekarza</Label><Input id="new-doctor-password" type="password"
                                                  v-model="(newDoctor as any).password"
                                                  :class="{'border-red-500': doctorFormErrors.password}"/>
                            <InputError :message="doctorFormErrors.password?.[0]"/>
                        </div>
                        <div class="space-y-1 mt-2"><Label for="new-doctor-password-confirm">Potwierdź
                            hasło</Label><Input id="new-doctor-password-confirm" type="password"
                                                v-model="(newDoctor as any).password_confirmation"
                                                :class="{'border-red-500': doctorFormErrors.password_confirmation}"
                                                placeholder="Wpisz ponownie hasło"/>
                            <InputError :message="doctorFormErrors.password_confirmation?.[0]"/>
                        </div>
                    </div>
                    <div class="flex justify-end gap-3 pt-4">
                        <Button type="button" variant="outline" @click="showAddDoctorForm = false">Anuluj</Button>
                        <Button @click="addDoctor" :disabled="doctorFormLoading"
                                class="flex bg-nova-primary hover:bg-nova-accent dark:text-nova-light dark:hover:bg-nova-primary dark:bg-nova-accent items-center gap-2">
                            <Icon v-if="doctorFormLoading" name="loader2" class="animate-spin" size="16"/>
                            <span>Zapisz Lekarza</span></Button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Edycji Lekarza -->
        <div v-if="showEditDoctorForm && selectedDoctor"
             class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
            <div
                class="bg-white dark:bg-gray-800 rounded-xl p-6 max-w-lg w-full mx-auto shadow-lg overflow-y-auto max-h-[90vh]">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-medium">Edytuj Dane Lekarza</h3>
                    <Button variant="ghost" class="h-8 w-8 p-0" @click="showEditDoctorForm = false">
                        <Icon name="x" size="18"/>
                    </Button>
                </div>
                <div class="space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="space-y-1"><Label for="edit-first_name">Imię</Label><Input id="edit-first_name"
                                                                                               v-model="selectedDoctor.first_name"
                                                                                               :class="{'border-red-500': doctorFormErrors.first_name}"/>
                            <InputError :message="doctorFormErrors.first_name?.[0]"/>
                        </div>
                        <div class="space-y-1"><Label for="edit-last_name">Nazwisko</Label><Input id="edit-last_name"
                                                                                                  v-model="selectedDoctor.last_name"
                                                                                                  :class="{'border-red-500': doctorFormErrors.last_name}"/>
                            <InputError :message="doctorFormErrors.last_name?.[0]"/>
                        </div>
                    </div>
                    <div class="space-y-1"><Label for="edit-specialization">Specjalizacja</Label><Input
                        id="edit-specialization" v-model="selectedDoctor.specialization"
                        :class="{'border-red-500': doctorFormErrors.specialization}"/>
                        <InputError :message="doctorFormErrors.specialization?.[0]"/>
                    </div>
                    <div class="space-y-1">
                        <Label for="edit-bio">Bio (opcjonalnie)</Label>
                        <Input id="edit-bio"
                               v-model="bioValue"
                               :class="{'border-red-500': doctorFormErrors.bio}"/>
                        <div v-if="doctorFormErrors.bio" class="text-sm text-red-500">
                            {{ doctorFormErrors.bio[0] }}
                        </div>
                    </div>
                    <div class="space-y-1">
                        <Label for="edit-price_modifier">Modyfikator ceny</Label>
                        <InputNumber
                            id="edit-price_modifier"
                            v-model="selectedDoctor.price_modifier"
                            :min="0.5"
                            :max="2.0"
                            :step="0.05"
                            showButtons
                            buttonLayout="horizontal"
                            inputClass="w-full rounded-md border border-input px-3 py-2 text-sm"
                            class="w-full rounded-md border border-input px-3 py-2 text-sm"
                            :class="{'p-invalid': doctorFormErrors.price_modifier}"
                        >
                            <template #incrementbuttonicon>
                                <Icon name="plus" size="14"/>
                            </template>
                            <template #decrementbuttonicon class="rounded-xl">
                                <Icon name="minus" size="14" class="mr-2"/>
                            </template>
                        </InputNumber>
                        <p class="text-xs text-muted-foreground mt-1">Wartość 1.0 oznacza standardową cenę, 1.1 to
                            +10%</p>
                        <InputError :message="doctorFormErrors.price_modifier?.[0]"/>
                    </div>
                    <!-- Pole user_id jest zazwyczaj nieedytowalne po utworzeniu -->
                    <div class="flex justify-end gap-3 pt-4">
                        <Button type="button" variant="outline" @click="showEditDoctorForm = false">Anuluj</Button>
                        <Button @click="updateDoctor" :disabled="doctorFormLoading"
                                class="flex bg-nova-primary hover:bg-nova-accent dark:bg-nova-accent dark:hover:bg-nova-primary dark:text-nova-light items-center gap-2">
                            <Icon v-if="doctorFormLoading" name="loader2" class="animate-spin" size="16"/>
                            <span>Aktualizuj Lekarza</span></Button>
                    </div>
                </div>
            </div>
        </div>
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
    background-color: #f9fafb; /* Zastępuje var(--table-header-bg) */
    color: #374151; /* Zastępuje var(--table-header-color) */
    font-weight: 600;
    text-align: left;
    padding: 0.75rem 1rem;
    font-size: 0.875rem;
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

/* Niestandardowe style dla toastów */
.custom-toast {
    --p-toast-width: 350px;
    --p-toast-border-radius: 8px;
    --p-toast-transition-duration: 0.3s;
}

:deep(.p-toast) {
    font-family: 'Inter', sans-serif;
}


</style>
