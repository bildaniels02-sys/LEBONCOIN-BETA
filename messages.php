<?php
require_once __DIR__ . '/includes/functions.php';
requireLogin();

$messages = [];
global $mysqli;
$sql = 'SELECT m.*, a.title, u.email AS sender_email
    FROM messages m
    JOIN ads a ON m.ad_id = a.id
    JOIN users u ON m.sender_id = u.id
    WHERE m.sender_id = :user_id OR a.user_id = :user_id
    ORDER BY m.created_at DESC';
$final = db_prepare_sql($sql, ['user_id' => $_SESSION['user_id']]);
$res = $mysqli->query($final);
if ($res) {
    $messages = $res->fetch_all(MYSQLI_ASSOC);
    $res->free();
}

include __DIR__ . '/templates/header.php';
?>
<section class="messages-list">
    <h2>Mes messages</h2>
    <?php if (empty($messages)): ?>
        <p>Vous n'avez aucun message pour le moment.</p>
    <?php else: ?>
        <?php foreach ($messages as $message): ?>
            <article class="message-card">
                <p><strong>Annonce :</strong> <a href="ad_detail.php?id=<?php echo sanitize($message['ad_id']); ?>"><?php echo sanitize($message['title']); ?></a></p>
                <p><strong>Expéditeur :</strong> <?php echo sanitize($message['sender_email']); ?></p>
                <p><?php echo nl2br(sanitize($message['content'])); ?></p>
                <p class="meta"><?php echo sanitize($message['created_at']); ?></p>
            </article>
        <?php endforeach; ?>
    <?php endif; ?>
</section>
<?php include __DIR__ . '/templates/footer.php';
