<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { ref, onMounted } from 'vue';
import axios from 'axios';
import { useRoute, useRouter } from 'vue-router';
import { Separator } from '@/components/ui/separator';
import { Button } from '@/components/ui/button';
import { Skeleton } from '@/components/ui/skeleton';
import Card from 'primevue/card';

interface Doctor {
    id: number;
    first_name?: string;
    last_name?: string;
    name?: string;
    full_name?: string;
    specialization: string;
    createdAt?: string;
    bio?: string;
    image_url?: string;
    profile_picture_path?: string;
}

const route = useRoute();
const router = useRouter();
const doctor = ref<Doctor | null>(null);
const loading = ref(true);
const error = ref<string | null>(null);

const fetchDoctor = async () => {
    try {
        loading.value = true;
        error.value = null;

        const doctorId = route.params.id;
        const response = await axios.get(`/api/v1/admin/doctors/${doctorId}`);

        console.log('Dane lekarza z API dla strony szczegółów:', response.data);

        if (!response.data) {
            throw new Error('Nie znaleziono danych lekarza');
        }

        doctor.value = response.data.data;

    } catch (err) {
        console.error('Błąd podczas pobierania danych lekarza:', err);
        error.value = 'Nie udało się pobrać danych lekarza';
    } finally {
        loading.value = false;
    }
};

const getDoctorName = (doctor: Doctor): string => {
    if (doctor.name) return doctor.name;
    if (doctor.full_name) return doctor.full_name;
    if (doctor.first_name && doctor.last_name) return `${doctor.first_name} ${doctor.last_name}`;
    return 'Brak danych';
};

const formatCreatedAt = (dateString?: string): string => {
    if (!dateString) return 'Brak danych';
    return new Date(dateString).toLocaleDateString('pl-PL');
};

onMounted(() => {
    fetchDoctor();
});

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Wizyty',
        href: '/admin/appointments',
    },
    {
        title: doctor.value ? getDoctorName(doctor.value) : 'Szczegóły lekarza',
    },
];
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <div class="mb-4 flex items-center ml-2">
                <h1 class="text-3xl font-bold">Profil Lekarza</h1>
            </div>

            <!-- Ładowanie -->
            <div v-if="loading">
                <Skeleton class="h-[70vh] rounded-md border border-sidebar-border/70 dark:border-sidebar-border" />
            </div>

            <!-- Błąd -->
            <div v-else-if="error" class="p-4 text-center text-red-500">
                {{ error }}
            </div>

            <!-- Dane lekarza -->
            <div v-else-if="doctor" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Lewa kolumna ze zdjęciem -->
                <div class="lg:col-span-1 ">
                    <div class="rounded-xl overflow-hidden shadow-md border border-gray-200 dark:border-gray-700">
                        <img
                            :src="doctor.profile_picture_url || `https://ui-avatars.com/api/?name=${encodeURIComponent(getDoctorName(doctor))}&background=random&size=500`"
                            :alt="`Dr. ${getDoctorName(doctor)}`"
                            class="w-full aspect-square object-cover"
                        />
                    </div>
                </div>

                <div class="lg:col-span-2">
                    <Card class="border border-gray-200 dark:border-gray-700 shadow-md" style="--p-card-border-radius: 0.75rem;">
                        <template #title>
                            <h2 class="text-2xl font-bold ml-3 p-4">{{ getDoctorName(doctor) }}</h2>
                        </template>

                        <template #subtitle>
                            <p class="text-xl dark:text-gray-300 ml-7">
                                {{ doctor.specialization || 'Brak określonej specjalizacji' }}
                            </p>
                        </template>

                        <template #content>
                            <Separator class="my-4" />
                            <div class="space-y-4 p-2">
                                <h3 class="text-lg font-semibold ml-7">O lekarzu</h3>
                                <p class="dark:text-gray-300 ml-7">
                                    {{ doctor.bio || 'Brak informacji o lekarzu.' }}
                                </p>
                                <p class="dark:text-gray-300 ml-7">
                                    <span class="font-medium">Data rejestracji lekarza:</span> {{ formatCreatedAt(doctor.created_at) }}
                                </p>
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
                Nie znaleziono danych lekarza.
            </div>
        </div>
    </AppLayout>
</template>
