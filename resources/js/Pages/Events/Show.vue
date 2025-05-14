<script setup>
import { ref, computed } from 'vue'
import { usePage, router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'

const props = defineProps({
    event: Object,
    messages: Array,
})

const page = usePage()
const currentUser = page.props.auth.user

const isParticipating = computed(() =>
    props.event.participants.some(p => p.id === currentUser.id)
)

const participationLabel = computed(() =>
    isParticipating.value ? 'Annuler ma participation' : 'Participer à cet événement'
)

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
</script>

<template>
    <AuthenticatedLayout>
        <div class="page-all max-w-3xl mx-auto p-6">
            <!-- Titre -->
            <h1 class="text-3xl font-bold text-teal-600 mb-6">{{ event.name_event }}</h1>

            <!-- Image -->
            <div v-if="event.picture_event" class="mb-6">
                <img
                    :src="`/storage/${event.picture_event}`"
                    alt="Image de l'événement"
                    class="w-full max-h-96 object-cover rounded shadow"
                />
            </div>

            <!-- Détails de l’événement -->
            <div class="bg-white p-4 rounded shadow space-y-3 text-gray-800 mb-6">
                <p><strong>Date :</strong> {{ event.date }} à {{ event.hour }}</p>
                <p><strong>Lieu :</strong> {{ event.location }}</p>
                <p><strong>Limite de participants :</strong> Entre {{ event.min_person }} et {{ event.max_person }} personnes</p>
                <p>
                    <strong>Participants inscrits :</strong>
                    <span v-if="event.participants.length === 0" class="text-gray-500">Aucun participant</span>
                    <span v-else class="text-teal-700 font-semibold">
                        {{ event.participants.length }} participant<span v-if="event.participants.length > 1">s</span>
                    </span>
                </p>
                <p><strong>Créé le :</strong> {{ new Date(event.created_at).toLocaleString() }}</p>
                <p><strong>Dernière mise à jour :</strong> {{ new Date(event.updated_at).toLocaleString() }}</p>
                <p>
                    <strong>Créé par :</strong>
                    <a
                        :href="route('users.show', event.creator.id)"
                        class="text-cyan-700 font-semibold hover:underline"
                    >
                        {{ event.creator.pseudo }}
                    </a>
                </p>
                <p class="text-gray-700 whitespace-pre-line"><strong>Description :</strong><br />{{ event.description }}</p>
            </div>

            <!-- Bouton de participation -->
            <div class="flex justify-end mb-6">
                <button
                    @click="toggleParticipation"
                    class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded shadow font-semibold"
                >
                    {{ participationLabel }}
                </button>
            </div>

            <!-- Section commentaires -->
            <div class="bg-white p-4 rounded shadow">
                <h2 class="text-xl font-semibold text-teal-700 mb-4">Commentaires</h2>

                <div v-if="messages.length === 0" class="text-gray-500 mb-4">Aucun commentaire pour l’instant.</div>

                <div v-for="msg in messages" :key="msg.id" class="flex items-start gap-3 mb-6">
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
                            <span class="ml-2 text-xs text-gray-400">{{ new Date(msg.created_at).toLocaleString() }}</span>
                        </p>
                        <p class="text-gray-800 mt-1 whitespace-pre-line">{{ msg.text }}</p>
                    </div>
                </div>

                <!-- Formulaire commentaire -->
                <div class="mt-4">
                    <textarea
                        v-model="newMessage"
                        rows="3"
                        placeholder="Exprime-toi..."
                        class="w-full p-2 border border-gray-300 rounded resize-none mb-2"
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
    background: rgb(249, 245, 242);
}
</style>
