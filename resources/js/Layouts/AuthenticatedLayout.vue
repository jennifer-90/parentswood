<script setup>
import {ref} from 'vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { usePage } from '@inertiajs/vue3';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import NavLink from '@/Components/NavLink.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';
import {Link} from '@inertiajs/vue3';

// Ref pour afficher ou cacher le menu responsive
const showingNavigationDropdown = ref(false);

// Fonction utilitaire pour vérifier si une route est active
const isActive = (route) => window.location.pathname === route;

const page = usePage();


// Fonction pour savoir si l'utilisateur est admin ou super-admin
const isAdmin = () => {
    const role = page.props.auth.user?.role;
    return role === 'Admin' || role === 'Super-admin';
};

// Fonction pour récupérer le bon label
const adminLabel = () => {
    const role = page.props.auth.user?.role;
    if (role === 'Super-admin') return 'Super-Admin';
    if (role === 'Admin') return 'Admin';
    return '';
};






/* TEST VOIR QUEL ROLE IL RENVOIT : console.log('Rôle de l’utilisateur :', page.props.auth.user?.role); */

</script>




<template>
    <div>
        <div>
            <!-- Vérifiez si la clé 'flash' existe et affichez les messages d'erreur -->
            <div v-if="$page.props.flash && $page.props.flash.error" class="bg-red-100 text-red-700 p-4 rounded mb-4">
                {{ $page.props.flash.error }}
            </div>
        </div>

        <div class="min-h-screen bg-gray-100">
            <!-- Barre de navigation principale -->
            <nav class="border-b border-gray-100 bg-white">

                <!-- Conteneur de navigation -->
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="flex h-16 justify-between">

                        <!-- Section gauche : Logo et liens de navigation -->
                        <div class="flex">
                            <!-- Logo -->
                            <div class="flex items-center space-x-4">
                                <img src="/storage/parentswood_logo.png" alt="ParentsWood Logo" class="h-16 w-16">
                                <p class="text-2xl font-extrabold text-gray-900">PARENTS.WOOD</p>
                            </div>

                            <!-- Liens de navigation (affichés uniquement sur grand écran) -->
                            <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">

                                <NavLink v-if="route('dashboard')"
                                         :href="route('dashboard')"
                                         :active="route().current('dashboard')">
                                    Dashboard
                                </NavLink>

                                <NavLink v-if="isAdmin()"
                                         :href="route('users.index')"
                                         :active="route().current('users.index') ? 'btn-disabled' : 'btn'">
                                    {{ adminLabel() }}
                                </NavLink>



                                <NavLink :href="route('profile.edit')"
                                         :active="route().current('profile.edit')">
                                    Mon profil
                                </NavLink>

                                <NavLink :href="route('events.index')" :active="route().current('events.index')">
                                    Évènements
                                </NavLink>

                                <NavLink><i class="fa-solid fa-comments"></i></NavLink>
                            </div>
                        </div>

                        <!-- Section droite : Dropdown pour les paramètres -->
                        <div class="hidden sm:ms-6 sm:flex sm:items-center">
                            <div class="relative ms-3">
                                <Dropdown align="right" width="48">
                                    <!-- Déclencheur du dropdown -->
                                    <template #trigger>
                                        <span class="inline-flex rounded-md">
                                            <button type="button"
                                                    class="inline-flex items-center rounded-md border border-transparent bg-white px-3 py-2 text-sm font-medium leading-4 text-gray-500 transition duration-150 ease-in-out hover:text-gray-700 focus:outline-none">
                                                <!-- Affichage du pseudo de l'utilisateur -->
                                                <p class="pseudo"><i class="fa-solid fa-user"></i>  &ensp; {{ $page.props.auth.user.pseudo }}</p>

                                                <!-- Petite flèche pour le dropdown -->
                                                <svg class="-me-0.5 ms-2 h-4 w-4" xmlns="http://www.w3.org/2000/svg"
                                                     viewBox="0 0 20 20" fill="currentColor">
                                                    <path fill-rule="evenodd"
                                                          d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                                          clip-rule="evenodd"/>
                                                </svg>
                                            </button>
                                        </span>
                                    </template>

                                    <!-- Contenu du dropdown -->
                                    <template #content>
                                        <DropdownLink :href="route('logout')" method="post" as="button">
                                            Log Out <i class="fa-solid fa-right-from-bracket"></i>
                                        </DropdownLink>
                                    </template>
                                </Dropdown>
                            </div>
                        </div>

                        <!-- Bouton hamburger pour les écrans petits -->
                        <button
                            @click="showingNavigationDropdown = !showingNavigationDropdown"
                            class="inline-flex items-center justify-center rounded-md p-2 text-gray-400 transition duration-150 ease-in-out hover:bg-gray-100 hover:text-gray-500 focus:bg-gray-100 focus:text-gray-500 focus:outline-none sm:hidden">
                            <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                                <!-- Icône des trois barres -->
                                <path
                                    :class="{ hidden: showingNavigationDropdown, 'inline-flex': !showingNavigationDropdown }"
                                    stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 6h16M4 12h16M4 18h16"/>
                                <!-- Icône de la croix -->
                                <path
                                    :class="{ hidden: !showingNavigationDropdown, 'inline-flex': showingNavigationDropdown }"
                                    stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Menu de navigation responsive (pour petits écrans) -->
                <div :class="{ block: showingNavigationDropdown, hidden: !showingNavigationDropdown }"
                     class="sm:hidden">
                    <div class="space-y-1 pb-3 pt-2">
                        <ResponsiveNavLink :href="route('dashboard')" :active="route().current('dashboard')">
                            Dashboard
                        </ResponsiveNavLink>
                    </div>

                    <!-- Options de paramètres pour les petits écrans -->
                    <div class="border-t border-gray-200 pb-1 pt-4">
                        <div class="px-4">
                            <!-- Pseudo et email de l'utilisateur -->
                            <div class="text-base font-medium text-gray-800">
                                {{ $page.props.auth.user.pseudo }}
                            </div>
                            <div class="text-sm font-medium text-gray-500">
                                {{ $page.props.auth.user.email }}
                            </div>
                        </div>

                        <div class="mt-3 space-y-1">
                            <ResponsiveNavLink :href="route('profile.edit')">
                                Profile
                            </ResponsiveNavLink>
                            <ResponsiveNavLink :href="route('logout')" method="post" as="button">
                                Log Out
                            </ResponsiveNavLink>
                        </div>
                    </div>
                </div>
            </nav>

            <!-- Header (optionnel) -->
            <header class="bg-white shadow" v-if="$slots.header">
                <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                    <slot name="header"/>
                </div>
            </header>

            <!-- Contenu principal -->
            <main>
                <slot/>
            </main>
        </div>
    </div>
</template>

<style>
.pseudo{
    color:#1abc9c;
    font-size: 16px;
}
</style>
