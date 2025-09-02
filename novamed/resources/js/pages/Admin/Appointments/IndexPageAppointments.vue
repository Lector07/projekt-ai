<script setup lang="ts">
import {ref, onMounted, reactive} from 'vue';
import {useRouter} from 'vue-router';
import axios from 'axios';
import AppLayout from '@/layouts/AppLayout.vue';
import {Button} from '@/components/ui/button';
import {Input} from '@/components/ui/input';
import {Label} from '@/components/ui/label';
import {parseDate} from '@internationalized/date';
import type {DateValue} from '@internationalized/date';
import Icon from '@/components/Icon.vue';
import type {BreadcrumbItem} from '@/types';
import { ClipboardMinus } from 'lucide-vue-next';
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
import Toast from 'primevue/toast';
import {useToast} from 'primevue/usetoast';
import {
    ScrollArea,
} from "@/components/ui/scroll-area";
import {Calendar} from '@/components/ui/calendar';
import {format} from 'date-fns';
import {pl} from 'date-fns/locale';
import {TooltipContent} from "@/components/ui/tooltip";
import {Tooltip, TooltipTrigger} from "@/components/ui/tooltip";
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Checkbox } from '@/components/ui/checkbox';

interface Patient {
    id: number;
    name: string;
}

interface Doctor {
    id: number;
    first_name: string;
    last_name: string;
    specialization: string;
}

interface Procedure {
    id: number;
    name: string;
}

interface Appointment {
    id: number;
    patient: Patient;
    doctor: Doctor;
    procedure: Procedure;
    appointment_datetime: string;
    status: string;
}

interface AppointmentFilters {
    doctor_name: string;
    patient_name: string;
    status: string;
    date_from: string;
    date_to: string;

    [key: string]: string;
}

const toast = useToast();

const appointments = ref<Appointment[]>([]);
const loading = ref(true);
const meta = ref({
    current_page: 1,
    from: 1,
    last_page: 1,
    per_page: 8,
    to: 8,
    total: 0,
});

const filters = reactive<AppointmentFilters>({
    doctor_name: '',
    patient_name: '',
    status: '',
    date_from: '',
    date_to: '',
});

const statuses = [
    {value: '', label: 'Wszystkie statusy'},
    {value: 'scheduled', label: 'Zarezerwowana'},
    {value: 'completed', label: 'Zakończona'},
    {value: 'confirmed', label: 'Potwierdzona'},
    {value: 'cancelled', label: 'Anulowana'},
    {value: 'cancelled_by_patient', label: 'Anulowano przez pacjenta'},
    {value: 'cancelled_by_clinic', label: 'Anulowano przez klinikę'},
    {value: 'no_show', label: 'Nieobecność'},
];

const allPatients = ref<Patient[]>([]);
const allDoctors = ref<Doctor[]>([]);
const patientsLoading = ref(false);
const doctorsLoading = ref(false);
const showEditAppointmentForm = ref(false);
const selectedAppointment = ref<any>(null);
const appointmentErrors = ref<any>({});
const appointmentFormLoading = ref(false);
const procedures = ref<{ id: number, name: string }[]>([]);
const proceduresLoading = ref(false);
const dateFrom = ref<DateValue | undefined>(undefined);
const dateTo = ref<DateValue | undefined>(undefined);
const selectedDate = ref<DateValue | undefined>(undefined);

// === NOWA SEKCJA DLA KONFIGURACJI RAPORTU ===
const reportLoading = ref(false);
const isReportConfigOpen = ref(false);

const availableFields = [
    { field: 'id', header: 'ID Wizyty', type: 'numeric' },
    { field: 'appointment_datetime', header: 'Data wizyty', type: 'date' },
    { field: 'status_translated', header: 'Status', type: 'text' },
    { field: 'patient_name', header: 'Pacjent', type: 'text' },
    { field: 'doctor_full_name', header: 'Lekarz', type: 'text' },
    { field: 'procedure_name', header: 'Zabieg', type: 'text' },
    { field: 'procedure_base_price', header: 'Cena (PLN)', type: 'numeric' },
    { field: 'patient_notes', header: 'Notatki pacjenta', type: 'text' },
];

const reportConfig = reactive({
    title: 'Zestawienie Wizyt',
    companyInfo: {
        name: 'Klinika Chirurgii Plastycznej "Projekt AI"',
        address: 'ul. Medyczna 1',
        postalCode: '00-001',
        city: 'Warszawa',
        taxId: '123-456-78-90'
    },
    columns: availableFields.map(f => ({
        field: f.field,
        header: f.header,
        visible: ['id', 'appointment_datetime', 'status_translated', 'doctor_full_name', 'procedure_name', 'procedure_base_price'].includes(f.field),
        width: -1, // -1 oznacza auto
        format: f.type === 'numeric' ? '#,##0.00' : (f.type === 'date' ? 'yyyy-MM-dd HH:mm' : null),
        groupCalculation: f.type === 'numeric' ? 'SUM' : 'NONE',
        reportCalculation: f.type === 'numeric' ? 'SUM' : 'NONE',
    })),
    groups: [
        { field: 'doctor_full_name', label: '"Lekarz: " + $F{doctor_full_name}', showFooter: true },
    ],
    useSubreportBorders: false,
    pageFooterEnabled: true,
});

const addGroup = () => {
    reportConfig.groups.push({ field: '', label: '', showFooter: false });
};

const removeGroup = (index: number) => {
    reportConfig.groups.splice(index, 1);
};

const openReportConfig = () => {
    isReportConfigOpen.value = true;
};

const generateReport = async () => {
    reportLoading.value = true;
    isReportConfigOpen.value = false;

    // Przygotuj konfigurację do wysłania - odfiltruj puste grupy
    const finalConfig = JSON.parse(JSON.stringify(reportConfig)); // Głęboka kopia
    finalConfig.groups = finalConfig.groups.filter(g => g.field);
    finalConfig.columns.forEach(c => {
        if (c.width === null || c.width === '') c.width = -1;
    });

    try {
        const activeFilters = { ...Object.fromEntries(Object.entries(filters).filter(([_, v]) => v !== '')) };

        const response = await axios.post('/api/v1/admin/appointments/report', {
            config: finalConfig,
            filters: activeFilters
        }, {
            responseType: 'blob',
        });

        const blob = new Blob([response.data], { type: 'application/pdf' });
        const link = document.createElement('a');
        link.href = window.URL.createObjectURL(blob);
        link.setAttribute('download', `${finalConfig.title.replace(/\s/g, '_')}.pdf`);
        document.body.appendChild(link);
        link.click();
        link.remove();
        window.URL.revokeObjectURL(link.href);
        showSuccessToast('Sukces', 'Raport został pomyślnie wygenerowany.');

    } catch (error) {
        console.error('Błąd podczas generowania raportu:', error);
        showErrorToast('Błąd', 'Nie udało się wygenerować raportu.');
    } finally {
        reportLoading.value = false;
    }
};
// === KONIEC SEKCJI RAPORTU ===

const handleApiResponse = (response: any, entityName: string) => {
    if (response?.data?.data) {
        return response.data.data;
    }
    console.error(`Nieprawidłowa odpowiedź API ${entityName}:`, response);
    return [];
};

const loadPatients = async () => {
    patientsLoading.value = true;
    try {
        const response = await axios.get('/api/v1/admin/users?role=patient&per_page=1000');
        allPatients.value = response.data?.data || [];
    } catch (error) {
        console.error('Błąd podczas pobierania pacjentów:', error);
        showErrorToast('Błąd', 'Nie udało się załadować listy pacjentów');
    } finally {
        patientsLoading.value = false;
    }
};

const loadDoctors = async () => {
    doctorsLoading.value = true;
    try {
        const response = await axios.get('/api/v1/admin/doctors');
        allDoctors.value = handleApiResponse(response, 'lekarzy');
    } catch (error) {
        console.error('Błąd podczas pobierania lekarzy:', error);
        showErrorToast('Błąd', 'Nie udało się załadować listy lekarzy');
    } finally {
        doctorsLoading.value = false;
    }
};

const loadProcedures = async () => {
    proceduresLoading.value = true;
    try {
        const response = await axios.get('/api/v1/admin/procedures');
        procedures.value = response.data.data;
    } catch (error) {
        console.error('Błąd podczas pobierania procedur:', error);
        showErrorToast('Błąd', 'Nie udało się załadować listy procedur');
    } finally {
        proceduresLoading.value = false;
    }
};

const changePage = (page: number) => {
    meta.value.current_page = page;
    loadAppointments();
};

const onDateFromChange = (date: DateValue | undefined) => {
    dateFrom.value = date;
    filters.date_from = date ? date.toString() : '';
};

const onDateToChange = (date: DateValue | undefined) => {
    dateTo.value = date;
    filters.date_to = date ? date.toString() : '';
};

const formatDisplayDate = (dateString: string) => {
    if (!dateString) return '';
    return format(new Date(dateString), 'd MMMM yyyy', { locale: pl });
};

const loadAppointments = async () => {
    loading.value = true;
    try {
        const params = {
            page: meta.value.current_page,
            ...Object.fromEntries(
                Object.entries(filters).filter(([_, value]) => value !== '')
            ),
        };
        const response = await axios.get('/api/v1/admin/appointments', { params });
        appointments.value = response.data.data;
        meta.value = response.data.meta;
    } catch (error) {
        console.error('Błąd podczas pobierania wizyt:', error);
        showErrorToast('Błąd', 'Nie udało się załadować listy wizyt.');
    } finally {
        loading.value = false;
    }
};

const resetFilters = () => {
    Object.keys(filters).forEach((key) => { filters[key] = ''; });
    dateFrom.value = undefined;
    dateTo.value = undefined;
    meta.value.current_page = 1;
    loadAppointments();
};

const deleteAppointment = async (id: number) => {
    if (!confirm('Czy na pewno chcesz usunąć tę wizytę?')) return;
    try {
        await axios.delete(`/api/v1/admin/appointments/${id}`);
        loadAppointments();
        showSuccessToast('Sukces', 'Wizyta została usunięta.');
    } catch (error) {
        console.error('Błąd podczas usuwania wizyty:', error);
        showErrorToast('Błąd', 'Nie udało się usunąć wizyty.');
    }
};

const openEditForm = async (appointment: Appointment) => {
    appointmentErrors.value = {};
    appointmentFormLoading.value = true;
    try {
        const response = await axios.get(`/api/v1/admin/appointments/${appointment.id}`);
        selectedAppointment.value = response.data.data;
        await Promise.all([loadProcedures(), loadPatients(), loadDoctors()]);
        selectedAppointment.value.procedure_id = selectedAppointment.value.procedure.id;
        selectedAppointment.value.patient_id = selectedAppointment.value.patient.id;
        selectedAppointment.value.doctor_id = selectedAppointment.value.doctor.id;
        if (selectedAppointment.value.appointment_datetime) {
            const dateStr = selectedAppointment.value.appointment_datetime.split('T')[0];
            selectedDate.value = parseDate(dateStr);
        }
        showEditAppointmentForm.value = true;
    } catch (error) {
        console.error('Błąd podczas pobierania danych wizyty:', error);
        showErrorToast('Błąd', 'Nie udało się pobrać szczegółów wizyty');
    } finally {
        appointmentFormLoading.value = false;
    }
};

const closeEditForm = () => {
    showEditAppointmentForm.value = false;
    selectedAppointment.value = null;
    appointmentErrors.value = {};
};

const onAppointmentDateChange = (date: DateValue | undefined) => {
    selectedDate.value = date;
    if (date && selectedAppointment.value) {
        selectedAppointment.value.appointment_datetime = date.toString();
    }
};

const updateAppointment = async () => {
    appointmentFormLoading.value = true;
    appointmentErrors.value = {};
    try {
        await axios.put(`/api/v1/admin/appointments/${selectedAppointment.value.id}`, {
            status: selectedAppointment.value.status,
            appointment_datetime: selectedAppointment.value.appointment_datetime,
            patient_notes: selectedAppointment.value.patient_notes,
            doctor_notes: selectedAppointment.value.doctor_notes,
            procedure_id: selectedAppointment.value.procedure_id,
            patient_id: selectedAppointment.value.patient_id,
            doctor_id: selectedAppointment.value.doctor_id
        });
        closeEditForm();
        loadAppointments();
        showSuccessToast('Sukces', 'Wizyta została zaktualizowana');
    } catch (error: any) {
        console.error('Błąd podczas aktualizacji wizyty:', error);
        if (error.response?.data?.errors) {
            appointmentErrors.value = error.response.data.errors;
        }
        showErrorToast('Błąd', 'Nie udało się zaktualizować wizyty');
    } finally {
        appointmentFormLoading.value = false;
    }
};

const showSuccessToast = (title: string, content: string) => {
    toast.add({ severity: 'success', summary: title, detail: content, life: 3000 });
};

const showErrorToast = (title: string, content: string) => {
    toast.add({ severity: 'error', summary: title, detail: content, life: 3000 });
};

const formatDateTime = (dateTimeStr: string) => {
    return new Date(dateTimeStr).toLocaleString('pl-PL', { year: 'numeric', month: 'numeric', day: 'numeric', hour: '2-digit', minute: '2-digit' });
};

const getStatusLabel = (status: string) => {
    return statuses.find(s => s.value === status)?.label || status;
};

const getStatusClass = (status: string) => {
    switch (status) {
        case 'scheduled': return 'bg-blue-100 text-blue-800';
        case 'completed': return 'bg-green-100 text-green-800';
        case 'cancelled': return 'bg-red-100 text-red-800';
        case 'confirmed': return 'bg-purple-100 text-purple-800';
        case 'cancelled_by_patient': return 'bg-yellow-100 text-yellow-800';
        case 'no_show': return 'bg-orange-100 text-orange-800';
        case 'cancelled_by_clinic': return 'bg-red-200 text-red-800';
        default: return 'bg-gray-100 text-gray-800';
    }
};

onMounted(() => {
    loadAppointments();
});

const router = useRouter();
const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Zarządzanie Wizytami' }
];
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Toast/>

        <div class="container mx-auto px-2 py-4">
            <h1 class="text-2xl font-bold mb-1 ml-3 dark:text-white">Zarządzanie Wizytami</h1>

            <Button @click="openReportConfig"
                    class="bg-nova-primary hover:bg-nova-accent mt-2 ml-3 mb-2 dark:bg-green-700 dark:hover:bg-green-800 text-white">
                <ClipboardMinus />
                Generuj Raport
            </Button>

            <div class="rounded-lg shadow-sm mb-4 p-4 border dark:border-gray-700">
                <h2 class="text-lg font-semibold mb-2 dark:text-white">Filtry</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <Label for="doctor-filter" class="mb-1 dark:text-gray-200">Lekarz</Label>
                        <Input
                            id="doctor-filter"
                            v-model="filters.doctor_name"
                            type="text"
                            placeholder="Imię lub nazwisko lekarza"
                            class="w-full"
                        />
                    </div>
                    <div>
                        <Label for="patient-filter" class="mb-1 dark:text-gray-200">Pacjent</Label>
                        <Input
                            id="patient-filter"
                            v-model="filters.patient_name"
                            type="text"
                            placeholder="Imię lub nazwisko pacjenta"
                            class="w-full"
                        />
                    </div>
                    <div>
                        <Label for="status-filter" class="mb-1 dark:text-gray-200">Status</Label>
                        <select
                            id="status-filter"
                            v-model="filters.status"
                            class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm "
                        >
                            <option v-for="status in statuses" :key="status.value" :value="status.value">
                                {{ status.label }}
                            </option>
                        </select>
                    </div>
                    <div>
                        <Label for="date-from" class="mb-1 dark:text-gray-200">Data od</Label>
                        <Popover>
                            <PopoverTrigger as-child>
                                <Button
                                    variant="outline"
                                    class="w-full justify-start text-left font-normal "
                                    :class="!filters.date_from && 'text-muted-foreground dark:text-gray-400'"
                                >
                                    <Icon name="calendar" size="16" class="mr-2"/>
                                    <span>{{ filters.date_from ? formatDisplayDate(filters.date_from) : 'Wybierz datę' }}</span>
                                </Button>
                            </PopoverTrigger>
                            <PopoverContent class="w-auto p-0">
                                <Calendar :model-value="dateFrom" initial-focus mode="single"
                                          @update:model-value="onDateFromChange"
                                          class="dark:bg-gray-800 dark:text-white"/>
                            </PopoverContent>
                        </Popover>
                    </div>
                    <div>
                        <Label for="date-to" class="mb-1 dark:text-gray-200">Data do</Label>
                        <Popover>
                            <PopoverTrigger as-child>
                                <Button
                                    variant="outline"
                                    class="w-full justify-start text-left font-normal "
                                    :class="!filters.date_to && 'text-muted-foreground dark:text-gray-400'"
                                >
                                    <Icon name="calendar" size="16" class="mr-2"/>
                                    <span>{{ filters.date_to ? formatDisplayDate(filters.date_to) : 'Wybierz datę' }}</span>
                                </Button>
                            </PopoverTrigger>
                            <PopoverContent class="w-auto p-0 ">
                                <Calendar :model-value="dateTo" initial-focus @update:model-value="onDateToChange"
                                          class="dark:bg-gray-800 dark:text-white"/>
                            </PopoverContent>
                        </Popover>
                    </div>
                    <div class="flex items-end space-x-2">
                        <Button @click="loadAppointments"
                                class="bg-nova-primary hover:bg-nova-accent dark:bg-nova-accent dark:hover:bg-nova-primary dark:text-nova-light">
                            <Icon name="search" size="16" class="mr-2"/>
                            Filtruj
                        </Button>
                        <Button variant="outline" @click="resetFilters"
                                class="dark:bg-gray-700 dark:text-white dark:border-gray-600 dark:hover:bg-gray-600">
                            <Icon name="x" size="16" class="mr-2"/>
                            Wyczyść filtry
                        </Button>
                    </div>
                </div>
            </div>

            <div class="rounded-lg dark:bg-gray-900 shadow-sm overflow-hidden border dark:border-gray-700">
                <ScrollArea class="w-full h-[clamp(250px,calc(100vh-400px),500px)]">
                    <Table class="dark:text-gray-200">
                        <TableCaption v-if="!loading && appointments.length === 0" class="dark:text-gray-400">
                            Brak wizyt spełniających kryteria
                        </TableCaption>
                        <TableHeader class="dark:bg-gray-800">
                            <TableRow class="dark:border-gray-700">
                                <TableHead class="dark:text-gray-200">ID</TableHead>
                                <TableHead class="dark:text-gray-200">Pacjent</TableHead>
                                <TableHead class="dark:text-gray-200">Lekarz</TableHead>
                                <TableHead class="dark:text-gray-200">Procedura</TableHead>
                                <TableHead class="dark:text-gray-200">Data i godzina</TableHead>
                                <TableHead class="dark:text-gray-200">Status</TableHead>
                                <TableHead class="text-center dark:text-gray-200">Akcje</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-if="loading" class="dark:border-gray-700">
                                <TableCell colSpan="7" class="text-center py-4 dark:text-gray-200">
                                    <div class="flex justify-center items-center">
                                        <Icon name="loader-2" class="animate-spin h-8 w-8 text-nova-primary"/>
                                    </div>
                                </TableCell>
                            </TableRow>
                            <TableRow
                                v-else
                                v-for="appointment in appointments"
                                :key="appointment.id"
                                class="border-b hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-700"
                            >
                                <TableCell class="dark:text-gray-200">{{ appointment.id }}</TableCell>
                                <TableCell>
                                    <router-link :to="`/admin/patients/${appointment.patient.id}`"
                                                 class="text-nova-primary hover:underline">
                                        {{ appointment.patient.name }}
                                    </router-link>
                                </TableCell>
                                <TableCell>
                                    <div v-if="appointment.doctor">
                                        {{ appointment.doctor.first_name }} {{ appointment.doctor.last_name }}
                                    </div>
                                    <div v-else>Brak lekara</div>
                                </TableCell>
                                <TableCell class="dark:text-gray-200">{{ appointment.procedure.name }}</TableCell>
                                <TableCell class="dark:text-gray-200">
                                    {{ formatDateTime(appointment.appointment_datetime) }}
                                </TableCell>
                                <TableCell>
                                    <span
                                        class="px-2 py-1 text-xs font-medium rounded-full"
                                        :class="getStatusClass(appointment.status)"
                                    >
                                        {{ getStatusLabel(appointment.status) }}
                                    </span>
                                </TableCell>
                                <TableCell>
                                    <div class="flex space-x-2">
                                        <Tooltip>
                                            <TooltipTrigger as-child>
                                                <Button variant="ghost" size="sm" @click="openEditForm(appointment)">
                                                    <Icon name="pencil" size="16"/>
                                                </Button>
                                            </TooltipTrigger>
                                            <TooltipContent>Edytuj</TooltipContent>
                                        </Tooltip>
                                        <Tooltip>
                                            <TooltipTrigger as-child>
                                                <Button variant="ghost" size="sm" @click="deleteAppointment(appointment.id)">
                                                    <Icon name="trash-2" size="16"/>
                                                </Button>
                                            </TooltipTrigger>
                                            <TooltipContent>Usuń</TooltipContent>
                                        </Tooltip>
                                    </div>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </ScrollArea>

                <div class="flex justify-center items-center p-3 border-t dark:bg-background dark:border-gray-700 ">
                    <Pagination
                        v-if="meta.last_page > 1"
                        :items-per-page="meta.per_page"
                        :total="meta.total"
                        :sibling-count="1"
                        show-edges
                        :default-page="meta.current_page"
                        @update:page="changePage"
                    >
                        <PaginationList v-slot="{ items }" class="flex items-center gap-1">
                            <PaginationFirst @click="changePage(1)"
                                             class="dark:bg-gray-700 dark:text-white dark:border-gray-600"/>
                            <PaginationPrevious @click="changePage(Math.max(1, meta.current_page - 1))"
                                                class="dark:bg-gray-700 dark:text-white dark:border-gray-600"/>

                            <template v-for="(item, index) in items" :key="index">
                                <PaginationListItem v-if="item.type === 'page'" :value="item.value" as-child>
                                    <Button
                                        class="w-9 h-9 p-0"
                                        :variant="item.value === meta.current_page ? 'bg-nova-primary hover:bg-nova-accent text-white' : 'ghost'"
                                        @click="changePage(item.value)"
                                    >
                                        {{ item.value }}
                                    </Button>
                                </PaginationListItem>
                                <PaginationEllipsis v-else :index="index" class="dark:text-gray-400"/>
                            </template>

                            <PaginationNext @click="changePage(Math.min(meta.last_page, meta.current_page + 1))"
                                            class="dark:bg-gray-700 dark:text-white dark:border-gray-600"/>
                            <PaginationLast @click="changePage(meta.last_page)"
                                            class="dark:bg-gray-700 dark:text-white dark:border-gray-600"/>
                        </PaginationList>
                    </Pagination>
                </div>
            </div>
        </div>

        <Dialog :open="isReportConfigOpen" @update:open="isReportConfigOpen = $event">
            <DialogContent class="max-w-3xl">
                <DialogHeader>
                    <DialogTitle>Zaawansowana Konfiguracja Raportu</DialogTitle>
                    <DialogDescription>
                        Dostosuj każdy aspekt raportu, który zostanie wygenerowany.
                    </DialogDescription>
                </DialogHeader>

                <div class="max-h-[70vh] overflow-y-auto p-1 pr-4">
                    <Accordion type="single" collapsible class="w-full" default-value="'item-1'">
                        <AccordionItem value="item-1">
                            <AccordionTrigger>Opcje Główne</AccordionTrigger>
                            <AccordionContent class="space-y-4">
                                <div class="grid grid-cols-4 items-center gap-4">
                                    <Label for="report-title" class="text-right">Tytuł Raportu</Label>
                                    <Input id="report-title" v-model="reportConfig.title" class="col-span-3" />
                                </div>
                                <div class="flex items-center space-x-2">
                                    <Checkbox id="page-footer" v-model:checked="reportConfig.pageFooterEnabled" />
                                    <label for="page-footer" class="text-sm font-medium">Dołącz stopkę z numeracją stron</label>
                                </div>
                            </AccordionContent>
                        </AccordionItem>

                        <AccordionItem value="item-2">
                            <AccordionTrigger>Kolumny</AccordionTrigger>
                            <AccordionContent>
                                <div class="grid grid-cols-5 gap-x-4 gap-y-2 items-center font-semibold text-sm">
                                    <span>Widoczna</span>
                                    <span class="col-span-2">Nagłówek</span>
                                    <span>Szerokość (auto: -1)</span>
                                    <span>Format</span>
                                </div>
                                <div v-for="(col, index) in reportConfig.columns" :key="index" class="grid grid-cols-5 gap-x-4 gap-y-2 items-center mt-2">
                                    <Checkbox v-model="col.visible" />
                                    <Input v-model="col.header" class="col-span-2 text-xs h-8"/>
                                    <Input v-model.number="col.width" type="number" class="text-xs h-8"/>
                                    <Input v-model="col.format" class="text-xs h-8" placeholder="np. #,##0.00"/>
                                </div>
                            </AccordionContent>
                        </AccordionItem>

                        <AccordionItem value="item-3">
                            <AccordionTrigger>Grupowanie</AccordionTrigger>
                            <AccordionContent>
                                <div v-for="(group, index) in reportConfig.groups" :key="index" class="p-2 border rounded-md mb-2">
                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <Label>Grupuj po polu</Label>
                                            <select v-model="group.field" class="w-full mt-1 rounded-md border border-input bg-background px-3 py-2 text-sm">
                                                <option v-for="field in availableFields" :key="field.field" :value="field.field">{{ field.header }}</option>
                                            </select>
                                        </div>
                                        <div>
                                            <Label>Wyrażenie nagłówka</Label>
                                            <Input v-model="group.label" placeholder='"Dział: " + $F{...}' class="mt-1"/>
                                        </div>
                                    </div>
                                    <div class="flex justify-between items-center mt-2">
                                        <div class="flex items-center space-x-2">
                                            <Checkbox :id="`group-footer-${index}`" v-model="group.showFooter" />
                                            <label :for="`group-footer-${index}`" class="text-sm">Pokaż podsumowanie w nagłówku</label>
                                        </div>
                                        <Button variant="destructive" size="sm" @click="removeGroup(index)">Usuń</Button>
                                    </div>
                                </div>
                                <Button @click="addGroup" class="mt-2 w-full">Dodaj nową grupę</Button>
                            </AccordionContent>
                        </AccordionItem>
                    </Accordion>
                </div>

                <DialogFooter>
                    <Button @click="generateReport" :disabled="reportLoading">
                        <Icon v-if="reportLoading" name="loader-2" class="animate-spin mr-2" size="16"/>
                        <span v-else>Generuj PDF</span>
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Okno dialogowe do edycji wizyty (istniejący kod) -->
        <div v-if="showEditAppointmentForm && selectedAppointment"
             class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-2 sm:p-4">
            <div class="bg-white dark:bg-gray-800 rounded-xl w-full max-w-md max-h-[90vh] flex flex-col shadow-lg">
                <!-- Nagłówek formularza -->
                <div class="flex justify-between items-center p-3 sm:p-4 border-b dark:border-gray-700">
                    <h3 class="text-lg font-medium dark:text-white">Edycja Wizyty</h3>
                    <Button variant="ghost" class="h-8 w-8 p-0 dark:text-white dark:hover:bg-gray-700" @click="closeEditForm">
                        <Icon name="x" size="18"/>
                    </Button>
                </div>

                <!-- Zawartość z przewijaniem -->
                <div class="overflow-y-auto p-3 sm:p-4 flex-grow">
                    <div class="space-y-3 sm:space-y-4">
                        <div class="space-y-1">
                            <Label for="edit-patient" class="dark:text-gray-200 text-sm">Pacjent</Label>
                            <select
                                id="edit-patient"
                                v-model="selectedAppointment.patient_id"
                                class="w-full rounded-md border border-input bg-background px-2 py-1 text-xs sm:text-sm dark:bg-gray-700 dark:text-white dark:border-gray-600"
                                :class="{'border-red-500': appointmentErrors.patient_id}"
                            >
                                <option value="" disabled>{{ patientsLoading ? 'Ładowanie pacjentów...' : 'Wybierz pacjenta' }}</option>
                                <option v-for="patient in allPatients || []" :key="patient.id" :value="patient.id">
                                    {{ patient.name }}
                                </option>
                            </select>
                            <div v-if="appointmentErrors.patient_id" class="text-xs text-red-500">
                                {{ appointmentErrors.patient_id[0] }}
                            </div>
                        </div>

                        <div class="space-y-1">
                            <Label for="edit-doctor" class="dark:text-gray-200 text-sm">Lekarz</Label>
                            <select
                                id="edit-doctor"
                                v-model="selectedAppointment.doctor_id"
                                class="w-full rounded-md border border-input bg-background px-2 py-1 text-xs sm:text-sm dark:bg-gray-700 dark:text-white dark:border-gray-600"
                                :class="{'border-red-500': appointmentErrors.doctor_id}"
                            >
                                <option value="" disabled>{{ doctorsLoading ? 'Ładowanie lekarzy...' : 'Wybierz lekarza' }}</option>
                                <option v-for="doctor in allDoctors || []" :key="doctor.id" :value="doctor.id">
                                    {{ doctor.first_name }} {{ doctor.last_name }}
                                </option>
                            </select>
                            <div v-if="appointmentErrors.doctor_id" class="text-xs text-red-500">
                                {{ appointmentErrors.doctor_id[0] }}
                            </div>
                        </div>

                        <div class="space-y-1">
                            <Label for="edit-procedure" class="dark:text-gray-200 text-sm">Procedura</Label>
                            <select
                                id="edit-procedure"
                                v-model="selectedAppointment.procedure_id"
                                class="w-full rounded-md border border-input bg-background px-2 py-1 text-xs sm:text-sm dark:bg-gray-700 dark:text-white dark:border-gray-600"
                                :class="{'border-red-500': appointmentErrors.procedure_id}"
                            >
                                <option value="" disabled>Wybierz procedurę</option>
                                <option v-for="procedure in procedures || []" :key="procedure.id" :value="procedure.id">
                                    {{ procedure.name }}
                                </option>
                            </select>
                            <div v-if="proceduresLoading" class="text-xs text-amber-500 mt-1">
                                Ładowanie procedur...
                            </div>
                            <div v-if="!proceduresLoading && procedures && procedures.length === 0" class="text-xs text-amber-500 mt-1">
                                Brak dostępnych procedur
                            </div>
                            <div v-if="appointmentErrors.procedure_id" class="text-xs text-red-500">
                                {{ appointmentErrors.procedure_id[0] }}
                            </div>
                        </div>

                        <div class="space-y-1">
                            <Label class="dark:text-gray-200 text-sm">Data wizyty</Label>
                            <Popover>
                                <PopoverTrigger as-child>
                                    <Button
                                        variant="outline"
                                        class="w-full justify-start text-left font-normal text-xs sm:text-sm"
                                        :class="!selectedAppointment.appointment_datetime && 'text-muted-foreground'"
                                    >
                                        <Icon name="calendar" class="mr-2 h-4 w-4"/>
                                        <span>{{ selectedAppointment.appointment_datetime ? formatDisplayDate(selectedAppointment.appointment_datetime) : 'Wybierz datę' }}</span>
                                    </Button>
                                </PopoverTrigger>
                                <PopoverContent class="w-auto p-0 dark:bg-gray-800 dark:border-gray-700">
                                    <Calendar
                                        :model-value="selectedDate"
                                        @update:model-value="onAppointmentDateChange"
                                        initial-focus
                                        mode="single"
                                        class="dark:bg-gray-800 dark:text-white"
                                    />
                                </PopoverContent>
                            </Popover>
                            <div v-if="appointmentErrors.appointment_datetime" class="text-xs text-red-500">
                                {{ appointmentErrors.appointment_datetime[0] }}
                            </div>
                        </div>

                        <div class="space-y-1">
                            <Label for="edit-status" class="dark:text-gray-200 text-sm">Status wizyty</Label>
                            <select
                                id="edit-status"
                                v-model="selectedAppointment.status"
                                class="w-full rounded-md border border-input bg-background px-2 py-1 text-xs sm:text-sm dark:bg-gray-700 dark:text-white dark:border-gray-600"
                                :class="{'border-red-500': appointmentErrors.status}"
                            >
                                <option v-for="status in statusOptions" :key="status.value" :value="status.value">
                                    {{ status.label }}
                                </option>
                            </select>
                            <div v-if="appointmentErrors.status" class="text-xs text-red-500">
                                {{ appointmentErrors.status[0] }}
                            </div>
                        </div>

                        <div class="space-y-1">
                            <Label for="patient-notes" class="dark:text-gray-200 text-sm">Notatki pacjenta</Label>
                            <textarea
                                id="patient-notes"
                                v-model="selectedAppointment.patient_notes"
                                class="w-full rounded-md border border-input px-2 py-1 text-xs sm:text-sm dark:bg-gray-700 dark:text-white dark:border-gray-600"
                                :class="{'border-red-500': appointmentErrors.patient_notes}"
                                rows="2"
                                placeholder="Notatki od pacjenta"
                            ></textarea>
                            <div v-if="appointmentErrors.patient_notes" class="text-xs text-red-500">
                                {{ appointmentErrors.patient_notes[0] }}
                            </div>
                        </div>

                        <div class="space-y-1">
                            <Label for="doctor-notes" class="dark:text-gray-200 text-sm">Notatki lekarza</Label>
                            <textarea
                                id="doctor-notes"
                                v-model="selectedAppointment.doctor_notes"
                                class="w-full rounded-md border border-input px-2 py-1 text-xs sm:text-sm dark:bg-gray-700 dark:text-white dark:border-gray-600"
                                :class="{'border-red-500': appointmentErrors.doctor_notes}"
                                rows="2"
                                placeholder="Notatki od lekarza"
                            ></textarea>
                            <div v-if="appointmentErrors.doctor_notes" class="text-xs text-red-500">
                                {{ appointmentErrors.doctor_notes[0] }}
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end gap-3 p-3 sm:p-4 border-t dark:border-gray-700">
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        @click="closeEditForm"
                        class="dark:bg-gray-700 dark:text-white dark:border-gray-600 dark:hover:bg-gray-600"
                    >
                        Anuluj
                    </Button>
                    <Button
                        @click="updateAppointment"
                        :disabled="appointmentFormLoading"
                        size="sm"
                        class="flex bg-nova-primary hover:bg-nova-accent dark:bg-nova-accent dark:hover:bg-nova-primary dark:text-nova-light items-center gap-2"
                    >
                        <Icon v-if="appointmentFormLoading" name="loader2" class="animate-spin" size="16"/>
                        <span>Aktualizuj</span>
                    </Button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
:deep(thead th) {
    background-color: #f9fafb;
    color: #374151;
    font-weight: 600;
    text-align: left;
    padding: 0.75rem 1rem;
    font-size: 0.875rem;
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

tbody td {
    padding: 1rem;
    vertical-align: middle;
}
</style>
