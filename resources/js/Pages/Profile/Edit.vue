<template>
    <Head title="Mon profil" />

    <AuthenticatedLayout>
        <div class="py-4 bg-[#f9f5f2] min-h-screen">
            <div class="mx-auto w-full px-3 sm:px-5">
                <div class="bg-white shadow-lg rounded-lg p-4 sm:p-6">
                    <!-- Titre principal avec icône -->
                    <div class="bg-transparent border-2 border-[#59c4b4] rounded-lg p-4 mb-8" style="background-color: rgba(89, 196, 180, 0.05);">
                        <div class="flex items-center justify-center gap-3">
                            <div class="bg-[#59c4b4] text-white rounded-full p-3">
                                <i class="fa-solid fa-user text-xl"></i>
                            </div>
                            <h1 class="text-2xl font-bold text-gray-800">Mon Profil</h1>
                        </div>
                        <p class="mt-2 text-center text-gray-600">Gérez vos informations personnelles et votre mot de passe</p>
                    </div>

                    <!-- Section profil -->
                    <div class="bg-[#59c4b4]/10 p-6 rounded-lg mb-8">
                        <div class="flex flex-col items-center">
                            <div class="relative group">
                                <img
                                    :src="$page.props.auth.user.picture_profil_url || '/images/default-avatar.png'"
                                    alt="Photo de profil"
                                    class="w-32 h-32 rounded-full border-4 border-white shadow-lg transition-transform duration-300 group-hover:scale-105"
                                >
                                <div class="absolute inset-0 rounded-full bg-black bg-opacity-30 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                                    <span class="text-white text-sm font-medium">Modifier</span>
                                </div>
                            </div>
                            <h2 class="mt-4 text-xl font-semibold text-gray-800">
                                {{ $page.props.auth.user.first_name }} {{ $page.props.auth.user.last_name }}
                            </h2>
                            <p class="text-gray-500">@{{ $page.props.auth.user.pseudo }}</p>
                        </div>
                    </div>

                    <!-- Grille des formulaires -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                        <!-- Formulaire informations -->
                        <div class="bg-[#59c4b4]/10 p-6 rounded-lg border border-gray-100">
                            <UpdateProfileInformationForm
                                :must-verify-email="mustVerifyEmail"
                                :status="status"
                            />
                        </div>

                        <!-- Formulaire mot de passe -->
                        <div class="bg-[#59c4b4]/10 p-6 rounded-lg border border-gray-100">
                            <UpdatePasswordForm />
                        </div>
                    </div>

                    <!-- Section suppression de compte -->
                    <div class="bg-red-50 p-6 rounded-lg border border-red-100">
                        <DeleteUserForm />
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue'
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue'
import DeleteUserForm from './Partials/DeleteUserForm.vue'
import { Head } from '@inertiajs/vue3'

defineProps({
    mustVerifyEmail: Boolean,
    status: String,
})
</script>

<style scoped>
/* Animations et styles spécifiques */
.transition-all {
    transition: all 0.3s ease-in-out;
}

/* Style personnalisé pour les champs de formulaire */
:deep(.input-group) {
    @apply mb-4;
}

:deep(label) {
    @apply block text-sm font-medium text-gray-700 mb-1;
}

:deep(input[type="text"],
      input[type="email"],
      input[type="password"],
      input[type="file"]) {
    @apply w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#59c4b4] focus:border-transparent transition duration-200;
}

:deep(button[type="submit"]) {
    @apply bg-gradient-to-r from-[#59c4b4] to-[#4db3a3] text-white px-6 py-2 rounded-lg font-medium hover:opacity-90 transition duration-200 shadow-md hover:shadow-lg transform hover:-translate-y-0.5;
}

:deep(.danger-zone) {
    @apply mt-8 pt-6 border-t border-gray-200;
}

:deep(.danger-zone h3) {
    @apply text-lg font-medium text-red-700 flex items-center;
}

:deep(.danger-zone p) {
    @apply text-sm text-red-600 mt-1;
}
</style>
