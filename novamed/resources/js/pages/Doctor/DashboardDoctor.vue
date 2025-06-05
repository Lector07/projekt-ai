<script setup lang="ts">
import Icon from '@/components/Icon.vue';
import { Badge } from '@/components/ui/badge'; // Dla statusu
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import { Skeleton } from '@/components/ui/skeleton';
import AppLayout from '@/layouts/AppLayout.vue';
import { useAuthStore } from '@/stores/auth';
import type { BreadcrumbItem } from '@/types';
import axios from 'axios';
import { computed, onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';

const router = useRouter();
const authStore = useAuthStore();
const doctorName = computed(() => authStore.user?.name || 'Lekarzu');

interface AppointmentInfo {
    id: number;
    time: string;
    datetime: string;
    patient_name: string;
    procedure_name: string;
    status: string;
}

const todaysAppointments = ref<AppointmentInfo[]>([]);
const tomorrowsAppointments = ref<AppointmentInfo[]>([]);
const loading = ref(true);
const error = ref<string | null>(null);

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Przegląd dnia' }];

async function fetchDashboardData() {
    loading.value = true;
    error.value = null;
    try {
        const response = await axios.get('/api/v1/doctor/dashboard-data');
        if (response.data && response.data.data) {
            todaysAppointments.value = response.data.data.todays_appointments || [];
            tomorrowsAppointments.value = response.data.data.tomorrows_appointments || [];
        } else {
            throw new Error('Niekompletne dane z API');
        }
    } catch (err: any) {
        console.error('Błąd podczas pobierania danych dashboardu lekarza:', err);
        error.value = err.response?.data?.message || 'Nie udało się załadować danych dashboardu.';
    } finally {
        loading.value = false;
    }
}

const getStatusInfo = (
    status: string,
): {
    text: string;
    variant: 'default' | 'secondary' | 'destructive' | 'outline';
} => {
    switch (status) {
        case 'scheduled':
            return { text: 'Zaplanowana', variant: 'secondary' };
        case 'confirmed':
            return { text: 'Potwierdzona', variant: 'default' };
        case 'completed':
            return { text: 'Zakończona', variant: 'default' };
        case 'cancelled_by_patient':
            return { text: 'Odwołana (Pacjent)', variant: 'destructive' };
        case 'cancelled_by_clinic':
            return { text: 'Odwołana (Klinika)', variant: 'destructive' };
        case 'no_show':
            return { text: 'Nieobecność', variant: 'destructive' };
        default:
            return { text: status, variant: 'secondary' };
    }
};

const navigateToAppointmentDetails = (appointmentId: number) => {
    router.push({ name: 'doctor.appointments.show', params: { id: appointmentId } });
};


onMounted(() => {
    fetchDashboardData();
});
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
            <Card class="border dark:border-gray-700 dark:bg-gray-800">
                <CardHeader class="flex flex-row items-center justify-between">
                    <div>
                        <CardTitle class="text-2xl font-bold text-gray-900 md:text-3xl dark:text-gray-100">Witaj, {{ doctorName }}!</CardTitle>
                        <CardDescription class="text-gray-600 dark:text-gray-400">Oto Twój przegląd dnia.</CardDescription>
                    </div>
                    <div v-if="authStore.user?.avatar" class="flex-shrink-0">
                        <img
                            :src="authStore.user?.avatar"
                            alt="Avatar użytkownika"
                            class="h-16 w-16 rounded-full object-cover border-2 border-nova-accent"
                        />
                    </div>
                    <div v-else class="flex-shrink-0 bg-gray-200 dark:bg-gray-700 h-16 w-16 rounded-full flex items-center justify-center">
                        <Icon name="user" class="h-8 w-8 text-gray-500 dark:text-gray-400" />
                    </div>
                </CardHeader>
            </Card>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <Card class="border dark:border-gray-700 dark:bg-gray-800">
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2 text-xl text-gray-800 dark:text-gray-200">
                            <Icon name="calendar" class="mr-3 h-6 w-6 text-green-500" />
                            Wizyty na dziś
                            <span class="text-green-600">
                                {{
                                    new Date().toLocaleDateString('pl-PL', {
                                        day: '2-digit',
                                        month: 'long',
                                    })
                                }}
                            </span>
                        </CardTitle>
                    </CardHeader>
                    <Separator class="mb-4 dark:bg-gray-700" />
                    <CardContent>
                        <div v-if="loading" class="space-y-4">
                            <Skeleton v-for="i in 3" :key="`today-skel-${i}`" class="h-16 w-full dark:bg-gray-700" />
                        </div>
                        <div v-else-if="error" class="py-4 text-center text-red-500 dark:text-red-400">{{ error }}</div>
                        <div v-else-if="todaysAppointments.length === 0" class="py-8 text-center text-gray-500 dark:text-gray-400">
                            <Icon name="calendar" class="mx-auto mb-2 h-12 w-12 opacity-50" />
                            Brak zaplanowanych wizyt na dziś.
                        </div>
                        <ul v-else class="max-h-[400px] space-y-3 overflow-y-auto pr-2">
                            <li
                                v-for="appt in todaysAppointments"
                                :key="`today-${appt.id}`"
                                class="cursor-pointer rounded-lg border bg-white p-3 transition-shadow hover:shadow-md dark:border-gray-700 dark:bg-gray-700/30 hover:dark:bg-gray-700/60"
                                @click="navigateToAppointmentDetails(appt.id)"
                            >
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="font-semibold text-gray-800 dark:text-gray-100">{{ appt.time }} - {{ appt.patient_name }}</p>
                                        <p
                                            class="text-sm text-gray-600 dark:text-gray-400 cursor-pointer hover:text-nova-accent dark:hover:text-blue-400"
                                            @click.stop="navigateToAppointmentDetails(appt.id)"
                                        >{{ appt.procedure_name }}</p>
                                    </div>
                                    <Badge :variant="getStatusInfo(appt.status).variant" class="text-xs">
                                        {{ getStatusInfo(appt.status).text }}
                                    </Badge>
                                </div>
                            </li>
                        </ul>
                    </CardContent>
                </Card>

                <Card class="border dark:border-gray-700 dark:bg-gray-800">
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2 text-xl text-gray-800 dark:text-gray-200">
                            <Icon name="calendar" class="mr-3 h-6 w-6 text-blue-500" />
                            Wizyty na jutro
                            <span class="text-blue-500">
                                {{
                                    new Date(Date.now() + 86400000).toLocaleDateString('pl-PL', {
                                        day: '2-digit',
                                        month: 'long',
                                    })
                                }}</span
                            >
                        </CardTitle>
                    </CardHeader>
                    <Separator class="mb-4 dark:bg-gray-700" />
                    <CardContent>
                        <div v-if="loading" class="space-y-4">
                            <Skeleton v-for="i in 2" :key="`tomorrow-skel-${i}`" class="h-16 w-full dark:bg-gray-700" />
                        </div>
                        <div v-else-if="error && !todaysAppointments.length" class="py-4 text-center text-red-500 dark:text-red-400">
                            Błąd ładowania.
                        </div>
                        <div v-else-if="tomorrowsAppointments.length === 0" class="py-8 text-center text-gray-500 dark:text-gray-400">
                            <Icon name="calendar" class="mx-auto mb-2 h-12 w-12 opacity-50" />
                            Brak zaplanowanych wizyt na jutro.
                        </div>
                        <ul v-else class="max-h-[400px] space-y-3 overflow-y-auto pr-2">
                            <li
                                v-for="appt in tomorrowsAppointments"
                                :key="`tomorrow-${appt.id}`"
                                class="cursor-pointer rounded-lg border bg-white p-3 transition-transform duration-100 hover:-translate-y-1 hover:shadow-md dark:border-gray-700 dark:bg-gray-700/30 hover:dark:bg-gray-700/60"
                                @click="navigateToAppointmentDetails(appt.id)"
                            >
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="font-semibold text-gray-800 dark:text-gray-100">{{ appt.time }} - {{ appt.patient_name }}</p>
                                        <p class="text-sm text-gray-600 dark:text-gray-400">{{ appt.procedure_name }}</p>
                                    </div>
                                    <Badge :variant="getStatusInfo(appt.status).variant" class="text-xs">
                                        {{ getStatusInfo(appt.status).text }}
                                    </Badge>
                                </div>
                            </li>
                        </ul>
                    </CardContent>
                </Card>
            </div>

            <Separator class="my-6 dark:bg-gray-700" />

            <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-2">
                <Card class="border transition-shadow hover:shadow-lg dark:border-gray-700 dark:bg-gray-800">
                    <CardHeader>
                        <CardTitle class="flex items-center text-lg dark:text-gray-200">
                            <Icon name="calendar-days" class="text-nova-primary dark:text-nova-accent mr-2 h-5 w-5" />
                            Mój Grafik
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p class="mb-3 text-sm text-gray-600 dark:text-gray-400">Zobacz swój pełny kalendarz wizyt i zarządzaj swoją dostępnością.</p>
                        <Button
                            class="bg-nova-primary hover:bg-nova-accent dark:bg-nova-accent dark:hover:bg-nova-primary w-full text-white dark:text-gray-900"
                            @click="router.push({ name: 'doctor.schedule.events' })"
                        >
                            Przejdź do Grafiku
                        </Button>
                    </CardContent>
                </Card>
                <Card class="border transition-shadow hover:shadow-lg dark:border-gray-700 dark:bg-gray-800">
                    <CardHeader>
                        <CardTitle class="flex items-center text-lg dark:text-gray-200">
                            <Icon name="user-circle-2" class="text-nova-primary dark:text-nova-accent mr-2 h-5 w-5" />
                            Mój Profil
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p class="mb-3 text-sm text-gray-600 dark:text-gray-400">Zaktualizuj swoje dane profilowe, bio oraz zdjęcie.</p>
                        <Button
                            class="bg-nova-primary hover:bg-nova-accent dark:bg-nova-accent dark:hover:bg-nova-primary w-full text-white dark:text-gray-900"
                            @click="router.push({ name: 'doctor.profile.show' })"
                        >
                            Edytuj Profil
                        </Button>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
