<template>
    <AuthenticatedLayout>
        <div class="profile-page">
            <div class="profile-wrapper">
                <!-- PHOTO DE PROFIL -->
                <div class="photo-section">
                    <img
                        :src="user.picture_profil || '/images/default-avatar.png'"
                        alt="Photo de profil"
                        class="profile-photo"
                    />
                </div>

                <!-- INFOS -->
                <div class="info-section">
                    <h1 class="profile-title">{{ user.pseudo }}</h1>

                    <ul class="profile-details">
                        <li><strong>Nom :</strong> {{ user.last_name }}</li>
                        <li><strong>Prénom :</strong> {{ user.first_name }}</li>
                        <li><strong>🎭 Pseudo :</strong> {{ user.pseudo }}</li>
                        <li><strong>🎂 Âge :</strong> {{ user.age || 'Non renseigné' }}</li>
                        <li><strong>👪 Type de parent :</strong> {{ user.genre }}</li>
                        <li><strong>🎯 Centres d’intérêt :</strong> {{ user.centre_interets?.join(', ') || 'Non précisé' }}</li>
                        <li><strong>📊 Événements créés :</strong> {{ user.events_created || 0 }}</li>
                    </ul>

                    <Link
                        :href="route('profile.edit')"
                        class="edit-btn"
                    >
                        ✏️ Modifier mon profil
                    </Link>


                    <!-- Bouton modifier -->
                    <Link
                        v-if="user.id === $page.props.auth.user.id"
                        :href="route('profile.edit')"
                        class="edit-btn"
                    >
                        ✏️ Modifier mon profil
                    </Link>
                </div>
            </div>

            <!-- ÉVÉNEMENTS PARTICIPÉS -->
            <div class="events-section">
                <h2>📅 Événements à venir</h2>
                <ul>
                    <li v-for="event in upcomingEvents" :key="event.id">✅ {{ event.name_event }} (le {{ event.date }})</li>
                </ul>

                <h2 class="mt-6">📜 Événements passés</h2>
                <ul>
                    <li v-for="event in pastEvents" :key="event.id">🔙 {{ event.name_event }} (le {{ event.date }})</li>
                </ul>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { computed, defineProps } from 'vue'
import { Link } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'

const props = defineProps({
    user: Object
})

// Séparer les événements passés / futurs
const today = new Date()

const upcomingEvents = computed(() =>
    props.user.events_participated?.filter(e => new Date(e.date) >= today)
)

const pastEvents = computed(() =>
    props.user.events_participated?.filter(e => new Date(e.date) < today)
)
</script>

<style scoped>
.profile-page {
    background: #f9f5f2;
    padding: 2rem;
    min-height: 100vh;
}

.profile-wrapper {
    display: flex;
    flex-direction: row;
    gap: 2rem;
    background: white;
    padding: 2rem;
    border-radius: 16px;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.06);
    margin-bottom: 2rem;
    flex-wrap: wrap;
}

.photo-section {
    flex: 0 0 180px;
    display: flex;
    justify-content: center;
    align-items: center;
}

.profile-photo {
    width: 160px;
    height: 160px;
    border-radius: 50%;
    object-fit: cover;
    border: 4px solid #0d9488;
}

.info-section {
    flex: 1;
}

.profile-title {
    font-size: 1.8rem;
    font-weight: bold;
    color: #0f766e;
    margin-bottom: 1rem;
}

.profile-details {
    list-style: none;
    padding: 0;
}

.profile-details li {
    margin-bottom: 0.5rem;
}

.edit-btn {
    display: inline-block;
    margin-top: 1rem;
    background-color: #fca311;
    color: white;
    padding: 0.6rem 1.2rem;
    border-radius: 6px;
    font-weight: bold;
    text-decoration: none;
}

.edit-btn:hover {
    background-color: #e58e0f;
}

.events-section {
    background: #ffffff;
    padding: 1.5rem;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
}

.events-section h2 {
    color: #0f766e;
    font-size: 1.2rem;
    margin-bottom: 0.5rem;
}

.events-section ul {
    list-style: none;
    padding-left: 1rem;
}

.events-section li {
    padding: 0.4rem 0;
    font-size: 0.95rem;
}
</style>
