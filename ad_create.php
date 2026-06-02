<?php
require_once __DIR__ . '/includes/functions.php';
requireLogin();

$errors = [];
$title = '';
$price = '';
$description = '';
$category = '';

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
    if (empty($photo) || empty($photo['name'])) {
        $errors[] = 'La photo est requise.';
    }

    if (empty($errors)) {
        $photoPath = uploadPhoto($photo, $photoError);
        if (!$photoPath) {
            $errors[] = $photoError ?: 'La photo doit être un fichier image valide.';
        } else {
            $stmt = $pdo->prepare('INSERT INTO ads (user_id, title, price, description, photo, category) VALUES (:user_id, :title, :price, :description, :photo, :category)');
            $stmt->execute([
                ':user_id' => $_SESSION['user_id'],
                ':title' => $title,
                ':price' => $price,
                ':description' => $description,
                ':photo' => $photoPath,
                ':category' => $category,
            ]);
            flash('success', 'Annonce créée avec succès.');
            redirect('index.php');
        }
    }
}

$categories = getCategories();
include __DIR__ . '/templates/header.php';
?>
<section class="form-page">
    <h2>Créer une annonce</h2>
    <?php if ($errors): ?>
        <div class="alert alert-error"><ul><?php foreach ($errors as $error): ?><li><?php echo sanitize($error); ?></li><?php endforeach; ?></ul></div>
    <?php endif; ?>
    <form method="post" action="ad_create.php" enctype="multipart/form-data">
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
        <label>Photo</label>
        <input type="file" name="photo" accept="image/*" required>
        <button type="submit">Publier l'annonce</button>
    </form>
</section>
<?php include __DIR__ . '/templates/footer.php';
