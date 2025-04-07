<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import {Head, Link, useForm} from '@inertiajs/vue3';
import {computed, ref, watch} from 'vue';
import axios from 'axios';

// Formulaire initialisé avec des valeurs vides
const form = useForm({
    last_name: '',
    first_name: '',
    pseudo: '',
    genre: '',
    email: '',
    password: '',
    password_confirmation: '',
});

// Vérification instantanée du pseudo
const pseudoAvailable = ref(null); // null = non vérifié, true = disponible, false = pris
const pseudoErrorMessage = ref('');
let debounceTimeout; // Utilisé pour limiter les requêtes backend

const checkPseudoAvailability = async (pseudo) => {
    clearTimeout(debounceTimeout);
    debounceTimeout = setTimeout(async () => {
        if (!pseudo.trim()) {
            pseudoAvailable.value = null;
            pseudoErrorMessage.value = '';
            return;
        }

        try {
            const response = await axios.get(route('pseudo.check', {pseudo}));
            pseudoAvailable.value = response.data.available;
            pseudoErrorMessage.value = pseudoAvailable.value
                ? 'Ce pseudo est disponible ! 😊'
                : 'Ce pseudo est déjà pris. 😢';
        } catch (error) {
            pseudoAvailable.value = null;
            pseudoErrorMessage.value = 'Erreur lors de la vérification. Veuillez réessayer.';
        }
    }, 300); // Attends 300ms après la dernière frappe
};

// Déclenche la vérification lorsque le pseudo change
watch(() => form.pseudo, (newPseudo) => {
    checkPseudoAvailability(newPseudo);
});

// Validation dynamique
const isLastNameValid = computed(() => form.last_name.trim().length > 0);
const isFirstNameValid = computed(() => form.first_name.trim().length > 0);
const isPasswordValid = computed(() => {
    const hasMinLength = form.password.length >= 8;
    const hasUpperCase = /[A-Z]/.test(form.password);
    const hasLowerCase = /[a-z]/.test(form.password);
    const hasNumber = /[0-9]/.test(form.password);
    const hasSymbol = /[\W_]/.test(form.password);
    return hasMinLength && hasUpperCase && hasLowerCase && hasNumber && hasSymbol;
});
const isPasswordMatch = computed(() => {
    return form.password_confirmation !== '' && form.password === form.password_confirmation;
});

// Soumission du formulaire
const submit = () => {
    form.post(route('register'), {
        onError: (errors) => {
            console.error('Erreurs de validation:', errors);
        },
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>


<template>
    <GuestLayout>
        <div class="page-all">
            <Head title="Inscription"/>
            <div class="text-center mb-6">
                <h1 class="text-2xl font-bold text-teal-600">Inscription</h1>
            </div>

            <!------------------- FORMULAIRE ---------------------------------------------------------------->
            <form @submit.prevent="submit" class="bg-white p-6 rounded shadow-md">
                <div class="grid grid-cols-2 gap-4">

                    <div> <!-- Nom -->
                        <label for="last_name" :class="{'text-green-500': isLastNameValid, 'text-black': !isLastNameValid}" class="flex items-center">
                            Nom
                            <span v-if="!isLastNameValid" class="text-red-500 ml-1"> *</span>
                            <span v-if="isLastNameValid" class="text-green-500 ml-1">✓</span>
                        </label>
                        <TextInput id="last_name" v-model="form.last_name" required autofocus autocomplete="family-name"/>
                        <InputError :message="form.errors.last_name"/>
                    </div>

                    <div> <!-- Prénom -->
                        <label for="first_name" :class="{'text-green-500': isFirstNameValid, 'text-black': !isFirstNameValid}" class="flex items-center">
                            Prénom
                            <span v-if="!isFirstNameValid" class="text-red-500 ml-1"> *</span>
                            <span v-if="isFirstNameValid" class="text-green-500 ml-1">✓</span>
                        </label>
                        <TextInput id="first_name" v-model="form.first_name" required autocomplete="given-name"/>
                        <InputError :message="form.errors.first_name"/>
                    </div>

                    <div> <!-- Pseudo -->
                        <InputLabel for="pseudo" value="Pseudo *"/>
                        <TextInput id="pseudo" v-model="form.pseudo" required autocomplete="off" placeholder="Choisissez un pseudo"/>
                        <p v-if="pseudoAvailable !== null" :class="pseudoAvailable ? 'text-green-500' : 'text-red-500'">
                            {{ pseudoErrorMessage }}
                        </p>
                        <InputError :message="form.errors.pseudo"/>
                    </div>

                    <div> <!-- Genre -->
                        <InputLabel for="genre" value="Genre *"/>
                        <select id="genre" v-model="form.genre" required class="mt-1 block w-full">
                            <option value="" disabled>-- Sélectionnez votre genre --</option>
                            <option value="homme">Homme</option>
                            <option value="femme">Femme</option>
                            <option value="autre">Autre</option>
                            <option value="non_specifie">Préférer ne pas dire</option>
                        </select>
                        <InputError :message="form.errors.genre"/>
                    </div>

                    <div> <!-- Email -->
                        <InputLabel for="email" value="Email *"/>
                        <TextInput id="email" type="email" v-model="form.email" required autocomplete="email"/>
                        <InputError :message="form.errors.email"/>
                    </div>

                    <div> <!-- Mot de passe -->
                        <label
                            for="password" :class="{'text-green-500': isPasswordValid, 'text-black': !isPasswordValid}" class="flex items-center">
                            Mot de passe
                            <span v-if="!isPasswordValid" class="text-red-500 ml-1"> *</span>
                            <span v-if="isPasswordValid" class="text-green-500 ml-1">✓</span>
                        </label>
                        <TextInput id="password" type="password" v-model="form.password" required autocomplete="new-password"/>
                        <InputError v-if="!isPasswordValid" message="Le mot de passe doit contenir au moins 8 caractères, une majuscule, une minuscule, un chiffre et un symbole."/>
                    </div>

                    <div class="col-span-2"> <!-- Confirmation du mot de passe -->
                        <label for="password_confirmation" :class="{'text-green-500': isPasswordMatch, 'text-black': !isPasswordMatch}" class="flex items-center">
                            Confirmer le mot de passe
                            <span v-if="!isPasswordMatch" class="text-red-500 ml-1"> *</span>
                            <span v-if="isPasswordMatch" class="text-green-500 ml-1">✓</span>
                        </label>
                        <TextInput id="password_confirmation" type="password" v-model="form.password_confirmation" required/>
                        <InputError v-if="!isPasswordMatch && form.password_confirmation !== ''" message="Les mots de passe ne correspondent pas."/>
                    </div>
                </div>

                <!------- BOUTONS ------->
                <div class="mt-6 flex justify-between items-center">
                    <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                        S'inscrire
                    </PrimaryButton>
                    <PrimaryButton>
                        <Link href="/">
                            <button class="btn">Retour à l'accueil</button>
                        </Link>
                    </PrimaryButton>

                </div>
            </form>
        </div>
    </GuestLayout>
</template>



<style scoped>
h1 {
    color: #1abc9c;
}

.page-all {
    background: rgb(249, 245, 242);
    padding-bottom: 80px;
}

form {
    max-width: 600px;
    margin: 0 auto;
}

input {
    width: 100%;
    padding: 10px;
    margin-top: 5px;
    border: 1px solid;
    border-radius: 5px;
}

input:focus {
    outline: none;
}

</style>
