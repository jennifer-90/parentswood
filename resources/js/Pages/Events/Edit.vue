<script setup>
import { ref, computed, watch, reactive } from 'vue'
import { useForm, Head } from '@inertiajs/vue3'
import axios from 'axios'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import ConfirmModal from '@/Components/ConfirmModal.vue'
import villesData from '@/data/villes_belges.json' // remplacer par une API externe si souhaité

const route = window.route

/*=====================================================================================*/

/** Normalise n'importe quelle forme d'objets vers des IDs numériques */
const normalizeIds = (arr) => {
    if (!Array.isArray(arr)) return []
    return arr
        .map((x) => {
            if (typeof x === 'number' || typeof x === 'string') return Number(x)
            if (x?.id != null) return Number(x.id)
            if (x?.interet_id != null) return Number(x.interet_id)
            if (x?.interest_id != null) return Number(x.interest_id)
            if (x?.centre_interet_id != null) return Number(x.centre_interet_id)
            if (x?.pivot?.interet_id != null) return Number(x.pivot.interet_id)
            if (x?.pivot?.centre_interet_id != null) return Number(x.pivot.centre_interet_id)
            return NaN
        })
        .filter(Number.isFinite)
}

/** Récupère le premier tableau d'intérêts non vide de l'event */
const getSelectedIdsFromEvent = (ev) => {
    const candidate =
        (Array.isArray(ev?.centres_interet) && ev.centres_interet.length && ev.centres_interet) ||
        (Array.isArray(ev?.centresInteret) && ev.centresInteret.length && ev.centresInteret) ||
        (Array.isArray(ev?.interets) && ev.interets.length && ev.interets) ||
        (Array.isArray(ev?.interests) && ev.interests.length && ev.interests) ||
        []
    return normalizeIds(candidate)
}

// Villes depuis un JSON local (peut devenir une API)
const villes = ref(villesData.villes)

// Props venant d'Inertia
const props = defineProps({
    event: Object,
    errors: Object,
    interets: {type: Array, default: () => []},
})

// Dates min/max (sans piège UTC)
const toYmd = (d) =>
    `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`

const todayDateObj = new Date()
const nextYearDateObj = new Date()
nextYearDateObj.setFullYear(nextYearDateObj.getFullYear() + 1)
const today = toYmd(todayDateObj)
const maxDate = toYmd(nextYearDateObj)

// État UI
const validationErrors = ref({})
const isLoading = ref(false)
const successMessage = ref('')
const errorMessage = ref('')

// Toasts
const showSuccessMessage = (message) => {
    successMessage.value = message
    errorMessage.value = ''
    setTimeout(() => (successMessage.value = ''), 5000)
}
const showErrorMessage = (message) => {
    errorMessage.value = message
    successMessage.value = ''
    window.scrollTo({top: 0, behavior: 'smooth'})
    setTimeout(() => (errorMessage.value = ''), 10000)
}

// Scroll vers le 1er champ en erreur
const scrollToFirstError = () => {
    const firstError = Object.keys(validationErrors.value).find((key) => validationErrors.value[key])
    if (firstError) {
        const element = document.getElementById(firstError)
        if (element) {
            element.scrollIntoView({behavior: 'smooth', block: 'center'})
            element.focus()
        }
    }
}

const onlyDate = (d) => {
    if (!d) return ''
    const s = String(d)
    return s.includes('T') ? s.split('T')[0] : s.split(' ')[0]
}
const onlyHm = (h) => (h ? String(h).slice(0, 5) : '')

// Formulaire (pré-rempli)
const form = useForm({
    name_event: props.event.name_event || '',
    description: props.event.description || '',
    date: onlyDate(props.event?.date),
    hour: onlyHm(props.event?.hour),
    location: props.event.location || '',
    min_person: props.event.min_person || 1,
    max_person: props.event.max_person || '',
    picture_event: null, // on ne précharge pas de fichier
    _method: 'PUT', // on envoie via POST + spoof PUT
    centres_interet: [],
})

// Remplir les centres d'intérêt dès que l'event est dispo
watch(
    () => props.event,
    (ev) => {
        form.date = onlyDate(ev?.date)
        form.hour = onlyHm(ev?.hour)
        form.centres_interet = getSelectedIdsFromEvent(ev)
    },
    {immediate: true}
)

// Limite centres d'intérêt
const MAX_CI = 5
const selectedInteretCount = computed(() => form.centres_interet.length)
const isInterestDisabled = (id) => selectedInteretCount.value >= MAX_CI && !form.centres_interet.includes(Number(id))

// --- Helpers sécurité pour la modale (v-html) ---
const escapeHtml = (s) =>
    String(s ?? '').replace(/[&<>"]/g, (c) => ({'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;'}[c]))

// Validation client
const validateForm = () => {
    const errors = {}
    let isValid = true

    if (!form.name_event || !form.name_event.trim()) {
        errors.name_event = 'Le nom est requis.'
        isValid = false
    }

    if (!form.date) {
        errors.date = 'La date est requise.'
        isValid = false
    } else {
        const selectedDate = new Date(form.date)
        const t = new Date()
        t.setHours(0, 0, 0, 0)
        if (selectedDate < t) {
            errors.date = 'La date ne peut pas être dans le passé.'
            isValid = false
        }
    }

    if (!form.hour) {
        errors.hour = "L'heure est requise."
        isValid = false
    }

    if (!form.location) {
        errors.location = 'Le lieu est requis.'
        isValid = false
    }

    const min = Number(form.min_person)
    const max = Number(form.max_person)

    if (!Number.isFinite(min) || min < 1) {
        errors.min_person = 'Minimum 1 participant.'
        isValid = false
    } else if (min > 100) {
        errors.min_person = 'Le nombre maximum de participants est de 100.'
        isValid = false
    }

    if (!Number.isFinite(max) || max < 1) {
        errors.max_person = 'Le nombre maximum de participants est requis.'
        isValid = false
    } else if (max <= min) {
        errors.max_person = 'Doit être supérieur au nombre minimum de participants.'
        isValid = false
    } else if (max > 100) {
        errors.max_person = 'Le nombre maximum de participants est de 100.'
        isValid = false
    }

    if (form.picture_event && form.picture_event instanceof File) {
        const allowedTypes = ['image/jpeg', 'image/png']
        if (!allowedTypes.includes(form.picture_event.type)) {
            errors.picture_event = 'Le format du fichier doit être JPEG ou PNG.'
            isValid = false
        }
        if (form.picture_event.size > 2 * 1024 * 1024) {
            errors.picture_event = "L'image ne doit pas dépasser 2 Mo."
            isValid = false
        }
    }

    validationErrors.value = errors
    if (!isValid) scrollToFirstError()
    return isValid
}

// Reset erreur champ
const resetFieldError = (field) => {
    if (validationErrors.value[field]) delete validationErrors.value[field]
}

// Gestion image
const imageChanged = ref(false)
const previewUrl = ref(props.event.picture_event ? `/storage/${props.event.picture_event}` : null)
const currentImageUrl = ref(props.event.picture_event ? `/storage/${props.event.picture_event}` : null)

const handleFileChange = (e) => {
    const file = e.target.files?.[0]
    if (!file) return
    const allowedTypes = ['image/jpeg', 'image/png']
    if (!allowedTypes.includes(file.type)) {
        validationErrors.value.picture_event = 'Veuillez télécharger une image valide (JPEG ou PNG).'
        e.target.value = ''
        return
    }
    if (file.size > 2 * 1024 * 1024) {
        validationErrors.value.picture_event = "L'image ne doit pas dépasser 2 Mo."
        e.target.value = ''
        return
    }
    if (validationErrors.value.picture_event) delete validationErrors.value.picture_event
    form.picture_event = file
    imageChanged.value = true
    const reader = new FileReader()
    reader.onload = (ev) => (previewUrl.value = ev.target?.result)
    reader.readAsDataURL(file)
}

const removeCurrentImage = async () => {
    const ok = await askConfirm({
        title: 'Supprimer l’image',
        message: `Voulez-vous vraiment supprimer l’image de « <strong>${escapeHtml(props.event?.name_event || 'cet événement')}</strong> » ?`,
        confirmLabel: 'Supprimer',
    })
    if (!ok) return

    form.picture_event = 'REMOVE_IMAGE'
    previewUrl.value = null
    imageChanged.value = true
}

const restoreImage = () => {
    form.picture_event = null
    previewUrl.value = currentImageUrl.value
    imageChanged.value = false
    const fileInput = document.getElementById('picture_event')
    if (fileInput) fileInput.value = ''
}

// Submit
const submit = async () => {
    if (!validateForm()) return

    isLoading.value = true
    validationErrors.value = {}

    const formData = new FormData()
    ;['name_event', 'description', 'date', 'hour', 'location', 'min_person', 'max_person'].forEach((field) => {
        const value = form[field]
        if (value !== null && value !== undefined && value !== '') {
            formData.append(field, value)
        }
    })

    ;(form.centres_interet || []).forEach((id) => formData.append('centres_interet[]', Number(id)))

    if (form.picture_event instanceof File) {
        formData.append('picture_event', form.picture_event, form.picture_event.name)
    } else if (form.picture_event === 'REMOVE_IMAGE') {
        formData.append('picture_event', 'REMOVE_IMAGE')
    }

    formData.append('_method', 'PUT')

    try {
        await axios.post(route('events.update', {event: props.event.id}), formData)
        successMessage.value = 'Événement mis à jour avec succès !'
        errorMessage.value = ''
        setTimeout(() => (window.location.href = route('events.index')), 1500)
    } catch (error) {
        console.error('Erreur lors de la mise à jour:', error)
        if (error.response?.status === 422) {
            const serverErrors = error.response?.data?.errors || {}
            const flat = {}
            Object.keys(serverErrors).forEach((key) => {
                const msgs = serverErrors[key]
                flat[key] = Array.isArray(msgs) ? msgs[0] : msgs
            })
            validationErrors.value = flat
            errorMessage.value = 'Veuillez corriger les erreurs dans le formulaire.'
            scrollToFirstError()
        } else {
            errorMessage.value = 'Une erreur est survenue lors de la mise à jour.'
            window.scrollTo({top: 0, behavior: 'smooth'})
        }
    } finally {
        isLoading.value = false
    }
}

// Désactivation
const deactivateEvent = async () => {
    const ok = await askConfirm({
        title: 'Désactiver cet événement',
        message:
            "Êtes-vous sûr de vouloir désactiver cet événement ?<br>Il ne sera plus visible par les autres utilisateurs.",
        confirmLabel: 'Désactiver',
    })
    if (!ok) return

    try {
        isLoading.value = true
        const response = await axios.put(route('events.deactivate', props.event.id))
        if (response.data?.success) {
            showSuccessMessage(response.data.message || 'Événement désactivé.')
            setTimeout(() => (window.location.href = route('events.index')), 1500)
        } else {
            showErrorMessage(
                response.data?.message || "Une erreur est survenue lors de la désactivation de l'événement."
            )
        }
    } catch (error) {
        console.error("Erreur lors de la désactivation de l'événement:", error)
        showErrorMessage(
            error.response?.data?.message || "Une erreur est survenue lors de la désactivation de l'événement."
        )
    } finally {
        isLoading.value = false
    }
}

/* ========== Modale de confirmation / alerte ========== */
const modal = reactive({
    open: false,
    title: '',
    message: '',
    confirmLabel: 'Confirmer',
    cancelLabel: 'Annuler',
    showCancel: true,
    disabled: false,
})

let modalResolve = null

function askConfirm({
                        title = 'Confirmation',
                        message = '',
                        confirmLabel = 'Confirmer',
                        cancelLabel = 'Annuler',
                        disabled = false,
                    } = {}) {
    return new Promise((resolve) => {
        modal.title = title
        modal.message = message
        modal.confirmLabel = confirmLabel
        modal.cancelLabel = cancelLabel
        modal.showCancel = true
        modal.disabled = disabled
        modal.open = true
        modalResolve = resolve
    })
}

function showAlert({title = 'Information', message = '', okLabel = 'OK'} = {}) {
    return new Promise((resolve) => {
        modal.title = title
        modal.message = message
        modal.confirmLabel = okLabel
        modal.cancelLabel = ''
        modal.showCancel = false
        modal.disabled = false
        modal.open = true
        modalResolve = resolve
    })
}

function onModalConfirm() {
    modal.open = false
    if (modalResolve) {
        const r = modalResolve
        modalResolve = null
        r(true)
    }
}

function onModalCancel() {
    modal.open = false
    if (modalResolve) {
        const r = modalResolve
        modalResolve = null
        r(false)
    }
}
</script>

<template>
    <Head :title="`Modifier ${props.event?.name_event ?? ''}`"/>

    <AuthenticatedLayout>
        <!-- Overlay de chargement -->
        <div v-if="isLoading" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white p-6 rounded-lg shadow-xl">
                <div class="flex items-center">
                    <svg
                        class="animate-spin -ml-1 mr-3 h-8 w-8 text-[#59c4b4]"
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 24 24"
                    >
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path
                            class="opacity-75"
                            fill="currentColor"
                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                        ></path>
                    </svg>
                    <span class="text-lg font-medium text-gray-700">Enregistrement en cours...</span>
                </div>
            </div>
        </div>

        <!-- Toast succès -->
        <div v-if="successMessage" class="fixed top-4 right-4 z-50">
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                <span class="block sm:inline">{{ successMessage }}</span>
                <span class="absolute top-0 bottom-0 right-0 px-4 py-3" @click="successMessage = ''">
          <svg
              class="fill-current h-6 w-6 text-green-500"
              role="button"
              xmlns="http://www.w3.org/2000/svg"
              viewBox="0 0 20 20"
          ><title>Close</title><path
              d="M14.348 14.849a1.2 1.2 0 0 1-1.697 0L10 11.819l-2.651 3.029a1.2 1.2 0 1 1-1.697-1.697l2.758-3.15-2.759-3.152a1.2 1.2 0 1 1 1.697-1.697L10 8.183l2.651-3.031a1.2 1.2 0 1 1 1.697 1.697l-2.758 3.152 2.758 3.15a1.2 1.2 0 0 1 0 1.698z"
          /></svg>
        </span>
            </div>
        </div>

        <!-- Toast erreur -->
        <div v-if="errorMessage" class="fixed top-4 right-4 z-50">
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                <span class="block sm:inline">{{ errorMessage }}</span>
                <span class="absolute top-0 bottom-0 right-0 px-4 py-3" @click="errorMessage = ''">
          <svg
              class="fill-current h-6 w-6 text-red-500"
              role="button"
              xmlns="http://www.w3.org/2000/svg"
              viewBox="0 0 20 20"
          ><title>Close</title><path
              d="M14.348 14.849a1.2 1.2 0 0 1-1.697 0L10 11.819l-2.651 3.029a1.2 1.2 0 1 1-1.697-1.697l2.758-3.15-2.759-3.152a1.2 1.2 0 1 1 1.697-1.697L10 8.183l2.651-3.031a1.2 1.2 0 1 1 1.697 1.697l-2.758 3.152 2.758 3.15a1.2 1.2 0 0 1 0 1.698z"
          /></svg>
        </span>
            </div>
        </div>

        <div class="py-4 bg-[#f9f5f2] min-h-screen">
            <div class="mx-auto w-full px-3 sm:px-5">
                <div class="bg-white shadow-lg rounded-lg p-4 sm:p-6">
                    <!-- En-tête -->
                    <div class="bg-transparent border-2 border-[#59c4b4] rounded-lg p-4 mb-8"
                         style="background-color: rgba(89, 196, 180, 0.05);">
                        <div class="flex items-center justify-center gap-3">
                            <div class="bg-[#59c4b4] text-white rounded-full p-3">
                                <i class="fa-solid fa-pen-to-square text-xl"></i>
                            </div>
                            <h1 class="text-2xl font-bold text-gray-800">Modifier l'événement</h1>
                        </div>
                        <p class="mt-2 text-center text-gray-600">Mettez à jour les détails de votre événement</p>
                    </div>

                    <!-- Message de succès dans la carte -->
                    <div
                        v-if="successMessage"
                        class="mb-6 bg-green-100 border border-green-200 text-green-700 px-4 py-3 rounded-lg text-center font-semibold shadow-sm"
                    >
                        {{ successMessage }}
                    </div>

                    <!-- FORM -->
                    <form @submit.prevent="submit" class="space-y-6 max-w-3xl mx-auto">
                        <!-- Grille 2 colonnes -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Colonne gauche -->
                            <div class="space-y-6">
                                <!-- Nom -->
                                <div class="bg-white p-4 rounded-lg border border-gray-200 shadow-sm">
                                    <label for="name_event" class="block text-sm font-medium text-gray-700 mb-1">
                                        <i class="fa-solid fa-heading mr-2 text-[#59c4b4]"></i>Nom de l'événement
                                    </label>
                                    <input
                                        v-model="form.name_event"
                                        @input="resetFieldError('name_event')"
                                        type="text"
                                        id="name_event"
                                        :class="{ 'border-red-500': validationErrors.name_event, 'border-gray-300': !validationErrors.name_event }"
                                        class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-[#59c4b4] focus:border-transparent transition duration-200"
                                        placeholder="Ex: Soirée jeux de société"
                                    />
                                    <p v-if="validationErrors.name_event" class="mt-1 text-sm text-red-600">
                                        {{ validationErrors.name_event }}
                                    </p>
                                </div>

                                <!-- Date & Heure -->
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
                                            :class="{ 'border-red-500': validationErrors.date, 'border-gray-300': !validationErrors.date }"
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
                                            :class="{ 'border-red-500': validationErrors.hour, 'border-gray-300': !validationErrors.hour }"
                                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-[#59c4b4] focus:border-transparent transition duration-200"
                                        />
                                        <p v-if="validationErrors.hour" class="mt-1 text-sm text-red-600">
                                            {{ validationErrors.hour }}
                                        </p>
                                    </div>
                                </div>

                                <!-- Ville -->
                                <div class="bg-white p-4 rounded-lg border border-gray-200 shadow-sm">
                                    <label for="location" class="block text-sm font-medium text-gray-700 mb-1">
                                        <i class="fa-solid fa-location-dot mr-2 text-[#59c4b4]"></i>Ville
                                    </label>
                                    <select
                                        v-model="form.location"
                                        @change="resetFieldError('location')"
                                        id="location"
                                        :class="{ 'border-red-500': validationErrors.location, 'border-gray-300': !validationErrors.location }"
                                        class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-[#59c4b4] focus:border-transparent transition duration-200"
                                    >
                                        <option value="" disabled>Sélectionnez une ville</option>
                                        <option v-for="ville in villes" :key="ville" :value="ville">{{ ville }}</option>
                                    </select>
                                    <p v-if="validationErrors.location" class="mt-1 text-sm text-red-600">
                                        {{ validationErrors.location }}
                                    </p>
                                </div>
                            </div>

                            <!-- Colonne droite -->
                            <div class="space-y-6">
                                <!-- Min/Max -->
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
                                            :class="{ 'border-red-500': validationErrors.min_person, 'border-gray-300': !validationErrors.min_person }"
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
                                            :class="{ 'border-red-500': validationErrors.max_person, 'border-gray-300': !validationErrors.max_person }"
                                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-[#59c4b4] focus:border-transparent transition duration-200"
                                        />
                                        <p v-if="validationErrors.max_person" class="mt-1 text-sm text-red-600">
                                            {{ validationErrors.max_person }}
                                        </p>
                                    </div>
                                </div>

                                <!-- Image -->
                                <div class="bg-white p-4 rounded-lg border border-gray-200 shadow-sm">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        <i class="fa-solid fa-image mr-2 text-[#59c4b4]"></i>Image de l'événement
                                    </label>

                                    <div class="mt-1 flex items-center">
                                        <label
                                            for="picture_event"
                                            class="cursor-pointer bg-gray-50 hover:bg-gray-100 text-gray-700 py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#59c4b4]"
                                        >
                                            <i class="fa-solid fa-upload mr-2"></i>
                                            {{
                                                form.picture_event && form.picture_event.name ? form.picture_event.name : 'Télécharger une image'
                                            }}
                                        </label>

                                        <input id="picture_event" type="file" class="hidden"
                                               accept="image/jpeg,image/png" @change="handleFileChange"/>

                                        <button
                                            v-if="(previewUrl || currentImageUrl) && form.picture_event !== 'REMOVE_IMAGE'"
                                            @click="removeCurrentImage"
                                            type="button"
                                            class="ml-3 inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-red-700 bg-red-100 hover:bg-red-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500"
                                        >
                                            <i class="fa-solid fa-trash mr-1"></i>Supprimer
                                        </button>
                                        <button
                                            v-else-if="form.picture_event === 'REMOVE_IMAGE'"
                                            @click="restoreImage"
                                            type="button"
                                            class="ml-3 inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-green-700 bg-green-100 hover:bg-green-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
                                        >
                                            <i class="fa-solid fa-rotate-left mr-1"></i>Rétablir
                                        </button>
                                    </div>

                                    <p v-if="validationErrors.picture_event" class="mt-1 text-sm text-red-600">
                                        {{ validationErrors.picture_event }}
                                    </p>

                                    <!-- Aperçu -->
                                    <div v-if="previewUrl || (currentImageUrl && form.picture_event !== 'REMOVE_IMAGE')"
                                         class="mt-4">
                                        <p class="text-sm font-medium text-gray-700 mb-2">
                                            {{ previewUrl && imageChanged ? 'Nouvel aperçu :' : 'Image actuelle :' }}
                                        </p>
                                        <div class="relative">
                                            <img
                                                :src="previewUrl || currentImageUrl"
                                                :alt="props.event?.name_event || 'Image de l’événement'"
                                                class="h-48 w-full object-cover rounded-lg border border-gray-200"
                                            />
                                            <button
                                                v-if="currentImageUrl && !imageChanged"
                                                @click="removeCurrentImage"
                                                type="button"
                                                class="absolute top-2 right-2 bg-red-500 text-white p-1 rounded-full hover:bg-red-600 transition-colors"
                                                title="Supprimer cette image"
                                            >
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>
                                    </div>

                                    <div v-else-if="form.picture_event === 'REMOVE_IMAGE'"
                                         class="mt-4 p-3 bg-yellow-50 border border-yellow-200 rounded-md">
                                        <p class="text-sm text-yellow-700 flex items-center">
                                            <i class="fas fa-exclamation-triangle mr-2"></i>
                                            L'image sera supprimée lors de l'enregistrement des modifications.
                                            <button @click="restoreImage" type="button"
                                                    class="ml-2 text-yellow-700 hover:text-yellow-900 font-medium">
                                                Annuler
                                            </button>
                                        </p>
                                    </div>

                                    <div v-else
                                         class="mt-4 p-3 bg-gray-50 border border-gray-200 rounded-md text-sm text-gray-600">
                                        Aucune image pour cet événement.
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- /Grille 2 colonnes -->

                        <div id="centres_interet" class="bg-white p-4 rounded-lg border border-gray-200 shadow-sm">
                            <div class="flex items-center justify-between mb-2">
                                <label class="block text-sm font-medium text-gray-700">
                                    <i class="fa-solid fa-star mr-2 text-[#59c4b4]"></i>Centres d'intérêt
                                </label>
                                <button type="button" @click="form.centres_interet = []"
                                        class="text-xs px-2 py-1 rounded border border-gray-300 hover:bg-gray-50">
                                    Effacer
                                </button>
                            </div>

                            <p class="mt-2 text-xs text-gray-500">* Tu peux choisir jusqu’à {{ MAX_CI }} centres
                                d’intérêt ({{ selectedInteretCount }}/{{ MAX_CI }}).</p>

                            <div
                                :class="['rounded-lg border p-3', validationErrors.centres_interet ? 'border-red-500' : 'border-gray-300']">
                                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2">
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

                            <!-- Chips -->
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
                                @input="resetFieldError('description')"
                                id="description"
                                rows="4"
                                :class="{ 'border-red-500': validationErrors.description, 'border-gray-300': !validationErrors.description }"
                                class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-[#59c4b4] focus:border-transparent transition duration-200"
                                placeholder="Décrivez votre événement en détail..."
                            ></textarea>
                            <p v-if="validationErrors.description" class="mt-1 text-sm text-red-600">
                                {{ validationErrors.description }}
                            </p>
                        </div>

                        <!-- Boutons d'action -->
                        <div class="flex flex-col sm:flex-row justify-between gap-3 pt-4 border-t border-gray-200">
                            <div class="flex flex-col sm:flex-row gap-3">
                                <a
                                    :href="route('events.show', props.event.id)"
                                    class="px-4 py-2 border border-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition-all duration-200 flex items-center justify-center"
                                >
                                    <i class="fa-solid fa-eye mr-2"></i>Voir l'événement
                                </a>
                                <button
                                    type="button"
                                    @click="deactivateEvent"
                                    class="px-4 py-2 border border-red-300 text-red-700 font-medium rounded-lg hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-all duration-200 flex items-center justify-center"
                                >
                                    <i class="fa-solid fa-eye-slash mr-2"></i>Désactiver
                                </button>
                            </div>

                            <div class="flex flex-col sm:flex-row gap-3">
                                <a
                                    :href="route('events.index')"
                                    class="px-4 py-2 border border-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition-all duration-200 flex items-center justify-center"
                                >
                                    <i class="fa-solid fa-arrow-left mr-2"></i>Retour
                                </a>
                                <button
                                    type="submit"
                                    class="px-6 py-2.5 bg-[#59c4b4] text-white font-medium rounded-lg hover:bg-[#4db3a3] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#59c4b4] transition-all duration-200 flex items-center justify-center"
                                    :disabled="isLoading"
                                    :aria-busy="isLoading ? 'true' : 'false'"
                                    :class="{ 'opacity-50 cursor-not-allowed': isLoading }"
                                >
                                    <i class="fa-solid fa-save mr-2"></i>
                                    {{ isLoading ? 'Enregistrement...' : 'Enregistrer les modifications' }}
                                </button>
                            </div>
                        </div>
                    </form>
                    <!-- /FORM -->
                </div>
            </div>
        </div>

        <!-- ===== Modale globale ===== -->
        <ConfirmModal
            v-model:open="modal.open"
            :title="modal.title"
            :confirmLabel="modal.confirmLabel"
            :cancelLabel="modal.cancelLabel"
            :showCancel="modal.showCancel"
            :disabled="modal.disabled"
            @confirm="onModalConfirm"
            @cancel="onModalCancel"
        >
            <div class="text-sm text-gray-700" v-html="modal.message"></div>
        </ConfirmModal>
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
