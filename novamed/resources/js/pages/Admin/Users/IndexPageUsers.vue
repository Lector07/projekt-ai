<script setup lang="ts">
import {ref, onMounted, watch, computed} from 'vue';
import {useRouter} from 'vue-router';
import axios from 'axios';
import AppLayout from '@/layouts/AppLayout.vue';
import {Button} from '@/components/ui/button';
import {Input} from '@/components/ui/input';
import {Label} from '@/components/ui/label';
import {Skeleton} from '@/components/ui/skeleton';
import Icon from '@/components/Icon.vue';
import type {BreadcrumbItem} from '@/types';
import {
    Table,
    TableBody,
    TableCaption,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from "@/components/ui/table";
import {
    Pagination,
    PaginationEllipsis,
    PaginationFirst,
    PaginationPrevious,
    PaginationLast,
    PaginationNext
} from '@/components/ui/pagination';
import {PaginationList, PaginationListItem} from 'reka-ui';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from "@/components/ui/popover";
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";
import Toast from 'primevue/toast';
import { useToast } from 'primevue/usetoast';
import {
    ContextMenu,
    ContextMenuContent,
    ContextMenuItem,
    ContextMenuSeparator,
    ContextMenuTrigger,
} from '@/components/ui/context-menu';

import {
    ScrollArea,
    ScrollBar
} from "@/components/ui/scroll-area";
import {TooltipContent} from "@/components/ui/tooltip";

// Interfejs dla danych użytkownika
interface User {
    id: number;
    name: string;
    email: string;
    role: string;
    roles?: string[];
    created_at: string;
    password?: string;
    password_confirmation?: string;
}

// Parametry zapytania
const query = ref({
    page: 1,
    per_page: 12,
    search: '',
    role: ''
});

// Zmienne reaktywne
const users = ref<User[]>([]);
const loading = ref(true);
const error = ref<string | null>(null);
const totalPages = ref(0);
const totalItems = ref(0);
const currentPage = computed(() => query.value.page);
const itemsPerPage = computed(() => query.value.per_page);

// Stan dla popoverów
const showAddUserForm = ref(false);
const showEditUserForm = ref(false);
const selectedUser = ref<User | null>(null);
const userFormLoading = ref(false);
const userErrors = ref<Record<string, string[]>>({});

// Formularz nowego użytkownika
const newUser = ref<User>({
    id: 0,
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    role: 'patient',
    created_at: ''
});

const router = useRouter();
const toast = useToast();

// Okruszki dla nawigacji
const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Zarządzanie Użytkownikami',
        href: '/admin/users',
    }
];

// Funkcja do ładowania użytkowników
const loadUsers = async () => {
    loading.value = true;
    error.value = null;

    try {
        const params = new URLSearchParams();
        params.append('page', query.value.page.toString());
        params.append('per_page', query.value.per_page.toString());

        if (query.value.search) {
            params.append('search', query.value.search);
        }

        if (query.value.role) {
            params.append('role', query.value.role);
        }

        const response = await axios.get(`/api/v1/admin/users?${params.toString()}`);

        users.value = response.data.data;
        totalPages.value = response.data.meta.last_page;
        totalItems.value = response.data.meta.total;
    } catch (err: any) {
        console.error('Błąd podczas pobierania użytkowników:', err);
        error.value = err.response?.data?.message || 'Wystąpił błąd podczas ładowania danych.';
    } finally {
        loading.value = false;
    }
};

// Resetowanie strony przy zmianie filtrów
const resetPagination = () => {
    query.value.page = 1;
};

// Funkcja do nawigacji między stronami
const goToPage = (page: number) => {
    query.value.page = page;
};

// Konfiguracja niestandardowych ikon dla toastów
const toastIcons = {
    successIcon: 'pi pi-check-circle',
    infoIcon: 'pi pi-info-circle',
    warnIcon: 'pi pi-exclamation-triangle',
    errorIcon: 'pi pi-times-circle',
    closeIcon: 'pi pi-times'
};

// Funkcje do wyświetlania toastów z dodatkowymi opcjami
const showSuccessToast = (summary: string, detail: string) => {
    toast.add({
        severity: 'success',
        summary,
        detail,
        life: 3000,
        styleClass: 'custom-toast-success',
        contentStyleClass: 'custom-toast-content'
    });
};

const showInfoToast = (summary: string, detail: string) => {
    toast.add({
        severity: 'info',
        summary,
        detail,
        life: 3000,
        styleClass: 'custom-toast-info',
        contentStyleClass: 'custom-toast-content'
    });
};

const showWarnToast = (summary: string, detail: string) => {
    toast.add({
        severity: 'warn',
        summary,
        detail,
        life: 3000,
        styleClass: 'custom-toast-warn',
        contentStyleClass: 'custom-toast-content'
    });
};

const showErrorToast = (summary: string, detail: string) => {
    toast.add({
        severity: 'error',
        summary,
        detail,
        life: 3000,
        styleClass: 'custom-toast-error',
        contentStyleClass: 'custom-toast-content'
    });
};

// Funkcja otwierająca formularz edycji
const editUser = (user: User) => {
    if (user.role === 'admin') {
        showErrorToast('Błąd', 'Nie można edytować administratora systemu');
        return;
    }
    selectedUser.value = { ...user };
    showEditUserForm.value = true;
};

// Czyszczenie formularza użytkownika
const resetUserForm = () => {
    newUser.value = {
        id: 0,
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
        role: 'patient',
        created_at: ''
    };
    userErrors.value = {};
};

// Walidacja formularza
const validateUserForm = (user: User) => {
    const errors: Record<string, string[]> = {};

    if (!user.name) errors.name = ['Imię i nazwisko jest wymagane'];
    if (!user.email) errors.email = ['Email jest wymagany'];
    else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(user.email)) {
        errors.email = ['Podaj prawidłowy adres email'];
    }

    // Walidacja hasła tylko dla nowego użytkownika lub gdy hasło jest zmieniane
    if (!user.id) {
        if (!user.password) errors.password = ['Hasło jest wymagane'];
        else if (user.password.length < 8) {
            errors.password = ['Hasło musi mieć co najmniej 8 znaków'];
        }

        if (user.password !== user.password_confirmation) {
            errors.password_confirmation = ['Hasła nie są identyczne'];
        }
    } else if (user.password) {
        if (user.password.length < 8) {
            errors.password = ['Hasło musi mieć co najmniej 8 znaków'];
        }

        if (user.password !== user.password_confirmation) {
            errors.password_confirmation = ['Hasła nie są identyczne'];
        }
    }

    userErrors.value = errors;
    return Object.keys(errors).length === 0;
};

// Dodawanie nowego użytkownika
const addUser = async () => {
    if (!validateUserForm(newUser.value)) return;

    userFormLoading.value = true;

    try {
        await axios.post('/api/v1/admin/users', newUser.value);
        showAddUserForm.value = false;
        resetUserForm();
        loadUsers();
        showSuccessToast('Sukces', 'Użytkownik został dodany pomyślnie');
    } catch (err: any) {
        if (err.response?.status === 422) {
            userErrors.value = err.response.data.errors;
        } else {
            showErrorToast('Błąd', err.response?.data?.message || 'Wystąpił błąd podczas dodawania użytkownika');
        }
    } finally {
        userFormLoading.value = false;
    }
};

// Aktualizacja użytkownika
const updateUser = async () => {
    if (!selectedUser.value || !validateUserForm(selectedUser.value)) return;

    userFormLoading.value = true;

    // Przygotuj dane do wysłania - usuń puste hasło
    const userData = { ...selectedUser.value };
    if (!userData.password) {
        delete userData.password;
        delete userData.password_confirmation;
    }

    try {
        await axios.put(`/api/v1/admin/users/${selectedUser.value.id}`, userData);
        showEditUserForm.value = false;
        selectedUser.value = null;
        loadUsers();
        toast.add({
            severity: 'success',
            summary: 'Sukces',
            detail: 'Użytkownik został zaktualizowany pomyślnie',
            life: 3000
        });
    } catch (err: any) {
        if (err.response?.status === 422) {
            userErrors.value = err.response.data.errors;
        } else {
            toast.add({
                severity: 'error',
                summary: 'Błąd',
                detail: err.response?.data?.message || 'Wystąpił błąd podczas aktualizacji użytkownika',
                life: 5000
            });
        }
    } finally {
        userFormLoading.value = false;
    }
};

// Funkcja do usuwania użytkownika
const deleteUser = async (id: number) => {
    const user = users.value.find(u => u.id === id);
    if (!user) return;
    if (user.role === 'admin') {
        toast.add({
            severity: 'error',
            summary: 'Błąd',
            detail: 'Nie można usunąć administratora systemu',
            life: 3000
        });
        return;
    }

    if (!confirm('Czy na pewno chcesz usunąć tego użytkownika?')) {
        return;
    }

    try {
        await axios.delete(`/api/v1/admin/users/${id}`);
        toast.add({
            severity: 'success',
            summary: 'Sukces',
            detail: 'Użytkownik został usunięty pomyślnie',
            life: 3000
        });
        loadUsers();
    } catch (err: any) {
        console.error('Błąd podczas usuwania użytkownika:', err);
        toast.add({
            severity: 'error',
            summary: 'Błąd',
            detail: err.response?.data?.message || 'Wystąpił błąd podczas usuwania użytkownika',
            life: 5000
        });
    }
};

// Formatowanie daty
const formatDate = (dateString: string) => {
    const date = new Date(dateString);
    return new Intl.DateTimeFormat('pl-PL').format(date);
};

// Śledzenie zmian w zapytaniu
watch(() => query.value.search, resetPagination);
watch(() => query.value.role, resetPagination);
watch(query, loadUsers, {deep: true});

onMounted(() => {
    loadUsers();
    resetUserForm();
});
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Toast
            position="top-right"
            v-bind="toastIcons"
            :breakpoints="{'640px': {width: '100%', right: '0', left: '0'}}"
        />
        <div class="flex flex-col gap-5 p-6">
            <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4">
                <h1 class="text-2xl font-bold text-nova-darkest dark:text-nova-light">Zarządzanie Użytkownikami</h1>

                <Button
                    variant="default"
                    class="flex bg-nova-primary hover:bg-nova-accent dark:bg-nova-accent hover:dark:bg-nova-primary dark:text-nova-light items-center gap-2"
                    @click="showAddUserForm = true"
                >
                    <Icon name="userPlus" size="18"/>
                    <span>Dodaj Nowego Użytkownika</span>
                </Button>
            </div>

            <div
                class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 bg-white dark:bg-gray-900 p-4 border rounded-xl">
                <div class="space-y-2">
                    <Label for="search">Wyszukiwanie</Label>
                    <div class="relative">
                        <Input
                            id="search"
                            v-model="query.search"
                            placeholder="Szukaj po imieniu, nazwisku..."
                            @keyup.enter="resetPagination(); loadUsers()"
                            class="dark:bg-background pr-10"
                        />
                        <div class="absolute inset-y-0 right-0 flex items-center pr-3 cursor-pointer" @click="resetPagination(); loadUsers()">
                            <Icon name="search" size="16" class="text-gray-400" />
                        </div>
                    </div>
                </div>

                <div class="space-y-2">
                    <Label for="roleFilter">Filtruj po roli</Label>
                    <select
                        id="roleFilter"
                        v-model="query.role"
                        class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        <option value="">Wszystkie role</option>
                        <option value="admin">Administrator</option>
                        <option value="patient">Pacjent</option>
                        <option value="doctor">Lekarz</option>
                    </select>
                </div>
            </div>

            <div v-if="loading" class="bg-white mt-2 dark:bg-gray-800 rounded-xl shadow-sm p-4">
                <div v-for="i in 5" :key="i" class="mb-3">
                    <Skeleton class="h-12 w-full"/>
                </div>
            </div>

            <div v-else-if="error"
                 class="p-6 bg-red-50 text-red-500 rounded-xl border border-red-100 dark:bg-red-900/20 dark:border-red-800 dark:text-red-400">
                {{ error }}
            </div>

            <div v-if="!loading && !error && users.length === 0"
                 class="p-8 text-center text-gray-500 bg-white dark:bg-gray-800 rounded-xl shadow-sm">
                Nie znaleziono użytkowników pasujących do kryteriów wyszukiwania.
            </div>

            <div v-else-if="!loading && !error" class="bg-white dark:bg-gray-800 rounded-xl shadow-sm overflow-hidden">
                <ScrollArea class="w-full h-[clamp(250px,calc(100vh-400px),500px)]">
                    <div class="overflow-x-auto">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead class="w-16">ID</TableHead>
                                    <TableHead>Imię i Nazwisko</TableHead>
                                    <TableHead>Email</TableHead>
                                    <TableHead>Rola</TableHead>
                                    <TableHead>Data utworzenia</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="user in users" :key="user.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                    <ContextMenu>
                                        <ContextMenuTrigger :asChild="true">
                                            <tr class="contents cursor-context-menu">
                                                <TableCell>{{ user.id }}</TableCell>
                                                <TableCell>{{ user.name }}</TableCell>
                                                <TableCell>{{ user.email }}</TableCell>
                                                <TableCell>
                        <span :class="`px-2 py-1 text-xs font-medium rounded-full ${
                            user.role === 'admin' ? 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300' :
                            user.role === 'doctor' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300' :
                            'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300'
                        }`">
                            {{ user.role === 'admin' ? 'Administrator' : user.role === 'doctor' ? 'Lekarz' : 'Pacjent' }}
                        </span>
                                                </TableCell>
                                                <TableCell>{{ formatDate(user.created_at) }}</TableCell>
                                            </tr>
                                        </ContextMenuTrigger>
                                        <ContextMenuContent>
                                            <ContextMenuItem @click="editUser(user)">
                                                <Icon name="edit" size="16" class="mr-2"/>
                                                Edytuj
                                            </ContextMenuItem>
                                            <ContextMenuSeparator v-if="user.role !== 'admin'" />
                                            <ContextMenuItem
                                                v-if="user.role !== 'admin'"
                                                @click="deleteUser(user.id)"
                                                class="text-red-600 cursor-pointer">
                                                <Icon name="trash2" size="16" class="mr-2"/>
                                                Usuń
                                            </ContextMenuItem>
                                        </ContextMenuContent>
                                    </ContextMenu>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>
                </ScrollArea>

                <div
                    class="flex justify-center items-center px-4 py-3  dark:bg-gray-700 border-t border-gray-200 dark:border-gray-600">
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
        </div>

        <!-- Modal do dodawania nowego użytkownika -->
        <div v-if="showAddUserForm" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
            <div class="bg-white dark:bg-gray-800 rounded-xl p-6 max-w-md w-full mx-4 shadow-lg">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-medium">Dodaj Nowego Użytkownika</h3>
                    <Button variant="ghost" class="h-8 w-8 p-0" @click="showAddUserForm = false">
                        <Icon name="x" size="18" />
                    </Button>
                </div>

                <div class="space-y-4">
                    <!-- Imię i nazwisko -->
                    <div class="space-y-2">
                        <Label for="new-name">Imię i nazwisko</Label>
                        <Input
                            id="new-name"
                            v-model="newUser.name"
                            :class="{'border-red-500': userErrors.name}"
                            placeholder="Wprowadź imię i nazwisko"
                        />
                        <div v-if="userErrors.name" class="text-sm text-red-500">
                            {{ userErrors.name[0] }}
                        </div>
                    </div>

                    <!-- Email -->
                    <div class="space-y-2">
                        <Label for="new-email">Email</Label>
                        <Input
                            id="new-email"
                            type="email"
                            v-model="newUser.email"
                            :class="{'border-red-500': userErrors.email}"
                            placeholder="Wprowadź adres email"
                        />
                        <div v-if="userErrors.email" class="text-sm text-red-500">
                            {{ userErrors.email[0] }}
                        </div>
                    </div>

                    <!-- Hasło -->
                    <div class="space-y-2">
                        <Label for="new-password">Hasło</Label>
                        <Input
                            id="new-password"
                            type="password"
                            v-model="newUser.password"
                            :class="{'border-red-500': userErrors.password}"
                            placeholder="Wprowadź hasło"
                        />
                        <div v-if="userErrors.password" class="text-sm text-red-500">
                            {{ userErrors.password[0] }}
                        </div>
                    </div>

                    <!-- Potwierdzenie hasła -->
                    <div class="space-y-2">
                        <Label for="new-password-confirmation">Potwierdzenie hasła</Label>
                        <Input
                            id="new-password-confirmation"
                            type="password"
                            v-model="newUser.password_confirmation"
                            :class="{'border-red-500': userErrors.password_confirmation}"
                            placeholder="Potwierdź hasło"
                        />
                        <div v-if="userErrors.password_confirmation" class="text-sm text-red-500">
                            {{ userErrors.password_confirmation[0] }}
                        </div>
                    </div>

                    <!-- Wybór roli -->
                    <div class="space-y-2">
                        <Label for="new-role">Rola użytkownika</Label>
                        <select
                            id="new-role"
                            v-model="newUser.role"
                            class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                            :class="{'border-red-500': userErrors.role}"
                        >
                            <option value="admin">Administrator</option>
                            <option value="doctor">Lekarz</option>
                            <option value="patient">Pacjent</option>
                        </select>
                        <div v-if="userErrors.role" class="text-sm text-red-500">
                            {{ userErrors.role[0] }}
                        </div>
                    </div>

                    <!-- Przyciski -->
                    <div class="flex justify-end gap-3 pt-4">
                        <Button
                            type="button"
                            variant="outline"
                            @click="showAddUserForm = false"
                        >
                            Anuluj
                        </Button>
                        <Button
                            @click="addUser"
                            :disabled="userFormLoading"
                            class="flex bg-nova-primary hover:bg-nova-accent items-center gap-2"
                        >
                            <Icon v-if="userFormLoading" name="loader2" class="animate-spin" size="16" />
                            <span>Zapisz</span>
                        </Button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal do edycji użytkownika - przeniesiony poza Popover -->
        <div v-if="showEditUserForm && selectedUser" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
            <div class="bg-white dark:bg-gray-800 rounded-xl p-6 max-w-md w-full mx-4 shadow-lg">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-medium">Edycja Użytkownika</h3>
                    <Button variant="ghost" class="h-8 w-8 p-0" @click="showEditUserForm = false">
                        <Icon name="x" size="18" />
                    </Button>
                </div>

                <div class="space-y-4">
                    <!-- Imię i nazwisko -->
                    <div class="space-y-2">
                        <Label for="edit-name">Imię i nazwisko</Label>
                        <Input
                            id="edit-name"
                            v-model="selectedUser.name"
                            :class="{'border-red-500': userErrors.name}"
                            placeholder="Wprowadź imię i nazwisko"
                        />
                        <div v-if="userErrors.name" class="text-sm text-red-500">
                            {{ userErrors.name[0] }}
                        </div>
                    </div>

                    <!-- Email -->
                    <div class="space-y-2">
                        <Label for="edit-email">Email</Label>
                        <Input
                            id="edit-email"
                            type="email"
                            v-model="selectedUser.email"
                            :class="{'border-red-500': userErrors.email}"
                            placeholder="Wprowadź adres email"
                        />
                        <div v-if="userErrors.email" class="text-sm text-red-500">
                            {{ userErrors.email[0] }}
                        </div>
                    </div>

                    <!-- Hasło (opcjonalne przy edycji) -->
                    <div class="space-y-2">
                        <Label for="edit-password">Nowe hasło (opcjonalne)</Label>
                        <Input
                            id="edit-password"
                            type="password"
                            v-model="selectedUser.password"
                            :class="{'border-red-500': userErrors.password}"
                            placeholder="Pozostaw puste, aby nie zmieniać"
                        />
                        <div v-if="userErrors.password" class="text-sm text-red-500">
                            {{ userErrors.password[0] }}
                        </div>
                    </div>

                    <!-- Potwierdzenie hasła -->
                    <div class="space-y-2">
                        <Label for="edit-password-confirmation">Potwierdzenie hasła</Label>
                        <Input
                            id="edit-password-confirmation"
                            type="password"
                            v-model="selectedUser.password_confirmation"
                            :class="{'border-red-500': userErrors.password_confirmation}"
                            placeholder="Potwierdź nowe hasło"
                        />
                        <div v-if="userErrors.password_confirmation" class="text-sm text-red-500">
                            {{ userErrors.password_confirmation[0] }}
                        </div>
                    </div>

                    <!-- Wybór roli -->
                    <div class="space-y-2">
                        <Label for="edit-role">Rola użytkownika</Label>
                        <select
                            id="edit-role"
                            v-model="selectedUser.role"
                            class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                            :class="{'border-red-500': userErrors.role}"
                        >
                            <option value="admin">Administrator</option>
                            <option value="doctor">Lekarz</option>
                            <option value="patient">Pacjent</option>
                        </select>
                        <div v-if="userErrors.role" class="text-sm text-red-500">
                            {{ userErrors.role[0] }}
                        </div>
                    </div>

                    <!-- Przyciski -->
                    <div class="flex justify-end gap-3 pt-4">
                        <Button
                            type="button"
                            variant="outline"
                            @click="showEditUserForm = false"
                        >
                            Anuluj
                        </Button>
                        <Button
                            @click="updateUser"
                            :disabled="userFormLoading"
                            class="flex bg-nova-primary hover:bg-nova-accent items-center gap-2"
                        >
                            <Icon v-if="userFormLoading" name="loader2" class="animate-spin" size="16" />
                            <span>Aktualizuj</span>
                        </Button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style>
/* Styles that need to be global to affect PrimeVue components */
.p-toast {
    opacity: 1 !important;
}

.p-toast .p-toast-message {
    margin: 0 0 1rem 0;
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -4px rgba(0, 0, 0, 0.1) !important;
    border-radius: 0.5rem !important;
    overflow: hidden;
    padding: 1rem !important;
    border-width: 0 !important;
    border-left-width: 6px !important;
    border-style: solid !important;
}

.p-toast .p-toast-message-content {
    padding: 0 !important;
    border-width: 0 !important;
    display: flex !important;
    align-items: center !important;
}

.p-toast .p-toast-message-icon {
    font-size: 1.5rem !important;
    margin-right: 0.75rem !important;
}

.p-toast .p-toast-summary {
    font-weight: 600 !important;
    font-size: 1rem !important;
    margin-bottom: 0.25rem !important;
}

.p-toast .p-toast-detail {
    margin-top: 0.25rem !important;
    font-size: 0.875rem !important;
}

.p-toast .p-toast-icon-close {
    width: 1.75rem !important;
    height: 1.75rem !important;
    border-radius: 50% !important;
    background: transparent !important;
    transition: background-color 0.2s !important;
    position: absolute !important;
    top: 0.75rem !important;
    right: 0.75rem !important;
}

.p-toast .p-toast-icon-close:hover {
    background: rgba(0, 0, 0, 0.1) !important;
}

/* Success message */
.p-toast .p-toast-message.p-toast-message-success {
    background-color: #ecfdf5 !important;
    border-color: #10b981 !important;
}

.p-toast .p-toast-message.p-toast-message-success .p-toast-message-icon,
.p-toast .p-toast-message.p-toast-message-success .p-toast-summary {
    color: #065f46 !important;
}

.p-toast .p-toast-message.p-toast-message-success .p-toast-detail {
    color: #047857 !important;
}

/* Info message */
.p-toast .p-toast-message.p-toast-message-info {
    background-color: #eff6ff !important;
    border-color: #3b82f6 !important;
}

.p-toast .p-toast-message.p-toast-message-info .p-toast-message-icon,
.p-toast .p-toast-message.p-toast-message-info .p-toast-summary {
    color: #1e40af !important;
}

.p-toast .p-toast-message.p-toast-message-info .p-toast-detail {
    color: #1d4ed8 !important;
}

/* Warning message */
.p-toast .p-toast-message.p-toast-message-warn {
    background-color: #fffbeb !important;
    border-color: #f59e0b !important;
}

.p-toast .p-toast-message.p-toast-message-warn .p-toast-message-icon,
.p-toast .p-toast-message.p-toast-message-warn .p-toast-summary {
    color: #92400e !important;
}

.p-toast .p-toast-message.p-toast-message-warn .p-toast-detail {
    color: #b45309 !important;
}

/* Error message */
.p-toast .p-toast-message.p-toast-message-error {
    background-color: #fef2f2 !important;
    border-color: #ef4444 !important;
}

.p-toast .p-toast-message.p-toast-message-error .p-toast-message-icon,
.p-toast .p-toast-message.p-toast-message-error .p-toast-summary {
    color: #991b1b !important;
}

.p-toast .p-toast-message.p-toast-message-error .p-toast-detail {
    color: #b91c1c !important;
}
</style>

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
    background-color: #f9fafb; /* Zastępuje var(--table-header-bg) */
    color: #374151; /* Zastępuje var(--table-header-color) */
    font-weight: 600;
    text-align: left;
    padding: 0.75rem 1rem;
    font-size: 0.875rem;
}

:deep(tbody td) {
    padding: 1rem !important;
    vertical-align: middle;
}

.cursor-context-menu {
    cursor: context-menu;
}

:deep(.context-menu-content) {
    z-index: 50;
}

/* Niestandardowe style dla toastów */
.custom-toast {
    --p-toast-width: 350px;
    --p-toast-border-radius: 8px;
    --p-toast-transition-duration: 0.3s;
}

:deep(.p-toast) {
    font-family: 'Inter', sans-serif;
}
</style>

