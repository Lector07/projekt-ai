<script setup lang="ts">
import {ref, onMounted} from 'vue';
import {useRoute, useRouter} from 'vue-router';
import axios from 'axios';
import AppLayout from '@/layouts/AppLayout.vue';
import {Button} from '@/components/ui/button';
import Icon from '@/components/Icon.vue';
import {Label} from '@/components/ui/label';
import {Popover, PopoverContent, PopoverTrigger} from '@/components/ui/popover';
// Jeśli brakuje modułu kalendarza, należy go zainstalować lub stworzyć
import {Calendar} from '@/components/ui/calendar';
import type {BreadcrumbItem} from "@/types";

// Zmienne stanu
const route = useRoute();
const router = useRouter();
const loading = ref(true);
const error = ref<string | null>(null);
const appointment = ref<any>(null);

// Funkcje pomocnicze
const formatDateTime = (dateString?: string): string => {
    if (!dateString) return 'Brak danych';
    try {
        const date = new Date(dateString);
        return date.toLocaleDateString('pl-PL', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    } catch (e) {
        console.error('Błąd formatowania daty:', e);
        return 'Format nieznany';
    }
};

const formatStatus = (status: string): string => {
    const statuses: Record<string, string> = {
        'scheduled': 'Zaplanowana',
        'completed': 'Zakończona',
        'cancelled': 'Anulowana',
        'no-show': 'Nieobecność'
    };
    return statuses[status] || status;
};

const getStatusClass = (status: string): string => {
    switch (status) {
        case 'scheduled':
            return 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300';
        case 'completed':
            return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300';
        case 'cancelled':
            return 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300';
        case 'no-show':
            return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300';
        default:
            return 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-300';
    }
};

// Pobieranie danych wizyty
const fetchAppointment = async () => {
    try {
        loading.value = true;
        const appointmentId = route.params.id;

        const response = await axios.get(`/api/v1/admin/appointments/${appointmentId}`);
        appointment.value = response.data.data;

        console.log("Początkowe dane wizyty:", appointment.value);

        // Pobierz dodatkowe dane lekarza
        if (appointment.value?.doctor?.id) {
            try {
                const doctorResponse = await axios.get(`/api/v1/admin/doctors/${appointment.value.doctor.id}`);
                if (doctorResponse.data && doctorResponse.data.data) {
                    appointment.value.doctor = {
                        ...appointment.value.doctor,
                        ...doctorResponse.data.data
                    };
                    console.log("Dane lekarza po aktualizacji:", appointment.value.doctor);
                }
            } catch (error) {
                console.error('Błąd pobierania danych lekarza:', error);
            }
        }

        // Pobierz dodatkowe dane procedury
        if (appointment.value?.procedure?.id) {
            try {
                const procedureResponse = await axios.get(`/api/v1/admin/procedures/${appointment.value.procedure.id}`);
                if (procedureResponse.data && procedureResponse.data.data) {
                    appointment.value.procedure = {
                        ...appointment.value.procedure,
                        ...procedureResponse.data.data
                    };
                    console.log("Dane procedury po aktualizacji:", appointment.value.procedure);
                }
            } catch (error) {
                console.error('Błąd pobierania danych procedury:', error);
            }
        }

        document.title = `Wizyta #${appointment.value.id} - NovaMed Admin`;
    } catch (err) {
        console.error('Błąd podczas pobierania szczegółów wizyty:', err);
        error.value = 'Nie udało się pobrać danych wizyty. Spróbuj ponownie później.';
    } finally {
        loading.value = false;
    }
};

const showEditModal = ref(false);
const selectedDate = ref<Date | null>(null);
const appointmentErrors = ref<Record<string, string[]>>({});
const appointmentFormLoading = ref(false);

// Status wizyty - opcje dla selecta
const statusOptions = [
    {value: 'scheduled', label: 'Zaplanowana'},
    {value: 'completed', label: 'Zakończona'},
    {value: 'cancelled', label: 'Anulowana'},
    {value: 'no-show', label: 'Nieobecność'}
];

// Funkcja powrotu do listy wizyt
const goBack = () => {
    router.push('/admin/appointments');
};

// Funkcja edycji wizyty
const goToEdit = () => {
    if (appointment.value) {
        // Ustaw datę na podstawie istniejącej wizyty
        if (appointment.value.appointment_datetime) {
            selectedDate.value = new Date(appointment.value.appointment_datetime);
        }
        showEditModal.value = true;
    }
};

// Funkcje obsługi formularza
const closeEditForm = () => {
    showEditModal.value = false;
    appointmentErrors.value = {};
};

const onAppointmentDateChange = (date: Date) => {
    selectedDate.value = date;
};

const updateAppointment = async () => {
    try {
        appointmentFormLoading.value = true;
        appointmentErrors.value = {};

        const formData = {
            status: appointment.value.status,
            patient_notes: appointment.value.patient_notes,
            doctor_notes: appointment.value.doctor_notes,
            appointment_datetime: selectedDate.value?.toISOString(),
        };

        await axios.put(`/api/v1/admin/appointments/${appointment.value.id}`, formData);

        // Odśwież dane wizyty
        await fetchAppointment();

        // Zamknij formularz
        showEditModal.value = false;

    } catch (error: any) {
        if (error.response?.status === 422) {
            appointmentErrors.value = error.response.data.errors;
        } else {
            console.error('Błąd podczas aktualizacji wizyty:', error);
        }
    } finally {
        appointmentFormLoading.value = false;
    }
};

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Zarządzanie Wizytami',
        href: '/admin/appointments',
    },
    {
        title: appointment.value ? `Wizyta #${appointment.value.id}` : 'Szczegóły wizyty',
    }
];

// Inicjalizacja
onMounted(() => {
    fetchAppointment();
});
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="container mx-auto px-4 sm:px-6 py-8">
            <!-- Nagłówek -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6 gap-4">
                <div>
                    <h1 class="text-xl sm:text-2xl font-bold tracking-tight">
                        Szczegóły wizyty {{ appointment?.id ? `#${appointment.id}` : '' }}
                    </h1>
                </div>
                <Button v-if="appointment" @click="goToEdit"
                        class="bg-nova-primary hover:bg-nova-accent dark:bg-nova-accent hover:dark:bg-nova-primary dark:text-nova-light w-full sm:w-auto">
                    <Icon name="edit" size="16" class="mr-1"/>
                    Edytuj wizytę
                </Button>
            </div>

            <!-- Wskaźnik ładowania -->
            <div v-if="loading" class="py-12 flex justify-center">
                <Icon name="loader2" class="animate-spin h-8 w-8 text-gray-500"/>
            </div>

            <!-- Komunikat błędu -->
            <div v-else-if="error" class="py-6 bg-red-50 text-red-600 px-4 rounded-lg shadow-sm">
                <div class="flex items-center">
                    <Icon name="alert-triangle" class="h-5 w-5 mr-2 flex-shrink-0"/>
                    <span>{{ error }}</span>
                </div>
            </div>

            <!-- Dane wizyty -->
            <div v-else-if="appointment" class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
                <!-- Status wizyty -->
                <div class="p-2 sm:p-4 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900">
                    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4">
                        <div>
                            <h2 class="text-lg font-medium">Status wizyty</h2>
                            <span
                                :class="`px-2.5 py-0.5 rounded-full text-xs font-medium inline-flex items-center ${getStatusClass(appointment.status)}`">
                                {{ formatStatus(appointment.status) }}
                            </span>
                        </div>
                        <div class="text-left sm:text-right">
                            <p class="text-sm text-gray-500 dark:text-gray-400">Utworzono</p>
                            <p class="font-medium">{{ formatDateTime(appointment.created_at) }}</p>
                        </div>
                    </div>
                </div>

                <!-- Podstawowe informacje -->
                <div class="p-2 sm:p-4 grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Lewa kolumna -->
                    <div class="space-y-2">
                        <!-- Informacje o pacjencie -->
                        <div class="space-y-2">
                            <h3 class="text-md font-medium border-b pb-1 border-gray-200 dark:border-gray-700">
                                Pacjent</h3>
                            <div v-if="appointment.patient" class="bg-gray-50 dark:bg-gray-900 p-4 rounded">
                                <div class="flex items-center">
                                    <div v-if="appointment.patient.profile_picture_url" class="flex-shrink-0 mr-3">
                                        <img :src="appointment.patient.profile_picture_url" alt="Zdjęcie pacjenta"
                                             class="h-10 w-10 rounded-full object-cover"/>
                                    </div>
                                    <div v-else class="flex-shrink-0 mr-3">
                                        <div
                                            class="h-10 w-10 rounded-full bg-gray-300 flex items-center justify-center">
                                            <Icon name="user" class="text-gray-500" size="20"/>
                                        </div>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <h4 class="font-medium truncate">{{ appointment.patient.name }}</h4>
                                        <p class="text-sm text-gray-500 truncate">{{ appointment.patient.email }}</p>
                                    </div>
                                </div>

                                <div class="mt-3 pt-3 border-t border-gray-200 dark:border-gray-700">
                                    <router-link :to="`/admin/patients/${appointment.patient.id}`">
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            class="w-full sm:w-auto bg-nova-primary text-nova-light hover:bg-nova-accent dark:bg-nova-accent hover:dark:bg-nova-primary dark:text-nova-light"
                                        >
                                            Zobacz profil pacjenta
                                        </Button>
                                    </router-link>
                                </div>
                            </div>
                        </div>

                        <!-- Informacje o lekarzu -->
                        <div class="space-y-2">
                            <h3 class="text-md font-medium border-b pb-1 border-gray-200 dark:border-gray-700">
                                Lekarz</h3>
                            <div v-if="appointment.doctor" class="bg-gray-50 dark:bg-gray-900 p-4 rounded">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 mr-3">
                                        <div
                                            class="h-10 w-10 rounded-full bg-gray-300 flex items-center justify-center">
                                            <Icon name="user-md" class="text-gray-500" size="20"/>
                                        </div>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <h4 class="font-medium truncate">{{ appointment.doctor.name }}</h4>
                                        <p class="text-sm text-gray-500 truncate">
                                            {{ appointment.doctor.specialization || 'Brak specjalizacji' }}
                                        </p>
                                    </div>
                                </div>

                                <div class="mt-3 pt-3 border-t border-gray-200 dark:border-gray-700">
                                    <router-link :to="`/admin/doctors/${appointment.doctor.id}`">
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            class="w-full sm:w-auto bg-nova-primary text-nova-light hover:bg-nova-accent dark:bg-nova-accent hover:dark:bg-nova-primary dark:text-nova-light"
                                        >
                                            Zobacz profil lekarza
                                        </Button>
                                    </router-link>
                                </div>
                            </div>
                        </div>

                        <!-- Procedura -->
                        <div class="space-y-2">
                            <h3 class="text-md font-medium border-b pb-1 border-gray-200 dark:border-gray-700">
                                Procedura</h3>
                            <div v-if="appointment.procedure" class="bg-gray-50 dark:bg-gray-900 p-4 rounded">
                                <h4 class="font-medium">{{ appointment.procedure.name }}</h4>
                                <p v-if="appointment.procedure.description"
                                   class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                    {{ appointment.procedure.description }}
                                </p>
                                <p v-else class="text-sm text-gray-500 italic mt-1">Brak opisu</p>
                                <div class="mt-2 flex flex-col sm:flex-row sm:justify-between">
                                    <p class="font-medium">
                                        Cena: {{
                                            appointment.procedure.base_price ? `${appointment.procedure.base_price} PLN` : (appointment.procedure.price ? `${appointment.procedure.price} PLN` : 'Nie określono')
                                        }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Prawa kolumna -->
                    <div class="space-y-2">
                        <!-- Termin wizyty -->
                        <div class="space-y-2">
                            <h3 class="text-md font-medium border-b pb-1 border-gray-200 dark:border-gray-700">Termin
                                wizyty</h3>
                            <div class="bg-gray-50 dark:bg-gray-900 p-4 rounded">
                                <div class="flex items-center mb-1">
                                    <Icon name="calendar" size="18" class="mr-2 text-gray-500 flex-shrink-0"/>
                                    <span>{{ formatDateTime(appointment.appointment_datetime) }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Notatki pacjenta -->
                        <div class="space-y-2">
                            <h3 class="text-md font-medium border-b pb-1 border-gray-200 dark:border-gray-700">Notatki
                                pacjenta</h3>
                            <div class="bg-gray-50 dark:bg-gray-900 p-4 rounded min-h-[100px]">
                                <p v-if="appointment.patient_notes" class="whitespace-pre-line">
                                    {{ appointment.patient_notes }}</p>
                                <p v-else class="text-gray-500 italic">Brak notatek od pacjenta</p>
                            </div>
                        </div>

                        <!-- Notatki lekarza -->
                        <div class="space-y-2">
                            <h3 class="text-md font-medium border-b pb-1 border-gray-200 dark:border-gray-700">Notatki
                                lekarza</h3>
                            <div class="bg-gray-50 dark:bg-gray-900 p-4 rounded min-h-[100px]">
                                <p v-if="appointment.doctor_notes" class="whitespace-pre-line">
                                    {{ appointment.doctor_notes }}</p>
                                <p v-else class="text-gray-500 italic">Brak notatek od lekarza</p>
                            </div>
                        </div>

                        <!-- Historia zmian -->
                        <div class="space-y-2">
                            <h3 class="text-md font-medium border-b pb-1 border-gray-200 dark:border-gray-700">
                                Historia</h3>
                            <div class="bg-gray-50 dark:bg-gray-900 p-4 rounded">
                                <div class="flex flex-col sm:flex-row sm:justify-between gap-4">
                                    <div>
                                        <p class="text-sm text-gray-500">Ostatnia aktualizacja</p>
                                        <p>{{ formatDateTime(appointment.updated_at) }}</p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-500">Utworzono</p>
                                        <p>{{ formatDateTime(appointment.created_at) }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Przyciski akcji -->
                <div
                    class="p-2 sm:p-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 flex flex-col sm:flex-row sm:justify-end gap-3">
                    <Button variant="outline" class="w-full sm:w-auto text-nova-light bg-nova-primary hover:bg-nova-accent dark:bg-nova-accent hover:dark:bg-nova-primary dark:text-nova-light" @click="goBack">
                        Powrót
                    </Button>
                </div>
            </div>
        </div>

        <!-- Modal edycji wizyty -->
        <div v-if="showEditModal && appointment"
             class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
            <div class="bg-white dark:bg-gray-800 rounded-xl p-6 max-w-md w-full mx-4 shadow-lg">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-medium">Edycja wizyty #{{ appointment.id }}</h3>
                    <Button variant="ghost" class="h-8 w-8 p-0" @click="closeEditForm">
                        <Icon name="x" size="18"/>
                    </Button>
                </div>

                <div class="space-y-4">
                    <!-- Pacjent (tylko informacyjnie) -->
                    <div class="space-y-2">
                        <Label>Pacjent</Label>
                        <p class="text-sm p-2 bg-gray-50 dark:bg-gray-900 rounded">{{ appointment.patient.name }}</p>
                    </div>

                    <!-- Lekarz (tylko informacyjnie) -->
                    <div class="space-y-2">
                        <Label>Lekarz</Label>
                        <p class="text-sm p-2 bg-gray-50 dark:bg-gray-900 rounded">{{ appointment.doctor.name }}</p>
                    </div>

                    <!-- Data wizyty -->
                    <div class="space-y-2">
                        <Label>Data wizyty</Label>
                        <Popover>
                            <PopoverTrigger as-child>
                                <Button
                                    variant="outline"
                                    class="w-full justify-start text-left font-normal"
                                >
                                    <Icon name="calendar" size="16" class="mr-2"/>
                                    {{
                                        selectedDate ? formatDateTime(selectedDate.toISOString()) : "Wybierz datę wizyty"
                                    }}
                                </Button>
                            </PopoverTrigger>
                            <PopoverContent class="w-auto p-0">
                                <Calendar v-model="selectedDate" initial-focus mode="single"
                                          @update:model-value="onAppointmentDateChange"/>
                            </PopoverContent>
                        </Popover>
                        <div v-if="appointmentErrors.appointment_datetime" class="text-sm text-red-500">
                            {{ appointmentErrors.appointment_datetime[0] }}
                        </div>
                    </div>

                    <!-- Status -->
                    <div class="space-y-2">
                        <Label for="edit-status">Status wizyty</Label>
                        <select
                            id="edit-status"
                            v-model="appointment.status"
                            class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                            :class="{'border-red-500': appointmentErrors.status}"
                        >
                            <option v-for="status in statusOptions" :key="status.value" :value="status.value">
                                {{ status.label }}
                            </option>
                        </select>
                        <div v-if="appointmentErrors.status" class="text-sm text-red-500">
                            {{ appointmentErrors.status[0] }}
                        </div>
                    </div>

                    <!-- Notatki pacjenta -->
                    <div class="space-y-2">
                        <Label for="patient-notes">Notatki pacjenta</Label>
                        <textarea
                            id="patient-notes"
                            v-model="appointment.patient_notes"
                            class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                            :class="{'border-red-500': appointmentErrors.patient_notes}"
                            rows="3"
                            placeholder="Notatki od pacjenta"
                        ></textarea>
                        <div v-if="appointmentErrors.patient_notes" class="text-sm text-red-500">
                            {{ appointmentErrors.patient_notes[0] }}
                        </div>
                    </div>

                    <!-- Notatki lekarza -->
                    <div class="space-y-2">
                        <Label for="doctor-notes">Notatki lekarza</Label>
                        <textarea
                            id="doctor-notes"
                            v-model="appointment.doctor_notes"
                            class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                            :class="{'border-red-500': appointmentErrors.doctor_notes}"
                            rows="3"
                            placeholder="Notatki od lekarza"
                        ></textarea>
                        <div v-if="appointmentErrors.doctor_notes" class="text-sm text-red-500">
                            {{ appointmentErrors.doctor_notes[0] }}
                        </div>
                    </div>

                    <!-- Przyciski -->
                    <div class="flex justify-end gap-3 pt-4">
                        <Button
                            type="button"
                            variant="outline"
                            @click="closeEditForm"
                            class="bg-nova-accent hover:bg-nova-primary dark:bg-nova-primary hover:dark:bg-nova-light hover:dark:text-nova-primary"
                        >
                            Anuluj
                        </Button>
                        <Button
                            @click="updateAppointment"
                            :disabled="appointmentFormLoading"
                            class="flex bg-nova-primary hover:bg-nova-accent dark:bg-nova-accent hover:dark:bg-nova-primary dark:text-nova-light items-center gap-2"
                        >
                            <Icon v-if="appointmentFormLoading" name="loader2" class="animate-spin " size="16"/>
                            <span>Aktualizuj</span>
                        </Button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.badge {
    display: inline-flex;
    align-items: center;
    padding: 0.25rem 0.5rem;
    border-radius: 9999px;
    font-size: 0.75rem;
    font-weight: 500;
}
</style>
