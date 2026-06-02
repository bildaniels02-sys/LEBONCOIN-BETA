<?php
require_once __DIR__ . '/includes/functions.php';
requireLogin();

$id = $_GET['id'] ?? null;
if (!$id || !is_numeric($id)) redirect('conversations.php');

global $mysqli;
$final = db_prepare_sql('SELECT * FROM conversations WHERE id = :id', ['id' => $id]);
$res = $mysqli->query($final);
$conv = $res ? $res->fetch_assoc() : false;
if (!$conv) redirect('conversations.php');
if ($conv['user_one'] != $_SESSION['user_id'] && $conv['user_two'] != $_SESSION['user_id']) redirect('conversations.php');

// mark messages as read for this user
$sql = db_prepare_sql('UPDATE messages SET is_read = 1 WHERE conversation_id = :id AND sender_id != :user_id', ['id' => $id, 'user_id' => $_SESSION['user_id']]);
$mysqli->query($sql);

// fetch messages
$final = db_prepare_sql('SELECT m.*, u.email as sender_email FROM messages m JOIN users u ON m.sender_id = u.id WHERE m.conversation_id = :id ORDER BY m.created_at ASC', ['id' => $id]);
$res = $mysqli->query($final);
$messages = [];
if ($res) {
    $messages = $res->fetch_all(MYSQLI_ASSOC);
    $res->free();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $content = trim($_POST['message'] ?? '');
    if ($content !== '') {
        $sql = db_prepare_sql('INSERT INTO messages (sender_id, ad_id, conversation_id, content, is_read) VALUES (:sender_id, :ad_id, :conv_id, :content, 0)', ['sender_id' => $_SESSION['user_id'], 'ad_id' => $conv['ad_id'], 'conv_id' => $id, 'content' => $content]);
        $mysqli->query($sql);
        redirect('conversation_view.php?id=' . $id);
    }
}

include __DIR__ . '/templates/header.php';
?>
<section class="conversation">
    <h2>Conversation pour : <?php echo sanitize($conv['id']); ?> - <?php echo sanitize($conv['ad_id']); ?></h2>
    <div class="messages-thread">
        <?php foreach ($messages as $m): ?>
            <div class="message <?php echo $m['sender_id'] == $_SESSION['user_id'] ? 'out' : 'in'; ?>">
                <p class="meta"><?php echo sanitize($m['sender_email']); ?> — <?php echo sanitize($m['created_at']); ?></p>
                <p><?php echo nl2br(sanitize($m['content'])); ?></p>
            </div>
        <?php endforeach; ?>
    </div>
    <form method="post" action="conversation_view.php?id=<?php echo $id; ?>">
        <textarea name="message" required></textarea>
        <button type="submit">Envoyer</button>
    </form>
</section>
<?php include __DIR__ . '/templates/footer.php';
