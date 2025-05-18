<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';
import { useRoute, useRouter } from 'vue-router';
import { Separator } from '@/components/ui/separator';
import { Button } from '@/components/ui/button';
import { Skeleton } from '@/components/ui/skeleton';
import Card from 'primevue/card';

interface User {
    id: number;
    name: string;
    email?: string;
    role?: string;
    created_at?: string;
    image_url?: string;
    profile_picture_path?: string;
}

const route = useRoute();
const router = useRouter();
const patient = ref<User | null>(null);
const loading = ref(true);
const error = ref<string | null>(null);

const fetchPatient = async () => {
    try {
        loading.value = true;
        error.value = null;

        const patientId = route.params.id;
        const response = await axios.get(`/api/v1/admin/users/${patientId}`);

        if (!response.data) {
            throw new Error('Nie znaleziono danych pacjenta');
        }

        console.log("Dane pacjenta z API:", response.data);
        // Przypisujemy zagnieżdżony obiekt data, a nie całą odpowiedź
        patient.value = response.data.data;
    } catch (err) {
        console.error('Błąd podczas pobierania danych pacjenta:', err);
        error.value = 'Nie udało się pobrać danych pacjenta';
    } finally {
        loading.value = false;
    }
};

const formatCreatedAt = (dateString?: string): string => {
    if (!dateString) return 'Brak danych';
    return new Date(dateString).toLocaleDateString('pl-PL');
};

const translateRole = (role?: string): string => {
    if (!role) return 'Brak danych';

    const roles: Record<string, string> = {
        'admin': 'Administrator',
        'doctor': 'Lekarz',
        'patient': 'Pacjent',
    };

    return roles[role] || role;
};

onMounted(() => {
    fetchPatient();
});

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    {
        title: 'Wizyty',
        href: '/admin/appointments',
    },
    {
        title: patient.value ? patient.value.name : 'Szczegóły pacjenta',
    },
]);
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <div class="mb-4 flex items-center ml-2">
                <h1 class="text-3xl font-bold">O Pacjencie</h1>
            </div>

            <!-- Ładowanie -->
            <div v-if="loading">
                <Skeleton class="h-[70vh] rounded-md border border-sidebar-border/70 dark:border-sidebar-border" />
            </div>

            <!-- Błąd -->
            <div v-else-if="error" class="p-4 text-center text-red-500">
                {{ error }}
            </div>

            <!-- Dane pacjenta -->
            <div v-else-if="patient" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Lewa kolumna ze zdjęciem -->
                <div class="lg:col-span-1">
                    <div class="rounded-xl overflow-hidden shadow-md border border-gray-200 dark:border-gray-700">
                        <img
                            :src="patient.profile_picture_path || `https://ui-avatars.com/api/?name=${encodeURIComponent(patient.name)}&background=random&size=500`"
                            :alt="patient.name"
                            class="w-full aspect-square object-cover"
                        />
                    </div>
                </div>

                <!-- Prawa kolumna z kartą informacyjną -->
                <div class="lg:col-span-2">
                    <Card class="border border-gray-200 dark:border-gray-700 shadow-md" style="--p-card-border-radius: 0.75rem;">
                        <template #title>
                            <h2 class="text-2xl font-bold ml-3 p-4">{{ patient.name }}</h2>
                        </template>

                        <template #subtitle>
                            <p v-if="patient.email" class="text-xl dark:text-gray-300 ml-7">
                                {{ patient.email }}
                            </p>
                        </template>

                        <template #content>
                            <Separator class="my-4" />
                            <div class="space-y-4 p-2">
                                <h3 class="text-lg font-semibold ml-7">Dane pacjenta</h3>

                                <div class="ml-7 space-y-2">
                                    <p v-if="patient.email" class="dark:text-gray-300">
                                        <span class="font-medium">Email:</span> {{ patient.email }}
                                    </p>
                                    <p v-if="patient?.role" class="dark:text-gray-300">
                                        <span class="font-medium">Rola:</span> {{ translateRole(patient.role) }}
                                    </p>
                                    <p v-if="patient?.created_at" class="dark:text-gray-300">
                                        <span class="font-medium">Data rejestracji:</span> {{ formatCreatedAt(patient.created_at) }}
                                    </p>
                                </div>
                            </div>

                            <div class="flex justify-end gap-4 mt-6 p-6">
                                <Button
                                    variant="outline"
                                    class="bg-nova-primary hover:bg-nova-accent dark:bg-nova-accent hover:dark:bg-nova-dark text-nova-light"
                                    @click="router.push('/admin/appointments')"
                                >
                                    Powrót
                                </Button>
                            </div>
                        </template>
                    </Card>
                </div>
            </div>

            <div v-else class="p-4 text-center text-gray-500">
                Nie znaleziono danych pacjenta.
            </div>
        </div>
    </AppLayout>
</template>
