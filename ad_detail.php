<?php
require_once __DIR__ . '/includes/functions.php';

$id = $_GET['id'] ?? null;
if (!$id || !is_numeric($id)) {
    redirect('index.php');
}

$stmt = $pdo->prepare('SELECT a.*, u.email AS owner_email FROM ads a JOIN users u ON a.user_id = u.id WHERE a.id = :id');
$stmt->execute([':id' => $id]);
$ad = $stmt->fetch();
if (!$ad) {
    redirect('index.php');
}

$isFavourite = false;
if (isLoggedIn()) {
    $stmt = $pdo->prepare('SELECT 1 FROM favorites WHERE user_id = :user_id AND ad_id = :ad_id');
    $stmt->execute([':user_id' => $_SESSION['user_id'], ':ad_id' => $id]);
    $isFavourite = (bool)$stmt->fetch();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isLoggedIn()) {
    if (!empty($_POST['toggle_favorite'])) {
        if ($isFavourite) {
            $stmt = $pdo->prepare('DELETE FROM favorites WHERE user_id = :user_id AND ad_id = :ad_id');
            $stmt->execute([':user_id' => $_SESSION['user_id'], ':ad_id' => $id]);
            flash('success', 'Annonce retirée de vos favoris.');
        } else {
            $stmt = $pdo->prepare('INSERT IGNORE INTO favorites (user_id, ad_id) VALUES (:user_id, :ad_id)');
            $stmt->execute([':user_id' => $_SESSION['user_id'], ':ad_id' => $id]);
            flash('success', 'Annonce ajoutée à vos favoris.');
        }
        redirect('ad_detail.php?id=' . $id);
    }
    if (!empty($_POST['send_message'])) {
        $message = trim($_POST['message'] ?? '');
        if ($message !== '') {
            $stmt = $pdo->prepare('INSERT INTO messages (sender_id, ad_id, content) VALUES (:sender_id, :ad_id, :content)');
            $stmt->execute([':sender_id' => $_SESSION['user_id'], ':ad_id' => $id, ':content' => $message]);
            flash('success', 'Message envoyé au propriétaire.');
            redirect('ad_detail.php?id=' . $id);
        } else {
            flash('error', 'Le message ne peut pas être vide.');
            redirect('ad_detail.php?id=' . $id);
        }
    }
}

include __DIR__ . '/templates/header.php';
?>
<article class="ad-detail">
    <img src="<?php echo sanitize($ad['photo']); ?>" alt="<?php echo sanitize($ad['title']); ?>">
    <div class="ad-detail-content">
        <h2><?php echo sanitize($ad['title']); ?></h2>
        <p class="price"><?php echo number_format($ad['price'], 2, ',', ' '); ?> €</p>
        <p><?php echo nl2br(sanitize($ad['description'])); ?></p>
        <p><strong>Catégorie:</strong> <?php echo sanitize($ad['category']); ?></p>
        <p><strong>Publié par:</strong> <?php echo sanitize($ad['owner_email']); ?></p>
        <p><strong>Créée le:</strong> <?php echo sanitize($ad['created_at']); ?></p>
        <?php if (isLoggedIn()): ?>
            <form method="post" action="ad_detail.php?id=<?php echo $id; ?>">
                <button type="submit" name="toggle_favorite"><?php echo $isFavourite ? 'Retirer des favoris' : 'Ajouter aux favoris'; ?></button>
            </form>
            <?php if ($ad['user_id'] === $_SESSION['user_id']): ?>
                <p class="owner-actions">
                    <a class="button button-secondary" href="ad_edit.php?id=<?php echo $id; ?>">Modifier</a>
                    <a class="button button-danger" href="ad_delete.php?id=<?php echo $id; ?>">Supprimer</a>
                </p>
            <?php endif; ?>
            <section class="message-form">
                <h3>Envoyer un message</h3>
                <form method="post" action="ad_detail.php?id=<?php echo $id; ?>">
                    <textarea name="message" required></textarea>
                    <button type="submit" name="send_message">Envoyer</button>
                </form>
            </section>
        <?php else: ?>
            <p>Veuillez vous connecter pour ajouter aux favoris ou envoyer un message.</p>
        <?php endif; ?>
    </div>
</article>
<?php include __DIR__ . '/templates/footer.php';
