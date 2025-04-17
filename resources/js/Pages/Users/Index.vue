<script setup>
import { defineProps, ref, computed } from 'vue';
import { router, usePage, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";

const props = defineProps({
    users: Object,
    userRole: Array,
});

const page = usePage();
const totalUsers = computed(() => page.props.totalUsers);
const currentUserId = page.props.auth.user.id;
const currentUserCreatedAt = page.props.auth.user.created_at ? new Date(page.props.auth.user.created_at) : null;
const search = ref('');

const cannotEditRole = (user) => {
    const isOlderSuperAdmin = user.roles[0]?.name === 'Super-admin' && new Date(user.created_at) < currentUserCreatedAt;
    return user.id === currentUserId || user.anonyme || isOlderSuperAdmin;
};

const filteredUsers = computed(() => {
    if (!search.value.trim()) return props.users.data;
    const query = search.value.toLowerCase();
    return props.users.data.filter(user =>
        user.pseudo.toLowerCase().includes(query) ||
        user.email.toLowerCase().includes(query) ||
        user.first_name.toLowerCase().includes(query) ||
        user.last_name.toLowerCase().includes(query)
    );
});

const highlightMatch = (text) => {
    if (!search.value.trim()) return text;
    const regex = new RegExp(`(${search.value})`, 'gi');
    return text.replace(regex, '<mark class="bg-yellow-200">$1</mark>');
};

const toggleActivation = (user) => {
    if (user.anonyme) return;
    if (!confirm(`Es-tu sûr de vouloir ${user.is_actif ? 'désactiver' : 'activer'} ${user.pseudo} ?`)) return;
    router.post(route('users.toggleActivation', user.id), {}, {
        preserveScroll: true,
        onSuccess: () => alert('Statut mis à jour'),
        onError: () => alert("Une erreur s'est produite."),
    });
};

const anonymizeUser = (user) => {
    if (!confirm(`Es-tu sûr de vouloir anonymiser ${user.pseudo} ? Cette action est irréversible.`)) return;
    router.post(route('users.anonymize', user.id), {}, {
        preserveScroll: true,
        onSuccess: () => alert('Utilisateur anonymisé'),
        onError: () => alert("Une erreur s'est produite lors de l'anonymisation."),
    });
};

const updateRole = (user, event) => {
    if (cannotEditRole(user)) return;
    const selectedRole = event.target.value;
    if (!confirm(`Souhaites-tu vraiment attribuer le rôle \"${selectedRole}\" à ${user.pseudo} ?`)) return;
    router.post(route('users.updateRole', user.id), { role: selectedRole }, {
        preserveScroll: true,
        onSuccess: () => alert('Rôle mis à jour'),
        onError: () => alert("Une erreur s'est produite lors de la mise à jour du rôle."),
    });
};

const goToPage = (url) => {
    if (url) router.get(url);
};
</script>

<template>
    <AuthenticatedLayout>
        <div class="page-all">
            <h1 class="text-2xl font-bold text-teal-600 mb-4 flex items-center gap-3">
                Liste des utilisateurs
                <span class="text-sm font-medium bg-teal-100 text-teal-800 px-3 py-1 rounded-full shadow-sm">
                    {{ totalUsers }} inscrits
                </span>
            </h1>

            <input v-model="search" type="text" placeholder="Rechercher un pseudo, prénom, nom ou email..."
                   class="mb-4 px-4 py-2 border border-gray-300 rounded w-full" />

            <div class="overflow-x-auto">
                <table class="table-auto w-full border-collapse border border-gray-300 min-w-[1000px]">
                    <thead>
                    <tr class="bg-gray-100">
                        <th class="border border-gray-300 px-4 py-2">Pseudo</th>
                        <th class="border border-gray-300 px-4 py-2">Prénom</th>
                        <th class="border border-gray-300 px-4 py-2">Nom</th>
                        <th class="border border-gray-300 px-4 py-2">Email</th>
                        <th class="border border-gray-300 px-4 py-2">Rôle</th>
                        <th class="border border-gray-300 px-4 py-2">Dernière connexion</th>
                        <th v-if="props.userRole.includes('Admin') || props.userRole.includes('Super-admin')" class="border border-gray-300 px-4 py-2">Activation</th>
                        <th v-if="props.userRole.includes('Super-admin')" class="border border-gray-300 px-4 py-2">Anonymat</th>
                        <th class="border border-gray-300 px-4 py-2">Statut</th>
                    </tr>
                    </thead>

                    <tbody>
                    <tr v-for="user in filteredUsers" :key="user.id">
                        <td class="border border-gray-300 px-4 py-2">
                            <Link
                                v-if="!user.anonyme"
                                :href="route('users.show', user.id)"
                                class="text-blue-600 hover:underline font-medium"
                                v-html="highlightMatch(user.pseudo)"
                            />
                            <span v-else v-html="highlightMatch(user.pseudo)"></span>
                            <span v-if="user.id === currentUserId" class="text-sm text-teal-500 font-semibold"> (Moi)</span>
                        </td>
                        <td class="border border-gray-300 px-4 py-2" v-html="highlightMatch(user.first_name)"></td>
                        <td class="border border-gray-300 px-4 py-2" v-html="highlightMatch(user.last_name)"></td>
                        <td class="border border-gray-300 px-4 py-2" v-html="highlightMatch(user.email)"></td>
                        <td class="border border-gray-300 px-4 py-2 text-center">
                            <div v-if="props.userRole.includes('Super-admin')">
                                <select :value="user.roles[0]?.name" @change="(event) => updateRole(user, event)"
                                        class="px-2 py-1 border rounded"
                                        :disabled="cannotEditRole(user)">
                                    <option value="User">User</option>
                                    <option value="Admin">Admin</option>
                                    <option value="Super-admin">Super-admin</option>
                                </select>
                            </div>
                            <div v-else>
                                <span v-for="role in user.roles" :key="role.id"
                                      class="bg-blue-100 text-blue-600 px-2 py-1 rounded">
                                    {{ role.name }}
                                </span>
                            </div>
                        </td>
                        <td class="border border-gray-300 px-4 py-2 text-center">
                            {{ user.last_login ? new Date(user.last_login).toLocaleString() : 'Jamais' }}
                        </td>
                        <td v-if="props.userRole.includes('Admin') || props.userRole.includes('Super-admin')" class="border border-gray-300 px-4 py-2 text-center">
                            <button
                                v-if="!user.roles.some(r => ['Admin', 'Super-admin'].includes(r.name)) && !user.anonyme"
                                @click="toggleActivation(user)"
                                class="bg-teal-600 hover:bg-teal-700 text-white font-bold py-1 px-3 rounded">
                                {{ user.is_actif ? 'Désactiver' : 'Activer' }}
                            </button>
                            <span v-else class="text-gray-500 italic">Impossible à désactiver</span>
                        </td>
                        <td v-if="props.userRole.includes('Super-admin')" class="border border-gray-300 px-4 py-2 text-center">
                            <button
                                v-if="!user.roles.some(r => ['Admin', 'Super-admin'].includes(r.name)) && !user.anonyme"
                                @click="anonymizeUser(user)"
                                class="bg-teal-600 hover:bg-teal-700 text-white font-bold py-1 px-3 rounded">
                                Anonymiser
                            </button>
                            <span v-else-if="user.anonyme" class="text-gray-500 italic">Déjà anonymisé</span>
                            <span v-else class="text-gray-500 italic">Impossible à anonymiser</span>
                        </td>
                        <td class="border border-gray-300 px-4 py-2 text-center">
                            <span :class="user.is_actif ? 'text-green-600' : 'text-red-600'">
                                {{ user.is_actif ? 'Actif' : 'Inactif' }}
                            </span>
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>

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

mark {
    background-color: #fef08a;
    padding: 0 2px;
    border-radius: 2px;
    font-weight: 600;
}
</style>
