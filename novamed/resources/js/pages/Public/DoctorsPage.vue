<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Button } from '@/components/ui/button'
import { ref, onMounted } from 'vue';
import axios from 'axios';
import PlaceholderPattern from '../../components/PlaceholderPattern.vue';
import { ScrollArea } from '@/components/ui/scroll-area';
import { Separator } from '@/components/ui/separator';

interface Doctor {
    id: number;
    name?: string;
    first_name?: string;
    last_name?: string;
    full_name?: string;
    specialization: string;
    image_url?: string;
}

const doctors = ref<Doctor[]>([]);
const loading = ref(true);
const error = ref<string | null>(null);

const fetchDoctors = async () => {
    try {
        loading.value = true;
        const response = await axios.get('/api/doctors');
        doctors.value = response.data.data;

        if (doctors.value.length > 0) {
            console.log('Struktura pierwszego lekarza:', doctors.value[0]);
        }
    } catch (err) {
        console.error('Błąd podczas pobierania danych:', err);
        error.value = 'Nie udało się pobrać listy lekarzy';
    } finally {
        loading.value = false;
    }
};

onMounted(() => {
    fetchDoctors();
});

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Lekarze',
        to: '/doctors',
    },
];

const getDoctorName = (doctor: Doctor): string => {
    if (doctor.name) return doctor.name;
    if (doctor.full_name) return doctor.full_name;
    if (doctor.first_name && doctor.last_name) return `${doctor.first_name} ${doctor.last_name}`;
    return 'Brak danych';
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <!-- Ładowanie -->
            <div v-if="loading" class="grid auto-rows-min gap-4 md:grid-cols-3">
                <div v-for="i in 3" :key="i" class="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                    <PlaceholderPattern />
                </div>
            </div>

            <!-- Błąd -->
            <div v-else-if="error" class="p-4 text-center text-red-500">
                {{ error }}
            </div>

            <!-- Lista lekarzy w ScrollArea -->
            <div v-else class="grid gap-4 md:grid-cols-2">
                <ScrollArea class="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                    <div class="p-4">
                        <h4 class="mb-4 text-lg font-medium leading-none">
                            Lista lekarzy
                        </h4>

                        <div v-for="doctor in doctors" :key="doctor.id">
                            <div class="flex items-center gap-3 py-2">
                                <img
                                    :src="doctor.image_url || `https://ui-avatars.com/api/?name=${encodeURIComponent(getDoctorName(doctor))}&background=random`"
                                    alt="zdjęcie lekarza"
                                    class="h-12 w-12 rounded-full object-cover"
                                />
                                <div>
                                    <div class="font-medium">{{ getDoctorName(doctor) }}</div>
                                    <div class="text-sm text-gray-600">
                                        {{ doctor.specialization || 'Brak specjalizacji' }}
                                    </div>
                                </div>
                                <div class="ml-auto flex gap-2">
                                    <Button class="inline-block rounded-sm bg-nova-light hover:bg-nova-accent hover:text-nova-light border border-nova-primary px-5 py-1.5 text-sm leading-normal text-[#1b1b18] hover:border-nova-accent dark:border-nova-primary dark:text-[#EDEDEC] dark:hover:border-nova-accent duration-100 ease-in transform transition-transform hover:scale-105">Profil</Button>
                                    <Button class="inline-block rounded-sm bg-nova-light hover:bg-nova-accent hover:text-nova-light border border-nova-primary px-5 py-1.5 text-sm leading-normal text-[#1b1b18] hover:border-nova-accent dark:border-nova-primary dark:text-[#EDEDEC] dark:hover:border-nova-accent duration-100 ease-in transform transition-transform hover:scale-105">Umów</Button>
                                </div>
                            </div>
                            <Separator class="my-2" />
                        </div>
                    </div>
                </ScrollArea>

                <div class="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                    <PlaceholderPattern />
                </div>
            </div>

            <div class="relative flex-grow rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                <PlaceholderPattern />
            </div>
        </div>
    </AppLayout>
</template>
