<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';

import FullCalendar from '@fullcalendar/vue3';
import dayGridPlugin from '@fullcalendar/daygrid';
import interactionPlugin from '@fullcalendar/interaction';
import frLocale from '@fullcalendar/core/locales/fr';
import '@fullcalendar/common/main.css';
import PrimaryButton from "@/Components/PrimaryButton.vue";

// Options pour FullCalendar
const calendarOptions = {
    plugins: [dayGridPlugin, interactionPlugin],
    initialView: 'dayGridMonth',
    headerToolbar: {
        left: 'prev,next today', // Boutons de navigation
        center: 'title', // Mois et année
        right: 'dayGridMonth,dayGridWeek,dayGridDay', // Vues disponibles
    },
    selectable: true, // Permet la sélection de dates
    editable: true, // Permet de déplacer ou modifier les événements
    locale: frLocale, // Langue française
    dayGridMonth: {
        titleFormat: { year: 'numeric', month: 'short' } // Affiche "Jan 2025"
    },
    dayGridWeek: {
        titleFormat: { year: 'numeric', month: 'short', day: 'numeric' } // Affiche "Jan 15 – 21, 2025"
    },
    dayGridDay: {
        titleFormat: { year: 'numeric', month: 'short', day: 'numeric' } // Affiche "Jan 15, 2025"
    },
    events: [
        { title: 'Événement 1', start: '2025-01-15' }, // Exemple d'événement 1
        { title: 'Événement 2', start: '2025-01-18' }, // Exemple d'événement 2
    ],
};
</script>

<template>
    <Head title="Mon tableau de bord" />
    <AuthenticatedLayout>
        <div class="py-4 bg-[#f9f5f2] min-h-screen">
            <div class="mx-auto w-full px-5">

                <!-- Tableau de bord -->
                <div class="bg-white shadow-lg rounded-lg p-6 text-center">
                    <h3 class="text-3xl font-bold text-gray-800 mb-6">Mon tableau de bord:</h3>

                    <!-- Disposition des trois div alignés horizontalement -->
                    <div class="flex gap-6">
                        <!-- Div pour le calendrier -->
                        <div class="flex-1 bg-gray-100 p-4 rounded-lg">
                            <h1 class="text-xl font-bold mb-4"><i class="fa-solid fa-calendar-days"></i> <br> Calendrier </h1>
                            <!-- FullCalendar avec mois et année au-dessus des boutons -->
                            <div class="fc-toolbar">
                                <FullCalendar :options="calendarOptions" />
                            </div>
                        </div>

                        <!-- Div pour les événements -->
                        <div class="flex-1 bg-gray-50 p-4 rounded-lg">
                            <h1 class="text-xl font-bold mb-4"><i class="fa-solid fa-users"></i> <br>Section évènements</h1>
                            <!-- 1. Première div : Section événements -->
                            <div class="bg-gray-100 p-4 rounded-lg mb-4 flex flex-col justify-between" style="min-height: 200px;">
                                <h1 class="text-m font-bold mb-4"> <br> Vous souhaitez créer un évènement?</h1>
                                <p class="text-gray-600 mt-4">Attention il ne vous reste plus que x évènements à créer.</p>
                                <div>
                                    <br> <primary-button class="bg-green-500 text-white py-2 px-4 rounded mb-4 hover:bg-green-600">
                                        Créer un événement
                                    </primary-button>
                                    &ensp;
                                    <primary-button class="bg-blue-500 text-white py-2 px-4 rounded mb-4 hover:bg-blue-600">
                                        Voir vos événements
                                    </primary-button>
                                </div>
                            </div>

                            <!-- 2. Deuxième div : Vos événements créés -->
                            <div class="bg-gray-100 p-4 rounded-lg" style="min-height: 200px;">
                                <h1 class="text-xl font-bold mb-4">Vos évènements créés</h1>
                                <!-- Conteneur pour les divs alignées horizontalement et de même largeur -->
                                <div class="flex space-x-4">
                                    <!-- Première div : Total des événements -->
                                    <div class="flex-1 bg-white p-4 rounded-lg shadow-md flex items-center justify-center">
                                        <div class="text-center">
                                            <span class="text-4xl font-bold text-blue-500">76</span>
                                            <p class="text-gray-600">évènement(s) créé(s) à ce jour.</p>
                                        </div>
                                    </div>

                                    <!-- Deuxième div : Événements actifs -->
                                    <div class="flex-1 bg-white p-4 rounded-lg shadow-md flex items-center justify-center">
                                        <div class="text-center">
                                            <span class="text-4xl font-bold text-blue-500">5</span>
                                            <p class="text-gray-600">évènement(s) actif(s).</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Div pour la boîte à messages -->
                        <div class="flex-1 bg-gray-50 p-4 rounded-lg">
                            <h1 class="text-xl font-bold mb-4"><i class="fa-solid fa-envelope"></i> <br>Ma boite à messages </h1>
                            <p class="text-gray-600 mt-4">Aucun message pour le moment.</p>
                        </div>
                    </div>
                    <br><p>Ton prochain évènement se déroule dans "chrono" </p>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style>
/* === Disposition générale === */
.flex {
    display: flex;
    justify-content: space-between; /* Répartit uniformément les div */
}

/* === Boutons de navigation dans le calendrier (flèches, Aujourd'hui, etc.) === */
.fc-button {
    background-color: #1abc9c; /* Couleur de fond */
    border: none; /* Suppression des bordures */
    color: white; /* Couleur du texte */
    border-radius: 3px; /* Coins légèrement arrondis */
    padding: 2px 6px; /* Réduction du padding pour boutons plus compacts */
    margin: 0 4px; /* Espacement entre les boutons */
    font-size: 11px; /* Taille de la police réduite */
    min-width: 45px; /* Largeur minimale pour un bouton compact */
    height: 28px; /* Hauteur réduite pour le bouton "Aujourd'hui" */
    transition: background-color 0.3s ease; /* Animation pour effet au survol */
}

.fc-button:hover {
    background-color: #16a085; /* Couleur au survol */
}

/* === Titre de la barre d'outils (Mois et Année) === */
.fc-toolbar-title {
    font-size: 16px; /* Taille augmentée pour plus de lisibilité */
    color: #1abc9c; /* Couleur du texte */
    font-weight: bold; /* Texte en gras */
    margin-bottom: 8px; /* Espacement avec les boutons */
    text-align: center; /* Centre le texte */
}

/* === Boutons de vue (Mois, Semaine, Jour) === */
.fc-button-group .fc-button {
    padding: 2px 6px; /* Même padding que les autres boutons */
    font-size: 11px; /* Taille de la police */
    min-width: 45px; /* Largeur minimale */
    height: 28px; /* Hauteur légèrement augmentée */
}

/* === Cases des jours du calendrier === */
.fc-daygrid-day {
    height: 80px; /* Hauteur des cases des jours */
    background-color: #f9f9f9; /* Couleur de fond */
    border: 2px solid #ddd; /* Bordure des cases */
    position: relative; /* Position relative pour contenir les éléments internes */
}

/* === Contenu des cases des jours === */
.fc-daygrid-day-frame {
    height: 100%; /* Prend toute la hauteur de la case */
    display: flex; /* Flexbox pour alignement */
    align-items: flex-start; /* Aligne le contenu en haut */
    justify-content: flex-start; /* Aligne le contenu à gauche */
    padding: 8px; /* Espacement autour du contenu */
    box-sizing: border-box; /* Inclut le padding dans la taille de la case */
}

/* === Numéros des jours === */
.fc-daygrid-day-number {
    font-size: 14px; /* Taille réduite des numéros des jours */
    font-weight: bold; /* Texte en gras */
    position: relative; /* Positionnement pour rester dans la case */
    z-index: 1; /* Priorité d'affichage au-dessus des événements */
}

/* === Événements affichés dans le calendrier === */
.fc-event {
    background-color: #1abc9c; /* Couleur de fond des événements */
    border: 1px solid #16a085; /* Bordure des événements */
    color: white; /* Couleur du texte des événements */
    border-radius: 5px; /* Coins arrondis */
    font-size: 10px; /* Réduction de la taille de la police pour les événements */
    padding: 2px 4px; /* Espacement interne réduit */
    margin: 2px; /* Espacement entre plusieurs événements */
}

/* === Lignes entre les semaines === */
.fc-daygrid-row {
    border-bottom: 2px solid #ccc; /* Séparation claire entre les semaines */
}

/* Cible tous les boutons de la barre d'outils */
.fc .fc-button {
    padding: 4px 8px; /* Ajuste le padding pour modifier la taille */
    font-size: 14px;  /* Ajuste la taille de la police */
}

/* Cible spécifiquement le bouton "Aujourd'hui" */
.fc .fc-today-button {
    padding: 4px 8px; /* Ajuste le padding pour ce bouton */
    font-size: 14px;  /* Ajuste la taille de la police */
}

</style>
