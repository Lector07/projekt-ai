<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle} from '@/components/ui/card';
import Icon from '@/components/Icon.vue';
import { Badge } from '@/components/ui/badge';
import type { BreadcrumbItem } from '@/types';
import { Skeleton } from '@/components/ui/skeleton';
import { useToast } from 'primevue/usetoast';
import {CircleAlert} from 'lucide-vue-next';

interface DoctorInfo {
    id: number;
    first_name: string;
    last_name: string;
    specialization: string;
}

interface ProcedureInfo {
    id: number;
    name: string;
    description?: string;
    base_price: number;
}

interface AppointmentDetail {
    id: number;
    appointment_datetime: string;
    status: 'scheduled' | 'confirmed' | 'completed' | 'cancelled_by_patient' | 'cancelled_by_clinic' | 'no_show';
    patient_notes?: string | null;
    admin_notes?: string | null;
    doctor: DoctorInfo;
    procedure: ProcedureInfo;
    created_at: string;
}

const props = defineProps<{
    id: string;
}>();

const router = useRouter();
const toast = useToast();

const appointment = ref<AppointmentDetail | null>(null);
const loading = ref(true);
const error = ref<string | null>(null);

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    { title: 'Panel Pacjenta', href: '/dashboard' },
    { title: 'Moje Wizyty', href: '/patient/appointments' },
    { title: appointment.value ? `Wizyta` : 'Szczegóły Wizyty' },
]);

const fetchAppointmentDetail = async (appointmentId: string) => {
    loading.value = true;
    error.value = null;
    appointment.value = null;
    try {
        const response = await axios.get(`/api/v1/patient/appointments/${appointmentId}`);
        appointment.value = response.data.data;
    } catch (err: any) {
        console.error("Błąd podczas pobierania szczegółów wizyty:", err);
        error.value = err.response?.data?.message || "Nie udało się pobrać danych wizyty.";
    } finally {
        loading.value = false;
    }
};

const formatDateTime = (dateTimeString?: string): string => {
    if (!dateTimeString) return 'Brak danych';
    const date = new Date(dateTimeString);
    return date.toLocaleString('pl-PL', {
        year: 'numeric', month: 'long', day: 'numeric',
        hour: '2-digit', minute: '2-digit'
    });
};

const getStatusInfo = (statusValue?: AppointmentDetail['status']): { text: string; variant: 'default' | 'secondary' | 'destructive' | 'outline' | undefined } => {
    if (!statusValue) {
        console.warn('getStatusInfo otrzymało pusty status');
        return { text: 'Nieokreślony', variant: 'secondary' };
    }
    switch (statusValue) {
        case 'scheduled': return { text: 'Zaplanowana', variant: 'outline' };
        case 'confirmed': return { text: 'Potwierdzona', variant: 'default' };
        case 'completed': return { text: 'Zakończona', variant: 'default' };
        case 'cancelled_by_patient': return { text: 'Odwołana (Ty)', variant: 'destructive' };
        case 'cancelled_by_clinic': return { text: 'Odwołana (Klinika)', variant: 'destructive' };
        case 'no_show': return { text: 'Nieobecność', variant: 'outline' };
        default:
            console.warn(`Nieznany status wizyty w getStatusInfo: ${statusValue}`);
            return { text: String(statusValue), variant: 'secondary' };
    }
};

const canCancel = (appointment_datetime?: string, status?: AppointmentDetail['status']): boolean => {
    if (!appointment_datetime || !status) return false;
    if (status !== 'scheduled' && status !== 'confirmed') return false;
    const appointmentDate = new Date(appointment_datetime);
    const now = new Date();
    return appointmentDate.getTime() > now.getTime() + 24 * 60 * 60 * 1000;
};

const cancelAppointment = async () => {
    if (!appointment.value || !confirm("Czy na pewno chcesz odwołać tę wizytę?")) return;
    try {
        await axios.delete(`/api/v1/patient/appointments/${appointment.value.id}`);
        toast.add({ severity: 'success', summary: 'Sukces', detail: 'Wizyta została pomyślnie odwołana.', life: 3000 });
        router.push({ name: 'patient.appointments' });
    } catch (err: any) {
        toast.add({ severity: 'error', summary: 'Błąd', detail: err.response?.data?.message || "Nie udało się odwołać wizyty.", life: 3000 });
    }
};

watch(() => props.id, (newId) => {
    if (newId) {
        fetchAppointmentDetail(newId);
    }
}, { immediate: true });

</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Toast/>
        <div class="container mx-auto py-6 px-4 sm:px-6 lg:px-8">
            <div v-if="loading" class="space-y-6">
                <Skeleton class="h-12 w-1/2 dark:bg-gray-700" />
                <Skeleton class="h-8 w-1/3 dark:bg-gray-700" />
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-4">
                    <Skeleton class="h-48 w-full dark:bg-gray-700" />
                    <Skeleton class="h-48 w-full dark:bg-gray-700" />
                </div>
            </div>
            <div v-else-if="error" class="p-6 text-center text-red-500 dark:text-red-400 rounded-lg bg-red-50 dark:bg-red-900/20">
                <Icon name="alert-circle" class="mx-auto h-12 w-12 text-red-400 dark:text-red-500" />
                <p class="mt-2 text-lg font-medium">{{ error }}</p>
                <Button @click="fetchAppointmentDetail(props.id)" variant="outline" class="mt-4">Spróbuj ponownie</Button>
            </div>
            <div v-else-if="appointment" class="space-y-6">
                <div class="flex flex-col sm:flex-row justify-between items-start">
                    <div>
                        <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 dark:text-gray-100">
                            Szczegóły Wizyty
                        </h1>
                        <p class="text-gray-500 dark:text-gray-400 text-sm">
                            Zarezerwowano: {{ formatDateTime(appointment.created_at) }}
                        </p>
                    </div>
                    <Badge
                        v-if="appointment.status"
                        :variant="getStatusInfo(appointment.status).variant"
                        class="mt-2 sm:mt-0 text-sm px-3 py-1"
                    >
                        {{ getStatusInfo(appointment.status).text }}
                    </Badge>
                    <Badge v-else variant="secondary" class="mt-2 sm:mt-0 text-sm px-3 py-1">
                        Brak statusu
                    </Badge>
                </div>

                <Card class="dark:bg-gray-800 border dark:border-gray-700">
                    <CardHeader>
                        <CardTitle class="text-xl">Informacje o Wizycie</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div>
                            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Data i Godzina</h3>
                            <p class="text-lg text-gray-900 dark:text-gray-100">{{ formatDateTime(appointment.appointment_datetime) }}</p>
                        </div>
                        <hr class="dark:border-gray-700"/>
                        <div>
                            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Lekarz</h3>
                            <p class="text-lg text-gray-900 dark:text-gray-100">
                                Dr {{ appointment.doctor.first_name }} {{ appointment.doctor.last_name }}
                            </p>
                            <p class="text-sm text-gray-600 dark:text-gray-300">{{ appointment.doctor.specialization }}</p>
                            <Button variant="link" size="sm" @click="router.push(`/doctors/${appointment.doctor.id}`)" class="p-0 h-auto">Zobacz profil lekarza</Button>
                        </div>
                        <hr class="dark:border-gray-700"/>
                        <div>
                            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Zabieg</h3>
                            <p class="text-lg text-gray-900 dark:text-gray-100">{{ appointment.procedure.name }}</p>
                            <p v-if="appointment.procedure.description" class="text-sm text-gray-600 dark:text-gray-300 mt-1">{{ appointment.procedure.description }}</p>
                            <p class="text-sm font-semibold text-gray-700 dark:text-gray-200 mt-1">Cena: {{ appointment.procedure.base_price }} zł</p>
                        </div>
                        <hr class="dark:border-gray-700"/>
                        <div v-if="appointment.patient_notes">
                            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Twoje notatki</h3>
                            <p class="text-gray-700 dark:text-gray-200 whitespace-pre-wrap bg-gray-50 dark:bg-gray-700/50 p-3 rounded-md">{{ appointment.patient_notes }}</p>
                        </div>
                        <div v-if="appointment.admin_notes">
                            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Notatki od kliniki</h3>
                            <p class="text-gray-700 dark:text-gray-200 whitespace-pre-wrap bg-gray-50 dark:bg-gray-700/50 p-3 rounded-md">{{ appointment.admin_notes }}</p>
                        </div>
                    </CardContent>
                </Card>

                <div class="flex flex-col sm:flex-row gap-3 pt-4">
                    <Button @click="router.push('/patient/appointments')" variant="outline" class="w-full sm:w-auto dark:text-gray-200 dark:border-gray-600 dark:hover:bg-gray-700">
                        <Icon name="arrow-left" class="mr-2 h-4 w-4" />
                        Powrót do listy wizyt
                    </Button>
                    <Button
                        v-if="canCancel(appointment.appointment_datetime, appointment.status)"
                        @click="cancelAppointment"
                        variant="destructive"
                        class="w-full sm:w-auto"
                    >
                        <Icon name="x-circle" class="mr-2 h-4 w-4" />
                        Odwołaj wizytę
                    </Button>
                    <div v-else class="w-full sm:w-auto sm:ml-auto">
                        <p class="text-sm text-yellow-700 dark:text-yellow-400 bg-yellow-50 dark:bg-yellow-800/30 p-3 rounded-md flex items-center shadow-sm">
                            <CircleAlert class="mr-2 h-5 w-5 flex-shrink-0 text-yellow-500 dark:text-yellow-300" />
                            <span>Nie można odwołać wizyty później niż 24 godziny przed jej terminem.</span>
                        </p>
                    </div>
                </div>

            </div>
            <div v-else class="text-center py-10">
                <p class="text-xl text-gray-500 dark:text-gray-400">Nie znaleziono wizyty.</p>
            </div>
        </div>
    </AppLayout>
</template>
