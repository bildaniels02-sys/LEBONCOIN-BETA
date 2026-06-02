<?php
require_once __DIR__ . '/includes/functions.php';
requireLogin();

$ads = [];
global $mysqli;
$final = db_prepare_sql('SELECT a.*, u.email AS owner_email FROM ads a JOIN favorites f ON a.id = f.ad_id JOIN users u ON a.user_id = u.id WHERE f.user_id = :user_id ORDER BY a.created_at DESC', ['user_id' => $_SESSION['user_id']]);
$res = $mysqli->query($final);
if ($res) {
    $ads = $res->fetch_all(MYSQLI_ASSOC);
    $res->free();
}

include __DIR__ . '/templates/header.php';
?>
<section class="ads-grid">
    <h2>Mes favoris</h2>
    <?php if (empty($ads)): ?>
        <p>Vous n'avez pas encore d'annonces favorites.</p>
    <?php else: ?>
        <?php foreach ($ads as $ad): ?>
            <article class="ad-card">
                <a href="ad_detail.php?id=<?php echo $ad['id']; ?>">
                    <img src="<?php echo sanitize($ad['photo']); ?>" alt="<?php echo sanitize($ad['title']); ?>">
                </a>
                <div class="card-content">
                    <h2><?php echo sanitize($ad['title']); ?></h2>
                    <p class="price"><?php echo number_format($ad['price'], 2, ',', ' '); ?> €</p>
                    <a class="button" href="ad_detail.php?id=<?php echo $ad['id']; ?>">Voir l'annonce</a>
                </div>
            </article>
        <?php endforeach; ?>
    <?php endif; ?>
</section>
<?php include __DIR__ . '/templates/footer.php';
