<script setup lang="ts">
import {ref, onMounted, computed} from 'vue';
import axios from 'axios';
import AppLayout from '@/layouts/AppLayout.vue';
import {Button} from '@/components/ui/button';
import {Input} from '@/components/ui/input'
import {InputError} from '@/components/ui/input-error';
import {TableHeader, TableRow, TableCell, TableHead, Table, TableBody} from '@/components/ui/table';
import {Card, CardHeader, CardTitle, CardDescription, CardContent} from '@/components/ui/card';
import {Badge} from '@/components/ui/badge';
import InputNumber from 'primevue/inputnumber';
import Icon from '@/components/Icon.vue';
import {Search} from 'lucide-vue-next';
import FloatLabel from 'primevue/floatlabel';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger
} from '@/components/ui/tooltip'
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {Textarea} from '@/components/ui/textarea'
import {Label} from '@/components/ui/label'
import {useToast} from 'primevue/usetoast';
import {
    Pagination,
    PaginationEllipsis,
    PaginationFirst,
    PaginationPrevious,
    PaginationLast,
    PaginationNext,
} from '@/components/ui/pagination';
import {PaginationList, PaginationListItem} from 'reka-ui';
import type {BreadcrumbItem} from "@/types";
import {ScrollArea} from "@/components/ui/scroll-area";
import {Separator} from "@/components/ui/separator";

const toast = useToast();
const loading = ref(false);
const error = ref<string | null>(null);
const procedures = ref<any[]>([]);
const categories = ref<any[]>([]);
const selectedProcedure = ref<any>(null);

const searchQuery = ref('');
const selectedCategory = ref<string | null>(null);

const currentPage = ref(1);
const itemsPerPage = ref(10);
const totalItems = ref(0);
const totalPages = ref(0);

const showAddForm = ref(false);
const showEditForm = ref(false);
const formLoading = ref(false);
const formErrors = ref<Record<string, string[]>>({});
const newProcedure = ref({
    name: '',
    description: '',
    base_price: 0,
    procedure_category_id: null,
    recovery_timeline_info: '',
});

const loadProcedures = async () => {
    loading.value = true;
    error.value = null;
    try {
        const params = new URLSearchParams();
        params.append('page', currentPage.value.toString());
        params.append('per_page', itemsPerPage.value.toString());

        if (searchQuery.value) params.append('search', searchQuery.value);
        if (selectedCategory.value) params.append('category_id', selectedCategory.value);

        const url = `/api/v1/admin/procedures?${params.toString()}`;
        console.log('Wywołanie API:', url);

        const response = await axios.get(url);
        procedures.value = response.data.data;
        totalItems.value = response.data.meta.total;
        totalPages.value = response.data.meta.last_page;
    } catch (err: any) {
        console.error('Błąd podczas pobierania procedur:', err);
        error.value = err.response?.data?.message || 'Wystąpił błąd podczas ładowania danych.';
        showErrorToast('Błąd', 'Nie udało się załadować procedur');
    } finally {
        loading.value = false;
    }
};

const loadCategories = async () => {
    try {
        const response = await axios.get('/api/v1/admin/procedures/categories');
        categories.value = response.data.data;
        console.log('Załadowane kategorie:', categories.value);
    } catch (err: any) {
        console.error('Błąd podczas pobierania kategorii:', err);
        showErrorToast('Błąd', 'Nie udało się załadować kategorii procedur');
    }
};

const addProcedure = async () => {
    formLoading.value = true;
    formErrors.value = {};

    try {
        const response = await axios.post('/api/v1/admin/procedures', newProcedure.value);
        procedures.value = [response.data.data, ...procedures.value];
        showAddForm.value = false;
        resetForm();
        showSuccessToast('Sukces', 'Procedura została dodana');
        await loadProcedures();
    } catch (err: any) {
        console.error('Błąd podczas dodawania procedury:', err);
        formErrors.value = err.response?.data?.errors || {};
        showErrorToast('Błąd', 'Nie udało się dodać procedury');
    } finally {
        formLoading.value = false;
    }
};

const editProcedure = (procedure: any) => {
    selectedProcedure.value = {...procedure};
    showEditForm.value = true;
};

const updateProcedure = async () => {
    if (!selectedProcedure.value) return;

    formLoading.value = true;
    formErrors.value = {};

    try {
        await axios.put(`/api/v1/admin/procedures/${selectedProcedure.value.id}`, selectedProcedure.value);
        showEditForm.value = false;
        showSuccessToast('Sukces', 'Procedura została zaktualizowana');
        await loadProcedures();
    } catch (err: any) {
        console.error('Błąd podczas aktualizacji procedury:', err);
        formErrors.value = err.response?.data?.errors || {};
        showErrorToast('Błąd', 'Nie udało się zaktualizować procedury');
    } finally {
        formLoading.value = false;
    }
};

const deleteProcedure = async (id: number) => {
    if (!confirm('Czy na pewno chcesz usunąć tę procedurę?')) return;

    try {
        await axios.delete(`/api/v1/admin/procedures/${id}`);
        await loadProcedures();
        showSuccessToast('Sukces', 'Procedura została usunięta');
    } catch (err: any) {
        console.error('Błąd podczas usuwania procedury:', err);
        showErrorToast('Błąd', 'Nie udało się usunąć procedury');
    }
};

const goToPage = (page: number) => {
    currentPage.value = page;
    loadProcedures();
};

const resetForm = () => {
    newProcedure.value = {
        name: '',
        description: '',
        base_price: 0,
        procedure_category_id: null,
        recovery_timeline_info: ''
    };
    formErrors.value = {};
};

const truncateText = (text: string, maxLength: number): string => {
    if (!text || text.length <= maxLength) return text;
    return text.substring(0, maxLength) + '...';
};


const resetFilters = () => {
    searchQuery.value = '';
    selectedCategory.value = null;
    currentPage.value = 1;
    loadProcedures();
};
// Toasty
const showSuccessToast = (summary: string, detail: string) => {
    toast.add({
        severity: 'success',
        summary,
        detail,
        life: 3000
    });
};

const showErrorToast = (summary: string, detail: string) => {
    toast.add({
        severity: 'error',
        summary,
        detail,
        life: 5000
    });
};

onMounted(() => {
    loadProcedures();
    loadCategories();
});

const formatPrice = (price: number) => {
    return new Intl.NumberFormat('pl-PL', {
        style: 'currency',
        currency: 'PLN'
    }).format(price);
};

const breadcrumbs: BreadcrumbItem[] = [
    {title: 'Zarządzanie zabiegamiami'},
];

const recoveryPlaceholder = `Dzień 1-3: ...
Tydzień 1-2: ...
Miesiąc 1-2: ...
Miesiąc 3-6: ...`;

</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="container mx-auto py-6 px-2 sm:px-4 lg:px-6">
            <div class="rounded-lg border border-border  shadow-sm bg-card mb-3 md:mb-4 overflow-hidden">
                <div class="p-2 sm:p-4 border-b">
                    <div class="flex flex-col  sm:flex-row justify-between items-start sm:items-center">
                        <div>
                            <h2 class="text-2xl font-bold leading-7 text-foreground">Zabiegi Medyczne</h2>
                            <p class="text-sm text-muted-foreground mt-1">Zarządzaj dostępnymi zabiegami</p>
                        </div>
                        <Button @click="showAddForm = true"
                                class="mt-2 sm:mt-0 bg-nova-primary hover:bg-nova-accent dark:bg-nova-accent dark:hover:bg-nova-primary dark:text-nova-light">
                            <Icon name="plus" size="16" class="mr-2"/>
                            Dodaj Zabieg
                        </Button>
                    </div>
                </div>
                <div class="p-4 sm:p-6">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="relative">
                            <Label for="search" class="mb-1">Wyszukaj</Label>
                            <div class="relative">
                                <Input
                                    id="search"
                                    v-model="searchQuery"
                                    placeholder="Wpisz nazwę zabiegu..."
                                    @keyup.enter="currentPage = 1; loadProcedures()"
                                    class="pr-10"
                                />
                                <div class="absolute inset-y-0 right-0 flex items-center pr-3 cursor-pointer"
                                     @click="currentPage = 1; loadProcedures()">
                                    <Search class="h-4 w-4 text-gray-400"/>
                                </div>
                            </div>
                        </div>
                        <div>
                            <Label for="category" class="mb-1 w-full sm:w-1/2">Kategoria</Label>
                            <Select v-model="selectedCategory"
                                    @update:modelValue="() => { currentPage = 1; loadProcedures() }">
                                <SelectTrigger id="category" class="w-full">
                                    <SelectValue :placeholder="'Wybierz kategorię'"/>
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="category in categories" :key="category.id" :value="category.id">
                                        {{ category.name }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <Button
                                v-if="selectedCategory"
                                variant="outline"
                                size="sm"
                                @click="resetFilters"
                                class="mt-2">
                                <Icon name="x-circle" size="14" class="mr-1"/>
                                Wyczyść filtry
                            </Button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="rounded-lg border border-border shadow-sm bg-card mb-4 overflow-hidden">
                <div v-if="loading" class="flex justify-center items-center p-8">
                    <Icon name="loader2" size="32" class="animate-spin text-nova-primary"/>
                </div>

                <div v-else-if="error" class="p-6 text-center text-red-500">
                    {{ error }}
                </div>

                <div v-else-if="procedures.length === 0" class="p-6 text-center text-gray-500">
                    Nie znaleziono żadnych procedur.
                    <Button variant="link" @click="resetFilters">Wyczyść filtry</Button>
                </div>

                <div v-else class="w-full overflow-x-auto dark:bg-gray-900">
                    <ScrollArea class="w-full h-[clamp(250px,calc(100vh-400px),500px)]">
                        <Table class="w-full">
                            <TableHeader class="sticky top-0 bg-card z-10">
                                <TableRow class="border-b border-border">
                                    <TableHead class="whitespace-nowrap">Nazwa</TableHead>
                                    <TableHead class="max-w-[40%]">Opis</TableHead>
                                    <TableHead class="whitespace-nowrap">Kategoria</TableHead>
                                    <TableHead class="text-center whitespace-nowrap">Cena Bazowa</TableHead>
                                    <TableHead class="text-center whitespace-nowrap">Akcje</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="procedure in procedures" :key="procedure.id"
                                          class="border-b border-border hover:bg-muted/50">
                                    <TableCell class="font-medium">{{ procedure.name }}</TableCell>
                                    <TableCell class="text-sm text-gray-500 max-w-[200px] min-w-[150px]">
                                        <div v-if="procedure.description" class="truncate">
                                            <span :title="procedure.description">{{ truncateText(procedure.description, 40) }}</span>
                                        </div>
                                        <div v-else class="text-gray-400">Brak opisu</div>
                                    </TableCell>
                                    <TableCell>
                                        <Badge variant="outline" class="bg-nova-primary dark:bg-nova-accent text-nova-light">
                                            {{ procedure.category?.name || 'Brak kategorii' }}
                                        </Badge>
                                    </TableCell>
                                    <TableCell class="text-left">{{ formatPrice(procedure.base_price) }}</TableCell>
                                    <TableCell class="text-left">
                                        <TooltipProvider class="flex space-x-1">
                                            <Tooltip>
                                                <TooltipTrigger>
                                                    <Button variant="ghost" size="sm" @click="editProcedure(procedure)">
                                                        <Icon name="edit" size="16"/>
                                                    </Button>
                                                </TooltipTrigger>
                                                <TooltipContent>
                                                    <p>Edytuj</p>
                                                </TooltipContent>
                                            </Tooltip>

                                            <Tooltip>
                                                <TooltipTrigger>
                                                    <Button variant="ghost" size="sm" @click="deleteProcedure(procedure.id)">
                                                        <Icon name="trash" size="16" class="text-red-500"/>
                                                    </Button>
                                                </TooltipTrigger>
                                                <TooltipContent>
                                                    <p>Usuń</p>
                                                </TooltipContent>
                                            </Tooltip>
                                        </TooltipProvider>
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </ScrollArea>
                </div>

                <div v-if="totalPages > 1 && procedures.length > 0"
                     class="flex justify-center items-center p-3 sm:p-4 border-t border-border">
                    <Pagination
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
                                        :variant="currentPage === item.value ? 'default' : 'outline'"
                                        :class="currentPage === item.value ? 'bg-nova-primary hover:bg-nova-accent text-white' : ''"
                                        size="sm"
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

        <div v-if="showAddForm" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
            <Card class="max-w-xl w-full mx-auto shadow-lg max-h-[90vh] overflow-y-auto">
                <CardHeader class="flex justify-between items-center border-b">
                    <CardTitle>Dodaj nowy zabieg</CardTitle>
                    <Button variant="ghost" size="icon" @click="showAddForm = false">
                        <Icon name="x" size="18"/>
                    </Button>
                </CardHeader>
                <CardContent class="pt-4">
                    <form class="space-y-4" @submit.prevent="addProcedure">
                        <div class="space-y-2">
                            <Label for="name">Nazwa</Label>
                            <Input id="name" v-model="newProcedure.name" placeholder="Wpisz nazwę zabiegu"
                                   :class="{'border-red-500': formErrors.name}"/>
                            <InputError :message="formErrors.name?.[0]"/>
                        </div>

                        <div class="space-y-2">
                            <Label for="category_id">Kategoria</Label>
                            <Select v-model="newProcedure.procedure_category_id">
                            <SelectTrigger id="category_id" class="w-full">
                                    <SelectValue :placeholder="'Wybierz kategorię'"/>
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="category in categories" :key="category.id" :value="category.id">
                                        {{ category.name }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <InputError :message="formErrors.category_id?.[0]"/>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="space-y-2">
                                <Label for="base_price">Cena Bazowa (PLN)</Label>
                                <InputNumber
                                    id="base_price"
                                    v-model="newProcedure.base_price"
                                    :min="0"
                                    :step="10"
                                    currency="PLN"
                                    mode="currency"
                                    locale="pl-PL"
                                    inputId="locale-pl-PL"
                                    showButtons
                                    buttonLayout="horizontal"
                                    inputClass="w-full rounded-md border border-input px-3 py-2 text-sm"
                                    class="w-full rounded-md border border-input px-3 py-2 text-sm"
                                    :class="{'p-invalid': formErrors.base_price}"
                                >
                                    <template #incrementbuttonicon>
                                        <Icon name="plus" size="14"/>
                                    </template>
                                    <template #decrementbuttonicon>
                                        <Icon name="minus" size="14" class="mr-2"/>
                                    </template>
                                </InputNumber>
                                <InputError :message="formErrors.base_price?.[0]"/>
                            </div>
                        </div>

                        <div class="space-y-2">
                            <Label for="description">Opisz zabieg</Label>
                            <Textarea
                                id="description"
                                v-model="newProcedure.description"
                                placeholder="Tutaj możesz wpisać opis zabiegu"
                                class="w-full resize-none"
                                :class="{'border-red-500': formErrors.description}"
                            />
                            <InputError :message="formErrors.description?.[0]"/>
                        </div>

                        <div class="space-y-2">
                            <Label for="recovery_info">Informacje o Rekonwalescencji</Label>
                            <FloatLabel>
                                <Textarea
                                    id="recovery_timeline_info"
                                    v-model="newProcedure.recovery_timeline_info"
                                    rows="5"
                                    class="w-full resize-none"
                                    :class="{'p-invalid': formErrors.recovery_info}"
                                    :placeholder="recoveryPlaceholder"
                                />
                            </FloatLabel>
                            <p class="text-xs mt-1 ml-1 text-gray-500">Format: Dzień/Tydzień/Miesiąc: opis</p>
                            <InputError :message="formErrors.recovery_info?.[0]"/>
                        </div>

                        <div class="flex justify-end gap-3 pt-2">
                            <Button type="button" variant="outline" @click="showAddForm = false">Anuluj</Button>
                            <Button
                                type="submit"
                                :disabled="formLoading"
                                class="flex bg-nova-primary hover:bg-nova-accent dark:bg-nova-accent dark:hover:bg-nova-primary dark:text-nova-light items-center gap-2"
                            >
                                <Icon v-if="formLoading" name="loader2" class="animate-spin" size="16"/>
                                <span>Zapisz Procedurę</span>
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </div>

        <div v-if="showEditForm && selectedProcedure"
             class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
            <Card class="max-w-xl w-full mx-auto shadow-lg max-h-[90vh] overflow-y-auto">
                <CardHeader class="flex justify-between items-center border-b">
                    <CardTitle>Edytuj zabieg</CardTitle>
                    <Button variant="ghost" size="icon" @click="showEditForm = false">
                        <Icon name="x" size="18"/>
                    </Button>
                </CardHeader>
                <CardContent class="pt-4">
                    <form class="space-y-4" @submit.prevent="updateProcedure">
                        <div class="space-y-2">
                            <Label for="edit-name">Nazwa</Label>
                            <Input id="edit-name" v-model="selectedProcedure.name"
                                   :class="{'border-red-500': formErrors.name}"/>
                            <InputError :message="formErrors.name?.[0]"/>
                        </div>

                        <div class="space-y-2">
                            <Label for="edit-category_id">Kategoria</Label>
                            <Select v-model="selectedProcedure.procedure_category_id">
                                <SelectTrigger id="edit-category_id" class="w-full">
                                    <SelectValue :placeholder="'Wybierz kategorię'"/>
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="category in categories" :key="category.id" :value="category.id">
                                        {{ category.name }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <InputError :message="formErrors.category_id?.[0]"/>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="space-y-2">
                                <Label for="edit-base_price">Cena bazowa</Label>
                                <InputNumber
                                    id="edit-base_price"
                                    v-model="selectedProcedure.base_price"
                                    :min="0"
                                    :step="10"
                                    showButtons
                                    currency="PLN"
                                    mode="currency"
                                    buttonLayout="horizontal"
                                    inputClass="w-full rounded-md border border-input px-3 py-2 text-sm"
                                    class="w-full rounded-md border border-input px-3 py-2 text-sm"
                                    :class="{'p-invalid': formErrors.base_price}"
                                >
                                    <template #incrementbuttonicon>
                                        <Icon name="plus" size="14"/>
                                    </template>
                                    <template #decrementbuttonicon>
                                        <Icon name="minus" size="14" class="mr-2"/>
                                    </template>
                                </InputNumber>
                                <InputError :message="formErrors.base_price?.[0]"/>
                            </div>
                        </div>

                        <div class="space-y-2">
                            <Label for="edit-description">Opis</Label>
                            <FloatLabel>
                                <Textarea
                                    id="edit-description"
                                    v-model="selectedProcedure.description"
                                    rows="3"
                                    class="w-full resize-none"
                                    :class="{'p-invalid': formErrors.description}"
                                />
                            </FloatLabel>
                            <InputError :message="formErrors.description?.[0]"/>
                        </div>

                        <div class="space-y-2">
                            <Label for="edit-recovery_info">Informacje o Rekonwalescencji</Label>
                            <FloatLabel>
                                <Textarea
                                    id="edit-recovery_timeline_info"
                                    v-model="selectedProcedure.recovery_timeline_info"
                                    rows="5"
                                    class="w-full resize-none"
                                    :class="{'p-invalid': formErrors.recovery_info}"
                                    :placeholder="recoveryPlaceholder"
                                />
                            </FloatLabel>
                            <p class="text-xs text-gray-500">Format: Dzień/Tydzień/Miesiąc: opis</p>
                            <InputError :message="formErrors.recovery_info?.[0]"/>
                        </div>

                        <div class="flex justify-end gap-3 pt-2">
                            <Button type="button" variant="outline" @click="showEditForm = false">Anuluj</Button>
                            <Button
                                type="submit"
                                :disabled="formLoading"
                                class="flex bg-nova-primary hover:bg-nova-accent dark:bg-nova-accent dark:hover:bg-nova-primary dark:text-nova-light items-center gap-2"
                            >
                                <Icon v-if="formLoading" name="loader2" class="animate-spin" size="16"/>
                                <span>Aktualizuj Procedurę</span>
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>

<style scoped>
@media (max-width: 768px) {
    :deep(th), :deep(td) {
        padding: 0.5rem;
        font-size: 0.875rem;
    }
}


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
