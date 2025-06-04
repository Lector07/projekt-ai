<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { format } from 'date-fns';
import { pl } from 'date-fns/locale';
import AppLayout from '@/layouts/AppLayout.vue';
import { Card, CardHeader, CardTitle, CardContent, CardFooter } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import Icon from '@/components/Icon.vue';
import { Separator } from '@/components/ui/separator';
import { Skeleton } from '@/components/ui/skeleton';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import {Pagination, PaginationContent, PaginationEllipsis, PaginationFirst, PaginationItem, PaginationLast, PaginationNext, PaginationPrevious } from '@/components/ui/pagination';
import axios from 'axios';

const router = useRouter();
const appointments = ref<Appointment[]>([]);
const loading = ref(true);
const error = ref('');
const currentPage = ref(1);
const lastPage = ref(1);
const total = ref(0);
const perPage = ref(10);

interface Appointment {
    id: number;
    appointment_datetime: string;
    status: string;
    patient: {
        name: string;
    };
    procedure: {
        name: string;
    };
}

// Filtry
const filters = ref({
    status: 'all', // Zmiana z '' na 'all'
    searchQuery: '',
    dateFrom: '',
    dateTo: '',
    sortBy: 'newest',
});

const resetFilters = () => {
    filters.value = {
        status: '',
        searchQuery: '',
        dateFrom: '',
        dateTo: '',
        sortBy: 'newest',
    };
    applyFilters();
};

const BreadcrumbItems = computed(() => [
    { title: 'Moje wizyty', href: '/doctor/appointments' },
]);

// Opcje statusów
const statusOptions = [
    { value: 'all', label: 'Wszystkie statusy' },
    { value: 'scheduled', label: 'Zaplanowane' },
    { value: 'completed', label: 'Zakończone' },
    {value: 'confirmed', label: 'Potwierdzone'},
    { value: 'cancelled', label: 'Anulowane' },
    { value: 'no_show', label: 'Nieobecność' },
];

// Opcje sortowania
const sortOptions = [
    { value: 'newest', label: 'Najnowsze' },
    { value: 'oldest', label: 'Najstarsze' },
    { value: 'patient_asc', label: 'Pacjent (A-Z)' },
    { value: 'patient_desc', label: 'Pacjent (Z-A)' },
];

// Status z kolorami
const getStatusInfo = (status: string) => {
    const statusMap = {
        scheduled: { text: 'Zaplanowana', variant: 'default' },
        in_progress: { text: 'W trakcie', variant: 'warning' },
        confirmed: { text: 'Potwierdzona', variant: 'defoult' },
        completed: { text: 'Zakończona', variant: 'success' },
        cancelled: { text: 'Anulowana', variant: 'destructive' },
        no_show: { text: 'Nieobecność', variant: 'destructive' },
    };

    return statusMap[status as keyof typeof statusMap] || { text: 'Nieznany', variant: 'secondary' };
};

// Formatowanie daty
const formatDate = (dateString: string) => {
    try {
        return format(new Date(dateString), 'dd MMMM yyyy', { locale: pl });
    } catch (e) {
        return dateString;
    }
};

// Formatowanie godziny
const formatTime = (dateString: string) => {
    try {
        return format(new Date(dateString), 'HH:mm');
    } catch (e) {
        return '';
    }
};

// Pobieranie danych
const fetchAppointments = async (page: number = 1) => {
    loading.value = true;
    error.value = '';

    try {
        const params = {
            page,
            status: filters.value.status !== 'all' ? filters.value.status : '',
            search: filters.value.searchQuery,
            date_from: filters.value.dateFrom,
            date_to: filters.value.dateTo,
            sort_by: filters.value.sortBy,
        };

        const response = await axios.get('/api/v1/doctor/appointments', { params });
        appointments.value = response.data.data;
        currentPage.value = response.data.meta.current_page;
        lastPage.value = response.data.meta.last_page;
        total.value = response.data.meta.total;
    } catch (err) {
        error.value = 'Nie udało się załadować listy wizyt. Spróbuj ponownie później.';
        console.error('Error fetching appointments:', err);
    } finally {
        loading.value = false;
    }
};

// Zmiana strony
const onPageChange = (page: number) => {
    currentPage.value = page;
    fetchAppointments(page);
};

// Zastosowanie filtrów
const applyFilters = () => {
    currentPage.value = 1;
    fetchAppointments(1);
};

// Resetowanie filtrów
// Przejście do szczegółów wizyty
const navigateToAppointmentDetails = (id: number) => {
    router.push({ name: 'doctor.appointments.show', params: { id } });
};

// Pobranie danych przy montowaniu komponentu
onMounted(() => {
    fetchAppointments();
});
</script>

<template>
    <AppLayout :breadcrumbs="BreadcrumbItems">
        <div class="container mx-auto px-4 py-8">
            <div class="mb-6 flex flex-col items-start justify-between md:flex-row md:items-center">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Moje wizyty</h1>
                    <p class="text-gray-600 dark:text-gray-400">Zarządzaj wizytami pacjentów i swoim grafikiem</p>
                </div>
            </div>

            <Card class="mb-6 border dark:border-gray-700 dark:bg-gray-800">
                <CardHeader>
                    <CardTitle class="text-lg dark:text-gray-200">Filtrowanie i wyszukiwanie</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"> Wyszukaj pacjenta </label>
                            <Input v-model="filters.searchQuery" placeholder="Nazwisko pacjenta..." class="w-full" />
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"> Status wizyty </label>
                            <Select v-model="filters.status">
                                <SelectTrigger class="w-full">
                                    <SelectValue placeholder="Wybierz status" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="option in statusOptions" :key="option.value" :value="option.value">
                                        {{ option.label }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"> Od daty </label>
                            <Input v-model="filters.dateFrom" type="date" class="w-full" />
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"> Do daty </label>
                            <Input v-model="filters.dateTo" type="date" class="w-full" />
                        </div>
                    </div>

                    <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"> Sortowanie </label>
                            <Select v-model="filters.sortBy">
                                <SelectTrigger class="w-full">
                                    <SelectValue placeholder="Sortuj według" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="option in sortOptions" :key="option.value" :value="option.value">
                                        {{ option.label }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <div class="flex items-end space-x-2">
                            <Button class="bg-nova-primary hover:bg-nova-accent text-white" @click="applyFilters">
                                <Icon name="filter" class="mr-2 h-4 w-4" />
                                Zastosuj filtry
                            </Button>
                            <Button variant="outline" @click="resetFilters">
                                <Icon name="x" class="mr-2 h-4 w-4" />
                                Resetuj
                            </Button>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <Card class="border dark:border-gray-700 dark:bg-gray-800">
                <CardContent class="p-0">
                    <div v-if="loading" class="space-y-4 p-6">
                        <Skeleton v-for="i in 5" :key="i" class="h-16 w-full dark:bg-gray-700" />
                    </div>

                    <div v-else-if="error" class="p-6 text-center text-red-500 dark:text-red-400">
                        {{ error }}
                    </div>

                    <div v-else-if="appointments.length === 0" class="p-6 text-center">
                        <Icon name="calendar-off" class="mx-auto mb-2 h-12 w-12 text-gray-400 dark:text-gray-600" />
                        <p class="text-gray-600 dark:text-gray-400">Nie znaleziono wizyt spełniających kryteria.</p>
                    </div>

                    <div v-else>
                        <table class="w-full">
                            <thead>
                                <tr class="border-b bg-gray-50 dark:border-gray-700 dark:bg-gray-700/70">
                                    <th class="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase dark:text-gray-400">
                                        Data i godzina
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase dark:text-gray-400">
                                        Pacjent
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase dark:text-gray-400">
                                        Zabieg
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase dark:text-gray-400">
                                        Status
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase dark:text-gray-400">
                                        Akcje
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                <tr
                                    v-for="appointment in appointments"
                                    :key="appointment.id"
                                    class="cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700/30"
                                    @click="navigateToAppointmentDetails(appointment.id)"
                                >
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                            {{ formatDate(appointment.appointment_datetime) }}
                                        </div>
                                        <div class="text-sm text-gray-500 dark:text-gray-400">
                                            {{ formatTime(appointment.appointment_datetime) }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                            {{ appointment.patient.name }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm text-gray-500 dark:text-gray-400">
                                            {{ appointment.procedure.name }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <Badge :variant="getStatusInfo(appointment.status).variant">
                                            {{ getStatusInfo(appointment.status).text }}
                                        </Badge>
                                    </td>
                                    <td class="px-6 py-4 text-right text-sm font-medium whitespace-nowrap">
                                        <Button variant="ghost" size="sm" @click.stop="navigateToAppointmentDetails(appointment.id)">
                                            <Icon name="eye" class="mr-1 h-4 w-4" />
                                            Szczegóły
                                        </Button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </CardContent>

                <CardFooter v-if="lastPage > 1" class="border-t px-6 py-4 dark:border-gray-700">
                    <Pagination
                        :current-page="currentPage"
                        :total-pages="lastPage"
                        :itemsPerPage="perPage"
                        @page-change="onPageChange"
                    />
                </CardFooter>
            </Card>
        </div>
    </AppLayout>
</template>
