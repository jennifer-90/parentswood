<script setup>
import { ref, computed } from 'vue'
import { useForm, Head } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import villesData from '@/data/villes_belges.json'

const props = defineProps({ interets: Array })

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
    centres_interet: [],
})

const MAX_CI = 5
const selectedInteretCount = computed(() => form.centres_interet.length)
const isInterestDisabled = (id) =>
    selectedInteretCount.value >= MAX_CI && !form.centres_interet.includes(Number(id))


// Liste des villes
const villes = ref(villesData.villes)

// Dates min/max
const today = new Date().toISOString().split('T')[0]
const nextYear = new Date()
nextYear.setFullYear(nextYear.getFullYear() + 1)
const maxDate = nextYear.toISOString().split('T')[0]

// Validation du formulaire
const validateForm = () => {
    const errors = {}

    if (!form.name_event.trim()) {
        errors.name_event = 'Le nom de l\'événement est requis.'
    }

    if (!form.date) {
        errors.date = 'La date est requise.'
    } else {
        const selectedDate = new Date(form.date)
        const today = new Date()
        today.setHours(0, 0, 0, 0)

        if (selectedDate < today) {
            errors.date = 'La date doit être aujourd\'hui ou une date ultérieure.'
        }

        const maxDate = new Date()
        maxDate.setFullYear(maxDate.getFullYear() + 1)
        if (selectedDate > maxDate) {
            errors.date = 'La date ne peut pas être plus d\'un an dans le futur.'
        }
    }

    if (!form.hour) {
        errors.hour = 'L\'heure est requise.'
    }

    if (!form.location) {
        errors.location = 'Le lieu est requis.'
    }

    if (!form.min_person) {
        errors.min_person = 'Le nombre minimum de participants est requis.'
    } else if (form.min_person < 1) {
        errors.min_person = 'Le nombre minimum de participants doit être d\'au moins 1.'
    }

    if (!form.max_person) {
        errors.max_person = 'Le nombre maximum de participants est requis.'
    } else if (form.max_person < 1) {
        errors.max_person = 'Le nombre maximum de participants doit être d\'au moins 1.'
    } else if (parseInt(form.max_person) <= parseInt(form.min_person)) {
        errors.max_person = 'Le nombre maximum de participants doit être supérieur au minimum.'
    }

    if (form.picture_event) {
        const allowedTypes = ['image/jpeg', 'image/png', 'image/jpg']
        const maxSize = 2 * 1024 * 1024 // 2MB

        if (!allowedTypes.includes(form.picture_event.type)) {
            errors.picture_event = 'Le fichier doit être une image (JPEG, PNG, JPG).'
        } else if (form.picture_event.size > maxSize) {
            errors.picture_event = 'L\'image ne doit pas dépasser 2 Mo.'
        }
    }

    if ((form.centres_interet?.length || 0) > MAX_CI) {
        errors.centres_interet = `Vous pouvez sélectionner au maximum ${MAX_CI} centres d’intérêt.`
    }

    return errors
}



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

// État des erreurs de validation
const validationErrors = ref({})

// Réinitialiser les erreurs lors de la modification des champs
const resetFieldError = (field) => {
    if (validationErrors.value[field]) {
        validationErrors.value[field] = ''
    }
}

// Soumission avec validation
const submit = () => {
    // Validation côté client
    const errors = validateForm()

    if (Object.keys(errors).length > 0) {
        validationErrors.value = errors
        // Faire défiler jusqu'au premier champ en erreur
        const firstErrorField = Object.keys(errors)[0]
        const element = document.getElementById(firstErrorField)
        if (element) {
            element.scrollIntoView({ behavior: 'smooth', block: 'center' })
            element.focus()
        }
        return
    }

    // Si la validation est passée, on envoie le formulaire
    form.post(route('events.store'), {
        preserveScroll: true, forceFormData: true,
        onSuccess: () => {
            successMessage.value = '🎉 Événement créé avec succès !'
            form.reset()
            previewUrl.value = null
            validationErrors.value = {}
            // Faire défiler vers le haut pour voir le message de succès
            window.scrollTo({ top: 0, behavior: 'smooth' })
            setTimeout(() => successMessage.value = '', 4000)
        },
        onError: (errors) => {
            if (errors.errors) {
                // Gestion des erreurs côté serveur
                validationErrors.value = {}
                for (const [field, messages] of Object.entries(errors.errors)) {
                    validationErrors.value[field] = Array.isArray(messages) ? messages[0] : messages
                }

                // Faire défiler jusqu'au premier champ en erreur
                const firstErrorField = Object.keys(errors.errors)[0]
                const element = document.getElementById(firstErrorField)
                if (element) {
                    element.scrollIntoView({ behavior: 'smooth', block: 'center' })
                    element.focus()
                }
            } else {
                // Erreur générique
                alert("❌ Une erreur s'est produite lors de la création de l'événement.")
            }
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
                                        @input="resetFieldError('name_event')"
                                        type="text"
                                        id="name_event"
                                        :class="{
                                            'border-red-500': validationErrors.name_event,
                                            'border-gray-300': !validationErrors.name_event
                                        }"
                                        class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-[#59c4b4] focus:border-transparent transition duration-200"
                                        placeholder="Ex: Soirée jeux de société"
                                    />
                                    <p v-if="validationErrors.name_event" class="mt-1 text-sm text-red-600">
                                        {{ validationErrors.name_event }}
                                    </p>
                                </div>

                                <!-- Date et Heure -->
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="bg-white p-4 rounded-lg border border-gray-200 shadow-sm">
                                        <label for="date" class="block text-sm font-medium text-gray-700 mb-1">
                                            <i class="fa-regular fa-calendar-days mr-2 text-[#59c4b4]"></i>Date
                                        </label>
                                        <input
                                            v-model="form.date"
                                            @input="resetFieldError('date')"
                                            type="date"
                                            id="date"
                                            :class="{
                                                'border-red-500': validationErrors.date,
                                                'border-gray-300': !validationErrors.date
                                            }"
                                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-[#59c4b4] focus:border-transparent transition duration-200"
                                            :min="today"
                                            :max="maxDate"
                                        />
                                        <p v-if="validationErrors.date" class="mt-1 text-sm text-red-600">
                                            {{ validationErrors.date }}
                                        </p>
                                    </div>
                                    <div class="bg-white p-4 rounded-lg border border-gray-200 shadow-sm">
                                        <label for="hour" class="block text-sm font-medium text-gray-700 mb-1">
                                            <i class="fa-regular fa-clock mr-2 text-[#59c4b4]"></i>Heure
                                        </label>
                                        <input
                                            v-model="form.hour"
                                            @input="resetFieldError('hour')"
                                            type="time"
                                            id="hour"
                                            :class="{
                                                'border-red-500': validationErrors.hour,
                                                'border-gray-300': !validationErrors.hour
                                            }"
                                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-[#59c4b4] focus:border-transparent transition duration-200"
                                        />
                                        <p v-if="validationErrors.hour" class="mt-1 text-sm text-red-600">
                                            {{ validationErrors.hour }}
                                        </p>
                                    </div>
                                </div>

                                <!-- Lieu -->
                                <div class="bg-white p-4 rounded-lg border border-gray-200 shadow-sm">
                                    <label for="location" class="block text-sm font-medium text-gray-700 mb-1">
                                        <i class="fa-solid fa-location-dot mr-2 text-[#59c4b4]"></i>Ville
                                    </label>
                                    <select
                                        v-model="form.location"
                                        @change="resetFieldError('location')"
                                        id="location"
                                        :class="{
                                            'border-red-500': validationErrors.location,
                                            'border-gray-300': !validationErrors.location
                                        }"
                                        class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-[#59c4b4] focus:border-transparent transition duration-200"
                                    >
                                        <option value="" disabled selected>Sélectionnez une ville</option>
                                        <option v-for="ville in villes" :key="ville" :value="ville">{{ ville }}</option>
                                    </select>
                                    <p v-if="validationErrors.location" class="mt-1 text-sm text-red-600">
                                        {{ validationErrors.location }}
                                    </p>
                                </div>
                            </div>

                            <!-- Colonne de droite -->
                            <div class="space-y-6">
                                <!-- Particpants -->
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="bg-white p-4 rounded-lg border border-gray-200 shadow-sm">
                                        <label for="min_person" class="block text-sm font-medium text-gray-700 mb-1">
                                            <i class="fa-solid fa-user-group mr-2 text-[#59c4b4]"></i>Min. participants
                                        </label>
                                        <input
                                            v-model.number="form.min_person"
                                            @input="[resetFieldError('min_person'), resetFieldError('max_person')]"
                                            type="number"
                                            id="min_person"
                                            min="1"
                                            :class="{
                                                'border-red-500': validationErrors.min_person,
                                                'border-gray-300': !validationErrors.min_person
                                            }"
                                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-[#59c4b4] focus:border-transparent transition duration-200"
                                        />
                                        <p v-if="validationErrors.min_person" class="mt-1 text-sm text-red-600">
                                            {{ validationErrors.min_person }}
                                        </p>
                                    </div>
                                    <div class="bg-white p-4 rounded-lg border border-gray-200 shadow-sm">
                                        <label for="max_person" class="block text-sm font-medium text-gray-700 mb-1">
                                            <i class="fa-solid fa-users mr-2 text-[#59c4b4]"></i>Max. participants
                                        </label>
                                        <input
                                            v-model.number="form.max_person"
                                            @input="resetFieldError('max_person')"
                                            type="number"
                                            id="max_person"
                                            :min="form.min_person || 1"
                                            :class="{
                                                'border-red-500': validationErrors.max_person,
                                                'border-gray-300': !validationErrors.max_person
                                            }"
                                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-[#59c4b4] focus:border-transparent transition duration-200"
                                        />
                                        <p v-if="validationErrors.max_person" class="mt-1 text-sm text-red-600">
                                            {{ validationErrors.max_person }}
                                        </p>
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
                                    <p v-if="validationErrors.picture_event" class="mt-2 text-sm text-red-600">
                                        {{ validationErrors.picture_event }}
                                    </p>
                                </div>
                            </div>
                        </div>




                        <!-- Centres d’intérêt (checkboxes) -->
                        <div class="bg-white p-4 rounded-lg border border-gray-200 shadow-sm">
                            <div class="flex items-center justify-between mb-2">
                                <label class="block text-sm font-medium text-gray-700">
                                    <i class="fa-solid fa-star mr-2 text-[#59c4b4]"></i>Centres d'intérêt
                                </label>

                                <button
                                    type="button"
                                    @click="form.centres_interet = []"
                                    class="text-xs px-2 py-1 rounded border border-gray-300 hover:bg-gray-50"
                                >
                                    Effacer
                                </button>
                            </div>

                            <!-- Indication -->
                            <p class="mt-2 text-xs text-gray-500">
                                * Tu peux choisir jusqu’à {{ MAX_CI }} centres d’intérêt ({{ selectedInteretCount }}/{{ MAX_CI }}).
                            </p>

                            <div
                                :class="[
      'rounded-lg border p-3',
      validationErrors.centres_interet ? 'border-red-500' : 'border-gray-300'
    ]"
                            >
                                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2">
                                    <!-- Checkboxes -->
                                    <label
                                        v-for="ci in (props.interets ?? [])"
                                        :key="ci.id"
                                        class="flex items-center gap-2 rounded-md border px-3 py-2 hover:border-[#59c4b4] transition"
                                    >
                                        <input
                                            type="checkbox"
                                            :value="Number(ci.id)"
                                            v-model="form.centres_interet"
                                            :disabled="isInterestDisabled(ci.id)"
                                            class="h-4 w-4 text-[#59c4b4] border-gray-300 rounded focus:ring-[#59c4b4]"
                                            :class="isInterestDisabled(ci.id) ? 'opacity-50 cursor-not-allowed' : ''"
                                        />
                                        <span class="text-sm text-gray-700">{{ ci.name }}</span>
                                    </label>
                                </div>
                            </div>

                            <p v-if="validationErrors.centres_interet" class="mt-1 text-sm text-red-600">
                                {{ validationErrors.centres_interet }}
                            </p>

                            <!-- Chips des sélectionnés  -->
                            <div v-if="form.centres_interet.length" class="mt-2 flex flex-wrap gap-2">
    <span
        v-for="id in form.centres_interet"
        :key="'chip-'+id"
        class="inline-flex items-center text-xs bg-[#59c4b4]/10 text-[#59c4b4] px-2 py-1 rounded-full"
    >
      {{ (props.interets || []).find(ci => Number(ci.id) === Number(id))?.name }}
    </span>
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
                            <p v-if="validationErrors.description" class="mt-1 text-sm text-red-600">
                                {{ validationErrors.description }}
                            </p>
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
