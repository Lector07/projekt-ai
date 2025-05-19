<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { ScrollArea } from '@/components/ui/scroll-area';
import { Separator } from '@/components/ui/separator';
import { Button } from '@/components/ui/button';
import { ref, onMounted } from 'vue';
import axios from 'axios';
import { Pagination, PaginationEllipsis, PaginationFirst, PaginationPrevious, PaginationLast, PaginationNext } from '@/components/ui/pagination';
import { PaginationList, PaginationListItem} from 'reka-ui';
import { Skeleton } from '@/components/ui/skeleton';

interface Procedure {
    id: number;
    name: string;
    description: string;
    procedure_category_id?: number;
    category?: {
        id: number;
        name: string;
        slug: string;
    };
    base_price: number;
}

interface ProcedureCategory {
    id: number;
    name: string;
    slug: string;
}

const procedures = ref<Procedure[]>([]);
const loading = ref(true);
const error = ref<string | null>(null);
const categories = ref<ProcedureCategory[]>([]);
const selectedCategory = ref<number | null>(null);
const currentPage = ref(1);
const totalItems = ref(0);
const itemsPerPage = ref(10);
const totalPages = ref(1);

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Zabiegi',
    },
];

const fetchProcedures = async (page = 1) => {
    try {
        loading.value = true;
        error.value = null;

        console.log(`Pobieranie zabiegów dla strony ${page}...`);

        const response = await axios.get('/api/procedures', {
            params: {
                page: page,
                procedure_category_id: selectedCategory.value,
                per_page: itemsPerPage.value
            }
        });

        console.log('Odpowiedź API:', response.data);

        if (!response.data.data || !Array.isArray(response.data.data)) {
            throw new Error('Nieprawidłowa struktura odpowiedzi API');
        }

        procedures.value = response.data.data;
        totalItems.value = response.data.total || 0;
        currentPage.value = page;
        totalPages.value = Math.ceil(totalItems.value / itemsPerPage.value);

        console.log(`Pobrano ${procedures.value.length} zabiegów dla strony ${currentPage.value}`);

        if (categories.value.length === 0) {
            fetchCategories();
        }
    } catch (err) {
        console.error('Błąd podczas pobierania zabiegów:', err);
        error.value = 'Nie udało się pobrać listy zabiegów.';
        procedures.value = [];
    } finally {
        loading.value = false;
    }
};

const fetchCategories = async () => {
    try {
        const response = await axios.get('/api/procedures/categories');
        categories.value = response.data;
    } catch (err) {
        console.error('Błąd podczas pobierania kategorii:', err);
    }
};

const filterByCategory = () => {
    currentPage.value = 1;
    fetchProcedures(1);
};

const goToPage = (page: number) => {
    if (page === currentPage.value) return;
    console.log(`Zmiana strony na: ${page}`);
    fetchProcedures(page);
};

onMounted(() => {
    fetchProcedures(1);
});

const getCategoryName = (procedure: Procedure): string => {
    if (procedure.category) {
        return procedure.category.name;
    }

    if (procedure.procedure_category_id) {
        const category = categories.value.find(c => c.id === procedure.procedure_category_id);
        return category ? category.name : 'Brak kategorii';
    }

    return 'Brak kategorii';
};

const getPageNumbers = () => {
    const pages = [];
    const maxVisible = 5;

    if (totalPages.value <= maxVisible) {
        for (let i = 1; i <= totalPages.value; i++) {
            pages.push(i);
        }
    } else {
        pages.push(1);

        const startPage = Math.max(2, currentPage.value - 1);
        const endPage = Math.min(totalPages.value - 1, currentPage.value + 1);

        if (startPage > 2) {
            pages.push('...');
        }

        for (let i = startPage; i <= endPage; i++) {
            pages.push(i);
        }

        if (endPage < totalPages.value - 1) {
            pages.push('...');
        }

        pages.push(totalPages.value);
    }

    return pages;
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <div class="flex gap-2">
                <select
                    v-model="selectedCategory"
                    class="rounded-md border border-input bg-background px-3 py-2 text-sm"
                    @change="filterByCategory"
                >
                    <option :value="null">Wszystkie kategorie</option>
                    <option v-for="category in categories" :key="category.id" :value="category.id">
                        {{ category.name }}
                    </option>
                </select>
            </div>

            <div v-if="loading">
                <ScrollArea class="h-[70vh] rounded-md border border-sidebar-border/70 dark:border-sidebar-border">
                    <div class="p-4">
                        <Skeleton class="h-6 w-72 mb-4" />

                        <div v-for="i in 5" :key="i" class="mb-4">
                            <div class="rounded-lg border border-sidebar-border/70 p-4 dark:border-sidebar-border">
                                <div class="mb-2 flex items-center justify-between">
                                    <Skeleton class="h-6 w-48" />
                                    <Skeleton class="h-6 w-24 rounded-full" />
                                </div>

                                <Skeleton class="h-4 w-full mb-2" />
                                <Skeleton class="h-4 w-3/4 mb-4" />

                                <div class="flex items-center justify-between">
                                    <Skeleton class="h-6 w-20" />
                                    <Skeleton class="h-9 w-24 rounded-md" />
                                </div>
                            </div>
                            <Separator class="my-2" />
                        </div>
                    </div>
                </ScrollArea>
            </div>

            <div v-else-if="error" class="p-4 text-center text-red-500">
                {{ error }}
            </div>

            <div v-else>
                <ScrollArea class="h-[70vh] rounded-md border border-sidebar-border/70 dark:border-sidebar-border">
                    <div class="p-4">
                        <h4 class="mb-4 text-lg font-medium leading-none">
                            Lista dostępnych zabiegów
                        </h4>
                        <div v-if="procedures.length === 0" class="py-4 text-center text-gray-500">
                            Brak zabiegów do wyświetlenia.
                        </div>

                        <div v-else v-for="procedure in procedures" :key="procedure.id" class="mb-4">
                            <div class="rounded-lg border border-sidebar-border/70 p-4 dark:border-sidebar-border">
                                <div class="mb-2 flex items-center justify-between">
                                    <h3 class="text-xl font-medium">{{ procedure.name }}</h3>
                                    <span class="rounded-full bg-nova-accent dark:bg-nova-primary text-nova-light px-3 py-1 text-sm font-medium">
                                        {{ getCategoryName(procedure) }}
                                    </span>
                                </div>

                                <p class="mb-4 text-sm text-gray-600 dark:text-gray-400">{{ procedure.description }}</p>

                                <div class="flex items-center justify-between">
                                    <div class="font-extrabold text-nova-darkest dark:text-nova-light">{{ procedure.base_price }} zł</div>
                                    <Button as-child class="bg-nova-primary dark:bg-nova-accent dark:text-nova-light hover:bg-nova-accent">
                                        <router-link :to="{ name: 'procedure.detail', params: { id: procedure.id } }">
                                            Szczegóły
                                        </router-link>
                                    </Button>
                                </div>
                            </div>
                            <Separator class="my-2" />
                        </div>
                    </div>
                </ScrollArea>

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
                            <PaginationFirst @click="goToPage(1)" />
                            <PaginationPrevious @click="goToPage(Math.max(1, currentPage - 1))" />

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
                                <PaginationEllipsis v-else :index="index" />
                            </template>

                            <PaginationNext @click="goToPage(Math.min(totalPages, currentPage + 1))" />
                            <PaginationLast @click="goToPage(totalPages)" />
                        </PaginationList>
                    </Pagination>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
