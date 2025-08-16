<template>
    <div>
        <div v-if="$page.props.flash && $page.props.flash.error"
             class="bg-red-100 text-red-700 p-4 text-center font-medium">
            {{ $page.props.flash.error }}
        </div>

        <div class="min-h-screen bg-gray-50">
            <!-- Navigation -->
            <nav class="bg-white shadow-sm">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="flex justify-between h-16">
                        <!-- Logo et navigation principale -->
                        <div class="flex items-center">
                            <!-- Logo avec conteneur carré pour éviter la déformation -->
                            <div class="flex-shrink-0 flex items-center">
                                <div class="h-12 w-12 flex items-center justify-center overflow-hidden">
                                    <img
                                        src="/images/logo.png"
                                        alt="Logo ParentsWood"
                                        class="h-auto w-full object-contain"
                                    >
                                </div>
                                <span class="ml-3 text-2xl font-bold bg-gradient-to-r from-teal-600 to-teal-400 bg-clip-text text-transparent">
                                    PARENTS.WOOD
                                </span>
                            </div>

                            <!-- Liens de navigation -->
                            <div class="hidden md:ml-10 md:flex md:space-x-8">
                                <NavLink
                                    :href="route('dashboard')"
                                    :active="route().current('dashboard')"
                                    class="hover:text-teal-600 transition-colors"
                                >
                                    <i class="fas fa-home mr-2"></i> Tableau de bord
                                </NavLink>

                                <NavLink
                                    v-if="isAdmin()"
                                    :href="route('admin.index')"
                                    :active="route().current('admin.index')"
                                    class="hover:text-teal-600 transition-colors"
                                >
                                    <i class="fas fa-shield-alt mr-2"></i> {{ adminLabel() }}
                                </NavLink>

                                <NavLink
                                    :href="route('events.index')"
                                    :active="route().current('events.index')"
                                    class="hover:text-teal-600 transition-colors"
                                >
                                    <i class="fas fa-calendar-alt mr-2"></i> Événements
                                </NavLink>

                                <NavLink
                                    :href="route('users.show', { user: $page.props.auth.user.id })"
                                    :active="route().current('users.show') && $page.url.includes(`/users/${$page.props.auth.user.id}`)"
                                    class="hover:text-teal-600 transition-colors"
                                >
                                    <i class="fas fa-user mr-2 w-5 text-center"></i> Mon profil
                                </NavLink>
                            </div>
                        </div>

                        <!-- Menu utilisateur -->
                        <div class="hidden md:flex md:items-center md:space-x-4">
                            <Dropdown align="right" width="48">
                                <template #trigger>
                                    <button class="flex items-center space-x-2 text-gray-700 hover:text-teal-600 transition-colors focus:outline-none">
                                        <div class="h-8 w-8 rounded-full bg-teal-100 flex items-center justify-center text-teal-600">
                                            <i class="fas fa-user"></i>
                                        </div>
                                        <span class="font-medium">{{ $page.props.auth.user.pseudo }}</span>
                                        <i class="fas fa-chevron-down text-xs"></i>
                                    </button>
                                </template>

                                <template #content>
                                    <DropdownLink :href="route('profile.edit')" class="flex items-center">
                                        <i class="fas fa-user-edit mr-2 w-5 text-center"></i> Modifier le profil
                                    </DropdownLink>

                                    <DropdownLink
                                        :href="route('logout')"
                                        method="post"
                                        as="button"
                                        class="flex items-center text-red-600 hover:bg-red-50"
                                    >
                                        <i class="fas fa-sign-out-alt mr-2 w-5 text-center"></i> Déconnexion
                                    </DropdownLink>
                                </template>
                            </Dropdown>
                        </div>

                        <!-- Bouton menu mobile -->
                        <div class="flex items-center md:hidden">
                            <button
                                @click="showingNavigationDropdown = !showingNavigationDropdown"
                                class="inline-flex items-center justify-center p-2 rounded-md text-gray-700 hover:text-teal-600 hover:bg-gray-100 focus:outline-none"
                            >
                                <svg
                                    class="h-6 w-6"
                                    :class="{ 'hidden': showingNavigationDropdown, 'block': !showingNavigationDropdown }"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                                </svg>
                                <svg
                                    class="h-6 w-6"
                                    :class="{ 'hidden': !showingNavigationDropdown, 'block': showingNavigationDropdown }"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Menu mobile -->
                <div
                    :class="{'block': showingNavigationDropdown, 'hidden': !showingNavigationDropdown}"
                    class="md:hidden border-t border-gray-200"
                >
                    <div class="pt-2 pb-3 space-y-1">
                        <ResponsiveNavLink
                            :href="route('dashboard')"
                            :active="route().current('dashboard')"
                            class="flex items-center px-4 py-3"
                        >
                            <i class="fas fa-home mr-3 w-5 text-center"></i> Tableau de bord
                        </ResponsiveNavLink>

                        <ResponsiveNavLink
                            v-if="isAdmin()"
                            :href="route('admin.index')"
                            :active="route().current('admin.index')"
                            class="flex items-center px-4 py-3"
                        >
                            <i class="fas fa-shield-alt mr-3 w-5 text-center"></i> {{ adminLabel() }}
                        </ResponsiveNavLink>

                        <ResponsiveNavLink
                            :href="route('events.index')"
                            :active="route().current('events.index')"
                            class="flex items-center px-4 py-3"
                        >
                            <i class="fas fa-calendar-alt mr-3 w-5 text-center"></i> Événements
                        </ResponsiveNavLink>

                        <ResponsiveNavLink
                            :href="route('profile.edit')"
                            :active="route().current('profile.edit')"
                            class="flex items-center px-4 py-3"
                        >
                            <i class="fas fa-user-edit mr-3 w-5 text-center"></i> Mon profil
                        </ResponsiveNavLink>

                        <ResponsiveNavLink
                            :href="route('logout')"
                            method="post"
                            as="button"
                            class="w-full text-left px-4 py-3 text-red-600 hover:bg-red-50"
                        >
                            <i class="fas fa-sign-out-alt mr-3 w-5 text-center"></i> Déconnexion
                        </ResponsiveNavLink>
                    </div>
                </div>
            </nav>

            <!-- En-tête de page -->
            <header class="bg-white shadow" v-if="$slots.header">
                <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                    <slot name="header" />
                </div>
            </header>

            <!-- Contenu principal -->
            <main class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
                <slot />
            </main>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import { usePage, Link } from '@inertiajs/vue3';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import NavLink from '@/Components/NavLink.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';

const showingNavigationDropdown = ref(false);
const page = usePage();

const isAdmin = () => {
    const role = page.props.auth.user?.role;
    return role === 'Admin' || role === 'Super-admin';
};

const adminLabel = () => {
    const role = page.props.auth.user?.role;
    if (role === 'Super-admin') return 'Super-Admin';
    if (role === 'Admin') return 'Admin';
    return '';
};

// Fonction simple pour formater la date
const formatDate = (dateString) => {
    if (!dateString) return '';
    const date = new Date(dateString);
    return date.toLocaleDateString('fr-FR', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
};
</script>

<style scoped>
/* Animation pour le menu déroulant */
.fade-enter-active, .fade-leave-active {
    transition: opacity 0.2s;
}
.fade-enter-from, .fade-leave-to {
    opacity: 0;
}

/* Style personnalisé pour les liens actifs */
.router-link-active {
    @apply text-teal-600 font-medium;
}

/* Style pour les icônes dans les liens */
i.fas {
    width: 1.25rem;
    display: inline-block;
    text-align: center;
}
</style>
