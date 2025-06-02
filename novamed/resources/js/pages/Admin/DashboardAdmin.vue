<script setup lang="ts">
import {ref, onMounted, computed, nextTick, watch} from 'vue';
import AppLayout from "@/layouts/AppLayout.vue";
import type {BreadcrumbItem} from "@/types";
import Card from 'primevue/card';
import {Skeleton} from "@/components/ui/skeleton";
import {Button} from '@/components/ui/button';
import PlaceholderPattern from '@/components/PlaceholderPattern.vue';
import Chart from 'primevue/chart';
import axios from 'axios';
import {Label} from "@/components/ui/label";
import {useAuthStore} from '@/stores/auth';
import {Separator} from "@/components/ui/separator";

// Interfejs dla danych dashboardu
interface DashboardStats {
    users: {
        patientCount: number;
        doctorCount: number;
    };
    totalProcedures: number;
    appointments: {
        total: number;
        upcoming: number;
        completed: number;
        cancelled: number;
    };
    charts?: {
        appointmentsPerMonth: number[];
        popularProcedures: Array<{
            id: number;
            name: string;
            count: number;
        }>;
    };
}

// Zmienne reaktywne
const loading = ref(true);
const error = ref<string | null>(null);
const dataLoaded = ref(false);
const stats = ref<DashboardStats>({
    users: {
        patientCount: 0,
        doctorCount: 0
    },
    totalProcedures: 0,
    appointments: {
        total: 0,
        upcoming: 0,
        completed: 0,
        cancelled: 0
    },
    charts: {
        appointmentsPerMonth: [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
        popularProcedures: []
    }
});

// Dla debugowania
const rawApiResponse = ref<any>(null);

const authStore = useAuthStore();
const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Statystyki'
    },
];

// Funkcja do odświeżania strony (zamiast window.location.reload())
const refreshPage = () => {
    const loc = window?.location as any;
    if (loc) loc.reload();
};

// Obserwacja zmian w danych statystyk
watch(stats, (newValue) => {
    console.log("Stats zostały zaktualizowane:", newValue);
    dataLoaded.value = true;
}, {deep: true});

// Dane dla wykresu wizyt
const appointmentsChartData = computed(() => {
    const monthData = stats.value.charts?.appointmentsPerMonth ||
        [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0];

    // Upewniamy się, że mamy tablicę
    const dataArray = Array.isArray(monthData) ?
        monthData.map(v => Number(v) || 0) :
        [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0];

    console.log("Dane dla wykresu wizyt:", dataArray);

    return {
        labels: ['Styczeń', 'Luty', 'Marzec', 'Kwiecień', 'Maj', 'Czerwiec',
            'Lipiec', 'Sierpień', 'Wrzesień', 'Październik', 'Listopad', 'Grudzień'],
        datasets: [
            {
                label: 'Liczba wizyt',
                data: dataArray,
                borderColor: '#4361ee',
                backgroundColor: 'rgba(67, 97, 238, 0.2)',
                tension: 0.4
            }
        ]
    };
});

const appointmentsChartOptions = ref({
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            position: 'bottom'
        }
    }
});

// Dane dla wykresu procedur
const proceduresChartData = computed(() => {
    const procedures = stats.value.charts?.popularProcedures || [];

    if (!procedures.length) {
        return {
            labels: ['Brak danych'],
            datasets: [{
                data: [1],
                backgroundColor: ['#e9ecef']
            }]
        };
    }

    console.log("Dane dla wykresu procedur:", procedures);

    return {
        labels: procedures.map(p => p.name || ''),
        datasets: [{
            data: procedures.map(p => Number(p.count) || 0),
            backgroundColor: ['#4361ee', '#3f37c9', '#4895ef', '#4cc9f0', '#560bad']
        }]
    };
});

const proceduresChartOptions = ref({
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            position: 'right'
        }
    }
});

onMounted(async () => {
    try {
        loading.value = true;
        const response = await axios.get('/api/v1/admin/dashboard');

        if (response.data && typeof response.data === 'object') {
            stats.value = {
                users: {
                    patientCount: Number(response.data.users?.patientCount || 0),
                    doctorCount: Number(response.data.users?.doctorCount || 0)
                },
                totalProcedures: Number(response.data.totalProcedures || 0),
                appointments: {
                    total: Number(response.data.appointments?.total || 0),
                    upcoming: Number(response.data.appointments?.upcoming || 0),
                    completed: Number(response.data.appointments?.completed || 0),
                    cancelled: Number(response.data.appointments?.cancelled || 0)
                },
                charts: {
                    appointmentsPerMonth: Array.isArray(response.data.charts?.appointmentsPerMonth)
                        ? response.data.charts.appointmentsPerMonth.map((v: number) => Number(v) || 0)
                        : [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0],

// Poprawka dla mapowania popularnych procedur
                    popularProcedures: Array.isArray(response.data.charts?.popularProcedures)
                        ? response.data.charts.popularProcedures.map((p: {
                            id?: number,
                            name?: string,
                            count?: number
                        }) => ({
                            id: Number(p.id || 0),
                            name: String(p.name || ''),
                            count: Number(p.count || 0)
                        }))
                        : []
                }
            };
        } else {
            error.value = 'Serwer zwrócił nieprawidłowe dane.';
        }
    } catch (err: any) {
        console.error("Błąd podczas pobierania danych:", err);

        if (err.response?.status === 403) {
            error.value = 'Brak uprawnień do wyświetlenia panelu administratora.';
        } else if (err.response?.status === 401) {
            error.value = 'Sesja wygasła. Zaloguj się ponownie.';
        } else {
            error.value = `Wystąpił błąd: ${err.message || 'Nieznany błąd'}`;
        }
    } finally {
        loading.value = false;
    }
});
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-6">
            <div class="flex items-center">
                <Label class="text-3xl font-bold text-nova-darkest dark:text-nova-light">Panel Administratora</Label>
            </div>

            <div v-if="loading" class="flex flex-col h-full w-full gap-8">
                <!-- Szkielety ładowania dla kart statystyk użytkowników (3 karty) -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div v-for="i in 3" :key="i"
                         class="border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden shadow-md p-4 bg-white dark:bg-gray-800 transition-all duration-200">
                        <Skeleton class="h-6 w-3/4 mb-2"/>
                        <Skeleton class="h-8 w-1/2"/>
                    </div>
                </div>

                <!-- Szkielety ładowania dla kart statystyk wizyt (4 karty) -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <div v-for="i in 4" :key="i"
                         class="border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden shadow-md p-4 bg-white dark:bg-gray-800 transition-all duration-200">
                        <Skeleton class="h-6 w-3/4 mb-2"/>
                        <Skeleton class="h-8 w-1/2"/>
                    </div>
                </div>

                <!-- Szkielety dla wykresów -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    <div
                        class="border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden shadow-md bg-white dark:bg-gray-800">
                        <div class="p-4">
                            <Skeleton class="h-6 w-2/5 mb-4"/>
                            <div class="h-[320px] relative">
                                <PlaceholderPattern/>
                            </div>
                        </div>
                    </div>
                    <div
                        class="border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden shadow-md bg-white dark:bg-gray-800">
                        <div class="p-4">
                            <Skeleton class="h-6 w-2/5 mb-4"/>
                            <div class="h-[320px] relative">
                                <PlaceholderPattern/>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div v-else-if="error"
                 class="p-6 text-center rounded-xl bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800">
                <p class="text-red-500 dark:text-red-400 font-medium">{{ error }}</p>
                <Button class="mt-4" variant="outline" @click="refreshPage">
                    Odśwież stronę
                </Button>
            </div>

            <div v-else class="flex flex-col h-full w-full gap-8">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div
                        class="border border-gray-200 dark:border-gray-700 shadow-md rounded-xl hover:shadow-lg bg-white dark:bg-gray-900">
                        <div class="p-4">
                            <h3 class="text-lg font-medium mb-1">Pacjenci</h3>
                            <p class="text-2xl font-bold text-nova-primary dark:text-nova-accent">
                                {{ stats.users.patientCount }}</p>
                        </div>
                    </div>

                    <div
                        class="border border-gray-200 dark:border-gray-700 shadow-md rounded-xl hover:shadow-lg bg-white dark:bg-gray-900">
                        <div class="p-4">
                            <h3 class="text-lg font-medium mb-1">Lekarze</h3>
                            <p class="text-2xl font-bold text-nova-primary dark:text-nova-accent">
                                {{ stats.users.doctorCount }}</p>
                        </div>
                    </div>

                    <div
                        class="border border-gray-200 dark:border-gray-700 shadow-md rounded-xl hover:shadow-lg bg-white dark:bg-gray-900">
                        <div class="p-4">
                            <h3 class="text-lg font-medium mb-1">Procedury medyczne</h3>
                            <p class="text-2xl font-bold text-nova-primary dark:text-nova-accent">
                                {{ stats.totalProcedures }}</p>
                        </div>
                    </div>
                </div>

                <!-- Karty statystyk wizyt -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <div
                        class="border border-gray-200 dark:border-gray-700 shadow-md rounded-xl hover:shadow-lg transition-all duration-200 bg-white dark:bg-gray-900">
                        <div class="p-4">
                            <h3 class="text-lg font-medium mb-1">Wszystkie wizyty</h3>
                            <p class="text-2xl font-bold text-nova-primary dark:text-nova-accent">
                                {{ stats.appointments.total }}</p>
                        </div>
                    </div>

                    <div
                        class="border border-gray-200 dark:border-gray-700 shadow-md rounded-xl hover:shadow-lg transition-all duration-200 bg-white dark:bg-gray-900">
                        <div class="p-4">
                            <h3 class="text-lg font-medium mb-1">Nadchodzące wizyty</h3>
                            <p class="text-2xl font-bold text-nova-accent">{{ stats.appointments.upcoming }}</p>
                        </div>
                    </div>

                    <div
                        class="border border-gray-200 dark:border-gray-700 shadow-md rounded-xl hover:shadow-lg transition-all duration-200 bg-white dark:bg-gray-900">
                        <div class="p-4">
                            <h3 class="text-lg font-medium mb-1">Zakończone wizyty</h3>
                            <p class="text-2xl font-bold text-green-500">{{ stats.appointments.completed }}</p>
                        </div>
                    </div>

                    <div
                        class="border border-gray-200 dark:border-gray-700 shadow-md rounded-xl hover:shadow-lg transition-all duration-200 bg-white dark:bg-gray-900">
                        <div class="p-4">
                            <h3 class="text-lg font-medium mb-1">Anulowane wizyty</h3>
                            <p class="text-2xl font-bold text-red-500">{{ stats.appointments.cancelled }}</p>
                        </div>
                    </div>
                </div>

                <!-- Wykresy -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    <!-- Wykres wizyt miesięcznie -->
                    <div
                        class="border border-gray-200 dark:border-gray-700 shadow-md rounded-xl overflow-hidden bg-white dark:bg-gray-900">
                        <div class="p-4">
                            <h3 class="text-lg font-medium mb-2">Wizyty w ciągu roku</h3>
                            <Separator class="mb-2"/>
                            <div style="height: 320px;">
                                <Chart type="line" :data="appointmentsChartData" :options="appointmentsChartOptions"/>
                            </div>
                        </div>
                    </div>

                    <!-- Wykres popularnych procedur -->
                    <div
                        class="border border-gray-200 dark:border-gray-700 shadow-md rounded-xl overflow-hidden bg-white dark:bg-gray-900">
                        <div class="p-4">
                            <h3 class="text-lg font-medium mb-2">Popularne procedury</h3>
                            <Separator class="mb-2"/>
                            <div style="height: 320px">
                                <Chart type="pie" :data="proceduresChartData" :options="proceduresChartOptions"/>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
/* Dostosowanie komponentów PrimeVue */
:deep(.p-component) {
    font-family: inherit;
}

:deep(.p-card) {
    border: 1px;
    border-radius: 0.5rem;
    overflow: hidden;
}

:deep(.p-card .p-card-content) {
    padding: 1rem;
}

:deep(.p-chart) {
    width: 100%;
    height: 100%;
}
</style>


