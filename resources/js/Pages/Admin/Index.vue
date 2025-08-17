<script setup>

import {ref, computed} from 'vue';
import {router, usePage, Link, Head, useRemember} from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { watch } from 'vue';


const route = window.route;
const debounce = (fn, d=300) => {
    let t;
    return (...a)=>{
        clearTimeout(t);
        t=setTimeout(()=>fn(...a), d);
    };
};

/****************************************************************/
/*==============================================================*/
/* -- PROPS: données envoyées par le contrôleur vers Inertia -- */
const props = defineProps({
    users: Object,
    events: Object,
    userRole: Array,
});
/*==============================================================*/
/****************************************************************/


const getUserGlobalIndex = (index) => {
    return ((props.users.current_page - 1) * props.users.per_page) + index + 1;
};
const getEventGlobalIndex = (index) => {
    return ((props.events.current_page - 1) * props.events.per_page) + index + 1;
};

/* ########## INFOS DU USER CONNECTE A L'HEURE ACTUEL ##########################################################*/
const page = usePage(); // donne accès à tout ce qu'Inertia expose (props, auth, etc.)
const nowConnectUser = page.props.auth.user; // récupère l’utilisateur connecté
const nowConnectUserId = nowConnectUser.id;
const nowConnectUserCreatedAt = new Date(nowConnectUser.created_at); // sa date de création => règle de “seniorité” avec isOlderSuperAdmin



/* ########## TEXTE DYNAMIQUE POUR LE TITRE DE LA PAGE ADMIN ##################################################*/
const adminTitle = computed(() => {
    if (nowConnectUser.role === 'Super-admin') {
        return 'Gestion Super Admin';
    }
    if (nowConnectUser.role === 'Admin') {
        return 'Gestion Admin';
    }
});

/* ########## CHANGER UN ROLE ##################################################################################*/
const isSuperAdmin = () => nowConnectUser.role === 'Super-admin'; //déclartion de la variable que si le user connecté a le role de super-admin
const isSelf = (user) => user.id === nowConnectUserId;

/** Détermine si le menu déroulant (option/select) des rôles doit être désactivé pour ce user */
const cannotEditRole = (user) => {
    const targetRole = user.roles?.[0]?.name || 'User'; // "?." ==> évite une erreur si roles n’existe pas (De base impossible)

    // Super-admin qui veut changer le rôle d'un autre utilisateur
    if (isSuperAdmin()) {
        const isOlderSuperAdmin = targetRole === 'Super-admin'
            && new Date(user.created_at) < nowConnectUserCreatedAt;
        return isSelf(user) || //la personne connecté elle-meme
            user.anonyme || //la personne est anonymisée
            isOlderSuperAdmin; //c’est un Super-admin plus ancien que la personne qui essaye de modifier
    }

    // Pour l'admin : pas soi-même, pas un Super-admin, pas un anonymisé
    if (nowConnectUser.role === 'Admin') {
        return isSelf(user) ||
            user.anonyme ||
            targetRole === 'Super-admin';
    }

    // Tous les autres rôles : non modifiable
    return true;
};


const updateRole = (user, event) => {
    if (cannotEditRole(user)) return;
    const newRole = event.target.value;
    if (!confirm(`Attribuer le rôle "${newRole}" à ${user.pseudo} ?`)) return;
    router.post(route('users.updateRole', user.id), {role: newRole}, {preserveScroll: true});
};

/* ########## BARRE DE RECHERCHE ############################################################################*/
/** escapeHtml ==> But : Protège des injections HTML (XSS) quand on insère du HTML à la main (v-html)*/
const escapeHtml = (s) =>
    String(s ?? '').replace(/[&<>"]/g,
        c => ({
            '&':'&amp;',
            '<':'&lt;',
            '>':'&gt;',
            '"':'&quot;'
        }[c]));
        // Ex.: "<scr" + "ipt>alert(1)</scr" + "ipt>" devient &lt;script&gt;alert(1)&lt;/script&gt;.


/** Surligne le terme recherché avec <mark> en évitant le XSS  - On échappe d'abord le HTML pour éviter le XSS */
const highlight = (text, search) => {
    const safe = escapeHtml(text);
    if (!search) return safe; //Si aucun terme recherché, on renvoie le texte tel quel.

    // On échappe les caractères spéciaux de "search" pour la RegExp.
    const esc = search.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'); //préparation (échapper la recherche pour la regex).
    return safe.replace(new RegExp(`(${esc})`, 'gi'), //fonctionnalité (surlignage avec <mark>).
        '<mark class="bg-yellow-200 px-1 rounded">$1</mark>');
};

//searchUser persiste entre les pages/pagination (grâce à useRemember)
const searchUser = useRemember(props.filters?.search ?? '', 'admin.users.search');
const searchEvent = ref('');


watch(
    searchUser,
    debounce((v) => {
        router.get(
            route('admin.index'),
            { search: v },
            {
                preserveState: true,
                replace: true,
                preserveScroll: true  // ne remonte pas la page
            });
    }));

/* ########## TRIER/FILTRER LE TABLEAU USER ####################################################################*/

const sortField = ref('created_at');
const sortAsc = ref(true);

const sortBy = (field) => {
    if (sortField.value === field) {
        sortAsc.value = !sortAsc.value;
    } else {
        sortField.value = field;
        sortAsc.value = true;
    }
};

const sortedUsers = computed(() => {
    const fieldName   = sortField.value; // colonne choisie (pseudo, email, ...)
    const isAscending = sortAsc.value;   // sens du tri

    // 0) Petit helper pour convertir proprement "anonyme" en booléen (gère 0/1, "0"/"1", true/false, null)
    const toBool = (x) => !!Number(x);

    // 1) Séparer les utilisateurs : non-anonymes d’un côté, anonymes de l’autre
    const nonAnonymous = props.users.data.filter(u => !toBool(u.anonyme));
    const anonymous    = props.users.data.filter(u =>  toBool(u.anonyme));

    // 2) Définir un comparateur pour trier par la colonne choisie
    const normalize = (v) => {
        if (v == null) return '';                    // null/undefined -> chaîne vide
        return typeof v === 'string' ? v.toLowerCase() : v; // strings en minuscule pour un tri case-insensitive
    };

    const compareByField = (a, b) => {
        const aValue = normalize(a[fieldName]);
        const bValue = normalize(b[fieldName]);

        if (aValue < bValue) return isAscending ? -1 : 1;
        if (aValue > bValue) return isAscending ?  1 : -1;
        return 0; // égalité
    };

    // 3) On trie chaque groupe, puis on remet les non-anonymes en premier
    nonAnonymous.sort(compareByField);
    anonymous.sort(compareByField);

    return [...nonAnonymous, ...anonymous];
});

const filteredUsers = computed(() => {
    const query = searchUser.value.toLowerCase();
    return sortedUsers.value.filter(user =>
        user.pseudo.toLowerCase().includes(query) ||
        user.first_name.toLowerCase().includes(query) ||
        user.last_name.toLowerCase().includes(query) ||
        user.email.toLowerCase().includes(query)
    );
});

/* ########## TRIER/FILTRER LE TABLEAU EVENTS ###############################################################*/
const filteredEvents = computed(() => {
    const query = searchEvent.value.toLowerCase();
    return props.events.data.filter(event =>
        event.name_event.toLowerCase().includes(query) ||
        event.location.toLowerCase().includes(query)
    );
});

/* ########## ACTIVER/DESACTIVER UN USER  ##################################################################*/
const toggleActivation = (user) => {
    if (user.anonyme || user.roles.some(r => r.name === 'Super-admin')) return;
    if (!confirm(`Voulez-vous ${user.is_actif ? 'désactiver' : 'activer'} ${user.pseudo} ?`)) return;
    router.post(route('users.toggleActivation', user.id), {}, {preserveScroll: true});
};

/* ########## ANOMYSER UN USER #############################################################################*/
const anonymizeUser = (user) => {
    if (user.roles.some(r => r.name === 'Super-admin') || user.anonyme) return;
    if (!confirm(`Voulez-vous anonymiser ${user.pseudo} ?`)) return;
    router.post(route('users.anonymize', user.id), {}, {preserveScroll: true});
};

/* ########## ACTIVER/DESACTIVER UN EVENT ##################################################################*/
const toggleEventActivation = (event) => {
    if (!confirm(`Voulez-vous ${event.inactif ? 'activer' : 'désactiver'} l'événement "${event.name_event}" ?`)) {
        return;
    }

    router.post(route('events.toggleActive', event.id), {}, {
        preserveScroll: true,
        onSuccess: () => {
            // Mettre à jour l'état local
            event.inactif = !event.inactif;
        },
        onError: (errors) => {
            console.error('Erreur:', errors);
            alert('Une erreur est survenue lors de la mise à jour du statut.');
        }
    });
};

/* ########## VALIDER/REFUSER UN EVENT #######################################################################*/
const acceptEvent = (event) => {
    if (!confirm(`Accepter l'événement "${event.name_event}" ?`)) return;
    router.post(route('events.accept', event.id), {}, {
        preserveScroll: true,
        onSuccess: () => router.visit(route('admin.index'), {preserveScroll: true, preserveState: false}),
        onError: () => alert('Erreur lors de l\'acceptation.')
    });
};

const refuseEvent = (event) => {
    if (!confirm(`Refuser l'événement "${event.name_event}" ?`)) return;
    router.post(route('events.refuse', event.id), {}, {
        preserveScroll: true,
        onSuccess: () => router.visit(route('admin.index')),
        onError: () => alert('Erreur lors du refus.')
    });
};

/* ########## CREER DES USERS FICTIFS #####################################################################/

/* ...imports existants... */
const seedUsers = () => {
    if (!confirm('Créer 10 utilisateurs de test ?')) return;
    router.post(route('admin.seed.users'), { count: 10 }, {
        preserveScroll: true,
        onSuccess: () => router.visit(route('admin.index'), {
            preserveScroll: true, preserveState: false
        }),
    });
};

</script>

<template>
    <Head :title="adminTitle"/>

    <AuthenticatedLayout>
        <div class="py-4 bg-[#f9f5f2] min-h-screen">
            <div class="mx-auto w-full px-3 sm:px-5">
                <div class="bg-white shadow-lg rounded-lg p-4 sm:p-6">


                    <!-- Titre principal avec icône -->
                    <div class="bg-transparent border-2 border-[#ffb347] rounded-lg p-4 mb-6"
                         style="background-color: rgba(255, 179, 71, 0.05);">
                        <div class="flex items-center justify-center gap-3">
                            <div class="bg-[#ffb347] text-white rounded-full p-3">
                                <i class="fa-solid fa-user-shield text-xl"></i>
                            </div>
                            <h1 class="text-2xl sm:text-3xl font-bold text-gray-800">{{ adminTitle }}</h1>
                        </div>
                    </div>

                    <!-- Bandeau d’accueil (toujours visible) -->
                    <div class="mb-6 rounded-lg border border-[#59c4b4]/30 bg-[#59c4b4]/10 p-4">
                        <div class="flex items-start gap-3">
                            <i class="fa-solid fa-circle-info mt-1 text-[#59c4b4]"></i>
                            <div>
                                <p class="font-semibold text-gray-800">Bienvenue dans l’espace d’administration</p>
                                <ul class="mt-2 text-sm text-gray-700 list-disc pl-5 space-y-1">
                                    <li>Utilisez les barres de recherche pour filtrer.</li>
                                    <li>Cliquez sur un <span class="font-semibold">Pseudo</span> pour ouvrir le profil.</li>
                                    <li>Cliquez sur les entêtes <span class="font-semibold">Pseudo / Nom / Email</span> pour trier.</li>
                                    <li>Les boutons permettent d’activer/désactiver, anonymiser ou changer un rôle.</li>

                                    <!-- Génération d’utilisateurs (réservé Super-admin) -->
                                    <li v-if="isSuperAdmin()">
                                        Générer 10 utilisateurs de test autant de fois que nécessaire
                                        (réservé <span class="font-semibold">Super-admin</span>).
                                    </li>

                                    <!-- NOUVEAU : export -->
                                    <li>
                                        Export CSV : utilisez les boutons “<span class="font-semibold">Exporter les utilisateurs</span>”
                                        et “<span class="font-semibold">Exporter les événements</span>”.
                                    </li>
                                    <li>
                                        Seul un <span class="font-semibold">Super-admin</span> peut attribuer le rôle « Super-admin ».
                                    </li>
                                    <li>
                                        Pour <span class="font-semibold">anonymiser</span> ou <span class="font-semibold">désactiver</span> un <span class="font-semibold">Admin</span>,
                                        un Super-admin doit d’abord le faire passer en « User ».
                                    </li>
                                    <li>
                                        Personne ne peut <span class="font-semibold">s’auto-désactiver</span> ni s’<span class="font-semibold">auto-anonymiser</span>.
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>


                    <!-- Layout responsive : vertical sur mobile, horizontal sur desktop -->
                    <!-- <div class="flex flex-col xl:flex-row gap-4 sm:gap-6">    || pivot - mettre côte à cote -->
                    <div class="flex flex-col gap-4 sm:gap-6">

                        <!-- Section Utilisateurs -->
                        <div class="xl:flex-1 bg-[#59c4b4]/10 p-4 rounded-lg shadow-md" style="min-height: 600px;">
                            <!-- Titre de section avec icône -->
                            <div class="bg-[#59c4b4] text-white rounded-lg p-3 mb-4 flex items-center gap-3">
                                <i class="fa-solid fa-users text-lg"></i>
                                <h2 class="text-lg font-semibold">Gestion des Utilisateurs</h2>
                            </div>

                            <!-- Barre de recherche -->
                            <div class="mb-4">
                                <input
                                    v-model="searchUser"
                                    type="text"
                                    placeholder="Rechercher un utilisateur..."
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#59c4b4] focus:border-transparent transition-all"
                                />
                            </div>

                            <!-- Bouton d'export -->
                            <div class="mb-4">
                                <a
                                    :href="route('admin.export.users')"
                                    class="inline-flex items-center gap-2 bg-gradient-to-r from-[#ffb347] to-[#ff9500] hover:from-[#ff9500] hover:to-[#e6850e] text-white px-4 py-2 rounded-lg font-semibold transition-all duration-300 transform hover:scale-105 shadow-md hover:shadow-lg"
                                >
                                    <i class="fa-solid fa-download"></i>
                                    Exporter les utilisateurs (.csv)
                                </a>
                            </div>

                            <!-- Bouton utilisateurs tests -->
                            <button
                                v-if="isSuperAdmin()"
                                @click="seedUsers"
                                class="inline-flex items-center gap-2 bg-gradient-to-r from-[#59c4b4] to-[#3aa796]
                             hover:from-[#3aa796] hover:to-[#318e80] text-white px-4 py-2 rounded-lg
                             font-semibold transition-all duration-300 transform hover:scale-105 shadow-md">
                                <i class="fa-solid fa-user-plus"></i>
                                Générer 10 utilisateurs tests
                            </button>
                            <br><br>


                            <!-- Table des utilisateurs -->
                            <div class="overflow-x-auto bg-white rounded-lg shadow-sm border border-gray-200">
                                <table class="min-w-full">
                                    <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider cursor-pointer hover:bg-gray-100 transition-colors"
                                            @click="sortBy('pseudo')">
                                            Pseudo
                                            <i v-if="sortField === 'pseudo'"
                                               :class="sortAsc ? 'fa fa-sort-up' : 'fa fa-sort-down'" class="ml-1"></i>
                                        </th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider cursor-pointer hover:bg-gray-100 transition-colors"
                                            @click="sortBy('first_name')">
                                            Nom
                                            <i v-if="sortField === 'first_name'"
                                               :class="sortAsc ? 'fa fa-sort-up' : 'fa fa-sort-down'" class="ml-1"></i>
                                        </th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider cursor-pointer hover:bg-gray-100 transition-colors"
                                            @click="sortBy('email')">
                                            Email
                                            <i v-if="sortField === 'email'"
                                               :class="sortAsc ? 'fa fa-sort-up' : 'fa fa-sort-down'" class="ml-1"></i>
                                        </th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                            Statut
                                        </th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                            Rôle
                                        </th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                            Actions
                                        </th>
                                    </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="(user, index) in filteredUsers" :key="user.id"
                                        :class="index % 2 === 0 ? 'bg-white' : 'bg-gray-50'"
                                        class="hover:bg-blue-50 transition-colors">

                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <Link
                                                :href="route('users.show', user.id)"
                                            class="font-medium text-blue-600 hover:underline focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                                            >
                                            <span v-html="highlight(user.pseudo, searchUser)"></span>
                                            </Link>
                                        </td>

                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <span v-html="highlight(user.first_name + ' ' + user.last_name, searchUser)"
                                                  class="text-gray-700"></span>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <span v-html="highlight(user.email, searchUser)"
                                                  class="text-gray-700"></span>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                                <span v-if="user.is_actif"
                                                      class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                    <span class="w-2 h-2 bg-green-400 rounded-full mr-1.5"></span>
                                                    Actif
                                                </span>
                                            <span v-else
                                                  class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                                    <span class="w-2 h-2 bg-gray-400 rounded-full mr-1.5"></span>
                                                    Inactif
                                                </span>
                                        </td>


                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <select
                                                :value="user.roles[0]?.name || 'User'"
                                                @change="updateRole(user, $event)"
                                                :disabled="cannotEditRole(user)"
                                                class="text-sm rounded-full px-3 py-1 font-medium border-0 focus:ring-2 focus:ring-[#59c4b4] transition-all"
                                                :class="{
                                                        'bg-red-100 text-red-800': user.roles[0]?.name === 'Super-admin',
                                                        'bg-blue-100 text-blue-800': user.roles[0]?.name === 'Admin',
                                                        'bg-green-100 text-green-800': user.roles[0]?.name === 'User',
                                                        'opacity-50 cursor-not-allowed': cannotEditRole(user)
                                                    }"
                                            >
                                                <option value="User">User</option>
                                                <option value="Admin">Admin</option>
                                                <option value="Super-admin" v-if="isSuperAdmin()">Super-admin</option>
                                            </select>
                                        </td>



                                        <td class="px-4 py-3 whitespace-nowrap space-x-2">
                                            <button
                                                @click="toggleActivation(user)"
                                                :disabled="
                                                user.id === nowConnectUserId || // NE PAS se désactiver soi-même
                                                user.anonyme ||
                                                user.roles.some(r => r.name === 'Super-admin') ||
                                                user.roles.some(r => r.name === 'Admin')//pas touche aux Super-admins
  "
                                                class="inline-flex items-center px-3 py-1.5 rounded-lg text-sm font-medium transition-all duration-300 transform hover:scale-105 shadow-sm hover:shadow-md disabled:opacity-50 disabled:cursor-not-allowed disabled:transform-none"
                                                :class="user.is_actif
                                                        ? 'bg-gradient-to-r from-red-500 to-red-600 hover:from-red-600 hover:to-red-700 text-white'
                                                        : 'bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white'"
                                            >
                                                <i :class="user.is_actif ? 'fa-solid fa-user-slash' : 'fa-solid fa-user-check'"
                                                   class="mr-1"></i>
                                                {{ user.is_actif ? 'Désactiver' : 'Activer' }}
                                            </button>

                                            <!-- Bouton Anonymiser -->
                                            <button
                                                v-if="isSuperAdmin()"
                                                @click="anonymizeUser(user)"
                                                :disabled="user.roles.some(r => ['Admin', 'Super-admin'].includes(r.name)) || user.anonyme"
                                                class="inline-flex items-center px-3 py-1.5 rounded-lg text-sm font-medium
                                               bg-gradient-to-r from-orange-500 to-orange-600
                                               hover:from-orange-600 hover:to-orange-700
                                               text-white transition-all duration-300 transform
                                               hover:scale-105 shadow-sm hover:shadow-md
                                               disabled:opacity-40 disabled:cursor-not-allowed disabled:shadow-none disabled:hover:scale-100 disabled:bg-gradient-to-r disabled:from-gray-300 disabled:to-gray-400 disabled:text-gray-700"
                                            >
                                                Anonymiser
                                            </button>

                                        </td>
                                    </tr>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Pagination Utilisateurs -->
                            <div class="flex justify-center items-center mt-4 space-x-2">
                                <template v-for="(link, index) in props.users.links" :key="index">
                                    <span
                                        v-if="link.url === null"
                                        class="text-gray-400 px-3 py-2 rounded-lg"
                                        v-html="link.label"
                                    />
                                    <button
                                        v-else
                                        @click="router.get(link.url)"
                                        class="px-3 py-2 rounded-lg transition-all duration-300 hover:shadow-md"
                                        :class="{
                                            'bg-[#59c4b4] text-white font-bold shadow-md': link.active,
                                            'bg-white text-[#59c4b4] border border-[#59c4b4] hover:bg-[#59c4b4] hover:text-white': !link.active,
                                        }"
                                        v-html="link.label"
                                    />
                                </template>
                            </div>
                        </div>
                        <!-- Section Événements -->
                        <div class="xl:flex-1 bg-[#59c4b4]/10 p-4 rounded-lg shadow-md" style="min-height: 600px;">
                            <!-- Titre de section avec icône -->
                            <div class="bg-[#59c4b4] text-white rounded-lg p-3 mb-4 flex items-center gap-3">
                                <i class="fa-solid fa-calendar-days text-lg"></i>
                                <h2 class="text-lg font-semibold">Gestion des Événements</h2>
                            </div>

                            <!-- Barre de recherche événements -->
                            <div class="mb-4">
                                <input
                                    v-model="searchEvent"
                                    type="text"
                                    placeholder="Rechercher un événement..."
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#59c4b4] focus:border-transparent transition-all"
                                />
                            </div>

                            <!-- Bouton d'export Événements -->
                            <div class="mb-4">
                                <a
                                    :href="route('admin.export.events', { q: searchEvent })"
                                    class="inline-flex items-center gap-2 bg-gradient-to-r from-[#ffb347] to-[#ff9500] hover:from-[#ff9500] hover:to-[#e6850e] text-white px-4 py-2 rounded-lg font-semibold transition-all duration-300 transform hover:scale-105 shadow-md hover:shadow-lg"
                                >
                                    <i class="fa-solid fa-download"></i>
                                    Exporter les événements (.csv)
                                </a>
                            </div>

                            <!-- Table des événements -->
                            <div class="overflow-x-auto bg-white rounded-lg shadow-sm border border-gray-200">
                                <table class="min-w-full">
                                    <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                            #
                                        </th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                            Nom
                                        </th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                            Lieu
                                        </th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                            Date
                                        </th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                            Créateur
                                        </th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                            Confirmation
                                        </th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                            Actions
                                        </th>
                                    </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="(event, index) in filteredEvents" :key="event.id"
                                        :class="index % 2 === 0 ? 'bg-white' : 'bg-gray-50'"
                                        class="hover:bg-blue-50 transition-colors">
                                        <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900">
                                            {{ getEventGlobalIndex(index) }}
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <span v-html="highlight(event.name_event, searchEvent)"
                                                  class="font-medium text-gray-900"></span>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <span v-html="highlight(event.location, searchEvent)"
                                                  class="text-gray-700"></span>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700">
                                            {{ new Date(event.date).toLocaleDateString('fr-BE') }}
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700">
                                            <span>{{ event.creator?.pseudo ?? 'Inconnu' }}</span>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                                <span v-if="event.confirmed == true"
                                                      class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                    <i class="fa fa-check mr-1"></i> Accepté
                                                    <span v-if="event.validated_by"
                                                          class="block text-xs text-gray-500 mt-1">
                                                        par {{
                                                            event.validated_by.pseudo
                                                        }} | {{
                                                            new Date(event.validated_at).toLocaleDateString('fr-BE')
                                                        }}
                                                    </span>
                                                </span>
                                            <span v-else-if="event.confirmed == false"
                                                  class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                    <i class="fa fa-close mr-1"></i> Refusé
                                                    <span v-if="event.validated_by"
                                                          class="block text-xs text-gray-500 mt-1">
                                                        par {{
                                                            event.validated_by.pseudo
                                                        }} | {{
                                                            new Date(event.validated_at).toLocaleDateString('fr-BE')
                                                        }}
                                                    </span>
                                                </span>
                                            <span v-else
                                                  class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                                    <i class="fa fa-clock mr-1"></i> En attente
                                                </span>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap space-x-2">
                                            <button
                                                @click="toggleEventActivation(event)"
                                                v-if="event.confirmed !== null"
                                                class="inline-flex items-center px-3 py-1.5 rounded-lg text-sm font-medium transition-all duration-300 transform hover:scale-105 shadow-sm hover:shadow-md"
                                                :class="event.inactif
        ? 'bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white'
        : 'bg-gradient-to-r from-red-500 to-red-600 hover:from-red-600 hover:to-red-700 text-white'"
                                            >
                                                {{ event.inactif ? 'Activer' : 'Désactiver' }}
                                            </button>

                                            <template v-if="event.confirmed === null">
                                                <button
                                                    @click="acceptEvent(event)"
                                                    class="inline-flex items-center px-3 py-1.5 rounded-lg text-sm font-medium bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white transition-all duration-300 transform hover:scale-105 shadow-sm hover:shadow-md"
                                                >
                                                    <i class="fa-solid fa-check mr-1"></i>
                                                    Accepter
                                                </button>

                                                <button
                                                    @click="refuseEvent(event)"
                                                    class="inline-flex items-center px-3 py-1.5 rounded-lg text-sm font-medium bg-gradient-to-r from-red-500 to-red-600 hover:from-red-600 hover:to-red-700 text-white transition-all duration-300 transform hover:scale-105 shadow-sm hover:shadow-md"
                                                >
                                                    <i class="fa-solid fa-times mr-1"></i>
                                                    Refuser
                                                </button>
                                            </template>
                                        </td>
                                    </tr>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Pagination Événements -->
                            <div class="flex justify-center items-center mt-4 space-x-2">
                                <template v-for="(link, index) in props.events.links" :key="index">
                                    <span
                                        v-if="link.url === null"
                                        class="text-gray-400 px-3 py-2 rounded-lg"
                                        v-html="link.label"
                                    />
                                    <button
                                        v-else
                                        @click="router.get(link.url)"
                                        class="px-3 py-2 rounded-lg transition-all duration-300 hover:shadow-md"
                                        :class="{
                                            'bg-[#59c4b4] text-white font-bold shadow-md': link.active,
                                            'bg-white text-[#59c4b4] border border-[#59c4b4] hover:bg-[#59c4b4] hover:text-white': !link.active,
                                        }"
                                        v-html="link.label"
                                    />
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
/* Styles pour la surbrillance des résultats de recherche */
mark {
    background-color: #fef08a;
    padding: 2px 4px;
    border-radius: 3px;
    font-weight: 600;
}

/* Animation pour les boutons */
/*.transition-all {
    transition: all 0.2s ease-in-out;
}*/

/* Styles pour les badges de statut */
.badge {
    display: inline-flex;
    align-items: center;
    padding: 0.25rem 0.75rem;
    border-radius: 9999px;
    font-size: 0.75rem;
    font-weight: 500;
}

/* Amélioration des tables */
table {
    border-collapse: separate;
    border-spacing: 0;
}

th {
    position: sticky;
    top: 0;
    z-index: 10;
}

/* Scrollbar personnalisée */
.overflow-x-auto::-webkit-scrollbar {
    height: 6px;
}

.overflow-x-auto::-webkit-scrollbar-track {
    background: #f1f5f9;
    border-radius: 3px;
}

.overflow-x-auto::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 3px;
}

.overflow-x-auto::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}

/* Effets hover pour les lignes de tableau */
/*
tr:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

*/
/* Animation pour les boutons avec gradient */
.bg-gradient-to-r {
    background-size: 200% 200%;
    /*animation: gradient-shift 3s ease infinite;*/
}

@keyframes gradient-shift {
    0% {
        background-position: 0% 50%;
    }
    50% {
        background-position: 100% 50%;
    }
    100% {
        background-position: 0% 50%;
    }
}

/* Styles pour les icônes */
.fa-solid, .fa {
    transition: transform 0.2s ease;
}

button:hover .fa-solid,
button:hover .fa {
    transform: scale(1.1);
}

/* Responsive design pour mobile */
@media (max-width: 640px) {
    .xl\:flex-row {
        flex-direction: column;
    }

    table {
        font-size: 0.875rem;
    }

    .px-4 {
        padding-left: 0.75rem;
        padding-right: 0.75rem;
    }

    .py-3 {
        padding-top: 0.5rem;
        padding-bottom: 0.5rem;
    }
}

/* Animation d'apparition pour les sections */
/*.xl\:flex-1 {
    animation: fadeInUp 0.6s ease-out;
}*/

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Styles pour les focus states */
input:focus,
select:focus {
    outline: none;
    box-shadow: 0 0 0 3px rgba(89, 196, 180, 0.1);
}

/* Amélioration des badges de rôles */
select option {
    padding: 0.5rem;
    background-color: white;
}

/* Styles pour les états disabled */
button:disabled {
    cursor: not-allowed;
    opacity: 0.5;
    transform: none !important;
}

button:disabled:hover {
    transform: none !important;
    box-shadow: none !important;
}

/* Animation pour les cartes principales */
/*.bg-white.shadow-lg {
    animation: slideInFromTop 0.8s ease-out;
}*/

@keyframes slideInFromTop {
    from {
        opacity: 0;
        transform: translateY(-30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Styles pour les titres de section */
.bg-\[#59c4b4\] {
    background: linear-gradient(135deg, #59c4b4 0%, #4db3a3 100%);
    box-shadow: 0 2px 4px rgba(89, 196, 180, 0.2);
}

/* Styles pour le titre principal */
.bg-\[#ffb347\] {
    background: linear-gradient(135deg, #ffb347 0%, #ff9500 100%);
    box-shadow: 0 2px 4px rgba(255, 179, 71, 0.2);
}

/* Amélioration des liens de pagination */
.space-x-2 button,
.space-x-2 span {
    min-width: 2.5rem;
    text-align: center;
}

/* Styles pour les états de confirmation des événements */
.bg-green-100 {
    background-color: rgba(34, 197, 94, 0.1);
    border: 1px solid rgba(34, 197, 94, 0.2);
}

.bg-red-100 {
    background-color: rgba(239, 68, 68, 0.1);
    border: 1px solid rgba(239, 68, 68, 0.2);
}

.bg-yellow-100 {
    background-color: rgba(245, 158, 11, 0.1);
    border: 1px solid rgba(245, 158, 11, 0.2);
}

/* Amélioration de l'accessibilité */
button:focus-visible {
    outline: 2px solid #59c4b4;
    outline-offset: 2px;
}

/* Styles pour les états de chargement */
.loading {
    position: relative;
    overflow: hidden;
}

.loading::after {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.4), transparent);
    animation: loading 1.5s infinite;
}

@keyframes loading {
    0% {
        left: -100%;
    }
    100% {
        left: 100%;
    }
}

/* Styles additionnels pour l'harmonie visuelle */
.hover\:scale-105:hover {
    transform: scale(1.05);
}

/* Amélioration des transitions sur les éléments interactifs */
button, select, input {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

/* Styles pour les badges de statut avec animations */
.inline-flex.items-center {
    transition: all 0.2s ease;
}

.inline-flex.items-center:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

/* Amélioration des ombres pour la profondeur */
.shadow-md {
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
}

.shadow-lg {
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
}

/* Styles pour les états de survol des lignes de tableau */
tbody tr {
    transition: all 0.2s ease;
}

tbody tr:hover {
    background-color: rgba(59, 130, 246, 0.05) !important;
    /*transform: translateY(-1px);*/
}

/* Animation pour les icônes de tri */
.fa-sort-up, .fa-sort-down {
    animation: sortBounce 0.3s ease;
}

@keyframes sortBounce {
    0%, 20%, 50%, 80%, 100% {
        transform: translateY(0);
    }
    40% {
        transform: translateY(-3px);
    }
    60% {
        transform: translateY(-1px);
    }
}
</style>
