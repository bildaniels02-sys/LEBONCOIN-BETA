<?php
require_once __DIR__ . '/../includes/config.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Le Bon Coin Beta</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<header class="site-header">
    <div class="container">
        <h1><a href="index.php">Le Bon Coin Beta</a></h1>
        <nav>
            <a href="index.php">Annonces</a>
            <?php if (isLoggedIn()): ?>
                <a href="ad_create.php">Créer une annonce</a>
                <a href="favorites.php">Favoris</a>
                <a href="messages.php">Messages</a>
                <a href="account.php">Mon compte</a>
                <a href="logout.php">Déconnexion</a>
            <?php else: ?>
                <a href="login.php">Connexion</a>
                <a href="register.php">Inscription</a>
            <?php endif; ?>
        </nav>
    </div>
</header>
<main class="container">
<?php if ($error = flash('error')): ?>
    <div class="alert alert-error"><?php echo sanitize($error); ?></div>
<?php endif; ?>
<?php if ($success = flash('success')): ?>
    <div class="alert alert-success"><?php echo sanitize($success); ?></div>
<?php endif; ?>
