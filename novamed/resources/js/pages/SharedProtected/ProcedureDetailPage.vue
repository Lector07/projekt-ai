<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import AppLayout from '@/layouts/AppLayout.vue';
import axios from 'axios';
import { type BreadcrumbItem } from '@/types';
import { Button } from '@/components/ui/button';
import { Skeleton } from '@/components/ui/skeleton';
import { Separator } from '@/components/ui/separator';
import { Badge } from '@/components/ui/badge';
import { Stepper, StepperDescription, StepperItem, StepperSeparator, StepperTitle } from '@/components/ui/stepper';
import { Circle, Dot, CalendarPlus, AlertCircle, FileQuestion } from 'lucide-vue-next'; // Zaimportuj AlertCircle i FileQuestion, jeśli Icon.vue ich nie obsługuje
import Icon from '@/components/Icon.vue'; // Jeśli używasz generycznego komponentu Icon

interface Doctor {
    id: number;
    name: string;
    specialization: string;
    photo_url?: string;
}

interface ProcedureCategory {
    id: number;
    name: string;
    slug: string;
}

interface TimelineStep {
    step: number;
    title: string;
    description: string;
}

interface Procedure {
    id: number;
    name: string;
    description: string;
    procedure_category_id?: number;
    category?: ProcedureCategory;
    base_price: number;
    recovery_info?: string;
    recovery_timeline_info?: string | TimelineStep[] | any[]; // Dodano any[] dla elastyczności, jeśli item ma inne pola
    doctors?: Doctor[];
}

const route = useRoute();
const router = useRouter(); // Zdefiniuj router
const procedureId = route.params.id;
const procedure = ref<Procedure | null>(null);
const loading = ref(true);
const error = ref<string | null>(null);

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Zabiegi',
        href: '/procedures',
    },
    {
        title: 'Szczegóły zabiegu',
    }
];

const categoryName = computed(() => {
    if (!procedure.value) return '';
    if (procedure.value.category && procedure.value.category.name) {
        return procedure.value.category.name;
    }
    return 'Brak kategorii';
});

const timelineSteps = computed((): TimelineStep[] => {
    if (!procedure.value || !procedure.value.recovery_timeline_info) return [];

    if (typeof procedure.value.recovery_timeline_info === 'string') {
        try {
            const parsed = JSON.parse(procedure.value.recovery_timeline_info);
            if (Array.isArray(parsed)) { // Upewnij się, że wynik parsowania to tablica
                return parsed.map((item: any, index: number) => ({ // Użyj any dla item, jeśli struktura jest niepewna
                    step: item.step || index + 1,
                    title: item.title || `Etap ${index + 1}`,
                    description: item.description || ''
                }));
            }
        } catch {
            const lines = procedure.value.recovery_timeline_info.split('\n').filter(line => line.trim());
            return lines.map((line, index) => {
                const [title, ...descParts] = line.split(':');
                const description = descParts.join(':').trim();
                return {
                    step: index + 1,
                    title: title ? title.trim() : `Etap ${index + 1}`,
                    description: description
                };
            });
        }
    } else if (Array.isArray(procedure.value.recovery_timeline_info)) {
        return procedure.value.recovery_timeline_info.map((item: any, index: number) => ({ // Użyj any dla item
            step: item.step || index + 1,
            title: item.title || `Etap ${index + 1}`,
            description: item.description || ''
        }));
    }
    return [];
});

const fetchProcedure = async () => {
    try {
        loading.value = true;
        error.value = null;
        const response = await axios.get(`/api/v1/procedures/${procedureId}`);
        if (response.data && response.data.data && typeof response.data.data === 'object') {
            procedure.value = response.data.data;
        } else if (response.data && typeof response.data === 'object' && !response.data.data) {
            // Jeśli API zwraca obiekt procedury bezpośrednio (bez opakowania 'data')
            procedure.value = response.data as Procedure;
        }
        else {
            console.warn('Nieoczekiwana struktura danych zabiegu z API lub brak danych:', response.data);
            procedure.value = null;
            if (response.status !== 404) {
                error.value = 'Otrzymano niekompletne dane zabiegu.';
            }
        }
    } catch (err: any) {
        console.error('Błąd podczas pobierania szczegółów zabiegu:', err);
        if (err.response?.status === 404) {
            error.value = 'Nie znaleziono zabiegu o podanym ID.';
        } else {
            error.value = err.response?.data?.message || 'Nie udało się pobrać szczegółów zabiegu.';
        }
        procedure.value = null;
    } finally {
        loading.value = false;
    }
};

onMounted(() => {
    if (procedureId) {
        fetchProcedure();
    } else {
        error.value = 'Brak ID zabiegu do załadowania.';
        loading.value = false;
    }
});
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-6 md:p-8">
            <div v-if="loading" class="space-y-6">
                <Skeleton class="h-10 w-3/4 dark:bg-gray-700" />
                <Skeleton class="h-6 w-1/4 dark:bg-gray-700" />
                <hr class="dark:border-gray-700 my-4"/>
                <Skeleton class="h-8 w-1/5 dark:bg-gray-700" />
                <Skeleton class="h-4 w-full dark:bg-gray-700" />
                <Skeleton class="h-4 w-full dark:bg-gray-700" />
                <Skeleton class="h-4 w-2/3 dark:bg-gray-700" />
                <hr class="dark:border-gray-700 my-4"/>
                <Skeleton class="h-10 w-1/4 dark:bg-gray-700" />
            </div>

            <div v-else-if="error" class="p-6 text-center text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/20 rounded-lg">
                <Icon name="alert-circle" class="mx-auto h-12 w-12 text-red-500" />
                <p class="mt-2 text-xl font-semibold">{{ error }}</p>
                <Button @click="fetchProcedure" variant="outline" class="mt-6">Spróbuj ponownie</Button>
            </div>

            <div v-else-if="procedure" class="bg-white dark:bg-gray-800 rounded-lg border dark:border-gray-700 shadow-lg p-6 md:p-8 space-y-6">
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                    <h1 class="text-3xl md:text-4xl font-bold text-gray-900 dark:text-gray-100">{{ procedure.name }}</h1>
                    <Badge variant="secondary" class="text-sm px-3 py-1.5 dark:bg-gray-700 dark:text-gray-300">
                        {{ categoryName }}
                    </Badge>
                </div>

                <Separator class="dark:bg-gray-700" />

                <div>
                    <h2 class="text-xl font-semibold mb-2 text-gray-800 dark:text-gray-200">Opis</h2>
                    <p class="whitespace-pre-line text-gray-700 dark:text-gray-300 leading-relaxed">
                        {{ procedure.description || 'Brak szczegółowego opisu dla tego zabiegu.' }}
                    </p>
                </div>

                <div v-if="procedure.recovery_info" >
                    <h2 class="text-xl font-semibold mb-2 text-gray-800 dark:text-gray-200">Informacje o Rekonwalescencji</h2>
                    <p class="whitespace-pre-line text-gray-700 dark:text-gray-300 leading-relaxed">
                        {{ procedure.recovery_info }}
                    </p>
                </div>

                <div v-if="timelineSteps && timelineSteps.length > 0">
                    <h2 class="text-xl font-semibold mb-4 text-gray-800 dark:text-gray-200">Harmonogram Rekonwalescencji</h2>
                    <Stepper orientation="vertical" class="w-full">
                        <StepperItem
                            v-for="(step, index) in timelineSteps"
                            :key="step.step"
                            class="relative flex w-full items-start gap-x-4 pb-4"
                            :step="step.step"
                            :state="index === 0 ? 'active' : 'inactive'"
                        >
                            <div class="flex flex-col items-center h-full">
                                <Button
                                    :variant="index === 0 ? 'default' : 'outline'"
                                    size="icon"
                                    class="z-10 rounded-full shrink-0 w-9 h-9"
                                    :class="[index === 0 && 'bg-nova-primary text-white ring-2 ring-nova-primary ring-offset-2 dark:ring-offset-gray-800']"
                                >
                                    <Circle v-if="index === 0" class="h-5 w-5"/>
                                    <Dot v-else class="h-6 w-6 text-gray-400 dark:text-gray-500"/>
                                </Button>
                                <StepperSeparator
                                    v-if="index < timelineSteps.length - 1"
                                    class="mt-1.5 block flex-grow w-0.5 rounded-full bg-gray-200 dark:bg-gray-700 group-data-[state=completed]:bg-nova-primary"
                                />
                            </div>
                            <div class="pt-1.5">
                                <StepperTitle
                                    :class="[index === 0 ? 'text-nova-primary dark:text-nova-accent' : 'text-gray-700 dark:text-gray-300']"
                                    class="text-md font-semibold transition"
                                >
                                    {{ step.title }}
                                </StepperTitle>
                                <StepperDescription
                                    :class="[index === 0 ? 'text-gray-600 dark:text-gray-400' : 'text-gray-500 dark:text-gray-500']"
                                    class="text-sm transition mt-0.5"
                                >
                                    {{ step.description }}
                                </StepperDescription>
                            </div>
                        </StepperItem>
                    </Stepper>
                </div>

                <div v-if="procedure.doctors && procedure.doctors.length > 0" >
                    <h2 class="text-xl font-semibold mb-3 text-gray-800 dark:text-gray-200">Lekarze Wykonujący Zabieg</h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                        <div v-for="doctor in procedure.doctors" :key="doctor.id" class="rounded-lg border dark:border-gray-700 p-4 flex items-center gap-3 bg-gray-50 dark:bg-gray-700/30 hover:shadow-md transition-shadow">
                            <div class="w-12 h-12 rounded-full bg-gray-300 dark:bg-gray-600 overflow-hidden shrink-0">
                                <img v-if="doctor.photo_url" :src="doctor.photo_url" :alt="`Dr. ${doctor.name}`" class="w-full h-full object-cover" />
                                <div v-else class="w-full h-full flex items-center justify-center bg-nova-accent text-white text-xl font-bold">
                                    {{ doctor.name ? doctor.name.charAt(0) : 'L' }}
                                </div>
                            </div>
                            <div>
                                <p class="font-semibold text-gray-900 dark:text-gray-100">{{ doctor.name }}</p>
                                <p class="text-sm text-gray-500 dark:text-gray-400">{{ doctor.specialization }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <Separator class="dark:bg-gray-700" />

                <div class="mt-6 flex flex-col sm:flex-row items-center justify-between gap-4">
                    <div class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                        Cena: {{ procedure.base_price }} zł
                    </div>
                    <Button
                        @click="router.push({ name: 'book.appointment', query: { procedure_id: procedure.id } })"
                        size="lg"
                        class="w-full sm:w-auto bg-nova-primary hover:bg-nova-accent dark:bg-nova-accent dark:text-gray-900 dark:hover:bg-nova-primary"
                    >
                        <CalendarPlus class="mr-2 h-5 w-5" />
                        Umów wizytę
                    </Button>
                </div>
            </div>
            <div v-else class="text-center py-10">
                <Icon name="file-question" class="mx-auto h-16 w-16 text-gray-400 dark:text-gray-500" />
                <p class="mt-4 text-xl text-gray-500 dark:text-gray-400">Nie znaleziono informacji o tym zabiegu.</p>
                <Button @click="router.back()" variant="outline" class="mt-6">Powrót</Button>
            </div>
        </div>
    </AppLayout>
</template>
