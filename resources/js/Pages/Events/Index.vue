<script setup>
import { defineProps, computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    events: Array
});

const now = new Date();

const upcomingEvents = computed(() =>
    props.events.data.filter(event => new Date(event.date) > new Date())
);

const pastEvents = computed(() =>
    props.events.data.filter(event => new Date(event.date) <= new Date())
);

</script>

<template>
    <AuthenticatedLayout>
        <div class="page-all">
            <h1 class="text-2xl font-bold text-teal-600 mb-4">Tous les événements</h1>

            <div class="mb-8">
                <h2 class="text-xl font-semibold text-gray-700 mb-2">Evènements à venir</h2>
                <div v-if="upcomingEvents.length">
                    <ul class="space-y-4">
                        <li v-for="event in upcomingEvents" :key="event.id" class="p-4 bg-white border rounded shadow">
                            <h3 class="text-lg font-bold text-teal-700">{{ event.title }}</h3>
                            <p class="text-sm text-gray-600">{{ new Date(event.date).toLocaleString() }}</p>
                            <p class="mt-1 text-gray-800">{{ event.description }}</p>
                            <Link :href="route('events.show', event.id)" class="text-teal-600 hover:underline mt-2 inline-block">Voir détails</Link>
                        </li>
                    </ul>
                </div>
                <div v-else class="text-gray-500 italic">
                    Pas encore d'évènements à venir. <Link class="text-teal-600 hover:underline" :href="route('events.create')">Veux-tu en créer un ?</Link>
                </div>
            </div>

            <div>
                <h2 class="text-xl font-semibold text-gray-700 mb-2">Evènements passés</h2>
                <div v-if="pastEvents.length">
                    <ul class="space-y-4">
                        <li v-for="event in pastEvents" :key="event.id" class="p-4 bg-gray-100 border rounded">
                            <h3 class="text-lg font-semibold text-gray-700">{{ event.title }}</h3>
                            <p class="text-sm text-gray-500">{{ new Date(event.date).toLocaleString() }}</p>
                            <Link :href="route('events.show', event.id)" class="text-teal-600 hover:underline mt-2 inline-block">Voir détails</Link>
                        </li>
                    </ul>
                </div>
                <div v-else class="text-gray-400 italic">Aucun évènement passé pour le moment.</div>
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
