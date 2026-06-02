<?php
require_once __DIR__ . '/includes/functions.php';
requireLogin();

global $mysqli;
// list conversations for current user
$sql = "SELECT c.*, a.title, 
           (SELECT m.content FROM messages m WHERE m.conversation_id = c.id ORDER BY m.created_at DESC LIMIT 1) AS last_message,
           (SELECT COUNT(*) FROM messages m WHERE m.conversation_id = c.id AND m.is_read = 0 AND m.sender_id != :user_id) AS unread_count
        FROM conversations c
        JOIN ads a ON c.ad_id = a.id
        WHERE c.user_one = :user_id OR c.user_two = :user_id
        ORDER BY c.created_at DESC";
$final = db_prepare_sql($sql, ['user_id' => $_SESSION['user_id']]);
$res = $mysqli->query($final);
$conversations = [];
if ($res) {
    $conversations = $res->fetch_all(MYSQLI_ASSOC);
    $res->free();
}

include __DIR__ . '/templates/header.php';
?>
<section>
    <h2>Mes conversations</h2>
    <?php if (empty($conversations)): ?>
        <p>Aucune conversation pour le moment.</p>
    <?php else: ?>
        <ul class="conversations-list">
            <?php foreach ($conversations as $c): ?>
                <li>
                    <a href="conversation_view.php?id=<?php echo $c['id']; ?>">
                        <strong><?php echo sanitize($c['title']); ?></strong>
                        <p><?php echo sanitize($c['last_message']); ?></p>
                        <?php if ($c['unread_count'] > 0): ?>
                            <span class="badge"><?php echo (int)$c['unread_count']; ?></span>
                        <?php endif; ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</section>
<?php include __DIR__ . '/templates/footer.php';
