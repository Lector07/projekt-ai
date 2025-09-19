<script setup lang="ts">
import {ref, onMounted, reactive, computed, watch} from 'vue';
import {useRouter} from 'vue-router';
import axios from 'axios';
import AppLayout from '@/layouts/AppLayout.vue';
import {Button} from '@/components/ui/button';
import {Input} from '@/components/ui/input';
import {Label} from '@/components/ui/label';
import Icon from '@/components/Icon.vue';
import type { User, BreadcrumbItem } from '@/types.d';
import { MoveRight, MoveLeft } from 'lucide-vue-next';
import { PrinterCheck } from 'lucide-vue-next';
import { Table, TableBody, TableCaption, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { Pagination, PaginationEllipsis, PaginationFirst, PaginationPrevious, PaginationLast, PaginationNext } from '@/components/ui/pagination';
import {PaginationList, PaginationListItem} from 'reka-ui';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import Toast from 'primevue/toast';
import {useToast} from 'primevue/usetoast';
import {ScrollArea} from '@/components/ui/scroll-area';
import {Tooltip, TooltipContent, TooltipTrigger} from "@/components/ui/tooltip";
import {Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle} from '@/components/ui/dialog';
import ReportGenerator from '@/components/ReportGenerator.vue';
import { useAuthStore } from '@/stores/auth';
import { type DateValue, getLocalTimeZone, today, parseDate } from '@internationalized/date';
import {
    DatePickerAnchor,
    DatePickerCalendar,
    DatePickerCell,
    DatePickerCellTrigger,
    DatePickerContent,
    DatePickerField,
    DatePickerGrid,
    DatePickerGridBody,
    DatePickerGridHead,
    DatePickerGridRow,
    DatePickerHeadCell,
    DatePickerHeader,
    DatePickerHeading,
    DatePickerInput,
    DatePickerNext,
    DatePickerPrev,
    DatePickerRoot,
    DatePickerTrigger,
} from 'reka-ui';

interface Patient { id: number; name: string; }
interface Doctor { id: number; first_name: string; last_name: string; specialization: string; }
interface Procedure { id: number; name: string; }
interface Appointment { id: number; patient: Patient; doctor: Doctor; procedure: Procedure; appointment_datetime: string; status: string; }
interface AppointmentFilters { doctor_name: string; patient_name: string; status: string; date_from: string; date_to: string; [key: string]: string; }

const toast = useToast();
const router = useRouter();

const appointments = ref<Appointment[]>([]);
const loading = ref(true);
const meta = ref({current_page: 1, from: 1, last_page: 1, per_page: 8, to: 8, total: 0});
const filters = reactive<AppointmentFilters>({ doctor_name: '', patient_name: '', status: '', date_from: '', date_to: '' });

const dateFrom = ref<DateValue | undefined>(undefined);
const dateTo = ref<DateValue | undefined>(undefined);
const showEditAppointmentForm = ref(false);
const selectedAppointment = ref<any>(null);
const appointmentErrors = ref<any>({});
const appointmentFormLoading = ref(false);
const selectedDate = ref<DateValue | undefined>(undefined);

const allPatients = ref<Patient[]>([]);
const allDoctors = ref<Doctor[]>([]);
const procedures = ref<{ id: number, name: string }[]>([]);

const statuses = [ {value: '', label: 'Wszystkie statusy'}, {value: 'scheduled', label: 'Zarezerwowana'}, {value: 'completed', label: 'Zakończona'}, {value: 'confirmed', label: 'Potwierdzona'}, {value: 'cancelled', label: 'Anulowana'}, {value: 'cancelled_by_patient', label: 'Anulowano przez pacjenta'}, {value: 'cancelled_by_clinic', label: 'Anulowano przez klinikę'}, {value: 'no_show', label: 'Nieobecność'} ];
const statusOptions = statuses.filter(s => s.value !== '');
const breadcrumbs: BreadcrumbItem[] = [{title: 'Zarządzanie Wizytami'}];

const isReportGeneratorOpen = ref(false);
const activeFilters = computed(() => {
    const f = {...filters};
    if (f.status === 'all') f.status = '';
    return Object.fromEntries(Object.entries(f).filter(([_, v]) => v !== null && v !== ''));
});
const openReportGenerator = () => { isReportGeneratorOpen.value = true; };

const showSuccessToast = (title: string, content: string) => toast.add({ severity: 'success', summary: title, detail: content, life: 3000 });
const showErrorToast = (title: string, content: string) => toast.add({ severity: 'error', summary: title, detail: content, life: 3000 });

const loadAppointments = async () => {
    loading.value = true;
    try {
        const response = await axios.get('/api/v1/admin/appointments', {params: {page: meta.value.current_page, ...activeFilters.value}});
        appointments.value = response.data.data;
        meta.value = response.data.meta;
    } catch (error) {
        console.error("Błąd ładowania wizyt:", error);
        showErrorToast('Błąd', 'Nie udało się załadować listy wizyt.');
    } finally {
        loading.value = false;
    }
};

const loadAllDependencies = async () => {
    try {
        const [patientsRes, doctorsRes, proceduresRes] = await Promise.all([
            axios.get('/api/v1/admin/users?role=patient&per_page=1000'),
            axios.get('/api/v1/admin/doctors'),
            axios.get('/api/v1/admin/procedures')
        ]);
        allPatients.value = patientsRes.data?.data || [];
        allDoctors.value = doctorsRes.data?.data || [];
        procedures.value = proceduresRes.data?.data || [];
    } catch (error) {
        console.error("Błąd ładowania danych pomocniczych:", error);
        showErrorToast('Błąd krytyczny', 'Nie udało się załadować danych potrzebnych do edycji.');
    }
}

const openEditForm = async (appointment: Appointment) => {
    appointmentErrors.value = {};
    selectedAppointment.value = null;
    showEditAppointmentForm.value = true;
    appointmentFormLoading.value = true;
    try {
        const response = await axios.get(`/api/v1/admin/appointments/${appointment.id}`);
        if (!response.data || !response.data.data) {
            showErrorToast('Błąd', 'Nie udało się pobrać szczegółów wizyty');
            closeEditForm();
            return;
        }
        selectedAppointment.value = response.data.data;
        selectedAppointment.value.procedure_id = selectedAppointment.value.procedure.id;
        selectedAppointment.value.patient_id = selectedAppointment.value.patient.id;
        selectedAppointment.value.doctor_id = selectedAppointment.value.doctor.id;
        if (selectedAppointment.value.appointment_datetime) {
            const dateStr = selectedAppointment.value.appointment_datetime.split('T')[0];
            try {
                selectedDate.value = parseDate(dateStr);
            } catch (e) {
                console.error('Błąd parsowania daty:', e, dateStr);
                selectedDate.value = undefined;
            }
        }
    } catch (error) {
        console.error("Błąd pobierania szczegółów wizyty:", error);
        showErrorToast('Błąd', 'Nie udało się pobrać szczegółów wizyty');
        closeEditForm();
    } finally {
        appointmentFormLoading.value = false;
    }
};

const closeEditForm = () => {
    showEditAppointmentForm.value = false;
    selectedAppointment.value = null;
    appointmentErrors.value = {};
};

const updateAppointment = async () => {
    if (!selectedAppointment.value) return;
    appointmentFormLoading.value = true;
    appointmentErrors.value = {};
    try {
        await axios.put(`/api/v1/admin/appointments/${selectedAppointment.value.id}`, {
            status: selectedAppointment.value.status,
            appointment_datetime: selectedAppointment.value.appointment_datetime,
            patient_notes: selectedAppointment.value.patient_notes,
            admin_notes: selectedAppointment.value.admin_notes,
            procedure_id: selectedAppointment.value.procedure_id,
            patient_id: selectedAppointment.value.patient_id,
            doctor_id: selectedAppointment.value.doctor_id
        });
        closeEditForm();
        loadAppointments();
        showSuccessToast('Sukces', 'Wizyta została zaktualizowana');
    } catch (error: any) {
        if (error.response?.data?.errors) {
            appointmentErrors.value = error.response.data.errors;
        }
        showErrorToast('Błąd', 'Nie udało się zaktualizować wizyty');
    } finally {
        appointmentFormLoading.value = false;
    }
};

const deleteAppointment = async (id: number) => {
    if (!confirm('Czy na pewno chcesz usunąć tę wizytę?')) return;
    try {
        await axios.delete(`/api/v1/admin/appointments/${id}`);
        loadAppointments();
        showSuccessToast('Sukces', 'Wizyta została usunięta.');
    } catch (error) {
        showErrorToast('Błąd', 'Nie udało się usunąć wizyty.');
    }
};

const resetFilters = () => {
    Object.keys(filters).forEach((key) => { (filters as any)[key] = ''; });
    dateFrom.value = undefined;
    dateTo.value = undefined;
    meta.value.current_page = 1;
    loadAppointments();
};

const changePage = (page: number) => {
    meta.value.current_page = page;
    loadAppointments();
};
const formatDateTime = (dateTimeStr: string) => new Date(dateTimeStr).toLocaleString('pl-PL', { year: 'numeric', month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit' });
const getStatusLabel = (status: string) => statuses.find(s => s.value === status)?.label || status;
const getStatusClass = (status: string) => {
    switch (status) {
        case 'scheduled': return 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200';
        case 'completed': return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200';
        case 'cancelled': return 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200';
        case 'confirmed': return 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200';
        case 'cancelled_by_patient': return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200';
        case 'no_show': return 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200';
        case 'cancelled_by_clinic': return 'bg-red-200 text-red-800 dark:bg-red-800 dark:text-red-200';
        default: return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200';
    }
};

const auth = useAuthStore();
const userRole = computed(() => auth.user?.role ?? 'guest');

watch(dateFrom, (newDate) => {
    filters.date_from = newDate ? newDate.toString() : '';
});

watch(dateTo, (newDate) => {
    filters.date_to = newDate ? newDate.toString() : '';
});

watch(selectedDate, (newDate) => {
    if (selectedAppointment.value && newDate) {

        const timePart = selectedAppointment.value.appointment_datetime?.split(' ')[1] || '12:00:00';

        const datePart = newDate.toString();

        selectedAppointment.value.appointment_datetime = `${datePart} ${timePart}`;
    }
});

onMounted(() => {
    loadAppointments();
    loadAllDependencies();
});
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Toast/>
        <div class="container mx-auto px-4 py-6 flex flex-col flex-grow">
            <div class="flex justify-between items-center mb-4">
                <h1 class="text-3xl font-bold dark:text-white">Zarządzanie Wizytami</h1>
            </div>
            <div class="rounded-lg shadow-sm mb-6 p-4 border dark:border-gray-700 bg-white dark:bg-gray-800">
                <h2 class="text-lg font-semibold mb-3 dark:text-white">Filtry</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div>
                        <Label for="doctor-filter" class="mb-1 dark:text-gray-200">Lekarz</Label>
                        <Input id="doctor-filter" v-model="filters.doctor_name" type="text" placeholder="Imię lub nazwisko"/>
                    </div>
                    <div>
                        <Label for="patient-filter" class="mb-1 dark:text-gray-200">Pacjent</Label>
                        <Input id="patient-filter" v-model="filters.patient_name" type="text" placeholder="Imię lub nazwisko"/>
                    </div>
                    <div>
                        <Label for="status-filter" class="mb-1 dark:text-gray-200">Status</Label>
                        <Select v-model="filters.status">
                            <SelectTrigger id="status-filter"><SelectValue placeholder="Wszystkie statusy" /></SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">Wszystkie statusy</SelectItem>
                                <SelectItem v-for="status in statuses.filter(s => s.value !== '')" :key="status.value" :value="status.value">{{ status.label }}</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    <div>
                        <Label for="date-from" class="mb-1 dark:text-gray-200">Data od</Label>
                        <DatePickerRoot v-model="dateFrom" locale="pl-PL" class="relative">
                            <DatePickerField v-slot="{ segments }" class="flex h-10 items-center w-full justify-between text-left font-normal border border-input rounded-md px-1 text-sm ring-offset-background focus-within:ring-2 focus-within:ring-ring focus-within:ring-offset-2 data-[invalid]:border-red-500 data-[disabled]:cursor-not-allowed data-[disabled]:opacity-50" :class="!dateFrom && 'text-muted-foreground'">
                                <div class="flex items-center">
                                    <template v-for="segment in segments" :key="segment.part">
                                        <DatePickerInput
                                            :part="segment.part"
                                            class="rounded p-0.5 focus:outline-none focus:shadow-[0_0_0_2px] focus:shadow-black data-[placeholder]:text-muted-foreground"
                                        >
                                            {{ segment.value }}
                                        </DatePickerInput>
                                    </template>
                                </div>
                                <DatePickerTrigger class="focus:shadow-[0_0_0_2px] rounded p-1 focus:shadow-black">
                                    <Icon name="calendar" class="h-4 w-4"/>
                                </DatePickerTrigger>
                            </DatePickerField>
                            <DatePickerAnchor />
                            <DatePickerContent :side-offset="2" class="bg-white dark:bg-gray-900 border rounded-lg shadow-lg p-1 z-50">
                                <DatePickerCalendar v-slot="{ weekDays, grid }" class="p-2">
                                    <DatePickerHeader class="flex items-center justify-between">
                                        <DatePickerPrev class="inline-flex items-center cursor-pointer justify-center rounded-md text-sm font-medium w-9 h-9 hover:bg-accent active:scale-90">
                                            <MoveLeft class="w-4 h-4" />
                                        </DatePickerPrev>
                                        <DatePickerHeading class="text-sm font-medium" />
                                        <DatePickerNext class="inline-flex items-center cursor-pointer justify-center rounded-md text-sm font-medium w-9 h-9 hover:bg-accent active:scale-90">
                                            <MoveRight class="w-4 h-4" />
                                        </DatePickerNext>
                                    </DatePickerHeader>
                                    <div class="flex flex-col space-y-4 pt-3">
                                        <DatePickerGrid v-for="month in grid" :key="month.value.toString()" class="w-full border-collapse select-none space-y-1">
                                            <DatePickerGridHead>
                                                <DatePickerGridRow class=" flex w-full justify-between">
                                                    <DatePickerHeadCell v-for="day in weekDays" :key="day" class="w-11 text-center rounded-md text-xs">
                                                        {{ day }}
                                                    </DatePickerHeadCell>
                                                </DatePickerGridRow>
                                            </DatePickerGridHead>
                                            <DatePickerGridBody>
                                                <DatePickerGridRow v-for="(weekDates, index) in month.rows" :key="`weekDate-${index}`" class="flex w-full">
                                                    <DatePickerCell v-for="weekDate in weekDates" :key="weekDate.toString()" :date="weekDate">
                                                        <DatePickerCellTrigger
                                                            :day="weekDate"
                                                            :month="month.value"
                                                            class="relative flex items-center justify-center whitespace-nowrap rounded-md text-sm w-6 h-6 outline-none hover:bg-accent data-[selected]:bg-nova-primary data-[selected]:font-semibold data-[outside-view]:text-muted-foreground data-[selected]:text-primary-foreground data-[unavailable]:text-muted-foreground data-[unavailable]:opacity-50"
                                                        />
                                                    </DatePickerCell>
                                                </DatePickerGridRow>
                                            </DatePickerGridBody>
                                        </DatePickerGrid>
                                    </div>
                                </DatePickerCalendar>
                            </DatePickerContent>
                        </DatePickerRoot>
                    </div>
                    <div>
                        <Label for="date-to" class="mb-1 dark:text-gray-200">Data do</Label>
                        <DatePickerRoot v-model="dateTo" locale="pl-PL" class="relative">
                            <DatePickerField v-slot="{ segments }" class="flex h-10 items-center w-full justify-between text-left font-normal border border-input rounded-md px-1 text-sm ring-offset-background focus-within:ring-2 focus-within:ring-ring focus-within:ring-offset-2 data-[invalid]:border-red-500 data-[disabled]:cursor-not-allowed data-[disabled]:opacity-50" :class="!dateFrom && 'text-muted-foreground'">
                                <div class="flex items-center">
                                    <template v-for="segment in segments" :key="segment.part">
                                        <DatePickerInput
                                            :part="segment.part"
                                            class="rounded p-0.5 focus:outline-none focus:shadow-[0_0_0_2px] focus:shadow-black data-[placeholder]:text-muted-foreground"
                                        >
                                            {{ segment.value }}
                                        </DatePickerInput>
                                    </template>
                                </div>
                                <DatePickerTrigger class="focus:shadow-[0_0_0_2px] rounded p-1 focus:shadow-black">
                                    <Icon name="calendar" class="h-4 w-4"/>
                                </DatePickerTrigger>
                            </DatePickerField>
                            <DatePickerAnchor />
                            <DatePickerContent :side-offset="2" class="bg-white dark:bg-gray-900 border rounded-lg shadow-lg p-1 z-50">
                                <DatePickerCalendar v-slot="{ weekDays, grid }" class="p-2">
                                    <DatePickerHeader class="flex items-center justify-between">
                                        <DatePickerPrev class="inline-flex items-center cursor-pointer justify-center rounded-md text-sm font-medium w-9 h-9 hover:bg-accent active:scale-90">
                                            <MoveLeft class="w-4 h-4" />
                                        </DatePickerPrev>
                                        <DatePickerHeading class="text-sm font-medium" />
                                        <DatePickerNext class="inline-flex items-center cursor-pointer justify-center rounded-md text-sm font-medium w-9 h-9 hover:bg-accent active:scale-90">
                                            <MoveRight class="w-4 h-4" />
                                        </DatePickerNext>
                                    </DatePickerHeader>
                                    <div class="flex flex-col space-y-4 pt-3">
                                        <DatePickerGrid v-for="month in grid" :key="month.value.toString()" class="w-full border-collapse select-none space-y-1">
                                            <DatePickerGridHead>
                                                <DatePickerGridRow class=" flex w-full justify-between">
                                                    <DatePickerHeadCell v-for="day in weekDays" :key="day" class="w-11 text-center rounded-md text-xs">
                                                        {{ day }}
                                                    </DatePickerHeadCell>
                                                </DatePickerGridRow>
                                            </DatePickerGridHead>
                                            <DatePickerGridBody>
                                                <DatePickerGridRow v-for="(weekDates, index) in month.rows" :key="`weekDate-${index}`" class="flex w-full">
                                                    <DatePickerCell v-for="weekDate in weekDates" :key="weekDate.toString()" :date="weekDate">
                                                        <DatePickerCellTrigger
                                                            :day="weekDate"
                                                            :month="month.value"
                                                            class="relative flex items-center justify-center whitespace-nowrap rounded-md text-sm w-6 h-6 outline-none hover:bg-accent data-[selected]:bg-nova-primary data-[selected]:font-semibold data-[outside-view]:text-muted-foreground data-[selected]:text-primary-foreground data-[unavailable]:text-muted-foreground data-[unavailable]:opacity-50"
                                                        />
                                                    </DatePickerCell>
                                                </DatePickerGridRow>
                                            </DatePickerGridBody>
                                        </DatePickerGrid>
                                    </div>
                                </DatePickerCalendar>
                            </DatePickerContent>
                        </DatePickerRoot>
                    </div>
                    <div class="flex items-end space-x-2">
                        <Button @click="loadAppointments" class="bg-nova-primary hover:bg-nova-accent"><Icon name="search" class="mr-2 h-4 w-4"/>Filtruj</Button>
                        <Button variant="outline" @click="resetFilters"><Icon name="x" class="mr-2 h-4 w-4"/>Wyczyść</Button>
                        <Tooltip>
                            <TooltipTrigger as-child><Button @click="openReportGenerator" class="bg-nova-primary hover:bg-nova-accent" aria-label="Generator raportów"><PrinterCheck /></Button></TooltipTrigger>
                            <TooltipContent align="center" side="bottom">Generator raportów</TooltipContent>
                        </Tooltip>
                    </div>
                </div>
            </div>

            <div class="rounded-lg dark:bg-gray-800 shadow-sm border  dark:border-gray-700 flex flex-col flex-grow overflow-hidden">
                <ScrollArea class="w-full h-[auto]">
                    <Table class="table-auto ">
                        <TableCaption v-if="!loading && appointments.length === 0">Brak wizyt spełniających kryteria</TableCaption>
                        <TableHeader><TableRow><TableHead>ID</TableHead><TableHead>Pacjent</TableHead><TableHead>Lekarz</TableHead><TableHead>Procedura</TableHead><TableHead>Data i godzina</TableHead><TableHead>Status</TableHead><TableHead class="text-center">Akcje</TableHead><TableHead class="text-center">Szczegóły</TableHead></TableRow></TableHeader>
                        <TableBody>
                            <TableRow v-if="loading"><TableCell colspan="7" class="text-center py-8"><Icon name="loader-2" class="animate-spin h-8 w-8 text-primary mx-auto"/></TableCell></TableRow>
                            <TableRow v-else v-for="appointment in appointments" :key="appointment.id">
                                <TableCell>{{ appointment.id }}</TableCell>
                                <TableCell class="font-medium">{{ appointment.patient.name }}</TableCell>
                                <TableCell>{{ appointment.doctor.first_name }} {{ appointment.doctor.last_name }}</TableCell>
                                <TableCell>{{ appointment.procedure.name }}</TableCell>
                                <TableCell>{{ formatDateTime(appointment.appointment_datetime) }}</TableCell>
                                <TableCell><span class="px-2 py-1 text-xs font-medium rounded-full" :class="getStatusClass(appointment.status)">{{ getStatusLabel(appointment.status) }}</span></TableCell>
                                <TableCell class="text-center"><div class="flex space-x-1 justify-center"><Tooltip><TooltipTrigger as-child><Button variant="ghost" size="icon" @click="openEditForm(appointment)"><Icon name="pencil" size="16"/></Button></TooltipTrigger><TooltipContent side="bottom">Edytuj</TooltipContent></Tooltip></div></TableCell>
                                <TableCell class="text-center"><Tooltip><TooltipTrigger as-child><Button variant="ghost" size="icon" @click="router.push({ name: 'admin-appointment-details', params: { id: appointment.id } })"><Icon name="info" size="16"/></Button></TooltipTrigger><TooltipContent side="bottom">Szczegóły</TooltipContent></Tooltip></TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </ScrollArea>
                <div class="flex justify-center items-center p-2 border-t dark:border-gray-700">
                    <Pagination v-if="meta.last_page > 1" :total="meta.total" :items-per-page="meta.per_page" :page="meta.current_page" @update:page="changePage" show-edges>
                        <PaginationList v-slot="{ items }" class="flex items-center gap-1">
                            <PaginationFirst/><PaginationPrevious/>
                            <template v-for="(item, index) in items">
                                <PaginationListItem v-if="item.type === 'page'" :key="index" :value="item.value" as-child><Button class="w-9 h-9 p-0" :variant="item.value === meta.current_page ? 'default' : 'outline'" :class="meta.current_page === item.value ? 'bg-nova-primary hover:bg-nova-accent text-white' : 'dark:border-gray-600 dark:bg-gray-700 dark:text-white'">{{ item.value }}</Button></PaginationListItem>
                                <PaginationEllipsis v-else :key="item.type" :index="index"/>
                            </template>
                            <PaginationNext/><PaginationLast/>
                        </PaginationList>
                    </Pagination>
                </div>
            </div>
        </div>

        <ReportGenerator v-model="isReportGeneratorOpen" :active-filters="activeFilters" :user-role="userRole" report-type="appointments" />

        <Dialog :open="showEditAppointmentForm" @update:open="showEditAppointmentForm = $event">
            <DialogContent class="max-w-md">
                <DialogHeader><DialogTitle>Edycja Wizyty #{{ selectedAppointment?.id }}</DialogTitle></DialogHeader>
                <div v-if="appointmentFormLoading || !selectedAppointment" class="flex justify-center items-center py-10"><Icon name="loader-2" class="animate-spin h-8 w-8 text-primary"/></div>
                <div v-else class="space-y-4 py-4">
                    <div class="space-y-1">
                        <Label for="edit-patient">Pacjent</Label>
                        <Select v-model="selectedAppointment.patient_id" :class="{'border-red-500': appointmentErrors.patient_id}"><SelectTrigger id="edit-patient"><SelectValue placeholder="Wybierz pacjenta" /></SelectTrigger><SelectContent><SelectItem v-for="patient in allPatients" :key="patient.id" :value="patient.id">{{ patient.name }}</SelectItem></SelectContent></Select>
                        <div v-if="appointmentErrors.patient_id" class="text-xs text-red-500">{{ appointmentErrors.patient_id[0] }}</div>
                    </div>
                    <div class="space-y-1">
                        <Label for="edit-doctor">Lekarz</Label>
                        <Select v-model="selectedAppointment.doctor_id" :class="{'border-red-500': appointmentErrors.doctor_id}"><SelectTrigger id="edit-doctor"><SelectValue placeholder="Wybierz lekarza" /></SelectTrigger><SelectContent><SelectItem v-for="doctor in allDoctors" :key="doctor.id" :value="doctor.id">{{ doctor.first_name }} {{ doctor.last_name }}</SelectItem></SelectContent></Select>
                        <div v-if="appointmentErrors.doctor_id" class="text-xs text-red-500">{{ appointmentErrors.doctor_id[0] }}</div>
                    </div>
                    <div class="space-y-1">
                        <Label for="edit-procedure">Procedura</Label>
                        <Select v-model="selectedAppointment.procedure_id" :class="{'border-red-500': appointmentErrors.procedure_id}"><SelectTrigger id="edit-procedure"><SelectValue placeholder="Wybierz procedurę" /></SelectTrigger><SelectContent><SelectItem v-for="procedure in procedures" :key="procedure.id" :value="procedure.id">{{ procedure.name }}</SelectItem></SelectContent></Select>
                        <div v-if="appointmentErrors.procedure_id" class="text-xs text-red-500">{{ appointmentErrors.procedure_id[0] }}</div>
                    </div>
                    <div>
                        <Label for="date-to" class="mb-1 dark:text-gray-200">Data wizyty</Label>
                        <DatePickerRoot v-model="selectedDate" locale="pl-PL" class="relative">
                            <DatePickerField v-slot="{ segments }" class="flex h-10 items-center w-full justify-between text-left font-normal border border-input rounded-md px-1 text-sm ring-offset-background focus-within:ring-2 focus-within:ring-ring focus-within:ring-offset-2 data-[invalid]:border-red-500 data-[disabled]:cursor-not-allowed data-[disabled]:opacity-50" :class="!dateFrom && 'text-muted-foreground'">
                                <div class="flex items-center">
                                    <template v-for="segment in segments" :key="segment.part">
                                        <DatePickerInput
                                            :part="segment.part"
                                            class="rounded p-0.5 focus:outline-none focus:shadow-[0_0_0_2px] focus:shadow-black data-[placeholder]:text-muted-foreground"
                                        >
                                            {{ segment.value }}
                                        </DatePickerInput>
                                    </template>
                                </div>
                                <DatePickerTrigger class="focus:shadow-[0_0_0_2px] rounded p-1 focus:shadow-black">
                                    <Icon name="calendar" class="h-4 w-4"/>
                                </DatePickerTrigger>
                            </DatePickerField>
                            <DatePickerAnchor />
                            <DatePickerContent :side-offset="2" class="bg-white dark:bg-gray-900 border rounded-lg shadow-lg p-1 z-50">
                                <DatePickerCalendar v-slot="{ weekDays, grid }" class="p-2">
                                    <DatePickerHeader class="flex items-center justify-between">
                                        <DatePickerPrev class="inline-flex items-center cursor-pointer justify-center rounded-md text-sm font-medium w-9 h-9 hover:bg-accent active:scale-90">
                                            <MoveLeft class="w-4 h-4" />
                                        </DatePickerPrev>
                                        <DatePickerHeading class="text-sm font-medium" />
                                        <DatePickerNext class="inline-flex items-center cursor-pointer justify-center rounded-md text-sm font-medium w-9 h-9 hover:bg-accent active:scale-90">
                                            <MoveRight class="w-4 h-4" />
                                        </DatePickerNext>
                                    </DatePickerHeader>
                                    <div class="flex flex-col space-y-4 pt-3">
                                        <DatePickerGrid v-for="month in grid" :key="month.value.toString()" class="w-full border-collapse select-none space-y-1">
                                            <DatePickerGridHead>
                                                <DatePickerGridRow class=" flex w-full justify-between">
                                                    <DatePickerHeadCell v-for="day in weekDays" :key="day" class="w-11 text-center rounded-md text-xs">
                                                        {{ day }}
                                                    </DatePickerHeadCell>
                                                </DatePickerGridRow>
                                            </DatePickerGridHead>
                                            <DatePickerGridBody>
                                                <DatePickerGridRow v-for="(weekDates, index) in month.rows" :key="`weekDate-${index}`" class="flex w-full">
                                                    <DatePickerCell v-for="weekDate in weekDates" :key="weekDate.toString()" :date="weekDate">
                                                        <DatePickerCellTrigger
                                                            :day="weekDate"
                                                            :month="month.value"
                                                            class="relative flex items-center justify-center whitespace-nowrap rounded-md text-sm w-6 h-6 outline-none hover:bg-accent data-[selected]:bg-nova-primary data-[selected]:font-semibold data-[outside-view]:text-muted-foreground data-[selected]:text-primary-foreground data-[unavailable]:text-muted-foreground data-[unavailable]:opacity-50"
                                                        />
                                                    </DatePickerCell>
                                                </DatePickerGridRow>
                                            </DatePickerGridBody>
                                        </DatePickerGrid>
                                    </div>
                                </DatePickerCalendar>
                            </DatePickerContent>
                        </DatePickerRoot>
                    </div>
                    <div class="space-y-1">
                        <Label for="edit-status">Status wizyty</Label>
                        <Select v-model="selectedAppointment.status" :class="{'border-red-500': appointmentErrors.status}"><SelectTrigger id="edit-status"><SelectValue placeholder="Wybierz status" /></SelectTrigger><SelectContent><SelectItem v-for="status in statusOptions" :key="status.value" :value="status.value">{{ status.label }}</SelectItem></SelectContent></Select>
                        <div v-if="appointmentErrors.status" class="text-xs text-red-500">{{ appointmentErrors.status[0] }}</div>
                    </div>
                </div>
                <DialogFooter>
                    <Button variant="outline" @click="closeEditForm">Anuluj</Button>
                    <Button @click="updateAppointment" :disabled="appointmentFormLoading"><Icon v-if="appointmentFormLoading" name="loader-2" class="animate-spin mr-2"/><span>Zapisz zmiany</span></Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
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

.dark :deep(thead th) {
    background-color: #1f2937;
    color: #d1d5db;
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
</style>
