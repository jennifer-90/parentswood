<script setup>
import { ref, computed } from 'vue'
import { usePage, router, Link } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head } from '@inertiajs/vue3'

const props = defineProps({
    event: Object,
    messages: Array,
})

const page = usePage()
const currentUser = page.props.auth.user
const newMessage = ref('')

// Vérifications des rôles et permissions
const userIsAdmin = computed(() =>
    currentUser?.roles?.some(role => ['Admin', 'Super-admin'].includes(role.name)) ?? false
)

const isParticipating = computed(() =>
    props.event.participants?.some(p => p.id === currentUser?.id) ?? false
)

const canEditEvent = computed(() =>
    props.event?.created_by === currentUser?.id || userIsAdmin.value
)

// Gestion de la participation
const toggleParticipation = () => {
    if (!currentUser) {
        router.visit(route('login'))
        return
    }

    // Créez une copie locale de l'événement pour la réactivité
    const updatedEvent = { ...props.event }
    const isCurrentlyParticipating = isParticipating.value

    // Mettez à jour l'interface utilisateur immédiatement
    if (isCurrentlyParticipating) {
        // Retirer de la liste des participants
        updatedEvent.participants = updatedEvent.participants?.filter(
            p => p.id !== currentUser.id
        ) || []
    } else {
        // Ajouter à la liste des participants
        if (!updatedEvent.participants) {
            updatedEvent.participants = []
        }
        updatedEvent.participants = [...updatedEvent.participants, currentUser]
    }

    // Mettre à jour la référence de l'événement
    Object.assign(props.event, updatedEvent)

    // Envoyer la requête au serveur
    router.post(
        route('events.toggleParticipation', { event: props.event.id }),
        {},
        {
            preserveScroll: true,
            onError: () => {
                // En cas d'erreur, remettre l'état précédent
                alert("❌ Une erreur est survenue lors de la mise à jour de votre participation.")
                router.reload({ only: ['event'] })
            }
        }
    )
}

// Gestion des commentaires
const postComment = () => {
    if (!newMessage.value.trim()) return
    if (!currentUser) {
        router.visit(route('login'))
        return
    }

    router.post(
        route('messages.store', { event: props.event.id }),
        { text: newMessage.value },
        {
            preserveScroll: true,
            onSuccess: () => {
                newMessage.value = ''
                // Recharger les messages après l'ajout
                router.reload({ only: ['messages'] })
            },
            onError: () => {
                alert("❌ Une erreur est survenue lors de l'envoi du commentaire.")
            }
        }
    )
}

// Suppression d'un événement
const deleteEvent = () => {
    if (confirm('Êtes-vous sûr de vouloir supprimer cet événement ? Cette action est irréversible.')) {
        router.delete(route('events.destroy', props.event.id), {
            onSuccess: () => {
                // Rediriger vers la liste des événements après la suppression
                router.visit(route('events.index'))
            }
        })
    }
}

// Formatage des dates
const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('fr-BE', {
        day: '2-digit',
        month: 'long',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    })
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
                                <h1 class="text-3xl md:text-4xl font-bold mb-2">{{ event.name_event }}</h1>
                                <div class="flex items-center text-lg">
                                    <i class="fa-solid fa-location-dot mr-2"></i>
                                    <span>{{ event.location }}</span>
                                    <span class="mx-3">•</span>
                                    <i class="fa-regular fa-calendar-days mr-2"></i>
                                    <span>{{ formatDate(event.date + ' ' + event.hour) }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Badge de statut -->
                        <div class="absolute top-4 right-4 bg-white/90 text-gray-800 px-3 py-1 rounded-full text-sm font-medium shadow-md">
                            <i class="fa-solid fa-users mr-1"></i>
                            {{ event.participants?.length || 0 }} / {{ event.max_person }} participants
                        </div>
                    </div>
                </div>

                <!-- Suite du template, à ajouter après la section d'en-tête existante -->

                <!-- Contenu principal -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Colonne de gauche - Détails -->
                    <div class="lg:col-span-2 space-y-6">
                        <!-- Section Description -->
                        <div class="bg-white rounded-xl shadow-md overflow-hidden">
                            <div class="p-6">
                                <div class="flex items-center mb-4">
                                    <div class="bg-[#59c4b4] text-white p-2 rounded-lg">
                                        <i class="fa-regular fa-file-lines text-xl"></i>
                                    </div>
                                    <h2 class="ml-3 text-xl font-bold text-gray-800">Description</h2>
                                </div>
                                <p class="text-gray-700 whitespace-pre-line">{{ event.description || "Aucune description fournie." }}</p>

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
                                                <Link
                                                    :href="route('users.show', event.creator.id)"
                                                    class="font-medium text-[#59c4b4] hover:underline flex items-center"
                                                >
                                                    {{ event.creator.pseudo }}
                                                    <i class="fa-solid fa-arrow-up-right-from-square ml-1 text-xs"></i>
                                                </Link>
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
                            {{ event.participants?.length || 0 }}
                        </span>
                                    </div>
                                    <button
                                        @click="toggleParticipation"
                                        :class="{
        'bg-[#59c4b4] hover:bg-[#4db3a3] text-white': !isParticipating,
        'bg-red-100 hover:bg-red-200 text-red-700': isParticipating
    }"
                                        class="px-4 py-2 rounded-lg font-medium transition-colors duration-200 flex items-center"
                                    >
                                        <i class="fa-solid mr-2" :class="isParticipating ? 'fa-user-minus' : 'fa-user-plus'"></i>
                                        {{ isParticipating ? 'Annuler ma participation' : 'Participer' }}
                                    </button>
                                </div>

                                <div v-if="event.participants && event.participants.length > 0" class="mt-4">
                                    <div class="flex flex-wrap gap-3">
                                        <div v-for="participant in event.participants" :key="participant.id" class="flex items-center">
                                            <Link
                                                :href="route('users.show', participant.id)"
                                                class="group flex items-center"
                                            >
                                                <img
                                                    :src="participant.picture || '/images/default-avatar.png'"
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
                                    <div v-for="message in messages" :key="message.id" class="flex items-start gap-3 p-3 hover:bg-gray-50 rounded-lg transition-colors">
                                        <Link :href="route('users.show', message.user.id)" class="flex-shrink-0">
                                            <img
                                                :src="message.user.picture || '/images/default-avatar.png'"
                                                :alt="message.user.pseudo"
                                                class="w-10 h-10 rounded-full object-cover border-2 border-white shadow-sm"
                                            />
                                        </Link>
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center justify-between">
                                                <Link
                                                    :href="route('users.show', message.user.id)"
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
                                    <i class="fa-regular fa-comment-slash text-3xl mb-2 opacity-50"></i>
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
                        @keydown.enter.exclusive.prevent="postComment"
                    ></textarea>
                                    <button
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
                                    Appuyez sur Entrée pour envoyer
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
                            v-if="canEditEvent"
                            :href="route('events.edit', event.id)"
                            class="px-4 py-2.5 border border-[#59c4b4] text-[#59c4b4] font-medium rounded-lg hover:bg-[#59c4b4]/10 transition-colors flex items-center justify-center"
                        >
                            <i class="fa-solid fa-pen-to-square mr-2"></i>
                            Modifier l'événement
                        </Link>
                    </div>

                    <div class="flex flex-col sm:flex-row gap-3">
                        <button
                            v-if="canEditEvent"
                            @click="deleteEvent"
                            class="px-4 py-2.5 border border-red-300 text-red-600 font-medium rounded-lg hover:bg-red-50 transition-colors flex items-center justify-center"
                        >
                            <i class="fa-solid fa-trash-alt mr-2"></i>
                            Annuler l'événement
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>


<style scoped>
/* Styles spécifiques pour les barres de défilement */
.comments-container::-webkit-scrollbar {
    width: 6px;
}

.comments-container::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 10px;
}

.comments-container::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 10px;
}

.comments-container::-webkit-scrollbar-thumb:hover {
    background: #a8a8a8;
}

/* Animation pour les nouveaux messages */
.message-enter-active,
.message-leave-active {
    transition: all 0.3s ease;
}

.message-enter-from,
.message-leave-to {
    opacity: 0;
    transform: translateY(10px);
}

/* Style pour les liens dans le contenu */
a {
    @apply text-[#59c4b4] hover:underline;
}

/* Amélioration de la lisibilité sur mobile */
@media (max-width: 640px) {
    .comments-container {
        max-height: 300px !important;
    }
}
</style>
