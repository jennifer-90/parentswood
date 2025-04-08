<script setup>
import { defineProps } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";

// Props reçues de Laravel (utilisateurs paginés + rôle de l'utilisateur connecté)
const props = defineProps({
    users: Object,
    userRole: Array,
});

// Accès à l'utilisateur connecté
const page = usePage();
const currentUserId = page.props.auth.user.id;

// Fonction AJAX pour changer le statut actif/inactif d’un utilisateur
const toggleActivation = (user) => {
    // Confirmation avant d'envoyer la requête
    if (!confirm(`Es-tu sûr de vouloir ${user.is_actif ? 'désactiver' : 'activer'} ${user.pseudo} ?`)) {
        return;
    }

    router.post(route('users.toggleActivation', user.id), {}, {
        preserveScroll: true,
        onSuccess: () => {
            console.log('Statut mis à jour');
        },
        onError: () => {
            alert("Une erreur s'est produite.");
        }
    });
};

// Fonction pour la pagination
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
                    <th v-if="props.userRole.includes('Super-admin')" class="border border-gray-300 px-4 py-2">Action</th>
                    <th class="border border-gray-300 px-4 py-2">Statut</th>
                </tr>
                </thead>

                <tbody>
                <tr v-for="user in props.users.data" :key="user.id">
                    <!-- Nom -->
                    <td class="border border-gray-300 px-4 py-2">
                        {{ user.first_name }} {{ user.last_name }}
                        <span v-if="user.id === currentUserId" class="text-sm text-teal-500 font-semibold">(Moi)</span>
                    </td>

                    <!-- Email -->
                    <td class="border border-gray-300 px-4 py-2">{{ user.email }}</td>

                    <!-- Rôles -->
                    <td class="border border-gray-300 px-4 py-2">
                        <span v-for="role in user.roles" :key="role.id"
                              class="bg-blue-100 text-blue-600 px-2 py-1 rounded mr-1">
                            {{ role.name }}
                        </span>
                    </td>

                    <!-- Colonne action visible uniquement si connecté en Super-admin -->
                    <td v-if="props.userRole.includes('Super-admin')"
                        class="border border-gray-300 px-4 py-2 text-center">

                        <!-- Cas 1 : Cible est Admin ou Super-admin → affichage bloqué -->
                        <span v-if="user.roles.some(r => ['Admin', 'Super-admin'].includes(r.name))"
                              class="text-gray-500 italic flex items-center justify-center gap-1">Impossible à désactiver
                        </span>

                        <!-- Cas 2 : Utilisateur désactivable (non admin/super-admin) -->
                        <button
                            v-else
                            @click="toggleActivation(user)"
                            class="bg-red-500 hover:bg-red-600 text-white font-bold py-1 px-3 rounded">
                            {{ user.is_actif ? 'Désactiver' : 'Activer' }}
                        </button>
                    </td>


                    <!-- Statut -->
                    <td class="border border-gray-300 px-4 py-2 text-center">
                        <span :class="user.is_actif ? 'text-green-600' : 'text-red-600'">
                            {{ user.is_actif ? 'Actif' : 'Inactif' }}
                        </span>
                    </td>
                </tr>
                </tbody>
            </table>

            <!-- Pagination -->
            <div class="mt-4 flex justify-between items-center">
                <button
                    class="bg-gray-200 text-gray-700 px-4 py-2 rounded hover:bg-gray-300"
                    :disabled="!props.users.prev_page_url"
                    @click="goToPage(props.users.prev_page_url)">
                    Précédent
                </button>

                <span class="text-gray-500">
                    Page {{ props.users.current_page }} sur {{ props.users.last_page }}
                </span>

                <button
                    class="bg-gray-200 text-gray-700 px-4 py-2 rounded hover:bg-gray-300"
                    :disabled="!props.users.next_page_url"
                    @click="goToPage(props.users.next_page_url)">
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
