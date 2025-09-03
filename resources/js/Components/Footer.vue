<script setup>
import { computed } from 'vue'

const props = defineProps({
    appName: { type: String, default: 'Parentswood' },
    urls:    { type: Object, default: () => ({}) }
})

const year = new Date().getFullYear()

// URLs par défaut (écrasables via props.urls)
const resolvedUrls = computed(() => ({
    privacy: '#',
    terms:   '#',
    legal:   '#',
    contact: 'mailto:parentswood@gmail.com',
    ...props.urls
}))
</script>

<template>
    <footer class="bg-white border-t border-gray-200">
        <section class="mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-8 py-10 sm:py-12">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <!-- Marque + réseaux -->
                <div>
                    <div class="flex items-center gap-3">
                        <span class="text-xl font-bold tracking-tight text-gray-900">{{ appName }}</span>
                    </div>
                    <p class="mt-3 text-sm leading-relaxed text-gray-600">
                        Créez, découvrez et rejoignez des activités près de chez vous.
                        Une communauté bienveillante, des rencontres simples.
                    </p>

                    <div class="mt-4 flex items-center gap-3">
                        <a href="https://www.instagram.com/" aria-label="Instagram"
                           class="group inline-flex h-9 w-9 items-center justify-center rounded-full border border-gray-200 hover:border-[#59c4b4] transition-colors">
                            <i class="fa-brands fa-instagram text-gray-500 group-hover:text-[#59c4b4]"></i>
                        </a>
                        <a href="https://www.facebook.com/" aria-label="Facebook"
                           class="group inline-flex h-9 w-9 items-center justify-center rounded-full border border-gray-200 hover:border-[#59c4b4] transition-colors">
                            <i class="fa-brands fa-facebook text-gray-500 group-hover:text-[#59c4b4]"></i>
                        </a>
                        <a href="https://x.com/" aria-label="X / Twitter"
                           class="group inline-flex h-9 w-9 items-center justify-center rounded-full border border-gray-200 hover:border-[#59c4b4] transition-colors">
                            <i class="fa-brands fa-x-twitter text-gray-500 group-hover:text-[#59c4b4]"></i>
                        </a>
                        <a href="https://fr.linkedin.com/" aria-label="LinkedIn"
                           class="group inline-flex h-9 w-9 items-center justify-center rounded-full border border-gray-200 hover:border-[#59c4b4] transition-colors">
                            <i class="fa-brands fa-linkedin text-gray-500 group-hover:text-[#59c4b4]"></i>
                        </a>
                    </div>
                </div>

                <!-- À propos (2 colonnes) -->
                <nav aria-labelledby="footer-about-title">
                    <h4 id="footer-about-title" class="text-sm font-semibold text-gray-900 tracking-wider">À propos</h4>
                    <ul class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-2 text-sm">
                        <li>
                            <a href="#" class="text-gray-600 hover:text-[#59c4b4] transition-colors">
                                <i class="fa-solid fa-people-group mr-2 text-xs opacity-70"></i> Notre mission
                            </a>
                        </li>

                        <li>
                            <a :href="resolvedUrls.legal" class="text-gray-600 hover:text-[#59c4b4] transition-colors">
                                <i class="fa-solid fa-file-contract mr-2 text-xs opacity-70"></i> Mentions légales
                            </a>
                        </li>

                        <li>
                            <a :href="resolvedUrls.privacy" class="text-gray-600 hover:text-[#59c4b4] transition-colors">
                                <i class="fa-solid fa-user-shield mr-2 text-xs opacity-70"></i> Vie privée
                            </a>
                        </li>
                        <li>
                            <a :href="resolvedUrls.terms" class="text-gray-600 hover:text-[#59c4b4] transition-colors">
                                <i class="fa-solid fa-scale-balanced mr-2 text-xs opacity-70"></i> Conditions d’utilisation
                            </a>
                        </li>

                        <li>
                            <a href="#" class="text-gray-600 hover:text-[#59c4b4] transition-colors">
                                <i class="fa-solid fa-flag mr-2 text-xs opacity-70"></i> Charte de la communauté
                            </a>
                        </li>

                    </ul>
                </nav>

                <!-- Contact -->
                <div aria-labelledby="footer-contact-title">
                    <h4 id="footer-contact-title" class="text-sm font-semibold text-gray-900 tracking-wider">Contact</h4>
                    <ul class="mt-4 space-y-3 text-sm">
                        <li class="flex items-start gap-3 text-gray-600">
                            <i class="fa-solid fa-location-dot mt-0.5 text-[#59c4b4]"></i>
                            <span>Jambes, Belgique</span>
                        </li>
                        <li class="flex items-start gap-3 text-gray-600">
                            <i class="fa-solid fa-envelope mt-0.5 text-[#59c4b4]"></i>
                            <a :href="resolvedUrls.contact" class="hover:text-[#59c4b4] transition-colors">
                                parentswood@gmail.com
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Barre du bas -->
            <div class="mt-10 pt-6 border-t border-gray-200 flex flex-col md:flex-row items-center justify-between gap-3">
                <p class="text-sm text-gray-500">
                    © {{ year }} {{ appName }} — Tous droits réservés.
                </p>
                <div class="flex items-center gap-4 text-sm">
                    <a :href="resolvedUrls.privacy" class="text-gray-500 hover:text-[#59c4b4] transition-colors">Vie privée</a>
                    <span aria-hidden="true" class="text-gray-300">•</span>
                    <a :href="resolvedUrls.terms" class="text-gray-500 hover:text-[#59c4b4] transition-colors">Conditions</a>
                    <span aria-hidden="true" class="text-gray-300">•</span>
                    <a :href="resolvedUrls.legal" class="text-gray-500 hover:text-[#59c4b4] transition-colors">Mentions légales</a>
                </div>
            </div>
        </section>
    </footer>
</template>
