<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const form = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
})

const updatePassword = () => {
    form.post(
        route('profile.updatePassword'),
        {
            _method: 'PATCH',
            current_password: form.current_password,
            password: form.password,
            password_confirmation: form.password_confirmation,
        },
        {
            preserveScroll: true,
            preserveState: true,
            forceFormData: true,
            onSuccess: () => form.reset(),
            onError: () => {
                if (form.errors.password) {
                    form.reset('password','password_confirmation')
                }
                if (form.errors.current_password) {
                    form.reset('current_password')
                }
            },
        }
    )
}
</script>

<template>
    <form @submit.prevent="updatePassword" class="space-y-6">
        <div class="space-y-1">
            <h3 class="text-lg font-medium text-gray-900 flex items-center">
                <span class="bg-[#59c4b4] text-white p-1.5 rounded-full mr-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                </span>
                Sécurité du compte
            </h3>
            <p class="text-sm text-gray-500">Mettez à jour votre mot de passe pour sécuriser votre compte.</p>
        </div>

        <div class="space-y-6">
            <!-- Mot de passe actuel -->
            <div class="space-y-2">
                <InputLabel for="current_password" value="Mot de passe actuel" />
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <TextInput
                        id="current_password"
                        ref="currentPasswordInput"
                        v-model="form.current_password"
                        type="password"
                        class="w-full pl-10"
                        :class="{ 'border-red-300': form.errors.current_password }"
                        autocomplete="current-password"
                    />
                </div>
                <InputError :message="form.errors.current_password" class="mt-1" />
            </div>

            <!-- Nouveau mot de passe -->
            <div class="space-y-2">
                <InputLabel for="password" value="Nouveau mot de passe" />
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 1a4.5 4.5 0 00-4.5 4.5V9H5a2 2 0 00-2 2v6a2 2 0 002 2h10a2 2 0 002-2v-6a2 2 0 00-2-2h-.5V5.5A4.5 4.5 0 0010 1zm3 8V5.5a3 3 0 10-6 0V9h6z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <TextInput
                        id="password"
                        ref="passwordInput"
                        v-model="form.password"
                        type="password"
                        class="w-full pl-10"
                        :class="{ 'border-red-300': form.errors.password }"
                        autocomplete="new-password"
                    />
                </div>
                <InputError :message="form.errors.password" class="mt-1" />
                <p class="mt-1 text-xs text-gray-500">Utilisez au moins 8 caractères avec des lettres, des chiffres et des symboles.</p>
            </div>

            <!-- Confirmation du mot de passe -->
            <div class="space-y-2">
                <InputLabel for="password_confirmation" value="Confirmer le mot de passe" />
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <TextInput
                        id="password_confirmation"
                        v-model="form.password_confirmation"
                        type="password"
                        class="w-full pl-10"
                        :class="{ 'border-red-300': form.errors.password_confirmation }"
                        autocomplete="new-password"
                    />
                </div>
                <InputError :message="form.errors.password_confirmation" class="mt-1" />
            </div>

            <!-- Indicateur de force du mot de passe -->
            <div class="space-y-2">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium text-gray-700">Force du mot de passe</span>
                    <span class="text-xs font-medium text-gray-500">Moyen</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2">
                    <div class="bg-yellow-400 h-2 rounded-full" style="width: 60%"></div>
                </div>
            </div>

            <!-- Bouton de soumission -->
            <div class="flex justify-end pt-2">
                <PrimaryButton
                    type="submit"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                    class="w-full sm:w-auto justify-center"
                >
                    <span v-if="!form.processing">
                        Mettre à jour le mot de passe
                    </span>
                    <span v-else class="flex items-center">
                        <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Mise à jour...
                    </span>
                </PrimaryButton>
            </div>

            <!-- Message de succès -->
            <div v-if="form.recentlySuccessful" class="mt-4">
                <div class="rounded-md bg-green-50 p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-green-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-green-800">
                                Votre mot de passe a été mis à jour avec succès !
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</template>

<style scoped>
/* Animation pour l'indicateur de force du mot de passe */
@keyframes progress-bar-stripes {
    0% { background-position: 1rem 0; }
    100% { background-position: 0 0; }
}

.progress-bar-animated {
    animation: progress-bar-stripes 1s linear infinite;
}
</style>
