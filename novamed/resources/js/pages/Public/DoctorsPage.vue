<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import {type BreadcrumbItem} from '@/types';
import {ref, onMounted} from 'vue';
import axios from 'axios';
import PlaceholderPattern from '../../components/PlaceholderPattern.vue';
import {Separator} from '@/components/ui/separator';
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
    image_url?: string;
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

        console.log(`Pobieranie lekarzy dla strony ${page}...`);

        const response = await axios.get('/api/doctors', {
            params: {
                page: page,
                per_page: itemsPerPage.value
            }
        });

        console.log('Odpowiedź API:', response.data);

        if (!response.data.data || !Array.isArray(response.data.data)) {
            throw new Error('Nieprawidłowa struktura odpowiedzi API');
        }

        doctors.value = response.data.data;
        totalItems.value = response.data.total || 0;
        currentPage.value = page;
        totalPages.value = Math.ceil(totalItems.value / itemsPerPage.value);

        console.log(`Pobrano ${doctors.value.length} lekarzy dla strony ${currentPage.value}`);
    } catch (err) {
        console.error('Błąd podczas pobierania danych:', err);
        error.value = 'Nie udało się pobrać listy lekarzy';
        doctors.value = [];
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

            <!-- Ładowanie - struktura odpowiadająca kontenerowi z kartami -->
            <div v-if="loading" class="rounded-md border border-sidebar-border/70 dark:border-sidebar-border mx-1 p-3">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div v-for="i in 4" :key="i" class="border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden shadow-sm">
                        <Skeleton class="w-full h-52" />
                        <div class="p-4">
                            <Skeleton class="h-6 w-3/4 mb-3" />
                            <Skeleton class="h-4 w-1/2 mb-6" />
                            <div class="flex justify-end gap-2">
                                <Skeleton class="h-9 w-20" />
                                <Skeleton class="h-9 w-24" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Błąd -->
            <div v-else-if="error" class="p-4 text-center text-red-500">
                {{ error }}
            </div>

            <!-- Karty lekarzy bez ScrollArea -->
            <div v-else class="rounded-md border border-sidebar-border/70 dark:border-sidebar-border mx-1 p-3">
                <div v-if="doctors.length === 0" class="py-4 text-center text-gray-500">
                    Nie znaleziono lekarzy.
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <Card
                        v-for="doctor in doctors"
                        :key="doctor.id"
                        style="width: 100%; overflow: hidden; --p-card-border-radius: 0.75rem; "
                        class="mx-auto border border-gray-200 dark:border-gray-700 shadow-sm"
                    >
                        <template #header>
                            <img
                                :src="doctor.image_url || `https://ui-avatars.com/api/?name=${encodeURIComponent(getDoctorName(doctor))}&background=random&size=250`"
                                :alt="`Dr. ${getDoctorName(doctor)}`"
                                class="w-full h-52 object-cover"
                            />
                        </template>

                        <template #title>
                            <h3 class="text-xl font-semibold m-1 ml-3">{{ getDoctorName(doctor) }}</h3>
                        </template>

                        <template #content>
                            <p class="ml-3 dark:text-gray-300">
                                {{ doctor.specialization || 'Brak określonej specjalizacji' }}
                            </p>
                        </template>

                        <template #footer>
                            <div class="flex gap-4 mt-1 justify-end p-2">
                                <Button
                                    variant="outline"
                                    class="w-auto bg-nova-primary hover:bg-nova-accent dark:bg-nova-accent hover:dark:bg-nova-dark text-nova-light"
                                    @click="router.push(`/doctors/${doctor.id}`)"
                                >
                                    Profil
                                </Button>
                                <Button class="w-auto bg-nova-accent hover:bg-nova-primary dark:bg-nova-primary hover:dark:bg-nova-dark text-nova-light">Umów wizytę</Button>
                            </div>
                        </template>
                    </Card>
                </div>

                <!-- Paginacja -->
                <div class="mt-4 flex justify-center">
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
