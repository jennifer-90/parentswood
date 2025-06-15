<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

// Initialise le formulaire pour la mise à jour du mot de passe
// Les champs correspondent à ceux attendus par le back-end
const form = useForm({
    current_password: '',
    password:         '',
    password_confirmation: '',
})

/**
 * updatePassword : appelé lors de la soumission du formulaire
 * - form.post() envoie la requête en POST multipart
 * - _method: 'PATCH' override pour Laravel afin qu'il traite comme PATCH
 * - forceFormData: true pour envoyer les fichiers (pas de fichiers ici, mais obligatoire pour override)
 * - preserveScroll: true pour ne pas scroll la page en haut après réponse
 * - onSuccess: reset du formulaire après succès
 * - onError: focus et reset des champs en erreur
 */
const updatePassword = () => {
    form.post(
        route('profile.updatePassword'),
        {
            _method:              'PATCH', // Override pour Laravel
            current_password:     form.current_password,
            password:             form.password,
            password_confirmation: form.password_confirmation,
        },
        {
            forceFormData:  true,
            preserveScroll: true,
            onSuccess:      () => form.reset(), // Vide les champs après succès
            onError:        () => {
                // Si erreur sur nouveau mot de passe, on le réinitialise pour retaper
                if (form.errors.password) {
                    form.reset('password','password_confirmation')
                }
                // Si erreur sur mot de passe actuel, on réinitialise ce champ
                if (form.errors.current_password) {
                    form.reset('current_password')
                }
            },
        }
    )
}
</script>

<template>
    .<!-- Formulaire de changement de mot de passe -->
        <form @submit.prevent="updatePassword" class="w-full space-y-4">
            <header class="text-center mb-4">
                <h2 class="text-xl font-semibold text-gray-800">Modifier mon mot de passe</h2>
                <p class="text-sm text-gray-500">Assurez-vous d'utiliser un mot de passe fort.</p>
            </header>

            <!-- Champ : mot de passe actuel -->
            <div>
                <InputLabel for="current_password" value="Mot de passe actuel" />
                <TextInput
                    id="current_password"
                    ref="currentPasswordInput"
                    v-model="form.current_password"
                    type="password"
                    class="mt-1 block w-full text-sm py-2"
                    autocomplete="current-password"
                />
                <InputError :message="form.errors.current_password" class="mt-1 text-xs" />
            </div>

            <!-- Champ : nouveau mot de passe -->
            <div>
                <InputLabel for="password" value="Nouveau mot de passe" />
                <TextInput
                    id="password"
                    ref="passwordInput"
                    v-model="form.password"
                    type="password"
                    class="mt-1 block w-full text-sm py-2"
                    autocomplete="new-password"
                />
                <InputError :message="form.errors.password" class="mt-1 text-xs" />
            </div>

            <!-- Champ : confirmation du nouveau mot de passe -->
            <div>
                <InputLabel for="password_confirmation" value="Confirmer le mot de passe" />
                <TextInput
                    id="password_confirmation"
                    v-model="form.password_confirmation"
                    type="password"
                    class="mt-1 block w-full text-sm py-2"
                    autocomplete="new-password"
                />
                <InputError :message="form.errors.password_confirmation" class="mt-1 text-xs" />
            </div>

            <!-- Bouton -->
            <div class="flex justify-center mt-6">
                <PrimaryButton class="px-6 py-2 text-sm" :disabled="form.processing">
                    Enregistrer
                </PrimaryButton>
            </div>

            <!-- Message de confirmation -->
            <p v-if="form.recentlySuccessful" class="text-center text-green-600 text-sm">
                Mot de passe mis à jour !
            </p>
        </form>
</template>
