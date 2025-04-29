<script setup>
import { ref, computed } from 'vue';
import { router, usePage, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    users: Object,  // pagination Inertia
    events: Object, // pagination Inertia
    userRole: Array,
});

const page = usePage();
const currentUser = page.props.auth.user;
const currentUserId = currentUser.id;
const currentUserCreatedAt = new Date(currentUser.created_at);

const searchUser = ref('');
const searchEvent = ref('');
const sortField = ref('created_at');
const sortAsc = ref(true);

// Fonction de surbrillance des résultats recherchés
const highlight = (text, search) => {
    if (!search) return text;
    const regex = new RegExp(`(${search})`, 'gi');
    return text.replace(regex, '<mark class="bg-yellow-200">$1</mark>');
};

// Fonction de tri sur clic d'en-tête
const sortBy = (field) => {
    if (sortField.value === field) {
        sortAsc.value = !sortAsc.value;
    } else {
        sortField.value = field;
        sortAsc.value = true;
    }
};

// Détermine si l'utilisateur connecté est Super-admin
const isSuperAdmin = () => currentUser.role === 'Super-admin';

// Trie les utilisateurs selon la colonne choisie
const sortedUsers = computed(() => {
    return [...props.users.data].sort((a, b) => {
        let aField = a[sortField.value] ?? '';
        let bField = b[sortField.value] ?? '';

        if (typeof aField === 'string') aField = aField.toLowerCase();
        if (typeof bField === 'string') bField = bField.toLowerCase();

        return sortAsc.value
            ? aField > bField ? 1 : -1
            : aField < bField ? 1 : -1;
    });
});

// Filtrage des utilisateurs avec recherche
const filteredUsers = computed(() => {
    const query = searchUser.value.toLowerCase();
    return sortedUsers.value.filter(user =>
        user.pseudo.toLowerCase().includes(query) ||
        user.first_name.toLowerCase().includes(query) ||
        user.last_name.toLowerCase().includes(query) ||
        user.email.toLowerCase().includes(query)
    );
});

// Numérotation globale des utilisateurs paginés
const getUserGlobalIndex = (index) => {
    return ((props.users.current_page - 1) * props.users.per_page) + index + 1;
};

// Permissions pour bloquer certaines modifications de rôles
const cannotEditRole = (user) => {
    const isOlderSuperAdmin = user.roles[0]?.name === 'Super-admin' && new Date(user.created_at) < currentUserCreatedAt;
    return user.id === currentUserId || user.anonyme || isOlderSuperAdmin;
};

// Activation / désactivation utilisateur
const toggleActivation = (user) => {
    if (user.anonyme || user.roles.some(r => r.name === 'Super-admin')) return;
    if (!confirm(`Voulez-vous ${user.is_actif ? 'désactiver' : 'activer'} ${user.pseudo} ?`)) return;
    router.post(route('users.toggleActivation', user.id), {}, { preserveScroll: true });
};

// Anonymisation utilisateur
const anonymizeUser = (user) => {
    if (user.roles.some(r => r.name === 'Super-admin') || user.anonyme) return;
    if (!confirm(`Voulez-vous anonymiser ${user.pseudo} ?`)) return;
    router.post(route('users.anonymize', user.id), {}, { preserveScroll: true });
};

// Mise à jour du rôle utilisateur
const updateRole = (user, event) => {
    if (cannotEditRole(user)) return;
    const newRole = event.target.value;
    if (!confirm(`Attribuer le rôle "${newRole}" à ${user.pseudo} ?`)) return;
    router.post(route('users.updateRole', user.id), { role: newRole }, { preserveScroll: true });
};

// Recherche côté client pour événements (non paginés)
const filteredEvents = computed(() => {
    const query = searchEvent.value.toLowerCase();
    return props.events.filter(event =>
        event.name_event.toLowerCase().includes(query) ||
        event.location.toLowerCase().includes(query)
    );
});
</script>



<template>
    <AuthenticatedLayout>
        <div class="page-all">
            <h1 class="text-2xl font-bold text-teal-600 mb-6">Gestion Administration</h1>

            <!-- Tableau des utilisateurs -->
            <div class="mb-10">
                <h2 class="text-xl font-semibold mb-3">&#128100; Utilisateurs</h2>
                <input v-model="searchUser" type="text" placeholder="Recherche utilisateur..." class="mb-4 px-4 py-2 border rounded w-full" />

                <div class="overflow-x-auto">
                    <table class="table-auto w-full border-collapse border">
                        <thead class="bg-gray-100">
                        <tr>
                            <th class="px-4 py-2">#</th>
                            <th class="px-4 py-2 cursor-pointer" @click="sortBy('pseudo')">Pseudo</th>
                            <th class="px-4 py-2 cursor-pointer" @click="sortBy('first_name')">Prénom</th>
                            <th class="px-4 py-2 cursor-pointer" @click="sortBy('last_name')">Nom</th>
                            <th class="px-4 py-2">Email</th>
                            <th class="px-4 py-2">Connexion</th>
                            <th class="px-4 py-2">Rôle</th>
                            <th class="px-4 py-2">Statut</th>
                            <th class="px-4 py-2">Actions</th>
                        </tr>
                        </thead>

                        <tbody>
                        <tr v-for="(user, index) in filteredUsers" :key="user.id">
                            <td class="border px-3 py-2">{{ getUserGlobalIndex(index) }}</td>
                            <td class="border px-3 py-2">&#128100;
                                <Link :href="route('users.show', user.id)"
                                      class="text-blue-600 hover:underline font-semibold"  v-html="highlight(user.pseudo, searchUser)" />
                                <span v-if="user.id === currentUserId" class="text-teal-500 font-semibold"> (Moi)</span>
                            </td>
                            <td class="border px-3 py-2 break-words max-w-[200px]" v-html="highlight(user.first_name,
                            searchUser)" />

                            <td class="border px-3 py-2 break-words max-w-[200px]" v-html="highlight(user.last_name, searchUser)" />

                            <td class="border px-3 py-2">
                                📧 <span v-html="highlight(user.email, searchUser)" />
                            </td>

                            <td class="border px-3 py-2">&#128338;
                                {{ user.last_login
                                ? new Date(user.last_login).toLocaleString('fr-FR', { day: '2-digit', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit' })
                                : 'Jamais connecté' }}
                            </td>

                            <td class="border px-3 py-2">
                                <div v-if="isSuperAdmin() && !cannotEditRole(user)">
                                    <select :value="user.roles[0]?.name" @change="(e) => updateRole(user, e)" class="border rounded">
                                        <option value="User">User</option>
                                        <option value="Admin">Admin</option>
                                        <option value="Super-admin">Super-admin</option>
                                    </select>
                                </div>
                                <div v-else>
                                    <span class="bg-blue-100 text-blue-600 px-2 py-1 rounded">{{ user.roles[0]?.name }}</span>
                                </div>
                            </td>
                            <td class="border px-3 py-2">
                                <span :class="user.is_actif ? 'text-green-600' : 'text-red-600'">
                                    {{ user.is_actif ? 'Actif' : 'Inactif' }}
                                </span>
                            </td>
                            <td class="border px-3 py-2 space-x-2">
                                <button v-if="!user.anonyme && !user.roles.some(r => r.name === 'Super-admin')"
                                        @click="toggleActivation(user)"
                                        class="bg-green-500 hover:bg-green-600 text-white px-2 py-1 rounded">
                                    {{ user.is_actif ? 'Désactiver' : 'Activer' }}
                                </button>
                                <button v-if="!user.anonyme && !user.roles.some(r => r.name === 'Super-admin')"
                                        @click="anonymizeUser(user)"
                                        class="bg-red-500 hover:bg-red-600 text-white px-2 py-1 rounded">
                                    Anonymiser
                                </button>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination USERS -->
                <div class="flex justify-center items-center mt-4 space-x-2">
                    <template v-for="(link, index) in props.users.links" :key="index">
                        <span
                            v-if="link.url === null"
                            class="text-gray-400 px-3 py-1"
                            v-html="link.label"
                        />
                        <button
                            v-else
                            @click="router.get(link.url)"
                            class="px-3 py-1 rounded"
                            :class="{
                                'text-teal-600 font-bold': link.active,
                                'hover:underline': !link.active,
                            }"
                            v-html="link.label"
                        />
                    </template>
                </div>
            </div>

            <!-- Tableau des événements -->
            <div class="mt-10">
                <h2 class="text-xl font-semibold mb-3">&#127800; Événements</h2>
                <input v-model="searchEvent" type="text" placeholder="Recherche événement..." class="mb-4 px-4 py-2 border rounded w-full" />

                <div class="overflow-x-auto">
                    <table class="table-auto w-full border-collapse border">
                        <thead class="bg-gray-100">
                        <tr>
                            <th class="px-4 py-2">#</th>
                            <th class="px-4 py-2">Évènement</th>
                            <th class="px-4 py-2">Date</th>
                            <th class="px-4 py-2">Heure</th>
                            <th class="px-4 py-2">Lieu</th>
                            <th class="px-4 py-2">Participants</th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr v-for="(event, index) in filteredEvents" :key="event.id">
                            <td class="border px-3 py-2">{{ index + 1 }}</td>
                            <td class="border px-3 py-2">	&#127800;
                                <Link :href="route('events.show', event.id)" class="text-teal-600 hover:underline font-semibold" v-html="highlight(event.name_event, searchEvent)" />
                            </td>
                            <td class="border px-3 py-2">{{ event.date }}</td>
                            <td class="border px-3 py-2">{{ event.hour }}</td>
                            <td class="border px-3 py-2">{{ event.location }}</td>
                            <td class="border px-3 py-2">{{ event.min_person }} - {{ event.max_person }}</td>
                        </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination EVENTS -->
                <div class="flex justify-center items-center mt-4 space-x-2">
                    <template v-for="(link, index) in props.events.links" :key="index">
                        <span
                            v-if="link.url === null"
                            class="text-gray-400 px-3 py-1"
                            v-html="link.label"
                        />
                        <button
                            v-else
                            @click="router.get(link.url)"
                            class="px-3 py-1 rounded"
                            :class="{
                                'text-teal-600 font-bold': link.active,
                                'hover:underline': !link.active,
                            }"
                            v-html="link.label"
                        />
                    </template>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.page-all {
    background: #f9f5f2;
    padding: 20px;
    border-radius: 10px;
}
mark {
    background-color: #fef08a;
    padding: 2px 4px;
    border-radius: 3px;
}
</style>

