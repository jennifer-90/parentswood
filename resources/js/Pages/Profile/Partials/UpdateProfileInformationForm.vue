<script setup>
import { useForm, usePage } from '@inertiajs/vue3'
import { ref, onBeforeUnmount } from 'vue'
import InputLabel from '@/Components/InputLabel.vue'
import TextInput from '@/Components/TextInput.vue'
import InputError from '@/Components/InputError.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'

const user = usePage().props.auth.user


// Petite aide: si on reçoit déjà une URL, on l'utilise.
// Sinon, si on reçoit un chemin relatif (ex: users/abc.jpg), on préfixe en /storage/.
// (utile si tu ne redéployes pas tout de suite le middleware)
const asUrl = (val) => {
    if (!val) return null
    if (val.startsWith('http') || val.startsWith('/')) return val
    return `/storage/${val}`
}

const form = useForm({
    first_name: user?.first_name ?? '',
    last_name:  user?.last_name ?? '',
    pseudo:     user?.pseudo ?? '',
    email:      user?.email ?? '',
    picture_profil: null,
})



const previewObjectUrl = ref(null)
const preview = ref(user?.picture_profil_url || asUrl(user?.picture_profil) || '/images/default-avatar.png')


function handleFileChange(event) {
    const file = event.target.files?.[0]
    if (!file) return
    form.picture_profil = file

    // libère l'ancien objectURL si existant
    if (previewObjectUrl.value) {
        URL.revokeObjectURL(previewObjectUrl.value)
        previewObjectUrl.value = null
    }
    previewObjectUrl.value = URL.createObjectURL(file)
    preview.value = previewObjectUrl.value
}

onBeforeUnmount(() => {
    if (previewObjectUrl.value) URL.revokeObjectURL(previewObjectUrl.value)
})


function submit() {
    form
        .transform((data) => ({
            ...data,
            _method: 'patch',      // on fait un POST avec override PATCH
        }))
        .post(route('profile.update'), {
            forceFormData: true,   // envoie bien en multipart/form-data
            preserveScroll: true,
            onSuccess: () => {
                const input = document.getElementById('picture_profil')
                if (input) input.value = ''
                // form.reset('picture_profil') // optionnel si tu veux aussi vider la valeur côté Inertia
            },
        })
}


</script>

<template>
    <form @submit.prevent="submit" enctype="multipart/form-data" class="space-y-6">
        <div class="space-y-1">
            <h3 class="text-lg font-medium text-gray-900 flex items-center">
                <span class="bg-[#59c4b4] text-white p-1.5 rounded-full mr-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                         stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                </span>
                Informations personnelles
            </h3>
            <p class="text-sm text-gray-500">Mettez à jour vos informations de profil et votre photo.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Prénom -->
            <div class="space-y-2">
                <InputLabel for="first_name" value="Prénom"/>
                <TextInput
                    id="first_name"
                    v-model="form.first_name"
                    type="text"
                    class="w-full"
                    :class="{ 'border-red-300': form.errors.first_name }"
                />
                <InputError :message="form.errors.first_name" class="mt-1"/>
            </div>

            <!-- Nom -->
            <div class="space-y-2">
                <InputLabel for="last_name" value="Nom"/>
                <TextInput
                    id="last_name"
                    v-model="form.last_name"
                    type="text"
                    class="w-full"
                    :class="{ 'border-red-300': form.errors.last_name }"
                />
                <InputError :message="form.errors.last_name" class="mt-1"/>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Pseudo -->
            <div class="space-y-2">
                <InputLabel for="pseudo" value="Pseudo"/>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <span class="text-gray-500">@</span>
                    </div>
                    <TextInput
                        id="pseudo"
                        v-model="form.pseudo"
                        type="text"
                        class="w-full pl-6"
                        :class="{ 'border-red-300': form.errors.pseudo }"
                    />
                </div>
                <InputError :message="form.errors.pseudo" class="mt-1"/>
            </div>

            <!-- Email -->
            <div class="space-y-2">
                <InputLabel for="email" value="Email"/>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                             fill="currentColor" aria-hidden="true">
                            <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"/>
                            <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"/>
                        </svg>
                    </div>
                    <TextInput
                        id="email"
                        v-model="form.email"
                        type="email"
                        class="w-full pl-10"
                        :class="{ 'border-red-300': form.errors.email }"
                    />
                </div>
                <InputError :message="form.errors.email" class="mt-1"/>
            </div>
        </div>

        <!-- Photo de profil -->
        <div class="space-y-4">
            <div>
                <InputLabel value="Photo de profil"/>
                <div class="flex items-center space-x-6">
                    <div class="shrink-0">
                        <img :src="preview" class="h-16 w-16 rounded-full object-cover border-2 border-white shadow"
                             alt="Photo de profil actuelle">
                    </div>
                    <label class="block">
                        <span class="sr-only">Choisir une photo de profil</span>
                        <input
                            type="file"
                            id="picture_profil"
                            accept="image/jpeg,image/png"
                            class="block w-full text-sm text-gray-500
                                file:mr-4 file:py-2 file:px-4
                                file:rounded-md file:border-0
                                file:text-sm file:font-semibold
                                file:bg-[#59c4b4] file:text-white
                                hover:file:bg-[#4db3a3]
                                transition-colors duration-200
                                cursor-pointer"
                            @change="handleFileChange"
                        />
                    </label>
                </div>
                <InputError :message="form.errors.picture_profil" class="mt-2"/>
            </div>
        </div>

        <div class="flex justify-end pt-4">
            <PrimaryButton
                type="submit"
                :class="{ 'opacity-25': form.processing }"
                :disabled="form.processing"
                class="w-full sm:w-auto justify-center"
            >
                <span v-if="!form.processing">
                    Enregistrer les modifications
                </span>
                <span v-else class="flex items-center">
                    <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg"
                         fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor"
                              d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Enregistrement...
                </span>
            </PrimaryButton>
        </div>

        <!-- Message de succès -->
        <div v-if="form.recentlySuccessful" class="mt-4">
            <div class="rounded-md bg-green-50 p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-green-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                             fill="currentColor">
                            <path fill-rule="evenodd"
                                  d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                  clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-green-800">
                            Votre profil a été mis à jour avec succès !
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </form>
</template>

<style scoped>
/* Styles spécifiques pour les transitions */
.enter-active, .leave-active {
    transition: opacity 0.3s ease, transform 0.3s ease;
}

.enter-from, .leave-to {
    opacity: 0;
    transform: translateY(-10px);
}
</style>
