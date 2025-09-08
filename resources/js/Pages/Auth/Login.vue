<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import {Head, Link, useForm} from '@inertiajs/vue3';

// Initialisation du formulaire
const form = useForm({
    identifier: '', // Email ou pseudo
    password: '',
});

// Soumission du formulaire
const submit = async () => {
    try {
        await form.post(route('login'), {
            onFinish: () => form.reset('password'),
        });
    } catch (error) {
        console.error('Erreur de connexion :', error);
    }
};
</script>

<template>
    <GuestLayout>
        <div class="page-all">
            <Head title="Connexion"/>
            <div class="text-center mb-6">
                <h1 class="text-2xl font-bold text-teal-600">Connexion</h1>
            </div>

            <!-- Formulaire -->
            <form @submit.prevent="submit" class="bg-white p-6 rounded shadow-md">
                <div class="grid gap-4">
                    <div> <!-- Champ Email ou Pseudo -->
                        <InputLabel for="identifier" value="Email ou Pseudo *"/>
                        <TextInput id="identifier" v-model="form.identifier" required
                                   placeholder="Entrez votre email ou pseudo" autocomplete="username"/>
                        <InputError :message="form.errors.identifier"/>
                    </div>

                    <div> <!-- Champ Mot de passe -->
                        <InputLabel for="password" value="Mot de passe *"/>
                        <TextInput id="password" type="password" v-model="form.password" required
                                   placeholder="Entrez votre mot de passe" autocomplete="current-password"/>
                        <InputError :message="form.errors.password"/>
                        <div class="mt-2 text-right">
                            <Link href="#" class="forgot-link">
                                Mot de passe oublié ?
                            </Link>
                        </div>


                    </div>
                </div>

                <!-- Boutons -->
                <div class="mt-6 flex justify-between items-center">
                    <PrimaryButton :class="{ 'opacity-25': form.processing }"
                                   :disabled="form.processing">
                        Connexion
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
    padding-bottom: 340px;
}

form {
    max-width: 400px;
    margin: 0 auto;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

input {
    width: 100%;
    padding: 10px;
    margin-top: 5px;
    border: 1px solid #ccc;
    border-radius: 5px;
}

.forgot-link {
    font-size: 0.875rem;       /* 14px */
    color: #1abc9c;
    text-decoration: underline; /* Soulignement permanent */
    transition: color 0.2s ease;
}

.forgot-link:hover {
    color: #15967f; /* un peu plus foncé au survol */
}


</style>
