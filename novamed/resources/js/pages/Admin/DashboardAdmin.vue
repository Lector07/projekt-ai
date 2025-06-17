<script setup lang="ts">
import PlaceholderPattern from '@/components/PlaceholderPattern.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import { Skeleton } from '@/components/ui/skeleton';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import axios from 'axios';
import Chart from 'primevue/chart';
import { computed, onMounted, ref, watch } from 'vue';

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
        cancelled_by_patient: number;
    };
    charts?: {
        appointmentsPerMonth: number[];
        appointmentsPerWeek?: number[];
        appointmentsPerDay?: number[];
        popularProcedures: Array<{
            id: number;
            name: string;
            count: number;
        }>;
    };
}

const loading = ref(true);
const error = ref<string | null>(null);
const dataLoaded = ref(false);
const stats = ref<DashboardStats>({
    users: {
        patientCount: 0,
        doctorCount: 0,
    },
    totalProcedures: 0,
    appointments: {
        total: 0,
        upcoming: 0,
        completed: 0,
        cancelled: 0,
        cancelled_by_patient: 0,
    },
    charts: {
        appointmentsPerMonth: [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
        popularProcedures: [],
    },
});
const selectedTimeRange = ref<TimeRange>('month');

const rawApiResponse = ref<any>(null);

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Statystyki',
    },
];

type TimeRange = 'day' | 'week' | 'month';

const refreshPage = () => {
    const loc = window?.location as any;
    if (loc) loc.reload();
};

const loadChartData = async (range: TimeRange) => {
    try {
        loading.value = true;
        const response = await axios.get(`/api/v1/admin/dashboard?time_range=${range}`);

        if (response.data && typeof response.data === 'object') {
            if (stats.value.charts) {
                if (range === 'month') {
                    stats.value.charts.appointmentsPerMonth = response.data.charts?.appointmentsPerMonth || [];
                } else if (range === 'week') {
                    stats.value.charts.appointmentsPerWeek = response.data.charts?.appointmentsPerWeek || [];
                } else if (range === 'day') {
                    stats.value.charts.appointmentsPerDay = response.data.charts?.appointmentsPerDay || [];
                }
            }
        }
    } catch (err: any) {
        console.error(`Błąd podczas ładowania danych dla okresu ${range}:`, err);
    } finally {
        loading.value = false;
    }
};
watch(selectedTimeRange, (newRange) => {
    if (newRange !== 'month') {
        loadChartData(newRange);
    }
});

watch(
    stats,
    (newValue) => {
        dataLoaded.value = true;
    },
    { deep: true },
);

const appointmentsChartData = computed(() => {
    let labels: string[] = [];
    let data: number[] = [];

    if (selectedTimeRange.value === 'month') {
        labels = [
            'Styczeń',
            'Luty',
            'Marzec',
            'Kwiecień',
            'Maj',
            'Czerwiec',
            'Lipiec',
            'Sierpień',
            'Wrzesień',
            'Październik',
            'Listopad',
            'Grudzień',
        ];
        data = stats.value.charts?.appointmentsPerMonth || [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0];
    } else if (selectedTimeRange.value === 'week') {
        labels = ['Poniedziałek', 'Wtorek', 'Środa', 'Czwartek', 'Piątek', 'Sobota', 'Niedziela'];
        data = stats.value.charts?.appointmentsPerWeek || [0, 0, 0, 0, 0, 0, 0];
    } else if (selectedTimeRange.value === 'day') {
        // Dla dni potrzebujemy ostatnich 7 dni
        const today = new Date();
        labels = Array(7)
            .fill(0)
            .map((_, i) => {
                const d = new Date(today);
                d.setDate(today.getDate() - (6 - i));
                return d.toLocaleDateString('pl-PL', { day: 'numeric', month: 'numeric' });
            });
        data = stats.value.charts?.appointmentsPerDay || [0, 0, 0, 0, 0, 0, 0];
    }

    return {
        labels,
        datasets: [
            {
                label: 'Liczba wizyt',
                data,
                borderColor: '#4361ee',
                backgroundColor: 'rgba(67, 97, 238, 0.2)',
                tension: 0.4,
            },
        ],
    };
});

const appointmentsChartOptions = ref({
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            position: 'bottom',
        },
    },
});

const proceduresChartData = computed(() => {
    const procedures = stats.value.charts?.popularProcedures || [];

    if (!procedures.length) {
        return {
            labels: ['Brak danych'],
            datasets: [
                {
                    data: [1],
                    backgroundColor: ['#e9ecef'],
                },
            ],
        };
    }

    console.log('Dane dla wykresu procedur:', procedures);

    return {
        labels: procedures.map((p) => p.name || ''),
        datasets: [
            {
                data: procedures.map((p) => Number(p.count) || 0),
                backgroundColor: ['#4361ee', '#3f37c9', '#4895ef', '#4cc9f0', '#560bad'],
            },
        ],
    };
});

const proceduresChartOptions = ref({
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            position: 'right',
        },
    },
});

onMounted(async () => {
    try {
        loading.value = true;
        const response = await axios.get('/api/v1/admin/dashboard');
        rawApiResponse.value = response.data;

        if (response.data && typeof response.data === 'object') {
            stats.value = {
                users: {
                    patientCount: Number(response.data.users?.patientCount || 0),
                    doctorCount: Number(response.data.users?.doctorCount || 0),
                },
                totalProcedures: Number(response.data.totalProcedures || 0),
                appointments: {
                    total: Number(response.data.appointments?.total || 0),
                    upcoming: Number(response.data.appointments?.upcoming || 0),
                    completed: Number(response.data.appointments?.completed || 0),
                    cancelled: Number(response.data.appointments?.cancelled || 0),
                    cancelled_by_patient: Number(response.data.appointments?.cancelled_by_patient || 0),
                },
                charts: {
                    appointmentsPerMonth: Array.isArray(response.data.charts?.appointmentsPerMonth)
                        ? response.data.charts.appointmentsPerMonth.map((v: number) => Number(v) || 0)
                        : [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
                    popularProcedures: Array.isArray(response.data.charts?.popularProcedures)
                        ? response.data.charts.popularProcedures.map((p: { id?: number; name?: string; count?: number }) => ({
                              id: Number(p.id || 0),
                              name: String(p.name || ''),
                              count: Number(p.count || 0),
                          }))
                        : [],
                },
            };
        } else {
            error.value = 'Serwer zwrócił nieprawidłowe dane.';
        }
    } catch (err: any) {
        console.error('Błąd podczas pobierania danych:', err);

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
                <Label class="text-nova-darkest dark:text-nova-light text-3xl font-bold">Panel Administratora</Label>
            </div>

            <div v-if="loading" class="flex h-full w-full flex-col gap-8">
                <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
                    <div
                        v-for="i in 3"
                        :key="i"
                        class="overflow-hidden rounded-xl border border-gray-200 bg-white p-4 shadow-md transition-all duration-200 dark:border-gray-700 dark:bg-gray-800"
                    >
                        <Skeleton class="mb-2 h-6 w-3/4" />
                        <Skeleton class="h-8 w-1/2" />
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5">
                    <div
                        v-for="i in 5"
                        :key="i"
                        class="overflow-hidden rounded-xl border border-gray-200 bg-white p-4 shadow-md transition-all duration-200 dark:border-gray-700 dark:bg-gray-800"
                    >
                        <Skeleton class="mb-2 h-6 w-3/4" />
                        <Skeleton class="h-8 w-1/2" />
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-8 lg:grid-cols-2">
                    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-md dark:border-gray-700 dark:bg-gray-800">
                        <div class="p-4">
                            <Skeleton class="mb-4 h-6 w-2/5" />
                            <div class="relative h-[320px]">
                                <PlaceholderPattern />
                            </div>
                        </div>
                    </div>
                    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-md dark:border-gray-700 dark:bg-gray-800">
                        <div class="p-4">
                            <Skeleton class="mb-4 h-6 w-2/5" />
                            <div class="relative h-[320px]">
                                <PlaceholderPattern />
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div v-else-if="error" class="rounded-xl border border-red-200 bg-red-50 p-6 text-center dark:border-red-800 dark:bg-red-900/20">
                <p class="font-medium text-red-500 dark:text-red-400">{{ error }}</p>
                <Button class="mt-4" variant="outline" @click="refreshPage"> Odśwież stronę </Button>
            </div>

            <div v-else class="flex h-full w-full flex-col gap-8">
                <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
                    <div class="rounded-xl border border-gray-200 bg-white shadow-md hover:shadow-lg dark:border-gray-700 dark:bg-gray-900">
                        <div class="p-4">
                            <h3 class="mb-1 text-lg font-medium">Pacjenci</h3>
                            <p class="text-nova-primary dark:text-nova-accent text-2xl font-bold">
                                {{ stats.users.patientCount }}
                            </p>
                        </div>
                    </div>

                    <div class="rounded-xl border border-gray-200 bg-white shadow-md hover:shadow-lg dark:border-gray-700 dark:bg-gray-900">
                        <div class="p-4">
                            <h3 class="mb-1 text-lg font-medium">Lekarze</h3>
                            <p class="text-nova-primary dark:text-nova-accent text-2xl font-bold">
                                {{ stats.users.doctorCount }}
                            </p>
                        </div>
                    </div>

                    <div class="rounded-xl border border-gray-200 bg-white shadow-md hover:shadow-lg dark:border-gray-700 dark:bg-gray-900">
                        <div class="p-4">
                            <h3 class="mb-1 text-lg font-medium">Procedury medyczne</h3>
                            <p class="text-nova-primary dark:text-nova-accent text-2xl font-bold">
                                {{ stats.totalProcedures }}
                            </p>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5">
                    <div
                        class="rounded-xl border border-gray-200 bg-white shadow-md transition-all duration-200 hover:shadow-lg dark:border-gray-700 dark:bg-gray-900"
                    >
                        <div class="p-4">
                            <h3 class="mb-1 text-lg font-medium">Wszystkie wizyty</h3>
                            <p class="text-nova-primary dark:text-nova-accent text-2xl font-bold">
                                {{ stats.appointments.total }}
                            </p>
                        </div>
                    </div>

                    <div
                        class="rounded-xl border border-gray-200 bg-white shadow-md transition-all duration-200 hover:shadow-lg dark:border-gray-700 dark:bg-gray-900"
                    >
                        <div class="p-4">
                            <h3 class="mb-1 text-lg font-medium">Nadchodzące wizyty</h3>
                            <p class="text-nova-accent text-2xl font-bold">{{ stats.appointments.upcoming }}</p>
                        </div>
                    </div>

                    <div
                        class="rounded-xl border border-gray-200 bg-white shadow-md transition-all duration-200 hover:shadow-lg dark:border-gray-700 dark:bg-gray-900"
                    >
                        <div class="p-4">
                            <h3 class="mb-1 text-lg font-medium">Zakończone wizyty</h3>
                            <p class="text-2xl font-bold text-green-500">{{ stats.appointments.completed }}</p>
                        </div>
                    </div>

                    <div
                        class="rounded-xl border border-gray-200 bg-white shadow-md transition-all duration-200 hover:shadow-lg dark:border-gray-700 dark:bg-gray-900"
                    >
                        <div class="p-4">
                            <h3 class="mb-1 text-lg font-medium">Anulowane wizyty</h3>
                            <p class="text-2xl font-bold text-red-500">{{ stats.appointments.cancelled }}</p>
                        </div>
                    </div>
                    <div
                        class="rounded-xl border border-gray-200 bg-white shadow-md transition-all duration-200 hover:shadow-lg dark:border-gray-700 dark:bg-gray-900"
                    >
                        <div class="p-4">
                            <h3 class="mb-1 text-lg font-medium">Anulowane przez pacjenta</h3>
                            <p class="text-2xl font-bold text-orange-500">{{ stats.appointments.cancelled_by_patient }}</p>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-8 lg:grid-cols-2">
                    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-md dark:border-gray-700 dark:bg-gray-900">
                        <div class="p-4">
                            <div class="mb-2 flex items-center justify-between">
                                <h3 class="text-lg font-medium">
                                    {{
                                        selectedTimeRange === 'day'
                                            ? 'Wizyty z ostatnich 7 dni'
                                            : selectedTimeRange === 'week'
                                              ? 'Wizyty w ciągu tygodnia'
                                              : 'Wizyty w ciągu roku'
                                    }}
                                </h3>
                                <div class="flex space-x-2">
                                    <Button
                                        size="sm"
                                        :variant="selectedTimeRange === 'day' ? 'default' : 'outline'"
                                        :class="selectedTimeRange === 'day' ? 'bg-nova-primary hover:bg-nova-accent' : ''"
                                        @click="selectedTimeRange = 'day'"
                                    >
                                        Dzień
                                    </Button>
                                    <Button
                                        size="sm"
                                        :variant="selectedTimeRange === 'week' ? 'default' : 'outline'"
                                        :class="selectedTimeRange === 'week' ? 'bg-nova-primary hover:bg-nova-accent' : ''"
                                        @click="selectedTimeRange = 'week'"
                                    >
                                        Tydzień
                                    </Button>
                                    <Button
                                        size="sm"
                                        :variant="selectedTimeRange === 'month' ? 'default' : 'outline'"
                                        :class="selectedTimeRange === 'month' ? 'bg-nova-primary hover:bg-nova-accent' : ''"
                                        @click="selectedTimeRange = 'month'"
                                    >
                                        Miesiąc
                                    </Button>
                                </div>
                            </div>
                            <Separator class="mb-2" />
                            <div style="height: 320px">
                                <Chart type="line" :data="appointmentsChartData" :options="appointmentsChartOptions" />
                            </div>
                        </div>
                    </div>

                    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-md dark:border-gray-700 dark:bg-gray-900">
                        <div class="p-4">
                            <h3 class="mb-2 text-lg font-medium">Popularne procedury</h3>
                            <Separator class="mb-2" />
                            <div style="height: 320px">
                                <Chart type="pie" :data="proceduresChartData" :options="proceduresChartOptions" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
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
