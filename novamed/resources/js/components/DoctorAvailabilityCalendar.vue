<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue';
import axios from 'axios';
import { Button } from '@/components/ui/button';
import Icon from '@/components/Icon.vue';
import { ArrowRight, ArrowLeft } from 'lucide-vue-next';

const props = defineProps<{
    doctorId: number | null;
    procedureId: number | null;
}>();

const emit = defineEmits<{
    (e: 'dateSelected', date: string, time: string): void;
}>();

interface AvailabilitySlot {
    date: string;
    times: string[];
}

interface CalendarDay {
    date: Date;
    isCurrentMonth: boolean;
    isToday: boolean;
    hasAnyBackendAvailability: boolean;
    isSelected: boolean;
}

interface BookedAppointment {
    date: string;
    time: string;
    end_time: string;
    procedure_duration: number;
}

interface DisplayableTimeSlot {
    time: string;
    isConflictingWithBooked: boolean;
    isGenerallyUnavailableByBackend: boolean;
    isDisabled: boolean;
}

interface AlternativeTimeProposal {
    date: string;
    time: string;
    formattedDate?: string;
}

interface ProcedureDetails {
    id: number;
    name: string;
    duration_minutes: number;
}

const loading = ref(false);
const currentDate = ref(new Date());
const selectedDate = ref<Date | null>(null);
const availabilityData = ref<AvailabilitySlot[]>([]);
const bookedAppointments = ref<BookedAppointment[]>([]);
const selectedTime = ref<string | null>(null);
const errorMessage = ref<string | null>(null);

const showProposalDialog = ref(false);
const proposedAlternative = ref<AlternativeTimeProposal | null>(null);
const unavailableSelectedTime = ref<string | null>(null);

const allProcedures = ref<ProcedureDetails[]>([]);

const currentProcedureDuration = computed(() => {
    if (props.procedureId && allProcedures.value.length > 0) {
        const procedure = allProcedures.value.find(p => p.id === props.procedureId);
        return procedure ? (procedure.duration_minutes || 30) : 30;
    }
    return 30;
});

async function fetchAllProcedures() {
    try {
        const response = await axios.get('/api/v1/procedures', { params: { limit: 999 } });
        allProcedures.value = (response.data.data || response.data || []).map((p: any) => ({
            id: p.id,
            name: p.name,
            duration_minutes: p.duration_minutes || 30
        }));
    } catch (error) {
        console.error('Błąd podczas pobierania listy wszystkich procedur:', error);
    }
}

function generateAllTimeSlotsForUI(): string[] {
    const slots: string[] = [];
    const workStartHour = 9;
    const workEndHour = 17;
    const slotInterval = 30;

    for (let hour = workStartHour; hour < workEndHour; hour++) {
        for (let minute = 0; minute < 60; minute += slotInterval) {
            slots.push(`${String(hour).padStart(2, '0')}:${String(minute).padStart(2, '0')}`);
        }
    }
    return slots;
}

async function fetchAvailability() {
    if (!props.doctorId || !props.procedureId) {
        availabilityData.value = [];
        return;
    }
    loading.value = true;
    errorMessage.value = null;

    const year = currentDate.value.getFullYear();
    const month = currentDate.value.getMonth();
    const firstDayCurrentMonth = new Date(year, month, 1);
    const lastDayCurrentMonth = new Date(year, month + 1, 0);
    const formattedStartDate = formatDate(firstDayCurrentMonth);
    const formattedEndDate = formatDate(lastDayCurrentMonth);

    try {
        const paramsForAvailability = {
            start_date: formattedStartDate,
            end_date: formattedEndDate,
            procedure_id: props.procedureId
        };
        const paramsForBooked = {
            start_date: formattedStartDate,
            end_date: formattedEndDate,
        };

        const [availabilityResponse, bookingsResponse] = await Promise.all([
            axios.get(`/api/v1/doctors/${props.doctorId}/availability`, { params: paramsForAvailability }),
            axios.get(`/api/v1/doctors/${props.doctorId}/booked-appointments`, { params: paramsForBooked })
        ]);

        availabilityData.value = availabilityResponse.data.data || [];
        if (typeof bookingsResponse.data === 'object' && bookingsResponse.data !== null && Array.isArray(bookingsResponse.data.data)) {
            bookedAppointments.value = bookingsResponse.data.data;
        } else {
            bookedAppointments.value = [];
        }

        if (selectedDate.value) {
            updateDaySpecificData();
        }
    } catch (error: any) {
        console.error('Błąd fetchAvailability:', error);
        errorMessage.value = 'Nie udało się pobrać grafiku.';
        availabilityData.value = [];
        bookedAppointments.value = [];
    } finally {
        loading.value = false;
    }
}
const isDayGenerallyAvailableByBackend = (dateStr: string): boolean => {
    const availabilitySlot = availabilityData.value.find(slot => slot.date === dateStr);
    return !!(availabilitySlot && availabilitySlot.times.length > 0);
};


const calendarDays = computed<CalendarDay[]>(() => {
    const year = currentDate.value.getFullYear();
    const month = currentDate.value.getMonth();
    const firstDayOfMonth = new Date(year, month, 1);
    const lastDayOfMonth = new Date(year, month + 1, 0);

    let startDayOfWeek = firstDayOfMonth.getDay();
    startDayOfWeek = startDayOfWeek === 0 ? 6 : startDayOfWeek - 1;

    const today = new Date();
    today.setHours(0,0,0,0);
    const daysArray: CalendarDay[] = [];

    const prevMonthLastDate = new Date(year, month, 0).getDate();
    for (let i = startDayOfWeek - 1; i >= 0; i--) {
        const date = new Date(year, month - 1, prevMonthLastDate - i);
        daysArray.push({
            date,
            isCurrentMonth: false,
            isToday: isSameDay(date, today),
            hasAnyBackendAvailability: isDayGenerallyAvailableByBackend(formatDate(date)),
            isSelected: selectedDate.value ? isSameDay(date, selectedDate.value) : false
        });
    }

    for (let i = 1; i <= lastDayOfMonth.getDate(); i++) {
        const date = new Date(year, month, i);
        daysArray.push({
            date,
            isCurrentMonth: true,
            isToday: isSameDay(date, today),
            hasAnyBackendAvailability: isDayGenerallyAvailableByBackend(formatDate(date)),
            isSelected: selectedDate.value ? isSameDay(date, selectedDate.value) : false
        });
    }

    const remainingCells = Math.max(0, 42 - daysArray.length);
    for (let i = 1; i <= remainingCells; i++) {
        const date = new Date(year, month + 1, i);
        daysArray.push({
            date,
            isCurrentMonth: false,
            isToday: isSameDay(date, today),
            hasAnyBackendAvailability: isDayGenerallyAvailableByBackend(formatDate(date)),
            isSelected: selectedDate.value ? isSameDay(date, selectedDate.value) : false
        });
    }
    return daysArray;
});

function formatDate(date: Date): string {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}

function formatDateForDisplay(dateStr: string): string {
    const date = new Date(dateStr);
    const day = date.getDate();
    const monthIdx = date.getMonth();
    const year = date.getFullYear();
    return `${day} ${months[monthIdx]} ${year}`;
}

function isSameDay(date1: Date, date2: Date): boolean {
    return date1.getFullYear() === date2.getFullYear() &&
        date1.getMonth() === date2.getMonth() &&
        date1.getDate() === date2.getDate();
}

function updateDaySpecificData() {
    if (!selectedDate.value) {
        return;
    }
    selectedTime.value = null;
}

function parseTimeToMinutes(timeStr: string): number {
    const [hours, minutes] = timeStr.split(':').map(Number);
    return hours * 60 + minutes;
}

function isTimeSlotConflictingWithBooked(timeToCheckStr: string, dateStr: string): boolean {
    if (!props.procedureId) return false;

    const newApptStartMinutes = parseTimeToMinutes(timeToCheckStr);
    const newApptEndMinutes = newApptStartMinutes + currentProcedureDuration.value;

    const appointmentsOnDate = bookedAppointments.value.filter(appt => appt.date === dateStr);

    for (const existingAppt of appointmentsOnDate) {
        const existingApptStartMinutes = parseTimeToMinutes(existingAppt.time);
        const existingApptDuration = existingAppt.procedure_duration || 30;
        const existingApptEndMinutes = existingApptStartMinutes + existingApptDuration;

        if (newApptStartMinutes < existingApptEndMinutes && newApptEndMinutes > existingApptStartMinutes) {
            return true;
        }
    }
    return false;
}

const displayableTimes = computed<DisplayableTimeSlot[]>(() => {
    if (!selectedDate.value || !props.procedureId) {
        return [];
    }
    const selectedDateStr = formatDate(selectedDate.value);
    const uiGridSlots = generateAllTimeSlotsForUI();

    const dayHasAnyBackendAvailability = isDayGenerallyAvailableByBackend(selectedDateStr);

    return uiGridSlots.map(time => {
        const isConflicting = isTimeSlotConflictingWithBooked(time, selectedDateStr);
        const isGenerallyUnavailable = !dayHasAnyBackendAvailability;

        const slotStartTimeInMinutes = parseTimeToMinutes(time);
        const workEndTimeInMinutes = 17 * 60;
        const slotFitsInWorkday = (slotStartTimeInMinutes + currentProcedureDuration.value) <= workEndTimeInMinutes;

        return {
            time,
            isConflictingWithBooked: isConflicting,
            isGenerallyUnavailableByBackend: isGenerallyUnavailable,
            isDisabled: isConflicting || isGenerallyUnavailable || !slotFitsInWorkday,
        };
    }).sort((a, b) => parseTimeToMinutes(a.time) - parseTimeToMinutes(b.time));
});

function findAlternativeTimeSlot(): AlternativeTimeProposal | null {
    if (!selectedDate.value || !unavailableSelectedTime.value) return null;

    const selectedDateStr = formatDate(selectedDate.value);
    const selectedTimeMinutes = parseTimeToMinutes(unavailableSelectedTime.value);

    if (isDayGenerallyAvailableByBackend(selectedDateStr)) {
        const dayAvailability = availabilityData.value.find(s => s.date === selectedDateStr);

        if (dayAvailability && dayAvailability.times.length > 0) {
            const standardTimes = dayAvailability.times.filter(time => {
                const [_, minutes] = time.split(':').map(Number);
                return minutes === 0 || minutes === 30;
            });

            let validTimes = standardTimes;
            if (standardTimes.length === 0 && dayAvailability.times.length > 0) {
                validTimes = dayAvailability.times.map(time => {
                    const [hours, minutes] = time.split(':').map(Number);
                    const roundedMinutes = minutes <= 30 ? 30 : 0;
                    const adjustedHours = minutes > 30 ? hours + 1 : hours;
                    return `${String(adjustedHours).padStart(2, '0')}:${String(roundedMinutes).padStart(2, '0')}`;
                }).filter((time, index, self) => {
                    return self.indexOf(time) === index &&
                        !isTimeSlotConflictingWithBooked(time, selectedDateStr);
                });
            }

            if (validTimes.length > 0) {
                let closestTime = validTimes[0];
                let minDiff = Math.abs(parseTimeToMinutes(closestTime) - selectedTimeMinutes);

                for (const time of validTimes) {
                    const diff = Math.abs(parseTimeToMinutes(time) - selectedTimeMinutes);
                    if (diff < minDiff) {
                        minDiff = diff;
                        closestTime = time;
                    }
                }

                return {
                    date: selectedDateStr,
                    time: closestTime,
                    formattedDate: formatDateForDisplay(selectedDateStr)
                };
            }
        }
    }

    const searchStartDate = new Date(selectedDate.value);
    searchStartDate.setDate(searchStartDate.getDate() + 1);

    for (let i = 0; i < 29; i++) {
        const currentDateStr = formatDate(searchStartDate);

        if (isDayGenerallyAvailableByBackend(currentDateStr)) {
            const dayAvailability = availabilityData.value.find(s => s.date === currentDateStr);

            if (dayAvailability && dayAvailability.times.length > 0) {
                for (const time of dayAvailability.times) {
                    const startMinutes = parseTimeToMinutes(time);
                    const endMinutes = startMinutes + currentProcedureDuration.value;

                    if (endMinutes > 17 * 60) continue;

                    let hasConflict = false;
                    const appointmentsOnDate = bookedAppointments.value.filter(appt => appt.date === currentDateStr);
                    for (const existingAppt of appointmentsOnDate) {
                        const existingStartMinutes = parseTimeToMinutes(existingAppt.time);
                        const existingEndMinutes = existingStartMinutes + existingAppt.procedure_duration;

                        if (startMinutes < existingEndMinutes && endMinutes > existingStartMinutes) {
                            hasConflict = true;
                            break;
                        }
                    }

                    if (!hasConflict) {
                        return {
                            date: currentDateStr,
                            time: time,
                            formattedDate: formatDateForDisplay(currentDateStr)
                        };
                    }
                }
            }
        }

        searchStartDate.setDate(searchStartDate.getDate() + 1);
    }

    return null;
}

function handleTimeSlotClick(slotItem: DisplayableTimeSlot) {
    if (slotItem.isDisabled) {
        unavailableSelectedTime.value = slotItem.time;
        const alternative = findAlternativeTimeSlot();

        if (alternative) {
            proposedAlternative.value = alternative;
            showProposalDialog.value = true;
        } else {
            if (slotItem.isGenerallyUnavailableByBackend) {
                errorMessage.value = `Brak dostępnych terminów w dniu ${selectedDate.value ? formatDateForDisplay(formatDate(selectedDate.value)) : ''} dla tej procedury. Brak bliskich alternatyw.`;
            } else if (slotItem.isConflictingWithBooked) {
                errorMessage.value = `Termin ${slotItem.time} koliduje z inną rezerwacją. Brak bliskich alternatyw.`;
            } else {
                errorMessage.value = `Wizyta o ${slotItem.time} (trwająca ${currentProcedureDuration.value} min) zakończyłaby się po godzinach pracy. Brak bliskich alternatyw.`;
            }
        }
        return;
    }

    errorMessage.value = null;
    selectTime(slotItem.time);
}

function handleBookingConflict(conflictTime: string): boolean {
    unavailableSelectedTime.value = conflictTime;
    const alternative = findAlternativeTimeSlot();

    if (alternative) {
        proposedAlternative.value = alternative;
        showProposalDialog.value = true;
        return true;
    } else {
        errorMessage.value = `Termin ${conflictTime} koliduje z inną wizytą. Brak bliskich alternatyw.`;
        return false;
    }
}

function acceptProposedTime() {
    if (proposedAlternative.value) {
        const [year, month, day] = proposedAlternative.value.date.split('-').map(Number);
        selectedDate.value = new Date(year, month - 1, day);
        updateDaySpecificData();
        selectTime(proposedAlternative.value.time);

        showProposalDialog.value = false;
        proposedAlternative.value = null;
        unavailableSelectedTime.value = null;
    }
}

function rejectProposedTime() {
    showProposalDialog.value = false;
    proposedAlternative.value = null;
    unavailableSelectedTime.value = null;
}

function previousMonth() {
    currentDate.value = new Date(currentDate.value.getFullYear(), currentDate.value.getMonth() - 1, 1);
}

function nextMonth() {
    currentDate.value = new Date(currentDate.value.getFullYear(), currentDate.value.getMonth() + 1, 1);
}

function selectDate(day: CalendarDay) {
    const today = new Date(); today.setHours(0,0,0,0);

    if (!day.isCurrentMonth) return;
    if (day.date < today && !isSameDay(day.date, today)) {
        errorMessage.value = "Nie można wybrać daty z przeszłości.";
        return;
    }

    errorMessage.value = null;
    selectedDate.value = day.date;
    updateDaySpecificData();
}

function selectTime(time: string) {
    selectedTime.value = time;
    if (selectedDate.value) {
        const dateStr = formatDate(selectedDate.value);
        emit('dateSelected', dateStr, time);
    }
}

const weekdays = ['Pn', 'Wt', 'Śr', 'Cz', 'Pt', 'So', 'Nd'];
const months = [
    'Styczeń', 'Luty', 'Marzec', 'Kwiecień', 'Maj', 'Czerwiec',
    'Lipiec', 'Sierpień', 'Wrzesień', 'Październik', 'Listopad', 'Grudzień'
];

const currentMonthName = computed(() => {
    return `${months[currentDate.value.getMonth()]} ${currentDate.value.getFullYear()}`;
});

defineExpose({
    handleBookingConflict
});

watch(() => props.doctorId, (newDoctorId) => {
    selectedDate.value = null;
    selectedTime.value = null;
    if (newDoctorId && props.procedureId) fetchAvailability();
    else {
        availabilityData.value = [];
        bookedAppointments.value = [];
    }
}, { immediate: true });

watch(() => currentDate.value, () => {
    selectedDate.value = null;
    selectedTime.value = null;
    if (props.doctorId && props.procedureId) fetchAvailability();
});

watch(() => props.procedureId, (newProcedureId) => {
    selectedDate.value = null;
    selectedTime.value = null;
    if (props.doctorId && newProcedureId) fetchAvailability();
    else {
        availabilityData.value = [];
    }
});

onMounted(async () => {
    await fetchAllProcedures();
    if (props.doctorId && props.procedureId) {
        fetchAvailability();
    }
});
</script>

<template>
    <div class="doctor-availability-calendar bg-gray-50 dark:bg-gray-800/30 p-4 rounded-lg shadow">
        <div class="flex items-center justify-between mb-4">
            <Button variant="outline" size="icon" @click="previousMonth" class="dark:border-gray-600 dark:text-gray-300 h-9 w-9">
                <ArrowLeft class="h-5 w-5" />
            </Button>
            <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 tabular-nums">{{ currentMonthName }}</h3>
            <Button variant="outline" size="icon" @click="nextMonth" class="dark:border-gray-600 dark:text-gray-300 h-9 w-9">
                <ArrowRight class="h-5 w-5" />
            </Button>
        </div>

        <div class="grid grid-cols-7 gap-1 mb-2">
            <div v-for="dayName in weekdays" :key="dayName" class="text-center text-xs font-medium text-gray-500 dark:text-gray-400 py-1">
                {{ dayName }}
            </div>
        </div>

        <div class="grid grid-cols-7 gap-1 mb-4">
            <Button
                v-for="(dayItem, index) in calendarDays"
                :key="`${dayItem.date.toISOString()}-${index}`"
                :variant="dayItem.isSelected ? 'default' : (dayItem.isCurrentMonth ? 'outline' : 'ghost')"
                :class="[
                    'h-9 p-0 flex items-center justify-center relative text-sm',
                    dayItem.isCurrentMonth ? 'dark:text-gray-200 dark:border-gray-600' : 'text-gray-400 dark:text-gray-500 cursor-default',
                    dayItem.isToday && dayItem.isCurrentMonth ? '!border-2 !border-nova-accent dark:!border-nova-primary' : '',
                    dayItem.isSelected ? 'bg-nova-primary hover:bg-nova-accent dark:bg-nova-accent dark:hover:bg-nova-primary text-white dark:text-gray-900' :
                        (dayItem.hasAnyBackendAvailability && dayItem.isCurrentMonth ? 'hover:bg-gray-100 dark:hover:bg-gray-700' : ''),
                    (dayItem.date < new Date(new Date().setHours(0,0,0,0)) && !isSameDay(dayItem.date, new Date(new Date().setHours(0,0,0,0)))) ? 'text-gray-400 dark:text-gray-500 bg-gray-200 dark:bg-gray-700/50 cursor-not-allowed hover:bg-gray-200 dark:hover:bg-gray-700/50' : '',
                    (!dayItem.isCurrentMonth || (dayItem.isCurrentMonth && !dayItem.hasAnyBackendAvailability && dayItem.date >= new Date(new Date().setHours(0,0,0,0)))) ? 'opacity-60 cursor-not-allowed' : ''
                ]"
                :disabled="!dayItem.isCurrentMonth || (dayItem.date < new Date(new Date().setHours(0,0,0,0)) && !isSameDay(dayItem.date, new Date(new Date().setHours(0,0,0,0)))) || (dayItem.isCurrentMonth && !dayItem.hasAnyBackendAvailability && dayItem.date >= new Date(new Date().setHours(0,0,0,0)))"
                @click="selectDate(dayItem)"
            >
                {{ dayItem.date.getDate() }}
                <span v-if="dayItem.hasAnyBackendAvailability && dayItem.isCurrentMonth && dayItem.date >= new Date(new Date().setHours(0,0,0,0))" class="absolute bottom-1 left-1/2 transform -translate-x-1/2 w-1.5 h-1.5 bg-green-500 rounded-full"></span>
            </Button>
        </div>

        <div v-if="loading && props.doctorId && props.procedureId" class="flex justify-center items-center py-4 min-h-[100px]">
            <Icon name="loader-2" class="animate-spin h-6 w-6 text-gray-600 dark:text-gray-400" />
            <span class="ml-2 text-gray-600 dark:text-gray-400">Ładowanie terminów...</span>
        </div>

        <div v-if="errorMessage" class="text-red-500 dark:text-red-400 text-sm py-2 text-center bg-red-50 dark:bg-red-900/20 p-2 rounded-md">
            {{ errorMessage }}
        </div>

        <div v-if="selectedDate && !loading && props.doctorId && props.procedureId && displayableTimes.length > 0" class="mt-4">
            <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Dostępne godziny na {{ formatDateForDisplay(formatDate(selectedDate)) }}:
            </h4>
            <div class="grid grid-cols-3 sm:grid-cols-4 gap-2">
                <Button
                    v-for="slotItem in displayableTimes"
                    :key="slotItem.time"
                    :variant="selectedTime === slotItem.time && !slotItem.isDisabled ? 'default' : 'outline'"
                    :class="[
                        'text-xs sm:text-sm h-8 sm:h-9',
                        selectedTime === slotItem.time && !slotItem.isDisabled ? 'bg-nova-primary hover:bg-nova-accent dark:bg-nova-accent dark:hover:bg-nova-primary text-white dark:text-gray-900'
                            : 'dark:border-gray-600 dark:text-gray-300',
                        slotItem.isDisabled ? 'bg-gray-200 text-gray-400 dark:bg-gray-700 dark:text-gray-500 hover:bg-gray-300 dark:hover:bg-gray-600 cursor-pointer'
                            : 'hover:bg-gray-100 dark:hover:bg-gray-700'
                    ]"
                    @click="handleTimeSlotClick(slotItem)"
                >
                    {{ slotItem.time }}
                </Button>
            </div>
        </div>
        <div v-if="selectedDate && !loading && props.doctorId && props.procedureId && displayableTimes.length === 0 && !errorMessage" class="mt-4 text-center text-sm text-gray-500 dark:text-gray-400 py-4">
            Brak dostępnych godzin w wybranym dniu dla tej procedury.
        </div>
        <div v-if="!selectedDate && !loading && !errorMessage && props.doctorId && props.procedureId" class="mt-4 text-center text-sm text-gray-500 dark:text-gray-400 py-4">
            Wybierz datę z kalendarza, aby zobaczyć dostępne godziny.
        </div>
        <div v-if="(!props.doctorId || !props.procedureId) && !loading" class="mt-4 text-center text-sm text-gray-500 dark:text-gray-400 py-4">
            Wybierz najpierw specjalistę oraz usługę, aby zobaczyć kalendarz dostępności.
        </div>

        <Transition
            enter-active-class="transition-all duration-300 ease-out"
            enter-from-class="opacity-0 scale-95"
            enter-to-class="opacity-100 scale-100"
            leave-active-class="transition-all duration-200 ease-in"
            leave-from-class="opacity-100 scale-100"
            leave-to-class="opacity-0 scale-95"
        >
            <div v-if="showProposalDialog && proposedAlternative" class="fixed inset-0 bg-opacity-50 backdrop-blur-sm flex items-center justify-center z-50 p-4">
                <div class="bg-white dark:bg-gray-800 border-1 border-nova-accent p-6 rounded-lg shadow-xl max-w-sm w-full transform">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-1">Termin niedostępny</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-300 mb-4">
                        Wybrany termin ({{ unavailableSelectedTime }} w dniu {{ selectedDate ? formatDateForDisplay(formatDate(selectedDate)) : '' }}) jest niedostępny.
                    </p>
                    <p class="text-sm text-gray-600 dark:text-gray-300 mb-4">
                        Czy chcesz zarezerwować najbliższy wolny termin?
                    </p>
                    <div class="p-3 rounded-md mb-6 border border-blue-200 dark:border-blue-700">
                        <p class="font-medium text-blue-700 dark:text-blue-300 text-center">
                            {{ proposedAlternative.formattedDate }}, godz. {{ proposedAlternative.time }}
                        </p>
                    </div>
                    <div class="flex justify-end space-x-3">
                        <Button variant="outline" @click="rejectProposedTime" class="dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700">
                            Nie, dziękuję
                        </Button>
                        <Button @click="acceptProposedTime" class="bg-nova-primary text-white hover:bg-nova-accent dark:bg-nova-accent dark:text-gray-900 dark:hover:bg-nova-primary">
                            Akceptuj
                        </Button>
                    </div>
                </div>
            </div>
        </Transition>
    </div>
</template>
