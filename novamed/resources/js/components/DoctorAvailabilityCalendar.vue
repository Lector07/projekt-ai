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
    hasAvailability: boolean;
    isSelected: boolean;
}

interface BookedAppointment {
    date: string;
    time: string;
}

interface DisplayableTimeSlot {
    time: string;
    isDisabled: boolean;
}

const loading = ref(false);
const currentDate = ref(new Date());
const selectedDate = ref<Date | null>(null);
const availabilityData = ref<AvailabilitySlot[]>([]);
const bookedAppointments = ref<BookedAppointment[]>([]); // Przechowuje zarezerwowane wizyty
const availableTimesForSelectedDate = ref<string[]>([]);
const selectedTime = ref<string | null>(null);
const errorMessage = ref<string | null>(null);

async function fetchAvailability() {
    if (!props.doctorId) {
        availabilityData.value = [];
        bookedAppointments.value = [];
        return;
    }

    loading.value = true;
    errorMessage.value = null;

    const startDate = new Date(currentDate.value.getFullYear(), currentDate.value.getMonth(), 1);
    const endDate = new Date(currentDate.value.getFullYear(), currentDate.value.getMonth() + 1, 0);
    const formattedStartDate = formatDate(startDate);
    const formattedEndDate = formatDate(endDate);

    try {
        const [availabilityResponse, bookingsResponse] = await Promise.all([
            axios.get(`/api/v1/doctors/${props.doctorId}/availability`, {
                params: {
                    start_date: formattedStartDate,
                    end_date: formattedEndDate,
                    procedure_id: props.procedureId || undefined
                }
            }),
            // Założenie: endpoint do pobierania zarezerwowanych wizyt
            axios.get(`/api/v1/doctors/${props.doctorId}/booked-appointments`, {
                params: {
                    start_date: formattedStartDate,
                    end_date: formattedEndDate,
                }
            })
        ]);

        availabilityData.value = availabilityResponse.data.data || [];
        bookedAppointments.value = bookingsResponse.data.data || [];

        if (selectedDate.value) {
            updateAvailableTimesForSelectedDate();
        }
    } catch (error) {
        console.error('Błąd podczas pobierania danych dostępności/rezerwacji:', error);
        errorMessage.value = 'Nie udało się pobrać danych. Spróbuj ponownie.';
        availabilityData.value = [];
        bookedAppointments.value = [];
    } finally {
        loading.value = false;
    }
}

const calendarDays = computed(() => {
    const year = currentDate.value.getFullYear();
    const month = currentDate.value.getMonth();
    const firstDay = new Date(year, month, 1);
    const lastDay = new Date(year, month + 1, 0);
    let startDay = firstDay.getDay() - 1;
    if (startDay < 0) startDay = 6;

    const today = new Date();
    today.setHours(0,0,0,0); // Normalizuj dzisiejszą datę do północy
    const days: CalendarDay[] = [];

    const prevMonthLastDay = new Date(year, month, 0).getDate();
    for (let i = startDay - 1; i >= 0; i--) {
        const date = new Date(year, month - 1, prevMonthLastDay - i);
        days.push({
            date,
            isCurrentMonth: false,
            isToday: isSameDay(date, today),
            hasAvailability: checkHasAvailability(formatDate(date)),
            isSelected: selectedDate.value ? isSameDay(date, selectedDate.value) : false
        });
    }

    for (let i = 1; i <= lastDay.getDate(); i++) {
        const date = new Date(year, month, i);
        days.push({
            date,
            isCurrentMonth: true,
            isToday: isSameDay(date, today),
            hasAvailability: checkHasAvailability(formatDate(date)),
            isSelected: selectedDate.value ? isSameDay(date, selectedDate.value) : false
        });
    }

    const remainingDays = 42 - days.length;
    for (let i = 1; i <= remainingDays; i++) {
        const date = new Date(year, month + 1, i);
        days.push({
            date,
            isCurrentMonth: false,
            isToday: isSameDay(date, today),
            hasAvailability: checkHasAvailability(formatDate(date)),
            isSelected: selectedDate.value ? isSameDay(date, selectedDate.value) : false
        });
    }
    return days;
});

function formatDate(date: Date): string {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}

function isSameDay(date1: Date, date2: Date): boolean {
    return date1.getFullYear() === date2.getFullYear() &&
        date1.getMonth() === date2.getMonth() &&
        date1.getDate() === date2.getDate();
}

function checkHasAvailability(dateStr: string): boolean {
    return availabilityData.value.some(slot => slot.date === dateStr && slot.times.length > 0);
}

function updateAvailableTimesForSelectedDate() {
    if (!selectedDate.value) {
        availableTimesForSelectedDate.value = [];
        return;
    }
    const dateStr = formatDate(selectedDate.value);
    const availabilitySlot = availabilityData.value.find(slot => slot.date === dateStr);
    if (availabilitySlot && availabilitySlot.times.length > 0) {
        availableTimesForSelectedDate.value = availabilitySlot.times;
    } else {
        availableTimesForSelectedDate.value = [];
    }
    selectedTime.value = null;
}

function parseTimeToMinutes(timeStr: string): number {
    const [hours, minutes] = timeStr.split(':').map(Number);
    return hours * 60 + minutes;
}

function isTimeSlotDisabled(timeToCheckStr: string, dateStr: string): boolean {
    const appointmentsOnDate = bookedAppointments.value.filter(appt => appt.date === dateStr);
    if (appointmentsOnDate.length === 0) {
        return false;
    }

    const timeToCheckInMinutes = parseTimeToMinutes(timeToCheckStr);
    const twoHoursInMinutes = 120;

    for (const appt of appointmentsOnDate) {
        const bookedTimeInMinutes = parseTimeToMinutes(appt.time);
        const windowStart = bookedTimeInMinutes - twoHoursInMinutes;
        const windowEnd = bookedTimeInMinutes + twoHoursInMinutes;

        if (timeToCheckInMinutes >= windowStart && timeToCheckInMinutes <= windowEnd) {
            return true;
        }
    }
    return false;
}

const displayableTimes = computed<DisplayableTimeSlot[]>(() => {
    if (!selectedDate.value) {
        return [];
    }
    const selectedDateStr = formatDate(selectedDate.value);
    return availableTimesForSelectedDate.value.map(time => ({
        time: time,
        isDisabled: isTimeSlotDisabled(time, selectedDateStr)
    }));
});

function previousMonth() {
    currentDate.value = new Date(currentDate.value.getFullYear(), currentDate.value.getMonth() - 1, 1);
}

function nextMonth() {
    currentDate.value = new Date(currentDate.value.getFullYear(), currentDate.value.getMonth() + 1, 1);
}

function selectDate(day: CalendarDay) {
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    if (day.date < today && !isSameDay(day.date, today)) { // Pozwól wybrać dzisiejszy dzień
        errorMessage.value = "Nie można wybrać daty z przeszłości";
        return;
    }
    if (!day.hasAvailability && day.isCurrentMonth) { // Sprawdzaj tylko dla bieżącego miesiąca, jeśli nie ma kropki
        errorMessage.value = "Brak dostępnych terminów w tym dniu";
        // Nie return, pozwól wybrać dzień, aby pokazać "Brak dostępnych godzin..." jeśli to prawda po filtracji
    }

    errorMessage.value = null;
    selectedDate.value = day.date;
    // selectedTime.value = null; // Jest już w updateAvailableTimesForSelectedDate
    updateAvailableTimesForSelectedDate();
}

function selectTime(time: string) {
    selectedTime.value = time;
    if (selectedDate.value) {
        const dateStr = formatDate(selectedDate.value);
        emit('dateSelected', dateStr, time);
    }
}

const weekdays = ['Pon', 'Wt', 'Śr', 'Czw', 'Pt', 'Sob', 'Ndz'];
const months = [
    'Styczeń', 'Luty', 'Marzec', 'Kwiecień', 'Maj', 'Czerwiec',
    'Lipiec', 'Sierpień', 'Wrzesień', 'Październik', 'Listopad', 'Grudzień'
];

const currentMonthName = computed(() => {
    return `${months[currentDate.value.getMonth()]} ${currentDate.value.getFullYear()}`;
});

watch(() => props.doctorId, (newDoctorId) => {
    if (newDoctorId) {
        selectedDate.value = null;
        selectedTime.value = null;
        availableTimesForSelectedDate.value = [];
        fetchAvailability();
    } else {
        availabilityData.value = [];
        bookedAppointments.value = [];
    }
}, { immediate: true });

watch(() => currentDate.value, () => {
    if (props.doctorId) {
        selectedDate.value = null; // Resetuj wybraną datę przy zmianie miesiąca
        selectedTime.value = null;
        availableTimesForSelectedDate.value = [];
        fetchAvailability();
    }
});

watch(() => props.procedureId, () => {
    if (props.doctorId) {
        selectedDate.value = null; // Resetuj również przy zmianie procedury
        selectedTime.value = null;
        availableTimesForSelectedDate.value = [];
        fetchAvailability();
    }
});

onMounted(() => {
    if (props.doctorId) {
        fetchAvailability();
    }
});
</script>

<template>
    <div class="doctor-availability-calendar">
        <div class="flex items-center justify-between mb-4">
            <Button variant="outline" size="sm" @click="previousMonth" class="dark:border-gray-600 dark:text-gray-300">
                <ArrowLeft class="h-4 w-4" />
            </Button>
            <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200">{{ currentMonthName }}</h3>
            <Button variant="outline" size="sm" @click="nextMonth" class="dark:border-gray-600 dark:text-gray-300">
                <ArrowRight class="h-4 w-4" />
            </Button>
        </div>

        <div class="grid grid-cols-7 gap-1 mb-2">
            <div v-for="dayName in weekdays" :key="dayName" class="text-center text-sm font-medium text-gray-500 dark:text-gray-400">
                {{ dayName }}
            </div>
        </div>

        <div class="grid grid-cols-7 gap-1 mb-4">
            <Button
                v-for="dayItem in calendarDays"
                :key="`${dayItem.date.getFullYear()}-${dayItem.date.getMonth()}-${dayItem.date.getDate()}`"
                :variant="dayItem.isSelected ? 'default' : (dayItem.isCurrentMonth ? 'outline' : 'ghost')"
                :class="[
                    'h-10 p-0 flex items-center justify-center relative', // Dodano relative dla kropki
                    dayItem.isCurrentMonth ? 'dark:text-gray-200' : 'text-gray-400 dark:text-gray-500',
                    dayItem.isToday ? 'border-2 border-nova-accent dark:border-nova-primary' : '',
                    dayItem.isSelected ? 'bg-nova-primary hover:bg-nova-accent dark:bg-nova-accent dark:hover:bg-nova-primary text-white dark:text-gray-900' :
                        (dayItem.hasAvailability && dayItem.isCurrentMonth ? 'border-nova-primary/50 dark:border-nova-accent/50' : ''),
                    (!dayItem.isCurrentMonth || (dayItem.isCurrentMonth && !dayItem.hasAvailability && !(dayItem.date < new Date() && !isSameDay(dayItem.date, new Date())) )) && !dayItem.isSelected ? 'opacity-50 cursor-not-allowed' : '',
                     (dayItem.date < new Date() && !isSameDay(dayItem.date, new Date())) ? 'text-gray-400 dark:text-gray-500 bg-gray-100 dark:bg-gray-800 cursor-not-allowed' : ''
                ]"
                :disabled="!dayItem.isCurrentMonth || (dayItem.date < new Date() && !isSameDay(dayItem.date, new Date())) || (dayItem.isCurrentMonth && !dayItem.hasAvailability)"
                @click="selectDate(dayItem)"
            >
                {{ dayItem.date.getDate() }}
                <span v-if="dayItem.hasAvailability && dayItem.isCurrentMonth" class="absolute bottom-1 left-1/2 transform -translate-x-1/2 w-1.5 h-1.5 bg-green-500 rounded-full"></span>
            </Button>
        </div>

        <div v-if="loading" class="flex justify-center items-center py-4">
            <Icon name="loader-2" class="animate-spin h-6 w-6 text-gray-600 dark:text-gray-400" />
            <span class="ml-2 text-gray-600 dark:text-gray-400">Ładowanie terminów...</span>
        </div>

        <div v-if="errorMessage" class="text-red-500 dark:text-red-400 text-sm py-2 text-center">
            {{ errorMessage }}
        </div>

        <div v-if="selectedDate && displayableTimes.length > 0 && displayableTimes.some(t => !t.isDisabled)" class="mt-4">
            <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Dostępne godziny na {{ formatDate(selectedDate) }}:
            </h4>
            <div class="grid grid-cols-3 sm:grid-cols-4 gap-2">
                <Button
                    v-for="slotItem in displayableTimes"
                    :key="slotItem.time"
                    :variant="selectedTime === slotItem.time && !slotItem.isDisabled ? 'default' : 'outline'"
                    :class="[
                        selectedTime === slotItem.time && !slotItem.isDisabled ? 'bg-nova-primary hover:bg-nova-accent dark:bg-nova-accent dark:hover:bg-nova-primary text-white dark:text-gray-900'
                            : 'dark:border-gray-600 dark:text-gray-300',
                        slotItem.isDisabled ? 'bg-gray-200 text-gray-400 dark:bg-gray-700 dark:text-gray-500 cursor-not-allowed opacity-70 hover:bg-gray-200 dark:hover:bg-gray-700' : ''
                    ]"
                    size="sm"
                    @click="selectTime(slotItem.time)"
                    :disabled="slotItem.isDisabled"
                >
                    {{ slotItem.time }}
                </Button>
            </div>
        </div>

        <div v-else-if="selectedDate && !loading && (availableTimesForSelectedDate.length === 0 || (displayableTimes.length > 0 && displayableTimes.every(t => t.isDisabled)))" class="mt-4 text-center text-sm text-gray-500 dark:text-gray-400">
            Brak dostępnych godzin w tym dniu. Wybierz inną datę.
        </div>

        <div v-else-if="!selectedDate && !loading && !errorMessage" class="mt-4 text-center text-sm text-gray-500 dark:text-gray-400">
            Wybierz datę z kalendarza, aby zobaczyć dostępne godziny.
        </div>
    </div>
</template>
