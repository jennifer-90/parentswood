<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import Footer from "@/Components/Footer.vue";
import Flash from '@/Components/Flash.vue'

const page = usePage();
const currentRoute = computed(() => page.url);
const showingMobileMenu = ref(false)



</script>

<template>
    <div class="min-h-screen bg-gray-50">
        <!-- En-tête -->
        <header class="bg-white shadow-sm">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-20 items-center">
                    <!-- Logo et nom du site -->
                    <div class="flex-shrink-0 flex items-center">
                        <Link href="/" class="flex items-center space-x-3">
                            <div class="h-12 w-12 flex items-center justify-center overflow-hidden">
                                <img
                                    src="/images/logo.png"
                                    alt="ParentsWood Logo"
                                    class="h-auto w-full object-contain"
                                >
                            </div>
                            <span class="text-2xl font-bold bg-gradient-to-r from-teal-600 to-teal-400 bg-clip-text text-transparent">
                                PARENTSWOOD
                            </span>
                        </Link>
                    </div>

                    <!-- Navigation -->
                    <nav class="hidden md:flex items-center space-x-6">
                        <Link
                            href="/"
                            :class="{
                                'text-teal-600 font-medium': currentRoute === '/',
                                'text-gray-600 hover:text-teal-600': currentRoute !== '/'
                            }"
                            class="text-sm font-medium transition-colors"
                        >
                            Accueil
                        </Link>

                        <div class="flex space-x-4">
                            <Link
                                href="/login"
                                :class="{
                                    'bg-teal-100 text-teal-700': currentRoute === '/login',
                                    'bg-white text-teal-600 border border-teal-600 hover:bg-teal-50': currentRoute !== '/login'
                                }"
                                class="px-4 py-2 rounded-full text-sm font-medium transition-colors"
                            >
                                Connexion
                            </Link>

                            <Link
                                href="/register"
                                :class="{
                                    'bg-teal-100 text-teal-700': currentRoute === '/register',
                                    'bg-teal-600 text-white hover:bg-teal-700': currentRoute !== '/register'
                                }"
                                class="px-4 py-2 rounded-full text-sm font-medium transition-colors"
                            >
                                Inscription
                            </Link>
                        </div>
                    </nav>

                    <!-- Bouton menu mobile -->
                    <div class="md:hidden">
                        <button
                            @click="showingMobileMenu = !showingMobileMenu"
                            class="inline-flex items-center justify-center p-2 rounded-md text-gray-600 hover:text-teal-600 hover:bg-gray-100 focus:outline-none"
                        >
                            <svg
                                class="h-6 w-6"
                                :class="{ 'hidden': showingMobileMenu, 'block': !showingMobileMenu }"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                            </svg>
                            <svg
                                class="h-6 w-6"
                                :class="{ 'hidden': !showingMobileMenu, 'block': showingMobileMenu }"
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
                v-show="showingMobileMenu"
                class="md:hidden border-t border-gray-200"
            >
                <div class="px-2 pt-2 pb-3 space-y-1">
                    <Link
                        href="/"
                        @click="showingMobileMenu = false"
                        :class="{
                            'bg-teal-50 text-teal-700': currentRoute === '/',
                            'text-gray-700 hover:bg-gray-100': currentRoute !== '/'
                        }"
                        class="block px-3 py-2 rounded-md text-base font-medium"
                    >
                        Accueil
                    </Link>

                    <div class="pt-4 border-t border-gray-200">
                        <Link
                            href="/login"
                            @click="showingMobileMenu = false"
                            :class="{
                                'bg-teal-100 text-teal-700': currentRoute === '/login',
                                'text-teal-600 hover:bg-teal-50': currentRoute !== '/login'
                            }"
                            class="block px-3 py-2 rounded-md text-base font-medium text-center"
                        >
                            Connexion
                        </Link>

                        <Link
                            href="/register"
                            @click="showingMobileMenu = false"
                            :class="{
                                'bg-teal-600 text-white': currentRoute !== '/register',
                                'bg-teal-700': currentRoute === '/register'
                            }"
                            class="block px-3 py-2 rounded-md text-base font-medium text-center mt-2"
                        >
                            Inscription
                        </Link>
                    </div>
                </div>
            </div>
        </header>

        <!-- Flash global (affiche $page.props.flash) -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4">
            <Flash />
        </div>

        <!-- Contenu principal -->
        <main>
            <slot />
        </main>

        <Footer />
    </div>
</template>

<style scoped>
/* Animation pour le menu mobile */
.fade-enter-active, .fade-leave-active {
    transition: opacity 0.3s ease;
}
.fade-enter-from, .fade-leave-to {
    opacity: 0;
}

/* Style pour les boutons actifs */
.router-link-active {
    @apply text-teal-600 font-medium;
}
</style>
