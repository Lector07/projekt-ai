<script setup lang="ts">
import { ref, onMounted, computed, watch } from 'vue';
import axios from 'axios';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectGroup, SelectItem, SelectLabel, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import Icon from '@/components/Icon.vue';
import type { BreadcrumbItem } from '@/types';
import { useRoute, useRouter } from 'vue-router';
import { useToast } from 'primevue/usetoast';
import { Card, CardContent, CardHeader, CardTitle, CardDescription, CardFooter } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import DoctorAvailabilityCalendar from '@/components/DoctorAvailabilityCalendar.vue';

const router = useRouter();
const route = useRoute();
const toast = useToast();

interface Procedure {
    id: number;
    name: string;
    base_price: number;
    description?: string;
    category_id: number; // Dodane pole
}

interface Doctor {
    id: number;
    first_name: string;
    last_name: string;
    specialization: string;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Panel Pacjenta', href: '/dashboard' },
    { title: 'Umów Wizytę' },
];

const initialProcedureIdFromQuery = ref<number | null>(null);
const selectedProcedureId = ref<string | undefined>(undefined);
const selectedDoctorId = ref<string | undefined>(undefined);
const appointmentDate = ref<string>('');
const appointmentTime = ref<string>('');
const patientNotes = ref<string>('');
const availabilityStatus = ref<{ available: boolean; message: string | null }>({ available: true, message: null });
const checkingAvailability = ref(false);

const procedures = ref<Procedure[]>([]);
const doctors = ref<Doctor[]>([]);
const loadingProcedures = ref(true);
const loadingDoctors = ref(false);

const formSubmitting = ref(false);
const formErrors = ref<Record<string, any>>({});

const selectedProcedureDetails = computed(() => procedures.value.find(p => p.id.toString() === selectedProcedureId.value));

async function fetchProcedures() {
    loadingProcedures.value = true;
    try {
        const response = await axios.get('/api/v1/procedures', { params: { limit: 999 } });
        procedures.value = response.data.data || response.data || [];
        if (initialProcedureIdFromQuery.value && procedures.value.some(p => p.id === initialProcedureIdFromQuery.value)) {
            selectedProcedureId.value = initialProcedureIdFromQuery.value.toString();
        }
    } catch (error) {
        console.error("Błąd podczas pobierania zabiegów:", error);
        toast.add({ severity: 'error', summary: 'Błąd', detail: 'Nie udało się pobrać listy zabiegów.', life: 3000 });
    } finally {
        loadingProcedures.value = false;
    }
}

async function fetchDoctorsForProcedure(procedureIdAsString: string) {
    const procedureId = Number(procedureIdAsString);
    if (!procedureId) {
        doctors.value = [];
        selectedDoctorId.value = undefined;
        return;
    }
    loadingDoctors.value = true;
    selectedDoctorId.value = undefined;

    try {
        console.log("Pobieranie lekarzy dla procedury ID:", procedureId);
        const response = await axios.get('/api/v1/doctors', {
            params: {
                procedure_id: procedureId
            }
        });

        console.log("Odpowiedź API lekarzy:", response.data);
        doctors.value = response.data.data || [];

        if (doctors.value.length === 0) {
            toast.add({
                severity: 'info',
                summary: 'Informacja',
                detail: 'Brak specjalistów dla tego zabiegu.',
                life: 3000
            });
        }
    } catch (error) {
        console.error("Błąd podczas pobierania specjalistów:", error);
        toast.add({
            severity: 'error',
            summary: 'Błąd',
            detail: 'Nie udało się pobrać listy specjalistów.',
            life: 3000
        });
        doctors.value = [];
    } finally {
        loadingDoctors.value = false;
    }
}

watch(selectedProcedureId, (newProcedureId) => {
    doctors.value = [];
    selectedDoctorId.value = undefined;
    appointmentDate.value = '';
    appointmentTime.value = '';
    if (newProcedureId) {
        fetchDoctorsForProcedure(newProcedureId);
    }
});

watch(selectedDoctorId, () => {
    appointmentDate.value = '';
    appointmentTime.value = '';
});

const combinedDateTime = computed(() => {
    if (appointmentDate.value && appointmentTime.value) {
        return `${appointmentDate.value} ${appointmentTime.value}:00`;
    }
    return null;
});

const canSubmit = computed(() => {
    return selectedProcedureId.value &&
        selectedDoctorId.value &&
        appointmentDate.value &&
        appointmentTime.value &&
        !formSubmitting.value &&
        availabilityStatus.value.available &&
        !checkingAvailability.value;
});

async function checkAppointmentAvailability() {
    if (!selectedDoctorId.value || !combinedDateTime.value) {
        availabilityStatus.value = { available: true, message: null };
        return;
    }

    checkingAvailability.value = true;
    availabilityStatus.value = { available: true, message: null };

    try {
        const response = await axios.get('/api/v1/appointments/check-availability', {
            params: {
                doctor_id: selectedDoctorId.value,
                appointment_datetime: combinedDateTime.value
            }
        });

        availabilityStatus.value = {
            available: response.data.available,
            message: response.data.message || null
        };
    } catch (error: any) {
        console.error('Błąd podczas sprawdzania dostępności:', error);

        if (error.response?.status === 409) {
            availabilityStatus.value = {
                available: false,
                message: error.response.data.message || 'Ten termin nie jest dostępny (konflikt z inną wizytą).'
            };
        } else if (error.response?.status === 422) {
            availabilityStatus.value = {
                available: false,
                message: error.response.data.errors?.appointment_datetime?.[0] ||
                    'Nieprawidłowa data wizyty. Wybierz inny termin.'
            };
        } else {
            availabilityStatus.value = {
                available: false,
                message: 'Wystąpił błąd podczas sprawdzania dostępności terminu.'
            };
        }
    } finally {
        checkingAvailability.value = false;
    }
}

watch([selectedDoctorId, combinedDateTime], () => {
    if (selectedDoctorId.value && combinedDateTime.value) {
        checkAppointmentAvailability();
    } else {
        availabilityStatus.value = { available: true, message: null }; // Resetuj, jeśli dane są niekompletne
    }
});



async function submitAppointment() {
    if (!canSubmit.value) {
        if (!availabilityStatus.value.available && availabilityStatus.value.message) {
            toast.add({ severity: 'error', summary: 'Termin niedostępny', detail: availabilityStatus.value.message, life: 5000 });
        } else if (checkingAvailability.value) {
            toast.add({ severity: 'info', summary: 'Proszę czekać', detail: 'Sprawdzanie dostępności terminu jest w toku.', life: 3000 });
        } else {
            toast.add({ severity: 'warn', summary: 'Niekompletne dane', detail: 'Proszę wybrać usługę, lekarza oraz termin z kalendarza.', life: 3000 });
        }
        return;
    }

    formSubmitting.value = true;
    formErrors.value = {};

    const payload = {
        procedure_id: Number(selectedProcedureId.value),
        doctor_id: Number(selectedDoctorId.value),
        appointment_datetime: combinedDateTime.value,
        patient_notes: patientNotes.value.trim() || null,
    };

    try {
        await axios.post('/api/v1/patient/appointments', payload);
        toast.add({ severity: 'success', summary: 'Sukces!', detail: 'Twoja wizyta została pomyślnie umówiona.', life: 4000 });
        router.push({ name: 'patient.appointments' });
    } catch (error: any) {
        console.error("Błąd podczas umawiania wizyty:", error);
        if (error.response?.status === 422) {
            formErrors.value = error.response.data.errors;
            if (formErrors.value.appointment_datetime) {
                availabilityStatus.value = {
                    available: false,
                    message: formErrors.value.appointment_datetime[0]
                };
                toast.add({ severity: 'error', summary: 'Termin zajęty', detail: formErrors.value.appointment_datetime[0], life: 4000 });
            } else {
                toast.add({ severity: 'error', summary: 'Błąd walidacji', detail: 'Sprawdź poprawność wprowadzonych danych.', life: 4000 });
            }
        } else if (error.response?.status === 409) {
            availabilityStatus.value = {
                available: false,
                message: error.response.data.message
            };
            toast.add({ severity: 'error', summary: 'Termin niedostępny', detail: availabilityStatus.value.message, life: 5000 });
        } else {
            toast.add({ severity: 'error', summary: 'Błąd', detail: error.response?.data?.message || 'Nie udało się umówić wizyty. Spróbuj ponownie.', life: 4000 });
        }
    } finally {
        formSubmitting.value = false;
    }
}

function handleDateTimeSelected(date: string, time: string) {
    appointmentDate.value = date;
    appointmentTime.value = time;
    formErrors.value.appointment_datetime = undefined;
}

onMounted(async () => {
    const queryProcedureId = route.query.procedure_id;
    if (queryProcedureId && typeof queryProcedureId === 'string') {
        const parsedId = parseInt(queryProcedureId, 10);
        if (!isNaN(parsedId)) {
            initialProcedureIdFromQuery.value = parsedId;
        }
    }
    await fetchProcedures();
});

watch([() => procedures.value, initialProcedureIdFromQuery], ([procs, initialId]) => {
    if (initialId && procs && procs.length > 0 && !selectedProcedureId.value) {
        const foundProcedure = procs.find(p => p.id === initialId);
        if (foundProcedure) {
            selectedProcedureId.value = initialId.toString();
        }
    }
}, { immediate: false });

</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="container mx-auto py-8 px-4 sm:px-6 lg:px-8 flex justify-center">
            <Card class="w-full max-w-2xl dark:bg-gray-800 border dark:border-gray-700 shadow-xl">
                <CardHeader class="pb-4">
                    <CardTitle class="text-3xl font-bold text-center text-gray-800 dark:text-gray-100">Umów Wizytę</CardTitle>
                    <CardDescription class="text-center text-gray-500 dark:text-gray-400 mt-1">
                        Wypełnij poniższy formularz, aby zarezerwować termin.
                    </CardDescription>
                </CardHeader>
                <Separator class="dark:bg-gray-700"/>
                <CardContent class="pt-6 space-y-8">
                    <div class="space-y-3 p-1 rounded-lg">
                        <div class="flex items-center gap-3 mb-1">
                            <div class="flex items-center mb-2 justify-center w-8 h-8 rounded-full bg-nova-primary text-white font-bold text-lg shrink-0">1</div>
                            <Label for="procedure" class="text-xl mb-2 font-semibold text-gray-700 dark:text-gray-200">Wybierz Usługę</Label>
                        </div>
                        <Select
                            v-model="selectedProcedureId"
                            :disabled="loadingProcedures || !!initialProcedureIdFromQuery"
                        >
                            <SelectTrigger id="procedure" class="w-full h-12 text-base dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200">
                                <SelectValue :placeholder="loadingProcedures ? 'Ładowanie usług...' : (selectedProcedureDetails ? selectedProcedureDetails.name + ` (${selectedProcedureDetails.base_price} zł)` : 'Wybierz rodzaj zabiegu')" />
                            </SelectTrigger>
                            <SelectContent class="dark:bg-gray-800 dark:border-gray-700">
                                <SelectGroup>
                                    <SelectLabel v-if="procedures.length > 0" class="dark:text-gray-400">Dostępne zabiegi</SelectLabel>
                                    <SelectItem
                                        v-for="proc in procedures"
                                        :key="proc.id"
                                        :value="proc.id.toString()"
                                        class="dark:text-gray-200 hover:dark:bg-gray-700"
                                    >
                                        {{ proc.name }} <span class="text-sm text-gray-500 dark:text-gray-400 ml-2">({{ proc.base_price }} zł)</span>
                                    </SelectItem>
                                    <div v-if="!loadingProcedures && procedures.length === 0" class="p-2 text-sm text-center text-gray-500 dark:text-gray-400">
                                        Brak dostępnych zabiegów.
                                    </div>
                                </SelectGroup>
                            </SelectContent>
                        </Select>
                        <p v-if="selectedProcedureDetails?.description && !initialProcedureIdFromQuery" class="text-sm text-gray-500 dark:text-gray-400 mt-1 px-1">
                            {{ selectedProcedureDetails.description }}
                        </p>
                        <div v-if="formErrors.procedure_id" class="text-sm text-red-500 mt-1">{{ formErrors.procedure_id[0] }}</div>
                    </div>

                    <div class="space-y-3 p-1 rounded-lg" v-if="selectedProcedureId">
                        <div class="flex items-center gap-3 mb-1">
                            <div class="flex mb-2 items-center justify-center w-8 h-8 rounded-full bg-nova-primary text-white font-bold text-lg shrink-0">2</div>
                            <Label for="doctor" class="text-xl mb-2 font-semibold text-gray-700 dark:text-gray-200">Wybierz Specjalistę</Label>
                        </div>
                        <Select v-model="selectedDoctorId" :disabled="loadingDoctors || doctors.length === 0">
                            <SelectTrigger id="doctor" class="w-full h-12 text-base dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200">
                                <SelectValue :placeholder="loadingDoctors ? 'Ładowanie specjalistów...' : (doctors.length === 0 && !loadingDoctors ? 'Brak specjalistów dla tej usługi' : 'Wybierz specjalistę')" />
                            </SelectTrigger>
                            <SelectContent class="dark:bg-gray-800 dark:border-gray-700">
                                <SelectGroup>
                                    <SelectLabel v-if="doctors.length > 0" class="dark:text-gray-400">Dostępni specjaliści</SelectLabel>
                                    <SelectItem v-for="doc in doctors" :key="doc.id" :value="doc.id.toString()" class="dark:text-gray-200 hover:dark:bg-gray-700">
                                        Dr {{ doc.first_name }} {{ doc.last_name }} <span class="text-sm text-gray-500 dark:text-gray-400 ml-2">({{ doc.specialization }})</span>
                                    </SelectItem>
                                </SelectGroup>
                            </SelectContent>
                        </Select>
                        <div v-if="formErrors.doctor_id" class="text-sm text-red-500 mt-1">{{ formErrors.doctor_id[0] }}</div>
                    </div>

                    <div class="space-y-3 p-1 rounded-lg" v-if="selectedDoctorId">
                        <div class="flex items-center gap-3 mb-1">
                            <div class="flex mb-2 items-center justify-center w-8 h-8 rounded-full bg-nova-primary text-white font-bold text-lg shrink-0">3</div>
                            <Label class="text-xl mb-2 font-semibold text-gray-700 dark:text-gray-200">Wybierz Termin Wizyty</Label>
                        </div>

                        <DoctorAvailabilityCalendar
                            :doctor-id="selectedDoctorId ? Number(selectedDoctorId) : null"
                            :procedure-id="selectedProcedureId ? Number(selectedProcedureId) : null"
                            @dateSelected="handleDateTimeSelected"
                            class="border dark:border-gray-700 rounded-md p-4 bg-white dark:bg-gray-700/30"
                        />
                        <div v-if="checkingAvailability" class="text-sm text-gray-500 dark:text-gray-400 mt-2">
                            Sprawdzanie dostępności terminu...
                        </div>
                        <div v-if="!checkingAvailability && availabilityStatus.message"
                             :class="availabilityStatus.available ? 'text-green-600 dark:text-green-400' : 'text-red-500 dark:text-red-400'"
                             class="text-sm mt-2 p-2 rounded-md"
                             :style="{ backgroundColor: availabilityStatus.available ? 'rgba(239, 246, 239, 0.7)' : 'rgba(254, 242, 242, 0.7)' }"
                        >
                            {{ availabilityStatus.message }}
                        </div>

                        <div v-if="appointmentDate && appointmentTime" class="mt-4 p-3 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-700 rounded-md">
                            <p class="text-sm font-medium text-green-700 dark:text-green-300">
                                <Icon name="check-circle" class="inline-block mr-2 h-5 w-5" />
                                Wybrany termin: {{ appointmentDate }}, godz. {{ appointmentTime }}
                            </p>
                        </div>
                        <div v-if="formErrors.appointment_datetime" class="text-sm text-red-500 mt-1 text-center">{{ formErrors.appointment_datetime[0] }}</div>
                    </div>

                    <div class="space-y-3 p-1 rounded-lg" v-if="selectedDoctorId && appointmentDate && appointmentTime">
                        <div class="flex items-center gap-3 mb-1">
                            <div class="flex mb-2 items-center justify-center w-8 h-8 rounded-full bg-nova-primary text-white font-bold text-lg shrink-0">4</div>
                            <Label for="patient-notes" class="text-xl mb-2 font-semibold text-gray-700 dark:text-gray-200">Dodatkowe Informacje <span class="text-sm font-normal text-gray-500 dark:text-gray-400">(opcjonalnie)</span></Label>
                        </div>
                        <Textarea
                            id="patient-notes"
                            v-model="patientNotes"
                            placeholder="Jeśli masz jakieś pytania lub dodatkowe informacje dla lekarza, wpisz je tutaj..."
                            class="min-h-[120px] dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200"
                            rows="4"
                        />
                        <div v-if="formErrors.patient_notes" class="text-sm text-red-500 mt-1">{{ formErrors.patient_notes[0] }}</div>
                    </div>
                </CardContent>
                <Separator class="dark:bg-gray-700" v-if="selectedProcedureId"/>
                <CardFooter class="pt-6" v-if="selectedProcedureId">
                    <Button
                        @click="submitAppointment"
                        :disabled="!canSubmit"
                        class="w-full text-lg py-3 h-12 bg-nova-primary text-white hover:bg-nova-accent dark:bg-nova-accent dark:text-gray-900 dark:hover:bg-nova-primary"
                    >
                        <Icon v-if="formSubmitting" name="loader-2" class="mr-2 h-5 w-5 animate-spin" />
                        {{ formSubmitting ? 'Rezerwowanie...' : 'Zarezerwuj Wizytę' }}
                    </Button>
                </CardFooter>
            </Card>
        </div>
        <Toast position="bottom-right" />
    </AppLayout>
</template>
