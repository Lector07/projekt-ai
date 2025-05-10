<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import PlaceholderPattern from '../../components/PlaceholderPattern.vue';
import { ScrollArea } from '@/components/ui/scroll-area';
import { Separator } from '@/components/ui/separator';
import { Button } from '@/components/ui/button';
import { ref, onMounted } from 'vue';
import axios from 'axios';
import {
    Pagination,
    PaginationEllipsis,
    PaginationFirst,
    PaginationLast,
    PaginationList,
    PaginationListItem,
    PaginationNext,
    PaginationPrev,
} from '@/components/ui/pagination';

interface Procedure {
    id: number;
    name: string;
    description: string;
    category?: string;
    base_price: number;
}

const procedures = ref<Procedure[]>([]);
const loading = ref(true);
const error = ref<string | null>(null);
const categories = ref<string[]>([]);
const selectedCategory = ref<string | null>(null);
const currentPage = ref(1);
const totalItems = ref(0);
const itemsPerPage = ref(10);

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Zabiegi',
        to: '/procedures',
    },
];

const fetchProcedures = async (page = 1) => {
    try {
        loading.value = true;
        const response = await axios.get('/api/v1/procedures', {
            params: {
                page,
                category: selectedCategory.value,
                per_page: itemsPerPage.value
            }
        });

        procedures.value = response.data.data;
        totalItems.value = response.data.total;
        currentPage.value = response.data.current_page;

        // Jeśli jeszcze nie pobrano kategorii
        if (categories.value.length === 0) {
            fetchCategories();
        }
    } catch (err) {
        console.error('Błąd podczas pobierania zabiegów:', err);
        error.value = 'Nie udało się pobrać listy zabiegów';
    } finally {
        loading.value = false;
    }
};

const fetchCategories = async () => {
    try {
        const response = await axios.get('/api/v1/procedures/categories');
        categories.value = response.data;
    } catch (err) {
        console.error('Błąd podczas pobierania kategorii:', err);
    }
};

const filterByCategory = () => {
    currentPage.value = 1;
    fetchProcedures(1);
};

onMounted(() => {
    fetchProcedures(1);
});
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <!-- Filtr kategorii -->
            <div class="flex gap-2">
                <select
                    v-model="selectedCategory"
                    class="rounded-md border border-input bg-background px-3 py-2 text-sm"
                    @change="filterByCategory"
                >
                    <option :value="null">Wszystkie kategorie</option>
                    <option v-for="category in categories" :key="category" :value="category">
                        {{ category }}
                    </option>
                </select>
            </div>

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

            <!-- Lista zabiegów w ScrollArea -->
            <div v-else>
                <ScrollArea class="h-[60vh] rounded-md border border-sidebar-border/70 dark:border-sidebar-border">
                    <div class="p-4">
                        <h4 class="mb-4 text-lg font-medium leading-none">
                            Lista dostępnych zabiegów
                        </h4>

                        <div v-for="procedure in procedures" :key="procedure.id" class="mb-4">
                            <div class="rounded-lg border border-sidebar-border/70 p-4 dark:border-sidebar-border">
                                <div class="mb-2 flex items-center justify-between">
                                    <h3 class="text-xl font-medium">{{ procedure.name }}</h3>
                                    <span class="rounded-full bg-primary/10 px-3 py-1 text-sm font-medium">{{ procedure.category }}</span>
                                </div>

                                <p class="mb-4 text-sm text-gray-600 dark:text-gray-400">{{ procedure.description }}</p>

                                <div class="flex items-center justify-between">
                                    <div class="font-bold text-primary">{{ procedure.base_price }} zł</div>
                                    <Button as-child>
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

                <!-- Paginacja -->
                <div class="mt-4 flex justify-center">
                    <Pagination
                        :items-per-page="itemsPerPage"
                        :total="totalItems"
                        :sibling-count="1"
                        show-edges
                        :default-page="currentPage"
                        v-slot="{ page }"
                        @change="fetchProcedures"
                    >
                        <PaginationList v-slot="{ items }" class="flex items-center gap-1">
                            <PaginationFirst />
                            <PaginationPrev />

                            <template v-for="(item, index) in items">
                                <PaginationListItem v-if="item.type === 'page'" :key="index" :value="item.value" as-child>
                                    <Button class="h-9 w-9 p-0" :variant="item.value === page ? 'default' : 'outline'">
                                        {{ item.value }}
                                    </Button>
                                </PaginationListItem>
                                <PaginationEllipsis v-else :key="item.type" :index="index" />
                            </template>

                            <PaginationNext />
                            <PaginationLast />
                        </PaginationList>
                    </Pagination>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
