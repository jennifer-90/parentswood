<script setup>
import { Link, router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import villes from '@/data/villes_belges.json'
import { defineProps, ref, watch } from 'vue'

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

    router.get(route('events.index'), params, { preserveState: true })
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
        <div class="page-all">

            <!-- En-tête -->
            <div class="flex flex-col items-center mb-10">
                <h1 class="text-3xl font-bold text-teal-700 mb-2">🌱 Bienvenue sur les événements ParentsWood</h1>
                <p class="text-gray-600 text-center max-w-xl mb-6">
                    Participe à des moments uniques avec d’autres parents solos. Découvre les événements proches de chez toi,
                    ou propose ton propre événement en quelques clics.
                </p>
                <Link
                    :href="route('events.create')"
                    class="bg-teal-600 text-white px-6 py-2 rounded hover:bg-teal-700 transition shadow"
                >
                    + Créer un événement
                </Link>
            </div>

            <!-- Filtres -->
            <div class="filters-wrapper border border-gray-200 rounded-xl shadow-sm p-4 mb-12 mx-auto max-w-6xl flex flex-wrap justify-center items-end gap-3">
                <select v-model="selectedVille" class="filter-input w-40">
                    <option value="">Toutes les villes</option>
                    <option v-for="ville in villes.villes" :key="ville" :value="ville">{{ ville }}</option>
                </select>

                <input type="date" v-model="selectedDate" class="filter-input w-40"/>

                <select v-model="selectedFilter" class="filter-input w-40">
                    <option value="">Tous les événements</option>
                    <option value="week">📆 Cette semaine</option>
                    <option value="month">🗓️ Ce mois-ci</option>
                </select>

                <select v-model="selectedInteret" class="filter-input w-40">
                    <option disabled value="">Centres d’intérêt</option>
                    <option>Sport</option>
                    <option>Culture</option>
                    <option>Sortie enfants</option>
                    <option>Relaxation</option>
                </select>

                <button
                    @click="resetFilters"
                    class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded text-sm font-medium transition"
                >
                    🔄 Réinitialiser
                </button>
            </div>

            <!-- Événements à venir -->
            <section class="mb-16">
                <h2 class="text-xl font-semibold text-gray-800 mb-5 text-center">~~ Événements à venir ~~</h2>

                <div v-if="props.upcomingEvents.data.length" class="flex flex-wrap justify-center gap-6">
                    <Link
                        v-for="event in props.upcomingEvents.data"
                        :key="event.id"
                        :href="route('events.show', event.id)"
                        class="block w-[280px] bg-white border border-gray-200 rounded-xl shadow-sm hover:shadow-md transition cursor-pointer"
                    >
                        <img
                            :src="event.picture_event ? '/storage/' + event.picture_event : defaultImage"
                            alt="Image événement"
                            class="w-full h-36 object-cover rounded-t-xl"
                        />
                        <div class="p-4">
                            <p class="text-sm font-bold text-gray-700 uppercase underline mb-1">
                                >> {{ new Date(event.date).toLocaleDateString('fr-FR', { month: 'long', year: 'numeric' }) }}
                            </p>

                            <h3 class="text-base font-semibold text-teal-700 truncate">{{ event.name_event }}</h3>
                            <p class="text-sm text-gray-500 mt-1">
                                🗓️ {{ new Date(event.date).toLocaleDateString('fr-BE') }} à 🕑 {{ event.hour.slice(0, 5) }}
                            </p>
                            <p class="text-sm text-gray-500 mt-1">📍 {{ event.location }}</p>
                            <p class="text-sm text-gray-600 mt-2 line-clamp-2">{{ event.description }}</p>
                        </div>

                    </Link>
                </div>


                <p v-else class="text-gray-500 italic text-center mt-6">
                    Pas encore d'évènements à venir.<br>
                    <Link class="text-teal-600 hover:underline font-medium" :href="route('events.create')">
                        Veux-tu en créer un ?
                    </Link>
                </p>
            </section>

            <!-- Pagination -->
            <div class="mt-10 flex justify-center gap-2">
                <Link
                    v-if="props.upcomingEvents.prev_page_url"
                    :href="props.upcomingEvents.prev_page_url"
                    class="px-4 py-2 border rounded text-sm text-gray-700 hover:bg-gray-200"
                >← Précédent</Link>

                <span class="px-4 py-2 text-sm text-gray-500">
          Page {{ props.upcomingEvents.current_page }} sur {{ props.upcomingEvents.last_page }}
        </span>

                <Link
                    v-if="props.upcomingEvents.next_page_url"
                    :href="props.upcomingEvents.next_page_url"
                    class="px-4 py-2 border rounded text-sm text-gray-700 hover:bg-gray-200"
                >Suivant →</Link>
            </div>

            <br><hr><br>

            <!-- Événements passés -->
            <section>
                <h2 class="text-xl font-semibold text-gray-800 mb-5 text-center">~~ Événements passés ~~</h2>
                <div v-if="props.pastEvents.length" class="flex flex-wrap justify-center gap-6">
                    <Link
                        v-for="event in props.pastEvents"
                        :key="event.id"
                        :href="route('events.show', event.id)"
                        class="block w-[280px] bg-gray-100 border border-gray-300 rounded-xl shadow-sm hover:shadow-md transition cursor-pointer"
                    >
                        <img
                            :src="event.picture_event ? '/storage/' + event.picture_event : defaultImage"
                            alt="Image événement"
                            class="w-full h-36 object-cover rounded-t-xl grayscale"
                        />
                        <div class="p-4">
                            <h3 class="text-base font-semibold text-gray-700 truncate">{{ event.name_event }}</h3>
                            <p class="text-sm text-gray-500 mt-1">
                                {{ new Date(event.date).toLocaleDateString('fr-BE') }} à {{ event.hour.slice(0, 5) }}
                            </p>
                        </div>
                    </Link>
                </div>
                <p v-else class="text-gray-400 italic text-center mt-6">
                    Aucun événement passé pour le moment.
                </p>
            </section>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.page-all {
    background: #f9f5f2;
    padding: 30px 20px;
    border-radius: 10px;
}

.filter-input {
    @apply border border-gray-300 rounded px-3 py-2 bg-white focus:outline-none focus:ring-2 focus:ring-teal-500;
}

.filters-wrapper {
    background: #fffdfa;
}
</style>


