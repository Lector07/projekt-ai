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
import { useToast } from 'primevue/usetoast';


import {
    ScrollArea,
    ScrollBar
} from "@/components/ui/scroll-area";
import { Calendar } from '@/components/ui/calendar';
import { format } from 'date-fns';
import { pl } from 'date-fns/locale';
import {TooltipContent} from "@/components/ui/tooltip";

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
    doctor_id: string;
    patient_id: string;
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
    doctor_id: '',
    patient_id: '',
    status: '',
    date_from: '',
    date_to: '',
});

// Lista statusów
const statuses = [
    { value: '', label: 'Wszystkie statusy' },
    { value: 'booked', label: 'Zarezerwowana' },
    { value: 'confirmed', label: 'Potwierdzona' },
    { value: 'completed', label: 'Zakończona' },
    { value: 'cancelled', label: 'Anulowana' },
    { value: 'no-show', label: 'Nieobecność' },
];

// Obsługa paginacji
const changePage = (page: number) => {
    meta.value.current_page = page;
    loadAppointments();
};

const calendarDateFrom = ref<Date | undefined>(undefined);
const calendarDateTo = ref<Date | undefined>(undefined);

// Funkcje obsługi zmiany daty
const onDateFromChange = (date: Date | undefined) => {
    filters.date_from = date ? format(date, 'yyyy-MM-dd') : '';
};

const onDateToChange = (date: Date | undefined) => {
    filters.date_to = date ? format(date, 'yyyy-MM-dd') : '';
};

// Funkcja formatująca datę do wyświetlenia
const formatDisplayDate = (dateString: string) => {
    if (!dateString) return '';
    const date = new Date(dateString);
    return format(date, 'd MMMM yyyy', { locale: pl });
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

        const response = await axios.get('/api/v1/admin/appointments', { params });
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
        case 'booked':
            return 'bg-blue-100 text-blue-800';
        case 'confirmed':
            return 'bg-green-100 text-green-800';
        case 'completed':
            return 'bg-purple-100 text-purple-800';
        case 'cancelled':
            return 'bg-red-100 text-red-800';
        case 'no-show':
            return 'bg-orange-100 text-orange-800';
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
            <h1 class="text-2xl font-bold mb-1">Zarządzanie Wizytami</h1>

            <div class="bg-white rounded-lg shadow-sm mb-4 p-4 border">
                <h2 class="text-lg font-semibold mb-2">Filtry</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <Label for="doctor-filter" class="mb-1">Lekarz (ID)</Label>
                        <Input
                            id="doctor-filter"
                            v-model="filters.doctor_id"
                            type="number"
                            placeholder="ID lekarza"
                            class="w-full"
                        />
                    </div>
                    <div>
                        <Label for="patient-filter" class="mb-1">Pacjent (ID)</Label>
                        <Input
                            id="patient-filter"
                            v-model="filters.patient_id"
                            type="number"
                            placeholder="ID pacjenta"
                            class="w-full"
                        />
                    </div>
                    <div>
                        <Label for="status-filter" class="mb-1">Status</Label>
                        <select
                            id="status-filter"
                            v-model="filters.status"
                            class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                        >
                            <option v-for="status in statuses" :key="status.value" :value="status.value">
                                {{ status.label }}
                            </option>
                        </select>
                    </div>
                    <div>
                        <Label for="date-from" class="mb-1">Data od</Label>
                        <Popover>
                            <PopoverTrigger as-child>
                                <Button
                                    variant="outline"
                                    class="w-full justify-start text-left font-normal"
                                    :class="!filters.date_from && 'text-muted-foreground'"
                                >
                                    <Icon name="calendar" size="16" class="mr-2" />
                                    {{ filters.date_from ? formatDisplayDate(filters.date_from) : "Wybierz datę początkową" }}
                                </Button>
                            </PopoverTrigger>
                            <PopoverContent class="w-auto p-0">
                                <Calendar v-model="calendarDateFrom" initial-focus @update:model-value="onDateFromChange" />
                            </PopoverContent>
                        </Popover>
                    </div>
                    <div>
                        <Label for="date-to" class="mb-1">Data do</Label>
                        <Popover>
                            <PopoverTrigger as-child>
                                <Button
                                    variant="outline"
                                    class="w-full justify-start text-left font-normal"
                                    :class="!filters.date_to && 'text-muted-foreground'"
                                >
                                    <Icon name="calendar" size="16" class="mr-2" />
                                    {{ filters.date_to ? formatDisplayDate(filters.date_to) : "Wybierz datę końcową" }}
                                </Button>
                            </PopoverTrigger>
                            <PopoverContent class="w-auto p-0">
                                <Calendar v-model="calendarDateTo" initial-focus @update:model-value="onDateToChange" />
                            </PopoverContent>
                        </Popover>
                    </div>
                    <div class="flex items-end space-x-2">
                        <Button @click="loadAppointments" class="bg-nova-primary hover:bg-nova-accent">
                            <Icon name="search" size="16" class="mr-2" />
                            Filtruj
                        </Button>
                        <Button variant="outline" @click="resetFilters">
                            <Icon name="x" size="16" class="mr-2" />
                            Reset
                        </Button>
                    </div>
                </div>
            </div>

            <!-- Tabela wizyt -->
            <div class="bg-white rounded-lg shadow-sm overflow-hidden border">
                <ScrollArea class="w-full h-[clamp(250px,calc(100vh-400px),500px)]">
                    <Table>
                        <TableCaption v-if="appointments.length === 0">
                            Brak wizyt spełniających kryteria
                        </TableCaption>
                        <TableHeader>
                            <TableRow>
                                <TableHead>ID</TableHead>
                                <TableHead>Pacjent</TableHead>
                                <TableHead>Lekarz</TableHead>
                                <TableHead>Procedura</TableHead>
                                <TableHead>Data i godzina</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead>Akcje</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-if="loading">
                                <TableCell colSpan="7" class="text-center py-4">
                                    <div class="flex justify-center items-center">
                                        <Icon name="loader2" class="animate-spin mr-2" size="20" />
                                        Ładowanie danych...
                                    </div>
                                </TableCell>
                            </TableRow>
                            <TableRow
                                v-else
                                v-for="appointment in appointments"
                                :key="appointment.id"
                                class="border-b hover:bg-gray-50"
                            >
                                <TableCell>{{ appointment.id }}</TableCell>
                                <TableCell>
                                    <router-link :to="`/admin/patients/${appointment.patient.id}`" class="text-blue-600 hover:underline">
                                        {{ appointment.patient.name }}
                                    </router-link>
                                </TableCell>
                                <TableCell>
                                    <router-link :to="`/admin/doctors/${appointment.doctor.id}`" class="text-blue-600 hover:underline">
                                        {{ appointment.doctor.name }}
                                    </router-link>
                                </TableCell>
                                <TableCell>{{ appointment.procedure.name }}</TableCell>
                                <TableCell>{{ formatDateTime(appointment.appointment_datetime) }}</TableCell>
                                <TableCell>
                <span :class="`px-2 py-1 rounded-full text-xs font-medium ${getStatusClass(appointment.status)}`">
                    {{ getStatusLabel(appointment.status) }}
                </span>
                                </TableCell>
                                <TableCell>
                                    <div class="flex space-x-2">
                                        <router-link :to="`/admin/appointments/${appointment.id}/edit`">
                                            <Button variant="outline" size="sm" class="flex items-center">
                                                <Icon name="edit" size="14" class="mr-1" />
                                                Edytuj
                                            </Button>
                                        </router-link>
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            class="flex items-center text-red-600 border-red-600 hover:bg-red-50"
                                            @click="deleteAppointment(appointment.id)"
                                        >
                                            <Icon name="trash-2" size="14" class="mr-1" />
                                            Usuń
                                        </Button>
                                    </div>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </ScrollArea>

                <!-- Paginacja -->
                <div class="flex justify-center items-center p-3 border-t">
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
                            <PaginationFirst @click="changePage(1)" />
                            <PaginationPrevious @click="changePage(Math.max(1, meta.current_page - 1))" />

                            <template v-for="(item, index) in items" :key="index">
                                <PaginationListItem v-if="item.type === 'page'" :value="item.value" as-child>
                                    <Button
                                        :variant="meta.current_page === item.value ? 'default' : 'outline'"
                                        :class="meta.current_page === item.value ? 'bg-nova-primary hover:bg-nova-accent text-white' : ''"
                                        size="sm"
                                    >
                                        {{ item.value }}
                                    </Button>
                                </PaginationListItem>
                                <PaginationEllipsis v-else :index="index" />
                            </template>

                            <PaginationNext @click="changePage(Math.min(meta.last_page, meta.current_page + 1))" />
                            <PaginationLast @click="changePage(meta.last_page)" />
                        </PaginationList>
                    </Pagination>
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
