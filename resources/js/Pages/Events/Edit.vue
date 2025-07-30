<script setup>
import { ref, onMounted } from 'vue'
import { useForm, router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import villesData from '@/data/villes_belges.json'
import { Head } from '@inertiajs/vue3'

// Liste des villes
const villes = ref(villesData.villes)

// Props
const props = defineProps({
    event: Object,
    errors: Object,
})

// Dates min/max
const today = new Date().toISOString().split('T')[0]
const nextYear = new Date()
nextYear.setFullYear(nextYear.getFullYear() + 1)
const maxDate = nextYear.toISOString().split('T')[0]

// Formulaire
const form = useForm({
    name_event: props.event.name_event,
    description: props.event.description,
    date: props.event.date,
    hour: props.event.hour.substring(0, 5), // Format HH:MM
    location: props.event.location,
    min_person: props.event.min_person,
    max_person: props.event.max_person,
    picture_event: null,
    _method: 'PUT',
})

// État pour prévisualisation et message de succès
const previewUrl = ref(props.event.image ? `/storage/${props.event.image}` : null)
const successMessage = ref('')

// Gestion de l'image
const handleFileChange = (e) => {
    const file = e.target.files[0]
    if (file) {
        form.picture_event = file
        previewUrl.value = URL.createObjectURL(file)
    }
}

// Soumission
const submit = () => {
    form.post(route('events.update', props.event.id), {
        onSuccess: () => {
            successMessage.value = '✅ Événement mis à jour avec succès !'
            setTimeout(() => successMessage.value = '', 4000)
        },
        onError: () => {
            alert("❌ Une erreur s'est produite lors de la mise à jour.")
        }
    })
}

// Suppression de l'événement
const deleteEvent = () => {
    if (confirm('Êtes-vous sûr de vouloir supprimer cet événement ? Cette action est irréversible.')) {
        router.delete(route('events.destroy', props.event.id), {
            onSuccess: () => {
                router.visit(route('events.index'))
            }
        })
    }
}
</script>

<template>
    <Head :title="`Modifier ${event.name_event}`" />

    <AuthenticatedLayout>
        <div class="py-4 bg-[#f9f5f2] min-h-screen">
            <div class="mx-auto w-full px-3 sm:px-5">
                <div class="bg-white shadow-lg rounded-lg p-4 sm:p-6">
                    <!-- En-tête avec icône -->
                    <div class="bg-transparent border-2 border-[#59c4b4] rounded-lg p-4 mb-8" style="background-color: rgba(89, 196, 180, 0.05);">
                        <div class="flex items-center justify-center gap-3">
                            <div class="bg-[#59c4b4] text-white rounded-full p-3">
                                <i class="fa-solid fa-pen-to-square text-xl"></i>
                            </div>
                            <h1 class="text-2xl font-bold text-gray-800">Modifier l'événement</h1>
                        </div>
                        <p class="mt-2 text-center text-gray-600">Mettez à jour les détails de votre événement</p>
                    </div>

                    <!-- Message de succès -->
                    <div v-if="successMessage" class="mb-6 bg-green-100 border border-green-200 text-green-700 px-4 py-3 rounded-lg text-center font-semibold shadow-sm">
                        {{ successMessage }}
                    </div>

                    <form @submit.prevent="submit" class="space-y-6 max-w-3xl mx-auto">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Colonne de gauche -->
                            <div class="space-y-6">
                                <!-- Nom de l'événement -->
                                <div class="bg-white p-4 rounded-lg border border-gray-200 shadow-sm">
                                    <label for="name_event" class="block text-sm font-medium text-gray-700 mb-1">
                                        <i class="fa-solid fa-heading mr-2 text-[#59c4b4]"></i>Nom de l'événement
                                    </label>
                                    <input
                                        v-model="form.name_event"
                                        type="text"
                                        id="name_event"
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#59c4b4] focus:border-transparent transition duration-200"
                                        placeholder="Ex: Soirée jeux de société"
                                        required
                                    />
                                    <p v-if="errors.name_event" class="mt-1 text-sm text-red-600">{{ errors.name_event }}</p>
                                </div>

                                <!-- Date et Heure -->
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="bg-white p-4 rounded-lg border border-gray-200 shadow-sm">
                                        <label for="date" class="block text-sm font-medium text-gray-700 mb-1">
                                            <i class="fa-regular fa-calendar-days mr-2 text-[#59c4b4]"></i>Date
                                        </label>
                                        <input
                                            v-model="form.date"
                                            type="date"
                                            id="date"
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#59c4b4] focus:border-transparent transition duration-200"
                                            :min="today"
                                            :max="maxDate"
                                            required
                                        />
                                        <p v-if="errors.date" class="mt-1 text-sm text-red-600">{{ errors.date }}</p>
                                    </div>
                                    <div class="bg-white p-4 rounded-lg border border-gray-200 shadow-sm">
                                        <label for="hour" class="block text-sm font-medium text-gray-700 mb-1">
                                            <i class="fa-regular fa-clock mr-2 text-[#59c4b4]"></i>Heure
                                        </label>
                                        <input
                                            v-model="form.hour"
                                            type="time"
                                            id="hour"
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#59c4b4] focus:border-transparent transition duration-200"
                                            required
                                        />
                                        <p v-if="errors.hour" class="mt-1 text-sm text-red-600">{{ errors.hour }}</p>
                                    </div>
                                </div>

                                <!-- Lieu -->
                                <div class="bg-white p-4 rounded-lg border border-gray-200 shadow-sm">
                                    <label for="location" class="block text-sm font-medium text-gray-700 mb-1">
                                        <i class="fa-solid fa-location-dot mr-2 text-[#59c4b4]"></i>Ville
                                    </label>
                                    <select
                                        v-model="form.location"
                                        id="location"
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#59c4b4] focus:border-transparent transition duration-200"
                                        required
                                    >
                                        <option value="" disabled>Sélectionnez une ville</option>
                                        <option v-for="ville in villes" :key="ville" :value="ville">{{ ville }}</option>
                                    </select>
                                    <p v-if="errors.location" class="mt-1 text-sm text-red-600">{{ errors.location }}</p>
                                </div>
                            </div>

                            <!-- Colonne de droite -->
                            <div class="space-y-6">
                                <!-- Taille du groupe -->
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="bg-white p-4 rounded-lg border border-gray-200 shadow-sm">
                                        <label for="min_person" class="block text-sm font-medium text-gray-700 mb-1">
                                            <i class="fa-solid fa-user-group mr-2 text-[#59c4b4]"></i>Min. participants
                                        </label>
                                        <input
                                            v-model.number="form.min_person"
                                            type="number"
                                            id="min_person"
                                            min="1"
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#59c4b4] focus:border-transparent transition duration-200"
                                        />
                                        <p v-if="errors.min_person" class="mt-1 text-sm text-red-600">{{ errors.min_person }}</p>
                                    </div>
                                    <div class="bg-white p-4 rounded-lg border border-gray-200 shadow-sm">
                                        <label for="max_person" class="block text-sm font-medium text-gray-700 mb-1">
                                            <i class="fa-solid fa-users mr-2 text-[#59c4b4]"></i>Max. participants
                                        </label>
                                        <input
                                            v-model.number="form.max_person"
                                            type="number"
                                            id="max_person"
                                            :min="form.min_person || 1"
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#59c4b4] focus:border-transparent transition duration-200"
                                        />
                                        <p v-if="errors.max_person" class="mt-1 text-sm text-red-600">{{ errors.max_person }}</p>
                                    </div>
                                </div>

                                <!-- Téléchargement de l'image -->
                                <div class="bg-white p-4 rounded-lg border border-gray-200 shadow-sm">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        <i class="fa-regular fa-image mr-2 text-[#59c4b4]"></i>Image de l'événement
                                    </label>
                                    <div class="mt-1 flex items-center">
                                        <label class="cursor-pointer">
                                            <span class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-lg shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#59c4b4]">
                                                <i class="fa-solid fa-upload mr-2"></i>
                                                {{ previewUrl ? 'Changer l\'image' : 'Télécharger une image' }}
                                            </span>
                                            <input type="file" class="hidden" @change="handleFileChange" accept="image/*">
                                        </label>
                                    </div>
                                    <!-- Aperçu de l'image -->
                                    <div v-if="previewUrl" class="mt-4">
                                        <p class="text-sm text-gray-500 mb-2">Aperçu :</p>
                                        <img :src="previewUrl" alt="Aperçu de l'image" class="h-32 w-full object-cover rounded-lg border border-gray-200">
                                    </div>
                                    <p v-if="errors.picture_event" class="mt-1 text-sm text-red-600">{{ errors.picture_event }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Description -->
                        <div class="bg-white p-4 rounded-lg border border-gray-200 shadow-sm">
                            <label for="description" class="block text-sm font-medium text-gray-700 mb-1">
                                <i class="fa-regular fa-comment-dots mr-2 text-[#59c4b4]"></i>Description
                            </label>
                            <textarea
                                v-model="form.description"
                                id="description"
                                rows="4"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#59c4b4] focus:border-transparent transition duration-200"
                                placeholder="Décrivez votre événement en détail..."
                            ></textarea>
                            <p v-if="errors.description" class="mt-1 text-sm text-red-600">{{ errors.description }}</p>
                        </div>

                        <!-- Boutons d'action -->
                        <div class="flex flex-col sm:flex-row justify-between gap-3 pt-4 border-t border-gray-200">
                            <div class="flex flex-col sm:flex-row gap-3">
                                <a
                                    :href="route('events.show', event.id)"
                                    class="px-4 py-2 border border-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition-all duration-200 flex items-center justify-center"
                                >
                                    <i class="fa-solid fa-eye mr-2"></i>
                                    Voir l'événement
                                </a>
                                <button
                                    type="button"
                                    @click="deleteEvent"
                                    class="px-4 py-2 border border-red-300 text-red-700 font-medium rounded-lg hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-all duration-200 flex items-center justify-center"
                                >
                                    <i class="fa-solid fa-trash-alt mr-2"></i>
                                    Supprimer
                                </button>
                            </div>
                            <div class="flex flex-col sm:flex-row gap-3">
                                <a
                                    :href="route('events.index')"
                                    class="px-4 py-2 border border-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition-all duration-200 flex items-center justify-center"
                                >
                                    <i class="fa-solid fa-arrow-left mr-2"></i>
                                    Retour
                                </a>
                                <button
                                    type="submit"
                                    class="px-6 py-2.5 bg-[#59c4b4] text-white font-medium rounded-lg hover:bg-[#4db3a3] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#59c4b4] transition-all duration-200 flex items-center justify-center"
                                    :disabled="form.processing"
                                    :class="{ 'opacity-50 cursor-not-allowed': form.processing }"
                                >
                                    <i class="fa-solid fa-save mr-2"></i>
                                    {{ form.processing ? 'Enregistrement...' : 'Enregistrer les modifications' }}
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
/* Styles spécifiques pour cette page */
input[type="date"]::-webkit-calendar-picker-indicator,
input[type="time"]::-webkit-calendar-picker-indicator {
    filter: invert(0.5);
    cursor: pointer;
}

input[type="file"] {
    border: 0;
    clip: rect(0, 0, 0, 0);
    height: 1px;
    overflow: hidden;
    padding: 0;
    position: absolute !important;
    white-space: nowrap;
    width: 1px;
}
</style>
