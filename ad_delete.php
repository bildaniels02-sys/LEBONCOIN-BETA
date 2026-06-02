<?php
require_once __DIR__ . '/includes/functions.php';
requireLogin();

$id = $_GET['id'] ?? null;
if (!$id || !is_numeric($id)) {
    redirect('index.php');
}

$ad = null;
global $mysqli;
$final = db_prepare_sql('SELECT * FROM ads WHERE id = :id AND user_id = :user_id', ['id' => $id, 'user_id' => $_SESSION['user_id']]);
$res = $mysqli->query($final);
$ad = $res ? $res->fetch_assoc() : false;
if (!$ad) {
    redirect('index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($_POST['confirm']) && $_POST['confirm'] === 'yes') {
        $sql = db_prepare_sql('DELETE FROM ads WHERE id = :id', ['id' => $id]);
        $mysqli->query($sql);
        flash('success', 'Annonce supprimée.');
        redirect('index.php');
    }
    redirect('ad_detail.php?id=' . $id);
}

include __DIR__ . '/templates/header.php';
?>
<section class="form-page">
    <h2>Supprimer l'annonce</h2>
    <p>Voulez-vous vraiment supprimer cette annonce ? Cette action est irréversible.</p>
    <form method="post" action="ad_delete.php?id=<?php echo $id; ?>">
        <button type="submit" name="confirm" value="yes" class="button button-danger">Oui, supprimer</button>
        <a class="button button-secondary" href="ad_detail.php?id=<?php echo $id; ?>">Non, annuler</a>
    </form>
</section>
<?php include __DIR__ . '/templates/footer.php';
