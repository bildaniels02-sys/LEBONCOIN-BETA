<?php
require_once __DIR__ . '/includes/functions.php';

$id = $_GET['id'] ?? null;
if (!$id || !is_numeric($id)) {
    redirect('index.php');
}

    global $mysqli;
    $final = db_prepare_sql('SELECT a.*, u.email AS owner_email FROM ads a JOIN users u ON a.user_id = u.id WHERE a.id = :id', ['id' => $id]);
    $res = $mysqli->query($final);
    $ad = $res ? $res->fetch_assoc() : false;
if (!$ad) {
    redirect('index.php');
}

$isFavourite = false;
if (isLoggedIn()) {
    $finalFav = db_prepare_sql('SELECT 1 FROM favorites WHERE user_id = :user_id AND ad_id = :ad_id', ['user_id' => $_SESSION['user_id'], 'ad_id' => $id]);
    $rf = $mysqli->query($finalFav);
    $fav = $rf ? $rf->fetch_assoc() : false;
    $isFavourite = (bool)$fav;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isLoggedIn()) {
    if (!empty($_POST['toggle_favorite'])) {
        if ($isFavourite) {
            $sql = db_prepare_sql('DELETE FROM favorites WHERE user_id = :user_id AND ad_id = :ad_id', ['user_id' => $_SESSION['user_id'], 'ad_id' => $id]);
            $mysqli->query($sql);
            flash('success', 'Annonce retirée de vos favoris.');
        } else {
            $sql = db_prepare_sql('INSERT IGNORE INTO favorites (user_id, ad_id) VALUES (:user_id, :ad_id)', ['user_id' => $_SESSION['user_id'], 'ad_id' => $id]);
            $mysqli->query($sql);
            flash('success', 'Annonce ajoutée à vos favoris.');
        }
        redirect('ad_detail.php?id=' . $id);
    }
    if (!empty($_POST['send_message'])) {
        $message = trim($_POST['message'] ?? '');
        if ($message !== '') {
            $sql = db_prepare_sql('INSERT INTO messages (sender_id, ad_id, content) VALUES (:sender_id, :ad_id, :content)', ['sender_id' => $_SESSION['user_id'], 'ad_id' => $id, 'content' => $message]);
            $mysqli->query($sql);
            flash('success', 'Message envoyé au propriétaire.');
            redirect('ad_detail.php?id=' . $id);
        } else {
            flash('error', 'Le message ne peut pas être vide.');
            redirect('ad_detail.php?id=' . $id);
        }
    }
    if (!empty($_POST['report_ad'])) {
        $reason = trim($_POST['reason'] ?? '');
        $comment = trim($_POST['report_comment'] ?? '');
        $reporter_id = isLoggedIn() ? $_SESSION['user_id'] : null;
        $sql = db_prepare_sql('INSERT INTO reports (ad_id, reporter_id, reason, comment) VALUES (:ad_id, :reporter_id, :reason, :comment)', ['ad_id' => $id, 'reporter_id' => $reporter_id, 'reason' => $reason, 'comment' => $comment]);
        $mysqli->query($sql);
        flash('success', 'Signalement envoyé. Merci de votre vigilance.');
        redirect('ad_detail.php?id=' . $id);
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
            <section class="report-form">
                <h3>Signaler cette annonce</h3>
                <form method="post" action="ad_detail.php?id=<?php echo $id; ?>">
                    <label for="reason">Motif</label>
                    <select name="reason" id="reason">
                        <option value="spam">Spam / Annonce répétée</option>
                        <option value="inappropriate">Contenu inapproprié</option>
                        <option value="fraud">Escroquerie / Fraude</option>
                        <option value="other">Autre</option>
                    </select>
                    <label for="report_comment">Commentaire (optionnel)</label>
                    <textarea name="report_comment" id="report_comment"></textarea>
                    <button type="submit" name="report_ad">Signaler</button>
                </form>
            </section>
        <?php else: ?>
            <p>Veuillez vous connecter pour ajouter aux favoris ou envoyer un message.</p>
        <?php endif; ?>
    </div>
</article>
<?php include __DIR__ . '/templates/footer.php';
