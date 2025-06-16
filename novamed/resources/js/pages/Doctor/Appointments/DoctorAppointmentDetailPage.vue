<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue, SelectGroup } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { Label } from '@/components/ui/label';
import Icon from '@/components/Icon.vue';
import { Badge } from '@/components/ui/badge';
import type { BreadcrumbItem } from '@/types';
import { useToast } from 'primevue/usetoast';
import { Skeleton } from '@/components/ui/skeleton';
import { Separator } from '@/components/ui/separator';

interface PatientInfo { id: number; name: string; email?:string; }
interface ProcedureInfo { id: number; name: string; base_price: number; description?: string;}
interface AppointmentFullDetail {
    id: number;
    appointment_datetime: string;
    status: string;
    patient_notes?: string | null;
    doctor_notes?: string | null;
    patient: PatientInfo;
    doctor: { id: number; first_name: string; last_name: string; specialization: string; };
    procedure: ProcedureInfo;
    created_at: string;
    updated_at?: string;
}

const props = defineProps<{ id: string }>();
const router = useRouter();
const toast = useToast();

const appointment = ref<AppointmentFullDetail | null>(null);
const editableStatus = ref<string>('');
const editableDoctorNotes = ref<string>('');

const loading = ref(true);
const saving = ref(false);
const error = ref<string | null>(null);

const possibleStatusChanges: Record<string, { value: string; label: string }[]> = {
    scheduled: [ { value: 'confirmed', label: 'Potwierdź wizytę' }, { value: 'cancelled_by_clinic', label: 'Odwołaj (Klinika)'} ],
    confirmed: [ { value: 'completed', label: 'Oznacz jako Zakończona' }, { value: 'no_show', label: 'Oznacz jako Nieobecność'}, { value: 'cancelled_by_clinic', label: 'Odwołaj (Klinika)'} ],
};

const availableStatusOptions = computed(() => {
    if (appointment.value && appointment.value.status) {
        return possibleStatusChanges[appointment.value.status] || [];
    }
    return [];
});

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    { title: 'Panel Lekarza', href: '/doctor/dashboard' },
    { title: 'Moje Wizyty', href: '/doctor/appointments' },
    { title: appointment.value ? `Wizyta #${appointment.value.id}` : 'Szczegóły Wizyty' },
]);

const formatDateTime = (dateStr?: string): string => {
    if (!dateStr) return 'Brak danych';
    try {
        return new Date(dateStr).toLocaleString('pl-PL', {
            year: 'numeric', month: 'long', day: 'numeric',
            hour: '2-digit', minute: '2-digit'
        });
    } catch (e) { return 'Niepr. data'; }
};

const getStatusInfo = (statusVal?: string): { text: string; variant: 'default' | 'secondary' | 'destructive' | 'outline' } => {
    if (!statusVal) return { text: 'Nieokreślony', variant: 'secondary' };
    switch (statusVal) {
        case 'scheduled': return { text: 'Zaplanowana', variant: 'outline' };
        case 'confirmed': return { text: 'Potwierdzona', variant: 'secondary' };
        case 'completed': return { text: 'Zakończona', variant: 'default' };
        case 'cancelled_by_patient': return { text: 'Odwołana (Pacjent)', variant: 'destructive' };
        case 'cancelled_by_clinic': return { text: 'Odwołana (Klinika)', variant: 'destructive' };
        case 'no_show': return { text: 'Nieobecność', variant: 'destructive' };
        default: return { text: statusVal, variant: 'secondary' };
    }
};

async function fetchAppointmentDetails() {
    loading.value = true;
    error.value = null;
    try {
        const response = await axios.get(`/api/v1/doctor/appointments/${props.id}`);
        appointment.value = response.data.data;
        if (appointment.value) {
            editableStatus.value = appointment.value.status;
            editableDoctorNotes.value = appointment.value.doctor_notes || '';
        }
    } catch (err:any) {
        console.error("Błąd podczas pobierania szczegółów wizyty:", err);
        if (err.response?.status === 403 || err.response?.status === 404) {
            error.value = err.response.data.message || "Nie masz dostępu do tej wizyty lub wizyta nie istnieje.";
        } else {
            error.value = "Nie udało się załadować danych wizyty.";
        }
        appointment.value = null;
    } finally {
        loading.value = false;
    }
}

async function saveAppointmentChanges() {
    if (!appointment.value) return;
    if (editableStatus.value === appointment.value.status && editableDoctorNotes.value === (appointment.value.doctor_notes || '')) {
        toast.add({severity: 'info', summary: 'Informacja', detail: 'Nie wprowadzono żadnych zmian.', life: 3000});
        return;
    }
    saving.value = true;
    try {
        const payload: { status?: string; doctor_notes?: string } = {};
        if (editableStatus.value !== appointment.value.status) {
            payload.status = editableStatus.value;
        }
        if (editableDoctorNotes.value !== (appointment.value.doctor_notes || '')) {
            payload.doctor_notes = editableDoctorNotes.value;
        }

        if (Object.keys(payload).length === 0) {
            toast.add({severity: 'info', summary: 'Informacja', detail: 'Nie wprowadzono żadnych zmian do zapisu.', life: 3000});
            saving.value = false;
            return;
        }

        const response = await axios.put(`/api/v1/doctor/appointments/${appointment.value.id}`, payload);
        appointment.value = response.data.data;
        if (appointment.value) {
            editableStatus.value = appointment.value.status;
            editableDoctorNotes.value = appointment.value.doctor_notes || '';
        }
        toast.add({severity: 'success', summary: 'Sukces', detail: 'Zmiany w wizycie zostały zapisane.', life: 3000});
    } catch (err:any) {
        console.error("Błąd podczas zapisywania zmian wizyty:", err);
        let detailError = 'Nie udało się zapisać zmian.';
        if (err.response?.data?.errors) {
            detailError = Object.values(err.response.data.errors).flat().join(' ');
        } else if (err.response?.data?.message) {
            detailError = err.response.data.message;
        }
        toast.add({severity: 'error', summary: 'Błąd', detail: detailError, life: 4000});
    } finally {
        saving.value = false;
    }
}

watch(() => props.id, (newId) => {
    if (newId) fetchAppointmentDetails();
}, { immediate: true });

</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="container mx-auto py-6 px-4 sm:px-6 lg:px-8">
            <div v-if="loading" class="space-y-6">
                <Skeleton class="h-10 w-3/4 rounded-md dark:bg-gray-700" />
                <Skeleton class="h-6 w-1/4 rounded-md dark:bg-gray-700" />
                <Separator class="dark:bg-gray-700 my-4"/>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="md:col-span-2 space-y-4">
                        <Skeleton class="h-8 w-1/3 dark:bg-gray-700" />
                        <Skeleton class="h-20 w-full dark:bg-gray-700" />
                        <Skeleton class="h-20 w-full dark:bg-gray-700" />
                    </div>
                    <div class="space-y-4">
                        <Skeleton class="h-8 w-1/2 dark:bg-gray-700" />
                        <Skeleton class="h-10 w-full dark:bg-gray-700" />
                        <Skeleton class="h-24 w-full dark:bg-gray-700" />
                        <Skeleton class="h-10 w-full dark:bg-gray-700" />
                    </div>
                </div>
            </div>
            <div v-else-if="error" class="p-6 text-center text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/20 rounded-lg shadow">
                <Icon name="alert-circle" class="mx-auto h-16 w-16 text-red-500 dark:text-red-400" />
                <p class="mt-4 text-xl font-semibold">{{ error }}</p>
                <Button @click="router.push({ name: 'doctor.appointments.index' })" variant="outline" class="mt-6 dark:text-gray-200 dark:border-gray-600 dark:hover:bg-gray-700">
                    Powrót do listy wizyt
                </Button>
            </div>
            <div v-else-if="appointment" class="space-y-8">
                <div class="flex flex-col sm:flex-row justify-between items-start gap-4">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100">
                            Wizyta #{{ appointment.id }}
                        </h1>
                        <p class="text-gray-500 dark:text-gray-400 text-sm">
                            Zarezerwowano: {{ formatDateTime(appointment.created_at) }}
                            <span v-if="appointment.updated_at && appointment.updated_at !== appointment.created_at">
                                (Ost. akt.: {{ formatDateTime(appointment.updated_at) }})
                            </span>
                        </p>
                    </div>
                    <Badge :variant="getStatusInfo(appointment.status).variant" class="text-base px-4 py-1.5">
                        {{ getStatusInfo(appointment.status).text }}
                    </Badge>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <Card class="lg:col-span-2 dark:bg-gray-800 border dark:border-gray-700 shadow-sm">
                        <CardHeader>
                            <CardTitle class="text-xl text-gray-800 dark:text-gray-100">Szczegóły Wizyty</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-5">
                            <div>
                                <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Pacjent</h3>
                                <p class="text-lg text-gray-900 dark:text-gray-200">{{ appointment.patient.name }}</p>
                                <p v-if="appointment.patient.email" class="text-sm text-gray-600 dark:text-gray-300">{{ appointment.patient.email }}</p>
                            </div>
                            <Separator class="dark:bg-gray-700"/>
                            <div>
                                <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Zabieg</h3>
                                <p class="text-lg text-gray-900 dark:text-gray-200">{{ appointment.procedure.name }}</p>
                                <p v-if="appointment.procedure.description" class="text-sm text-gray-600 dark:text-gray-300 mt-1">{{ appointment.procedure.description }}</p>
                            </div>
                            <Separator class="dark:bg-gray-700"/>
                            <div>
                                <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Termin</h3>
                                <p class="text-lg text-gray-900 dark:text-gray-200">{{ formatDateTime(appointment.appointment_datetime) }}</p>
                            </div>
                            <Separator class="dark:bg-gray-700" v-if="appointment.patient_notes"/>
                            <div v-if="appointment.patient_notes">
                                <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Notatki od pacjenta</h3>
                                <p class="mt-1 text-gray-700 dark:text-gray-200 whitespace-pre-wrap bg-gray-50 dark:bg-gray-700/40 p-3 rounded-md border dark:border-gray-600">{{ appointment.patient_notes }}</p>
                            </div>
                        </CardContent>
                    </Card>

                    <Card class="lg:col-span-1 dark:bg-gray-800 border dark:border-gray-700 shadow-sm">
                        <CardHeader><CardTitle class="text-xl text-gray-800 dark:text-gray-100">Akcje Lekarza</CardTitle></CardHeader>
                        <CardContent class="space-y-6">
                            <div>
                                <Label for="status-select" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Zmień Status Wizyty</Label>
                                <Select v-model="editableStatus" :disabled="availableStatusOptions.length === 0 || saving">
                                    <SelectTrigger id="status-select" class="w-full dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200">
                                        <SelectValue :placeholder="getStatusInfo(appointment.status).text" />
                                    </SelectTrigger>
                                    <SelectContent class="dark:bg-gray-800 dark:border-gray-600">
                                        <SelectGroup>
                                            <SelectItem
                                                v-for="option in availableStatusOptions"
                                                :key="option.value"
                                                :value="option.value"
                                                class="dark:text-gray-200 hover:dark:bg-gray-700"
                                            >
                                                {{ option.label }}
                                            </SelectItem>
                                            <SelectItem v-if="availableStatusOptions.length === 0 && appointment.status" :value="appointment.status" disabled class="dark:text-gray-400">
                                                Brak możliwych zmian statusu
                                            </SelectItem>
                                        </SelectGroup>
                                    </SelectContent>
                                </Select>
                            </div>
                            <div>
                                <Label for="doctor-notes" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Twoje Notatki</Label>
                                <Textarea id="doctor-notes" v-model="editableDoctorNotes" rows="5" class="dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200 min-h-[100px]" placeholder="Dodaj swoje notatki dotyczące wizyty..." :disabled="saving"/>
                            </div>
                            <Button @click="saveAppointmentChanges" :disabled="saving || (editableStatus === appointment.status && editableDoctorNotes === (appointment.doctor_notes || ''))" class="w-full bg-nova-primary text-white hover:bg-nova-accent dark:bg-nova-accent dark:text-gray-900 dark:hover:bg-nova-primary">
                                <Icon v-if="saving" name="loader-2" class="animate-spin mr-2 h-4 w-4"/>
                                Zapisz Zmiany
                            </Button>
                        </CardContent>
                    </Card>
                </div>
                <div class="mt-6">
                    <Button @click="router.push({ name: 'doctor.appointments.index' })" variant="outline" class="dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700">
                        <Icon name="arrow-left" class="mr-2 h-4 w-4"/> Powrót do listy wizyt
                    </Button>
                </div>
            </div>
            <div v-else class="text-center py-10">
                <Icon name="file-search" class="mx-auto h-16 w-16 text-gray-400 dark:text-gray-500" />
                <p class="mt-4 text-xl text-gray-500 dark:text-gray-400">Nie znaleziono wizyty.</p>
                <Button @click="router.push({ name: 'doctor.appointments.index' })" variant="link" class="mt-2">Powrót do listy</Button>
            </div>
            <Toast />
        </div>
    </AppLayout>
</template>
