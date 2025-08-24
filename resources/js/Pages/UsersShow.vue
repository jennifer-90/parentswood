<template>
    <AuthenticatedLayout>

        <div class="py-4 bg-[#f9f5f2] min-h-screen">
            <div class="mx-auto w-full px-3 sm:px-5">
                <div class="bg-white shadow-lg rounded-lg p-4 sm:p-6">

                    <div class="py-6">
                        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">


                            <!-- Titre principal avec icône -->
                            <div class="bg-transparent border-2 border-[#ffb347] rounded-lg p-4 mb-6"
                                 style="background-color: rgba(255, 179, 71, 0.05);">
                                <div class="flex items-center justify-center gap-3">
                                    <div class="bg-[#ffb347] text-white rounded-full p-3">
                                        <i class="fas fa-user text-xl"></i>
                                    </div>
                                    <h1 class="text-2xl sm:text-3xl font-bold text-gray-800">Profil de  {{ (user.pseudo || user.name).charAt(0).toUpperCase() + (user.pseudo || user.name).slice(1) }}</h1>
                                </div>
                            </div>

                            <!-- Bannière de profil -->
                            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-8">
                                <div class="relative h-48 bg-gradient-to-r from-teal-500 to-teal-300 rounded-t-lg">
                                    <!-- Bannière -->
                                    <div
                                        class="absolute bottom-0 left-0 right-0 h-1/2 bg-gradient-to-t from-black/30 to-transparent"></div>
                                </div>

                                <!-- Photo de profil et infos -->
                                <div class="px-6 pb-6 -mt-16 relative">
                                    <div class="flex flex-col md:flex-row items-start md:items-end">
                                        <div class="relative group">
                                            <div
                                                class="h-32 w-32 rounded-full border-4 border-white bg-white shadow-lg overflow-hidden">
                                                <img
                                                    :src="$page.props.auth.user.picture_profil || '/images/default-avatar.png'"
                                                    alt="Photo de profil"
                                                    class="h-full w-full object-cover"
                                                >
                                            </div>

                                            <div
                                                class="absolute inset-0 bg-black bg-opacity-30 rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                                                <button class="text-white bg-black/50 p-2 rounded-full">
                                                    <i class="fas fa-camera text-lg"></i>
                                                </button>
                                            </div>
                                        </div>

                                        <div class="mt-4 md:mt-0 md:ml-6">
                                            <h1 class="text-3xl font-bold text-gray-900">
                                                    {{ (user.pseudo || user.name).charAt(0).toUpperCase() + (user.pseudo || user.name).slice(1) }}
                                                </h1>

                                            <div class="flex items-center mt-2 space-x-4">
                                    <span class="flex items-center text-gray-600">
                                        <i class="fas fa-calendar-day text-teal-500 mr-1"></i>
                                        Membre depuis {{ formatDate(user.created_at) }}
                                    </span>
                                                <span v-if="user.role"
                                                      class="px-3 py-1 rounded-full text-xs font-medium"
                                                      :class="{
                                            'bg-red-100 text-red-800': user.role === 'Super-admin',
                                            'bg-blue-100 text-blue-800': user.role === 'Admin',
                                            'bg-green-100 text-green-800': user.role === 'User'
                                        }">
                                        {{ user.role }}
                                    </span>
                                            </div>
                                        </div>

                                        <div v-if="$page.props.auth.user && $page.props.auth.user.id === user.id" class="mt-4 md:mt-0 md:ml-auto">
                                            <Link
                                                :href="route('profile.edit')"
                                                class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-orange-400 to-orange-500 text-white rounded-lg hover:from-orange-500 hover:to-orange-600 transition-all shadow-md hover:shadow-lg"
                                            >
                                                <i class="fas fa-user-edit mr-2"></i>
                                                Modifier le profil
                                            </Link>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Grille principale -->
                            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                                <!-- Colonne de gauche - À propos -->
                                <div class="lg:col-span-1 space-y-6">
                                    <!-- À propos -->
                                    <div class="bg-white rounded-lg shadow-sm p-6">
                                        <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                                            <i class="fas fa-info-circle text-teal-500 mr-2"></i>
                                            À propos
                                        </h3>
                                        <div class="space-y-3 text-gray-600">
                                            <div v-if="user.phone" class="flex items-center">
                                                <i class="fas fa-phone text-teal-500 w-5 mr-3"></i>
                                                <span>{{ user.phone }}</span>
                                            </div>
                                            <div v-if="user.address" class="flex items-start">
                                                <i class="fas fa-map-marker-alt text-teal-500 w-5 mt-1 mr-3"></i>
                                                <span>{{ user.address }}</span>
                                            </div>
                                            <div v-if="user.bio" class="pt-2 border-t border-gray-100">
                                                <p class="text-gray-700">{{ user.bio }}</p>
                                            </div>
                                            <div v-else class="text-gray-400 italic">
                                                Aucune biographie renseignée
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Statistiques -->
                                    <div class="bg-white rounded-lg shadow-sm p-6">
                                        <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                                            <i class="fas fa-chart-pie text-teal-500 mr-2"></i>
                                            Statistiques
                                        </h3>
                                        <div class="grid grid-cols-2 gap-4">
                                            <div
                                                class="text-center p-3 bg-gradient-to-br from-teal-50 to-teal-100 rounded-lg">
                                                <div class="text-2xl font-bold text-teal-600">
                                                    {{ user.events_created_count || 0 }}
                                                </div>
                                                <div class="text-xs text-teal-800">Événements créés</div>
                                            </div>
                                            <div
                                                class="text-center p-3 bg-gradient-to-br from-orange-50 to-orange-100 rounded-lg">
                                                <div class="text-2xl font-bold text-orange-600">
                                                    {{ user.events_attended_count || 0 }}
                                                </div>
                                                <div class="text-xs text-orange-800">Participations</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Colonne de droite - Événements -->
                                <div class="lg:col-span-2 space-y-6">
                                    <!-- Onglets -->
                                    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                                        <div class="border-b border-gray-200">
                                            <nav class="flex -mb-px">
                                                <button
                                                    v-for="tab in tabs"
                                                    :key="tab.name"
                                                    @click="activeTab = tab.name"
                                                    :class="[
                                            activeTab === tab.name
                                                ? 'border-teal-500 text-teal-600'
                                                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                                            'whitespace-nowrap py-4 px-6 border-b-2 font-medium text-sm flex items-center'
                                        ]"
                                                >
                                                    <i :class="tab.icon" class="mr-2"></i>
                                                    {{ tab.name }}
                                                    <span v-if="tab.count !== undefined"
                                                          class="ml-2 px-2 py-0.5 text-xs rounded-full"
                                                          :class="activeTab === tab.name ? 'bg-teal-100 text-teal-800' : 'bg-gray-100 text-gray-600'">
                                            {{ tab.count }}
                                        </span>
                                                </button>
                                            </nav>
                                        </div>

                                        <!-- Contenu des onglets -->
                                        <div class="p-6">
                                            <!-- Événements à venir -->
                                            <div v-if="activeTab === 'Événements à venir'">
                                                <div v-if="upcomingEvents && upcomingEvents.length > 0"
                                                     class="space-y-4">
                                                    <div v-for="event in upcomingEvents" :key="event.id"
                                                         class="border rounded-lg overflow-hidden hover:shadow-md transition-shadow">
                                                        <div class="p-4">
                                                            <div class="flex justify-between items-start">
                                                                <div>
                                                                    <h4 class="font-semibold text-lg text-gray-900">
                                                                        {{ event.title }}</h4>
                                                                    <div
                                                                        class="flex items-center text-sm text-gray-500 mt-1">
                                                                        <i class="far fa-calendar-alt text-teal-500 mr-2"></i>
                                                                        {{ formatDate(event.start_date) }}
                                                                        <span v-if="event.end_date"> - {{
                                                                                formatDate(event.end_date)
                                                                            }}</span>
                                                                    </div>
                                                                    <div
                                                                        class="flex items-center text-sm text-gray-500 mt-1">
                                                                        <i class="fas fa-map-marker-alt text-teal-500 mr-2"></i>
                                                                        {{ event.location || 'Lieu non précisé' }}
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <p class="mt-2 text-gray-600 text-sm line-clamp-2">
                                                                {{ event.description }}</p>
                                                            <div class="mt-3 flex justify-between items-center">
                                                                <div class="flex -space-x-2">
                                                                    <img
                                                                        v-for="(participant, index) in event.participants?.slice(0, 3)"
                                                                        :key="index"
                                                                        :src="participant.profile_photo_url || 'https://ui-avatars.com/api/?name=' + participant.name"
                                                                        class="h-8 w-8 rounded-full border-2 border-white"
                                                                        :title="participant.name">
                                                                    <div v-if="event.participants?.length > 3"
                                                                         class="h-8 w-8 rounded-full bg-gray-100 border-2 border-white flex items-center justify-center text-xs text-gray-500">
                                                                        +{{ event.participants.length - 3 }}
                                                                    </div>
                                                                </div>
                                                                <Link :href="route('events.show', event.id)"
                                                                      class="text-teal-600 hover:text-teal-800 text-sm font-medium flex items-center">
                                                                    Voir l'événement
                                                                    <i class="fas fa-arrow-right ml-1 text-xs"></i>
                                                                </Link>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div v-else class="text-center py-10 text-gray-500">
                                                    <i class="far fa-calendar-alt text-4xl text-gray-300 mb-3"></i>
                                                    <p>Aucun événement à venir</p>
                                                </div>
                                            </div>

                                            <!-- Historique des événements -->
                                            <div v-else>
                                                <div v-if="pastEvents && pastEvents.length > 0" class="space-y-4">
                                                    <div v-for="event in pastEvents" :key="'past-'+event.id"
                                                         class="border rounded-lg overflow-hidden hover:shadow-md transition-all transform hover:-translate-y-0.5">
                                                        <div class="p-4 bg-gray-50">
                                                            <div class="flex justify-between items-start">
                                                                <div>
                                                                    <h4 class="font-semibold text-gray-900">
                                                                        {{ event.title }}</h4>
                                                                    <div
                                                                        class="flex items-center text-sm text-gray-500 mt-1">
                                                                        <i class="far fa-calendar-alt text-teal-500 mr-2"></i>
                                                                        {{ formatDate(event.start_date) }}
                                                                    </div>
                                                                </div>
                                                                <span
                                                                    class="px-2 py-1 text-xs font-medium rounded-full bg-gray-100 text-gray-800">
                                                        Terminé
                                                    </span>
                                                            </div>
                                                            <div class="mt-3 flex justify-between items-center">
                                                                <div class="flex items-center text-sm text-gray-500">
                                                                    <i class="fas fa-users mr-1.5"></i>
                                                                    {{ event.participants_count || 0 }} participants
                                                                </div>
                                                                <Link :href="route('events.show', event.id)"
                                                                      class="text-teal-600 hover:text-teal-800 text-sm font-medium flex items-center">
                                                                    Voir le récapitulatif
                                                                    <i class="fas fa-arrow-right ml-1 text-xs"></i>
                                                                </Link>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div v-else class="text-center py-10 text-gray-500">
                                                    <i class="fas fa-history text-4xl text-gray-300 mb-3"></i>
                                                    <p>Aucun événement passé</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>

    </AuthenticatedLayout>
</template>

<script setup>
import {ref, computed} from 'vue';
import {Link} from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    user: Object,
    upcomingEvents: {
        type: Array,
        default: () => []
    },
    pastEvents: {
        type: Array,
        default: () => []
    }
});

const activeTab = ref('Événements à venir');

const tabs = computed(() => [
    {
        name: 'Événements à venir',
        icon: 'fas fa-calendar-day text-teal-500',
        count: props.upcomingEvents?.length || 0
    },
    {
        name: 'Historique',
        icon: 'fas fa-history text-teal-500',
        count: props.pastEvents?.length || 0
    }
]);

const formatDate = (dateString) => {
    if (!dateString) return '';
    const date = new Date(dateString);
    return date.toLocaleDateString('fr-FR', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};
</script>

<style scoped>
/* Styles spécifiques */
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

/* Animation des cartes d'événements */
.event-card-enter-active,
.event-card-leave-active {
    transition: all 0.3s ease;
}

.event-card-enter-from,
.event-card-leave-to {
    opacity: 0;
    transform: translateY(10px);
}

/* Style personnalisé pour la barre de défilement */
::-webkit-scrollbar {
    width: 6px;
    height: 6px;
}

::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 10px;
}

::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 10px;
}

::-webkit-scrollbar-thumb:hover {
    background: #a8a8a8;
}
</style>
