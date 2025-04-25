# PROJET LARAVEL 11 AVEC VUE.JS 3

---

## 🔵 Introduction

Ce projet utilise **Laravel 11** pour le backend et **Vue.js 3** pour le frontend. Il est conçu pour être facilement
installé et lancé en local. Cette documentation vous guidera pas à pas.

---

## 🔵 Prérequis

Avant de commencer, assurez-vous d'avoir les outils suivants installés sur votre machine :

🔹1. **PHP** (version 8.2 ou supérieure) ➡️ [Télécharger PHP](https://www.php.net/downloads)

🔹2. **Composer** (pour gérer les dépendances PHP) ➡️ [Installer Composer](https://getcomposer.org/download/)

🔹3. **Node.js** (version 16 ou supérieure, inclut npm) ➡️ [Télécharger Node.js](https://nodejs.org/)

🔹4. **Git** (pour cloner le projet) ➡️ [Installer Git](https://git-scm.com/)

🔹5. **Base de données** : MySQL

🔹6. **Environnement de développement** (facultatif) : ➡️ Par
exemple, [Laragon](https://laragon.org/), [WampServer](https://www.wampserver.com/),
ou [XAMPP](https://www.apachefriends.org/index.html).

---

## 🔵 Installation

### 🔹 1. Cloner le projet depuis GitHub

```bash
  git clone https://github.com/votre-nom-utilisateur/parentswood.git
```

### 🔹2. Accéder au dossier du projet

```bash
  cd parentswood
```

### 🔹3. Installer les dépendances PHP

```bash
  composer install
```

### 🔹4. Installer les dépendances front-end

```bash
  npm install
```

### 🔹5. Configurer les variables d'environnement

##### - Dupliquez le fichier ```.env.example``` et renommez-le en ```.env```

```bash
  cp .env.example .env
```

##### - Modifiez le fichier ```.env``` pour configurer votre base de données.

```bash
  DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=parentswood
DB_USERNAME=root
DB_PASSWORD=''
```

### 🔹6. Générer la clé d'application

```bash
  php artisan key:generate
```

### 🔹7. Exécuter les migrations de base de données

```bash
  php artisan migrate
```

### 🔹8. Créer le lien symbolique pour les fichiers uploadés (images, etc.)
```bash
  php artisan storage:link
```

### 🔹9. Lancer le serveur de développement Laravel

```bash
  php artisan serve
```

##### - Cela démarrera le serveur à l'adresse : http://localhost:8000.

### 🔹10. Lancer le serveur Vite pour les assets front-end

##### - Dans un autre terminal, exécutez:

```bash
  npm run dev
```

##### - Cela permet de compiler les fichiers front-end et d'activer le rechargement automatique.

---

## 🔵 Accéder à l'application

##### Ouvrez votre navigateur et rendez-vous à : http://localhost:8000

## 🔵 Gestion des rôles

L'attribution des rôles se fait automatiquement via le formulaire d'inscription du site. Voici la logique appliquée :

Le premier utilisateur inscrit via le formulaire devient Super-admin.
Le deuxième utilisateur inscrit devient Admin.
Tous les utilisateurs inscrits par la suite reçoivent le rôle User par défaut.



