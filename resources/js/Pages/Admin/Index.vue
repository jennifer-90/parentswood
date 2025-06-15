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

const getEventGlobalIndex = (index) => {
    return ((props.events.current_page - 1) * props.events.per_page) + index + 1;
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

const filteredEvents = computed(() => {
    const query = searchEvent.value.toLowerCase();
    return props.events.data.filter(event =>
        event.name_event.toLowerCase().includes(query) ||
        event.location.toLowerCase().includes(query)
    );
});


const toggleEventActivation = (event) => {
    if (!confirm(`Voulez-vous ${event.inactif ? 'activer' : 'désactiver'} l'événement "${event.name_event}" ?`)) return;

    router.post(route('events.toggleActive', event.id), {}, {
        preserveScroll: true,
        onSuccess: () => {
            console.log('Statut mis à jour');
        },
        onError: () => {
            alert('Erreur lors du changement de statut.');
        }
    });
};


const acceptEvent = (event) => {
    if (!confirm(`Accepter l'événement "${event.name_event}" ?`)) return;

    router.post(route('events.accept', event.id), {}, {
        preserveScroll: true,

        onSuccess: () => {
            router.visit(route('admin.index'), {
                preserveScroll: true,
                preserveState: false, // recharge propre
            });
        },

        onError: () => {
            alert('Erreur lors de l’acceptation.');
        },
    });
};


const refuseEvent = (event) => {
    if (!confirm(`Refuser l'événement "${event.name_event}" ?`)) return;

    router.post(route('events.refuse', event.id), {}, {
        preserveScroll: true,
        onSuccess: () => {
            router.visit(route('admin.index')); // <-- recharge propre
        },
        onError: () => {
            alert('Erreur lors du refus.');
        },
    });
};

const isPending = (event) => event.confirmed === null || event.confirmed === 'null';







</script>

<template>
    <AuthenticatedLayout>
        <div class="page-all">
            <h1 class="text-2xl font-bold text-teal-600 mb-6"><i class="fa fa-cog fa-spin fa-1x fa-fw"></i> Gestion Administration </h1>

            <!-- ======================= USERS ======================-->
            <!-- Tableau des utilisateurs -->
            <div class="mb-10">
                <h2 class="text-xl font-semibold mb-3">&#128100; Utilisateurs</h2>

                <div class="mb-6 flex space-x-4">
                    <a :href="route('admin.export.users')" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded">
                        <i class="fa-solid fa-download"></i> Exporter les utilisateurs (.csv)
                    </a>
                </div>
                <input v-model="searchUser" type="text" placeholder="Recherche utilisateur..." class="mb-4 px-4 py-2 border rounded w-full" />

                <div class="overflow-x-auto">
                    <table class="table-auto w-full border-collapse border">
                        <thead class="bg-gray-100">
                        <tr>
                            <th class="px-4 py-2">#</th>
                            <th class="px-4 py-2 cursor-pointer" @click="sortBy('pseudo')">Pseudo</th>
                            <th class="px-4 py-2 cursor-pointer" @click="sortBy('first_name')">Prénom</th>
                            <th class="px-4 py-2 cursor-pointer" @click="sortBy('last_name')">Nom</th>
                            <th class="px-4 py-2">Email  </th>
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
                                <i class="fa-solid fa-envelope"></i>&nbsp; <span v-html="highlight( user.email, searchUser)" />
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
                                <button v-if="currentUser.role === 'Super-admin' && !user.anonyme && !user.roles.some(r => r.name === 'Super-admin')"
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


            <!-- ======================= ÉVÉNEMENTS ======================-->
            <div class="mt-10">
                <h2 class="text-xl font-semibold mb-3"><i class="fa-solid fa-calendar"></i> Événements</h2>
                <div>
                    <a :href="route('admin.export.events')" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded">
                        <i class="fa-solid fa-download"></i> Exporter les événements (.csv)
                    </a>
                </div><br>
                <input
                    v-model="searchEvent"
                    type="text"
                    placeholder="Recherche événement..."
                    class="mb-4 px-4 py-2 border rounded w-full"
                />

                <div class="overflow-x-auto">
                    <table class="table-auto w-full border-collapse border">
                        <thead class="bg-gray-100">
                        <tr>
                            <th class="px-4 py-2">#</th>
                            <th class="px-4 py-2">Évènement</th>
                            <th class="px-4 py-2">Date</th>
                            <th class="px-4 py-2">Heure</th>
                            <th class="px-4 py-2">Lieu</th>
                            <th class="px-4 py-2">Créateur</th>
                            <th class="px-4 py-2">Participants</th>
                            <th class="px-4 py-2">Statut</th>
                            <th class="px-4 py-2">Confirmation</th>
                            <th class="px-4 py-2">Actions</th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr
                            v-for="(event, index) in filteredEvents"
                            :key="event.id"
                            :class="{ 'bg-orange-50': event.confirmed === null }"
                        >
                            <td class="border px-3 py-2">{{ getEventGlobalIndex(index)}}</td>
                            <td class="border px-3 py-2">
                                <i class="fa-solid fa-users"></i>&nbsp;
                                <Link
                                    :href="route('events.show', event.id)"
                                    class="text-teal-600 hover:underline font-semibold"
                                    v-html="highlight(event.name_event, searchEvent)"
                                />
                            </td>
                            <td class="border px-3 py-2">{{ new Date(event.date).toLocaleDateString('fr-BE') }}</td>
                            <td class="border px-3 py-2">{{ new Date(`1970-01-01T${event.hour}`).toLocaleTimeString('fr-BE', { hour: '2-digit', minute: '2-digit' }) }}</td>
                            <td class="border px-3 py-2 break-words max-w-[200px]">{{ event.location }}</td>
                            <td class="border px-3 py-2 break-words max-w-[200px]">
                                <Link
                                    v-if="event.creator"
                                    :href="route('users.show', event.creator.id)"
                                    class="text-blue-600 hover:underline"
                                >&#128100;
                                    {{ event.creator.pseudo }}
                                </Link>
                                <span v-else>—</span>
                            </td>

                            <td class="border px-3 py-2">{{ event.min_person }} - {{ event.max_person }}</td>

                            <td class="border px-3 py-2">
        <span :class="event.inactif ? 'text-red-600' : 'text-green-600'">
            {{ event.inactif ? 'Inactif' : 'Actif' }}
        </span>
                            </td>

                            <td class="border px-3 py-2">
                                <!-- Ecrit "accepté" dans la colonne de confirmation -->
                                <span v-if="event.confirmed == true" class="text-600">
                                    <i class="fa fa-check" aria-hidden="true"></i> Accepté
                                    <span v-if="event.validated_by" class="italic text-gray-500">
                                        ( <i class="fa fa-arrow-right" aria-hidden="true"></i>
                                        par {{ event.validated_by.pseudo }} | le {{ new Date(event.validated_at).toLocaleDateString('fr-BE') }})
                                    </span>

                                </span>
                                <!-- Ecrit "refusé" dans la colonne de confirmation -->
                                <span v-else-if="event.confirmed == false" class="text-600">
                                    <i class="fa fa-close"></i> Refusé
                                    <span v-if="event.validated_by" class="italic text-gray-500">
                                        ( <i class="fa fa-arrow-right" aria-hidden="true"></i>
                                        par {{ event.validated_by.pseudo }} | le {{ new Date(event.validated_at).toLocaleDateString('fr-BE') }})
                                    </span>
                                </span>
                                <!-- Ecrit "En attente" dans la colonne de confirmation -->
                                <span v-else class="text-gray-600">En attente</span>
                            </td>

                            <td class="border px-3 py-2 space-x-2">
                                <!-- "v-if="event.confirmed !== null" ====> Button s'affiche pour les évènts acceptés ou refusés, mais pas ceux en attente -->
                                <button
                                    @click="toggleEventActivation(event)"
                                    v-if="event.confirmed !== null"
                                    class="bg-blue-500 hover:bg-blue-600 text-white px-2 py-1 rounded"
                                >
                                    {{ event.inactif ? 'Activer' : 'Désactiver' }}
                                </button>

                                <template v-if="event.confirmed === null">
                                    <button
                                        @click="acceptEvent(event)"
                                        class="bg-green-500 hover:bg-green-600 text-white px-2 py-1 rounded"
                                    >
                                        Accepter
                                    </button>

                                    <button
                                        @click="refuseEvent(event)"
                                        class="bg-red-500 hover:bg-red-600 text-white px-2 py-1 rounded"
                                    >
                                        Refuser
                                    </button>
                                </template>
                            </td>

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

