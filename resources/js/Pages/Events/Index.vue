<script setup>
import {Link, router} from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import villes from '@/data/villes_belges.json'
import {defineProps, ref, watch, computed } from 'vue'



// --- Affichage "voir plus" des événements passés ---
const showAllPast = ref(false)
const displayedPastEvents = computed(() =>
    showAllPast.value ? props.pastEvents : props.pastEvents.slice(0, 4)
)
const remainingPastCount = computed(() =>
    Math.max((props.pastEvents?.length || 0) - 4, 0)
)

// Props du contrôleur
const props = defineProps({
    upcomingEvents: Object,
    pastEvents: Array
})

// Image par défaut
const defaultImage = '/storage/events/default.png'

// Filtres utilisateur
const selectedVille = ref('')
const selectedDate = ref('')
const selectedFilter = ref('')
const selectedInteret = ref('')

// Watch : recharge les données depuis le serveur avec les bons filtres
watch([selectedVille, selectedDate, selectedFilter], () => {
    const params = {}

    if (selectedVille.value) params.ville = selectedVille.value
    if (selectedDate.value) params.date = selectedDate.value
    if (selectedFilter.value) params.filter = selectedFilter.value

    router.get(route('events.index'), params, {preserveState: true})
})

// Réinitialisation des filtres
const resetFilters = () => {
    selectedVille.value = ''
    selectedDate.value = ''
    selectedFilter.value = ''
    selectedInteret.value = ''
}


</script>

<template>
    <AuthenticatedLayout>
        <div class="py-4 bg-[#f9f5f2] min-h-screen">
            <div class="mx-auto w-full px-3 sm:px-5">
                <div class="bg-white shadow-lg rounded-lg p-4 sm:p-6">
                    <!-- Titre principal avec icône -->
                    <div class="bg-transparent border-2 border-[#59c4b4] rounded-lg p-4 mb-6" style="background-color: rgba(89, 196, 180, 0.05);">
                        <div class="flex items-center justify-center gap-3">
                            <div class="bg-[#59c4b4] text-white rounded-full p-3">
                                <i class="fa-solid fa-calendar-day text-xl"></i>
                            </div>
                            <h1 class="text-2xl font-bold text-gray-800">Événements à venir</h1>
                        </div>
                    </div>

                    <!-- Bandeau d’info permanent -->
                    <div
                        class="mb-6 rounded-lg border border-[#59c4b4]/30 bg-[#59c4b4]/10 p-4"
                        role="region"
                        aria-labelledby="events-help-title"
                    >
                        <div class="flex items-start gap-3">
                            <i class="fa-solid fa-circle-info mt-1 text-[#59c4b4]" aria-hidden="true"></i>
                            <div>
                                <p id="events-help-title" class="font-semibold text-gray-800">Comment utiliser cette page</p>
                                <ul class="mt-2 text-sm text-gray-700 list-disc pl-5 space-y-1 leading-relaxed">
                                    <li>Filtrez par <strong>Ville</strong>, <strong>Date</strong> ou <strong>Période</strong> pour affiner.</li>
                                    <li>Cliquez un évènement pour voir les détails et/ou vous inscrire.</li>
                                    <li>Créez le vôtre via <strong>“Créer un événement”</strong>.</li>
                                    <li>Les <strong>événements passés</strong> sont en bas (4 visibles, puis “Voir la suite”).</li>
                                </ul>
                            </div>
                        </div>
                    </div>


                    <!-- Filtres -->
                    <div class="bg-[#59c4b4]/10 p-6 rounded-lg mb-12">
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 items-end">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Ville</label>
                                <select
                                    v-model="selectedVille"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#59c4b4] focus:border-transparent transition duration-200"
                                >
                                    <option value="">Toutes les villes</option>
                                    <option v-for="ville in villes.villes" :key="ville" :value="ville">
                                        {{ ville }}
                                    </option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Date</label>
                                <input
                                    type="date"
                                    v-model="selectedDate"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#59c4b4] focus:border-transparent transition duration-200"
                                />
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Période</label>
                                <select
                                    v-model="selectedFilter"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#59c4b4] focus:border-transparent transition duration-200"
                                >
                                    <option value="">Tous les événements</option>
                                    <option value="week">Cette semaine</option>
                                    <option value="month">Ce mois-ci</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Centre d'intérêt</label>
                                <select
                                    v-model="selectedInteret"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#59c4b4] focus:border-transparent transition duration-200"
                                >
                                    <option value="">Tous les centres d'intérêt</option>
                                    <option>Sport</option>
                                    <option>Culture</option>
                                    <option>Sortie enfants</option>
                                    <option>Relaxation</option>
                                </select>
                            </div>

                            <button
                                @click="resetFilters"
                                class="h-[42px] flex items-center justify-center gap-2 px-4 py-2 bg-white border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors duration-200"
                            >
                                <svg xmlns="[http://www.w3.org/2000/svg](http://www.w3.org/2000/svg)" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 110 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z" clip-rule="evenodd"/>
                                </svg>
                                Réinitialiser
                            </button>
                        </div>
                    </div>

                    <!-- Section Événements à venir -->

                    <div class="bg-[#59c4b4]/10 p-6 rounded-lg mb-12">




                        <div class="flex items-center justify-between mb-6 flex-wrap gap-3">
                            <div class="bg-[#59c4b4] text-white rounded-lg p-3 flex items-center gap-3">
                                <i class="fa-solid fa-calendar-day"></i>
                                <h2 class="text-lg font-semibold">Événements à venir</h2>
                            </div>

                            <div class="flex items-center gap-3">
    <span class="bg-blue-100 text-blue-800 text-xs font-medium px-2.5 py-0.5 rounded">
      {{ upcomingEvents.total }} événement(s)
    </span>

                                <!-- bouton Créer un événement -->
                                <Link
                                    :href="route('events.create')"
                                    class="inline-flex items-center gap-2 bg-gradient-to-r from-[#ffb347] to-[#ff9500]
             hover:from-[#ff9500] hover:to-[#e6850e] text-white px-4 py-2 rounded-lg
             font-medium shadow-sm hover:shadow-md transition-all duration-300"
                                    aria-label="Créer un événement"
                                >
                                    <i class="fa-solid fa-plus"></i>
                                    Créer un événement
                                </Link>
                            </div>
                        </div>






                        <div v-if="upcomingEvents.data.length" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                            <Link
                                v-for="event in upcomingEvents.data"
                                :key="event.id"
                                :href="route('events.show', event.id)"
                                class="group bg-white rounded-xl shadow-sm overflow-hidden hover:shadow-md transition-all duration-300 border border-gray-100 hover:border-[#59c4b4]/30"
                            >
                                <div class="relative h-48 overflow-hidden">
                                    <img
                                        :src="event.picture_event ? '/storage/' + event.picture_event : defaultImage"
                                        :alt="'Image de ' + event.name_event"
                                        class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105"
                                    />
                                    <div class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/70 to-transparent p-4">
                                        <p class="text-white font-semibold text-lg truncate">{{ event.name_event }}</p>
                                        <p class="text-white/90 text-sm">📍 {{ event.location }}</p>
                                    </div>
                                    <div class="absolute top-3 right-3 bg-white/90 text-gray-800 text-xs font-medium px-2 py-1 rounded-full">
                                        {{
                                            new Date(event.date).toLocaleDateString('fr-BE', {
                                                day: 'numeric',
                                                month: 'short'
                                            })
                                        }}
                                    </div>
                                </div>
                                <div class="p-4">
                                    <div class="flex items-center text-sm text-gray-500 mb-2">
                                        <svg xmlns="[http://www.w3.org/2000/svg](http://www.w3.org/2000/svg)" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                        {{ event.hour.slice(0, 5) }}
                                    </div>
                                    <p class="text-gray-600 text-sm line-clamp-2 mb-3">{{ event.description }}</p>
                                    <div class="flex justify-between items-center">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-[#59c4b4]/10 text-[#59c4b4]">
                                            {{ event.participants_count }} participants
                                        </span>
                                        <span class="text-sm font-medium text-[#59c4b4] group-hover:underline">
                                            Voir détails →
                                        </span>
                                    </div>
                                </div>
                            </Link>
                        </div>

                        <div v-else class="text-center py-12 bg-white rounded-xl border-2 border-dashed border-gray-200">
                            <svg xmlns="[http://www.w3.org/2000/svg](http://www.w3.org/2000/svg)" class="h-12 w-12 mx-auto text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            <h3 class="mt-2 text-lg font-medium text-gray-900">Aucun événement à venir</h3>
                            <p class="mt-1 text-sm text-gray-500 max-w-md mx-auto">
                                Il n'y a pas d'événements prévus pour le moment. Soyez le premier à en créer un !
                            </p>
                            <div class="mt-6">
                                <Link
                                    :href="route('events.create')"
                                    class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-gradient-to-r from-[#59c4b4] to-[#4db3a3] hover:from-[#4db3a3] hover:to-[#3da393] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#59c4b4]"
                                >
                                    <svg xmlns="[http://www.w3.org/2000/svg](http://www.w3.org/2000/svg)" class="-ml-1 mr-2 h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd"/>
                                    </svg>
                                    Créer un événement
                                </Link>
                            </div>
                        </div>

                        <!-- Pagination -->
                        <div v-if="upcomingEvents.last_page > 1" class="mt-10 flex items-center justify-between border-t border-gray-200 px-4 py-3 sm:px-6">
                            <div class="flex flex-1 justify-between sm:hidden">
                                <Link
                                    v-if="upcomingEvents.prev_page_url"
                                    :href="upcomingEvents.prev_page_url"
                                    class="relative inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                                >
                                    Précédent
                                </Link>
                                <Link
                                    v-if="upcomingEvents.next_page_url"
                                    :href="upcomingEvents.next_page_url"
                                    class="relative ml-3 inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                                >
                                    Suivant
                                </Link>
                            </div>
                            <div class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
                                <div>
                                    <p class="text-sm text-gray-700">
                                        Affichage de <span class="font-medium">{{ upcomingEvents.from || 0 }}</span> à
                                        <span class="font-medium">{{ upcomingEvents.to || 0 }}</span> sur <span class="font-medium">{{ upcomingEvents.total }}</span> événements
                                    </p>
                                </div>
                                <div>
                                    <nav class="isolate inline-flex -space-x-px rounded-md shadow-sm" aria-label="Pagination">
                                        <Link
                                            v-if="upcomingEvents.prev_page_url"
                                            :href="upcomingEvents.prev_page_url"
                                            class="relative inline-flex items-center rounded-l-md px-2 py-2 text-gray-400 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:z-20 focus:outline-offset-0"
                                        >
                                            <span class="sr-only">Précédent</span>
                                            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                <path fill-rule="evenodd" d="M12.79 5.23a.75.75 0 01-.02 1.06L8.832 10l3.938 3.71a.75.75 0 11-1.04 1.08l-4.5-4.25a.75.75 0 010-1.08l4.5-4.25a.75.75 0 011.06.02z" clip-rule="evenodd"/>
                                            </svg>
                                        </Link>

                                        <Link
                                            v-for="page in upcomingEvents.last_page"
                                            :key="page"
                                            :href="upcomingEvents.path + '?page=' + page"
                                            :class="[
                                                'relative inline-flex items-center px-4 py-2 text-sm font-semibold',
                                                upcomingEvents.current_page === page
                                                    ? 'z-10 bg-[#59c4b4] text-white focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#59c4b4]'
                                                    : 'text-gray-900 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:outline-offset-0'
                                            ]"
                                            :aria-current="upcomingEvents.current_page === page ? 'page' : undefined"
                                        >
                                            {{ page }}
                                        </Link>

                                        <Link
                                            v-if="upcomingEvents.next_page_url"
                                            :href="upcomingEvents.next_page_url"
                                            class="relative inline-flex items-center rounded-r-md px-2 py-2 text-gray-400 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:z-20 focus:outline-offset-0"
                                        >
                                            <span class="sr-only">Suivant</span>
                                            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                <path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z" clip-rule="evenodd"/>
                                            </svg>
                                        </Link>
                                    </nav>
                                </div>
                            </div>
                        </div>
                    </div>



                    <!-- Section Événements passés -->
                    <div class="bg-[#59c4b4]/10 p-6 rounded-lg">
                        <div class="flex items-center justify-between mb-6">
                            <div class="bg-[#59c4b4] text-white rounded-lg p-3 flex items-center gap-3">
                                <i class="fa-solid fa-calendar-xmark"></i>
                                <h2 class="text-lg font-semibold">Événements passés</h2>
                            </div>
                            <span class="bg-gray-100 text-gray-800 text-xs font-medium px-2.5 py-0.5 rounded">
      {{ pastEvents.length }} événement(s)
    </span>
                        </div>

                        <div v-if="pastEvents.length" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                            <Link
                                v-for="event in displayedPastEvents"
                                :key="event.id"
                                :href="route('events.show', event.id)"
                                class="group bg-white rounded-xl shadow-sm overflow-hidden hover:shadow-md transition-all duration-300 border border-gray-100 opacity-80 hover:opacity-100"
                            >
                                <!-- carte identique à avant -->
                                <div class="relative h-48 overflow-hidden">
                                    <img
                                        :src="event.picture_event ? '/storage/' + event.picture_event : defaultImage"
                                        :alt="'Image de ' + event.name_event"
                                        class="w-full h-full object-cover grayscale group-hover:grayscale-0 transition-all duration-500 group-hover:scale-105"
                                    />
                                    <div class="absolute inset-0 bg-black/30 group-hover:bg-black/20 transition-colors duration-300"></div>
                                    <div class="absolute bottom-0 left-0 right-0 p-4">
                                        <p class="text-white font-semibold text-lg truncate">{{ event.name_event }}</p>
                                        <p class="text-white/90 text-sm">📍 {{ event.location }}</p>
                                    </div>
                                    <div class="absolute top-3 right-3 bg-white/90 text-gray-800 text-xs font-medium px-2 py-1 rounded-full">
                                        {{
                                            new Date(event.date).toLocaleDateString('fr-BE', {
                                                day: 'numeric',
                                                month: 'short'
                                            })
                                        }}
                                    </div>
                                </div>
                                <div class="p-4">
                                    <div class="flex items-center text-sm text-gray-500 mb-2">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                        {{ event.hour.slice(0, 5) }}
                                    </div>
                                    <div class="flex justify-between items-center">
          <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
            {{ event.participants_count }} participants
          </span>
                                        <span class="text-sm font-medium text-gray-500 group-hover:text-[#59c4b4] group-hover:underline transition-colors">
            Voir détails
          </span>
                                    </div>
                                </div>
                            </Link>
                        </div>

                        <!-- Bouton Voir la suite -->
                        <div v-if="!showAllPast && remainingPastCount > 0" class="mt-6 text-center">
                            <button
                                @click="showAllPast = true"
                                class="inline-flex items-center gap-2 bg-gradient-to-r from-[#59c4b4] to-[#4db3a3]
             hover:from-[#4db3a3] hover:to-[#3aa796] text-white px-4 py-2 rounded-lg
             font-medium shadow-sm hover:shadow-md transition-all duration-300"
                            >
                                Voir la suite ({{ remainingPastCount }})
                            </button>
                        </div>

                        <!-- Optionnel : bouton pour replier -->
                        <div v-else-if="showAllPast && pastEvents.length > 4" class="mt-4 text-center">
                            <button
                                @click="showAllPast = false"
                                class="inline-flex items-center gap-2 px-4 py-2 rounded-lg border border-gray-300 bg-white
             hover:bg-gray-50 text-gray-700 font-medium transition-all duration-300"
                            >
                                Voir moins
                            </button>
                        </div>

                        <div v-else class="text-center py-12 bg-white rounded-xl border-2 border-dashed border-gray-200">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <h3 class="mt-2 text-lg font-medium text-gray-900">Aucun événement passé</h3>
                            <p class="mt-1 text-sm text-gray-500">Aucun événement n'a encore eu lieu.</p>
                        </div>
                    </div>




                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
<style scoped>
/* Transitions personnalisées */
.transition-all {
    transition-property: all;
    transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
    transition-duration: 150ms;
}

/* Style personnalisé pour les champs de formulaire */
select:focus, input:focus {
    outline: none;
    box-shadow: 0 0 0 2px rgba(89, 196, 180, 0.2);
}

/* Animation de survol pour les cartes d'événements */
.group:hover .group-hover\:scale-105 {
    transform: scale(1.05);
}

/* Style personnalisé pour la pagination active */
.bg-\[\#59c4b4\] {
    background-color: #59c4b4;
}

/* Style pour les images en niveaux de gris */
.grayscale {
    filter: grayscale(100%);
}

/* Transition pour les images au survol */
.group:hover .group-hover\:grayscale-0 {
    filter: grayscale(0%);
}
</style>
