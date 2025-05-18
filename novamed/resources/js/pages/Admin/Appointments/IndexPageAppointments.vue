<script setup lang="ts">
import {ref, onMounted, watch, computed, reactive} from 'vue';
import {useRouter} from 'vue-router';
import axios from 'axios';
import AppLayout from '@/layouts/AppLayout.vue';
import {Button} from '@/components/ui/button';
import {Input} from '@/components/ui/input';
import {Label} from '@/components/ui/label';
import {Skeleton} from '@/components/ui/skeleton';
import Icon from '@/components/Icon.vue';
import type {BreadcrumbItem} from '@/types';
import {
    Table,
    TableBody,
    TableCaption,
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
    PaginationNext
} from '@/components/ui/pagination';
import {PaginationList, PaginationListItem} from 'reka-ui';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from "@/components/ui/popover";
import Toast from 'primevue/toast';
import {useToast} from 'primevue/usetoast';


import {
    ScrollArea,
} from "@/components/ui/scroll-area";
import {Calendar} from '@/components/ui/calendar';
import {format} from 'date-fns';
import {pl} from 'date-fns/locale';
import {TooltipContent} from "@/components/ui/tooltip";
import {Tooltip, TooltipTrigger} from "@/components/ui/tooltip";

// Definicje interfejsów
interface Patient {
    id: number;
    name: string;
}

interface Doctor {
    id: number;
    name: string;
}

interface Procedure {
    id: number;
    name: string;
}

interface Appointment {
    id: number;
    patient: Patient;
    doctor: Doctor;
    procedure: Procedure;
    appointment_datetime: string;
    status: string;
}

interface AppointmentFilters {
    doctor_name: string;
    patient_name: string;
    status: string;
    date_from: string;
    date_to: string;

    [key: string]: string; // Sygnatura indeksu dla dynamicznego dostępu
}

const toast = useToast();

// Dane wizyt z poprawnym typowaniem
const appointments = ref<Appointment[]>([]);
const loading = ref(true);
const meta = ref({
    current_page: 1,
    from: 1,
    last_page: 1,
    per_page: 8,
    to: 8,
    total: 0,
});

// Filtry z sygnaturą indeksu
const filters = reactive<AppointmentFilters>({
    doctor_name: '',
    patient_name: '',
    status: '',
    date_from: '',
    date_to: '',
});

// Lista statusów
const statuses = [
    {value: '', label: 'Wszystkie statusy'},
    {value: 'scheduled', label: 'Zarezerwowana'},
    {value: 'completed', label: 'Zakończona'},
    {value: 'cancelled', label: 'Anulowana'},
    {value: 'no_show', label: 'Nieobecność'},
];

const showEditAppointmentForm = ref(false);
const selectedAppointment = ref<any>(null);
const appointmentErrors = ref<any>({});
const appointmentFormLoading = ref(false);
const selectedDate = ref<Date | null>(null);

// Lista dostępnych statusów dla formularza edycji
const statusOptions = [
    {value: 'scheduled', label: 'Zarezerwowana'},
    {value: 'completed', label: 'Zakończona'},
    {value: 'cancelled', label: 'Anulowana'},
    {value: 'no_show', label: 'Nieobecność'},
];

// Obsługa paginacji
const changePage = (page: number) => {
    meta.value.current_page = page;
    loadAppointments();
};

const dateFrom = ref<Date | null>(null);
const dateTo = ref<Date | null>(null);

// Funkcje obsługi zmiany daty
const onDateFromChange = (date: Date | null) => {
    if (date) {
        filters.date_from = format(date, 'yyyy-MM-dd');
    } else {
        filters.date_from = '';
    }
};

const onDateToChange = (date: Date | null) => {
    if (date) {
        filters.date_to = format(date, 'yyyy-MM-dd');
    } else {
        filters.date_to = '';
    }
};

const formatDisplayDate = (dateString: string) => {
    if (!dateString) return '';
    const date = new Date(dateString);
    return format(date, 'd MMMM yyyy', {locale: pl});
};

// Ładowanie listy wizyt
const loadAppointments = async () => {
    loading.value = true;
    try {
        const params = {
            page: meta.value.current_page,
            ...Object.fromEntries(
                Object.entries(filters).filter(([_, value]) => value !== '')
            ),
        };

        const response = await axios.get('/api/v1/admin/appointments', {params});
        appointments.value = response.data.data;
        meta.value = response.data.meta;
    } catch (error) {
        console.error('Błąd podczas pobierania wizyt:', error);
        showErrorToast('Błąd', 'Nie udało się załadować listy wizyt.');
    } finally {
        loading.value = false;
    }
};

// Resetowanie filtrów
const resetFilters = () => {
    Object.keys(filters).forEach((key) => {
        filters[key] = '';
    });
    meta.value.current_page = 1;
    loadAppointments();
};

// Usuwanie wizyty
const deleteAppointment = async (id: number) => {
    if (!confirm('Czy na pewno chcesz usunąć tę wizytę?')) return;

    try {
        await axios.delete(`/api/v1/admin/appointments/${id}`);
        loadAppointments();
        showSuccessToast('Sukces', 'Wizyta została usunięta.');
    } catch (error) {
        console.error('Błąd podczas usuwania wizyty:', error);
        showErrorToast('Błąd', 'Nie udało się usunąć wizyty.');
    }
};

const openEditForm = async (appointment: Appointment) => {
    appointmentErrors.value = {};
    appointmentFormLoading.value = true;

    try {
        const response = await axios.get(`/api/v1/admin/appointments/${appointment.id}`);
        selectedAppointment.value = response.data.data;

        // Ustawienie daty dla pola kalendarza
        if (selectedAppointment.value.appointment_datetime) {
            selectedDate.value = new Date(selectedAppointment.value.appointment_datetime);
        }

        showEditAppointmentForm.value = true;
    } catch (error) {
        console.error('Błąd podczas pobierania danych wizyty:', error);
        showErrorToast('Błąd', 'Nie udało się pobrać szczegółów wizyty');
    } finally {
        appointmentFormLoading.value = false;
    }
};

// Zamknięcie formularza
const closeEditForm = () => {
    showEditAppointmentForm.value = false;
    selectedAppointment.value = null;
    appointmentErrors.value = {};
};

// Aktualizacja daty wizyty
const onAppointmentDateChange = (date: Date | null) => {
    selectedDate.value = date;
    if (date && selectedAppointment.value) {
        // Zachowaj godzinę z oryginalnej daty
        const originalDate = new Date(selectedAppointment.value.appointment_datetime);
        date.setHours(originalDate.getHours(), originalDate.getMinutes());
        selectedAppointment.value.appointment_datetime = date.toISOString();
    }
};

// Aktualizacja wizyty
const updateAppointment = async () => {
    appointmentFormLoading.value = true;
    appointmentErrors.value = {};

    try {
        await axios.put(`/api/v1/admin/appointments/${selectedAppointment.value.id}`, {
            status: selectedAppointment.value.status,
            appointment_datetime: selectedAppointment.value.appointment_datetime,
            patient_notes: selectedAppointment.value.patient_notes,
            doctor_notes: selectedAppointment.value.doctor_notes
        });

        closeEditForm();
        loadAppointments();
        showSuccessToast('Sukces', 'Wizyta została zaktualizowana');
    } catch (error: any) {
        console.error('Błąd podczas aktualizacji wizyty:', error);

        if (error.response && error.response.data && error.response.data.errors) {
            appointmentErrors.value = error.response.data.errors;
        }

        showErrorToast('Błąd', 'Nie udało się zaktualizować wizyty');
    } finally {
        appointmentFormLoading.value = false;
    }
};

// Toasty
const showSuccessToast = (title: string, content: string) => {
    toast.add({
        severity: 'success',
        summary: title,
        detail: content,
        life: 3000,
    });
};

const showErrorToast = (title: string, content: string) => {
    toast.add({
        severity: 'error',
        summary: title,
        detail: content,
        life: 3000,
    });
};

// Formatowanie daty
const formatDateTime = (dateTimeStr: string) => {
    const date = new Date(dateTimeStr);
    return date.toLocaleString('pl-PL', {
        year: 'numeric',
        month: 'numeric',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

// Tłumaczenie statusów
const getStatusLabel = (status: string) => {
    const statusObj = statuses.find(s => s.value === status);
    return statusObj ? statusObj.label : status;
};

// Klasa CSS dla statusu
const getStatusClass = (status: string) => {
    switch (status) {
        case 'scheduled':
            return 'bg-blue-100 text-blue-800';
        case 'completed':
            return 'bg-purple-100 text-purple-800';
        case 'cancelled':
            return 'bg-red-100 text-red-800';
        default:
            return 'bg-gray-100 text-gray-800';
    }
};

onMounted(() => {
    loadAppointments();
});

const router = useRouter();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Zarządzanie Wizytami',
    }
];
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="container mx-auto px-2 py-4">
            <h1 class="text-2xl font-bold mb-1 dark:text-white">Zarządzanie Wizytami</h1>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm mb-4 p-4 border dark:border-gray-700">
                <h2 class="text-lg font-semibold mb-2 dark:text-white">Filtry</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <Label for="doctor-filter" class="mb-1 dark:text-gray-200">Lekarz</Label>
                        <Input
                            id="doctor-filter"
                            v-model="filters.doctor_name"
                            type="text"
                            placeholder="Imię lub nazwisko lekarza"
                            class="w-full dark:bg-gray-700 dark:text-white dark:border-gray-600"
                        />
                    </div>
                    <div>
                        <Label for="patient-filter" class="mb-1 dark:text-gray-200">Pacjent</Label>
                        <Input
                            id="patient-filter"
                            v-model="filters.patient_name"
                            type="text"
                            placeholder="Imię lub nazwisko pacjenta"
                            class="w-full dark:bg-gray-700 dark:text-white dark:border-gray-600"
                        />
                    </div>
                    <div>
                        <Label for="status-filter" class="mb-1 dark:text-gray-200">Status</Label>
                        <select
                            id="status-filter"
                            v-model="filters.status"
                            class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm dark:bg-gray-700 dark:text-white dark:border-gray-600"
                        >
                            <option v-for="status in statuses" :key="status.value" :value="status.value">
                                {{ status.label }}
                            </option>
                        </select>
                    </div>
                    <div>
                        <Label for="date-from" class="mb-1 dark:text-gray-200">Data od</Label>
                        <Popover>
                            <PopoverTrigger as-child>
                                <Button
                                    variant="outline"
                                    class="w-full justify-start text-left font-normal dark:bg-gray-700 dark:text-white dark:border-gray-600"
                                    :class="!filters.date_from && 'text-muted-foreground dark:text-gray-400'"
                                >
                                    <Icon name="calendar" size="16" class="mr-2"/>
                                    {{
                                        filters.date_from ? formatDisplayDate(filters.date_from) : "Wybierz datę początkową"
                                    }}
                                </Button>
                            </PopoverTrigger>
                            <PopoverContent class="w-auto p-0 dark:bg-gray-800 dark:border-gray-700">
                                <Calendar v-model="dateFrom" initial-focus mode="single"
                                          @update:model-value="onDateFromChange"
                                          class="dark:bg-gray-800 dark:text-white"/>
                            </PopoverContent>
                        </Popover>
                    </div>
                    <div>
                        <Label for="date-to" class="mb-1 dark:text-gray-200">Data do</Label>
                        <Popover>
                            <PopoverTrigger as-child>
                                <Button
                                    variant="outline"
                                    class="w-full justify-start text-left font-normal dark:bg-gray-700 dark:text-white dark:border-gray-600"
                                    :class="!filters.date_to && 'text-muted-foreground dark:text-gray-400'"
                                >
                                    <Icon name="calendar" size="16" class="mr-2"/>
                                    {{ filters.date_to ? formatDisplayDate(filters.date_to) : "Wybierz datę końcową" }}
                                </Button>
                            </PopoverTrigger>
                            <PopoverContent class="w-auto p-0 dark:bg-gray-800 dark:border-gray-700">
                                <Calendar v-model="dateTo" initial-focus @update:model-value="onDateToChange"
                                          class="dark:bg-gray-800 dark:text-white"/>
                            </PopoverContent>
                        </Popover>
                    </div>
                    <div class="flex items-end space-x-2">
                        <Button @click="loadAppointments" class="bg-nova-primary hover:bg-nova-accent dark:text-nova-light dark:bg-nova-accent dark:hover:bg-nova-primary">
                            <Icon name="search" size="16" class="mr-2"/>
                            Filtruj
                        </Button>
                        <Button variant="outline" @click="resetFilters" class="dark:bg-gray-700 dark:text-white dark:border-gray-600 dark:hover:bg-gray-600">
                            <Icon name="x" size="16" class="mr-2"/>
                            Wyczyść filtry
                        </Button>
                    </div>
                </div>
            </div>

            <!-- Tabela wizyt -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm overflow-hidden border dark:border-gray-700">
                <ScrollArea class="w-full h-[clamp(250px,calc(100vh-400px),500px)]">
                    <Table class="dark:text-gray-200">
                        <TableCaption v-if="appointments.length === 0" class="dark:text-gray-400">
                            Brak wizyt spełniających kryteria
                        </TableCaption>
                        <TableHeader class="dark:bg-gray-800">
                            <TableRow class="dark:border-gray-700">
                                <TableHead class="dark:text-gray-200">ID</TableHead>
                                <TableHead class="dark:text-gray-200">Pacjent</TableHead>
                                <TableHead class="dark:text-gray-200">Lekarz</TableHead>
                                <TableHead class="dark:text-gray-200">Procedura</TableHead>
                                <TableHead class="dark:text-gray-200">Data i godzina</TableHead>
                                <TableHead class="dark:text-gray-200">Status</TableHead>
                                <TableHead class="dark:text-gray-200">Akcje</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-if="loading" class="dark:border-gray-700">
                                <TableCell colSpan="7" class="text-center py-4 dark:text-gray-200">
                                    <div class="flex justify-center items-center">
                                        <Icon name="loader2" class="animate-spin mr-2" size="20"/>
                                        Ładowanie danych...
                                    </div>
                                </TableCell>
                            </TableRow>
                            <TableRow
                                v-else
                                v-for="appointment in appointments"
                                :key="appointment.id"
                                class="border-b hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-700"
                            >
                                <TableCell class="dark:text-gray-200">{{ appointment.id }}</TableCell>
                                <TableCell>
                                    <router-link :to="`/admin/patients/${appointment.patient.id}`"
                                                 class="text-blue-600 hover:underline dark:text-blue-400">
                                        {{ appointment.patient.name }}
                                    </router-link>
                                </TableCell>
                                <TableCell>
                                    <router-link :to="`/admin/doctors/${appointment.doctor.id}`"
                                                 class="text-blue-600 hover:underline dark:text-blue-400">
                                        {{ appointment.doctor.name }}
                                    </router-link>
                                </TableCell>
                                <TableCell class="dark:text-gray-200">{{ appointment.procedure.name }}</TableCell>
                                <TableCell class="dark:text-gray-200">{{ formatDateTime(appointment.appointment_datetime) }}</TableCell>
                                <TableCell>
                <span :class="`px-2 py-1 rounded-full text-xs font-medium ${getStatusClass(appointment.status)}`">
                    {{ getStatusLabel(appointment.status) }}
                </span>
                                </TableCell>
                                <TableCell>
                                    <div class="flex space-x-2">
                                        <Button variant="outline" size="sm" class="flex items-center dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:hover:bg-gray-600"
                                                @click="openEditForm(appointment)">
                                            <Icon name="edit" size="14" class="mr-1"/>
                                            Edytuj
                                        </Button>
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            class="flex items-center text-red-600 border-red-600 hover:bg-red-50 dark:text-red-400 dark:border-red-500 dark:hover:bg-red-900/30"
                                            @click="deleteAppointment(appointment.id)"
                                        >
                                            <Icon name="trash-2" size="14" class="mr-1"/>
                                            Usuń
                                        </Button>
                                        <router-link :to="`/admin/appointment/${appointment.id}`">
                                            <Tooltip>
                                                <TooltipTrigger asChild>
                                                    <Button
                                                        variant="outline"
                                                        size="sm"
                                                        class="flex items-center text-blue-600 border-blue-600 hover:bg-blue-50 dark:text-blue-400 dark:border-blue-500 dark:hover:bg-blue-900/30"
                                                        @click="router.push(`/admin/appointment/${appointment.id}`)"
                                                    >
                                                        <Icon name="info" size="14" class="mr-1"/>
                                                    </Button>
                                                </TooltipTrigger>
                                                <TooltipContent>
                                                    <p>Szczegóły wizyty</p>
                                                </TooltipContent>
                                            </Tooltip>
                                        </router-link>
                                    </div>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </ScrollArea>

                <!-- Paginacja -->
                <div class="flex justify-center items-center p-3 border-t dark:border-gray-700 dark:bg-gray-800">
                    <Pagination
                        v-if="meta.last_page > 1"
                        :items-per-page="meta.per_page"
                        :total="meta.total"
                        :sibling-count="1"
                        show-edges
                        :default-page="meta.current_page"
                        @update:page="changePage"
                    >
                        <PaginationList v-slot="{ items }" class="flex items-center gap-1">
                            <PaginationFirst @click="changePage(1)" class="dark:bg-gray-700 dark:text-white dark:border-gray-600"/>
                            <PaginationPrevious @click="changePage(Math.max(1, meta.current_page - 1))" class="dark:bg-gray-700 dark:text-white dark:border-gray-600"/>

                            <template v-for="(item, index) in items" :key="index">
                                <PaginationListItem v-if="item.type === 'page'" :value="item.value" as-child>
                                    <Button
                                        :variant="meta.current_page === item.value ? 'default' : 'outline'"
                                        :class="meta.current_page === item.value ? 'bg-nova-primary hover:bg-nova-accent text-white' : 'dark:bg-gray-700 dark:text-white dark:border-gray-600'"
                                        size="sm"
                                    >
                                        {{ item.value }}
                                    </Button>
                                </PaginationListItem>
                                <PaginationEllipsis v-else :index="index" class="dark:text-gray-400"/>
                            </template>

                            <PaginationNext @click="changePage(Math.min(meta.last_page, meta.current_page + 1))" class="dark:bg-gray-700 dark:text-white dark:border-gray-600"/>
                            <PaginationLast @click="changePage(meta.last_page)" class="dark:bg-gray-700 dark:text-white dark:border-gray-600"/>
                        </PaginationList>
                    </Pagination>
                </div>
            </div>
        </div>

        <!-- Modal edycji wizyty -->
        <div v-if="showEditAppointmentForm && selectedAppointment"
             class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
            <div class="bg-white dark:bg-gray-800 rounded-xl p-6 max-w-md w-full mx-4 shadow-lg">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-medium dark:text-white">Edycja Wizyty</h3>
                    <Button variant="ghost" class="h-8 w-8 p-0 dark:text-white dark:hover:bg-gray-700" @click="closeEditForm">
                        <Icon name="x" size="18"/>
                    </Button>
                </div>

                <div class="space-y-4">
                    <!-- Pacjent (tylko informacyjnie) -->
                    <div class="space-y-2">
                        <Label class="dark:text-gray-200">Pacjent</Label>
                        <p class="text-sm p-2 bg-gray-50 dark:bg-gray-700 rounded dark:text-white">{{ selectedAppointment.patient.name }}</p>
                    </div>

                    <!-- Lekarz (tylko informacyjnie) -->
                    <div class="space-y-2">
                        <Label class="dark:text-gray-200">Lekarz</Label>
                        <p class="text-sm p-2 bg-gray-50 dark:bg-gray-700 rounded dark:text-white">{{ selectedAppointment.doctor.name }}</p>
                    </div>

                    <!-- Procedura (tylko informacyjnie) -->
                    <div class="space-y-2">
                        <Label class="dark:text-gray-200">Procedura</Label>
                        <p class="text-sm p-2 bg-gray-50 dark:bg-gray-700 rounded dark:text-white">{{ selectedAppointment.procedure.name }}</p>
                    </div>

                    <!-- Data wizyty -->
                    <div class="space-y-2">
                        <Label class="dark:text-gray-200">Data wizyty</Label>
                        <Popover>
                            <PopoverTrigger as-child>
                                <Button
                                    variant="outline"
                                    class="w-full justify-start text-left font-normal dark:bg-gray-700 dark:text-white dark:border-gray-600"
                                >
                                    <Icon name="calendar" size="16" class="mr-2"/>
                                    {{
                                        selectedDate ? formatDateTime(selectedDate.toISOString()) : "Wybierz datę wizyty"
                                    }}
                                </Button>
                            </PopoverTrigger>
                            <PopoverContent class="w-auto p-0 dark:bg-gray-800 dark:border-gray-700">
                                <Calendar v-model="selectedDate" initial-focus mode="single"
                                          @update:model-value="onAppointmentDateChange"
                                          class="dark:bg-gray-800 dark:text-white"/>
                            </PopoverContent>
                        </Popover>
                        <div v-if="appointmentErrors.appointment_datetime" class="text-sm text-red-500">
                            {{ appointmentErrors.appointment_datetime[0] }}
                        </div>
                    </div>

                    <!-- Status -->
                    <div class="space-y-2">
                        <Label for="edit-status" class="dark:text-gray-200">Status wizyty</Label>
                        <select
                            id="edit-status"
                            v-model="selectedAppointment.status"
                            class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm dark:bg-gray-700 dark:text-white dark:border-gray-600"
                            :class="{'border-red-500': appointmentErrors.status}"
                        >
                            <option v-for="status in statusOptions" :key="status.value" :value="status.value">
                                {{ status.label }}
                            </option>
                        </select>
                        <div v-if="appointmentErrors.status" class="text-sm text-red-500">
                            {{ appointmentErrors.status[0] }}
                        </div>
                    </div>

                    <!-- Notatki pacjenta -->
                    <div class="space-y-2">
                        <Label for="patient-notes" class="dark:text-gray-200">Notatki pacjenta</Label>
                        <textarea
                            id="patient-notes"
                            v-model="selectedAppointment.patient_notes"
                            class="w-full rounded-md border border-input px-3 py-2 text-sm dark:bg-gray-700 dark:text-white dark:border-gray-600"
                            :class="{'border-red-500': appointmentErrors.patient_notes}"
                            rows="3"
                            placeholder="Notatki od pacjenta"
                        ></textarea>
                        <div v-if="appointmentErrors.patient_notes" class="text-sm text-red-500">
                            {{ appointmentErrors.patient_notes[0] }}
                        </div>
                    </div>

                    <!-- Notatki lekarza -->
                    <div class="space-y-2">
                        <Label for="doctor-notes" class="dark:text-gray-200">Notatki lekarza</Label>
                        <textarea
                            id="doctor-notes"
                            v-model="selectedAppointment.doctor_notes"
                            class="w-full rounded-md border border-input px-3 py-2 text-sm dark:bg-gray-700 dark:text-white dark:border-gray-600"
                            :class="{'border-red-500': appointmentErrors.doctor_notes}"
                            rows="3"
                            placeholder="Notatki od lekarza"
                        ></textarea>
                        <div v-if="appointmentErrors.doctor_notes" class="text-sm text-red-500">
                            {{ appointmentErrors.doctor_notes[0] }}
                        </div>
                    </div>

                    <!-- Przyciski -->
                    <div class="flex justify-end gap-3 pt-4">
                        <Button
                            type="button"
                            variant="outline"
                            @click="closeEditForm"
                            class="dark:bg-gray-700 dark:text-white dark:border-gray-600 dark:hover:bg-gray-600"
                        >
                            Anuluj
                        </Button>
                        <Button
                            @click="updateAppointment"
                            :disabled="appointmentFormLoading"
                            class="flex bg-nova-primary hover:bg-nova-accent items-center gap-2"
                        >
                            <Icon v-if="appointmentFormLoading" name="loader2" class="animate-spin" size="16"/>
                            <span>Aktualizuj</span>
                        </Button>
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

thead th {
    background-color: #f9fafb;
    color: #374151;
    font-weight: 600;
    text-align: left;
    padding: 0.75rem 1rem;
    font-size: 0.875rem;
}

tbody td {
    padding: 1rem;
    vertical-align: middle;
}
</style>
