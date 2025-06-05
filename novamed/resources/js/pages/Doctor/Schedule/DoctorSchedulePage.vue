<script setup lang="ts">
import { ref, onMounted } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import FullCalendar from '@fullcalendar/vue3';
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';
import interactionPlugin from '@fullcalendar/interaction';
import listPlugin from '@fullcalendar/list';
import plLocale from '@fullcalendar/core/locales/pl';
import type { EventInput, EventClickArg, CalendarOptions } from '@fullcalendar/core';
import axios from 'axios';
import { useRouter } from 'vue-router';
import { Card, CardContent } from '@/components/ui/card';
import Icon from '@/components/Icon.vue';

interface BreadcrumbItem {
    title: string;
    href?: string;
}

const router = useRouter();
const calendarRef = ref<InstanceType<typeof FullCalendar> | null>(null);
const loadingEvents = ref(true);

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Panel Lekarza', href: '/doctor/dashboard' },
    { title: 'Mój Grafik' },
];

const calendarOptions: CalendarOptions = {
    plugins: [dayGridPlugin, timeGridPlugin, interactionPlugin, listPlugin],
    initialView: 'timeGridWeek',
    locale: plLocale,
    headerToolbar: {
        left: 'prev,next today',
        center: 'title',
        right: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek'
    },
    slotMinTime: '09:00:00',
    slotMaxTime: '18:00:00',
    allDaySlot: false,
    nowIndicator: true,
    editable: false,
    selectable: false,
    events: fetchCalendarEvents,
    eventClick: handleEventClick,
    height: 'auto',
    loading: (isLoading: boolean) => {
        loadingEvents.value = isLoading;
    },
    eventTimeFormat: {
        hour: '2-digit',
        minute: '2-digit',
        meridiem: false,
        hour12: false
    },
    displayEventEnd: true,
};

async function fetchCalendarEvents(fetchInfo: { start: Date, end: Date, startStr: string, endStr: string, timeZone: string },
                                   successCallback: (events: EventInput[]) => void,
                                   failureCallback: (error: any) => void) {
    try {
        const response = await axios.get('/api/v1/doctor/schedule/events', {
            params: {
                start: fetchInfo.startStr,
                end: fetchInfo.endStr,
            }
        });

        const calendarEvents = (response.data.data || []).map((eventData: any) => ({
            id: eventData.id,
            title: eventData.title,
            start: eventData.start,
            end: eventData.end,
            allDay: eventData.allDay,
            backgroundColor: eventData.backgroundColor,
            borderColor: eventData.borderColor,
            extendedProps: eventData.extendedProps
        }));
        successCallback(calendarEvents);
    } catch (error) {
        console.error('Błąd podczas pobierania wizyt dla kalendarza:', error);
        failureCallback(error);
    }
}

function handleEventClick(clickInfo: EventClickArg) {
    if (clickInfo.event.extendedProps && clickInfo.event.extendedProps.appointment_actual_id) {
        router.push({ name: 'doctor.appointments.show', params: { id: clickInfo.event.extendedProps.appointment_actual_id } });
    } else if (clickInfo.event.id) {
        router.push({ name: 'doctor.appointments.show', params: { id: clickInfo.event.id } });
    }
}

onMounted(() => {
});
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="container mx-auto py-6 px-4 sm:px-6 lg:px-8">
            <div class="mb-6">
                <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100">Mój Grafik Wizyt</h1>
            </div>

            <Card class="dark:bg-gray-800 border dark:border-gray-700 shadow-md">
                <CardContent class="p-4 md:p-6 relative">
                    <div v-if="loadingEvents" class="absolute inset-0 bg-white/70 dark:bg-gray-800/70 flex items-center justify-center z-20">
                        <Icon name="loader-2" class="h-12 w-12 animate-spin text-nova-primary dark:text-nova-accent" />
                    </div>
                    <FullCalendar ref="calendarRef" :options="calendarOptions" />
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>

