<script setup>
import { Link } from '@inertiajs/vue3';

const isActive = (route) => window.location.pathname === route;
</script>

<template>
    <header class="header">
        <!-- Logo et texte alignés à gauche -->
        <div class="flex items-center space-x-4">
            <Link href="/" class="flex items-center space-x-2">
                <img src="/storage/parentswood_logo.png" alt="ParentsWood Logo" class="h-16 w-16">
                <span class="text-2xl font-extrabold text-gray-900">PARENTS.WOOD</span>
            </Link>
        </div>

        <!-- Boutons à droite -->
        <nav class="nav">
            <Link href="/register" :class="isActive('/register') ? 'btn-disabled' : 'btn'">
                Inscription
            </Link>
            <Link href="/login" :class="isActive('/login') ? 'btn-disabled' : 'btn'">
                Connexion
            </Link>
        </nav>
    </header>

    <div>
        <slot />
    </div>
</template>

<style scoped>
.header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 70px 20px;
    background-color: #f9f9f9;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
}

/* Alignement du logo et du texte */
.flex {
    display: flex;
    align-items: center;
}

.nav {
    display: flex;
    gap: 16px;
}

.btn {
    background-color: #1abc9c;
    color: white;
    border: none;
    padding: 10px 15px;
    border-radius: 30px;
    cursor: pointer;
    text-decoration: none;
}

.btn:hover {
    background-color: #8be4d3;
    color: #fff;
    border: 2px solid #1abc9c;
}

.btn-disabled {
    background-color: #ccc;
    color: #666;
    cursor: not-allowed;
    border: none;
    padding: 10px 15px;
    border-radius: 30px;
    pointer-events: none; /* Empêche le clic */
}
</style>
