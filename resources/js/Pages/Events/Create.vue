<script setup>
import { ref } from 'vue'
import { useForm } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import villesData from '@/data/villes_belges.json'

// Liste des villes
const villes = ref(villesData.villes)

// Dates min/max
const today = new Date().toISOString().split('T')[0]
const nextYear = new Date()
nextYear.setFullYear(nextYear.getFullYear() + 1)
const maxDate = nextYear.toISOString().split('T')[0]

// Formulaire
const form = useForm({
    name_event: '',
    description: '',
    date: '',
    hour: '',
    location: '',
    min_person: 1,
    max_person: '',
    picture_event: null,
})

// État pour prévisualisation et message de confirmation
const previewUrl = ref(null)
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
    form.post(route('events.store'), {
        onSuccess: () => {
            successMessage.value = '🎉 Événement créé avec succès !'
            form.reset()
            previewUrl.value = null
            setTimeout(() => successMessage.value = '', 4000)
        },
        onError: () => {
            alert("❌ Une erreur s'est produite.")
        }
    })
}
</script>

<template>
    <Head title="Créer un événement" />

    <AuthenticatedLayout>
        <div class="py-4 bg-[#f9f5f2] min-h-screen">
            <div class="mx-auto w-full px-3 sm:px-5">
                <div class="bg-white shadow-lg rounded-lg p-4 sm:p-6">
                    <!-- En-tête avec icône -->
                    <div class="bg-transparent border-2 border-[#59c4b4] rounded-lg p-4 mb-8" style="background-color: rgba(89, 196, 180, 0.05);">
                        <div class="flex items-center justify-center gap-3">
                            <div class="bg-[#59c4b4] text-white rounded-full p-3">
                                <i class="fa-solid fa-calendar-plus text-xl"></i>
                            </div>
                            <h1 class="text-2xl font-bold text-gray-800">Créer un nouvel événement</h1>
                        </div>
                        <p class="mt-2 text-center text-gray-600">Remplissez les détails de votre événement</p>
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
                        </div>

                        <!-- Boutons d'action -->
                        <div class="flex flex-col sm:flex-row justify-end gap-3 pt-4 border-t border-gray-200">
                            <a
                                :href="route('events.index')"
                                class="px-6 py-2.5 border border-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition-all duration-200 flex items-center justify-center"
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
                                <i class="fa-solid fa-plus-circle mr-2"></i>
                                {{ form.processing ? 'Création en cours...' : 'Créer l\'événement' }}
                            </button>
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
