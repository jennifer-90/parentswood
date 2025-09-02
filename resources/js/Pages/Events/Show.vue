<!-- resources/js/Pages/Events/Show.vue -->
<script setup>
import { ref, computed, toRefs } from 'vue'
import { usePage, router, Link, Head } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import ConfirmModal from '@/Components/ConfirmModal.vue'

/* ======================
   Props & refs dérivés
   ====================== */
const props = defineProps({
    event: Object,
    messages: Array,
    already_reported: Boolean,
})
const { event, messages, already_reported } = toRefs(props)
const alreadyReported = ref(!!already_reported.value)

const page = usePage()
const currentUser = page.props.auth?.user
const newMessage = ref('')

/* ======================
   Helpers d’affichage
   ====================== */
const centres = computed(() => event.value?.centres_interet ?? event.value?.centresInteret ?? [])

function parseLocalDateTime(dRaw, hRaw) {
    if (!dRaw) return null
    if (dRaw instanceof Date && !Number.isNaN(dRaw.getTime())) return dRaw

    let y, m, d
    if (typeof dRaw === 'string') {
        const m1 = dRaw.match(/^(\d{4})-(\d{2})-(\d{2})/)
        if (!m1) return null
        y = +m1[1]; m = +m1[2]; d = +m1[3]
    } else {
        return null
    }

    let hh = 0, mm = 0, ss = 0
    if (typeof hRaw === 'string' && hRaw.length >= 5) {
        const parts = hRaw.split(':')
        hh = +(parts[0] ?? 0)
        mm = +(parts[1] ?? 0)
        ss = +(parts[2] ?? 0)
    }

    const dt = new Date(y, m - 1, d, hh, mm, ss) // pas de décalage TZ
    return Number.isNaN(dt.getTime()) ? null : dt
}

const eventDateTime = computed(() => {
    const dRaw = event.value?.date
    const hRaw = event.value?.hour ?? '00:00'
    return parseLocalDateTime(dRaw, hRaw)
})

const displayDateOnly = computed(() => {
    if (!eventDateTime.value) return ''
    return new Intl.DateTimeFormat('fr-BE', {
        weekday: 'short',
        day: '2-digit',
        month: 'long',
        year: 'numeric',
    }).format(eventDateTime.value)
})

const displayHour = computed(() => {
    if (eventDateTime.value) {
        return new Intl.DateTimeFormat('fr-BE', { hour: '2-digit', minute: '2-digit' }).format(eventDateTime.value)
    }
    const raw = String(event.value?.hour ?? '')
    return raw.length >= 5 ? raw.slice(0, 5) : ''
})

/* ======================
   Permissions & états
   ====================== */
const userIsAdmin = computed(() =>
    currentUser?.roles?.some(r => ['Admin', 'Super-admin'].includes(r.name)) ?? false
)

const isParticipating = computed(() =>
    event.value?.participants?.some(p => p.id === currentUser?.id) ?? false
)

const canEditEvent = computed(() => event.value?.created_by === currentUser?.id)

const isPastEvent = computed(() => {
    // si le back expose event.is_past, préfère-le → sinon fallback local :
    if (typeof event.value?.is_past === 'boolean') return event.value.is_past
    if (!eventDateTime.value) return false
    return eventDateTime.value.getTime() < Date.now()
})

/* ============================================================
   CAPACITÉ / BOUTON "PARTICIPER"
   ============================================================ */
const participantsCount = computed(() => Number(event.value?.participants?.length ?? 0))
const capacity = computed(() => Number(event.value?.max_person ?? 0))

const isFullForNewJoiners = computed(() =>
    !isParticipating.value && capacity.value > 0 && participantsCount.value >= capacity.value
)

const disabledReason = computed(() => {
    if (isPastEvent.value) return 'Événement passé'
    if (!isParticipating.value) {
        if (event.value?.cancelled_at) return 'Événement annulé'
        if (event.value?.inactif) return 'Événement inactif'
        if (isFullForNewJoiners.value) return 'Complet'
    }
    return ''
})

const participationLabel = computed(() => {
    if (isPastEvent.value) return 'Événement passé'
    if (isParticipating.value) return 'Annuler ma participation'
    if (event.value?.cancelled_at) return 'Événement annulé'
    if (event.value?.inactif) return 'Événement inactif'
    if (isFullForNewJoiners.value) return 'Complet'
    return 'Participer'
})

/* ======================
   Actions participation
   ====================== */
const toggleParticipation = () => {
    if (!currentUser) return router.visit(route('login'))

    if (!isParticipating.value && (isPastEvent.value || disabledReason.value)) return

    router.post(
        route('events.toggleParticipation', { event: event.value.id }),
        {},
        {
            preserveScroll: true,
            onSuccess: () => router.reload({ only: ['event'] }),
            onError: () => alert('❌ Erreur lors de la mise à jour de votre participation.'),
        }
    )
}

/* ======================
   Commentaires
   ====================== */
const postComment = () => {
    if (!newMessage.value.trim()) return
    if (!currentUser) return router.visit(route('login'))

    router.post(
        route('events.messages.store', { event: event.value.id }),
        { text: newMessage.value },
        {
            preserveScroll: true,
            onSuccess: () => {
                newMessage.value = ''
                router.reload({ only: ['messages'] })
            },
            onError: () => alert("❌ Erreur lors de l'envoi du commentaire."),
        }
    )
}

/* ======================
   Divers utils
   ====================== */
const formatDate = (input) => {
    const d =
        typeof input === 'string'
            ? new Date(input.includes('T') ? input : input.replace(' ', 'T'))
            : new Date(input)
    if (Number.isNaN(d.getTime())) return ''
    return new Intl.DateTimeFormat('fr-BE', {
        day: '2-digit',
        month: 'long',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    }).format(d)
}

/* ======================
   Signalement
   ====================== */
const reportEvent = () => {
    if (!currentUser) return router.visit(route('login'))
    router.post(route('events.report', event.value.id), {}, {
        preserveScroll: true,
        onSuccess: () => { alreadyReported.value = true },
    })
}

/* ======================
   Annulation via modale
   ====================== */
const canCancelEvent = computed(() =>
    currentUser?.id === event.value?.created_by && !event.value?.cancelled_at
)
const cancelDisabled = computed(() => isPastEvent.value || !canCancelEvent.value)

const showCancelModal = ref(false)
const confirmCancelChecked = ref(false)
const canConfirmCancel = computed(() => confirmCancelChecked.value && !cancelDisabled.value)

const openCancelModal = () => {
    if (cancelDisabled.value) return
    confirmCancelChecked.value = false
    showCancelModal.value = true
}

const submitCancel = () => {
    if (!canConfirmCancel.value) return
    router.post(
        route('events.cancel', event.value.id),
        {},
        {
            preserveScroll: true,
            onSuccess: () => { showCancelModal.value = false },
            // onError: on laisse la modale ouverte (ex: droits insuffisants)
        }
    )
}
</script>

<template>
    <Head :title="event.name_event" />

    <AuthenticatedLayout>
        <div class="py-6 bg-[#f9f5f2] min-h-screen">
            <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- En-tête avec image -->
                <div class="bg-white rounded-xl shadow-lg overflow-hidden mb-8">
                    <div class="relative h-64 md:h-80">
                        <img
                            :src="event.picture_event ? `/storage/${event.picture_event}` : '/images/event-default.jpg'"
                            :alt="`Image de ${event.name_event}`"
                            class="w-full h-full object-cover"
                        />
                        <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent flex items-end">
                            <div class="p-6 text-white w-full">
                                <h1 class="text-3xl md:text-4xl font-bold mb-2"> * {{ event.name_event }} * </h1>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Contenu principal -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Colonne de gauche - Détails -->
                    <div class="lg:col-span-2 space-y-6">
                        <!-- Centres d’intérêt -->
                        <div class="bg-white rounded-xl shadow-md overflow-hidden">
                            <div class="p-6">
                                <div class="flex items-center mb-4">
                                    <div class="bg-[#59c4b4] text-white p-2 rounded-lg">
                                        <i class="fa-solid fa-star text-xl"></i>
                                    </div>
                                    <h2 class="ml-3 text-xl font-bold text-gray-800">Centres d'intérêt</h2>
                                </div>

                                <div v-if="centres && centres.length" class="flex flex-wrap gap-2">
                  <span
                      v-for="ci in centres"
                      :key="ci.id"
                      class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium
                           bg-[#59c4b4]/10 text-[#59c4b4] border border-[#59c4b4]/20"
                  >
                    {{ ci.name }}
                  </span>
                                </div>
                                <p v-else class="text-gray-500 italic">Aucun centre d'intérêt renseigné.</p>
                            </div>
                        </div>

                        <!-- Section Description -->
                        <div class="bg-white rounded-xl shadow-md overflow-hidden">
                            <div class="p-6">
                                <div class="flex items-center mb-4">
                                    <div class="bg-[#59c4b4] text-white p-2 rounded-lg">
                                        <i class="fa-regular fa-file-lines text-xl"></i>
                                    </div>
                                    <h2 class="ml-3 text-xl font-bold text-gray-800">Description</h2>
                                </div>

                                <p class="text-gray-700 whitespace-pre-line">
                                    {{ event.description || "Aucune description fournie." }}
                                </p>

                                <!-- Infos clés -->
                                <div class="mt-6 pt-6 border-t border-gray-100">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div class="flex items-start">
                                            <div class="bg-[#59c4b4]/10 p-2 rounded-lg mr-3">
                                                <i class="fa-solid fa-user-group text-[#59c4b4]"></i>
                                            </div>
                                            <div>
                                                <p class="text-sm text-gray-500">Participants</p>
                                                <p class="font-medium">{{ event.min_person }} - {{ event.max_person }} personnes</p>
                                            </div>
                                        </div>

                                        <div class="flex items-start">
                                            <div class="bg-[#59c4b4]/10 p-2 rounded-lg mr-3">
                                                <i class="fa-solid fa-user-tie text-[#59c4b4]"></i>
                                            </div>
                                            <div>
                                                <p class="text-sm text-gray-500">Organisateur</p>
                                                <div class="px-0 py-1 whitespace-nowrap text-sm text-gray-700">
                                                    <Link
                                                        v-if="event.creator"
                                                        :href="route('users.show', { user: event.creator.pseudo })"
                                                        class="text-blue-600 hover:underline"
                                                    >
                                                        {{ event.creator.pseudo }}
                                                    </Link>
                                                    <span v-else>Inconnu</span>

                                                    <span
                                                        v-if="event.cancelled_at && event.cancelled_by === event.created_by"
                                                        class="ml-2 inline-flex items-center text-[12px] font-semibold text-red-700 bg-red-50 px-2 py-0.5 rounded"
                                                        :title="`le ${new Date(event.cancelled_at).toLocaleDateString('fr-BE')} à ${new Date(event.cancelled_at).toLocaleTimeString('fr-BE',{hour:'2-digit',minute:'2-digit'})}`"
                                                    >
                            a annulé
                          </span>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="flex items-start">
                                            <div class="bg-[#59c4b4]/10 p-2 rounded-lg mr-3">
                                                <i class="fa-regular fa-calendar text-[#59c4b4]"></i>
                                            </div>
                                            <div>
                                                <p class="text-sm text-gray-500">Date</p>
                                                <p class="font-medium">{{ displayDateOnly || 'Date à préciser' }}</p>
                                            </div>
                                        </div>

                                        <div class="flex items-start">
                                            <div class="bg-[#59c4b4]/10 p-2 rounded-lg mr-3">
                                                <i class="fa-regular fa-clock text-[#59c4b4]"></i>
                                            </div>
                                            <div>
                                                <p class="text-sm text-gray-500">Heure</p>
                                                <p class="font-medium">{{ displayHour || '—:—' }}</p>
                                            </div>
                                        </div>

                                        <div class="flex items-start">
                                            <div class="bg-[#59c4b4]/10 p-2 rounded-lg mr-3">
                                                <i class="fa-solid fa-location-dot text-[#59c4b4]"></i>
                                            </div>
                                            <div>
                                                <p class="text-sm text-gray-500">Lieu</p>
                                                <p class="font-medium">{{ event.location || 'Lieu à préciser' }}</p>
                                            </div>
                                        </div>

                                        <div class="flex items-start">
                                            <div class="bg-[#59c4b4]/10 p-2 rounded-lg mr-3">
                                                <i class="fa-solid fa-calendar-plus text-[#59c4b4]"></i>
                                            </div>
                                            <div>
                                                <p class="text-sm text-gray-500">Créé le</p>
                                                <p class="font-medium">{{ formatDate(event.created_at) }}</p>
                                            </div>
                                        </div>

                                        <div class="flex items-start">
                                            <div class="bg-[#59c4b4]/10 p-2 rounded-lg mr-3">
                                                <i class="fa-solid fa-pen-to-square text-[#59c4b4]"></i>
                                            </div>
                                            <div>
                                                <p class="text-sm text-gray-500">Dernière mise à jour</p>
                                                <p class="font-medium">{{ formatDate(event.updated_at) }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!-- /Infos clés -->
                            </div>
                        </div>

                        <!-- Section Participants -->
                        <div class="bg-white rounded-xl shadow-md overflow-hidden">
                            <div class="p-6">
                                <div class="flex items-center justify-between mb-4">
                                    <div class="flex items-center">
                                        <div class="bg-[#59c4b4] text-white p-2 rounded-lg">
                                            <i class="fa-solid fa-users text-xl"></i>
                                        </div>
                                        <h2 class="ml-3 text-xl font-bold text-gray-800">Participants</h2>
                                        <span class="ml-2 px-3 py-1 bg-gray-100 text-gray-800 text-sm font-medium rounded-full">
                      {{ participantsCount }}
                    </span>
                                    </div>

                                    <button
                                        @click="toggleParticipation"
                                        :disabled="isPastEvent || (!!disabledReason && !isParticipating)"
                                        :title="disabledReason || ''"
                                        class="px-4 py-2 rounded-lg font-medium transition-colors duration-200 flex items-center
                           disabled:bg-gray-200 disabled:text-gray-500 disabled:cursor-not-allowed disabled:opacity-70"
                                        :class="{
                      'bg-[#59c4b4] hover:bg-[#4db3a3] text-white': participationLabel === 'Participer',
                      'bg-red-100 hover:bg-red-200 text-red-700': isParticipating && !isPastEvent,
                      'bg-gray-200 text-gray-500 cursor-not-allowed opacity-70': !isParticipating && participationLabel !== 'Participer',
                    }"
                                    >
                                        <i class="fa-solid mr-2" :class="isParticipating ? 'fa-user-minus' : 'fa-user-plus'"></i>
                                        {{ participationLabel }}
                                    </button>
                                </div>

                                <div v-if="event.participants && event.participants.length > 0" class="mt-4">
                                    <div class="flex flex-wrap gap-3">
                                        <div v-for="participant in event.participants" :key="participant.id" class="flex items-center">
                                            <Link
                                                :href="route('users.show', { user: participant.pseudo })"
                                                class="group flex items-center"
                                            >
                                                <img
                                                    :src="participant.picture_profil_url || '/images/default-avatar.png'"
                                                    :alt="participant.pseudo"
                                                    class="w-10 h-10 rounded-full object-cover border-2 border-white shadow-sm group-hover:border-[#59c4b4] transition-colors"
                                                />
                                                <span class="ml-2 text-sm font-medium text-gray-700 group-hover:text-[#59c4b4] transition-colors">
                          {{ participant.pseudo }}
                        </span>
                                            </Link>
                                        </div>
                                    </div>
                                </div>
                                <div v-else class="text-gray-500 italic py-4 text-center">
                                    Aucun participant pour le moment. Soyez le premier à rejoindre !
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Colonne de droite - Commentaires -->
                    <div class="lg:col-span-1">
                        <div class="bg-white rounded-xl shadow-md overflow-hidden h-full flex flex-col">
                            <div class="p-6 border-b border-gray-100">
                                <div class="flex items-center">
                                    <div class="bg-[#59c4b4] text-white p-2 rounded-lg">
                                        <i class="fa-regular fa-comment-dots text-xl"></i>
                                    </div>
                                    <h2 class="ml-3 text-xl font-bold text-gray-800">Commentaires</h2>
                                    <span class="ml-2 px-3 py-1 bg-gray-100 text-gray-800 text-sm font-medium rounded-full">
                    {{ messages?.length || 0 }}
                  </span>
                                </div>
                            </div>

                            <!-- Liste des commentaires -->
                            <div class="flex-1 overflow-y-auto p-4 comments-container" style="max-height: 500px;">
                                <div v-if="messages && messages.length > 0" class="space-y-4">
                                    <div
                                        v-for="message in messages"
                                        :key="message.id"
                                        class="flex items-start gap-3 p-3 hover:bg-gray-50 rounded-lg transition-colors"
                                    >
                                        <Link :href="route('users.show', { user: message.user.pseudo })" class="flex-shrink-0">
                                            <img
                                                :src="message.user.picture_profil_url || '/images/default-avatar.png'"
                                                :alt="message.user.pseudo"
                                                class="w-10 h-10 rounded-full object-cover border-2 border-white shadow-sm"
                                            />
                                        </Link>
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center justify-between">
                                                <Link
                                                    :href="route('users.show', { user: message.user.pseudo })"
                                                    class="font-medium text-gray-900 hover:text-[#59c4b4] transition-colors"
                                                >
                                                    {{ message.user.pseudo }}
                                                </Link>
                                                <span class="text-xs text-gray-400">
                          {{ formatDate(message.created_at) }}
                        </span>
                                            </div>
                                            <p class="mt-1 text-gray-700 text-sm whitespace-pre-line">{{ message.text }}</p>
                                        </div>
                                    </div>
                                </div>
                                <div v-else class="text-center py-8 text-gray-500">
                                    <p>Aucun commentaire pour le moment.</p>
                                    <p class="text-sm mt-1">Soyez le premier à réagir !</p>
                                </div>
                            </div>

                            <!-- Formulaire de commentaire -->
                            <div class="p-4 border-t border-gray-100">
                                <div class="relative">
                  <textarea
                      v-model="newMessage"
                      rows="3"
                      placeholder="Écrire un commentaire..."
                      class="w-full px-4 py-3 pr-12 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#59c4b4] focus:border-transparent transition duration-200 resize-none"
                      @keydown.enter.exact.prevent="postComment"
                      @keydown.shift.enter.stop
                  ></textarea>

                                    <button
                                        type="button"
                                        @click="postComment"
                                        :disabled="!newMessage.trim()"
                                        :class="{
                      'bg-[#59c4b4] hover:bg-[#4db3a3]': newMessage.trim(),
                      'bg-gray-200 cursor-not-allowed': !newMessage.trim()
                    }"
                                        class="absolute right-3 bottom-3 p-2 text-white rounded-lg transition-colors duration-200"
                                    >
                                        <i class="fa-solid fa-paper-plane"></i>
                                    </button>
                                </div>
                                <p class="mt-2 text-xs text-gray-500 text-center">
                                    Entrée pour envoyer — Shift+Entrée pour une nouvelle ligne
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Boutons d'action -->
                <div class="mt-8 flex flex-col sm:flex-row justify-between gap-4">
                    <div class="flex flex-col sm:flex-row gap-3">
                        <Link
                            :href="route('events.index')"
                            class="px-4 py-2.5 border border-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-50 transition-colors flex items-center justify-center"
                        >
                            <i class="fa-solid fa-arrow-left mr-2"></i>
                            Retour aux événements
                        </Link>

                        <Link
                            v-if="canEditEvent && !isPastEvent"
                            :href="route('events.edit', event.id)"
                            class="px-4 py-2.5 border border-[#59c4b4] text-[#59c4b4] font-medium rounded-lg hover:bg-[#59c4b4]/10 transition-colors flex items-center justify-center"
                        >
                            <i class="fa-solid fa-pen-to-square mr-2"></i>
                            Modifier l'événement
                        </Link>

                        <span
                            v-else-if="canEditEvent && isPastEvent"
                            class="px-4 py-2.5 border border-gray-300 text-gray-400 font-medium rounded-lg bg-gray-100 cursor-not-allowed flex items-center justify-center"
                            title="Modification indisponible : événement passé"
                        >
              <i class="fa-solid fa-pen-to-square mr-2"></i>
              Modifier l'événement
            </span>
                    </div>

                    <div class="flex flex-col sm:flex-row gap-3">
                        <button
                            @click="reportEvent"
                            :disabled="alreadyReported"
                            class="px-4 py-2.5 border rounded-lg flex items-center justify-center"
                            :class="alreadyReported
                ? 'border-amber-200 text-amber-300 cursor-not-allowed'
                : 'border-amber-300 text-amber-700 hover:bg-amber-50'"
                        >
                            <i class="fa-solid fa-bell mr-2" :class="alreadyReported ? 'text-amber-300' : 'text-amber-600'"></i>
                            {{ alreadyReported ? 'Signalé' : 'Signaler' }}
                        </button>

                        <!-- Annuler l'événement (grisé si impossible) -->
                        <button
                            @click="openCancelModal"
                            :disabled="cancelDisabled"
                            class="px-4 py-2.5 rounded-lg flex items-center justify-center border transition-colors"
                            :class="cancelDisabled
                ? 'border-gray-300 text-gray-400 bg-gray-100 cursor-not-allowed'
                : 'border-red-300 text-red-600 hover:bg-red-50'"
                            :title="cancelDisabled ? (isPastEvent ? 'Événement passé' : 'Action non autorisée') : ''"
                        >
                            <i class="fa-solid fa-ban mr-2"></i>
                            Annuler l'événement
                        </button>

                        <!-- Badge si déjà annulé -->
                        <span
                            v-if="event.cancelled_at"
                            class="px-3 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-800"
                        >
              Annulé le {{ formatDate(event.cancelled_at) }}
            </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modale de confirmation -->
        <ConfirmModal
            v-model:open="showCancelModal"
            title="Confirmer l’annulation"
            confirmLabel="Confirmer l’annulation"
            cancelLabel="Fermer"
            :disabled="!canConfirmCancel"
            @confirm="submitCancel"
        >
            <p class="text-sm text-gray-700 mb-4">
                Cette action est <strong>définitive</strong>. L’événement sera <strong>mis inactif</strong> et
                les participants seront notifiés.
            </p>

            <label class="flex items-start gap-3 cursor-pointer">
                <input
                    type="checkbox"
                    class="mt-1 h-4 w-4 rounded border-gray-300 text-red-600 focus:ring-red-400"
                    v-model="confirmCancelChecked"
                />
                <span class="text-sm text-gray-700">
          Je confirme l’annulation de « {{ event.name_event }} ».
        </span>
            </label>
        </ConfirmModal>
    </AuthenticatedLayout>
</template>

<style scoped>
/* Styles spécifiques pour les barres de défilement */
.comments-container::-webkit-scrollbar { width: 6px; }
.comments-container::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 10px; }
.comments-container::-webkit-scrollbar-thumb { background: #c1c1c1; border-radius: 10px; }
.comments-container::-webkit-scrollbar-thumb:hover { background: #a8a8a8; }

/* Animation pour les nouveaux messages */
.message-enter-active, .message-leave-active { transition: all 0.3s ease; }
.message-enter-from, .message-leave-to { opacity: 0; transform: translateY(10px); }

/* Liens */
a { @apply text-[#59c4b4] hover:underline; }

/* Mobile */
@media (max-width: 640px) {
    .comments-container { max-height: 300px !important; }
}
</style>
