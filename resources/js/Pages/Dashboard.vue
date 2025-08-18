<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ref, computed, onMounted, nextTick } from 'vue';

import FullCalendar from '@fullcalendar/vue3';
import dayGridPlugin from '@fullcalendar/daygrid';
import interactionPlugin from '@fullcalendar/interaction';
import frLocale from '@fullcalendar/core/locales/fr';
import '@fullcalendar/common/main.css';
import primaryButton from "@/Components/PrimaryButton.vue";

const props = defineProps({
    user: Object,
    allEvents: Array,
    upcomingEvents: Array,
    pastEvents: Array,
    userCreatedEventsCount: Number,
    stats: Object,
    userParticipatedEvents: Array,
    chartData: Array,
});

// Options pour FullCalendar avec les vrais événements et amélioration visuelle
const calendarOptions = computed(() => ({
    plugins: [dayGridPlugin, interactionPlugin],
    initialView: 'dayGridMonth',
    headerToolbar: {
        left: 'prev,next today',
        center: 'title',
        right: 'dayGridMonth,dayGridWeek,dayGridDay',
    },
    selectable: true,
    editable: false,
    locale: frLocale,
    height: 'auto',
    aspectRatio: 1.35,
    dayMaxEvents: 3,
    moreLinkClick: 'popover',
    eventDisplay: 'block',
    dayGridMonth: {
        titleFormat: { year: 'numeric', month: 'long' }
    },
    dayGridWeek: {
        titleFormat: { year: 'numeric', month: 'short', day: 'numeric' }
    },
    dayGridDay: {
        titleFormat: { year: 'numeric', month: 'long', day: 'numeric' }
    },
    events: props.allEvents || [],
    eventClick: function(info) {
        if (info.event.url) {
            window.open(info.event.url, '_blank');
            info.jsEvent.preventDefault();
        }
    },
    eventMouseEnter: function(info) {
        info.el.style.cursor = 'pointer';
        info.el.style.transform = 'scale(1.02)';
        info.el.style.transition = 'all 0.2s ease';
    },
    eventMouseLeave: function(info) {
        info.el.style.transform = 'scale(1)';
    }
}));

// Formatage de la date pour l'affichage
const formatDate = (dateString) => {
    const date = new Date(dateString);
    return date.toLocaleDateString('fr-FR', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
};

// Fonction pour naviguer vers un événement
const goToEvent = (eventId) => {
    window.open(route('events.show', eventId), '_blank');
};

// État pour l'accordéon historique
const showHistory = ref(false);

// Fonction pour formater le mois en français
const formatMonth = (month) => {
    const months = [
        'janvier', 'février', 'mars', 'avril', 'mai', 'juin',
        'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'
    ];
    return months[month - 1];
};

// Fonction pour formater la prochaine date d'événement
const formatNextEventDate = (event) => {
    if (!event) return '';
    const date = new Date(event.date);
    const day = date.getDate();
    const month = formatMonth(date.getMonth() + 1);
    const time = event.hour || '';
    const location = event.location || '';
    return `${day} ${month} ${time ? 'à ' + time : ''} ${location ? 'à ' + location : ''}`;
};

// Fonction pour créer le graphique Plotly
const createChart = () => {
    if (!props.chartData || props.chartData.length === 0) return;

    const months = props.chartData.map(item => item.month);
    const counts = props.chartData.map(item => item.count);

    const data = [{
        x: months,
        y: counts,
        type: 'bar',
        marker: {
            color: 'rgba(89, 196, 180, 0.8)',
            line: {
                color: 'rgba(89, 196, 180, 1)',
                width: 2
            }
        },
        hovertemplate: '<b>%{x}</b><br>%{y} événement(s)<extra></extra>'
    }];

    const layout = {
        title: {
            text: 'Mes événements par mois',
            font: { size: 16, color: '#374151' }
        },
        xaxis: {
            title: 'Mois',
            tickangle: -45
        },
        yaxis: {
            title: 'Nombre d\'événements',
            dtick: 1
        },
        margin: { t: 50, l: 50, r: 20, b: 80 },
        plot_bgcolor: 'rgba(0,0,0,0)',
        paper_bgcolor: 'rgba(0,0,0,0)',
        font: { family: 'Inter, sans-serif' }
    };

    const config = {
        responsive: true,
        displayModeBar: false
    };

    window.Plotly.newPlot('eventsChart', data, layout, config);
};

// Monter le graphique après le rendu du composant
onMounted(() => {
    nextTick(() => {
        createChart();
    });
});
</script>

<template>
    <Head title="Mon tableau de bord" />
    <AuthenticatedLayout>
        <div class="py-4 bg-[#f9f5f2] min-h-screen">
            <div class="mx-auto w-full px-3 sm:px-5">
                <div class="bg-white shadow-lg rounded-lg p-4 sm:p-6">
                    <h3 class="text-2xl sm:text-3xl font-bold text-gray-800 mb-4 sm:mb-6 text-center">Mon tableau de bord</h3>

                    <!-- Bandeau récapitulatif -->
                    <div class="bg-transparent border-2 border-[#ffb347] rounded-lg p-4 mb-6" style="background-color: rgba(255, 179, 71, 0.05);">
                        <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                            <div class="flex items-center gap-3">
                                <div class="bg-[#ffb347] text-white rounded-full p-2">
                                    <i class="fa-solid fa-chart-line text-lg"></i>
                                </div>
                                <div>
                                    <h2 class="text-lg font-bold text-[#cc7a00]">Récapitulatif</h2>
                                    <p class="text-sm text-[#b36900]">Votre activité en un coup d'œil</p>
                                </div>
                            </div>
                            <div class="text-center">
                                <div class="text-2xl font-bold text-[#cc7a00]">{{ stats.totalUpcoming }}</div>
                                <div class="text-sm text-[#b36900] font-semibold">événement(s) à venir</div>
                            </div>
                            <div class="text-center">
                                <div class="text-lg font-bold text-[#cc7a00]">
                                    {{ stats.nextEvent ? 'Prochain événement' : 'Aucun événement' }}
                                </div>
                                <div class="text-sm text-[#b36900] font-semibold">
                                    {{ formatNextEventDate(stats.nextEvent) }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Layout responsive : vertical sur mobile, horizontal sur desktop -->
                    <div class="flex flex-col xl:flex-row gap-4 sm:gap-6">
                        <!-- Calendrier agrandi -->
                        <div class="xl:flex-1 bg-[#59c4b4]/10 p-4 rounded-lg calendar-container" style="min-height: 600px;">
                            <h1 class="text-lg sm:text-xl font-bold mb-4 text-center"><i class="fa-solid fa-calendar-days"></i> <br>Calendrier des événements</h1>
                            <div class="bg-white rounded-lg p-3 shadow-sm" style="overflow: hidden;">
                                <FullCalendar :options="calendarOptions" />
                            </div>
                        </div>

                        <!-- Section événements - Taille harmonisée -->
                        <div class="xl:flex-1 bg-[#59c4b4]/10 p-4 rounded-lg" style="min-height: 600px;">
                            <h1 class="text-lg sm:text-xl font-bold mb-4 text-center"><i class="fa-solid fa-calendar-alt"></i> <br>Section événements</h1>

                            <div class="bg-gray-100 p-4 rounded-lg mb-4" style="min-height: 120px;">
                                <div class="flex items-center justify-between mb-4">
                                    <h1 class="text-lg sm:text-xl font-bold text-center w-full">Créer un événement</h1>
                                </div>
                                <div class="flex flex-col justify-center items-center h-full">
                                    <p class="text-gray-600 text-sm mb-4 text-center">Organisez votre prochain événement</p>
                                    <Link :href="route('events.create')">
                                        <primary-button class="bg-gradient-to-r from-[#59c4b4] to-[#4db3a3] hover:from-[#4db3a3] hover:to-[#42a392] transform hover:scale-105 transition-all duration-200 shadow-lg px-4 py-2 text-sm">
                                            <i class="fa-solid fa-plus mr-2"></i>
                                            Créer un événement
                                        </primary-button>
                                    </Link>
                                </div>
                            </div>

                            <div class="bg-gray-100 p-4 rounded-lg mb-4" style="min-height: 120px;">
                                <h1 class="text-lg sm:text-xl font-bold mb-4 text-center">Vos événements créés</h1>
                                <div class="flex items-center justify-center h-full">
                                    <div class="text-center">
                                        <div class="bg-[#59c4b4] text-white rounded-full w-16 h-16 flex items-center justify-center text-2xl font-bold mb-2 mx-auto">
                                            {{ userCreatedEventsCount }}
                                        </div>
                                        <p class="text-gray-600 text-sm">événement(s) créé(s)</p>
                                    </div>
                                </div>
                            </div>

                            <div id="eventsChart" class="bg-gray-100 p-4 rounded-lg" style="min-height: 320px;"></div>
                        </div>

                        <!-- Mes événements auxquels je participe - Taille harmonisée -->
                        <div class="xl:flex-1 bg-[#59c4b4]/10 p-4 rounded-lg" style="min-height: 600px;">
                            <h1 class="text-lg sm:text-xl font-bold mb-4 text-center">
                                <i class="fa-solid fa-calendar-check"></i> <br>
                                Mes événements auxquels je participe
                            </h1>

                            <!-- Section À venir -->
                            <div class="mb-6">
                                <div class="flex items-center gap-2 mb-3">
                                    <div class="bg-[#59c4b4] text-white rounded-full px-3 py-1 text-xs font-semibold">
                                        À VENIR
                                    </div>
                                    <span class="text-sm text-gray-600">({{ stats.totalUpcoming }})</span>
                                </div>

                                <div class="space-y-3 overflow-y-auto" style="max-height: 300px;">
                                    <div v-if="upcomingEvents.length === 0" class="text-gray-600 text-center py-4">
                                        <i class="fa-solid fa-calendar-xmark text-2xl text-gray-400 mb-2"></i>
                                        <p class="text-sm">Aucun événement à venir</p>
                                    </div>

                                    <div v-else v-for="event in upcomingEvents" :key="'upcoming-' + event.id"
                                         @click="goToEvent(event.id)"
                                         class="bg-white border border-[#59c4b4]/30 rounded-lg p-3 cursor-pointer hover:shadow-md transition-all duration-200 hover:border-[#59c4b4] transform hover:scale-[1.02]">
                                        <div class="flex items-start justify-between">
                                            <div class="flex-1">

                                                <h3 class="font-semibold mb-1 text-sm text-gray-800">{{ event.name_event }}</h3>

                                                <div class="text-xs text-gray-600 space-y-1">
                                                    <div class="flex items-center">
                                                        <i class="fa-solid fa-calendar text-[#59c4b4] mr-2"></i>
                                                        {{ formatDate(event.date) }}
                                                    </div>
                                                    <div v-if="event.hour" class="flex items-center">
                                                        <i class="fa-solid fa-clock text-[#59c4b4] mr-2"></i>
                                                        {{ event.hour }}
                                                    </div>
                                                    <div v-if="event.location" class="flex items-center">
                                                        <i class="fa-solid fa-map-marker-alt text-[#59c4b4] mr-2"></i>
                                                        {{ event.location }}
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="bg-[#59c4b4]/20 text-[#4db3a3] rounded-full px-2 py-1 text-xs font-semibold ml-2">
                                                À venir
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Section Historique (accordéon) -->
                            <div>
                                <button @click="showHistory = !showHistory"
                                        class="w-full flex items-center justify-between bg-gray-200 hover:bg-gray-300 rounded-lg p-3 transition-colors duration-200">
                                    <div class="flex items-center gap-2">
                                        <div class="bg-gray-500 text-white rounded-full px-3 py-1 text-xs font-semibold">
                                            HISTORIQUE
                                        </div>
                                        <span class="text-sm text-gray-600">({{ stats.totalPast }})</span>
                                    </div>
                                    <i :class="['fa-solid', showHistory ? 'fa-chevron-up' : 'fa-chevron-down', 'text-gray-600']"></i>
                                </button>

                                <div v-show="showHistory" class="mt-3 space-y-3 overflow-y-auto" style="max-height: 200px;">
                                    <div v-if="pastEvents.length === 0" class="text-gray-600 text-center py-4">
                                        <i class="fa-solid fa-history text-2xl text-gray-400 mb-2"></i>
                                        <p class="text-sm">Aucun événement passé</p>
                                    </div>

                                    <div v-else v-for="event in pastEvents" :key="'past-' + event.id"
                                         @click="goToEvent(event.id)"
                                         class="bg-white border border-gray-200 rounded-lg p-3 cursor-pointer hover:shadow-md transition-all duration-200 hover:border-gray-300 transform hover:scale-[1.02] opacity-75">
                                        <div class="flex items-start justify-between">
                                            <div class="flex-1">
                                                <h3 class="font-semibold mb-1 text-sm text-gray-600">{{ event.name_event }}</h3>
                                                <div class="text-xs text-gray-500 space-y-1">
                                                    <div class="flex items-center">
                                                        <i class="fa-solid fa-calendar text-gray-500 mr-2"></i>
                                                        {{ formatDate(event.date) }}
                                                    </div>
                                                    <div v-if="event.hour" class="flex items-center">
                                                        <i class="fa-solid fa-clock text-gray-500 mr-2"></i>
                                                        {{ event.hour }}
                                                    </div>
                                                    <div v-if="event.location" class="flex items-center">
                                                        <i class="fa-solid fa-map-marker-alt text-gray-500 mr-2"></i>
                                                        {{ event.location }}
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="bg-gray-100 text-gray-600 rounded-full px-2 py-1 text-xs font-semibold ml-2">
                                                Passé
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Aide rapide / explications du tableau de bord -->
                <div class="mt-6 bg-gray-50 border border-gray-200 rounded-lg p-4 text-sm text-gray-700 leading-relaxed">
                    <h3 class="font-semibold text-gray-800 mb-2">
                        <i class="fa-solid fa-circle-info mr-1 text-[#59c4b4]"></i>
                        Aide rapide
                    </h3>
                    <ul class="list-disc pl-5 space-y-1">
                        <li><span class="font-medium">Calendrier :</span> utilisez <em>Préc./Suiv.</em> et les vues <em>Mois/Semaine/Jour</em> pour naviguer.</li>
                        <li><span class="font-medium">Événements :</span> cliquez sur un événement du calendrier pour ouvrir sa page (nouvel onglet).</li>
                        <li><span class="font-medium">Créer :</span> le bouton <em>“Créer un événement”</em> vous permet d’en proposer un nouveau.</li>
                        <li><span class="font-medium">Mes participations :</span> cliquez sur une carte “À venir” ou “Historique” pour voir le détail.</li>
                        <li><span class="font-medium">Graphique :</span> affiche le nombre d’événements auxquels vous participez sur les <strong>6 prochains mois</strong> (survolez une barre pour voir le détail).</li>
                    </ul>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style>
/* === Calendrier amélioré === */
.calendar-container .fc {
    font-family: 'Inter', sans-serif;
}

/* Conteneur du calendrier pour éviter le débordement */
.calendar-container .fc-scrollgrid-sync-table {
    width: 100% !important;
}

.calendar-container .fc-view-harness {
    overflow: hidden !important;
}

/* Boutons de navigation avec dégradé vert personnalisé */
.fc-button {
    background: linear-gradient(135deg, #59c4b4 0%, #4db3a3 100%) !important;
    border: none !important;
    color: white !important;
    border-radius: 8px !important;
    padding: 6px 10px !important;
    font-size: 11px !important;
    font-weight: 600 !important;
    transition: all 0.3s ease !important;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1) !important;
}

.fc-button:hover {
    transform: translateY(-1px) !important;
    box-shadow: 0 4px 8px rgba(0,0,0,0.2) !important;
    background: linear-gradient(135deg, #4db3a3 0%, #42a392 100%) !important;
}

.fc-button:disabled {
    opacity: 0.6 !important;
    transform: none !important;
}

/* Titre du calendrier */
.fc-toolbar-title {
    font-size: 16px !important;
    font-weight: 700 !important;
    color: #4a5568 !important;
    margin: 0 !important;
}

/* Cases des jours */
.fc-daygrid-day {
    background-color: #fafafa !important;
    border: 1px solid #e2e8f0 !important;
    transition: background-color 0.2s ease !important;
}

.fc-daygrid-day:hover {
    background-color: #f7fafc !important;
}

.fc-day-today {
    background-color: rgba(89, 196, 180, 0.1) !important;
    border-color: #59c4b4 !important;
}

/* Numéros des jours */
.fc-daygrid-day-number {
    color: #4a5568 !important;
    font-weight: 600 !important;
    padding: 4px !important;
}

.fc-day-today .fc-daygrid-day-number {
    color: #59c4b4 !important;
    font-weight: 700 !important;
}

/* Événements dans le calendrier avec dégradé orange personnalisé */
.fc-event {
    background: linear-gradient(135deg, #ffb347 0%, #ff9500 100%) !important;
    border: none !important;
    border-radius: 6px !important;
    color: white !important;
    font-size: 10px !important;
    font-weight: 500 !important;
    padding: 2px 6px !important;
    margin: 1px 2px !important;
    box-shadow: 0 1px 3px rgba(0,0,0,0.2) !important;
    transition: all 0.2s ease !important;
    cursor: pointer !important;
}

.fc-event:hover {
    transform: scale(1.02) !important;
    box-shadow: 0 2px 6px rgba(0,0,0,0.3) !important;
    background: linear-gradient(135deg, #ff9500 0%, #e6850e 100%) !important;
}

.fc-event-title {
    font-weight: 600 !important;
}

/* Lien "more" pour les événements supplémentaires */
.fc-more-link {
    color: #ffb347 !important;
    font-weight: 600 !important;
    font-size: 10px !important;
}

/* Bordures du calendrier */
.fc-scrollgrid {
    border: 1px solid #e2e8f0 !important;
    border-radius: 8px !important;
    overflow: hidden !important;
}

/* En-têtes des jours de la semaine */
.fc-col-header-cell {
    background-color: #f7fafc !important;
    border-color: #e2e8f0 !important;
    font-weight: 600 !important;
    color: #4a5568 !important;
    padding: 6px 4px !important;
}

/* Responsive pour mobile */
@media (max-width: 640px) {
    .fc-button {
        padding: 4px 6px !important;
        font-size: 9px !important;
    }

    .fc-toolbar-title {
        font-size: 14px !important;
    }

    .fc-event {
        font-size: 9px !important;
        padding: 1px 4px !important;
    }
}

/* Responsive pour tablette */
@media (max-width: 1024px) {
    .fc-button {
        padding: 5px 8px !important;
        font-size: 10px !important;
    }

    .fc-toolbar-title {
        font-size: 15px !important;
    }
}

/* Animation pour les cartes d'événements */
.transition-all {
    transition: all 0.2s ease-in-out;
}

/* Scrollbar personnalisée pour la section événements */
.overflow-y-auto::-webkit-scrollbar {
    width: 6px;
}

.overflow-y-auto::-webkit-scrollbar-track {
    background: #f1f5f9;
    border-radius: 3px;
}

.overflow-y-auto::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 3px;
}

.overflow-y-auto::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}
</style>
