<script setup>
import { ref } from 'vue';
import FullCalendar from '@fullcalendar/vue3';
import dayGridPlugin from '@fullcalendar/daygrid';
import interactionPlugin from '@fullcalendar/interaction';
import timeGridPlugin from '@fullcalendar/timegrid';
import frLocale from '@fullcalendar/core/locales/fr';

// Configuration du calendrier
const calendarOptions = ref({
    plugins: [dayGridPlugin, interactionPlugin, timeGridPlugin],
    initialView: 'dayGridMonth', // Vue initiale
    locales: [frLocale],
    locale: 'fr',
    headerToolbar: {
        left: 'prev,next today',
        center: 'title',
        right: 'dayGridMonth,timeGridWeek,timeGridDay',
    },
    events: [
        { title: 'Événement 1', date: '2025-01-15' },
        { title: 'Événement 2', date: '2025-01-18' },
    ],
});
</script>

<template>
    <div>
        <FullCalendar v-bind="calendarOptions" />
    </div>
</template>

<style scoped>
/* Ajoute des styles si nécessaire */
</style>
