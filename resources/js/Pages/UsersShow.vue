<template>
    <AuthenticatedLayout>
        <div class="profile-page">
            <div class="profile-container">
                <!-- PHOTO DE PROFIL -->
                <img
                    :src="user.picture_profil || '/images/default-avatar.png'"
                    alt="Photo de profil"
                    class="profile-photo"
                />

                <!-- INFOS UTILISATEUR -->
                <div class="profile-info">
                    <h1 class="profile-title">{{ user.pseudo }}</h1>
                    <ul>
                        <li><strong>Nom :</strong> {{ user.last_name }}</li>
                        <li><strong>Prénom :</strong> {{ user.first_name }}</li>
                        <li><strong>Pseudo :</strong> {{ user.pseudo }}</li>
                        <li><strong>Âge :</strong> {{ user.age || 'Non renseigné' }}</li>
                        <li><strong>Type de parent :</strong> {{ user.genre }}</li>
                        <li><strong>Centre(s) d’intérêt :</strong> {{ user.centre_interets?.join(', ') || 'Non précisé' }}</li>
                    </ul>

                    <!-- Bouton Modifier visible uniquement si c'est mon propre profil -->
                    <Link
                        v-if="user.id === $page.props.auth.user.id"
                        :href="route('profile.edit')"
                        class="edit-btn"
                    >
                        Modifier mon profil
                    </Link>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { defineProps } from 'vue';
import { Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    user: Object
});
</script>

<style scoped>
.profile-page {
    background: #f9f5f2;
    padding: 2rem;
    border-radius: 10px;
}

.profile-container {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 1.5rem;
    max-width: 600px;
    margin: auto;
}

.profile-photo {
    width: 150px;
    height: 150px;
    object-fit: cover;
    border-radius: 50%;
    border: 4px solid #1abc9c;
}

.profile-info {
    text-align: center;
}

.profile-title {
    font-size: 1.8rem;
    font-weight: bold;
    color: #1abc9c;
    margin-bottom: 1rem;
}

ul {
    list-style-type: none;
    padding: 0;
}

li {
    margin-bottom: 0.5rem;
    font-size: 1rem;
}

.edit-btn {
    display: inline-block;
    margin-top: 1rem;
    padding: 0.5rem 1rem;
    background-color: #fca311;
    color: white;
    border-radius: 5px;
    text-decoration: none;
    font-weight: bold;
}

.edit-btn:hover {
    background-color: #e4940f;
}
</style>
