<script setup>
import { ref, computed } from 'vue'
import { usePage, router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Link } from '@inertiajs/vue3'


const props = defineProps({
    event: Object,
    messages: Array,
})

const page = usePage()
const currentUser = page.props.auth.user
const userIsAdmin = computed(() =>
    currentUser?.roles?.some(role => ['Admin', 'Super-admin'].includes(role.name)) ?? false
)



// Vérifie si l'utilisateur participe déjà
const isParticipating = computed(() =>
    props.event.participants.some(p => p.id === currentUser.id)
)

// Label dynamique pour le bouton
const participationLabel = computed(() =>
    isParticipating.value ? '🚫 Annuler ma participation' : '✅ Participer à cet événement'
)

// Participation ou annulation
const toggleParticipation = () => {
    router.post(
        route('events.toggleParticipation', { event: props.event.id }),
        {},
        {
            onSuccess: () => location.reload(),
            onError: () => alert("Une erreur est survenue."),
        }
    )
}

// Commentaire
const newMessage = ref('')

const postComment = () => {
    if (!newMessage.value.trim()) return

    router.post(
        route('messages.store', { event: props.event.id }),
        { text: newMessage.value },
        {
            preserveScroll: true,
            onSuccess: () => {
                newMessage.value = ''
            },
            onError: () => {
                alert("Une erreur est survenue lors de l'envoi du commentaire.")
            }
        }
    )
}

const canEditEvent = computed(() =>
    props.event?.created_by === currentUser?.id || userIsAdmin.value
)



</script>

<template>
    <AuthenticatedLayout>
        <div class="page-all max-w-3xl mx-auto p-6">
            <!-- Titre -->
            <h1 class="text-3xl font-bold text-teal-700 mb-6 text-center">
                {{ event.name_event }}
            </h1>

            <!-- Image -->
            <div v-if="event.picture_event" class="mb-6">
                <img
                    :src="`/storage/${event.picture_event}`"
                    alt="Image de l'événement"
                    class="w-full h-72 object-cover rounded-xl shadow-md"
                />
            </div>

            <!-- Infos -->
            <div class="bg-white p-5 rounded-lg shadow mb-6 space-y-3 text-gray-800">
                <p><strong>📅 Date :</strong> {{ event.date }} à {{ event.hour.slice(0, 5) }}</p>
                <p><strong>* Lieu :</strong> {{ event.location }}</p>
                <p><strong>* Participants attendus :</strong> de {{ event.min_person }} à {{ event.max_person }} personnes</p>
                <p>
                    <strong>* Actuellement inscrit(s) :</strong>
                    <span v-if="event.participants.length === 0" class="text-gray-500">Personne pour l’instant</span>
                    <span v-else class="text-teal-700 font-semibold">
                    {{ event.participants.length }} participant<span v-if="event.participants.length > 1">s</span>
                </span>
                </p>
                <p><strong>* Créé le :</strong> {{ new Date(event.created_at).toLocaleString('fr-BE') }}</p>
                <p><strong>* Dernière mise à jour :</strong> {{ new Date(event.updated_at).toLocaleString('fr-BE') }}</p>
                <p>
                    <strong>👤 Organisé par :</strong>
                    <a
                        :href="route('users.show', event.creator.id)"
                        class="text-cyan-700 font-semibold hover:underline"
                    >
                        {{ event.creator.pseudo }}
                    </a>
                </p>
                <p class="text-gray-700 whitespace-pre-line">
                    <strong>* Description :</strong><br />{{ event.description }}
                </p>
            </div>

            <!-- Bouton de participation -->
            <div class="flex justify-center mb-8">
                <button
                    @click="toggleParticipation"
                    class="bg-teal-600 hover:bg-teal-700 text-white px-6 py-2 rounded-full shadow font-semibold"
                >
                    {{ participationLabel }}
                </button>
            </div>

            <!-- Bouton de update -->
            <div class="flex justify-center mb-8">
                <Link
                    v-if="canEditEvent"
                    :href="route('events.edit', event.id)"
                    class="bg-blue-600 text-white px-4 py-2 rounded shadow hover:bg-blue-700 font-semibold"
                >
                    * Modifier cet événement
                </Link>
            </div>




            <!-- Commentaires -->
            <div class="bg-white p-5 rounded-lg shadow">
                <h2 class="text-xl font-semibold text-teal-700 mb-4">💬 Commentaires</h2>

                <div v-if="messages.length === 0" class="text-gray-500 mb-4">
                    Aucun commentaire pour le moment. Sois le premier à t’exprimer ! 😊
                </div>

                <div v-for="msg in messages" :key="msg.id" class="flex items-start gap-4 mb-6">
                    <img
                        :src="msg.user.picture || '/images/default-avatar.png'"
                        class="w-10 h-10 rounded-full object-cover border"
                        alt="Avatar"
                    />
                    <div>
                        <p class="text-sm text-gray-600">
                            <a :href="route('users.show', msg.user.id)" class="text-cyan-700 font-semibold hover:underline">
                                {{ msg.user.pseudo }}
                            </a>
                            <span class="ml-2 text-xs text-gray-400">{{ new Date(msg.created_at).toLocaleString('fr-BE') }}</span>
                        </p>
                        <p class="text-gray-800 mt-1 whitespace-pre-line">{{ msg.text }}</p>
                    </div>
                </div>

                <!-- Formulaire -->
                <div class="mt-4">
                <textarea
                    v-model="newMessage"
                    rows="3"
                    placeholder="Exprime-toi librement…"
                    class="w-full p-3 border border-gray-300 rounded resize-none mb-3 focus:ring-teal-500 focus:border-teal-500"
                ></textarea>
                    <div class="text-end">
                        <button
                            @click="postComment"
                            class="bg-teal-600 hover:bg-teal-700 text-white px-4 py-2 rounded font-semibold"
                        >
                            Publier
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.page-all {
    background: #f9f5f2;
}
</style>
