# 📸 Camagru

## 📌 Table des matières
1. [📖 Introduction](#-introduction)  
2. [🚀 Fonctionnalités](#-fonctionnalités)  
3. [📦 Prérequis](#-prérequis)  
4. [⚙️ Installation et Démarrage](#️-installation-et-démarrage)  
5. [🎮 Utilisation](#-utilisation)  
6. [🔧 Architecture du projet](#-architecture-du-projet)  
7. [🛠 Technologies utilisées](#-technologies-utilisées)  
8. [⭐ Bonus Implémentés](#-bonus-implémentés)

---

## 📖 Introduction
Camagru est une application web permettant aux utilisateurs de capturer et modifier des images avec des filtres avant de les publier dans une galerie publique. Ce projet, réalisé dans le cadre de l'école 42, met en avant des concepts clés du développement web tels que l'authentification sécurisée, la gestion des sessions, la manipulation d'images côté serveur et les interactions en temps réel.

---

## 🚀 Fonctionnalités
### 🔹 **Authentification et Gestion des Utilisateurs**
- Inscription avec vérification par email.
- Connexion sécurisée avec gestion de session.
- Réinitialisation du mot de passe via un lien envoyé par email.
- Modification du profil (nom d'utilisateur, email, mot de passe).
- Suppression définitive du compte.

### 🔹 **Édition et Capture d’Images**
- Prise de photos via webcam.
- Upload d’images pour les utilisateurs sans webcam.
- Superposition d’images avec filtres personnalisés.
- Sauvegarde sécurisée des images sur le serveur.
- Nettoyage automatique des fichiers temporaires.

### 🔹 **Galerie Publique et Interactions**
- Affichage des images publiées par les utilisateurs.
- Possibilité de liker et commenter les images.
- Notifications par email lors de nouveaux commentaires (désactivable).
- Partage des images sur les réseaux sociaux.
- Pagination infinie avec chargement dynamique.

### 🔹 **Sécurité et Protection des Données**
- Protection CSRF sur toutes les requêtes sensibles.
- Validation et filtrage des entrées utilisateur.
- Sécurisation des mots de passe avec hachage robuste.
- Prévention des attaques XSS et injections SQL.
- Gestion stricte des permissions pour l'accès aux fichiers et aux actions utilisateur.

### 🔹 **Déploiement et Gestion des Conteneurs**
- Conteneurisation complète avec Docker et Docker Compose.
- Commandes Makefile pour simplifier l'installation et le nettoyage du projet.
- Stockage persistant des données via volumes Docker.
- Utilisation de **ngrok** pour l'exposition sécurisée du serveur local.

---

## 📦 Prérequis
- **PHP 8.0+**
- **MySQL 8.0+**
- **Docker & Docker Compose**
- **ngrok** (pour exposer le serveur en externe)
- **Un navigateur compatible** (Firefox 41+ ou Chrome 46+)

---

## ⚙️ Installation et Démarrage

### 🔹 **1. Configuration du projet**
- Copier le fichier `.env.example` en `.env`
- Modifier les variables d’environnement comme les accès à la base de données et les informations de messagerie.

### 🔹 **2. Lancer l'application**
L'ensemble du projet est automatisé via un `Makefile` avec les commandes suivantes :

- **`make build`** : Construit les conteneurs Docker sans utiliser le cache.  
- **`make up`** : Démarre les conteneurs en arrière-plan.  
- **`make down`** : Arrête et supprime les conteneurs, volumes et réseaux orphelins.  
- **`make restart`** : Redémarre l'application proprement.  
- **`make logs`** : Affiche les logs des services en temps réel.  
- **`make clean`** : Nettoie les conteneurs, volumes et fichiers temporaires.  
- **`make init`** : Initialise entièrement le projet (droits des fichiers, conteneurs, base de données).  

Une fois lancé, l’application est accessible à l’adresse :  
🔗 **[http://localhost:8080](http://localhost:8080)**

---

## 🎮 Utilisation

1. **Créer un compte** et vérifier votre adresse email.  
2. **Se connecter** et accéder à l'éditeur photo.  
3. **Capturer ou télécharger une image**, y ajouter des filtres et publier.  
4. **Interagir avec les autres utilisateurs** en likant et commentant leurs images.  
5. **Gérer votre profil** et vos préférences de notifications.  

---

## 🔧 Architecture du projet
Le projet suit une **architecture MVC (Modèle-Vue-Contrôleur)** pour une meilleure organisation du code. Voici un aperçu des principaux dossiers :

```
📦 camagru
├── 📂 public # Fichiers accessibles publiquement (CSS, JS, images)
├── 📂 src # Code source principal (MVC)
│ ├── 📂 controllers # Gère les requêtes HTTP et la logique métier
│ ├── 📂 models # Requêtes et interactions avec la base de données
│ ├── 📂 views # Pages affichées aux utilisateurs
├── 📂 config # Configuration et sécurité ├── 📂 assets # Ressources statiques (CSS, JavaScript, images)
├── 📜 .env # Configuration de l’environnement
├── 📜 docker-compose.yml # Configuration des services Docker
├── 📜 Makefile # Commandes pour faciliter l’installation et le nettoyage
└── 📜 README.md # Documentation du projet
```


---

## 🛠 Technologies utilisées
- **Langages :** PHP 8, JavaScript, HTML5, CSS3  
- **Base de données :** MySQL 8  
- **Serveur web :** Nginx  
- **Librairies :** PHPMailer pour l’envoi d’emails  
- **Sécurité :** Protection CSRF, hachage des mots de passe, filtrage des entrées  
- **Déploiement :** Docker et Docker Compose  
- **Exposition serveur :** ngrok  

---

## ⭐ Bonus Implémentés

📌 **Améliorations et fonctionnalités avancées :**  

- 🏆 **Pagination infinie AJAX**  
- 📩 **Notifications email pour les commentaires (désactivables)**  
- 📤 **Partage d’images sur Facebook**  
- 🎭 **Prévisualisation en direct des filtres appliqués**  
- 🧹 **Nettoyage automatique des fichiers temporaires**  
- 🚀 **Gestion simplifiée avec Makefile**

