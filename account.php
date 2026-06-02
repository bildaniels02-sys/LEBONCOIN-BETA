<?php
require_once __DIR__ . '/includes/functions.php';
requireLogin();

include __DIR__ . '/templates/header.php';
?>
<section class="account-page">
    <h2>Mon compte</h2>
    <p><strong>Email :</strong> <?php echo sanitize($_SESSION['user']['email']); ?></p>
    <p><strong>ID :</strong> <?php echo sanitize($_SESSION['user']['id']); ?></p>
    <p><a class="button" href="ad_create.php">Créer une nouvelle annonce</a></p>
</section>
<?php include __DIR__ . '/templates/footer.php';
