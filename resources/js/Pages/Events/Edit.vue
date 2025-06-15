<script setup>
import { ref, onMounted } from 'vue'
import { useForm } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import villesData from '@/data/villes_belges.json'

const props = defineProps({
    event: Object,
})

const villes = ref(villesData.villes)

// Prévisualisation de l’image actuelle ou sélectionnée
const previewUrl = ref(props.event.picture_event ? `/storage/${props.event.picture_event}` : null)

// Dates min/max
const today = new Date().toISOString().split('T')[0]
const nextYear = new Date()
nextYear.setFullYear(nextYear.getFullYear() + 1)
const maxDate = nextYear.toISOString().split('T')[0]

// Formulaire prérempli
const form = useForm({
    name_event: props.event.name_event,
    description: props.event.description,
    date: props.event.date,
    hour: props.event.hour,
    location: props.event.location,
    min_person: props.event.min_person,
    max_person: props.event.max_person,
    picture_event: null, // on ne précharge pas le fichier
})

// Mise à jour du fichier sélectionné
const handleFileChange = (e) => {
    const file = e.target.files[0]
    if (file) {
        form.picture_event = file
        previewUrl.value = URL.createObjectURL(file)
    }
}

// Soumission du formulaire
const submit = () => {
    form.post(route('events.update', props.event.id), {
        forceFormData: true, // nécessaire pour gérer l’image
        onSuccess: () => {
            successMessage.value = '✅ Événement mis à jour avec succès !'
            setTimeout(() => successMessage.value = '', 4000)
        },
        onError: () => alert("❌ Une erreur s'est produite.")
    })
}

const successMessage = ref('')
</script>

<template>
    <AuthenticatedLayout>
        <div class="max-w-2xl mx-auto bg-white shadow-md rounded-xl p-8 mt-10">
            <h1 class="text-3xl font-bold text-teal-700 mb-6 text-center">✏️ Modifier l’événement</h1>

            <div v-if="successMessage" class="mb-6 bg-green-100 text-green-800 px-4 py-3 rounded text-center font-semibold shadow">
                {{ successMessage }}
            </div>

            <form @submit.prevent="submit" class="space-y-5">
                <!-- Nom -->
                <div>
                    <label for="name_event" class="block text-sm font-medium text-gray-700">Nom de l'événement</label>
                    <input v-model="form.name_event" type="text" id="name_event" class="input" required />
                </div>

                <!-- Description -->
                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
                    <textarea v-model="form.description" id="description" class="input" rows="4" />
                </div>

                <!-- Date & Heure -->
                <div class="flex gap-4">
                    <div class="flex-1">
                        <label for="date" class="block text-sm font-medium text-gray-700">Date</label>
                        <input
                            v-model="form.date"
                            type="date"
                            id="date"
                            class="input"
                            :min="today"
                            :max="maxDate"
                            required
                        />
                    </div>
                    <div class="flex-1">
                        <label for="hour" class="block text-sm font-medium text-gray-700">Heure</label>
                        <input v-model="form.hour" type="time" id="hour" class="input" required />
                    </div>
                </div>

                <!-- Lieu -->
                <div>
                    <label for="location" class="block text-sm font-medium text-gray-700">Lieu</label>
                    <select v-model="form.location" id="location" class="input" required>
                        <option disabled value="">-- Sélectionne une ville --</option>
                        <option v-for="ville in villes" :key="ville" :value="ville">{{ ville }}</option>
                    </select>
                </div>

                <!-- Participants -->
                <div class="flex gap-4">
                    <div class="flex-1">
                        <label for="min_person" class="block text-sm font-medium text-gray-700">Participants min.</label>
                        <input v-model="form.min_person" type="number" min="1" id="min_person" class="input" required />
                    </div>
                    <div class="flex-1">
                        <label for="max_person" class="block text-sm font-medium text-gray-700">Participants max.</label>
                        <input v-model="form.max_person" type="number" min="1" id="max_person" class="input" required />
                    </div>
                </div>

                <!-- Image -->
                <div>
                    <label for="picture_event" class="block text-sm font-medium text-gray-700">Changer l’image</label>
                    <input
                        type="file"
                        @change="handleFileChange"
                        id="picture_event"
                        class="input"
                        accept="image/*"
                    />
                    <div v-if="previewUrl" class="mt-4">
                        <p class="text-sm text-gray-500 mb-2">
                            Aperçu de l’image (la nouvelle si modifiée, sinon l’actuelle) :
                        </p>
                        <img :src="previewUrl" alt="Prévisualisation" class="rounded-lg shadow-md max-h-64 object-cover" />
                    </div>

                </div>

                <!-- Bouton -->
                <div class="text-center pt-4">
                    <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-full hover:bg-blue-700 shadow font-semibold">
                        💾 Sauvegarder les modifications
                    </button>
                </div>
            </form>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.input {
    width: 100%;
    border: 1px solid #ccc;
    padding: 0.6rem;
    border-radius: 0.5rem;
    font-size: 0.95rem;
    background-color: #fff;
    transition: border-color 0.2s;
}
.input:focus {
    outline: none;
    border-color: #3b82f6; /* blue-500 */
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2);
}
</style>
