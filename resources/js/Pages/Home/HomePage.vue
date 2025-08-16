<template>
    <GuestLayout>

        <div
            v-if="page.props.flash.success"
            class="alert alert-success text-center mx-auto mt-3 w-50 shadow"
            role="alert"
        >
            {{ page.props.flash.success }}
        </div>

        <!-- Hero Section -->
        <div class="container mx-auto px-4 py-16">
            <div class="flex flex-col md:flex-row items-center gap-8">
                <div class="md:w-1/2 space-y-6">
                    <h1 class="text-4xl md:text-5xl font-bold text-gray-800 leading-tight">
                <span class="bg-clip-text text-transparent bg-gradient-to-r from-[#FFB347] to-[#FF9500]">
                    ParentsWood
                </span>
                        <br>
                        <span class="text-2xl md:text-3xl font-normal text-gray-600">

                    <i class="fa-solid fa-users"></i>  La communauté des parents solos
                </span>

                    </h1>
                    <p class="text-lg text-gray-600">
                        Rejoignez une communauté bienveillante de parents solos pour échanger, partager et participer
                        à des événements conviviaux près de chez vous.
                        <br>
                        <br>
                        Parce qu’on ne devrait jamais traverser la parentalité en solo. Parce que créer du lien change tout.
                    </p>
                    <div class="flex flex-col sm:flex-row gap-4 pt-4">
                        <Link href="/register"
                              class="bg-gradient-to-r from-[#FFB347] to-[#FF9500] text-white px-8 py-3 rounded-lg font-medium shadow-lg hover:shadow-xl transform hover:scale-105 transition-all duration-300 text-center">
                            <i class="fa-regular fa-heart"></i>
                            Je rejoins la communauté
                        </Link>
                        <Link href="/login"
                              class="border-2 border-[#A7D49B] text-[#2C5E1A] px-8 py-3 rounded-lg font-medium hover:bg-[#E8F5E9] transition-colors duration-300 text-center">
                            Se connecter
                        </Link>
                    </div>
                </div>
                <div class="md:w-1/2 flex justify-center mt-8 md:mt-0">
                    <div class="relative w-full max-w-md">
                        <div
                            class="absolute -inset-4 bg-gradient-to-r from-[#FFE8C6] to-[#E3F4E0] rounded-2xl transform rotate-1"></div>
                        <img src="/storage/picture_home.png" alt="Famille heureuse"
                             class="relative rounded-xl shadow-2xl w-full h-auto border-4 border-white">
                    </div>
                </div>
            </div>
        </div>
        <!-- About Us Section -->
        <div class="bg-white py-16 px-4">
            <div class="container mx-auto max-w-6xl">
                <div class="text-center mb-12">
                    <h2 class="text-3xl font-bold text-gray-800 mb-4"> Mon Histoire, votre Communauté</h2>
                    <div class="w-24 h-1 bg-gradient-to-r from-[#59C4B4] to-[#4DB3A3] mx-auto rounded-full"></div>
                </div>

                <div class="bg-[#F8FCF8] rounded-2xl p-8 md:p-12 shadow-lg">
                    <div class="grid md:grid-cols-2 gap-12 items-center">
                        <div>
                            <h3 class="text-2xl font-bold text-[#2C5E1A] mb-4">Bienvenue chez ParentsWood</h3>
                            <p class="text-gray-700 mb-6">
                                “Quand je suis devenue maman solo, je me suis sentie isolée. J’aurais aimé avoir un endroit pour rencontrer d’autres parents dans la même situation. Alors j’ai créé ParentsWood.”
                            </p>
                            <div class="space-y-4">
                                <div class="flex items-start">
                                    <div class="flex-shrink-0 mt-1">
                                        <div
                                            class="flex items-center justify-center h-6 w-6 rounded-full bg-[#E3F4E0] text-[#59C4B4]">
                                            ✓
                                        </div>
                                    </div>
                                    <p class="ml-3 text-gray-700">Événements conviviaux près de chez vous</p>
                                </div>
                                <div class="flex items-start">
                                    <div class="flex-shrink-0 mt-1">
                                        <div
                                            class="flex items-center justify-center h-6 w-6 rounded-full bg-[#E3F4E0] text-[#59C4B4]">
                                            ✓
                                        </div>
                                    </div>
                                    <p class="ml-3 text-gray-700">Rencontres bienveillantes entre parents</p>
                                </div>
                            </div>
                        </div>
                        <div class="relative">
                            <div class="bg-white p-6 rounded-xl shadow-md">
                                <div class="relative z-10">
                                    <div class="flex items-center mb-4">
                                        <div class="bg-[#E3F4E0] p-2 rounded-full mr-4">
                                            <svg class="w-8 h-8 text-[#59C4B4]" fill="none" stroke="currentColor"
                                                 viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                      d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                            </svg>
                                        </div>
                                        <h4 class="text-xl font-bold text-[#2C5E1A]">Notre Engagement</h4>
                                    </div>
                                    <p class="text-gray-700 italic">
                                        "Chez ParentsWood, chaque parent compte. Nous créons un espace sûr, inclusif et
                                        chaleureux pour tous."
                                    </p>
                                </div>
                            </div>
                            <div
                                class="absolute -bottom-4 -right-4 bg-[#FFB347] h-24 w-24 rounded-full -z-10 opacity-20"></div>
                            <div
                                class="absolute -top-4 -left-4 bg-[#59C4B4] h-16 w-16 rounded-full -z-10 opacity-20"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Search Section -->
        <div class="bg-white py-12 px-4 shadow-inner">
            <div class="container mx-auto max-w-6xl">
                <h2 class="text-3xl font-bold text-center mb-8 text-gray-800">Découvrez nos événements</h2>

                <div class="bg-white p-6 rounded-xl shadow-lg max-w-4xl mx-auto">
                    <div class="flex flex-col md:flex-row gap-4">
                        <div class="flex-1 relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                          d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z"
                                          clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <input
                                type="text"
                                v-model="searchQuery"
                                @keyup.enter="searchEvents"
                                placeholder="Rechercher un événement..."
                                class="block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg bg-gray-50 focus:ring-2 focus:ring-[#59C4B4] focus:border-transparent transition-all duration-200"
                            >
                        </div>
                        <div class="w-full md:w-64 relative">
                            <select
                                v-model="dateFilter"
                                class="w-full pl-3 pr-10 py-3 border border-gray-300 rounded-lg bg-gray-50 focus:ring-2 focus:ring-[#59C4B4] focus:border-transparent appearance-none"
                            >
                                <option value="all">Toutes les dates</option>
                                <option value="week">Cette semaine</option>
                                <option value="month">Ce mois-ci</option>
                                <option value="next3months">3 prochains mois</option>
                            </select>
                            <div
                                class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-700">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg"
                                     viewBox="0 0 20 20">
                                    <path
                                        d="M9.293 12.95l.707.707L15.657 8l-1.414-1.414L10 10.828 5.757 6.586 4.343 8z"/>
                                </svg>
                            </div>
                        </div>
                        <button
                            @click="searchEvents"
                            class="px-6 py-3 bg-gradient-to-r from-[#59C4B4] to-[#4DB3A3] text-white font-medium rounded-lg hover:from-[#4DB3A3] hover:to-[#3DA393] focus:outline-none focus:ring-2 focus:ring-[#59C4B4] focus:ring-offset-2 transition-all duration-200 shadow-md hover:shadow-lg transform hover:-translate-y-0.5"
                        >
                    <span class="flex items-center justify-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                             xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                        Rechercher
                    </span>
                        </button>
                    </div>
                </div>
            </div>
        </div>



        <!-- Events Grid -->
        <div class="container mx-auto px-4 py-12">
            <h2 class="text-3xl font-bold text-center mb-8 text-gray-800">Événements à venir</h2>


            <div v-if="filteredEvents.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div v-for="event in filteredEvents" :key="event.id"
                     class="bg-white rounded-xl shadow-md overflow-hidden hover:shadow-xl transition-shadow duration-300 border border-gray-100">
                    <div class="p-6">
                        <div class="flex justify-between items-start mb-4">
                            <h3 class="text-xl font-bold text-gray-800">{{ event.title }}</h3>
                            <span class="bg-[#59C4B4] bg-opacity-10 text-[#2C5E1A] text-sm font-medium px-3 py-1 rounded-full">
                        {{ event.participants_count }} participants
                    </span>
                        </div>

                        <div class="text-gray-600 mb-4">
                            <div class="flex items-center mb-2">
                                <svg class="w-5 h-5 mr-2 text-[#59C4B4]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                {{ formatDate(event.start_date) }}
                            </div>
                            <div class="flex items-center">
                                <svg class="w-5 h-5 mr-2 text-[#59C4B4]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                                {{ event.location || 'Lieu non spécifié' }}
                            </div>
                        </div>

                        <Link :href="route('login')"
                              class="inline-flex items-center text-[#59C4B4] hover:text-[#4DB3A3] font-medium transition-colors duration-200">
                            Voir les détails
                            <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                            </svg>
                        </Link>
                    </div>
                </div>
            </div>

            <div v-else class="text-center py-12">
                <div class="inline-block p-4 bg-[#E8F5E9] rounded-full mb-4">
                    <svg class="w-12 h-12 text-[#59C4B4]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <h3 class="text-xl font-medium text-gray-800 mb-2">Aucun événement à venir</h3>
                <p class="text-gray-600">Aucun événement ne correspond à votre recherche.</p>
            </div>
        </div>


        <!-- CTA Section -->
        <div class="bg-gradient-to-r from-[#59C4B4] to-[#4DB3A3] text-white py-16 px-4">
            <div class="container mx-auto text-center">
                <h2 class="text-3xl md:text-4xl font-bold mb-6">Prêt à nous rejoindre ?</h2>
                <p class="text-xl mb-8 max-w-3xl mx-auto text-white/90">
                    Inscrivez-vous dès maintenant pour accéder à tous nos événements et faire partie de notre communauté
                    bienveillante.
                </p>
                <div class="flex flex-col sm:flex-row justify-center gap-4">
                    <Link href="/register"
                          class="bg-white text-[#2C5E1A] hover:bg-gray-100 px-8 py-4 rounded-lg font-bold text-lg shadow-lg hover:shadow-xl transform hover:scale-105 transition-all duration-300">
                        Créer un compte
                    </Link>
                    <Link href="/login"
                          class="bg-transparent border-2 border-white text-white hover:bg-white hover:bg-opacity-10 px-8 py-4 rounded-lg font-bold text-lg transition-colors duration-300">
                        Se connecter
                    </Link>
                </div>
            </div>
        </div>

    </GuestLayout>
</template>


<script setup>
import {ref, computed, onMounted} from 'vue';
import {Link} from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { usePage } from '@inertiajs/vue3';

const page = usePage();

const props = defineProps({
    events: {
        type: Array,
        default: () => []
    }
});

const searchQuery = ref('');
const dateFilter = ref('all');
const loading = ref(false);

// Format date in French
const formatDate = (dateString) => {
    const options = {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    };
    return new Date(dateString).toLocaleDateString('fr-FR', options);
};

// Filter events based on search and date filter
const filteredEvents = computed(() => {
    let filtered = [...props.events];

    // Filter by search query
    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        filtered = filtered.filter(event =>
            event.title.toLowerCase().includes(query) ||
            (event.description && event.description.toLowerCase().includes(query)) ||
            (event.location && event.location.toLowerCase().includes(query))
        );
    }

    // Filter by date
    const now = new Date();
    switch (dateFilter.value) {
        case 'week':
            const nextWeek = new Date();
            nextWeek.setDate(now.getDate() + 7);
            filtered = filtered.filter(event => {
                const eventDate = new Date(event.start_date);
                return eventDate >= now && eventDate <= nextWeek;
            });
            break;
        case 'month':
            const nextMonth = new Date();
            nextMonth.setMonth(now.getMonth() + 1);
            filtered = filtered.filter(event => {
                const eventDate = new Date(event.start_date);
                return eventDate >= now && eventDate <= nextMonth;
            });
            break;
        case 'next3months':
            const in3Months = new Date();
            in3Months.setMonth(now.getMonth() + 3);
            filtered = filtered.filter(event => {
                const eventDate = new Date(event.start_date);
                return eventDate >= now && eventDate <= in3Months;
            });
            break;
        default:
            // Show all future events by default
            filtered = filtered.filter(event => new Date(event.start_date) >= now);
    }

    return filtered.sort((a, b) => new Date(a.start_date) - new Date(b.start_date));
});

const searchEvents = () => {
    // This would typically make an API call to search events
    console.log('Searching events with query:', searchQuery.value, 'and date filter:', dateFilter.value);
};

onMounted(() => {
    // You could fetch events here if not passed as props
    // fetchEvents();
});
</script>

<style scoped>
/* Custom scrollbar */
::-webkit-scrollbar {
    width: 8px;
    height: 8px;
}

::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 4px;
}

::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 4px;
}

::-webkit-scrollbar-thumb:hover {
    background: #a8a8a8;
}

/* Animation for cards */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.animate-fade-in-up {
    animation: fadeInUp 0.5s ease-out forwards;
}

/* Custom styles for the event cards */
.event-card {
    transition: all 0.3s ease;
    opacity: 0;
    animation: fadeInUp 0.5s ease-out forwards;
}

/* Add delay for each card */
.event-card:nth-child(1) {
    animation-delay: 0.1s;
}

.event-card:nth-child(2) {
    animation-delay: 0.2s;
}

.event-card:nth-child(3) {
    animation-delay: 0.3s;
}

.event-card:nth-child(4) {
    animation-delay: 0.4s;
}

.event-card:nth-child(5) {
    animation-delay: 0.5s;
}

.event-card:nth-child(6) {
    animation-delay: 0.6s;
}
</style>
