<script setup lang="ts">
import { ref, onMounted, reactive } from 'vue';
import axios from 'axios';
import { useToast } from 'primevue/usetoast';
import Icon from '@/components/Icon.vue';
import { Table, TableBody, TableHead, TableHeader, TableRow, TableCell } from '@/components/ui/table';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Skeleton } from '@/components/ui/skeleton';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { ContextMenu, ContextMenuContent, ContextMenuItem, ContextMenuSeparator, ContextMenuTrigger } from '@/components/ui/context-menu';
import AppLayout from '@/layouts/AppLayout.vue';
import Toast from 'primevue/toast';

interface Category {
    id: number;
    name: string;
    slug: string;
    created_at?: string;
    updated_at?: string;
}

const breadcrumbs = ref([ { title: 'Kategorie procedur' } ]);

const categories = ref<Category[]>([]);
const loading = ref(true);
const error = ref(false);
const toast = useToast();

const isDialogOpen = ref(false);
const isEditing = ref(false);
const form = reactive({
    id: null as number | null,
    name: '',
    slug: '',
});
const formErrors = ref<Record<string, string[]>>({});

const fetchCategories = async () => {
    loading.value = true;
    error.value = false;
    try {
        const response = await axios.get('/api/v1/admin/procedure-categories');
        if (response.data?.data && Array.isArray(response.data.data)) {
            categories.value = response.data.data;
        } else {
            error.value = true;
            toast.add({ severity: 'error', summary: 'Błąd', detail: 'Nieprawidłowy format danych.', life: 3000 });
        }
    } catch (err) {
        error.value = true;
        toast.add({ severity: 'error', summary: 'Błąd', detail: 'Nie udało się pobrać kategorii.', life: 3000 });
    } finally {
        loading.value = false;
    }
};

const openNewCategoryDialog = () => {
    isEditing.value = false;
    form.id = null;
    form.name = '';
    form.slug = '';
    formErrors.value = {};
    isDialogOpen.value = true;
};

const editCategory = (category: Category) => {
    isEditing.value = true;
    form.id = category.id;
    form.name = category.name;
    form.slug = category.slug;
    formErrors.value = {};
    isDialogOpen.value = true;
};

const saveCategory = async () => {
    formErrors.value = {};
    try {
        if (isEditing.value) {
            await axios.put(`/api/v1/admin/procedure-categories/${form.id}`, form);
            toast.add({ severity: 'success', summary: 'Sukces', detail: 'Kategoria została zaktualizowana.', life: 3000 });
        } else {
            await axios.post('/api/v1/admin/procedure-categories', form);
            toast.add({ severity: 'success', summary: 'Sukces', detail: 'Kategoria została dodana.', life: 3000 });
        }
        isDialogOpen.value = false;
        fetchCategories();
    } catch (err: any) {
        if (err.response?.status === 422) {
            formErrors.value = err.response.data.errors;
        } else {
            toast.add({ severity: 'error', summary: 'Błąd', detail: 'Wystąpił błąd podczas zapisywania.', life: 3000 });
        }
    }
};

const deleteCategory = async (id: number) => {
    if (!confirm('Czy na pewno chcesz usunąć tę kategorię? Usunięcie kategorii spowoduje również usunięcie powiązanych z nią procedur.')) return;
    try {
        await axios.delete(`/api/v1/admin/procedure-categories/${id}`);
        toast.add({ severity: 'success', summary: 'Sukces', detail: 'Kategoria została usunięta.', life: 3000 });
        fetchCategories();
    } catch (err) {
        toast.add({ severity: 'error', summary: 'Błąd', detail: 'Nie udało się usunąć kategorii.', life: 3000 });
    }
};

onMounted(() => { fetchCategories(); });
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Toast />
        <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-6 flex flex-col">
            <header class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 border rounded-md p-4 mb-4">
                <div>
                    <h1 class="text-xl sm:text-2xl font-semibold">Kategorie procedur</h1>
                    <p class="text-sm text-gray-500 mt-1">Zarządzaj kategoriami procedur medycznych.</p>
                </div>
                <Button @click="openNewCategoryDialog" class="w-full sm:w-auto flex-shrink-0 bg-nova-primary hover:bg-nova-accent dark:bg-nova-accent hover:dark:bg-nova-primary dark:text-nova-light items-center gap-2">
                    <Icon name="plus" size="16" class="mr-1" />
                    Dodaj kategorię
                </Button>
            </header>

            <main class="flex flex-col flex-grow">
                <div v-if="loading" class="space-y-2">
                    <Skeleton v-for="i in 5" :key="i" class="h-14 w-full" />
                </div>
                <div v-else-if="error" class="bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-300 rounded-lg p-6 text-center">
                    <p class="mb-4">Wystąpił błąd podczas pobierania danych.</p>
                    <Button @click="fetchCategories">Spróbuj ponownie</Button>
                </div>
                <div v-else class="rounded-lg dark:bg-gray-800 shadow-sm border dark:border-gray-700 overflow-hidden">
                    <div class="overflow-x-auto">
                        <Table class="w-full table-fixed">
                            <TableHeader>
                                <TableRow>
                                    <TableHead class="w-16 hidden sm:table-cell">ID</TableHead>
                                    <TableHead class="w-[20%]">Nazwa</TableHead>
                                    <TableHead class="hidden w-auto md:table-cell">Opis/Slug</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <template v-if="categories.length > 0">
                                    <ContextMenu v-for="category in categories" :key="category.id">
                                        <ContextMenuTrigger as-child>
                                            <TableRow class="hover:bg-gray-50 dark:hover:bg-gray-700/50 cursor-context-menu">
                                                <TableCell class="hidden content-center sm:table-cell">{{ category.id }}</TableCell>
                                                <TableCell class="font-medium content-center break-words">{{ category.name }}</TableCell>
                                                <TableCell class="hidden md:table-cell content-center text-sm text-gray-500 break-words text-balance">{{ category.slug }}</TableCell>
                                            </TableRow>
                                        </ContextMenuTrigger>
                                        <ContextMenuContent>
                                            <ContextMenuItem @click="editCategory(category)"><Icon name="edit" size="16" class="mr-2" />Edytuj</ContextMenuItem>
                                            <ContextMenuSeparator />
                                            <ContextMenuItem @click="deleteCategory(category.id)" class="text-red-600 cursor-pointer"><Icon name="trash2" size="16" class="mr-2" />Usuń</ContextMenuItem>
                                        </ContextMenuContent>
                                    </ContextMenu>
                                </template>
                                <TableRow v-else>
                                    <TableCell colspan="3" class="text-center text-gray-500 py-8">
                                        Brak zdefiniowanych kategorii.
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>
                </div>
            </main>

            <Dialog v-model:open="isDialogOpen">
                <DialogContent class="w-[95vw] sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle>{{ isEditing ? 'Edytuj kategorię' : 'Dodaj nową kategorię' }}</DialogTitle>
                    </DialogHeader>
                    <form @submit.prevent="saveCategory" class="space-y-4 pt-4">
                        <div>
                            <Label for="name" class="mb-2">Nazwa kategorii</Label>
                            <Input id="name" v-model="form.name" placeholder="np. Chirurgia Plastyczna" />
                            <p v-if="formErrors.name" class="mt-2 text-sm text-red-600">{{ formErrors.name[0] }}</p>
                        </div>
                        <div>
                            <Label for="slug" class="mb-2">Opis / Slug</Label>
                            <textarea
                                id="slug"
                                v-model="form.slug"
                                rows="4"
                                class="w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                placeholder="Krótki opis lub identyfikator (slug)"
                            ></textarea>
                            <p v-if="formErrors.slug" class="mt-2 text-sm text-red-600">{{ formErrors.slug[0] }}</p>
                        </div>
                        <DialogFooter class="!mt-6 flex-col-reverse sm:flex-row sm:justify-end sm:space-x-2">
                            <Button type="button" variant="outline" @click="isDialogOpen = false">Anuluj</Button>
                            <Button type="submit" class="bg-nova-primary text-nova-light hover:bg-nova-accent dark:bg-nova-accent hover:dark:bg-nova-primary">
                                {{ isEditing ? 'Zapisz zmiany' : 'Dodaj kategorię' }}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </div>
    </AppLayout>
</template>

<style scoped>
:deep(td),
:deep(th) {
    word-break: break-word;
    overflow-wrap: break-word;
    white-space: normal;
    min-width: 0;
}

:deep(td) {
    vertical-align: top;
}
</style>
