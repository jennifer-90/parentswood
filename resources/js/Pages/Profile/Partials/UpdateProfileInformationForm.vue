<script setup>

// Import des helpers Inertia pour gérer le formulaire et récupérer les props de la page
import { useForm, usePage } from '@inertiajs/vue3'
// Import du reactive ref de Vue pour la prévisualisation d'image
import { ref } from 'vue'

// Import des composants réutilisables pour les labels, inputs, erreurs et bouton
import InputLabel from '@/Components/InputLabel.vue'
import TextInput from '@/Components/TextInput.vue'
import InputError from '@/Components/InputError.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'

/*-------------------------------------------------------------------------------*/
/**
 * Récupère l’objet utilisateur authentifié envoyé par le back via Inertia
 */
const user = usePage().props.auth.user

/**
 * Initialise le formulaire Inertia avec les valeurs actuelles de l'utilisateur
 * picture_profil est null au départ : on ne change la photo que si l'utilisateur en sélectionne une nouvelle
 * */
const form = useForm({
    first_name:     user.first_name,
    last_name:      user.last_name,
    pseudo:         user.pseudo,
    email:          user.email,
    picture_profil: null,
})

/**
 * Prépare la source de l'image de prévisualisation
 * Si l'utilisateur a déjà une photo, on l'affiche depuis /storage ; sinon on utilise un avatar par défaut
 */
const preview = ref(
    user.picture_profil
        ? `/storage/${user.picture_profil}`
        : '/images/default-avatar.png'
)

/**
 * handleFileChange : appelé quand l'utilisateur sélectionne une nouvelle photo
 * - Récupère le premier fichier sélectionné
 * - Met à jour form.picture_profil pour qu'Inertia l'envoie au serveur
 * - Met à jour preview.value pour afficher immédiatement la nouvelle image
 */
function handleFileChange(event) {
    const file = event.target.files[0]
    if (!file) return
    form.picture_profil = file
    preview.value = URL.createObjectURL(file)
}

/**
 * submit : déclenché à l'envoi du formulaire
 * - form.submit('patch', url, options) :
 *     • 'patch' : méthode HTTP simulée (override) pour Laravel
 *     • route('profile.update') : URL nommée où envoyer la requête
 *     • options :
 *         - forceFormData: true  → envoie en multipart/form-data (indispensable pour les fichiers)
 *         - preserveScroll: true → ne pas remettre la page en haut après réponse
 */
function submit() {
    form.patch(
        route('profile.update'),
        {}, // l'objet de données est vide, donc le prochain argument est les options
        {
            forceFormData:  true,
            preserveScroll: true,
        }
    )
}
</script>

<template>
    <form @submit.prevent="submit"
          enctype="multipart/form-data"
          class="w-full max-w-md mx-auto space-y-4">

        <header class="text-center">
            <h2 class="text-xl font-semibold text-gray-800">Modifier mes informations</h2>
            <p class="text-sm text-gray-500">Vous pouvez modifier un ou plusieurs champs.</p>
        </header>

        <!-- Prénom -->
        <div>
            <InputLabel for="first_name" value="Prénom" />
            <TextInput id="first_name" v-model="form.first_name"
                       type="text" class="mt-1 block w-full text-sm py-2" />
            <InputError :message="form.errors.first_name" class="mt-1 text-xs" />
        </div>

        <!-- Nom -->
        <div>
            <InputLabel for="last_name" value="Nom" />
            <TextInput id="last_name" v-model="form.last_name"
                       type="text" class="mt-1 block w-full text-sm py-2" />
            <InputError :message="form.errors.last_name" class="mt-1 text-xs" />
        </div>

        <!-- Pseudo -->
        <div>
            <InputLabel for="pseudo" value="Pseudo" />
            <TextInput id="pseudo" v-model="form.pseudo"
                       type="text" class="mt-1 block w-full text-sm py-2" />
            <InputError :message="form.errors.pseudo" class="mt-1 text-xs" />
        </div>

        <!-- Email -->
        <div>
            <InputLabel for="email" value="Email" />
            <TextInput id="email" v-model="form.email"
                       type="email" class="mt-1 block w-full text-sm py-2" />
            <InputError :message="form.errors.email" class="mt-1 text-xs" />
        </div>

        <!-- Preview photo actuelle / nouvelle -->
        <div class="flex justify-center">
            <img :src="preview" alt="Aperçu" class="w-20 h-20 rounded-full border" />
        </div>

        <!-- Upload photo -->
        <div>
            <InputLabel for="picture_profil" value="Nouvelle photo" />
            <input id="picture_profil"
                   type="file"
                   class="mt-1 block w-full text-sm"
                   @change="handleFileChange" />
            <InputError :message="form.errors.picture_profil" class="mt-1 text-xs" />
        </div>

        <!-- Bouton -->
        <div class="flex justify-center mt-6">
            <PrimaryButton :disabled="form.processing"
                           class="px-6 py-2 text-sm">
                Sauvegarder
            </PrimaryButton>
        </div>

        <p v-if="form.recentlySuccessful"
           class="text-center text-green-600 text-sm">
            Modifications enregistrées !
        </p>
    </form>
</template>
