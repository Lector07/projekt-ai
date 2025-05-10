<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import { useRoute } from 'vue-router';
import AppLayout from '@/layouts/AppLayout.vue';
import axios from 'axios';
import { type BreadcrumbItem } from '@/types';
import { Button } from '@/components/ui/button';
import { Skeleton } from '@/components/ui/skeleton';
import { Separator } from '@/components/ui/separator';
import { Stepper, StepperDescription, StepperItem, StepperSeparator, StepperTitle, StepperTrigger } from '@/components/ui/stepper';
import { Check, Circle, Dot } from 'lucide-vue-next';

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
    recovery_timeline_info?: string | TimelineStep[];
    doctors?: Doctor[];
}

const route = useRoute();
const procedureId = route.params.id;
const procedure = ref<Procedure | null>(null);
const loading = ref(true);
const error = ref<string | null>(null);

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Zabiegi',
        href: '/procedures',  // zmieniono z href na to
    },
    {
        title: 'Szczegóły zabiegu',
        href: `/procedures/${procedureId}`,  // zmieniono z href na to
    }
];

const categoryName = computed(() => {
    if (!procedure.value) return '';

    if (procedure.value.category && procedure.value.category.name) {
        return procedure.value.category.name;
    }

    return 'Brak kategorii';
});

// Przekształcenie recovery_timeline_info na format odpowiedni dla steppera
const timelineSteps = computed((): TimelineStep[] => {
    if (!procedure.value || !procedure.value.recovery_timeline_info) return [];

    if (typeof procedure.value.recovery_timeline_info === 'string') {
        // Jeśli dane są w formacie tekstowym, próbujemy je sparsować
        try {
            // Próbujemy sprawdzić czy to JSON
            return JSON.parse(procedure.value.recovery_timeline_info);
        } catch {
            // Jeśli to nie JSON, zakładamy format tekstowy z liniami "Etap: Opis"
            const lines = procedure.value.recovery_timeline_info.split('\n').filter(line => line.trim());
            return lines.map((line, index) => {
                const [title, ...descParts] = line.split(':');
                const description = descParts.join(':').trim();
                return {
                    step: index + 1,
                    title: title.trim(),
                    description: description
                };
            });
        }
    } else if (Array.isArray(procedure.value.recovery_timeline_info)) {
        // Jeśli to już tablica obiektów
        return procedure.value.recovery_timeline_info.map((item, index) => ({
            step: index + 1,
            title: item.title || item.phase || `Etap ${index + 1}`,
            description: item.description || ''
        }));
    }

    return [];
});

const fetchProcedure = async () => {
    try {
        loading.value = true;
        error.value = null;

        const response = await axios.get(`/api/procedures/${procedureId}`);
        procedure.value = response.data;
    } catch (err) {
        console.error('Błąd podczas pobierania szczegółów zabiegu:', err);
        error.value = 'Nie udało się pobrać szczegółów zabiegu. Sprawdź konsolę po więcej szczegółów.';
    } finally {
        loading.value = false;
    }
};

onMounted(() => {
    fetchProcedure();
});
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <div v-if="loading">
                <Skeleton class="h-[70vh] rounded-md border border-sidebar-border/70 dark:border-sidebar-border" />
            </div>

            <div v-else-if="error" class="p-4 text-center text-red-500">
                {{ error }}
            </div>

            <div v-else-if="procedure" class="rounded-lg border border-sidebar-border/70 dark:border-sidebar-border p-6">
                <div class="mb-4 flex items-center justify-between">
                    <h1 class="text-3xl font-bold">{{ procedure.name }}</h1>
                    <span class="rounded-full bg-nova-accent dark:bg-nova-primary text-nova-light px-3 py-1 text-sm font-medium">
                        {{ categoryName }}
                    </span>
                </div>

                <Separator class="my-4" />

                <!-- Opis procedury -->
                <div class="mb-6">
                    <h2 class="text-xl font-semibold mb-2">Opis</h2>
                    <p class="whitespace-pre-line text-gray-700 dark:text-gray-300">
                        {{ procedure.description }}
                    </p>
                </div>

                <div v-if="procedure.recovery_info" class="mb-6">
                    <h2 class="text-xl font-semibold mb-2">Rekonwalescencja</h2>
                    <p class="whitespace-pre-line text-gray-700 dark:text-gray-300">
                        {{ procedure.recovery_info }}
                    </p>
                </div>

                <!-- Harmonogram rekonwalescencji jako stepper -->
                <div v-if="timelineSteps.length > 0" class="mb-6">
                    <h2 class="text-xl font-semibold mb-4">Harmonogram rekonwalescencji</h2>

                    <Stepper orientation="vertical" class="mx-auto flex w-full max-w-full flex-col justify-start gap-6">
                        <StepperItem
                            v-for="step in timelineSteps"
                            :key="step.step"
                            v-slot="{ state }"
                            class="relative flex w-full items-start gap-6"
                            :step="step.step"
                            :state="step.step === 1 ? 'active' : 'inactive'"
                        >
                            <StepperSeparator
                                v-if="step.step !== timelineSteps[timelineSteps.length - 1].step"
                                class="absolute left-[18px] top-[38px] block h-[105%] w-0.5 shrink-0 rounded-full bg-muted group-data-[state=completed]:bg-primary"
                            />

                            <StepperTrigger as-child>
                                <Button
                                    :variant="step.step === 1 ? 'default' : 'outline'"
                                    size="icon"
                                    class="z-10 rounded-full shrink-0"
                                    :class="[step.step === 1 && 'ring-2 ring-ring ring-offset-2 ring-offset-background']"
                                >
                                    <Circle v-if="step.step === 1" />
                                    <Dot v-else />
                                </Button>
                            </StepperTrigger>

                            <div class="flex flex-col gap-1">
                                <StepperTitle
                                    :class="[step.step === 1 && 'text-primary']"
                                    class="text-sm font-semibold transition lg:text-base"
                                >
                                    {{ step.title }}
                                </StepperTitle>
                                <StepperDescription
                                    :class="[step.step === 1 && 'text-primary']"
                                    class="text-sm text-muted-foreground transition lg:text-base"
                                >
                                    {{ step.description }}
                                </StepperDescription>
                            </div>
                        </StepperItem>
                    </Stepper>
                </div>

                <div v-if="procedure.doctors && procedure.doctors.length > 0" class="mb-6">
                    <h2 class="text-xl font-semibold mb-3">Lekarze wykonujący zabieg</h2>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div v-for="doctor in procedure.doctors" :key="doctor.id" class="rounded-lg border border-sidebar-border/70 dark:border-sidebar-border p-3 flex items-center">
                            <div class="w-12 h-12 rounded-full bg-gray-200 overflow-hidden mr-3">
                                <img v-if="doctor.photo_url" :src="doctor.photo_url" alt="Zdjęcie lekarza" class="w-full h-full object-cover" />
                                <div v-else class="w-full h-full flex items-center justify-center bg-nova-accent text-nova-light text-xl font-bold">
                                    {{ doctor.name.charAt(0) }}
                                </div>
                            </div>
                            <div>
                                <p class="font-medium">{{ doctor.name }}</p>
                                <p class="text-sm text-gray-500">{{ doctor.specialization }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <Separator class="my-4" />

                <div class="mt-6 flex flex-col sm:flex-row items-center justify-between gap-4">
                    <div class="text-2xl font-bold text-nova-light">
                        Cena: {{ procedure.base_price }} zł
                    </div>
                    <Button class="bg-nova-primary dark:bg-nova-accent dark:text-nova-light hover:bg-nova-accent">
                        Umów wizytę
                    </Button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
