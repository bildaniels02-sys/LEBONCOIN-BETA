<?php
require_once __DIR__ . '/includes/functions.php';
requireLogin();

$id = $_GET['id'] ?? null;
if (!$id || !is_numeric($id)) {
    redirect('index.php');
}

$stmt = $pdo->prepare('SELECT * FROM ads WHERE id = :id AND user_id = :user_id');
$stmt->execute([':id' => $id, ':user_id' => $_SESSION['user_id']]);
$ad = $stmt->fetch();
if (!$ad) {
    redirect('index.php');
}

$errors = [];
$title = $ad['title'];
$price = $ad['price'];
$description = $ad['description'];
$category = $ad['category'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $price = trim($_POST['price'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $category = trim($_POST['category'] ?? 'Autres');
    $photo = $_FILES['photo'] ?? null;

    if ($title === '') {
        $errors[] = 'Le titre est requis.';
    }
    if ($price === '' || !is_numeric($price) || $price < 0) {
        $errors[] = 'Le prix est invalide.';
    }
    if ($description === '') {
        $errors[] = 'La description est requise.';
    }

    if (empty($errors)) {
        $photoPath = $ad['photo'];
        if (!empty($photo['name'])) {
            $newPhoto = uploadPhoto($photo, $photoError);
            if ($newPhoto) {
                $photoPath = $newPhoto;
            } else {
                $errors[] = $photoError ?: 'La photo doit être un fichier image valide.';
            }
        }
    }
    if (empty($errors)) {
        $stmt = $pdo->prepare('UPDATE ads SET title = :title, price = :price, description = :description, category = :category, photo = :photo WHERE id = :id');
        $stmt->execute([
            ':title' => $title,
            ':price' => $price,
            ':description' => $description,
            ':category' => $category,
            ':photo' => $photoPath,
            ':id' => $id,
        ]);
        flash('success', 'Annonce modifiée avec succès.');
        redirect('ad_detail.php?id=' . $id);
    }
}

$categories = getCategories();
include __DIR__ . '/templates/header.php';
?>
<section class="form-page">
    <h2>Modifier l'annonce</h2>
    <?php if ($errors): ?>
        <div class="alert alert-error"><ul><?php foreach ($errors as $error): ?><li><?php echo sanitize($error); ?></li><?php endforeach; ?></ul></div>
    <?php endif; ?>
    <form method="post" action="ad_edit.php?id=<?php echo $id; ?>" enctype="multipart/form-data">
        <label>Titre</label>
        <input type="text" name="title" required value="<?php echo sanitize($title); ?>">
        <label>Prix (€)</label>
        <input type="number" step="0.01" name="price" required value="<?php echo sanitize($price); ?>">
        <label>Description</label>
        <textarea name="description" required><?php echo sanitize($description); ?></textarea>
        <label>Catégorie</label>
        <select name="category">
            <?php foreach ($categories as $cat): ?>
                <option value="<?php echo sanitize($cat); ?>" <?php echo $category === $cat ? 'selected' : ''; ?>><?php echo sanitize($cat); ?></option>
            <?php endforeach; ?>
        </select>
        <p>Photo actuelle:</p>
        <img class="edit-photo" src="<?php echo sanitize($ad['photo']); ?>" alt="Photo actuelle">
        <label>Remplacer la photo</label>
        <input type="file" name="photo" accept="image/*">
        <button type="submit">Enregistrer</button>
        <a class="button button-secondary" href="ad_detail.php?id=<?php echo $id; ?>">Annuler</a>
    </form>
</section>
<?php include __DIR__ . '/templates/footer.php';
