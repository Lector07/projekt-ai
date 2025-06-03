<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import {type BreadcrumbItem} from '@/types';
import {ref, onMounted} from 'vue';
import axios from 'axios';
import {Button} from '@/components/ui/button';
import Card from 'primevue/card';
import {
    Pagination,
    PaginationEllipsis,
    PaginationFirst,
    PaginationPrevious,
    PaginationLast,
    PaginationNext
} from '@/components/ui/pagination';
import {PaginationList, PaginationListItem} from 'reka-ui';
import {Skeleton} from '@/components/ui/skeleton';
import { useRouter } from 'vue-router';

const router = useRouter();

interface Doctor {
    id: number;
    name?: string;
    first_name?: string;
    last_name?: string;
    full_name?: string;
    specialization: string;
    // image_url?: string; // Zmień to
    profile_picture_url?: string; // Na to
    bio?: string;
}

const doctors = ref<Doctor[]>([]);
const loading = ref(true);
const error = ref<string | null>(null);
const currentPage = ref(1);
const itemsPerPage = ref(4);
const totalItems = ref(0);
const totalPages = ref(1);

const fetchDoctors = async (page = 1) => {
    try {
        loading.value = true;
        error.value = null;

        console.log(`Pobieranie lekarzy dla strony ${page} (per_page: ${itemsPerPage.value})...`);

        const response = await axios.get('/api/v1/doctors', {
            params: {
                page: page,
                per_page: itemsPerPage.value
            }
        });

        console.log('Odpowiedź API (DoctorsPage.vue):', response.data);

        // Sprawdzamy, czy odpowiedź ma dane (elastyczne sprawdzanie)
        if (!response.data) {
            throw new Error('Brak danych w odpowiedzi API');
        }

        // Obsługujemy zarówno przypadek gdy API zwraca {data: [...]} jak i gdy zwraca bezpośrednio tablicę
        if (Array.isArray(response.data)) {
            // API zwróciło bezpośrednio tablicę
            doctors.value = response.data;
            totalItems.value = response.data.length;
            currentPage.value = 1;
            totalPages.value = 1;
        } else if (Array.isArray(response.data.data)) {
            // API zwróciło obiekt z polem data (tablica)
            doctors.value = response.data.data;

            // Jeśli istnieją metadane paginacji, używamy ich
            if (response.data.meta) {
                totalItems.value = response.data.meta.total;
                currentPage.value = response.data.meta.current_page;
                totalPages.value = response.data.meta.last_page;
            } else {
                // Brak metadanych - zakładamy wszystko na jednej stronie
                totalItems.value = response.data.data.length;
                currentPage.value = 1;
                totalPages.value = 1;
            }
        } else {
            throw new Error('Nieprawidłowa struktura odpowiedzi API');
        }

        console.log(`Pobrano ${doctors.value.length} lekarzy. Strona: ${currentPage.value}/${totalPages.value}. Total: ${totalItems.value}.`);

    } catch (err: any) {
        console.error('Błąd podczas pobierania danych lekarzy:', err);
        error.value = err.message || 'Nie udało się pobrać listy lekarzy';
        doctors.value = [];
        totalItems.value = 0;
        currentPage.value = 1;
        totalPages.value = 1;
    } finally {
        loading.value = false;
    }
};

const goToPage = (page: number) => {
    if (page === currentPage.value) return;
    console.log(`Zmiana strony na: ${page}`);
    fetchDoctors(page);
};

onMounted(() => {
    fetchDoctors(1);
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
            <div class="flex items-center ml-2">
                <h1 class="text-3xl font-bold">Nasi Specjaliści</h1>
            </div>

            <div v-if="loading" class="flex flex-col h-full w-full rounded-md border border-sidebar-border/70 dark:border-sidebar-border mx-0 p-3 justify-center">
                <div class="flex-grow grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-12 auto-rows-max place-items-center">
                    <div v-for="i in 4" :key="i" class="border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden shadow-sm w-full h-auto flex flex-col">
                        <div class="flex h-full">
                            <div class="w-1/3" style="min-height: 250px;">
                                <Skeleton class="w-full h-full" />
                            </div>

                            <div class="w-2/3 flex flex-col p-4 h-full">
                                <div class="flex-grow">
                                    <Skeleton class="h-6 w-3/4 mb-2" />
                                    <Skeleton class="h-4 w-1/2 mb-4" />
                                    <Skeleton class="h-16 w-full mb-2" />
                                </div>

                                <div class="flex justify-end gap-4 mt-25 pt-2">
                                    <Skeleton class="h-9 w-20" />
                                    <Skeleton class="h-9 w-24" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div v-else-if="error" class="p-4 text-center text-red-500">
                {{ error }}
            </div>

            <div v-else class="flex flex-col h-full w-full rounded-md border border-sidebar-border/70 dark:border-sidebar-border mx-0 p-3 justify-center">
                <div v-if="doctors.length === 0" class="flex-grow py-4 text-center text-gray-500">
                    Nie znaleziono lekarzy.
                </div>

                <div v-else class="flex-grow grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-12 auto-rows-max place-items-center">
                    <Card
                        v-for="doctor in doctors"
                        :key="doctor.id"
                        style="--p-card-border-radius: 0.75rem; overflow: hidden;"
                        class="mx-auto border border-gray-200 dark:border-gray-700 shadow-sm w-full h-auto flex flex-col"
                    >
                        <template #content>
                            <div class="flex h-full">
                                <div class="w-1/3" style="min-height: 250px;">
                                    <img
                                        :src="doctor.profile_picture_url || `https://ui-avatars.com/api/?name=${encodeURIComponent(getDoctorName(doctor))}&background=random&size=250`"
                                        :alt="`Dr. ${getDoctorName(doctor)}`"
                                        class="w-full h-full object-cover object-center"
                                    />
                                </div>

                                <div class="w-2/3 flex flex-col p-4 h-full">
                                    <div class="flex-grow">
                                        <h3 class="text-xl font-semibold mb-2">{{ getDoctorName(doctor) }}</h3>
                                        <div class="dark:text-gray-300 mb-4">
                                            {{ doctor.specialization || 'Brak określonej specjalizacji' }}
                                        </div>
                                        <div class="text-sm text-gray-500 dark:text-gray-400" v-if="doctor.bio">
                                            {{ doctor.bio.substring(0, 100) }}{{ doctor.bio.length > 100 ? '...' : '' }}
                                        </div>
                                    </div>

                                    <div class="flex gap-4 justify-end mt-25 lg:mt-50 pt-2">
                                        <Button
                                            variant="outline"
                                            class="w-auto bg-nova-primary hover:bg-nova-accent dark:bg-nova-accent hover:dark:bg-nova-dark text-nova-light"
                                            @click="router.push(`/doctors/${doctor.id}`)"
                                        >
                                            Profil
                                        </Button>
                                        <Button class="w-auto bg-nova-accent hover:bg-nova-primary dark:bg-nova-primary hover:dark:bg-nova-dark text-nova-light">Umów wizytę</Button>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </Card>
                </div>

                <div class="mt-4 pt-2 flex justify-center">
                    <Pagination
                        v-if="totalPages > 1"
                        :items-per-page="itemsPerPage"
                        :total="totalItems"
                        :sibling-count="1"
                        show-edges
                        :default-page="currentPage"
                        @update:page="goToPage"
                    >
                        <PaginationList v-slot="{ items }" class="flex items-center gap-1">
                            <PaginationFirst @click="goToPage(1)"/>
                            <PaginationPrevious @click="goToPage(Math.max(1, currentPage - 1))"/>

                            <template v-for="(item, index) in items" :key="index">
                                <PaginationListItem v-if="item.type === 'page'" :value="item.value" as-child>
                                    <Button
                                        class="w-9 h-9 p-0"
                                        :variant="item.value === currentPage ? 'default' : 'outline'"
                                        @click="goToPage(item.value)"
                                    >
                                        {{ item.value }}
                                    </Button>
                                </PaginationListItem>
                                <PaginationEllipsis v-else :index="index"/>
                            </template>

                            <PaginationNext @click="goToPage(Math.min(totalPages, currentPage + 1))"/>
                            <PaginationLast @click="goToPage(totalPages)"/>
                        </PaginationList>
                    </Pagination>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
