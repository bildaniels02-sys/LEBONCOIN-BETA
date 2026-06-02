# Le Bon Coin Beta

Ce projet est une application web PHP inspirée de Le Bon Coin, conçue pour gérer des petites annonces.

## Fonctionnalités

- Inscription sécurisée avec email et mot de passe chiffré
- Connexion sécurisée
- Création d'annonces avec titre, prix, description, catégorie et photo
- Modification d'annonces avec formulaire pré-rempli
- Suppression d'annonces avec confirmation
- Liste publique d'annonces affichées sous forme de cartes
- Détail de chaque annonce
- Filtrage des annonces par recherche, catégorie et intervalle de prix
- Favoris pour les utilisateurs connectés
- Envoi de messages pour une annonce
- Consultation des messages reçus et envoyés

## Structure du projet

- `index.php` : page d'accueil et affichage des annonces avec filtres
- `register.php` : formulaire d'inscription
- `login.php` : formulaire de connexion
- `logout.php` : déconnexion
- `ad_create.php` : création d'une nouvelle annonce
- `ad_edit.php` : modification d'une annonce existante
- `ad_delete.php` : suppression d'une annonce avec confirmation
- `ad_detail.php` : page de détails d'une annonce + favoris + message
- `favorites.php` : liste des annonces favorites de l'utilisateur
- `messages.php` : consultation des échanges liés aux annonces
- `account.php` : page de compte utilisateur

### Dossiers

- `includes/` : configuration et fonctions partagées
  - `config.php` : connexion PDO et gestion de session
  - `functions.php` : fonctions utilitaires (redirection, flash messages, upload d'image, catégories)
- `templates/` : en-tête et pied de page partagés
  - `header.php`
  - `footer.php`
- `css/` : styles de l'application
  - `style.css`
- `sql/` : script d'initialisation de la base de données
  - `init.sql`
- `uploads/` : dossier généré pour stocker les photos d'annonces (créé automatiquement)

## Base de données

Le script `sql/init.sql` crée la base de données et les tables suivantes :

- `users` : utilisateurs avec email et mot de passe hashé
- `ads` : annonces reliées à un utilisateur
- `favorites` : relation favoris entre utilisateur et annonce
- `messages` : messages envoyés concernant une annonce

## Installation et exécution

1. Copier le dossier `PROJ ANNONCE` dans le répertoire de MAMP `htdocs`
2. Lancer MAMP et démarrer Apache et MySQL
3. Importer `sql/init.sql` dans MySQL via phpMyAdmin ou un terminal MySQL
4. Vérifier la configuration de base de données dans `includes/config.php`
   - hôte : `127.0.0.1`
   - base : `projet_annonce`
   - utilisateur : `root`
   - mot de passe : ``
5. Ouvrir le projet dans le navigateur : `http://localhost/PROJ%20ANNONCE/`

## Notes techniques

- Les mots de passe sont stockés avec `password_hash()` et vérifiés avec `password_verify()`.
- Les images sont uploadées dans le dossier `uploads/` et référencées par leur chemin relatif.
- Les pages nécessitant une connexion appellent `requireLogin()` pour restreindre l'accès.
- Les erreurs et messages de succès utilisent des flash messages stockés en session.

## Améliorations possibles

- Protection CSRF pour les formulaires
- Pagination des annonces
- Gestion des utilisateurs et profil
- Notifications d'email
- Recherche avancée et catégories dynamiques
- Téléchargement de plusieurs photos par annonce

## Images d'exemple

Le projet inclut maintenant des images SVG d'exemple dans `uploads/` :

- `uploads/sample-tv.svg`
- `uploads/sample-bike.svg`
- `uploads/sample-sofa.svg`

Tu peux les utiliser dans les annonces en sélectionnant ces fichiers comme photo dans le formulaire de création d'annonce, ou bien les ouvrir directement dans le navigateur.
