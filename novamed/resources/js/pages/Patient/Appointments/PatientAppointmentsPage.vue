<script setup lang="ts">
import Icon from '@/components/Icon.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Pagination, PaginationEllipsis, PaginationFirst, PaginationLast, PaginationNext, PaginationPrevious } from '@/components/ui/pagination';
import { Skeleton } from '@/components/ui/skeleton';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import axios from 'axios';
import { PaginationList, PaginationListItem } from 'reka-ui';
import { computed, onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';

const router = useRouter();

interface DoctorInfo {
    id: number;
    first_name: string;
    last_name: string;
    specialization: string;
}

interface ProcedureInfo {
    id: number;
    name: string;
    base_price: number;
}

interface Appointment {
    id: number;
    appointment_datetime: string;
    status: 'scheduled' | 'confirmed' | 'completed' | 'cancelled_by_patient' | 'cancelled_by_clinic' | 'no_show';
    patient_notes?: string | null;
    doctor: DoctorInfo;
    procedure: ProcedureInfo;
    created_at: string;
}

const appointments = ref<Appointment[]>([]);
const loading = ref(true);
const error = ref<string | null>(null);

const query = ref({
    page: 1,
    per_page: 8,

});

const totalPages = ref(0);
const totalItems = ref(0);

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Panel Pacjenta', href: '/dashboard' },
    { title: 'Moje Wizyty' },
];

const fetchAppointments = async () => {
    loading.value = true;
    error.value = null;
    try {
        const params = new URLSearchParams();
        params.append('page', query.value.page.toString());
        params.append('per_page', query.value.per_page.toString());
        const response = await axios.get('/api/v1/patient/appointments', { params });

        appointments.value = response.data.data || [];
        if (response.data.meta) {
            totalPages.value = response.data.meta.last_page || 1;
            totalItems.value = response.data.meta.total || 0;
            query.value.page = response.data.meta.current_page;
        } else {
            totalPages.value = 1;
            totalItems.value = appointments.value.length;
        }
    } catch (err: any) {
        console.error('Błąd podczas pobierania wizyt:', err);
        error.value = err.response?.data?.message || 'Nie udało się pobrać listy wizyt.';
        appointments.value = [];
    } finally {
        loading.value = false;
    }
};

const goToPage = (page: number) => {
    if (page < 1 || page > totalPages.value || page === query.value.page) return;
    query.value.page = page;
    fetchAppointments();
};

const formatDateTime = (dateTimeString: string): string => {
    if (!dateTimeString) return 'Brak danych';
    const date = new Date(dateTimeString);
    return date.toLocaleString('pl-PL', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const getStatusInfo = (
    status: Appointment['status'],
): {
    text: string;
    variant: 'default' | 'secondary' | 'destructive' | 'outline' | 'warning' | 'success';
} => {
    switch (status) {
        case 'scheduled':
            return { text: 'Zaplanowana', variant: 'outline' };
        case 'confirmed':
            return { text: 'Potwierdzona', variant: 'default' };
        case 'completed':
            return { text: 'Zakończona', variant: 'success' };
        case 'cancelled_by_patient':
            return { text: 'Odwołana (Ty)', variant: 'destructive' };
        case 'cancelled_by_clinic':
            return { text: 'Odwołana (Klinika)', variant: 'destructive' };
        case 'no_show':
            return { text: 'Nieobecność', variant: 'warning' };
        default:
            return { text: status, variant: 'secondary' };
    }
};

const cancelAppointment = async (appointmentId: number) => {
    if (!confirm('Czy na pewno chcesz odwołać tę wizytę?')) return;
    try {
        await axios.delete(`/api/v1/patient/appointments/${appointmentId}`);
        fetchAppointments();
    } catch (err: any) {
        console.error('Błąd podczas odwoływania wizyty:', err);
        alert(err.response?.data?.message || 'Nie udało się odwołać wizyty.');
    }
};

const canCancel = (appointment_datetime: string, status: Appointment['status']): boolean => {
    if (status !== 'scheduled' && status !== 'confirmed') return false;
    const appointmentDate = new Date(appointment_datetime);
    const now = new Date();
    return appointmentDate.getTime() > now.getTime() + 24 * 60 * 60 * 1000;
};

onMounted(() => {
    fetchAppointments();
});
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="container mx-auto px-4 py-6 sm:px-6 lg:px-8">
            <div class="mb-6 flex flex-col items-start justify-between gap-4 sm:flex-row sm:items-center">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-gray-900 sm:text-3xl dark:text-gray-100">Moje Wizyty</h1>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Przeglądaj swoje nadchodzące i przeszłe wizyty.</p>
                </div>
                <Button
                    @click="router.push('/procedures')"
                    class="bg-nova-primary hover:bg-nova-accent dark:bg-nova-accent dark:hover:bg-nova-primary w-full sm:w-auto dark:text-gray-900"
                >
                    <Icon name="plus-circle" class="mr-2 h-5 w-5" />
                    Umów nową wizytę
                </Button>
            </div>

            <div class="overflow-hidden rounded-lg border bg-white shadow-md dark:border-gray-700 dark:bg-gray-800">
                <div v-if="loading" class="space-y-4 p-6">
                    <Skeleton v-for="i in query.per_page" :key="`skel-appt-${i}`" class="h-16 w-full dark:bg-gray-700" />
                </div>
                <div v-else-if="error" class="p-6 text-center text-red-500 dark:text-red-400">
                    <Icon name="alert-triangle" class="mx-auto h-12 w-12 text-red-400 dark:text-red-500" />
                    <p class="mt-2 text-lg font-medium">{{ error }}</p>
                    <Button @click="fetchAppointments" variant="outline" class="mt-4">Spróbuj ponownie</Button>
                </div>
                <div v-else-if="!appointments.length" class="p-10 text-center text-gray-500 dark:text-gray-400">
                    <Icon name="calendar-off" class="mx-auto h-16 w-16 text-gray-400 dark:text-gray-500" />
                    <p class="mt-4 text-xl">Nie masz jeszcze żadnych wizyt.</p>
                    <p class="mt-1 text-sm">Wygląda na to, że czas umówić swoją pierwszą wizytę!</p>
                </div>
                <template v-else>
                    <div class="overflow-x-auto">
                        <Table class="min-w-full">
                            <TableHeader class="bg-gray-50 dark:bg-gray-700/60">
                                <TableRow class="dark:border-gray-600">
                                    <TableHead
                                        class="px-4 py-3 text-left text-xs font-medium tracking-wider text-gray-500  dark:text-gray-300"
                                    >
                                        Data i Godzina
                                    </TableHead>
                                    <TableHead
                                        class="px-4 py-3 text-left text-xs font-medium tracking-wider text-gray-500  dark:text-gray-300"
                                    >
                                        Lekarz
                                    </TableHead>
                                    <TableHead
                                        class="px-4 py-3 text-left text-xs font-medium tracking-wider text-gray-500  dark:text-gray-300"
                                    >
                                        Zabieg
                                    </TableHead>
                                    <TableHead
                                        class="px-4 py-3 text-left text-xs font-medium tracking-wider text-gray-500  dark:text-gray-300"
                                    >
                                        Status
                                    </TableHead>
                                    <TableHead
                                        class="px-4 py-3 text-left text-xs font-medium tracking-wider text-gray-500  dark:text-gray-300"
                                    >
                                        Akcje
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody class="divide-y divide-gray-200 dark:divide-gray-700">
                                <TableRow v-for="appt in appointments" :key="appt.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/70">
                                    <TableCell class="px-4 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                            {{ formatDateTime(appt.appointment_datetime) }}
                                        </div>
                                    </TableCell>
                                    <TableCell class="px-4 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900 dark:text-gray-100">
                                            {{ appt.doctor.first_name }} {{ appt.doctor.last_name }}
                                        </div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ appt.doctor.specialization }}
                                        </div>
                                    </TableCell>
                                    <TableCell class="px-4 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900 dark:text-gray-100">{{ appt.procedure.name }}</div>
                                    </TableCell>
                                    <TableCell class="px-4 py-4 whitespace-nowrap">
                                        <Badge :variant="getStatusInfo(appt.status).variant">
                                            {{ getStatusInfo(appt.status).text }}
                                        </Badge>
                                    </TableCell>
                                    <TableCell class="px-4 py-4 text-sm font-medium whitespace-nowrap">
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            @click="router.push({ name: 'patient.appointments.show', params: { id: appt.id } })"
                                            class="dark:text-gray-300 dark:hover:bg-gray-700"
                                            title="Zobacz szczegóły wizyty"
                                        >
                                            <Icon name="eye" class="h-4 w-4" />
                                        </Button>
                                        <Button
                                            v-if="canCancel(appt.appointment_datetime, appt.status)"
                                            variant="ghost"
                                            size="sm"
                                            @click="cancelAppointment(appt.id)"
                                            class="ml-2 text-red-600 hover:text-red-700 dark:text-red-400 dark:hover:bg-red-900/20 dark:hover:text-red-300"
                                            title="Odwołaj wizytę"
                                        >
                                            <Icon name="x-circle" class="h-4 w-4" />
                                        </Button>
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>
                    <div
                        v-if="totalPages > 1"
                        class="flex items-center justify-center border-t bg-gray-50 px-4 py-3 dark:border-gray-700 dark:bg-gray-800"
                    >
                        <Pagination :total="totalItems" :items-per-page="query.per_page" :sibling-count="1" show-edges v-model:page="query.page">
                            <PaginationList v-slot="{ items }" class="flex items-center gap-1">
                                <PaginationFirst
                                    @click="goToPage(1)"
                                    class="dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600"
                                />
                                <PaginationPrevious
                                    @click="goToPage(Math.max(1, query.page - 1))"
                                    class="dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600"
                                />
                                <template v-for="(item, index) in items" :key="index">
                                    <PaginationListItem v-if="item.type === 'page'" :value="item.value" as-child>
                                        <Button
                                            :variant="item.value === query.page ? 'default' : 'outline'"
                                            :class="
                                                item.value === query.page
                                                    ? 'bg-nova-primary hover:bg-nova-accent dark:bg-nova-accent dark:hover:bg-nova-primary text-white dark:text-gray-900'
                                                    : 'dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600'
                                            "
                                            size="sm"
                                        >
                                            {{ item.value }}
                                        </Button>
                                    </PaginationListItem>
                                    <PaginationEllipsis v-else :index="index" class="dark:text-gray-500" />
                                </template>
                                <PaginationNext
                                    @click="goToPage(Math.min(totalPages, query.page + 1))"
                                    class="dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600"
                                />
                                <PaginationLast
                                    @click="goToPage(totalPages)"
                                    class="dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600"
                                />
                            </PaginationList>
                        </Pagination>
                    </div>
                </template>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>

</style>
