<script setup lang="ts">
import { ref, onMounted, reactive } from 'vue';
import axios from 'axios';
import { useToast } from 'primevue/usetoast';
import Icon from '@/components/Icon.vue';
import { useRouter } from 'vue-router';
import {
    Table,
    TableBody,
    TableHead,
    TableHeader,
    TableRow,
    TableCell
} from '@/components/ui/table';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { ScrollArea } from '@/components/ui/scroll-area';
import { Skeleton } from '@/components/ui/skeleton';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle
} from '@/components/ui/dialog';
import {
    ContextMenu,
    ContextMenuContent,
    ContextMenuItem,
    ContextMenuSeparator,
    ContextMenuTrigger
} from '@/components/ui/context-menu';
import AppLayout from '@/layouts/AppLayout.vue';
import {Separator} from "@/components/ui/separator";
import Toast from 'primevue/toast';

interface Category {
    id: number;
    name: string;
    slug: string;
    created_at?: string;
    updated_at?: string;
}

const breadcrumbs = ref([
    {
        title: 'Kategorie procedur',
    },
]);

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
        console.log('Pobieranie kategorii...');
        const response = await axios.get('/api/v1/admin/procedure-categories');
        console.log('Odpowiedź API:', response);

        if (response.data && Array.isArray(response.data)) {
            categories.value = response.data;
        } else if (response.data && response.data.data && Array.isArray(response.data.data)) {
            categories.value = response.data.data;
        } else {
            console.error('Nieprawidłowa struktura danych:', response.data);
            error.value = true;
            toast.add({ severity: 'error', summary: 'Błąd', detail: 'Nieprawidłowy format danych.', life: 3000 });
        }
    } catch (err) {
        error.value = true;
        console.error('Błąd pobierania danych:', err);
        toast.add({ severity: 'error', summary: 'Błąd', detail: 'Nie udało się pobrać kategorii.', life: 3000 });
    } finally {
        loading.value = false;
        console.log('Stan kategorii:', categories.value);
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
        if (err.response && err.response.status === 422) {
            formErrors.value = err.response.data.errors;
        } else {
            toast.add({ severity: 'error', summary: 'Błąd', detail: 'Wystąpił błąd podczas zapisywania kategorii.', life: 3000 });
            console.error(err);
        }
    }
};

const deleteCategory = async (id: number) => {
    if (!confirm('Czy na pewno chcesz usunąć tę kategorię?')) return;

    try {
        await axios.delete(`/api/v1/admin/procedure-categories/${id}`);
        toast.add({ severity: 'success', summary: 'Sukces', detail: 'Kategoria została usunięta.', life: 3000 });
        fetchCategories();
    } catch (err) {
        toast.add({ severity: 'error', summary: 'Błąd', detail: 'Nie udało się usunąć kategorii.', life: 3000 });
        console.error(err);
    }
};


onMounted(() => {
    fetchCategories();
});
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Toast />
        <div class="container mx-auto py-6 px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center border rounded-md p-4 mb-6">
                <h1 class="text-2xl font-semibold">Kategorie procedur <span> <p class="text-sm text-gray-500">Zarządzaj kategoriami procedur</p></span></h1>
                <Button @click="openNewCategoryDialog" class="flex bg-nova-primary hover:bg-nova-accent dark:bg-nova-accent hover:dark:bg-nova-primary dark:text-nova-light items-center gap-2">
                    <Icon name="plus" size="16" class="mr-2" />
                    Dodaj kategorię
                </Button>
            </div>

            <Separator class="mb-4" />

            <p class="text-sm  mt-1 ml-2 text-gray-400">Kliknij PPM aby usunąć lub edytować</p>


            <div v-if="loading" class="flex flex-col h-full w-full gap-8">
                <div class="border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden shadow-sm bg-white dark:bg-gray-800">
                    <div class="p-4">
                        <div class="flex justify-between items-center mb-4">
                            <Skeleton class="h-8 w-1/4" />
                            <Skeleton class="h-10 w-36" />
                        </div>
                        <div class="space-y-2">
                            <Skeleton v-for="i in 5" :key="i" class="h-12 w-full" />
                        </div>
                    </div>
                </div>
            </div>

            <div v-else-if="error" class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 text-center">
                <p class="text-red-500 mb-4">
                    Wystąpił błąd podczas pobierania danych.
                </p>
                <Button @click="fetchCategories">Spróbuj ponownie</Button>
            </div>

            <div v-else class="bg-white mt-2 dark:bg-gray-900 w-full rounded-xl shadow-sm overflow-hidden">
                <div class="overflow-x-auto h-[auto] w-full">
                    <Table class="w-full p-2">
                        <TableHeader class="bg-[#f9fafb]">
                            <TableRow>
                                <TableHead class="w-16">ID</TableHead>
                                <TableHead>Nazwa</TableHead>
                                <TableHead>Opis kategorii</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="category in categories" :key="category.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                <ContextMenu>
                                    <ContextMenuTrigger :asChild="true">
                                        <tr class="contents cursor-context-menu">
                                            <TableCell>{{ category.id }}</TableCell>
                                            <TableCell>{{ category.name }}</TableCell>
                                            <TableCell>{{ category.slug }}</TableCell>
                                        </tr>
                                    </ContextMenuTrigger>
                                    <ContextMenuContent>
                                        <ContextMenuItem @click="editCategory(category)">
                                            <Icon name="edit" size="16" class="mr-2" />
                                            Edytuj
                                        </ContextMenuItem>
                                        <ContextMenuSeparator />
                                        <ContextMenuItem @click="deleteCategory(category.id)" class="text-red-600 cursor-pointer">
                                            <Icon name="trash2" size="16" class="mr-2" />
                                            Usuń
                                        </ContextMenuItem>
                                    </ContextMenuContent>
                                </ContextMenu>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>
            </div>


            <Dialog v-model:open="isDialogOpen">
                <DialogContent class="sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle>{{ isEditing ? 'Edytuj kategorię' : 'Dodaj nową kategorię' }}</DialogTitle>
                    </DialogHeader>
                    <form @submit.prevent="saveCategory" class="space-y-4 pt-4">
                        <div>
                            <Label for="name" class="mb-2">Nazwa kategorii</Label>
                            <Input id="name" v-model="form.name" placeholder="Wpisz nazwę nowej kategorii" />
                            <p v-if="formErrors.name" class="mt-2 text-sm text-red-600">
                                {{ formErrors.name[0] }}
                            </p>
                        </div>
                        <div>
                            <Label for="slug" class="mb-2">Opis kategorii</Label>
                            <textarea
                                id="slug"
                                v-model="form.slug"
                                rows="4"
                                class="w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                placeholder="Wprowadź szczegółowy opis kategorii zabiegów"
                            ></textarea>
                            <p v-if="formErrors.slug" class="mt-2 text-sm text-red-600">
                                {{ formErrors.slug[0] }}
                            </p>
                        </div>
                        <DialogFooter>
                            <Button type="button" variant="outline" @click="isDialogOpen = false">Anuluj</Button>
                            <Button type="submit" class="bg-nova-primary text-nova-light hover:bg-nova-accent dark:bg-nova-accent hover:dark:bg-nova-primary">{{ isEditing ? 'Zapisz zmiany' : 'Dodaj kategorię' }}</Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </div>
    </AppLayout>
</template>

<style scoped>
table {
    border-collapse: separate;
    border-spacing: 0;
    width: 100%;
}

th:first-child {
    border-top-left-radius: 0.5rem;
}

th:last-child {
    border-top-right-radius: 0.5rem;
}

tr:last-child td:first-child {
    border-bottom-left-radius: 0.5rem;
}

tr:last-child td:last-child {
    border-bottom-right-radius: 0.5rem;
}

:deep(thead th) {
    background-color: #f9fafb;
    color: #374151;
    font-weight: 600;
    text-align: left;
    padding: 0.75rem 1rem;
    font-size: 0.875rem;
}

:deep(tbody td) {
    padding: 1rem !important;
    vertical-align: middle;
}
</style>
