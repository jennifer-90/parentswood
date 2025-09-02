<script setup>
import { ref, computed, watch, reactive } from 'vue'
import { router, usePage, Link, Head, useRemember } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import ConfirmModal from '@/Components/ConfirmModal.vue'

const route = window.route

/** Lance la recherche après +-300 ms sans frappe (évite une requête par lettre). */
function debounce(action, delaiMs = 300) {
    let timeout = null
    return function (...args) {
        clearTimeout(timeout)
        timeout = setTimeout(function () {
            action(...args)
        }, delaiMs)
    }
}

/* -- PROPS: données envoyées par le contrôleur vers Inertia -- */
const props = defineProps({
    users: Object,
    events: Object,
    filters: Object,
})

const inertiaNavOpts = { preserveState: true, replace: true, preserveScroll: true }

/**
 * Aller vers une page Users donnée, en gardant les filtres
 * et en REMETTANT les Events sur la page 1.
 */
const goToUsersPagination = (linkUrl) => {
    const fullUrl = new URL(linkUrl, window.location.origin)
    const nextUsersPage = Number(
        fullUrl.searchParams.get('users_page') ?? fullUrl.searchParams.get('page') ?? '1'
    )

    router.get(
        route('admin.index'),
        {
            search_users: searchUser.value,
            search_events: searchEvent.value,
            users_page: nextUsersPage,
            events_page: 1,
        },
        inertiaNavOpts
    )
}

/**
 * Aller vers une page Events donnée, en gardant les filtres
 * et en GARDANT la page Users actuelle (pas de reset).
 */
const goToEventsPagination = (linkUrl) => {
    const fullUrl = new URL(linkUrl, window.location.origin)
    const nextEventsPage = Number(
        fullUrl.searchParams.get('events_page') ?? fullUrl.searchParams.get('page') ?? '1'
    )

    router.get(
        route('admin.index'),
        {
            search_users: searchUser.value,
            search_events: searchEvent.value,
            users_page: props.users?.current_page ?? 1,
            events_page: nextEventsPage,
        },
        inertiaNavOpts
    )
}

const getUserGlobalIndex = (i) => (props.users?.from ?? 0) + i
const getEventGlobalIndex = (i) => (props.events?.from ?? 0) + i

/* ########## INFOS DU USER CONNECTE ##########################################################*/
const page = usePage()
const nowConnectUser = page.props.auth.user
const nowConnectUserId = nowConnectUser.id

/* ########## TITRE PAGE ADMIN ##################################################*/
const roles = computed(() => page.props.auth.user?.roles || [])
const adminTitle = computed(() => {
    if (roles.value.includes('Super-admin')) return 'Gestion Super Admin'
    if (roles.value.includes('Admin')) return 'Gestion Admin'
    return 'Espace d’administration'
})

/* ########## ROLES #############################################################*/
const isSuperAdmin = () => roles.value.includes('Super-admin')
const isAdmin = () => roles.value.includes('Admin') || roles.value.includes('Super-admin')

const updateRole = async (user, event) => {
    if (!isSuperAdmin()) return
    if (user.cannot_edit_role) return

    const newRole = event.target.value
    const ok = await askConfirm({
        title: 'Attribuer un rôle',
        message: `Attribuer le rôle <strong>${escapeHtml(newRole)}</strong> à <strong>${escapeHtml(
            user.pseudo
        )}</strong> ?`,
        confirmLabel: 'Oui, attribuer',
    })
    if (!ok) return

    router.post(route('users.updateRole', { user: user.id }), { role: newRole }, { preserveScroll: true })
}

/* ########## BARRE DE RECHERCHE #############################################################*/
const escapeHtml = (s) =>
    String(s ?? '').replace(/[&<>"]/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]))

const highlight = (text, search) => {
    const safe = escapeHtml(text)
    if (!search) return safe
    const esc = search.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')
    return safe.replace(new RegExp(`(${esc})`, 'gi'), '<mark class="bg-yellow-200 px-1 rounded">$1</mark>')
}

const searchUser = useRemember(props.filters?.search_users ?? '', 'admin.users.search')
const searchEvent = useRemember(props.filters?.search_events ?? '', 'admin.events.search')

watch(
    searchUser,
    debounce((v) => {
        router.get(
            route('admin.index'),
            {
                search_users: v,
                search_events: searchEvent.value,
                users_page: 1,
                events_page: props.events?.current_page ?? 1,
            },
            inertiaNavOpts
        )
    }, 300)
)

watch(
    searchEvent,
    debounce((v) => {
        router.get(
            route('admin.index'),
            {
                search_users: searchUser.value,
                search_events: v,
                users_page: props.users?.current_page ?? 1,
                events_page: 1,
            },
            inertiaNavOpts
        )
    }, 300)
)

/* ########## TRIER/FILTRER USERS ############################################################*/
const sortField = ref('created_at')
const sortAsc = ref(true)

const sortBy = (field) => {
    if (sortField.value === field) sortAsc.value = !sortAsc.value
    else {
        sortField.value = field
        sortAsc.value = true
    }
}

const sortedUsers = computed(() => {
    if (!Array.isArray(props.users?.data)) return []
    const fieldName = sortField.value
    const isAscending = sortAsc.value

    const toBool = (x) => !!Number(x)

    const nonAnonymous = props.users.data.filter((u) => !toBool(u.anonyme))
    const anonymous = props.users.data.filter((u) => toBool(u.anonyme))

    const normalize = (v) => {
        if (v == null) return ''
        return typeof v === 'string' ? v.toLowerCase() : v
    }

    const compareByField = (a, b) => {
        const aValue = normalize(a[fieldName])
        const bValue = normalize(b[fieldName])
        if (aValue < bValue) return isAscending ? -1 : 1
        if (aValue > bValue) return isAscending ? 1 : -1
        return 0
    }

    nonAnonymous.sort(compareByField)
    anonymous.sort(compareByField)
    return [...nonAnonymous, ...anonymous]
})

const filteredUsers = computed(() => {
    const query = (searchUser.value || '').toLowerCase()
    return sortedUsers.value.filter((user) => {
        const pseudo = (user.pseudo || '').toLowerCase()
        const first = (user.first_name || '').toLowerCase()
        const last = (user.last_name || '').toLowerCase()
        const email = (user.email || '').toLowerCase()
        return pseudo.includes(query) || first.includes(query) || last.includes(query) || email.includes(query)
    })
})

/* ########## TRIER/FILTRER EVENTS ###########################################################*/
const prioritizedEvents = computed(() => {
    if (!Array.isArray(props.events?.data)) return []
    const searchTerm = (searchEvent.value || '').trim().toLowerCase()
    const allEvents = Array.isArray(props.events?.data) ? props.events.data : []

    const reportedEvents = []
    const regularEvents = []

    for (const event of allEvents) {
        const nameLower = (event.name_event || '').toLowerCase()
        const locationLower = (event.location || '').toLowerCase()
        const matchesSearch = !searchTerm || nameLower.includes(searchTerm) || locationLower.includes(searchTerm)
        if (!matchesSearch) continue
        const hasReports = Number(event.reports_count || 0) > 0
        ;(hasReports ? reportedEvents : regularEvents).push(event)
    }
    return reportedEvents.concat(regularEvents)
})

/* ########## ACTIONS USERS ###########################################################*/
const toggleActivation = async (user) => {
    if (user.anonyme || user.roles.some((r) => r.name === 'Super-admin')) return

    const ok = await askConfirm({
        title: user.is_actif ? 'Désactiver l’utilisateur' : 'Activer l’utilisateur',
        message: `Voulez-vous ${user.is_actif ? '<strong>désactiver</strong>' : '<strong>activer</strong>'} <strong>${escapeHtml(
            user.pseudo
        )}</strong> ?`,
        confirmLabel: user.is_actif ? 'Désactiver' : 'Activer',
    })
    if (!ok) return

    router.post(route('users.toggleActivation', { user: user.id }), {}, { preserveScroll: true })
}

const anonymizeUser = async (user) => {
    if (user.is_super_admin || user.anonyme) return

    const ok = await askConfirm({
        title: 'Anonymiser l’utilisateur',
        message: `Voulez-vous vraiment anonymiser <strong>${escapeHtml(user.pseudo)}</strong> ?`,
        confirmLabel: 'Anonymiser',
    })
    if (!ok) return

    router.post(route('users.anonymize', { user: user.id }), {}, { preserveScroll: true })
}

/* ########## ACTIONS EVENTS ###########################################################*/
const toggleEventActivation = async (event) => {
    const ok = await askConfirm({
        title: event.inactif ? 'Activer l’événement' : 'Désactiver l’événement',
        message: `Voulez-vous ${event.inactif ? '<strong>activer</strong>' : '<strong>désactiver</strong>'} l’événement « <strong>${escapeHtml(
            event.name_event
        )}</strong> » ?`,
        confirmLabel: event.inactif ? 'Activer' : 'Désactiver',
    })
    if (!ok) return

    router.post(route('events.toggleActive', { event: event.id }), {}, {
        preserveScroll: true,
        onSuccess: () => {
            event.inactif = !event.inactif
        },
        onError: async () => {
            await showAlert({ title: 'Erreur', message: 'Une erreur est survenue lors de la mise à jour du statut.' })
        },
    })
}

const acceptEvent = async (event) => {
    const ok = await askConfirm({
        title: 'Accepter l’événement',
        message: `Accepter l’événement « <strong>${escapeHtml(event.name_event)}</strong> » ?`,
        confirmLabel: 'Accepter',
    })
    if (!ok) return

    router.post(route('events.accept', { event: event.id }), {}, {
        preserveScroll: true,
        onError: async () => {
            await showAlert({ title: 'Erreur', message: 'Erreur lors de l’acceptation.' })
        },
    })
}


const refuseEvent = async (event) => {
    const ok = await askConfirm({
        title: 'Refuser l’événement',
        message: `Refuser l’événement « <strong>${escapeHtml(event.name_event)}</strong> » ?`,
        confirmLabel: 'Refuser',
    })
    if (!ok) return

    router.post(route('events.refuse', { event: event.id }), {}, {
        preserveScroll: true,
        onError: async () => {
            await showAlert({ title: 'Erreur', message: 'Erreur lors du refus.' })
        },
    })
}


/* ########## RESET ALERTS ###########################################################*/
const clearEventReports = async (event) => {
    const ok = await askConfirm({
        title: 'Remettre à zéro',
        message: `Remettre à zéro les signalements pour « <strong>${escapeHtml(event.name_event)}</strong> » ?`,
        confirmLabel: 'Oui, remettre à zéro',
    })
    if (!ok) return

    router.post(route('events.reports.clear', { event: event.id }), {}, {
        preserveScroll: true,
        onSuccess: () => {
            event.reports_count = 0
        },
        onError: async () => {
            await showAlert({ title: 'Erreur', message: 'Impossible de remettre à zéro les signalements.' })
        },
    })
}

/* ========== Modale de confirmation / alerte ========== */
const modal = reactive({
    open: false,
    title: '',
    message: '',
    confirmLabel: 'Confirmer',
    cancelLabel: 'Annuler',
    showCancel: true,
    disabled: false,
})

let modalResolve = null

function askConfirm({
                        title = 'Confirmation',
                        message = '',
                        confirmLabel = 'Confirmer',
                        cancelLabel = 'Annuler',
                        disabled = false,
                    } = {}) {
    return new Promise((resolve) => {
        modal.title = title
        modal.message = message
        modal.confirmLabel = confirmLabel
        modal.cancelLabel = cancelLabel
        modal.showCancel = true
        modal.disabled = disabled
        modal.open = true
        modalResolve = resolve
    })
}

function showAlert({ title = 'Information', message = '', okLabel = 'OK' } = {}) {
    return new Promise((resolve) => {
        modal.title = title
        modal.message = message
        modal.confirmLabel = okLabel
        modal.cancelLabel = ''
        modal.showCancel = false
        modal.disabled = false
        modal.open = true
        modalResolve = resolve
    })
}

function onModalConfirm() {
    modal.open = false
    if (modalResolve) {
        const r = modalResolve
        modalResolve = null
        r(true)
    }
}
function onModalCancel() {
    modal.open = false
    if (modalResolve) {
        const r = modalResolve
        modalResolve = null
        r(false)
    }
}
</script>

<template>
    <Head :title="adminTitle" />

    <AuthenticatedLayout>
        <div class="py-4 bg-[#f9f5f2] min-h-screen">
            <div class="mx-auto w-full px-3 sm:px-5">
                <div class="bg-white shadow-lg rounded-lg p-4 sm:p-6">
                    <!--########## Titre principal ##########-->
                    <div class="bg-transparent border-2 border-[#ffb347] rounded-lg p-4 mb-6" style="background-color: rgba(255, 179, 71, 0.05);">
                        <div class="flex items-center justify-center gap-3">
                            <div class="bg-[#ffb347] text-white rounded-full p-3">
                                <i class="fa-solid fa-user-shield text-xl"></i>
                            </div>
                            <h1 class="text-2xl sm:text-3xl font-bold text-gray-800">{{ adminTitle }}</h1>
                        </div>
                    </div>

                    <!--########## Bandeau d’accueil ##########-->
                    <div class="mb-6 rounded-lg border border-[#59c4b4]/30 bg-[#59c4b4]/10 p-4" role="region" aria-labelledby="admin-help-title">
                        <div class="flex items-start gap-3">
                            <i class="fa-solid fa-circle-info mt-1 text-[#59c4b4]" aria-hidden="true"></i>
                            <div>
                                <p id="admin-help-title" class="font-semibold text-gray-800">Bienvenue dans l’espace d’administration</p>

                                <ul class="mt-2 text-sm text-gray-700 list-disc pl-5 space-y-1 leading-relaxed">
                                    <li>Utilisez les barres de recherche pour filtrer.</li>
                                    <li>Cliquez sur un <strong>Pseudo</strong> pour ouvrir le profil.</li>
                                    <li>Cliquez sur les <strong>en-têtes</strong> <strong>Pseudo / Nom / Email</strong> pour trier.</li>
                                    <li v-if="isSuperAdmin()">
                                        Les boutons permettent d’activer/désactiver, anonymiser et <strong>changer un rôle</strong>.
                                    </li>
                                    <li v-else>
                                        Les boutons permettent d’activer/désactiver. <strong>Seul un Super-admin peut changer les rôles.</strong>
                                    </li>

                                    <template v-if="isSuperAdmin()">
                                        <li>Générez 10 utilisateurs de test autant de fois que nécessaire (réservé <strong>Super-admin</strong>).</li>
                                        <li>Seul un <strong>Super-admin</strong> peut attribuer le rôle « Super-admin ».</li>
                                        <li>Pour <strong>anonymiser</strong> ou <strong>désactiver</strong> un <strong>Admin</strong> ou un <strong>Super-admin</strong>, mettez d’abord son rôle sur « User ».</li>
                                        <li>Personne ne peut <strong>s’auto-désactiver</strong> ni <strong>s’auto-anonymiser</strong>.</li>
                                    </template>
                                    <template v-else>
                                        <li>Personne ne peut <strong>s’auto-désactiver</strong>.</li>
                                    </template>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- ================================================================== -->
                    <div class="flex flex-col gap-4 sm:gap-6">
                        <!--########## SECTION USERS ##########-->
                        <div class="xl:flex-1 bg-[#59c4b4]/10 p-4 rounded-lg shadow-md" style="min-height: 600px;">
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

                            <!-- Export users -->
                            <div class="mb-4">
                                <a
                                    :href="route('admin.export.users', { search_users: searchUser })"
                                    class="inline-flex items-center gap-2 bg-gradient-to-r from-[#ffb347] to-[#ff9500] hover:from-[#ff9500] hover:to-[#e6850e] text-white px-4 py-2 rounded-lg font-semibold transition-all duration-300 transform hover:scale-105 shadow-md hover:shadow-lg"
                                >
                                    <i class="fa-solid fa-download"></i>
                                    Exporter les utilisateurs (.csv)
                                </a>
                            </div>

                            <!-- Tableau users -->
                            <div class="overflow-x-auto bg-white rounded-lg shadow-sm border border-gray-200">
                                <table class="min-w-full ">
                                    <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider cursor-pointer hover:bg-gray-100 transition-colors" @click="sortBy('pseudo')">
                                            Pseudo
                                            <i v-if="sortField === 'pseudo'" :class="sortAsc ? 'fa fa-sort-up' : 'fa fa-sort-down'" class="ml-1"></i>
                                        </th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider cursor-pointer hover:bg-gray-100 transition-colors" @click="sortBy('first_name')">
                                            Nom
                                            <i v-if="sortField === 'first_name'" :class="sortAsc ? 'fa fa-sort-up' : 'fa fa-sort-down'" class="ml-1"></i>
                                        </th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider cursor-pointer hover:bg-gray-100 transition-colors" @click="sortBy('email')">
                                            Email
                                            <i v-if="sortField === 'email'" :class="sortAsc ? 'fa fa-sort-up' : 'fa fa-sort-down'" class="ml-1"></i>
                                        </th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Statut</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Rôle</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Actions</th>
                                    </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                    <tr
                                        v-for="(user, index) in filteredUsers"
                                        :key="user.id"
                                        :class="index % 2 === 0 ? 'bg-white' : 'bg-gray-50'"
                                        class="hover:bg-blue-50 transition-colors"
                                    >
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <i class="fa-solid fa-user text-[#59c4b4]" aria-hidden="true"></i>&nbsp;
                                            <Link
                                                :href="route('users.show', { user: user.pseudo })"
                                                class="font-medium text-blue-600 hover:underline focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                                            >
                                                <span class="inline-block truncate max-w-[8ch]" :title="user.pseudo" v-html="highlight(user.pseudo || '', searchUser)"></span>
                                            </Link>
                                        </td>

                                        <td class="px-4 py-3 whitespace-nowrap">
                        <span
                            class="inline-block truncate max-w-[14ch]"
                            :title="((user.first_name || '') + ' ' + (user.last_name || '')).trim()"
                            v-html="highlight((((user.first_name || '') + ' ' + (user.last_name || '')).trim()), searchUser)"
                        ></span>
                                        </td>

                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <span class="inline-block truncate max-w-[17ch]" :title="user.email" v-html="highlight(user.email || '', searchUser)"></span>
                                        </td>

                                        <td class="px-4 py-3 whitespace-nowrap">
                        <span v-if="!user.is_actif && user.self_deactivated" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800">
                          <span class="w-2 h-2 bg-amber-400 rounded-full mr-1.5"></span>
                          Auto-désactivation
                        </span>

                                            <span v-else-if="user.is_actif" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                          <span class="w-2 h-2 bg-green-400 rounded-full mr-1.5"></span>
                          Actif
                        </span>

                                            <span v-else class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                          <span class="w-2 h-2 bg-gray-400 rounded-full mr-1.5"></span>
                          Inactif
                        </span>
                                        </td>

                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <template v-if="isSuperAdmin()">
                                                <select
                                                    :value="user.role_name"
                                                    @change="updateRole(user, $event)"
                                                    :disabled="user.cannot_edit_role"
                                                    class="text-sm rounded-full px-3 py-1 font-medium border-0 focus:ring-2 focus:ring-[#59c4b4]"
                                                    :class="{
                              'bg-red-100 text-red-800': user.is_super_admin,
                              'bg-blue-100 text-blue-800': !user.is_super_admin && user.role_name === 'Admin',
                              'bg-green-100 text-green-800': user.role_name === 'User'
                            }"
                                                >
                                                    <option value="User">User</option>
                                                    <option value="Admin">Admin</option>
                                                    <option value="Super-admin">Super-admin</option>
                                                </select>
                                            </template>
                                            <template v-else>
                          <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-50 text-blue-700">
                            {{ user.role_name }}
                          </span>
                                            </template>
                                        </td>

                                        <td class="px-4 py-3 whitespace-nowrap space-x-2">
                                            <button
                                                @click="toggleActivation(user)"
                                                :disabled="
                            user.id === nowConnectUserId ||
                            user.anonyme ||
                            user.roles.some(r => r.name === 'Super-admin') ||
                            user.roles.some(r => r.name === 'Admin')
                          "
                                                class="inline-flex items-center px-3 py-1.5 rounded-lg text-sm font-medium transition-all duration-300 transform hover:scale-105 shadow-sm hover:shadow-md disabled:opacity-50 disabled:cursor-not-allowed disabled:transform-none"
                                                :class="user.is_actif
                              ? 'bg-gradient-to-r from-red-500 to-red-600 hover:from-red-600 hover:to-red-700 text-white'
                              : 'bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white'"
                                            >
                                                <i :class="user.is_actif ? 'fa-solid fa-user-slash' : 'fa-solid fa-user-check'" class="mr-1"></i>
                                                {{ user.is_actif ? 'Désactiver' : 'Activer' }}
                                            </button>

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

                            <!-- Pagination users -->
                            <div class="flex justify-center items-center mt-4 space-x-2">
                                <template v-for="(link, index) in props.users.links" :key="index">
                                    <span v-if="link.url === null" class="text-gray-400 px-3 py-2 rounded-lg" v-html="link.label" />
                                    <button
                                        v-else
                                        @click="goToUsersPagination(link.url)"
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

                        <!--########## SECTION EVENTS ##########-->
                        <hr />
                        <div class="mb-4 rounded-lg border border-[#59c4b4]/30 bg-[#59c4b4]/10 p-4">
                            <div class="flex items-start gap-3">
                                <i class="fa-solid fa-circle-info mt-1 text-[#59c4b4]" aria-hidden="true"></i>
                                <div>
                                    <p class="font-semibold text-gray-800">Aide – Gestion des Événements</p>
                                    <ul class="mt-2 text-sm text-gray-700 list-disc pl-5 space-y-1 leading-relaxed">
                                        <li>Cliquez sur le <strong>nom</strong> d’un événement pour ouvrir sa page (vue détail).</li>
                                        <li>La <strong>cloche</strong> indique le nombre de signalements ; elle devient <strong class="text-red-600">rouge</strong> si &gt; 0.</li>
                                        <li>Le bouton <em>Reset</em> remet le compteur de signalements à 0 <template v-if="isSuperAdmin()"> (Super-admin & Admin)</template><template v-else>(Admin)</template>.</li>
                                        <li>Le badge <strong>Actif / Inactif</strong> reflète la visibilité. Le bouton <em>Activer/Désactiver</em> bascule l’état.</li>
                                        <li><strong>Validation</strong> : <em>Accepter</em> publie l’événement (confirmé = true, inactif = false) ; <em>Refuser</em> le rend inactif.</li>
                                        <li><strong>Annulation</strong> : le créateur, un Admin ou un Super-admin peut annuler (l’événement passe Inactif, avec note/date).</li>
                                        <li>Les événements <strong>inactifs</strong> ne sont visibles que par l’Admin/Super-admin et le créateur.</li>
                                        <li>Le bouton “Exporter les événements (.csv)” exporte selon la recherche en cours.</li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <div class="xl:flex-1 bg-[#59c4b4]/10 p-4 rounded-lg shadow-md" style="min-height: 600px;">
                            <div class="bg-[#59c4b4] text-white rounded-lg p-3 mb-4 flex items-center gap-3">
                                <i class="fa-solid fa-calendar-days text-lg"></i>
                                <h2 class="text-lg font-semibold">Gestion des Événements</h2>
                            </div>

                            <!-- Barre recherche events -->
                            <div class="mb-4">
                                <input
                                    v-model="searchEvent"
                                    type="text"
                                    placeholder="Rechercher un événement..."
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#59c4b4] focus:border-transparent transition-all"
                                />
                            </div>

                            <!-- Export events -->
                            <div class="mb-4">
                                <a
                                    :href="route('admin.export.events', { search_events: searchEvent })"
                                    class="inline-flex items-center gap-2 bg-gradient-to-r from-[#ffb347] to-[#ff9500] hover:from-[#ff9500] hover:to-[#e6850e] text-white px-4 py-2 rounded-lg font-semibold transition-all duration-300 transform hover:scale-105 shadow-md hover:shadow-lg"
                                >
                                    <i class="fa-solid fa-download"></i>
                                    Exporter les événements (.csv)
                                </a>
                            </div>

                            <!-- Tableau events -->
                            <div class="overflow-x-auto bg-white rounded-lg shadow-sm border border-gray-200">
                                <table class="min-w-full table-fixed">
                                    <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">#</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Alert</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Nom</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Lieu</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Date</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Créateur</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Statut</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Confirmation</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Actions</th>
                                    </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                    <tr
                                        v-for="(event, index) in prioritizedEvents"
                                        :key="event.id"
                                        :class="index % 2 === 0 ? 'bg-white' : 'bg-gray-50'"
                                        class="hover:bg-blue-50 transition-colors"
                                    >
                                        <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900">
                                            {{ getEventGlobalIndex(index) }}
                                        </td>

                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <i class="fa-solid fa-bell" :class="event.reports_count > 0 ? 'text-red-600' : 'text-gray-400'"></i>
                                                <span v-if="event.reports_count" class="ml-1 text-xs font-semibold">
                            {{ event.reports_count }}
                          </span>

                                                <button
                                                    v-if="event.reports_count > 0"
                                                    @click="clearEventReports(event)"
                                                    class="ml-2 inline-flex items-center px-2 py-1 text-xs rounded border border-gray-300 hover:bg-gray-50"
                                                    title="Remettre à zéro les signalements"
                                                >
                                                    <i class="fa-solid fa-rotate-left mr-1"></i>
                                                    Reset
                                                </button>
                                            </div>
                                        </td>

                                        <td class="px-4 py-3">
                                            <Link :href="route('events.show', event.id)" class="font-medium text-blue-600 hover:underline">
                          <span
                              class="inline-block truncate max-w-[16ch] sm:max-w-[16ch] lg:max-w-[16ch]"
                              :title="event.name_event || ''"
                              :aria-label="event.name_event || ''"
                              v-html="highlight(event.name_event || '', searchEvent)"
                          ></span>
                                            </Link>
                                        </td>

                                        <td class="px-4 py-3 ">
                                            <i class="fa-solid fa-location-dot text-[#59c4b4] mr-2"></i>
                                            <span v-html="highlight(event.location, searchEvent)" class="text-gray-700"></span>
                                        </td>

                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700">
                                            <div class="flex items-center gap-2" :title="new Date(event.date).toLocaleString('fr-BE')">
                                                <i class="fa-solid fa-calendar text-[#59c4b4]" aria-hidden="true"></i>
                                                <span>{{ new Date(event.date).toLocaleDateString('fr-BE') }}</span>
                                            </div>
                                        </td>

                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700">
                                            <div class="flex items-center gap-1.5">
                                                <i class="fa-solid fa-user text-[#59c4b4]" aria-hidden="true"></i>
                                                <Link v-if="event.creator" :href="route('users.show', { user: event.creator.pseudo })" class="hover:underline">
                                                    {{ event.creator.pseudo }}
                                                </Link>
                                            </div>

                                            <span
                                                v-if="event.cancelled_at"
                                                class="mt-1 inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold bg-red-50 text-red-700"
                                                :title="`le ${new Date(event.cancelled_at).toLocaleDateString('fr-BE')} à ${new Date(event.cancelled_at).toLocaleTimeString('fr-BE',{hour:'2-digit',minute:'2-digit'})}`"
                                            >
                          a annulé
                        </span>
                                        </td>

                                        <td class="px-4 py-3 text-sm align-top">
                                            <div class="flex flex-col gap-1">
                          <span v-if="!event.inactif" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                            <span class="w-2 h-2 bg-green-400 rounded-full mr-1.5"></span>
                            Actif
                          </span>
                                                <span v-else class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                            <span class="w-2 h-2 bg-gray-400 rounded-full mr-1.5"></span>
                            Inactif
                          </span>

                                                <span
                                                    v-if="event.cancelled_at"
                                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-50 text-red-700 border border-red-200"
                                                    :title="`Annulé le ${new Date(event.cancelled_at).toLocaleDateString('fr-BE')} à ${new Date(event.cancelled_at).toLocaleTimeString('fr-BE',{hour:'2-digit', minute:'2-digit'})}`"
                                                >
                            <span class="w-2 h-2 bg-red-500 rounded-full mr-1.5"></span>
                            Annulé
                          </span>
                                            </div>
                                        </td>

                                        <td class="px-4 py-3 text-sm text-gray-700">
                                            <div v-if="event.confirmed === true">
                          <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                            <i class="fa fa-check mr-1"></i> Accepté
                          </span>

                                                <div v-if="event.validated_by" class="mt-1 text-xs text-gray-500 leading-snug break-words">
                                                    <div>par {{ event.validated_by.pseudo }}</div>
                                                    <div>{{ new Date(event.validated_at).toLocaleDateString('fr-BE') }}</div>
                                                </div>
                                            </div>

                                            <div v-else-if="event.confirmed === false">
                          <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                            <i class="fa fa-close mr-1"></i> Refusé
                          </span>
                                                <div v-if="event.validated_by" class="mt-1 text-xs text-gray-500 leading-snug break-words">
                                                    <div>par {{ event.validated_by.pseudo }}</div>
                                                    <div>{{ new Date(event.validated_at).toLocaleDateString('fr-BE') }}</div>
                                                </div>
                                            </div>

                                            <div v-else>
                          <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                            <i class="fa fa-clock mr-1"></i> En attente
                          </span>
                                            </div>
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

                            <!-- Pagination events -->
                            <div class="flex justify-center items-center mt-4 space-x-2">
                                <template v-for="(link, index) in props.events.links" :key="index">
                                    <span v-if="link.url === null" class="text-gray-400 px-3 py-2 rounded-lg" v-html="link.label" />
                                    <button
                                        v-else
                                        @click="goToEventsPagination(link.url)"
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

        <!-- ===== Modale globale ===== -->
        <ConfirmModal
            v-model:open="modal.open"
            :title="modal.title"
            :confirmLabel="modal.confirmLabel"
            :cancelLabel="modal.cancelLabel"
            :showCancel="modal.showCancel"
            :disabled="modal.disabled"
            @confirm="onModalConfirm"
            @cancel="onModalCancel"
        >
            <div class="text-sm text-gray-700" v-html="modal.message"></div>
        </ConfirmModal>
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

/* Styles pour les badges de statut */
.badge { display: inline-flex; align-items: center; padding: 0.25rem 0.75rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 500; }

/* Amélioration des tables */
table { border-collapse: separate; border-spacing: 0; }
th { position: sticky; top: 0; z-index: 10; }

/* Scrollbar personnalisée */
.overflow-x-auto::-webkit-scrollbar { height: 6px; }
.overflow-x-auto::-webkit-scrollbar-track { background: #f1f5f9; border-radius: 3px; }
.overflow-x-auto::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 3px; }
.overflow-x-auto::-webkit-scrollbar-thumb:hover { background: #94a3b8; }

/* Animation gradients */
.bg-gradient-to-r { background-size: 200% 200%; }

/* Icônes */
.fa-solid, .fa { transition: transform 0.2s ease; }
button:hover .fa-solid, button:hover .fa { transform: scale(1.1); }

/* Responsive */
@media (max-width: 640px) {
    .xl\:flex-row { flex-direction: column; }
    table { font-size: 0.875rem; }
    .px-4 { padding-left: 0.75rem; padding-right: 0.75rem; }
    .py-3 { padding-top: 0.5rem; padding-bottom: 0.5rem; }
}

/* Focus states */
input:focus, select:focus { outline: none; box-shadow: 0 0 0 3px rgba(89, 196, 180, 0.1); }

/* Disabled */
button:disabled { cursor: not-allowed; opacity: 0.5; transform: none !important; }
button:disabled:hover { transform: none !important; box-shadow: none !important; }

/* Ombres */
.shadow-md { box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1), 0 2px 4px -1px rgba(0,0,0,0.06); }
.shadow-lg { box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1), 0 4px 6px -2px rgba(0,0,0,0.05); }

/* Hover lignes */
tbody tr { transition: all 0.2s ease; }
tbody tr:hover { background-color: rgba(59, 130, 246, 0.05) !important; }

/* Animation tri */
.fa-sort-up, .fa-sort-down { animation: sortBounce 0.3s ease; }
@keyframes sortBounce {
    0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
    40% { transform: translateY(-3px); }
    60% { transform: translateY(-1px); }
}
</style>
