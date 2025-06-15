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
</script>

<template>
    <div>
        <div v-if="$page.props.flash && $page.props.flash.error" class="bg-red-100 text-red-700 p-4 rounded mb-4">
            {{ $page.props.flash.error }}
        </div>

        <div class="min-h-screen bg-gray-100">
            <!-- Navbar -->
            <nav class="border-b border-gray-100 bg-white">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="flex h-16 justify-between">

                        <!-- Left -->
                        <div class="flex">
                            <div class="flex items-center space-x-4">
                                <img src="/images/logo.png" alt="Logo ParentsWood" class="h-16 w-16">
                                <p class="text-2xl font-extrabold text-gray-900">PARENTS.WOOD</p>
                            </div>

                            <div class="hidden sm:ms-10 sm:flex space-x-8">
                                <NavLink :href="route('dashboard')" :active="route().current('dashboard')">
                                    Dashboard
                                </NavLink>

                                <NavLink v-if="isAdmin()" :href="route('admin.index')" :active="route().current('admin.index')">
                                    {{ adminLabel() }}
                                </NavLink>

                                <NavLink :href="route('events.index')" :active="route().current('events.index')">
                                    Évènements
                                </NavLink>

                                <NavLink :href="route('profile.edit')" :active="route().current('profile.edit')">
                                    Mon profil
                                </NavLink>
                            </div>
                        </div>

                        <!-- Right -->
                        <div class="hidden sm:flex sm:items-center">
                            <Dropdown align="right" width="48">
                                <template #trigger>
                                <span class="inline-flex rounded-md">
                                    <button type="button"
                                            class="inline-flex items-center rounded-md border border-transparent bg-white px-3 py-2 text-sm font-medium leading-4 text-gray-500 hover:text-gray-700 focus:outline-none">
                                        <p class="pseudo"><i class="fa-solid fa-user"></i> &ensp; {{ $page.props.auth.user.pseudo }}</p>
                                        <svg class="ms-2 h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                        </svg>
                                    </button>
                                </span>
                                </template>

                                <template #content>
                                    <DropdownLink :href="route('logout')" method="post" as="button">
                                        Déconnexion <i class="fa-solid fa-right-from-bracket"></i>
                                    </DropdownLink>
                                </template>
                            </Dropdown>
                        </div>

                        <!-- Hamburger -->
                        <button @click="showingNavigationDropdown = !showingNavigationDropdown"
                                class="inline-flex sm:hidden items-center justify-center rounded-md p-2 text-gray-400 hover:bg-gray-100 hover:text-gray-500">
                            <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                                <path :class="{ 'hidden': showingNavigationDropdown, 'inline-flex': !showingNavigationDropdown }"
                                      stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M4 6h16M4 12h16M4 18h16" />
                                <path :class="{ 'hidden': !showingNavigationDropdown, 'inline-flex': showingNavigationDropdown }"
                                      stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Responsive -->
                <div :class="{ block: showingNavigationDropdown, hidden: !showingNavigationDropdown }" class="sm:hidden">
                    <div class="space-y-1 pb-3 pt-2">
                        <ResponsiveNavLink :href="route('dashboard')" :active="route().current('dashboard')">
                            Dashboard
                        </ResponsiveNavLink>

                        <ResponsiveNavLink v-if="isAdmin()" :href="route('admin.index')">
                            {{ adminLabel() }}
                        </ResponsiveNavLink>

                        <ResponsiveNavLink :href="route('events.index')">
                            Évènements
                        </ResponsiveNavLink>

                        <ResponsiveNavLink :href="route('profile.edit')">
                            Mon profil
                        </ResponsiveNavLink>

                        <ResponsiveNavLink :href="route('logout')" method="post" as="button">
                            Déconnexion
                        </ResponsiveNavLink>
                    </div>
                </div>
            </nav>

            <header class="bg-white shadow" v-if="$slots.header">
                <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                    <slot name="header" />
                </div>
            </header>

            <main>
                <slot />
            </main>
        </div>
    </div>
</template>

<style scoped>
.pseudo {
    color: #1abc9c;
    font-size: 16px;
}
</style>
