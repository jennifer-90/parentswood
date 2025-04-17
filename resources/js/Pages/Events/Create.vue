<script setup>
import { ref } from 'vue'
import { router, useForm } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'

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

const submit = () => {
    form.post(route('events.store'), {
        onSuccess: () => {
            alert('Événement créé avec succès !')
        },
        onError: () => {
            alert("Une erreur s'est produite.")
        }
    })
}
</script>

<template>
    <AuthenticatedLayout>
        <div class="page-all max-w-2xl mx-auto bg-white shadow rounded p-6">
            <h1 class="text-2xl font-bold text-teal-600 mb-6">Créer un nouvel événement</h1>

            <form @submit.prevent="submit" class="space-y-4">
                <div>
                    <label for="name_event" class="block font-medium">Nom de l'événement</label>
                    <input v-model="form.name_event" type="text" id="name_event" class="input" required>
                </div>

                <div>
                    <label for="description" class="block font-medium">Description</label>
                    <textarea v-model="form.description" id="description" class="input"></textarea>
                </div>

                <div class="flex gap-4">
                    <div class="flex-1">
                        <label for="date" class="block font-medium">Date</label>
                        <input v-model="form.date" type="date" id="date" class="input" required>
                    </div>
                    <div class="flex-1">
                        <label for="hour" class="block font-medium">Heure</label>
                        <input v-model="form.hour" type="time" id="hour" class="input" required>
                    </div>
                </div>

                <div>
                    <label for="location" class="block font-medium">Lieu</label>
                    <input v-model="form.location" type="text" id="location" class="input" required>
                </div>

                <div class="flex gap-4">
                    <div class="flex-1">
                        <label for="min_person" class="block font-medium">Participants min.</label>
                        <input v-model="form.min_person" type="number" min="1" id="min_person" class="input" required>
                    </div>
                    <div class="flex-1">
                        <label for="max_person" class="block font-medium">Participants max.</label>
                        <input v-model="form.max_person" type="number" min="1" id="max_person" class="input" required>
                    </div>
                </div>

                <div>
                    <label for="picture_event" class="block font-medium">Image de l'événement</label>
                    <input type="file" @change="e => form.picture_event = e.target.files[0]" id="picture_event" class="input">
                </div>

                <div class="mt-6">
                    <button type="submit" class="bg-teal-600 text-white px-4 py-2 rounded hover:bg-teal-700">Créer l’événement</button>
                </div>
            </form>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.input {
    width: 100%;
    border: 1px solid #ccc;
    padding: 0.5rem;
    border-radius: 0.375rem;
}
.page-all {
    background: #fafafa;
    margin-top: 2rem;
}
</style>
