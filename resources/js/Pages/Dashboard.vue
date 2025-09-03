<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import {Head, Link, router, usePage} from '@inertiajs/vue3';
import {ref, computed, onMounted, nextTick} from 'vue';

import FullCalendar from '@fullcalendar/vue3';
import dayGridPlugin from '@fullcalendar/daygrid';
import interactionPlugin from '@fullcalendar/interaction';
import frLocale from '@fullcalendar/core/locales/fr';
import '@fullcalendar/common/main.css';
//import '@fullcalendar/core/index.css';
//import '@fullcalendar/daygrid/index.css';
import PrimaryButton from "@/Components/PrimaryButton.vue";
import Flash from "@/Components/Flash.vue";

const props = defineProps({
    user: Object,
    allEvents: {type: Array, default: () => []},
    upcomingEvents: {type: Array, default: () => []},
    pastEvents: {type: Array, default: () => []},

    userCreatedEventsCount: {type: Number, default: 0},

    // === EXISTANT (toujours utilisé pour la jauge) ===
    createdActiveCount: {type: Number, default: 0},

    // === OBSOLETTE côté affichage : on ne s’en sert plus pour « Passés »
    createdInactiveCount: {type: Number, default: 0},
    inactiveCreatedEvents: {type: Array, default: () => []},

    // === NOUVEAU (optionnel)
    pastCreatedEvents: {type: Array, default: () => []},
    createdPastCount: {type: Number, default: null},

    maxActiveSlots: {type: Number, default: 10},

    stats: {type: Object, default: () => ({totalUpcoming: 0, totalPast: 0, nextEvent: null})},
    userParticipatedEvents: {type: Array, default: () => []},
    chartData: {type: Array, default: () => []},
    isStaff: {type: Boolean, default: false},
    blockedUsers: {type: Array, default: () => []},
});

/* =======================
   Helpers "date/heure"
   ======================= */

// Normalise une date+heure en objet Date (UTC)
const toJsDate = (d, h) => {
    if (!d) return null;
    try {
        const day = typeof d === 'string' ? d : new Date(d).toISOString().slice(0, 10);
        let time = (h || '00:00').toString();
        // autorise HH:mm ou HH:mm:ss
        if (/^\d{2}:\d{2}$/.test(time)) time = `${time}:00`;
        // Ajout du Z pour forcer UTC
        const isoish = `${day}T${time}Z`;
        const jsd = new Date(isoish);
        return isNaN(jsd.getTime()) ? null : jsd;
    } catch (e) {
        return null;
    }
};

// Dit si un event est déjà passé (comparé "maintenant" – epoch UTC)
const isPast = (ev) => {
    const jsd = toJsDate(ev?.date, ev?.hour);
    if (!jsd) return true; // sécurité : si invalide, on considère passé
    return jsd.getTime() < Date.now();
};

/* =======================
   FullCalendar options
   ======================= */
const calendarOptions = computed(() => ({
    plugins: [dayGridPlugin, interactionPlugin],
    initialView: 'dayGridMonth',
    headerToolbar: {
        left: 'prev,next today',
        center: 'title',
        right: 'dayGridMonth,dayGridWeek,dayGridDay',
    },
    timeZone: 'UTC', // <-- important pour rester en phase avec le back
    selectable: true,
    editable: false,
    locale: frLocale,
    height: 'auto',
    aspectRatio: 1.35,
    dayMaxEvents: 3,
    moreLinkClick: 'popover',
    eventDisplay: 'block',
    dayGridMonth: {
        titleFormat: {year: 'numeric', month: 'long'}
    },
    dayGridWeek: {
        titleFormat: {year: 'numeric', month: 'short', day: 'numeric'}
    },
    dayGridDay: {
        titleFormat: {year: 'numeric', month: 'long', day: 'numeric'}
    },
    events: props.allEvents || [],
    eventClick: function (info) {
        if (info.event.url) {
            window.open(info.event.url, '_blank');
            info.jsEvent.preventDefault();
        }
    },
    eventMouseEnter: function (info) {
        info.el.style.cursor = 'pointer';
        info.el.style.transform = 'scale(1.02)';
        info.el.style.transition = 'all 0.2s ease';
    },
    eventMouseLeave: function (info) {
        info.el.style.transform = 'scale(1)';
    }
}));

// Formatage de la date pour l'affichage (UTC)
const formatDate = (dateString) => {
    const date = new Date(`${dateString}T00:00:00Z`);
    return date.toLocaleDateString('fr-FR', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        timeZone: 'UTC'
    });
};

// Fonction pour naviguer vers un événement
const goToEvent = (eventId) => {
    window.open(route('events.show', {event: eventId}), '_blank');
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

// Fonction pour formater la prochaine date d'événement (UTC)
const formatNextEventDate = (event) => {
    if (!event) return '';
    const d = new Date(`${event.date}T00:00:00Z`);
    const day = d.getUTCDate();
    const month = formatMonth(d.getUTCMonth() + 1);
    const time = (event.hour || '').toString(); // déjà HH:mm(:ss) en UTC
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
            font: {size: 16, color: '#374151'}
        },
        xaxis: {
            title: 'Mois',
            tickangle: -45
        },
        yaxis: {
            title: 'Nombre d\'événements',
            dtick: 1
        },
        margin: {t: 50, l: 50, r: 20, b: 80},
        plot_bgcolor: 'rgba(0,0,0,0)',
        paper_bgcolor: 'rgba(0,0,0,0)',
        font: {family: 'Inter, sans-serif'}
    };

    const config = {
        responsive: true,
        displayModeBar: false
    };

    if (window && window.Plotly) {
        window.Plotly.newPlot('eventsChart', data, layout, config)
    }
};

// Monter le graphique après le rendu du composant
onMounted(() => {
    nextTick(() => {
        createChart();
    });
});

const page = usePage();

// -------------------- Rôles / Staff --------------------
const userRoles = computed(() => page.props.auth?.user?.roles ?? props.user?.roles ?? [])
const hasRole = (name) => (userRoles.value || []).some((r) => (r?.name ?? r) === name)
const isStaff = computed(() => props.isStaff || hasRole('Admin') || hasRole('Super-admin'))

// -------------------- Bloqués --------------------
const blocked = computed(() => props.blockedUsers ?? [])
const defaultAvatar = '/images/default-avatar.png'
const unblock = (userId) => {
    router.delete(route('users.unblock', userId), {
        preserveScroll: true,
    })
}

/* =======================
   Limite actifs futurs
   ======================= */
const remainingActiveSlots = computed(() => {
    const left = props.maxActiveSlots - props.createdActiveCount
    return left < 0 ? 0 : left
})
const atLimit = computed(() => props.createdActiveCount >= props.maxActiveSlots)

/* ==========================================================
   Événements CRÉÉS : colonne « Passés »
   ========================================================== */
const derivedPastCreatedEvents = computed(() => {
    if (props.pastCreatedEvents?.length) return props.pastCreatedEvents
    const meId = props.user?.id
    if (!meId) return []
    return (props.allEvents || []).filter(e => (e?.created_by === meId) && isPast(e))
})
const createdPastCountSafe = computed(() => {
    if (typeof props.createdPastCount === 'number' && props.createdPastCount >= 0) {
        return props.createdPastCount
    }
    return derivedPastCreatedEvents.value.length
})

/* =======================
   Contact support
   ======================= */
const contactForm = ref({
    role: 'Admin', // 'Admin' | 'Super-admin'
    subject: '',
    message: '',
})
const sending = ref(false)
const errors = ref({})
const notice = ref(null)

const submitContact = () => {
    errors.value = {}
    notice.value = null
    sending.value = true

    router.post(route('users.sendAdminMessage'), contactForm.value, {
        preserveScroll: true,
        onError: (e) => {
            errors.value = e || {}
            sending.value = false
        },
        onSuccess: () => {
            sending.value = false
            notice.value = "Votre message a bien été envoyé."
            contactForm.value = {role: 'Admin', subject: '', message: ''}
            setTimeout(() => (notice.value = null), 4000)
        },
    })
}
</script>

<template>
    <Head title="Mon tableau de bord"/>
    <AuthenticatedLayout>
        <div class="py-4 bg-[#f9f5f2] ">
            <div class="mx-auto w-full px-3 sm:px-5">
                <div class="bg-white shadow-lg rounded-lg p-4 sm:p-6">
                    <h3 class="text-2xl sm:text-3xl font-bold text-gray-800 mb-4 sm:mb-6 text-center">Mon tableau de
                        bord</h3>

                    <!-- Bandeau récapitulatif -->
                    <div class="bg-transparent border-2 border-[#ffb347] rounded-lg p-4 mb-6"
                         style="background-color: rgba(255, 179, 71, 0.05);">
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
                        <!-- Calendrier -->
                        <div class="xl:flex-1 bg-[#59c4b4]/10 p-4 rounded-lg calendar-container"
                             style="min-height: 600px;">
                            <h1 class="text-lg sm:text-xl font-bold mb-4 text-center"><i
                                class="fa-solid fa-calendar-days"></i> <br>Calendrier des événements</h1>
                            <div class="bg-white rounded-lg p-3 shadow-sm" style="overflow: hidden;">
                                <FullCalendar :options="calendarOptions"/>
                            </div>
                        </div>

                        <!-- Section événements -->
                        <div class="xl:flex-1 bg-[#59c4b4]/10 p-4 rounded-lg" style="min-height: 600px;">
                            <h1 class="text-lg sm:text-xl font-bold mb-4 text-center"><i
                                class="fa-solid fa-calendar-alt"></i> <br>Section événements</h1>

                            <div class="bg-gray-100 p-4 rounded-lg mb-4" style="min-height: 120px;">
                                <div class="flex items-center justify-between mb-4">
                                    <h1 class="text-lg sm:text-xl font-bold text-center w-full">Créer un événement</h1>
                                </div>
                                <div class="flex flex-col justify-center items-center h-full">
                                    <p class="text-gray-600 text-sm mb-4 text-center">
                                        Organisez votre prochain événement
                                    </p>

                                    <!-- Bouton désactivé si limite atteinte -->
                                    <Link :href="atLimit ? '#' : route('events.create')">
                                        <PrimaryButton
                                            :disabled="atLimit"
                                            class="bg-gradient-to-r from-[#59c4b4] to-[#4db3a3] hover:from-[#4db3a3] hover:to-[#42a392]
                             transform hover:scale-105 transition-all duration-200 shadow-lg px-4 py-2 text-sm
                             disabled:opacity-60 disabled:cursor-not-allowed">
                                            <i class="fa-solid fa-plus mr-2"></i>
                                            {{ atLimit ? 'Limite atteinte' : 'Créer un événement' }}
                                        </PrimaryButton>
                                    </Link>

                                    <p v-if="atLimit" class="mt-2 text-xs text-red-600">
                                        Vous avez atteint la limite de {{ maxActiveSlots }} événements <strong>futurs &
                                        actifs</strong> créés.
                                    </p>
                                </div>
                            </div>

                            <div class="bg-gray-100 p-4 rounded-lg mb-4" style="min-height: 120px;">
                                <h1 class="text-lg sm:text-xl font-bold mb-4 text-center">Vos événements créés</h1>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <!-- Colonne Actifs (FUTURS & ACTIFS uniquement) -->
                                    <div class="bg-white rounded-lg p-4 border border-[#59c4b4]/30">
                                        <div class="flex items-center justify-between mb-2">
                                            <div class="font-semibold text-gray-800">Actifs</div>
                                            <div class="text-sm text-gray-500">{{ createdActiveCount }} /
                                                {{ maxActiveSlots }}
                                            </div>
                                        </div>

                                        <!-- Compteur + barre -->
                                        <div class="flex items-end gap-4">
                                            <div
                                                class="bg-[#59c4b4] text-white rounded-full w-16 h-16 flex items-center justify-center text-2xl font-bold">
                                                {{ createdActiveCount }}
                                            </div>
                                            <div class="flex-1">
                                                <div class="h-2 rounded bg-gray-200 overflow-hidden">
                                                    <div
                                                        class="h-2 rounded bg-[#59c4b4]"
                                                        :style="{ width: Math.min(100, Math.round((createdActiveCount / maxActiveSlots) * 100)) + '%' }"
                                                    />
                                                </div>
                                                <div class="text-xs text-gray-500 mt-1">
                                                    {{ remainingActiveSlots }} place(s) restante(s)
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Colonne Passés -->
                                    <div class="bg-white rounded-lg p-4 border border-gray-200">
                                        <div class="flex items-center justify-between mb-2">
                                            <div class="font-semibold text-gray-800">Passés</div>
                                            <div class="text-sm text-gray-500">{{ createdPastCountSafe }}</div>
                                        </div>

                                        <div v-if="createdPastCountSafe > 0" class="space-y-2 max-h-40 overflow-y-auto">
                                            <div
                                                v-for="e in derivedPastCreatedEvents"
                                                :key="'created-past-' + e.id"
                                                class="flex items-center justify-between text-sm bg-gray-50 rounded-md px-2 py-1 border"
                                            >
                                                <div class="truncate">
                                                    <span class="font-medium text-gray-700">{{
                                                            e.name_event || e.title
                                                        }}</span>
                                                    <span class="text-gray-500"> — {{ formatDate(e.date) }}</span>
                                                </div>
                                                <button
                                                    class="ml-3 text-xs px-2 py-1 rounded bg-gray-100 text-gray-600 cursor-default"
                                                    title="Événement passé"
                                                >
                                                    Passé
                                                </button>
                                            </div>
                                        </div>
                                        <div v-else class="text-sm text-gray-500">Aucun événement passé.</div>
                                    </div>
                                </div>
                            </div>

                            <div id="eventsChart" class="bg-gray-100 p-4 rounded-lg" style="min-height: 320px;"></div>
                        </div>

                        <!-- Mes événements auxquels je participe -->
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
                                                <h3 class="font-semibold mb-1 text-sm text-gray-800">{{
                                                        event.name_event
                                                    }}</h3>
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
                                            <div
                                                class="bg-[#59c4b4]/20 text-[#4db3a3] rounded-full px-2 py-1 text-xs font-semibold ml-2">
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
                                        <div
                                            class="bg-gray-500 text-white rounded-full px-3 py-1 text-xs font-semibold">
                                            HISTORIQUE
                                        </div>
                                        <span class="text-sm text-gray-600">({{ stats.totalPast }})</span>
                                    </div>
                                    <i :class="['fa-solid', showHistory ? 'fa-chevron-up' : 'fa-chevron-down', 'text-gray-600']"></i>
                                </button>

                                <div v-show="showHistory" class="mt-3 space-y-3 overflow-y-auto"
                                     style="max-height: 200px;">
                                    <div v-if="pastEvents.length === 0" class="text-gray-600 text-center py-4">
                                        <i class="fa-solid fa-history text-2xl text-gray-400 mb-2"></i>
                                        <p class="text-sm">Aucun événement passé</p>
                                    </div>

                                    <div v-else v-for="event in pastEvents" :key="'past-' + event.id"
                                         @click="goToEvent(event.id)"
                                         class="bg-white border border-gray-200 rounded-lg p-3 cursor-pointer hover:shadow-md transition-all duration-200 hover:border-gray-300 transform hover:scale-[1.02] opacity-75">
                                        <div class="flex items-start justify-between">
                                            <div class="flex-1">
                                                <h3 class="font-semibold mb-1 text-sm text-gray-600">{{
                                                        event.name_event
                                                    }}</h3>
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
                                            <div
                                                class="bg-gray-100 text-gray-600 rounded-full px-2 py-1 text-xs font-semibold ml-2">
                                                Passé
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div> <!-- /layout -->
                </div>
            </div>
        </div>

        <div class="mt-6">
            <div class="bg-white shadow-lg rounded-lg p-4 sm:p-6">
                <div class="bg-[#59c4b4]/10 p-4 rounded-lg">
                    <h1 class="text-lg sm:text-xl font-bold mb-4 text-center">
                        <i class="fa-solid fa-people-group"></i> <br>
                        Support & utilisateurs bloqués
                    </h1>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <!-- ====== Colonne gauche : Utilisateurs bloqués ====== -->
                        <div class="bg-white rounded-lg p-4 shadow-sm">
                            <h2 class="font-semibold text-gray-800 mb-3">
                                <i class="fa-solid fa-user-slash mr-2"></i> Utilisateurs bloqués
                            </h2>
                            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4">
                                <Flash/>
                            </div>

                            <!-- Empty state -->
                            <div v-if="blocked.length === 0" class="py-6 text-center text-gray-500">
                                <i class="fa-regular fa-face-smile-beam text-2xl mb-2"></i>
                                <p>Aucun utilisateur bloqué.</p>
                            </div>

                            <!-- Liste -->
                            <div v-else class="divide-y">
                                <div
                                    v-for="u in blocked"
                                    :key="u.id"
                                    class="py-3 flex items-center justify-between"
                                >
                                    <div class="flex items-center gap-3">
                                        <img
                                            :src="u.picture_profil_url || defaultAvatar"
                                            :alt="u.pseudo"
                                            class="w-10 h-10 rounded-full object-cover border border-gray-200"
                                            @error="$event.target.src = defaultAvatar"
                                        />
                                        <div>
                                            <div class="font-medium text-gray-800">{{ u.pseudo }}</div>
                                            <div v-if="u.first_name || u.last_name" class="text-xs text-gray-500">
                                                {{ u.first_name }} {{ u.last_name }}
                                            </div>
                                        </div>
                                    </div>

                                    <div class="flex items-center gap-3">
                                        <Link
                                            v-if="isStaff"
                                            :href="route('users.show', u.pseudo)"
                                            class="text-sm text-[#59c4b4] hover:underline"
                                        >
                                            Voir
                                        </Link>
                                        <button
                                            type="button"
                                            @click="unblock(u.id)"
                                            class="px-3 py-1.5 text-sm rounded-lg bg-red-50 text-red-600 hover:bg-red-100"
                                            title="Débloquer cet utilisateur"
                                        >
                                            Débloquer
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ====== Colonne droite : Contacter l’équipe ====== -->
                        <div class="bg-white rounded-lg p-4 shadow-sm">
                            <h2 class="font-semibold text-gray-800 mb-3">
                                <i class="fa-solid fa-envelope mr-2"></i> Contacter l’équipe
                            </h2>

                            <!-- message de succès -->
                            <div v-if="notice" class="mb-4 rounded-md bg-green-50 p-3 text-green-800 text-sm">
                                {{ notice }}
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                <div class="sm:col-span-1">
                                    <label for="c-role" class="block text-sm font-medium text-gray-700 mb-1">Destinataire</label>
                                    <select id="c-role" v-model="contactForm.role" class="...">
                                        <option value="Admin">Admin</option>
                                        <option value="Super-admin">Super-admin</option>
                                    </select>
                                    <p v-if="errors.role" class="mt-1 text-xs text-red-600">⚠️ {{ errors.role }}</p>
                                </div>

                                <div class="sm:col-span-2">
                                    <label for="c-subject"
                                           class="block text-sm font-medium text-gray-700 mb-1">Sujet</label>
                                    <input id="c-subject" type="text" v-model="contactForm.subject"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#59c4b4] focus:border-transparent"
                                           placeholder="Décrivez brièvement votre problème">
                                    <p v-if="errors.subject" class="mt-1 text-xs text-red-600">⚠️ {{
                                            errors.subject
                                        }}</p>
                                </div>

                                <div class="sm:col-span-3">
                                    <label for="c-message"
                                           class="block text-sm font-medium text-gray-700 mb-1">Message</label>
                                    <textarea id="c-message" v-model="contactForm.message" rows="5"
                                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#59c4b4] focus:border-transparent"
                                              placeholder="Expliquez votre situation en quelques lignes"></textarea>
                                    <p v-if="errors.message" class="mt-1 text-xs text-red-600">⚠️ {{
                                            errors.message
                                        }}</p>
                                </div>
                            </div>

                            <div class="mt-4 flex justify-end">
                                <button @click="submitContact" :disabled="sending"
                                        class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-white
                               bg-gradient-to-r from-[#59c4b4] to-[#4db3a3]
                               hover:from-[#4db3a3] hover:to-[#3aa796]
                               disabled:opacity-60 disabled:cursor-not-allowed">
                                    <i class="fa-solid fa-paper-plane"></i>
                                    <span>{{ sending ? 'Envoi…' : 'Envoyer' }}</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Aide rapide -->
        <div class="mt-6 bg-gray-50 border border-gray-200 rounded-lg p-4 text-sm text-gray-700 leading-relaxed">
            <h3 class="font-semibold text-gray-800 mb-2">
                <i class="fa-solid fa-circle-info mr-1 text-[#59c4b4]"></i>
                Aide rapide
            </h3>
            <ul class="list-disc pl-5 space-y-1">
                <li><span class="font-medium">Calendrier :</span> utilisez <em>Préc./Suiv.</em> et les vues <em>Mois/Semaine/Jour</em>
                    pour naviguer.
                </li>
                <li><span class="font-medium">Événements :</span> cliquez sur un événement du calendrier pour ouvrir sa
                    page (nouvel onglet).
                </li>
                <li><span class="font-medium">Créer :</span> le bouton <em>“Créer un événement”</em> est désactivé
                    lorsque vous avez {{ maxActiveSlots }} événements <strong>futurs & actifs</strong>.
                </li>
                <li><span class="font-medium">Mes participations :</span> cliquez sur une carte “À venir” ou
                    “Historique” pour voir le détail.
                </li>
                <li><span class="font-medium">Graphique :</span> affiche le nombre d’événements auxquels vous participez
                    sur les <strong>6 prochains mois</strong>.
                </li>
            </ul>
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

/* Boutons de navigation */
.fc-button {
    background: linear-gradient(135deg, #59c4b4 0%, #4db3a3 100%) !important;
    border: none !important;
    color: white !important;
    border-radius: 8px !important;
    padding: 6px 10px !important;
    font-size: 11px !important;
    font-weight: 600 !important;
    transition: all 0.3s ease !important;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1) !important;
}

.fc-button:hover {
    transform: translateY(-1px) !important;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2) !important;
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

/* Événements */
.fc-event {
    background: linear-gradient(135deg, #ffb347 0%, #ff9500 100%) !important;
    border: none !important;
    border-radius: 6px !important;
    color: white !important;
    font-size: 10px !important;
    font-weight: 500 !important;
    padding: 2px 6px !important;
    margin: 1px 2px !important;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.2) !important;
    transition: all 0.2s ease !important;
    cursor: pointer !important;
}

.fc-event:hover {
    transform: scale(1.02) !important;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.3) !important;
    background: linear-gradient(135deg, #ff9500 0%, #e6850e 100%) !important;
}

.fc-event-title {
    font-weight: 600 !important;
}

/* Lien "more" */
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

/* Responsive */
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

@media (max-width: 1024px) {
    .fc-button {
        padding: 5px 8px !important;
        font-size: 10px !important;
    }

    .fc-toolbar-title {
        font-size: 15px !important;
    }
}

/* Animations / scrollbars */
.transition-all {
    transition: all 0.2s ease-in-out;
}

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
