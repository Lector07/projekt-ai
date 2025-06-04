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
    slotMinTime: '08:00:00',
    slotMaxTime: '20:00:00',
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

<style>
.fc {
    --fc-border-color: var(--border-color, #e5e7eb);
    --fc-page-bg-color: transparent;
    --fc-neutral-bg-color: rgba(var(--card-foreground-rgb), 0.02);
    --fc-today-bg-color: rgba(var(--primary-rgb), 0.08);
    --fc-now-indicator-color: var(--nova-primary, #3b82f6);
    font-family: inherit;
}

.dark .fc {
    --fc-border-color: rgba(255, 255, 255, 0.1);
    --fc-neutral-bg-color: rgba(255, 255, 255, 0.03);
    --fc-today-bg-color: rgba(var(--nova-accent-rgb), 0.1);
    color-scheme: dark;
}

.fc .fc-toolbar {
    flex-wrap: wrap;
    gap: 0.75rem;
    margin-bottom: 1.25rem !important;
}

.fc .fc-toolbar-title {
    font-size: 1.25rem;
    font-weight: 600;
}

.fc .fc-button-primary {
    background-color: var(--nova-primary, #3b82f6);
    border-color: var(--nova-primary, #3b82f6);
    font-weight: 500;
    text-transform: capitalize;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
    border-radius: 0.375rem;
}

.fc .fc-button-primary:hover {
    background-color: var(--nova-accent, #2563eb);
    border-color: var(--nova-accent, #2563eb);
}

.fc .fc-button-primary:not(:disabled):active,
.fc .fc-button-primary:not(:disabled).fc-button-active {
    background-color: var(--nova-accent, #2563eb);
    border-color: var(--nova-accent, #2563eb);
}

.fc .fc-button-primary:disabled {
    opacity: 0.6;
}

.dark .fc .fc-button-primary {
    background-color: var(--nova-accent, #2563eb);
    border-color: var(--nova-accent, #2563eb);
}

.dark .fc .fc-button-primary:hover,
.dark .fc .fc-button-primary:not(:disabled):active,
.dark .fc .fc-button-primary:not(:disabled).fc-button-active {
    background-color: var(--nova-primary, #3b82f6);
    border-color: var(--nova-primary, #3b82f6);
}

.fc th {
    padding: 0.75rem 0.5rem;
    font-weight: 600;
    text-transform: uppercase;
    font-size: 0.75rem;
    letter-spacing: 0.05em;
}

.dark .fc th {
    color: rgba(255, 255, 255, 0.8);
}

.fc td {
    border-color: var(--fc-border-color);
}

.fc .fc-day {
    transition: background-color 0.2s;
}

.fc .fc-day:hover {
    background-color: var(--fc-neutral-bg-color);
}

.fc .fc-daygrid-day-number,
.fc .fc-col-header-cell-cushion {
    padding: 0.5rem;
    color: inherit;
    text-decoration: none;
}

.fc .fc-event {
    border-radius: 4px;
    border-width: 0;
    padding: 2px 4px;
    font-size: 0.85rem;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
    cursor: pointer;
    transition: transform 0.15s ease, box-shadow 0.15s ease;
}

.fc .fc-event:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.fc .fc-timegrid-now-indicator-line {
    border-width: 2px;
}

.fc .fc-timegrid-now-indicator-arrow {
    border-width: 5px;
}

.fc .fc-list {
    border-radius: 0.5rem;
    overflow: hidden;
}

.fc .fc-list-day-cushion {
    background-color: var(--fc-neutral-bg-color);
}

.fc .fc-list-event:hover td {
    background-color: var(--fc-neutral-bg-color);
}

@media (max-width: 768px) {
    .fc .fc-toolbar {
        flex-direction: column;
        align-items: flex-start;
    }

    .fc .fc-toolbar-chunk {
        margin-bottom: 0.5rem;
        display: flex;
        flex-wrap: wrap;
        gap: 0.25rem;
    }
}
</style>
