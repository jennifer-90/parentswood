<script setup>
import { ref } from 'vue';
import { usePage, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    event: Object,
    messages: Array,
});

const page = usePage();
const currentUser = page.props.auth.user;
const newMessage = ref('');

const postComment = () => {
    if (!newMessage.value.trim()) return;

    router.post(route('messages.store', { event: props.event.id }), {
        text: newMessage.value,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            newMessage.value = '';
        },
        onError: () => alert("Une erreur est survenue lors de l'envoi du commentaire."),
    });
};
</script>

<template>
    <AuthenticatedLayout>
        <div class="page-all max-w-3xl mx-auto">
            <h1 class="text-2xl font-bold text-teal-600 mb-4">{{ event.name_event }}</h1>

            <div class="bg-white p-4 rounded shadow mb-6">
                <p class="text-gray-800"><strong>Date :</strong> {{ event.date }} à {{ event.hour }}</p>
                <p class="text-gray-800"><strong>Lieu :</strong> {{ event.location }}</p>
                <p class="text-gray-800"><strong>Participants :</strong> {{ event.min_person }} à {{ event.max_person }}</p>
                <p class="text-gray-800"><strong>Description :</strong></p>
                <p class="mt-1 text-gray-700 whitespace-pre-line">{{ event.description }}</p>
            </div>



            <div class="bg-white p-4 rounded shadow">
                <h2 class="text-lg font-semibold text-teal-700 mb-5">Commentaires</h2>

                <div class="space-y-6 mb-6">
                    <div v-for="msg in messages" :key="msg.id" class="flex items-start gap-3">
                        <img
                            :src="msg.user.picture || '/images/default-avatar.png'"
                            alt="Avatar"
                            class="w-10 h-10 max-w-[40px] max-h-[40px] rounded-full object-cover border border-gray-300 shadow-sm inline-block"
                            style="width: 40px; height: 40px; object-fit: cover;"
                        />
                        <div class="flex-1">
                            <p class="text-sm text-gray-600">
                                <a :href="route('users.show', msg.user.id)" class="text-cyan-700 font-semibold hover:underline">
                                    {{ msg.user.pseudo }}
                                </a>
                                <span class="text-xs text-gray-400 ml-2">
                    {{ new Date(msg.created_at).toLocaleString() }}
                </span>
                            </p>
                            <p class="text-gray-800 mt-1 whitespace-pre-line">{{ msg.text }}</p>
                        </div>
                    </div>
                </div>



                <div class="flex flex-col gap-2">
                    <textarea v-model="newMessage" rows="3" placeholder="Exprime-toi..."
                              class="w-full p-2 border border-gray-300 rounded resize-none"></textarea>
                    <div class="flex justify-end">
                        <button @click="postComment"
                                class="bg-teal-600 hover:bg-teal-700 text-white px-4 py-2 rounded font-semibold">
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
    padding: 20px;
    border-radius: 10px;
}
</style>
