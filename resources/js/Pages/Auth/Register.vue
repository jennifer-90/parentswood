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


// --- Check pseudo (AJAX) ---
const pseudoAvailable = ref(null); // null = non vérifié, true = disponible, false = pris
const pseudoErrorMessage = ref('');
let debounceTimeout; // Utilisé pour limiter les requêtes backend

/* états pour gérer le 429 / cooldown */
const cooldownRemaining = ref(0);       // secondes restantes
let   cooldownTimer = null;             // setInterval handler

/* lance un cooldown (bloque les checks pendant N secondes) */
const startCooldown = (seconds) => {
    const s = Number.isFinite(+seconds) && +seconds > 0 ? +seconds : 60;
    // stop ancien timer si existant
    if (cooldownTimer) { clearInterval(cooldownTimer); cooldownTimer = null; }

    cooldownRemaining.value = s;
    pseudoAvailable.value = null;
    pseudoErrorMessage.value = `Trop de tentatives, réessaie dans ${cooldownRemaining.value}s.`;

    cooldownTimer = setInterval(() => {
        cooldownRemaining.value -= 1;
        if (cooldownRemaining.value <= 0) {
            clearInterval(cooldownTimer);
            cooldownTimer = null;
            cooldownRemaining.value = 0;
            // on efface le message à la fin du cooldown
            pseudoErrorMessage.value = '';
        } else {
            // met à jour le message chaque seconde
            pseudoErrorMessage.value = `Trop de tentatives, réessaie dans ${cooldownRemaining.value}s.`;
        }
    }, 1000);
};

watch(() => form.pseudo, (newPseudo) => {
    clearTimeout(debounceTimeout)
    debounceTimeout = setTimeout(async () => {
        const p = (newPseudo ?? '').trim().toLowerCase()

        // si vide, reset
        if (!p) {
            pseudoAvailable.value = null
            pseudoErrorMessage.value = ''
            return
        }

        // on ne spamme pas pendant le cooldown
        if (cooldownRemaining.value > 0) return

        try {
            const { data } = await axios.get(route('pseudo.check', { pseudo: p }))
            if (data.format_invalid) {
                pseudoAvailable.value = false
                pseudoErrorMessage.value = 'Le pseudo doit contenir 3–20 caractères, lettres/chiffres uniquement.'
            } else {
                pseudoAvailable.value = data.available
                pseudoErrorMessage.value = data.available
                    ? 'Ce pseudo est disponible ! 😊'
                    : 'Ce pseudo est déjà pris. 😢'
            }
        } catch (error) {
            if (error?.response?.status === 429) {
                const retryAfter = parseInt(error.response.headers?.['retry-after'], 10)
                startCooldown(retryAfter)
            } else {
                pseudoAvailable.value = null
                pseudoErrorMessage.value = 'Erreur lors de la vérification. Réessayez.'
            }
        }
    }, 300)
})


// --- Petits ✓ nom/prénom ---
const isLastNameValid = computed(() => form.last_name.trim().length > 0);
const isFirstNameValid = computed(() => form.first_name.trim().length > 0);

// --- MDP / confirmation ---
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


// --- Submit ---
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
                        <TextInput id="last_name" v-model="form.last_name" maxlength=50 required autofocus autocomplete ="family-name"/>
                        <InputError :message="form.errors.last_name"/>
                    </div>

                    <div> <!-- Prénom -->
                        <label for="first_name" :class="{'text-green-500': isFirstNameValid, 'text-black': !isFirstNameValid}" class="flex items-center">
                            Prénom
                            <span v-if="!isFirstNameValid" class="text-red-500 ml-1"> *</span>
                            <span v-if="isFirstNameValid" class="text-green-500 ml-1">✓</span>
                        </label>
                        <TextInput id="first_name" v-model="form.first_name" maxlength=50  required autocomplete="given-name"/>
                        <InputError :message="form.errors.first_name"/>
                    </div>

                    <div> <!-- Pseudo -->
                        <InputLabel for="pseudo" value="Pseudo *"/>
                        <TextInput id="pseudo" v-model="form.pseudo" maxlength=20  required autocomplete="off" placeholder="Choisissez un pseudo"/>

                        <p v-if="cooldownRemaining > 0" class="text-amber-600 mt-1 text-sm">
                            {{ pseudoErrorMessage }}
                        </p>
                        <p v-else-if="pseudoAvailable !== null"
                           :class="['mt-1 text-sm', pseudoAvailable ? 'text-green-600' : 'text-red-600']">
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
