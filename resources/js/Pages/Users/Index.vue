<script setup>
import {defineProps} from 'vue';
import {router} from '@inertiajs/vue3';
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";

defineProps({
    users: Object, // Laravel transmet un objet contenant les données de pagination
});

// Fonction pour changer de page
const goToPage = (url) => {
    if (url) {
        router.get(url);
    }
};
</script>

<template>
    <AuthenticatedLayout>
        <div class="page-all">
            <h1 class="text-2xl font-bold text-teal-600 mb-4">Liste des utilisateurs</h1>

            <!-- Tableau des utilisateurs -->
            <table class="table-auto w-full border-collapse border border-gray-300">
                <thead>
                <tr class="bg-gray-100">
                    <th class="border border-gray-300 px-4 py-2">Nom</th>
                    <th class="border border-gray-300 px-4 py-2">Email</th>
                    <th class="border border-gray-300 px-4 py-2">Rôles</th>
                </tr>
                </thead>
                <tbody>
                <tr v-for="user in users.data" :key="user.id">
                    <td class="border border-gray-300 px-4 py-2">{{ user.first_name }} {{ user.last_name }}</td>
                    <td class="border border-gray-300 px-4 py-2">{{ user.email }}</td>
                    <td class="border border-gray-300 px-4 py-2">
                        <span v-for="role in user.roles" :key="role.id"
                              class="bg-blue-100 text-blue-600 px-2 py-1 rounded mr-1">
                            {{ role.name }}
                        </span>
                    </td>
                </tr>
                </tbody>
            </table>

            <!-- Pagination -->
            <div class="mt-4 flex justify-between items-center">
                <button
                    class="bg-gray-200 text-gray-700 px-4 py-2 rounded hover:bg-gray-300"
                    :disabled="!users.prev_page_url"
                    @click="goToPage(users.prev_page_url)">
                    Précédent
                </button>

                <span class="text-gray-500">
                Page {{ users.current_page }} sur {{ users.last_page }}
            </span>

                <button
                    class="bg-gray-200 text-gray-700 px-4 py-2 rounded hover:bg-gray-300"
                    :disabled="!users.next_page_url"
                    @click="goToPage(users.next_page_url)">
                    Suivant
                </button>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
h1 {
    color: #1abc9c;
}

.page-all {
    background: rgb(249, 245, 242);
    padding: 20px;
    border-radius: 10px;
}
</style>
