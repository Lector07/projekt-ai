<script setup lang="ts">
import DoctorAvailabilityCalendar from '@/components/DoctorAvailabilityCalendar.vue';
import Icon from '@/components/Icon.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectGroup, SelectItem, SelectLabel, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Separator } from '@/components/ui/separator';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import axios from 'axios';
import { useToast } from 'primevue/usetoast';
import { computed, onMounted, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';

const router = useRouter();
const route = useRoute();
const toast = useToast();

interface Procedure {
    id: number;
    name: string;
    base_price: number;
    description?: string;
    category_id: number;
}

interface Doctor {
    id: number;
    first_name: string;
    last_name: string;
    specialization: string;
}

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Panel Pacjenta', href: '/dashboard' }, { title: 'Umów Wizytę' }];

const availabilityCalendar = ref(null);
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

const selectedProcedureDetails = computed(() => procedures.value.find((p) => p.id.toString() === selectedProcedureId.value));

const showConfirmationModal = ref(false);
const confirmedAppointment = ref<any>(null);

async function fetchProcedures() {
    loadingProcedures.value = true;
    try {
        const response = await axios.get('/api/v1/procedures', { params: { limit: 999 } });
        procedures.value = response.data.data || response.data || [];
        if (initialProcedureIdFromQuery.value && procedures.value.some((p) => p.id === initialProcedureIdFromQuery.value)) {
            selectedProcedureId.value = initialProcedureIdFromQuery.value.toString();
        }
    } catch (error) {
        console.error('Błąd podczas pobierania zabiegów:', error);
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
        console.log('Pobieranie lekarzy dla procedury ID:', procedureId);
        const response = await axios.get('/api/v1/doctors', {
            params: {
                procedure_id: procedureId,
            },
        });

        console.log('Odpowiedź API lekarzy:', response.data);
        doctors.value = response.data.data || [];

        if (doctors.value.length === 0) {
            toast.add({
                severity: 'info',
                summary: 'Informacja',
                detail: 'Brak specjalistów dla tego zabiegu.',
                life: 3000,
            });
        }
    } catch (error) {
        console.error('Błąd podczas pobierania specjalistów:', error);
        toast.add({
            severity: 'error',
            summary: 'Błąd',
            detail: 'Nie udało się pobrać listy specjalistów.',
            life: 3000,
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
    return (
        selectedProcedureId.value &&
        selectedDoctorId.value &&
        appointmentDate.value &&
        appointmentTime.value &&
        !formSubmitting.value &&
        availabilityStatus.value.available &&
        !checkingAvailability.value
    );
});

const checkAppointmentAvailability = async () => {
    if (!selectedDoctorId.value || !combinedDateTime.value || !selectedProcedureId.value) {
        availabilityStatus.value = { available: true, message: null };
        return;
    }

    checkingAvailability.value = true;
    availabilityStatus.value = { available: true, message: null };

    try {
        const response = await axios.get('/api/v1/appointments/check-availability', {
            params: {
                doctor_id: selectedDoctorId.value,
                appointment_datetime: combinedDateTime.value,
                procedure_id: selectedProcedureId.value,
            },
            skipErrorRedirect: true,
        });

        availabilityStatus.value = {
            available: response.data.available,
            message: response.data.message || null,
        };
    } catch (error) {
        console.error('Błąd podczas sprawdzania dostępności:', error);

        if (error.response) {
            if (
                error.response.status === 409 ||
                (error.response.data && error.response.data.message && error.response.data.message.includes('koliduje z inną wizytą'))
            ) {
                if (availabilityCalendar.value && appointmentTime.value) {
                    availabilityCalendar.value.handleBookingConflict(appointmentTime.value);
                } else {
                    availabilityStatus.value = {
                        available: false,
                        message: error.response.data.message || 'Wybrany termin koliduje z inną wizytą.',
                    };
                }
            } else {
                availabilityStatus.value = {
                    available: false,
                    message: error.response.data.message || 'Nie można zarezerwować tego terminu.',
                };
            }
        } else {
            availabilityStatus.value = {
                available: false,
                message: 'Brak połączenia z serwerem. Spróbuj ponownie później.',
            };
        }
    } finally {
        checkingAvailability.value = false;
    }
};


watch([selectedDoctorId, combinedDateTime], () => {
    if (selectedDoctorId.value && combinedDateTime.value) {
        checkAppointmentAvailability();
    } else {
        availabilityStatus.value = { available: true, message: null };
    }
});



async function submitAppointment() {
    if (!canSubmit.value) {
        if (!availabilityStatus.value.available && availabilityStatus.value.message) {
            toast.add({
                severity: 'error',
                summary: 'Termin niedostępny',
                detail: availabilityStatus.value.message,
                life: 5000,
            });
        } else if (checkingAvailability.value) {
            toast.add({
                severity: 'info',
                summary: 'Proszę czekać',
                detail: 'Sprawdzanie dostępności terminu jest w toku.',
                life: 3000,
            });
        } else {
            toast.add({
                severity: 'warn',
                summary: 'Niekompletne dane',
                detail: 'Proszę wybrać usługę, lekarza oraz termin z kalendarza.',
                life: 3000,
            });
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
        const response = await axios.post('/api/v1/patient/appointments', payload);

        confirmedAppointment.value = response.data.data;

        showConfirmationModal.value = true;

        toast.add({
            severity: 'success',
            summary: 'Sukces!',
            detail: 'Twoja wizyta została pomyślnie umówiona.',
            life: 4000,
        });
    } catch (error: any) {
        console.error('Błąd podczas umawiania wizyty:', error);
        if (error.response?.status === 422) {
            formErrors.value = error.response.data.errors;
            if (formErrors.value.appointment_datetime) {
                availabilityStatus.value = {
                    available: false,
                    message: formErrors.value.appointment_datetime[0],
                };
                toast.add({
                    severity: 'error',
                    summary: 'Termin zajęty',
                    detail: formErrors.value.appointment_datetime[0],
                    life: 4000,
                });
            } else {
                toast.add({
                    severity: 'error',
                    summary: 'Błąd walidacji',
                    detail: 'Sprawdź poprawność wprowadzonych danych.',
                    life: 4000,
                });
            }
        } else if (error.response?.status === 409) {
            if (availabilityCalendar.value && appointmentTime.value) {
                availabilityCalendar.value.handleBookingConflict(appointmentTime.value);
            } else {
                availabilityStatus.value = {
                    available: false,
                    message: error.response.data.message || 'Wybrany termin koliduje z inną wizytą.',
                };
                toast.add({
                    severity: 'error',
                    summary: 'Termin niedostępny',
                    detail: availabilityStatus.value.message,
                    life: 5000,
                });
            }
        } else {
            toast.add({
                severity: 'error',
                summary: 'Błąd',
                detail: error.response?.data?.message || 'Nie udało się umówić wizyty. Spróbuj ponownie.',
                life: 4000,
            });
        }
    } finally {
        formSubmitting.value = false;
    }
}

function closeConfirmationAndRedirect() {
    showConfirmationModal.value = false;
    router.push({ name: 'patient.appointments' });
}

function handleDateTimeSelected(date: string, time: string) {
    appointmentDate.value = date;
    appointmentTime.value = time;
    formErrors.value.appointment_datetime = undefined;

    const currentStatus = availabilityCalendar.value?.errorMessage;
    if (currentStatus && currentStatus.includes('niedostępny')) {
        availabilityStatus.value = {
            available: false,
            message: "Wybrany termin koliduje z inną wizytą."
        };
    }
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

watch(
    [() => procedures.value, initialProcedureIdFromQuery],
    ([procs, initialId]) => {
        if (initialId && procs && procs.length > 0 && !selectedProcedureId.value) {
            const foundProcedure = procs.find((p) => p.id === initialId);
            if (foundProcedure) {
                selectedProcedureId.value = initialId.toString();
            }
        }
    },
    { immediate: false },
);
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="container mx-auto flex justify-center px-4 py-8 sm:px-6 lg:px-8">
            <Card class="w-full max-w-2xl border shadow-xl dark:border-gray-700 dark:bg-gray-800">
                <CardHeader class="pb-4">
                    <CardTitle class="text-center text-3xl font-bold text-gray-800 dark:text-gray-100">Umów Wizytę </CardTitle>
                    <CardDescription class="mt-1 text-center text-gray-500 dark:text-gray-400">
                        Wypełnij poniższy formularz, aby zarezerwować termin.
                    </CardDescription>
                </CardHeader>
                <Separator class="dark:bg-gray-700" />
                <CardContent class="space-y-8 pt-6">
                    <div class="space-y-3 rounded-lg p-1">
                        <div class="mb-1 flex items-center gap-3">
                            <div
                                class="bg-nova-primary mb-2 flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-lg font-bold text-white"
                            >
                                1
                            </div>
                            <Label for="procedure" class="mb-2 text-xl font-semibold text-gray-700 dark:text-gray-200">Zabieg</Label>
                        </div>
                        <Select v-model="selectedProcedureId" :disabled="loadingProcedures || !!initialProcedureIdFromQuery">
                            <SelectTrigger id="procedure" class="h-12 w-full text-base dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                                <SelectValue
                                    :placeholder="
                                        loadingProcedures
                                            ? 'Ładowanie usług...'
                                            : selectedProcedureDetails
                                              ? selectedProcedureDetails.name + ` (${selectedProcedureDetails.base_price} zł)`
                                              : 'Wybierz rodzaj zabiegu'
                                    "
                                />
                            </SelectTrigger>
                            <SelectContent class="dark:border-gray-700 dark:bg-gray-800">
                                <SelectGroup>
                                    <SelectLabel v-if="procedures.length > 0" class="dark:text-gray-400">Dostępne zabiegi </SelectLabel>
                                    <SelectItem
                                        v-for="proc in procedures"
                                        :key="proc.id"
                                        :value="proc.id.toString()"
                                        class="dark:text-gray-200 hover:dark:bg-gray-700"
                                    >
                                        {{ proc.name }} <span class="ml-2 text-sm text-gray-500 dark:text-gray-400">({{ proc.base_price }} zł)</span>
                                    </SelectItem>
                                    <div
                                        v-if="!loadingProcedures && procedures.length === 0"
                                        class="p-2 text-center text-sm text-gray-500 dark:text-gray-400"
                                    >
                                        Brak dostępnych zabiegów.
                                    </div>
                                </SelectGroup>
                            </SelectContent>
                        </Select>
                        <p
                            v-if="selectedProcedureDetails?.description && !initialProcedureIdFromQuery"
                            class="mt-1 px-1 text-sm text-gray-500 dark:text-gray-400"
                        >
                            {{ selectedProcedureDetails.description }}
                        </p>
                        <div v-if="formErrors.procedure_id" class="mt-1 text-sm text-red-500">
                            {{ formErrors.procedure_id[0] }}
                        </div>
                    </div>

                    <div class="space-y-3 rounded-lg p-1" v-if="selectedProcedureId">
                        <div class="mb-1 flex items-center gap-3">
                            <div
                                class="bg-nova-primary mb-2 flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-lg font-bold text-white"
                            >
                                2
                            </div>
                            <Label for="doctor" class="mb-2 text-xl font-semibold text-gray-700 dark:text-gray-200">Wybierz Specjalistę</Label>
                        </div>
                        <Select v-model="selectedDoctorId" :disabled="loadingDoctors || doctors.length === 0">
                            <SelectTrigger id="doctor" class="h-12 w-full text-base dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                                <SelectValue
                                    :placeholder="
                                        loadingDoctors
                                            ? 'Ładowanie specjalistów...'
                                            : doctors.length === 0 && !loadingDoctors
                                              ? 'Brak specjalistów dla tej usługi'
                                              : 'Wybierz specjalistę'
                                    "
                                />
                            </SelectTrigger>
                            <SelectContent class="dark:border-gray-700 dark:bg-gray-800">
                                <SelectGroup>
                                    <SelectLabel v-if="doctors.length > 0" class="dark:text-gray-400">Dostępni specjaliści </SelectLabel>
                                    <SelectItem
                                        v-for="doc in doctors"
                                        :key="doc.id"
                                        :value="doc.id.toString()"
                                        class="dark:text-gray-200 hover:dark:bg-gray-700"
                                    >
                                        Dr {{ doc.first_name }} {{ doc.last_name }}
                                        <span class="ml-2 text-sm text-gray-500 dark:text-gray-400">({{ doc.specialization }})</span>
                                    </SelectItem>
                                </SelectGroup>
                            </SelectContent>
                        </Select>
                        <div v-if="formErrors.doctor_id" class="mt-1 text-sm text-red-500">{{ formErrors.doctor_id[0] }}</div>
                    </div>

                    <div class="space-y-3 rounded-lg p-1" v-if="selectedDoctorId">
                        <div class="mb-1 flex items-center gap-3">
                            <div
                                class="bg-nova-primary mb-2 flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-lg font-bold text-white"
                            >
                                3
                            </div>
                            <Label class="mb-2 text-xl font-semibold text-gray-700 dark:text-gray-200">Wybierz Termin Wizyty</Label>
                        </div>

                        <DoctorAvailabilityCalendar
                            ref="availabilityCalendar"
                            :doctor-id="selectedDoctorId ? Number(selectedDoctorId) : null"
                            :procedure-id="selectedProcedureId ? Number(selectedProcedureId) : null"
                            @dateSelected="handleDateTimeSelected"
                            class="rounded-md border bg-white p-4 dark:border-gray-700 dark:bg-gray-700/30"
                        />
                        <div v-if="checkingAvailability" class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                            Sprawdzanie dostępności terminu...
                        </div>
                        <div
                            v-if="!checkingAvailability && availabilityStatus.message"
                            :class="availabilityStatus.available ? 'text-green-600 dark:text-green-400' : 'text-red-500 dark:text-red-400'"
                            class="mt-2 rounded-md p-2 text-sm"
                            :style="{ backgroundColor: availabilityStatus.available ? 'rgba(239, 246, 239, 0.7)' : 'rgba(254, 242, 242, 0.7)' }"
                        >
                            {{ availabilityStatus.message }}
                        </div>

                        <div
                            v-if="appointmentDate && appointmentTime"
                            class="mt-4 rounded-md border border-green-200 bg-green-50 p-3 dark:border-green-700 dark:bg-green-900/20"
                        >
                            <p class="text-sm font-medium text-green-700 dark:text-green-300">
                                <Icon name="check-circle" class="mr-2 inline-block h-5 w-5" />
                                Wybrany termin: {{ appointmentDate }}, godz. {{ appointmentTime }}
                            </p>
                        </div>
                    </div>

                    <div class="space-y-3 rounded-lg p-1" v-if="selectedDoctorId && appointmentDate && appointmentTime">
                        <div class="mb-1 flex items-center gap-3">
                            <div
                                class="bg-nova-primary mb-2 flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-lg font-bold text-white"
                            >
                                4
                            </div>
                            <Label for="patient-notes" class="mb-2 text-xl font-semibold text-gray-700 dark:text-gray-200"
                                >Dodatkowe Informacje <span class="text-sm font-normal text-gray-500 dark:text-gray-400">(opcjonalnie)</span></Label
                            >
                        </div>
                        <Textarea
                            id="patient-notes"
                            v-model="patientNotes"
                            placeholder="Jeśli masz jakieś pytania lub dodatkowe informacje dla lekarza, wpisz je tutaj..."
                            class="min-h-[120px] dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200"
                            rows="4"
                        />
                        <div v-if="formErrors.patient_notes" class="mt-1 text-sm text-red-500">
                            {{ formErrors.patient_notes[0] }}
                        </div>
                    </div>
                </CardContent>
                <Separator class="dark:bg-gray-700" v-if="selectedProcedureId" />
                <CardFooter class="pt-6" v-if="selectedProcedureId">
                    <Button
                        @click="submitAppointment"
                        :disabled="!canSubmit"
                        class="bg-nova-primary hover:bg-nova-accent dark:bg-nova-accent dark:hover:bg-nova-primary h-12 w-full py-3 text-lg text-white dark:text-gray-900"
                    >
                        <Icon v-if="formSubmitting" name="loader-2" class="mr-2 h-5 w-5 animate-spin" />
                        {{ formSubmitting ? 'Rezerwowanie...' : 'Zarezerwuj Wizytę' }}
                    </Button>
                </CardFooter>
            </Card>
        </div>

        <Transition
            enter-active-class="transition-all duration-300 ease-out"
            enter-from-class="opacity-0 scale-95"
            enter-to-class="opacity-100 scale-100"
            leave-active-class="transition-all duration-200 ease-in"
            leave-from-class="opacity-100 scale-100"
            leave-to-class="opacity-0 scale-95"
        >
            <div
                v-if="showConfirmationModal && confirmedAppointment"
                class="bg-opacity-50  fixed inset-0 z-50 flex items-center justify-center p-4 backdrop-blur-sm"
            >
                <div class="border-nova-accent w-full max-w-md transform rounded-lg border-1 bg-white p-6 shadow-xl dark:bg-gray-800">
                    <div class="mb-4 flex items-center justify-between">
                        <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100">Potwierdzenie rezerwacji</h3>
                        <button
                            @click="closeConfirmationAndRedirect"
                            class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
                        >
                            <Icon name="x" class="h-5 w-5" />
                        </button>
                    </div>

                    <div class="mb-6 flex justify-center">
                        <div class="rounded-full bg-green-100 p-3 dark:bg-green-900">
                            <Icon name="check" class="h-10 w-10 text-green-600 dark:text-green-400" />
                        </div>
                    </div>

                    <p class="mb-6 text-center text-gray-700 dark:text-gray-300">Twoja wizyta została pomyślnie zarezerwowana</p>

                    <div class="mb-6 rounded-lg bg-gray-50 p-4 dark:bg-gray-700/30">
                        <div class="space-y-3">
                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-400">Usługa:</span>
                                <span class="font-medium">{{ confirmedAppointment.procedure?.name }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-400">Specjalista:</span>
                                <span class="font-medium">
                                    {{ confirmedAppointment.doctor?.first_name }}
                                    {{ confirmedAppointment.doctor?.last_name }}
                                </span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-400">Data:</span>
                                <span class="font-medium">{{ new Date(confirmedAppointment.appointment_datetime).toLocaleDateString('pl-PL') }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-400">Godzina:</span>
                                <span class="font-medium">
                                    {{ confirmedAppointment.appointment_datetime.substring(11, 16) }}
                                </span>
                            </div>
                            <div class="mt-3 border-t border-gray-200 pt-3 dark:border-gray-600">
                                <div class="flex justify-between">
                                    <span class="font-medium text-gray-700 dark:text-gray-300">Cena:</span>
                                    <span class="font-bold text-emerald-600 dark:text-emerald-400">
                                        {{
                                            confirmedAppointment.procedure?.base_price
                                                ? parseFloat(confirmedAppointment.procedure.base_price).toFixed(2)
                                                : '0.00'
                                        }}
                                        zł
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-center">
                        <Button
                            @click="closeConfirmationAndRedirect"
                            class="bg-nova-primary hover:bg-nova-accent dark:bg-nova-accent dark:hover:bg-nova-primary w-full text-white dark:text-gray-900"
                        >
                            Przejdź do moich wizyt
                        </Button>
                    </div>
                </div>
            </div>
        </Transition>

        <div class="mt-8 mb-2 text-center text-gray-500 dark:text-gray-400">
            <p class="text-sm">
                Masz pytania? Skontaktuj się z nami pod numerem
                <a href="tel:+48123456789" class="text-nova-primary hover:underline">+48 123 456 789</a>.
            </p>
        </div>
        <Toast position="bottom-right" />
    </AppLayout>
</template>
