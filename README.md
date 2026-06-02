# Le Bon Coin Beta — Documentation complète

Ce dépôt contient une application web PHP minimale permettant de publier, modifier et consulter des petites annonces. Le but est pédagogique : implémenter les fonctionnalités principales (authentification, CRUD d'annonces, favoris, messagerie) avec une base MySQL et un rendu HTML/CSS simple.

## Table des matières

- Présentation
- Installation rapide (MAMP)
- Base de données
- Structure du projet (fichiers importants)
- Flux principaux et exemples de requêtes
- Uploads et gestion des images
- Sécurité et bonnes pratiques
- Dépannage
- Améliorations possibles

## Présentation

Fonctionnalités implémentées :

- Inscription et connexion avec mot de passe haché
- Création, modification, suppression d'annonces (photo incluse)
- Liste publique d'annonces avec filtres (recherche, catégorie, prix)
- Page de détail d'annonce, ajout/retrait aux favoris
- Envoi et consultation de messages liés aux annonces

## Installation rapide (MAMP)

1. Copier le dossier `PROJ ANNONCE` dans le répertoire de MAMP `htdocs`.
2. Démarrer Apache et MySQL via l'interface MAMP.
3. Importer la base : ouvrir phpMyAdmin ou exécuter dans un terminal MySQL :

```sql
SOURCE /chemin/vers/PROJ ANNONCE/sql/init.sql;
```

4. Vérifier et, si besoin, adapter les informations de connexion dans `includes/config.php` :

- `$dbHost` (ex : `127.0.0.1`)
- `$dbName` (par défaut `projet_annonce`)
- `$dbUser` (par défaut `root`)
- `$dbPass` (par défaut `root` pour MAMP, ou vide selon ta config)

5. Ouvrir dans le navigateur : `http://localhost/PROJ%20ANNONCE/`

## Base de données

Le script `sql/init.sql` crée les tables suivantes :

- `users` (id, email, password_hash, created_at)
- `ads` (id, user_id, title, price, description, photo, category, created_at, updated_at)
- `favorites` (user_id, ad_id)
- `messages` (id, sender_id, ad_id, content, created_at)

Contraintes importantes : les relations utilisent `ON DELETE CASCADE` pour nettoyer les dépendances.

## Structure du projet (fichiers clés)

- `includes/config.php` : connexion MySQLi et helpers `db_prepare_sql()` (utilisé pour échapper les valeurs). La connexion disponible globalement via `$mysqli`.
- `includes/functions.php` : fonctions utilitaires (flash, uploadPhoto, sanitize, catégories).
- `templates/header.php` / `templates/footer.php` : layout commun.
- `index.php` : affichage et filtrage des annonces.
- `register.php`, `login.php`, `logout.php` : gestion des comptes.
- `ad_create.php`, `ad_edit.php`, `ad_delete.php` : CRUD annonces.
- `ad_detail.php` : fiche d'une annonce, envoi de message, favoris.
- `favorites.php`, `messages.php`, `account.php` : vues utilisateur.

## Flux principaux et exemples (explications)

- Authentification : `register.php` crée un `password_hash()` stocké dans `users.password_hash`. `login.php` vérifie avec `password_verify()` et sauvegarde `$_SESSION['user_id']` et `$_SESSION['user']`.

- Requêtes SQL : le code utilise `db_prepare_sql($sql, $params)` (dans `includes/config.php`) pour substituer des paramètres nommés `:name` par des valeurs échappées via `mysqli::real_escape_string`. Ensuite les requêtes sont exécutées par `$mysqli->query($final)` et les résultats récupérés par `$res->fetch_assoc()` ou `$res->fetch_all(MYSQLI_ASSOC)`.

  Exemple d'utilisation (lecture) :

```php
global $mysqli;
$final = db_prepare_sql('SELECT * FROM ads WHERE id = :id', ['id' => $id]);
$res = $mysqli->query($final);
$ad = $res ? $res->fetch_assoc() : false;
```

  Exemple d'insertion (création d'annonce) :

```php
$sql = db_prepare_sql('INSERT INTO ads (user_id, title, price, description, photo, category) VALUES (:user_id, :title, :price, :description, :photo, :category)', $params);
$mysqli->query($sql);
```

> Remarque : pour une sécurité maximale, il est recommandé d'utiliser `mysqli_prepare()` + `bind_param()` pour lier les valeurs au lieu de construire des requêtes en chaîne. Si tu veux, je convertis les requêtes sensibles (`register`, `login`, `create/update ads`) en `mysqli_prepare`.

## Uploads et gestion des images

- Le dossier `uploads/` contient les images téléchargées et quelques SVG d'exemple (`sample-tv.svg`, `sample-bike.svg`, `sample-sofa.svg`).
- La fonction `uploadPhoto()` (dans `includes/functions.php`) effectue plusieurs vérifications :
  - présence du fichier et code d'erreur d'upload
  - taille maximale (5 Mo)
  - détection du type via `getimagesize()` ou `finfo_file()` pour `image/svg+xml`
  - accepte les extensions standards `jpg, jpeg, png, gif, bmp, webp, tif, tiff, svg, ico`
  - renomme le fichier avec `uniqid()` et le déplace dans `uploads/`

Assure-toi que le dossier `uploads/` est accessible en écriture par le serveur web.

### Images d'exemple — explications détaillées

Les images d'exemple sont fournies dans `uploads/` pour accélérer les tests et les démonstrations. Voici comment les utiliser selon différents besoins :

- Visualiser une image dans le navigateur :

  Ouvre : `http://localhost/PROJ%20ANNONCE/uploads/sample-tv.svg` (ou remplace `sample-tv.svg` par `sample-bike.svg` / `sample-sofa.svg`).

- Utiliser une image d'exemple pour une annonce :

  - Méthode A — Upload via le formulaire (recommandée) :
    1. Ouvre l'image d'exemple dans le navigateur (voir ci‑dessus).
    2. Clic droit > Enregistrer sous... pour télécharger l'image sur ton poste.
    3. Dans `Créer une annonce`, utilise le champ `Photo` pour uploader le fichier téléchargé.

  - Méthode B — Référencer l'image serveur directement (sans upload) :
    1. Crée l'annonce (ou récupère son `id`).
    2. Dans phpMyAdmin ou via un client MySQL, mets à jour la colonne `photo` de la table `ads` pour pointer sur `uploads/sample-tv.svg` :

```sql
UPDATE ads SET photo = 'uploads/sample-tv.svg' WHERE id = 1;
```

    3. Recharge la page de détail de l'annonce ; l'image référencée côté serveur sera affichée.

  - Remarque : l'input `file` de HTML ne peut pas sélectionner un fichier qui est déjà sur le serveur — c'est pour cela que la méthode A (ré-uploader) est pratique pour une démonstration utilisateur.

- Ajouter d'autres images d'exemple :

  - Copie simplement tes fichiers dans le dossier `uploads/`.
  - Respecte la limite de taille (5 Mo) et les formats supportés : `jpg, jpeg, png, gif, bmp, webp, tif, tiff, svg, ico`.
  - Vérifie les permissions si l'image n'apparaît pas.

- Alternatives visibles lors de la soutenance :

  - Je peux ajouter un bouton "Utiliser une image d'exemple" dans le formulaire `ad_create.php` qui propose les images présentes dans `uploads/` et écrira automatiquement le chemin sélectionné dans le champ `photo` sans upload.
  - Je peux aussi ajouter une galerie d'images internes pour choisir directement depuis l'interface.

## Sécurité et bonnes pratiques

- Mots de passe : utilisation de `password_hash()` et `password_verify()`.
- Échappement : `db_prepare_sql()` utilise `mysqli::real_escape_string()` pour éviter les injections sur les valeurs. Toutefois, les requêtes construites restent moins sûres que des requêtes préparées paramétrées. Pour les opérations critiques, privilégier `mysqli_prepare` + `bind_param`.
- CSRF : les formulaires ne possèdent pas de jetons CSRF dans cette version. Ajouter un token pour protéger les actions POST.
- Validation côté serveur : toutes les entrées sont validées sommairement (présence, types numériques). Tu peux renforcer avec des filtres supplémentaires.
- Uploads : la validation d'image vérifie le type réel et la taille. Pour plus de sécurité, nettoyer/convertir les images uploadées côté serveur.

## Dépannage rapide

- Erreur de connexion MySQL : vérifier `includes/config.php` et les identifiants MAMP. Par défaut MAMP utilise `root`/`root`.
- Droit d'écriture pour `uploads/` : donner les permissions nécessaires au serveur web.
- PHP non trouvé dans le terminal Windows : utiliser le binaire PHP de MAMP (ex : `C:\MAMP\bin\php\php8.3.1\php.exe -l fichier.php`) pour vérifier la syntaxe.

## Améliorations possibles (prioritaires)

- Convertir les requêtes sensibles en `mysqli_prepare` + `bind_param` (sécurité renforcée).
- Ajouter CSRF tokens pour tous les formulaires POST.
- Pagination et tri pour la liste d'annonces.
- Multi-uploads (plusieurs photos par annonce) et stockage structuré.
- Tests unitaires simples pour valider les fonctions utilitaires.

## Si tu veux que j'aille plus loin

- Je peux convertir `register`, `login`, `ad_create`, `ad_edit` en requêtes préparées `mysqli_prepare`.
- Je peux ajouter CSRF tokens et un petit guide pour la soutenance (slides / script de démonstration).

---

Si tu veux que j'ajoute l'un des points ci‑dessus directement (p.ex. préparer/liaison MySQLi), dis-moi lesquels et je les implémente.
