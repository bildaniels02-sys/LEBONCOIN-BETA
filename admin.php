<?php
require_once __DIR__ . '/includes/functions.php';
requireLogin();

if (empty($_SESSION['user']['is_admin'])) {
    flash('error', 'Accès refusé.');
    redirect('index.php');
}

global $mysqli;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'delete_ad' && isset($_POST['ad_id']) && is_numeric($_POST['ad_id'])) {
        // soft-delete: set deleted_at
        $sql = db_prepare_sql('UPDATE ads SET deleted_at = NOW() WHERE id = :id', ['id' => (int)$_POST['ad_id']]);
        $mysqli->query($sql);
        flash('success', 'Annonce marquée comme supprimée.');
        redirect('admin.php');
    }
    if ($action === 'restore_ad' && isset($_POST['ad_id']) && is_numeric($_POST['ad_id'])) {
        $sql = db_prepare_sql('UPDATE ads SET deleted_at = NULL WHERE id = :id', ['id' => (int)$_POST['ad_id']]);
        $mysqli->query($sql);
        flash('success', 'Annonce restaurée.');
        redirect('admin.php');
    }
    if ($action === 'purge_ad' && isset($_POST['ad_id']) && is_numeric($_POST['ad_id'])) {
        $sql = db_prepare_sql('DELETE FROM ads WHERE id = :id', ['id' => (int)$_POST['ad_id']]);
        $mysqli->query($sql);
        flash('success', 'Annonce supprimée définitivement.');
        redirect('admin.php');
    }
    if ($action === 'promote_user' && isset($_POST['user_id']) && is_numeric($_POST['user_id'])) {
        $uid = (int)$_POST['user_id'];
        $sql = db_prepare_sql('UPDATE users SET is_admin = 1 WHERE id = :id', ['id' => $uid]);
        $mysqli->query($sql);
        flash('success', 'Utilisateur promu administrateur.');
        redirect('admin.php');
    }
    if ($action === 'dismiss_report' && isset($_POST['report_id']) && is_numeric($_POST['report_id'])) {
        $rid = (int)$_POST['report_id'];
        $sql = db_prepare_sql('UPDATE reports SET status = "dismissed" WHERE id = :id', ['id' => $rid]);
        $mysqli->query($sql);
        flash('success', 'Signalement ignoré.');
        redirect('admin.php');
    }
    if ($action === 'resolve_report' && isset($_POST['report_id']) && is_numeric($_POST['report_id'])) {
        $rid = (int)$_POST['report_id'];
        $sql = db_prepare_sql('UPDATE reports SET status = "resolved" WHERE id = :id', ['id' => $rid]);
        $mysqli->query($sql);
        flash('success', 'Signalement marqué comme résolu.');
        redirect('admin.php');
    }
    if ($action === 'remove_ad_from_report' && isset($_POST['report_id']) && isset($_POST['ad_id']) && is_numeric($_POST['ad_id'])) {
        $adid = (int)$_POST['ad_id'];
        // soft-delete the ad
        $sql = db_prepare_sql('UPDATE ads SET deleted_at = NOW() WHERE id = :id', ['id' => $adid]);
        $mysqli->query($sql);
        // mark report resolved
        $rid = (int)$_POST['report_id'];
        $sql = db_prepare_sql('UPDATE reports SET status = "resolved" WHERE id = :id', ['id' => $rid]);
        $mysqli->query($sql);
        flash('success', 'Annonce supprimée et signalement résolu.');
        redirect('admin.php');
    }
    if ($action === 'demote_user' && isset($_POST['user_id']) && is_numeric($_POST['user_id'])) {
        $uid = (int)$_POST['user_id'];
        if ($uid === $_SESSION['user_id']) {
            flash('error', 'Vous ne pouvez pas vous rétrograder vous-même.');
            redirect('admin.php');
        }
        $sql = db_prepare_sql('UPDATE users SET is_admin = 0 WHERE id = :id', ['id' => $uid]);
        $mysqli->query($sql);
        flash('success', 'Utilisateur rétrogradé.');
        redirect('admin.php');
    }
}

$res = $mysqli->query("SELECT a.id, a.title, a.created_at, a.deleted_at, u.email AS owner_email FROM ads a JOIN users u ON a.user_id = u.id ORDER BY a.created_at DESC");
$ads = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];

// fetch users
$res = $mysqli->query("SELECT id, email, created_at, COALESCE(is_admin,0) AS is_admin FROM users ORDER BY created_at DESC");
$users = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];

// fetch reports
$res = $mysqli->query("SELECT r.id, r.ad_id, r.reporter_id, r.reason, r.comment, r.status, r.created_at, a.title AS ad_title, u.email AS reporter_email FROM reports r LEFT JOIN ads a ON r.ad_id = a.id LEFT JOIN users u ON r.reporter_id = u.id ORDER BY r.created_at DESC");
$reports = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];

include __DIR__ . '/templates/header.php';
?>
<section class="admin">
    <h2>Administration</h2>

    <h3>Gestion des annonces</h3>
    <?php if (empty($ads)): ?>
        <p>Aucune annonce.</p>
    <?php else: ?>
        <table class="admin-table">
            <thead><tr><th>ID</th><th>Titre</th><th>Propriétaire</th><th>Créée</th><th>Actions</th></tr></thead>
            <tbody>
                <?php foreach ($ads as $a): ?>
                    <tr>
                        <td><?php echo (int)$a['id']; ?></td>
                        <td><?php echo sanitize($a['title']); ?><?php if (!empty($a['deleted_at'])): ?> <em>(supprimée)</em><?php endif; ?></td>
                        <td><?php echo sanitize($a['owner_email']); ?></td>
                        <td><?php echo sanitize($a['created_at']); ?></td>
                        <td>
                            <?php if (empty($a['deleted_at'])): ?>
                            <form method="post" style="display:inline">
                                <input type="hidden" name="ad_id" value="<?php echo (int)$a['id']; ?>">
                                <input type="hidden" name="action" value="delete_ad">
                                <button type="submit" onclick="return confirm('Marquer cette annonce comme supprimée ?')">Supprimer (logique)</button>
                            </form>
                            <form method="post" style="display:inline">
                                <input type="hidden" name="ad_id" value="<?php echo (int)$a['id']; ?>">
                                <input type="hidden" name="action" value="purge_ad">
                                <button type="submit" onclick="return confirm('Supprimer définitivement cette annonce ? Attention, irréversible.')">Supprimer définitivement</button>
                            </form>
                            <?php else: ?>
                            <form method="post" style="display:inline">
                                <input type="hidden" name="ad_id" value="<?php echo (int)$a['id']; ?>">
                                <input type="hidden" name="action" value="restore_ad">
                                <button type="submit">Restaurer</button>
                            </form>
                            <form method="post" style="display:inline">
                                <input type="hidden" name="ad_id" value="<?php echo (int)$a['id']; ?>">
                                <input type="hidden" name="action" value="purge_ad">
                                <button type="submit" onclick="return confirm('Supprimer définitivement cette annonce ?')">Purger</button>
                            </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <h3>Gestion des utilisateurs</h3>
    <?php if (empty($users)): ?>
        <p>Aucun utilisateur trouvé.</p>
    <?php else: ?>
        <table class="admin-table">
            <thead><tr><th>ID</th><th>Email</th><th>Admin</th><th>Inscrit</th><th>Actions</th></tr></thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                    <tr>
                        <td><?php echo (int)$u['id']; ?></td>
                        <td><?php echo sanitize($u['email']); ?></td>
                        <td><?php echo $u['is_admin'] ? 'Oui' : 'Non'; ?></td>
                        <td><?php echo sanitize($u['created_at']); ?></td>
                        <td>
                            <?php if (!$u['is_admin']): ?>
                                <form method="post" style="display:inline">
                                    <input type="hidden" name="user_id" value="<?php echo (int)$u['id']; ?>">
                                    <input type="hidden" name="action" value="promote_user">
                                    <button type="submit">Promouvoir</button>
                                </form>
                            <?php else: ?>
                                <?php if ($u['id'] != $_SESSION['user_id']): ?>
                                <form method="post" style="display:inline">
                                    <input type="hidden" name="user_id" value="<?php echo (int)$u['id']; ?>">
                                    <input type="hidden" name="action" value="demote_user">
                                    <button type="submit" onclick="return confirm('Rétrograder cet administrateur ?')">Rétrograder</button>
                                </form>
                                <?php endif; ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <h3>Signalements</h3>
    <?php if (empty($reports)): ?>
        <p>Aucun signalement.</p>
    <?php else: ?>
        <table class="admin-table">
            <thead><tr><th>ID</th><th>Annonce</th><th>Signaleur</th><th>Motif</th><th>Commentaire</th><th>Créée</th><th>Statut</th><th>Actions</th></tr></thead>
            <tbody>
                <?php foreach ($reports as $r): ?>
                    <tr>
                        <td><?php echo (int)$r['id']; ?></td>
                        <td><?php echo sanitize($r['ad_title']) ?: ('#' . (int)$r['ad_id']); ?></td>
                        <td><?php echo sanitize($r['reporter_email'] ?: 'Anonyme'); ?></td>
                        <td><?php echo sanitize($r['reason']); ?></td>
                        <td><?php echo sanitize($r['comment']); ?></td>
                        <td><?php echo sanitize($r['created_at']); ?></td>
                        <td><?php echo sanitize($r['status']); ?></td>
                        <td>
                            <form method="post" style="display:inline">
                                <input type="hidden" name="report_id" value="<?php echo (int)$r['id']; ?>">
                                <input type="hidden" name="action" value="dismiss_report">
                                <button type="submit">Ignorer</button>
                            </form>
                            <form method="post" style="display:inline">
                                <input type="hidden" name="report_id" value="<?php echo (int)$r['id']; ?>">
                                <input type="hidden" name="action" value="resolve_report">
                                <button type="submit">Résolu</button>
                            </form>
                            <form method="post" style="display:inline">
                                <input type="hidden" name="report_id" value="<?php echo (int)$r['id']; ?>">
                                <input type="hidden" name="action" value="remove_ad_from_report">
                                <input type="hidden" name="ad_id" value="<?php echo (int)$r['ad_id']; ?>">
                                <button type="submit" onclick="return confirm('Marquer l\'annonce comme supprimée ?')">Supprimer annonce</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</section>

<?php include __DIR__ . '/templates/footer.php';
