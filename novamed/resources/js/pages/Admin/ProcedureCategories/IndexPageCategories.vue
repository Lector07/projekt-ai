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

// Stan aplikacji
const categories = ref<Category[]>([]);
const loading = ref(true);
const error = ref(false);
const toast = useToast();

// Dialog/formularz
const isDialogOpen = ref(false);
const isEditing = ref(false);
const form = reactive({
    id: null as number | null,
    name: '',
});
const formErrors = ref<Record<string, string[]>>({});

// Pobieranie kategorii z prawidłowej tabeli
const fetchCategories = async () => {
    loading.value = true;
    error.value = false;

    try {
        // Dodane logowanie
        console.log('Pobieranie kategorii...');
        const response = await axios.get('/api/admin/procedure-categories');
        console.log('Odpowiedź API:', response);

        // Sprawdź strukturę odpowiedzi
        if (response.data && Array.isArray(response.data)) {
            // Gdy API zwraca bezpośrednio tablicę
            categories.value = response.data;
        } else if (response.data && response.data.data && Array.isArray(response.data.data)) {
            // Gdy API zwraca obiekt z właściwością data
            categories.value = response.data.data;
        } else {
            // Nieprawidłowa struktura odpowiedzi
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

// Dodawanie nowej kategorii
const openNewCategoryDialog = () => {
    isEditing.value = false;
    form.id = null;
    form.name = '';
    formErrors.value = {};
    isDialogOpen.value = true;
};

// Edycja kategorii
const editCategory = (category: Category) => {
    isEditing.value = true;
    form.id = category.id;
    form.name = category.name;
    formErrors.value = {};
    isDialogOpen.value = true;
};

// Zapisywanie kategorii (nowej lub edytowanej)
const saveCategory = async () => {
    formErrors.value = {};

    try {
        if (isEditing.value) {
            // Aktualizacja
            await axios.put(`/api/admin/procedure-categories/${form.id}`, form);
            toast.add({ severity: 'success', summary: 'Sukces', detail: 'Kategoria została zaktualizowana.', life: 3000 });
        } else {
            // Tworzenie
            await axios.post('/api/admin/procedure-categories', form);
            toast.add({ severity: 'success', summary: 'Sukces', detail: 'Kategoria została dodana.', life: 3000 });
        }

        isDialogOpen.value = false;
        fetchCategories(); // Odśwież dane
    } catch (err: any) {
        if (err.response && err.response.status === 422) {
            formErrors.value = err.response.data.errors;
        } else {
            toast.add({ severity: 'error', summary: 'Błąd', detail: 'Wystąpił błąd podczas zapisywania kategorii.', life: 3000 });
            console.error(err);
        }
    }
};

// Usuwanie kategorii
const deleteCategory = async (id: number) => {
    if (!confirm('Czy na pewno chcesz usunąć tę kategorię?')) return;

    try {
        await axios.delete(`/api/admin/procedure-categories/${id}`);
        toast.add({ severity: 'success', summary: 'Sukces', detail: 'Kategoria została usunięta.', life: 3000 });
        fetchCategories(); // Odśwież dane
    } catch (err) {
        toast.add({ severity: 'error', summary: 'Błąd', detail: 'Nie udało się usunąć kategorii.', life: 3000 });
        console.error(err);
    }
};

// Formatowanie daty
const formatDate = (dateString?: string) => {
    if (!dateString) return '';
    return new Date(dateString).toLocaleDateString('pl-PL');
};

onMounted(() => {
    fetchCategories();
});
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="container mx-auto py-6 px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-semibold">Kategorie procedur</h1>
                <Button @click="openNewCategoryDialog">
                    <Icon name="plus" size="16" class="mr-2" />
                    Dodaj kategorię
                </Button>
            </div>

            <!-- Skeleton podczas ładowania -->
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

            <!-- Błąd -->
            <div v-else-if="error" class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 text-center">
                <p class="text-red-500 mb-4">
                    Wystąpił błąd podczas pobierania danych.
                </p>
                <Button @click="fetchCategories">Spróbuj ponownie</Button>
            </div>

            <!-- Tabela kategorii -->
            <div v-else class="bg-white dark:bg-gray-800 rounded-xl shadow-sm overflow-hidden">
                <ScrollArea class="w-full h-[clamp(250px,calc(100vh-300px),600px)]">
                    <div class="overflow-x-auto">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead class="w-16">ID</TableHead>
                                    <TableHead>Nazwa</TableHead>
                                    <TableHead>Slug</TableHead>
                                    <TableHead>Data utworzenia</TableHead>
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
                                                <TableCell>{{ formatDate(category.created_at) }}</TableCell>
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
                </ScrollArea>
            </div>

            <!-- Dialog formularz dodawania/edycji kategorii -->
            <Dialog v-model:open="isDialogOpen">
                <DialogContent class="sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle>{{ isEditing ? 'Edytuj kategorię' : 'Dodaj nową kategorię' }}</DialogTitle>
                    </DialogHeader>
                    <form @submit.prevent="saveCategory" class="space-y-4 pt-4">
                        <div>
                            <Label for="name">Nazwa kategorii</Label>
                            <Input id="name" v-model="form.name" />
                            <p v-if="formErrors.name" class="mt-1 text-sm text-red-600">
                                {{ formErrors.name[0] }}
                            </p>
                        </div>
                        <DialogFooter>
                            <Button type="button" variant="outline" @click="isDialogOpen = false">Anuluj</Button>
                            <Button type="submit">{{ isEditing ? 'Zapisz zmiany' : 'Dodaj kategorię' }}</Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </div>
    </AppLayout>
</template>
