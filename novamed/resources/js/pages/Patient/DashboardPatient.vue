<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type User } from '@/types';
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import Icon from '@/components/Icon.vue';
import { useAuthStore } from '@/stores/auth';
import { useRouter } from 'vue-router';
import { Separator } from '@/components/ui/separator';

const router = useRouter();
const authStore = useAuthStore();
const user = computed<User | null>(() => authStore.user);

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Panel Pacjenta',
    },
];

interface Appointment {
    id: number;
    appointment_datetime: string;
    doctor: { name: string; specialization: string; };
    procedure: { name: string; };
    status: string;
}
const upcomingAppointments = ref<Appointment[]>([]);
const loadingAppointments = ref(true);

async function fetchUpcomingAppointments() {
    loadingAppointments.value = true;
    try {
        const response = await axios.get('/api/v1/patient/appointments', {
            params: {
                limit: 4,
                upcoming: true,
                status: 'scheduled,confirmed'
            }
        });
        upcomingAppointments.value = response.data.data || [];
    } catch (error) {
        console.error("Błąd podczas pobierania nadchodzących wizyt:", error);
    } finally {
        loadingAppointments.value = false;
    }
}

const nearestAppointment = computed(() => upcomingAppointments.value.length > 0 ? upcomingAppointments.value[0] : null);
const nextAppointments = computed(() => upcomingAppointments.value.length > 1 ? upcomingAppointments.value.slice(1, 4) : []);

interface Doctor {
    id: number;
    first_name: string;
    last_name: string;
    specialization: string;
    profile_picture_url?: string | null;
}
const suggestedDoctors = ref<Doctor[]>([]);
const loadingDoctors = ref(true);

async function fetchSuggestedDoctors() {
    loadingDoctors.value = true;
    try {
        const response = await axios.get('/api/v1/doctors', { params: { limit: 3, popular: true } });
        suggestedDoctors.value = response.data.data || [];
    } catch (error) {
        console.error("Błąd podczas pobierania proponowanych lekarzy:", error);
    } finally {
        loadingDoctors.value = false;
    }
}

interface Procedure {
    id: number;
    name: string;
    description: string;
    base_price: number;
}
const popularProcedures = ref<Procedure[]>([]);
const loadingProcedures = ref(true);

async function fetchPopularProcedures() {
    loadingProcedures.value = true;
    try {
        const response = await axios.get('/api/v1/procedures', { params: { limit: 6, popular: true } });
        popularProcedures.value = response.data.data || [];
    } catch (error) {
        console.error("Błąd podczas pobierania popularnych zabiegów:", error);
    } finally {
        loadingProcedures.value = false;
    }
}

const formatDateTime = (dateTimeString: string) => {
    if (!dateTimeString) return '';
    const date = new Date(dateTimeString);
    return date.toLocaleString('pl-PL', {
        year: 'numeric', month: 'long', day: 'numeric',
        hour: '2-digit', minute: '2-digit'
    });
};

const getInitials = (name: string | undefined) => {
    if (!name) return '';
    const parts = name.split(' ');
    if (parts.length > 1) {
        return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
    }
    return name.substring(0, 2).toUpperCase();
};


onMounted(() => {
    fetchUpcomingAppointments();
    fetchSuggestedDoctors();
    fetchPopularProcedures();
});
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
            <Card class="dark:bg-gray-800">
                <CardHeader class="flex flex-row items-center justify-between">
                    <div>
                        <CardTitle class="text-2xl font-bold">Witaj, {{ user?.name || 'Pacjencie' }}!</CardTitle>
                        <CardDescription>Oto podsumowanie Twojej aktywności i szybkie linki.</CardDescription>
                    </div>
                    <div v-if="authStore.user?.avatar" class="flex-shrink-0">
                        <div class="h-14 w-14 rounded-full bg-nova-primary/20 flex items-center justify-center text-foreground font-semibold shrink-0 border border-nova-primary/30">
                            <img v-if="user?.avatar || user?.profile_picture_url || user?.profile_picture_path"
                                 :src="user?.avatar || user?.profile_picture_url || user?.profile_picture_path"
                                 :alt="user?.name"
                                 class="h-full w-full object-cover rounded-full" />
                            <span v-else>{{ getInitials(user?.name) }}</span>
                        </div>
                    </div>
                    <div v-else class="flex-shrink-0 bg-gray-200 dark:bg-gray-700 h-16 w-16 rounded-full flex items-center justify-center">
                        <Icon name="user" class="h-8 w-8 text-gray-500 dark:text-gray-400" />
                    </div>
                </CardHeader>
                <CardContent class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                    <p class="text-muted-foreground">Masz szybki dostęp do swoich wizyt i ustawień konta.</p>
                    <div class="flex items-center gap-3">
                        <Button @click="router.push({ name: 'settings.profile' })" variant="outline" class="dark:text-gray-200 dark:border-gray-600 dark:hover:bg-gray-700">
                            <Icon name="user-cog" class="mr-2 h-4 w-4" />
                            Mój Profil
                        </Button>
                    </div>
                </CardContent>
            </Card>
            <Separator/>
            <Card class="dark:bg-gray-800">
                <CardHeader>
                    <CardTitle>Szybkie Akcje</CardTitle>
                </CardHeader>
                <CardContent class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <Button size="lg" class="w-full bg-nova-primary text-primary-foreground hover:bg-nova-primary/90" @click="router.push('/procedures')">
                        <Icon name="plus-circle" class="mr-2 h-5 w-5" />
                        Umów nową wizytę
                    </Button>
                    <Button size="lg" variant="secondary" class="w-full dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600" @click="router.push('/patient/appointments')">
                        <Icon name="history" class="mr-2 h-5 w-5" />
                        Historia wizyt
                    </Button>
                </CardContent>
            </Card>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="lg:col-span-2 space-y-2">
                    <Card class="dark:bg-gray-800">
                        <CardHeader>
                            <CardTitle>Nadchodzące Wizyty</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div v-if="loadingAppointments" class="space-y-4">
                                <div v-for="i in 2" :key="i" class="p-4 border rounded-lg dark:border-gray-700 animate-pulse">
                                    <div class="h-4 bg-gray-300 dark:bg-gray-600 rounded w-3/4 mb-2"></div>
                                    <div class="h-3 bg-gray-300 dark:bg-gray-600 rounded w-1/2 mb-1"></div>
                                    <div class="h-3 bg-gray-300 dark:bg-gray-600 rounded w-1/3"></div>
                                </div>
                            </div>
                            <div v-else-if="nearestAppointment">
                                <div class="bg-nova-primary/10 dark:bg-primary/20 p-4 rounded-lg mb-4 border border-nova-dark/30">
                                    <h3 class="font-semibold text-lg text-primary dark:text-blue-400">Najbliższa wizyta:</h3>
                                    <p><Icon name="calendar" class="inline-block mr-1 h-4 w-4" /> {{ formatDateTime(nearestAppointment.appointment_datetime) }}</p>
                                    <p><Icon name="user" class="inline-block mr-1 h-4 w-4" /> Dr {{ nearestAppointment.doctor.first_name }} {{ nearestAppointment.doctor.last_name }} ({{ nearestAppointment.doctor.specialization }})</p>
                                    <p><Icon name="stethoscope" class="inline-block mr-1 h-4 w-4" /> {{ nearestAppointment.procedure.name }}</p>
                                    <Button size="sm" class="mt-2 bg-nova-primary text-primary-foreground hover:bg-nova-primary/90" @click="router.push(`/patient/appointments/${nearestAppointment.id}`)">
                                        Szczegóły wizyty
                                    </Button>
                                </div>
                                <div v-if="nextAppointments.length > 0" class="space-y-3">
                                    <h4 class="font-medium text-muted-foreground">Kolejne wizyty:</h4>
                                    <div v-for="appt in nextAppointments" :key="appt.id" class="p-3 border rounded-md dark:border-gray-700 flex justify-between items-center">
                                        <div>
                                            <p class="font-medium">{{ formatDateTime(appt.appointment_datetime) }}</p>
                                            <p class="text-sm text-muted-foreground">Dr {{ appt.doctor.name }} - {{ appt.procedure.name }}</p>
                                        </div>
                                        <Button variant="ghost" size="sm" @click="router.push(`/patient/appointments/${appt.id}`)">
                                            <Icon name="eye" class="h-4 w-4" />
                                        </Button>
                                    </div>
                                </div>
                                <Button variant="outline" class="mt-4 w-full bg-nova-primary hover:bg-nova-primary/80 text-nova-light hover:text-nova-light dark:bg-nova-primary dark:text-gray-200 dark:border-gray-600 dark:hover:bg-gray-700" @click="router.push('/patient/appointments')">
                                    <Icon name="calendar-days" class="mr-2 h-4 w-4" />
                                    Zobacz wszystkie moje wizyty
                                </Button>
                            </div>
                            <div v-else class="text-center py-6">
                                <p class="text-muted-foreground">Nie masz żadnych nadchodzących wizyt.</p>
                                <Icon name="calendar-off" class="mx-auto my-2 h-12 w-12 text-gray-400 dark:text-gray-500" />
                            </div>
                        </CardContent>
                    </Card>

                    <Card class="dark:bg-gray-800">
                        <CardHeader>
                            <CardTitle>Polecani Specjaliści</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div v-if="loadingDoctors" class="space-y-3">
                                <div v-for="i in 2" :key="i" class="flex items-center gap-3 p-2 border rounded-md dark:border-gray-700 animate-pulse">
                                    <div class="h-10 w-10 rounded-full bg-gray-300 dark:bg-gray-600"></div>
                                    <div class="flex-1 space-y-1">
                                        <div class="h-3 bg-gray-300 dark:bg-gray-600 rounded w-3/4"></div>
                                        <div class="h-2 bg-gray-300 dark:bg-gray-600 rounded w-1/2"></div>
                                    </div>
                                </div>
                            </div>
                            <div v-else-if="suggestedDoctors.length > 0" class="space-y-3">
                                <div v-for="doc in suggestedDoctors" :key="doc.id" class="flex items-center gap-3 p-2 border rounded-md dark:border-gray-700 hover:bg-accent/50 dark:hover:bg-gray-700/50 transition-colors">
                                    <div class="h-10 w-10 rounded-full bg-muted flex items-center justify-center text-foreground font-semibold shrink-0">
                                        <img v-if="doc.profile_picture_url" :src="doc.profile_picture_url" :alt="`${doc.first_name} ${doc.last_name}`" class="h-full w-full object-cover rounded-full" />
                                        <span v-else>{{ getInitials(`${doc.first_name} ${doc.last_name}`) }}</span>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="font-medium truncate">{{ doc.first_name }} {{ doc.last_name }}</p>
                                        <p class="text-sm text-muted-foreground truncate">{{ doc.specialization }}</p>
                                    </div>
                                    <Button variant="ghost" size="sm" @click="router.push(`/doctors/${doc.id}`)">
                                        <Icon name="arrow-right-circle" class="h-4 w-4" />
                                    </Button>
                                </div>
                                <Button variant="outline" class="mt-2 w-full dark:text-gray-200 dark:border-gray-600 dark:hover:bg-gray-700" @click="router.push('/doctors')">
                                    Zobacz wszystkich specjalistów
                                </Button>
                            </div>
                            <p v-else class="text-muted-foreground text-center py-4">Brak polecanych specjalistów.</p>
                        </CardContent>
                    </Card>
                </div>

                <div class="space-y-6">
                    <Card class="dark:bg-gray-800">
                        <CardHeader>
                            <CardTitle>Popularne Zabiegi</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div v-if="loadingProcedures" class="space-y-3">
                                <div v-for="i in 2" :key="i" class="p-2 border rounded-md dark:border-gray-700 animate-pulse">
                                    <div class="h-4 bg-gray-300 dark:bg-gray-600 rounded w-5/6 mb-1"></div>
                                    <div class="h-3 bg-gray-300 dark:bg-gray-600 rounded w-3/4"></div>
                                </div>
                            </div>
                            <div v-else-if="popularProcedures.length > 0" class="space-y-3">
                                <div v-for="proc in popularProcedures" :key="proc.id" class="p-3 border rounded-md dark:border-gray-700 hover:bg-accent/50 dark:hover:bg-gray-700/50 transition-colors">
                                    <p class="font-medium">{{ proc.name }}</p>
                                    <p class="text-sm text-muted-foreground truncate">{{ proc.description }}</p>
                                    <Button variant="link" size="sm" class="p-0 h-auto mt-1 text-primary" @click="router.push(`/procedures/${proc.id}`)">
                                        Dowiedz się więcej
                                    </Button>
                                </div>
                                <Button variant="outline" class="mt-2 w-full dark:text-gray-200 dark:border-gray-600 dark:hover:bg-gray-700" @click="router.push('/procedures')">
                                    Przeglądaj wszystkie zabiegi
                                </Button>
                            </div>
                            <p v-else class="text-muted-foreground text-center py-4">Brak popularnych zabiegów.</p>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
