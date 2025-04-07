<template>
    <GuestLayout>
        <div class="homepage">

            <!-- Conteneur principal avec citation et image -->
            <div class="grid-container">
                <!-- Colonne gauche : Citation -->
                <div class="left-column">
                    <h3 class="quote">"Être parent solo ne veut pas dire être seul(e) !"</h3>
                    <p class="description">
                        ParentsWood est une plateforme dédiée aux parents, spécialement aux parents solos,
                        pour se rencontrer, échanger et participer à des événements gratuits. Rejoignez une
                        communauté bienveillante et enrichissante.
                    </p>
                </div>

                <!-- Colonne droite : Image -->
                <div class="right-column">
                    <img src="/storage/picture_home.png" alt="Illustration" class="quote-img" />
                </div>
            </div>
            <br><br>

            <!-- Barre de recherche -->
            <div class="search-container">
                <h2 class="title">Nos évènements :</h2>
                <input type="text" placeholder="Que cherchez-vous ?" class="search-input" />
                <input type="text" placeholder="Votre localité" class="search-input" />
                <select class="search-dropdown">
                    <option>Cette semaine</option>
                    <option>Ce mois-ci</option>
                    <option>Cette année</option>
                </select>
                <button class="search-btn">Chercher</button>
            </div>

            <!-- Section des événements -->
            <section class="events">
                <div class="event-grid">
                    <div class="event-card" v-for="event in events" :key="event.id">
                        <p class="event-date">{{ event.date }}</p>
                        <p class="event-participants">{{ event.participants }} participants</p>
                        <p class="event-price">{{ event.price }}</p>
                    </div>
                </div>
            </section>
        </div>
    </GuestLayout>
</template>

<script>
import GuestLayout from "@/Layouts/GuestLayout.vue";

export default {
    components: { GuestLayout },
    data() {
        return {
            events: [
                { id: 1, date: "15 février 2025", participants: 55, price: "Free" },
                { id: 2, date: "20 janvier 2025", participants: 60, price: "Free" },
                { id: 3, date: "25 mars 2025", participants: 60, price: "Free" },
                { id: 4, date: "21 janvier 2025", participants: 60, price: "Free" },
                { id: 5, date: "15 février 2025", participants: 55, price: "Free" },
                { id: 6, date: "20 janvier 2025", participants: 60, price: "Free" },
                { id: 7, date: "25 mars 2025", participants: 60, price: "Free" },
                { id: 8, date: "21 janvier 2025", participants: 60, price: "Free" },
            ],
        };
    },
};
</script>

<style scoped>
/* Styles globaux */
.homepage {
    font-family: 'Arial', sans-serif;
    background-color: rgb(249, 245, 242);
    padding: 70px;
}

/* Barre de recherche */
.search-container {
    display: flex;
    gap: 10px;
    margin-bottom: 30px;
}

.search-input {
    flex: 1;
    padding: 10px;
    border: 1px solid #ccc;
    border-radius: 5px;
}

.search-dropdown {
    padding: 10px;
    border: 1px solid #ccc;
    border-radius: 5px;
}

.search-btn {
    background-color: #f39c12;
    color: white;
    border: none;
    padding: 10px 15px;
    border-radius: 5px;
    cursor: pointer;
}

/* Grid pour citation et image */
.grid-container {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-bottom: 30px;
}

.left-column {
    display: flex;
    flex-direction: column;
    justify-content: center;
}

.quote {
    font-size: 1.5rem;
    color: #1abc9c;
    margin-bottom: 20px;
}

.description {
    font-size: 1rem;
    color: #333;
    line-height: 1.6;
}

.right-column {
    display: flex;
    justify-content: center;
    align-items: center;
}

.quote-img {
    width: 100%;
    max-width: 300px;
    border-radius: 8px;
}

/* Section des évènements */
.events {
    margin-top: 20px;
}

.title {
    color: #1abc9c;
    font-size: 1.5rem;
    margin-bottom: 20px;
    text-align: center;
}

.event-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
}

.event-card {
    background-color: white;
    padding: 15px;
    border-radius: 8px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    text-align: center;
}

.event-date {
    font-weight: bold;
    color: #333;
}

.event-participants {
    color: #666;
    margin: 10px 0;
}

.event-price {
    color: #f39c12;
    font-weight: bold;
}
</style>
