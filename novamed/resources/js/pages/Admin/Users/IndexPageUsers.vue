<script setup lang="ts">
import Icon from '@/components/Icon.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { ContextMenu, ContextMenuContent, ContextMenuItem, ContextMenuSeparator, ContextMenuTrigger } from '@/components/ui/context-menu';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Pagination, PaginationEllipsis, PaginationFirst, PaginationLast, PaginationNext, PaginationPrevious } from '@/components/ui/pagination';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { ScrollArea } from '@/components/ui/scroll-area';
import { Skeleton } from '@/components/ui/skeleton';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import axios from 'axios';
import Toast from 'primevue/toast';
import { useToast } from 'primevue/usetoast';
import { PaginationList, PaginationListItem } from 'reka-ui';
import { computed, onMounted, ref, watch } from 'vue';
import { debounce } from 'lodash';
import { RecycleScroller } from 'vue3-virtual-scroller';
import 'vue3-virtual-scroller/dist/vue3-virtual-scroller.css';


interface User {
    id: number;
    name: string;
    email: string;
    role: string;
    roles?: string[];
    created_at: string;
    password?: string;
    password_confirmation?: string;
    profile_picture_url?: string | null;
    avatar?: string | null;
}

const query = ref({
    page: 1,
    per_page: 12,
    search: '',
    role: '',
});

const users = ref<User[]>([]);
const loading = ref(true);
const error = ref<string | null>(null);
const totalPages = ref(0);
const totalItems = ref(0);
const currentPage = computed(() => query.value.page);
const itemsPerPage = computed(() => query.value.per_page);

const showAddUserForm = ref(false);
const showEditUserForm = ref(false);
const selectedUser = ref<User | null>(null);
const userFormLoading = ref(false);

const frontendUserErrors = ref<Record<string, string | null>>({});
const backendUserErrors = ref<Record<string, string[]>>({});

const avatarFile = ref<File | null>(null);
const avatarPreview = ref<string | null>(null);
const avatarUploadErrors = ref<Record<string, string[]>>({});
const avatarUploadLoading = ref(false);
const selectedUserForAvatar = ref<User | null>(null);
const showAvatarUploadModal = ref(false);
const avatarInputRef = ref<HTMLInputElement | null>(null);

const newUser = ref<User>({
    id: 0,
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    role: 'patient',
    created_at: '',
});

const toast = useToast();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Zarządzanie Użytkownikami',
        href: '/admin/users',
    },
];

const loadUsers = async () => {
    loading.value = true;
    error.value = null;
    try {
        const params = new URLSearchParams();
        params.append('page', query.value.page.toString());
        params.append('per_page', query.value.per_page.toString());
        if (query.value.search) params.append('search', query.value.search);
        if (query.value.role && query.value.role !== 'all') params.append('role', query.value.role);

        const response = await axios.get(`/api/v1/admin/users?${params.toString()}`);
        users.value = response.data.data.map((user: any) => ({
            ...user,
            profile_picture_url: user.avatar || user.profile_picture_url || null,
        }));
        totalPages.value = response.data.meta.last_page;
        totalItems.value = response.data.meta.total;
    } catch (err: any) {
        error.value = err.response?.data?.message || 'Wystąpił błąd podczas ładowania danych.';
    } finally {
        loading.value = false;
    }
};

const resetPagination = () => {
    query.value.page = 1;
};

const goToPage = (page: number) => {
    query.value.page = page;
};

const showSuccessToast = (summary: string, detail: string) => {
    toast.add({ severity: 'success', summary, detail, life: 3000 });
};

const showErrorToast = (summary: string, detail: string) => {
    toast.add({ severity: 'error', summary, detail, life: 3000 });
};

const editUser = (user: User) => {
    if (user.role === 'admin') {
        showErrorToast('Błąd', 'Nie można edytować administratora systemu');
        return;
    }
    selectedUser.value = JSON.parse(JSON.stringify(user));
    frontendUserErrors.value = {};
    backendUserErrors.value = {};
    showEditUserForm.value = true;
};

const resetUserForm = () => {
    newUser.value = {
        id: 0,
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
        role: 'patient',
        created_at: '',
    };
    frontendUserErrors.value = {};
    backendUserErrors.value = {};
};

const validateUserForm = (user: User, isNew: boolean): boolean => {
    frontendUserErrors.value = {};
    let isValid = true;

    if (!user.name.trim()) {
        frontendUserErrors.value.name = 'Imię i nazwisko jest wymagane.';
        isValid = false;
    }
    if (!user.email.trim()) {
        frontendUserErrors.value.email = 'Adres email jest wymagany.';
        isValid = false;
    } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(user.email)) {
        frontendUserErrors.value.email = 'Podaj prawidłowy adres email.';
        isValid = false;
    }
    if (!user.role) {
        frontendUserErrors.value.role = 'Rola użytkownika jest wymagana.';
        isValid = false;
    }

    if (isNew) {
        if (!user.password) {
            frontendUserErrors.value.password = 'Hasło jest wymagane.';
            isValid = false;
        } else if (user.password.length < 8) {
            frontendUserErrors.value.password = 'Hasło musi mieć co najmniej 8 znaków.';
            isValid = false;
        }
        if (user.password !== user.password_confirmation) {
            frontendUserErrors.value.password_confirmation = 'Hasła nie są identyczne.';
            isValid = false;
        }
    } else if (user.password) {
        if (user.password.length < 8) {
            frontendUserErrors.value.password = 'Nowe hasło musi mieć co najmniej 8 znaków.';
            isValid = false;
        }
        if (user.password !== user.password_confirmation) {
            frontendUserErrors.value.password_confirmation = 'Hasła nie są identyczne.';
            isValid = false;
        }
    }
    return isValid;
};

const addUser = async () => {
    backendUserErrors.value = {};
    if (!validateUserForm(newUser.value, true)) {
        showErrorToast('Błąd walidacji', 'Popraw błędy w formularzu.');
        return;
    }
    userFormLoading.value = true;
    try {
        await axios.post('/api/v1/admin/users', newUser.value);
        showAddUserForm.value = false;
        resetUserForm();
        loadUsers();
        showSuccessToast('Sukces', 'Użytkownik został dodany pomyślnie.');
    } catch (err: any) {
        if (err.response?.status === 422) {
            backendUserErrors.value = err.response.data.errors;
            showErrorToast('Błąd walidacji', 'Sprawdź błędy zwrócone przez serwer.');
        } else {
            showErrorToast('Błąd', err.response?.data?.message || 'Wystąpił błąd podczas dodawania użytkownika.');
        }
    } finally {
        userFormLoading.value = false;
    }
};

const updateUser = async () => {
    if (!selectedUser.value) return;
    backendUserErrors.value = {};
    if (!validateUserForm(selectedUser.value, false)) {
        showErrorToast('Błąd walidacji', 'Popraw błędy w formularzu.');
        return;
    }
    userFormLoading.value = true;
    const userData = { ...selectedUser.value };
    if (!userData.password && typeof userData.password !== 'undefined') {
        delete userData.password;
        delete userData.password_confirmation;
    } else if (userData.password && !userData.password_confirmation) {
        frontendUserErrors.value.password_confirmation = 'Potwierdzenie nowego hasła jest wymagane.';
        userFormLoading.value = false;
        showErrorToast('Błąd walidacji', 'Popraw błędy w formularzu.');
        return;
    }

    try {
        await axios.put(`/api/v1/admin/users/${selectedUser.value.id}`, userData);
        showEditUserForm.value = false;
        selectedUser.value = null;
        loadUsers();
        showSuccessToast('Sukces', 'Użytkownik został zaktualizowany pomyślnie.');
    } catch (err: any) {
        if (err.response?.status === 422) {
            backendUserErrors.value = err.response.data.errors;
            showErrorToast('Błąd walidacji', 'Sprawdź błędy zwrócone przez serwer.');
        } else {
            showErrorToast('Błąd', err.response?.data?.message || 'Wystąpił błąd podczas aktualizacji użytkownika.');
        }
    } finally {
        userFormLoading.value = false;
    }
};

const deleteUser = async (id: number) => {
    const user = users.value.find((u) => u.id === id);
    if (!user) return;
    if (user.role === 'admin') {
        showErrorToast('Błąd', 'Nie można usunąć administratora systemu.');
        return;
    }
    if (!confirm('Czy na pewno chcesz usunąć tego użytkownika?')) return;

    try {
        await axios.delete(`/api/v1/admin/users/${id}`);
        showSuccessToast('Sukces', 'Użytkownik został usunięty pomyślnie.');
        loadUsers();
    } catch (err: any) {
        showErrorToast('Błąd', err.response?.data?.message || 'Wystąpił błąd podczas usuwania użytkownika.');
    }
};

const formatDate = (dateString: string) => {
    if (!dateString) return 'Brak daty';
    const date = new Date(dateString);
    return new Intl.DateTimeFormat('pl-PL', { year: 'numeric', month: '2-digit', day: '2-digit' }).format(date);
};

const openAvatarModal = (user: User) => {
    selectedUserForAvatar.value = user;
    avatarFile.value = null;
    avatarPreview.value = user.profile_picture_url || user.avatar || null;
    avatarUploadErrors.value = {};
    showAvatarUploadModal.value = true;
};

const handleAvatarChange = (event: Event) => {
    const target = event.target as HTMLInputElement;
    if (target.files && target.files[0]) {
        const file = target.files[0];
        const allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
        if (!allowedTypes.includes(file.type)) {
            avatarUploadErrors.value = { avatar: ['Nieprawidłowy format pliku. Dozwolone: JPG, PNG, WEBP.'] };
            avatarFile.value = null;
            if (target) target.value = '';
            return;
        }
        if (file.size > 2 * 1024 * 1024) {
            avatarUploadErrors.value = { avatar: ['Plik jest za duży. Maksymalny rozmiar to 2MB.'] };
            avatarFile.value = null;
            if (target) target.value = '';
            return;
        }
        avatarUploadErrors.value = {};
        avatarFile.value = file;
        const reader = new FileReader();
        reader.onload = (e) => {
            avatarPreview.value = e.target?.result as string;
        };
        reader.readAsDataURL(file);
    }
};

const uploadAvatar = async () => {
    if (!avatarFile.value || !selectedUserForAvatar.value) return;
    avatarUploadLoading.value = true;
    avatarUploadErrors.value = {};
    const formData = new FormData();
    formData.append('avatar', avatarFile.value);
    try {
        const response = await axios.post(`/api/v1/admin/users/${selectedUserForAvatar.value.id}/avatar`, formData, {
            headers: { 'Content-Type': 'multipart/form-data' },
        });
        showAvatarUploadModal.value = false;
        showSuccessToast('Sukces', 'Avatar użytkownika został zaktualizowany.');
        const userIndex = users.value.findIndex((u) => u.id === selectedUserForAvatar.value!.id);
        if (userIndex !== -1) {
            users.value[userIndex].profile_picture_url = response.data.data.avatar || response.data.data.profile_picture_url;
        }
    } catch (err: any) {
        if (err.response?.status === 422) avatarUploadErrors.value = err.response.data.errors;
        else showErrorToast('Błąd', err.response?.data?.message || 'Wystąpił błąd podczas przesyłania avatara.');
    } finally {
        avatarUploadLoading.value = false;
    }
};

const deleteAvatar = async (userId: number) => {
    if (!confirm('Czy na pewno chcesz usunąć zdjęcie tego użytkownika?')) return;
    try {
        await axios.delete(`/api/v1/admin/users/${userId}/avatar`);
        showSuccessToast('Sukces', 'Zdjęcie użytkownika zostało usunięte.');
        const userIndex = users.value.findIndex((u) => u.id === userId);
        if (userIndex !== -1) users.value[userIndex].profile_picture_url = null;
        if (selectedUserForAvatar.value?.id === userId) {
            avatarPreview.value = null;
            showAvatarUploadModal.value = false;
        }
    } catch (err: any) {
        showErrorToast('Błąd', err.response?.data?.message || 'Wystąpił błąd podczas usuwania zdjęcia.');
    }
};

watch(query, loadUsers, { deep: true, immediate: false });
watch(() => query.value.search, debounce(() => {
    resetPagination();
    loadUsers();
}, 300));

onMounted(() => {
    loadUsers();
});
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Toast position="top-right" />
        <div class="flex flex-col gap-5 p-6">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <h1 class="text-nova-darkest dark:text-nova-light text-2xl font-bold">Zarządzanie Użytkownikami</h1>
                <Button
                    variant="default"
                    class="bg-nova-primary hover:bg-nova-accent dark:bg-nova-accent hover:dark:bg-nova-primary dark:text-nova-light flex items-center gap-2"
                    @click="
                        showAddUserForm = true;
                        resetUserForm();
                    "
                >
                    <Icon name="userPlus" size="18" />
                    <span>Dodaj Nowego Użytkownika</span>
                </Button>
            </div>

            <div class="grid grid-cols-1 gap-4 rounded-xl border p-4 md:grid-cols-2 lg:grid-cols-3">
                <div class="space-y-2">
                    <Label for="search">Wyszukiwanie</Label>
                    <div class="relative">
                        <Input
                            id="search"
                            v-model="query.search"
                            placeholder="Szukaj po imieniu, emailu..."
                            @keyup.enter="loadUsers()"
                            class="dark:bg-background pr-10"
                        />
                        <div class="absolute inset-y-0 right-0 flex cursor-pointer items-center pr-3" @click="loadUsers()">
                            <Icon name="search" size="16" class="text-gray-400" />
                        </div>
                    </div>
                </div>
                <div class="space-y-2">
                    <Label for="roleFilter">Filtruj po roli</Label>
                    <Select v-model="query.role" @update:modelValue="loadUsers()">
                        <SelectTrigger id="roleFilter">
                            <SelectValue placeholder="Wszystkie role" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">Wszystkie role</SelectItem>
                            <SelectItem value="admin">Administrator</SelectItem>
                            <SelectItem value="patient">Pacjent</SelectItem>
                            <SelectItem value="doctor">Lekarz</SelectItem>
                        </SelectContent>
                    </Select>
                </div>
            </div>

            <div v-if="loading" class="mt-2 rounded-xl bg-white p-4 shadow-sm dark:bg-gray-900">
                <Skeleton v-for="i in query.per_page" :key="`skel-${i}`" class="mb-2 h-12 w-full" />
            </div>
            <div
                v-else-if="error"
                class="rounded-xl border border-red-100 bg-red-50 p-6 text-red-500 dark:border-red-800 dark:bg-red-900/20 dark:text-red-400"
            >
                {{ error }}
            </div>
            <div v-else-if="users.length === 0" class="rounded-xl p-8 text-center text-gray-500 shadow-sm dark:bg-gray-900">
                Nie znaleziono użytkowników.
            </div>
            <div v-else class="overflow-hidden rounded-xl shadow-sm dark:border-gray-600 dark:bg-gray-900">
                <ScrollArea class="h-[auto] w-full dark:border-gray-600">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead class="w-16">ID</TableHead>
                                <TableHead class="w-16">Avatar</TableHead>
                                <TableHead>Imię i Nazwisko</TableHead>
                                <TableHead>Email</TableHead>
                                <TableHead>Rola</TableHead>
                                <TableHead>Data utworzenia</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="user in users" :key="user.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                <ContextMenu>
                                    <ContextMenuTrigger asChild>
                                        <tr class="contents cursor-context-menu">
                                            <TableCell>{{ user.id }}</TableCell>
                                            <TableCell>
                                                <img
                                                    :src="
                                                        user.profile_picture_url ||
                                                        user.avatar ||
                                                        `https://ui-avatars.com/api/?name=${encodeURIComponent(user.name)}&background=random&color=fff`
                                                    "
                                                    alt="Avatar"
                                                    class="h-10 w-10 cursor-pointer rounded-full object-cover"
                                                    @click="openAvatarModal(user)"
                                                    loading="lazy"
                                                />
                                            </TableCell>
                                            <TableCell>{{ user.name }}</TableCell>
                                            <TableCell>{{ user.email }}</TableCell>
                                            <TableCell>
                                                <span
                                                    :class="`rounded-full px-2 py-1 text-xs font-medium ${user.role === 'admin' ? 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300' : user.role === 'doctor' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300' : 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300'}`"
                                                >
                                                    {{ user.role === 'admin' ? 'Administrator' : user.role === 'doctor' ? 'Lekarz' : 'Pacjent' }}
                                                </span>
                                            </TableCell>
                                            <TableCell>{{ formatDate(user.created_at) }}</TableCell>
                                        </tr>
                                    </ContextMenuTrigger>
                                    <ContextMenuContent>
                                        <ContextMenuItem @click="editUser(user)" :disabled="user.role === 'admin'">
                                            <Icon name="edit" size="16" class="mr-2" />
                                            Edytuj
                                        </ContextMenuItem>
                                        <ContextMenuSeparator v-if="user.role !== 'admin'" />
                                        <ContextMenuItem @click="openAvatarModal(user)">
                                            <Icon name="image" size="14" class="mr-2" />
                                            Zmień avatar
                                        </ContextMenuItem>
                                        <ContextMenuSeparator v-if="user.role !== 'admin'" />
                                        <ContextMenuItem
                                            v-if="user.role !== 'admin'"
                                            @click="deleteUser(user.id)"
                                            class="cursor-pointer text-red-600"
                                        >
                                            <Icon name="trash2" size="16" class="mr-2" />
                                            Usuń
                                        </ContextMenuItem>
                                    </ContextMenuContent>
                                </ContextMenu>
                            </TableRow>
                        </TableBody>
                    </Table>
                </ScrollArea>
                <div
                    class="dark:bg-background flex items-center justify-center rounded-b-xl border-2 border-t border-gray-200 px-4 py-3 dark:border-gray-900"
                >
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
                                        :variant="currentPage === item.value ? 'default' : 'outline'"
                                        :class="currentPage === item.value ? 'bg-nova-primary hover:bg-nova-accent text-white' : ''"
                                        size="sm"
                                        >{{ item.value }}
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

        <div v-if="showAddUserForm" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
            <Card class="mx-auto max-h-[90vh] w-full max-w-md overflow-y-auto shadow-lg">
                <CardHeader class="flex items-center justify-between border-b">
                    <CardTitle>Dodaj Nowego Użytkownika</CardTitle>
                    <Button variant="ghost" size="icon" @click="showAddUserForm = false">
                        <Icon name="x" size="18" />
                    </Button>
                </CardHeader>
                <CardContent class="pt-4">
                    <form @submit.prevent="addUser" class="space-y-4">
                        <div>
                            <Label for="new-name" class="mb-1">Imię i nazwisko</Label
                            ><Input
                                id="new-name"
                                v-model="newUser.name"
                                placeholder="Wpisz imię i nazwisko"
                                :class="{ 'border-red-500': frontendUserErrors.name || backendUserErrors.name }"
                            />
                            <p v-if="frontendUserErrors.name" class="mt-1 text-xs text-red-500">
                                {{ frontendUserErrors.name }}
                            </p>
                            <InputError v-else-if="backendUserErrors.name" :message="backendUserErrors.name?.[0]" />
                        </div>
                        <div>
                            <Label for="new-email" class="mb-1">Email</Label
                            ><Input
                                id="new-email"
                                type="email"
                                placeholder="Wpisz adres email"
                                v-model="newUser.email"
                                :class="{ 'border-red-500': frontendUserErrors.email || backendUserErrors.email }"
                            />
                            <p v-if="frontendUserErrors.email" class="mt-1 text-xs text-red-500">
                                {{ frontendUserErrors.email }}
                            </p>
                            <InputError v-else-if="backendUserErrors.email" :message="backendUserErrors.email?.[0]" />
                        </div>
                        <div>
                            <Label for="new-password" class="mb-1">Hasło</Label
                            ><Input
                                id="new-password"
                                type="password"
                                v-model="newUser.password"
                                placeholder="Wpisz hasło"
                                :class="{ 'border-red-500': frontendUserErrors.password || backendUserErrors.password }"
                            />
                            <p v-if="frontendUserErrors.password" class="mt-1 text-xs text-red-500">
                                {{ frontendUserErrors.password }}
                            </p>
                            <InputError v-else-if="backendUserErrors.password" :message="backendUserErrors.password?.[0]" />
                        </div>
                        <div>
                            <Label for="new-password-confirmation" class="mb-1">Potwierdzenie hasła</Label
                            ><Input
                                id="new-password-confirmation"
                                type="password"
                                placeholder="Powtórz hasło"
                                v-model="newUser.password_confirmation"
                                :class="{ 'border-red-500': frontendUserErrors.password_confirmation || backendUserErrors.password_confirmation }"
                            />
                            <p v-if="frontendUserErrors.password_confirmation" class="mt-1 text-xs text-red-500">
                                {{ frontendUserErrors.password_confirmation }}
                            </p>
                            <InputError v-else-if="backendUserErrors.password_confirmation" :message="backendUserErrors.password_confirmation?.[0]" />
                        </div>
                        <div>
                            <Label for="new-role" class="mb-1">Rola</Label>
                            <Select v-model="newUser.role" :class="{ 'border-red-500': frontendUserErrors.role || backendUserErrors.role }">
                                <SelectTrigger id="new-role">
                                    <SelectValue placeholder="Wybierz rolę" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="patient">Pacjent</SelectItem>
                                    <SelectItem value="doctor">Lekarz</SelectItem>
                                    <SelectItem value="admin">Administrator</SelectItem>
                                </SelectContent>
                            </Select>
                            <p v-if="frontendUserErrors.role" class="mt-1 text-xs text-red-500">
                                {{ frontendUserErrors.role }}
                            </p>
                            <InputError v-else-if="backendUserErrors.role" :message="backendUserErrors.role?.[0]" />
                        </div>
                        <div class="flex justify-end gap-2 pt-2">
                            <Button type="button" variant="outline" @click="showAddUserForm = false">Anuluj</Button>
                            <Button type="submit" :disabled="userFormLoading" class="bg-nova-primary hover:bg-nova-accent">
                                <Icon v-if="userFormLoading" name="loader2" class="mr-2 animate-spin" />
                                Zapisz
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </div>

        <div v-if="showEditUserForm && selectedUser" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
            <Card class="mx-auto max-h-[90vh] w-full max-w-md overflow-y-auto shadow-lg">
                <CardHeader class="flex items-center justify-between border-b">
                    <CardTitle>Edytuj Użytkownika</CardTitle>
                    <Button variant="ghost" size="icon" @click="showEditUserForm = false">
                        <Icon name="x" size="18" />
                    </Button>
                </CardHeader>
                <CardContent class="pt-3">
                    <form @submit.prevent="updateUser" class="space-y-4">
                        <div>
                            <Label for="edit-name" class="mb-1">Imię i nazwisko</Label
                            ><Input
                                id="edit-name"
                                v-model="selectedUser.name"
                                :class="{ 'border-red-500': frontendUserErrors.name || backendUserErrors.name }"
                            />
                            <p v-if="frontendUserErrors.name" class="mt-1 text-xs text-red-500">
                                {{ frontendUserErrors.name }}
                            </p>
                            <InputError v-else-if="backendUserErrors.name" :message="backendUserErrors.name?.[0]" />
                        </div>
                        <div>
                            <Label for="edit-email" class="mb-1">Email</Label
                            ><Input
                                id="edit-email"
                                type="email"
                                v-model="selectedUser.email"
                                :class="{ 'border-red-500': frontendUserErrors.email || backendUserErrors.email }"
                            />
                            <p v-if="frontendUserErrors.email" class="mt-1 text-xs text-red-500">
                                {{ frontendUserErrors.email }}
                            </p>
                            <InputError v-else-if="backendUserErrors.email" :message="backendUserErrors.email?.[0]" />
                        </div>
                        <div>
                            <Label for="edit-password" class="mb-1">Nowe hasło (pozostaw puste, by nie zmieniać)</Label
                            ><Input
                                id="edit-password"
                                type="password"
                                v-model="selectedUser.password"
                                :class="{ 'border-red-500': frontendUserErrors.password || backendUserErrors.password }"
                            />
                            <p v-if="frontendUserErrors.password" class="mt-1 text-xs text-red-500">
                                {{ frontendUserErrors.password }}
                            </p>
                            <InputError v-else-if="backendUserErrors.password" :message="backendUserErrors.password?.[0]" />
                        </div>
                        <div>
                            <Label for="edit-password-confirmation" class="mb-1">Potwierdzenie nowego hasła</Label
                            ><Input
                                id="edit-password-confirmation"
                                type="password"
                                v-model="selectedUser.password_confirmation"
                                :class="{ 'border-red-500': frontendUserErrors.password_confirmation || backendUserErrors.password_confirmation }"
                            />
                            <p v-if="frontendUserErrors.password_confirmation" class="mt-1 text-xs text-red-500">
                                {{ frontendUserErrors.password_confirmation }}
                            </p>
                            <InputError v-else-if="backendUserErrors.password_confirmation" :message="backendUserErrors.password_confirmation?.[0]" />
                        </div>
                        <div>
                            <Label for="edit-role" class="mb-1">Rola</Label>
                            <Select v-model="selectedUser.role" :class="{ 'border-red-500': frontendUserErrors.role || backendUserErrors.role }">
                                <SelectTrigger id="edit-role">
                                    <SelectValue placeholder="Wybierz rolę" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="patient">Pacjent</SelectItem>
                                    <SelectItem value="doctor">Lekarz</SelectItem>
                                    <SelectItem value="admin">Administrator</SelectItem>
                                </SelectContent>
                            </Select>
                            <p v-if="frontendUserErrors.role" class="mt-1 text-xs text-red-500">
                                {{ frontendUserErrors.role }}
                            </p>
                            <InputError v-else-if="backendUserErrors.role" :message="backendUserErrors.role?.[0]" />
                        </div>
                        <div class="flex justify-end gap-2 pt-2">
                            <Button type="button" variant="outline" @click="showEditUserForm = false">Anuluj</Button>
                            <Button type="submit" :disabled="userFormLoading" class="bg-nova-primary hover:bg-nova-accent">
                                <Icon v-if="userFormLoading" name="loader2" class="mr-2 animate-spin" />
                                Aktualizuj
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </div>

        <div v-if="showAvatarUploadModal && selectedUserForAvatar" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
            <Card class="mx-auto w-full max-w-md border dark:border-gray-700 dark:bg-gray-800">
                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                    <div>
                        <CardTitle class="text-lg font-medium dark:text-gray-200">Zdjęcie użytkownika</CardTitle>
                        <CardDescription class="dark:text-gray-400">Zdjęcie profilowe</CardDescription>
                    </div>
                    <Button variant="ghost" class="h-8 w-8 p-0 dark:text-gray-200 dark:hover:bg-gray-700" @click="showAvatarUploadModal = false">
                        <Icon name="x" size="18" />
                    </Button>
                </CardHeader>
                <CardContent class="flex flex-col items-center gap-4">
                    <img
                        :src="
                            avatarPreview ||
                            `https://ui-avatars.com/api/?name=${encodeURIComponent(selectedUserForAvatar.name)}&background=random&color=fff&size=128`
                        "
                        alt="Podgląd zdjęcia"
                        class="h-36 w-36 rounded-full border-4 border-white object-cover shadow-lg dark:border-gray-700"
                    />
                    <input
                        type="file"
                        ref="avatarInputRef"
                        @change="handleAvatarChange"
                        accept="image/jpeg,image/png,image/webp"
                        class="hidden"
                        id="user-avatar-input"
                    />
                    <Button
                        type="button"
                        variant="outline"
                        @click="avatarInputRef?.click()"
                        class="w-full dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700"
                    >
                        <Icon name="upload-cloud" class="mr-2 h-4 w-4" />
                        Wybierz nowe zdjęcie
                    </Button>
                    <InputError :message="avatarUploadErrors.avatar?.[0]" />
                    <p class="text-center text-xs text-gray-500 dark:text-gray-400">Maks. 2MB. JPG, PNG, WEBP.</p>
                    <div class="w-full space-y-2">
                        <Button
                            v-if="avatarFile"
                            @click="uploadAvatar"
                            :disabled="avatarUploadLoading"
                            class="w-full bg-green-600 text-white hover:bg-green-700"
                        >
                            <Icon v-if="avatarUploadLoading" name="loader2" class="mr-2 h-4 w-4 animate-spin" />
                            Zapisz avatar
                        </Button>
                        <Button
                            v-if="(selectedUserForAvatar.profile_picture_url || selectedUserForAvatar.avatar) && !avatarFile"
                            type="button"
                            @click="deleteAvatar(selectedUserForAvatar.id)"
                            variant="destructive"
                            class="w-full"
                        >
                            <Icon name="trash-2" class="mr-2 h-4 w-4" />
                            Usuń zdjęcie
                        </Button>
                    </div>
                    <div class="flex w-full justify-end pt-2">
                        <Button
                            type="button"
                            variant="outline"
                            @click="showAvatarUploadModal = false"
                            class="dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600"
                        >
                            Anuluj
                        </Button>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>

<style>
.p-toast {
    opacity: 1 !important;
}

.p-toast .p-toast-message {
    margin: 0 0 1rem 0;
    box-shadow:
        0 10px 15px -3px rgba(0, 0, 0, 0.1),
        0 4px 6px -4px rgba(0, 0, 0, 0.1) !important;
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

.cursor-context-menu {
    cursor: context-menu;
}

:deep(.context-menu-content) {
    z-index: 50;
}

.custom-toast {
    --p-toast-width: 350px;
    --p-toast-border-radius: 8px;
    --p-toast-transition-duration: 0.3s;
}

:deep(.p-toast) {
    font-family: 'Inter', sans-serif;
}

.scroller {
    height: 600px;
    overflow-y: auto;
}
</style>
