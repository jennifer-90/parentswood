<!-- resources/js/Pages/Profile/Partials/DeactivateAccountCard.vue -->
<script setup>
import { ref, computed } from 'vue'
import { useForm } from '@inertiajs/vue3'
import InputLabel from '@/Components/InputLabel.vue'
import TextInput from '@/Components/TextInput.vue'
import InputError from '@/Components/InputError.vue'

const showModal = ref(false)
const confirmChecked = ref(false)

// On garde le design + UX ; l’action backend sera branchée après (route PATCH par ex. profile.deactivate)
const form = useForm({
    password: '', // vérification côté serveur (design inclus ici)
})

// Bouton principal -> ouvre la modal
const openModal = () => {
    showModal.value = true
    confirmChecked.value = false
    form.reset('password')
    form.clearErrors()
}

// Ferme la modal
const closeModal = () => {
    showModal.value = false
}

// Bouton "Confirmer" (ici on ne fait que montrer le design).
// Quand tu seras prêt, remplace le commentaire par :
// form.patch(route('profile.deactivate'), { ... })
const submit = () => {
    if (!canConfirm.value || form.processing) return
    form.patch(route('profile.deactivate'), {
        preserveScroll: true,
        onSuccess: () => {
            showModal.value = false
            form.reset()
        },
        onError: () => {
            // On laisse la modale ouverte pour afficher form.errors.password
        },
    })
}

// Activation du bouton confirmer
const canConfirm = computed(() => confirmChecked.value && form.password.trim().length > 0 && !form.processing)
</script>

<template>
    <section class="danger-zone mt-8">
        <!-- Bandeau d’avertissement -->
        <div class="rounded-lg border border-red-200 bg-red-50 p-4 mb-4">
            <div class="flex items-start gap-3">
                <div class="shrink-0">
          <span class="inline-flex h-9 w-9 items-center justify-center rounded-full bg-red-100 text-red-600">
            <i class="fa-solid fa-triangle-exclamation"></i>
          </span>
                </div>
                <div class="text-red-800">
                    <h3 class="font-semibold">Zone dangereuse</h3>
                    <ul class="mt-1 text-sm space-y-1">
                        <li class="flex items-start gap-2">
                            <i class="fa-solid fa-circle-small mt-1 text-red-400"></i>
                            <span>Mettre votre compte <strong>inactif</strong> masquera votre profil et vos activités aux autres utilisateurs.</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <i class="fa-solid fa-circle-small mt-1 text-red-400"></i>
                            <span>Vos données restent conservées afin de pouvoir <strong>réactiver</strong> votre compte plus tard.</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <i class="fa-solid fa-circle-small mt-1 text-red-400"></i>
                            <span>Vous ne recevrez plus de notifications et vous ne pourrez plus participer aux événements tant que le compte est inactif.</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Carte action -->
        <div class="rounded-lg border border-red-200 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between gap-4 flex-col sm:flex-row">
                <div class="text-center sm:text-left">
                    <h4 class="text-red-700 font-semibold flex items-center justify-center sm:justify-start gap-2">
                        <i class="fa-solid fa-user-slash"></i> Désactiver mon compte
                    </h4>
                    <p class="text-sm text-gray-600 mt-1">
                        Votre compte sera rendu <strong>inactif</strong>. Vous pourrez le réactiver ultérieurement en vous reconnectant
                        (ou via le support).
                    </p>
                </div>

                <button
                    type="button"
                    @click="openModal"
                    class="inline-flex items-center justify-center rounded-lg px-4 py-2.5 font-semibold text-white
                 bg-gradient-to-r from-red-500 to-red-600 hover:from-red-600 hover:to-red-700
                 shadow-sm hover:shadow-md transition"
                >
                    <i class="fa-solid fa-ban mr-2"></i>
                    Désactiver le compte
                </button>
            </div>
        </div>

        <!-- Modal de confirmation -->
        <div v-if="showModal" class="fixed inset-0 z-50">
            <!-- Overlay -->
            <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="closeModal"></div>

            <!-- Dialog -->
            <div
                class="absolute inset-0 flex items-center justify-center p-4"
                role="dialog" aria-modal="true" aria-labelledby="deactivate-title"
            >
                <div class="w-full max-w-lg rounded-xl bg-white shadow-xl">
                    <div class="px-5 py-4 border-b border-gray-100">
                        <h3 id="deactivate-title" class="text-lg font-semibold text-gray-900 flex items-center gap-2">
              <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-red-100 text-red-600">
                <i class="fa-solid fa-exclamation"></i>
              </span>
                            Confirmer la désactivation
                        </h3>
                    </div>

                    <div class="px-5 pt-4 pb-2 space-y-4">
                        <p class="text-sm text-gray-700">
                            Pour confirmer, veuillez <strong>cocher</strong> la case ci-dessous et entrer votre
                            <strong>mot de passe actuel</strong>.
                        </p>

                        <!-- Checkbox -->
                        <label class="flex items-start gap-3 cursor-pointer">
                            <input type="checkbox" class="mt-1 h-4 w-4 rounded border-gray-300 text-red-600 focus:ring-red-400"
                                   v-model="confirmChecked" />
                            <span class="text-sm text-gray-700">
                Je comprends que mon compte sera mis <strong>inactif</strong> et qu’il ne sera plus visible sur la plateforme.
              </span>
                        </label>

                        <!-- Password -->
                        <div class="space-y-1">
                            <InputLabel for="deactivate_password" value="Mot de passe actuel" />
                            <TextInput
                                id="deactivate_password"
                                v-model="form.password"
                                type="password"
                                class="w-full"
                                placeholder="••••••••"
                                autocomplete="current-password"
                                :class="{ 'border-red-300': form.errors.password }"
                            />
                            <InputError :message="form.errors.password" />
                        </div>
                    </div>

                    <div class="px-5 py-4 border-t border-gray-100 flex items-center justify-end gap-3">
                        <button
                            type="button"
                            @click="closeModal"
                            class="px-4 py-2.5 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50 transition"
                            :disabled="form.processing"
                        >
                            Annuler
                        </button>

                        <button
                            type="button"
                            @click="submit"
                            :disabled="!canConfirm || form.processing"
                            class="inline-flex items-center justify-center gap-2 rounded-lg px-4 py-2.5 font-semibold text-white
                             disabled:opacity-50 disabled:cursor-not-allowed
                             bg-gradient-to-r from-red-500 to-red-600 hover:from-red-600 hover:to-red-700
                             shadow-sm hover:shadow-md transition"
                            :aria-busy="form.processing ? 'true' : 'false'"
                        >
                            <svg v-if="form.processing" class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                            </svg>
                            <span>{{ form.processing ? 'Traitement…' : 'Confirmer la désactivation' }}</span>
                        </button>

                    </div>
                </div>
            </div>
        </div>
    </section>
</template>

<style scoped>
.danger-zone :is(button, a, input, textarea) { transition: all .2s ease; }
</style>
