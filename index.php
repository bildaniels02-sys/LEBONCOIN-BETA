<?php
require_once __DIR__ . '/includes/functions.php';

$filters = [
    'category' => $_GET['category'] ?? '',
    'min_price' => $_GET['min_price'] ?? '',
    'max_price' => $_GET['max_price'] ?? '',
    'search' => $_GET['search'] ?? '',
];

$sql = 'SELECT a.*, u.email AS owner_email FROM ads a JOIN users u ON a.user_id = u.id WHERE 1=1';
$params = [];

if ($filters['category']) {
    $sql .= ' AND category = :category';
    $params[':category'] = $filters['category'];
}
if ($filters['min_price'] !== '') {
    $sql .= ' AND price >= :min_price';
    $params[':min_price'] = $filters['min_price'];
}
if ($filters['max_price'] !== '') {
    $sql .= ' AND price <= :max_price';
    $params[':max_price'] = $filters['max_price'];
}
if ($filters['search']) {
    $sql .= ' AND (title LIKE :search OR description LIKE :search)';
    $params[':search'] = '%' . $filters['search'] . '%';
}
$sql .= ' ORDER BY created_at DESC';

$ads = [];
if (!empty($sql)) {
    global $mysqli;
    $final = db_prepare_sql($sql, $params);
    $res = $mysqli->query($final);
    if ($res) {
        $ads = $res->fetch_all(MYSQLI_ASSOC);
        $res->free();
    }
}
$categories = getCategories();
include __DIR__ . '/templates/header.php';
?>
<section class="filters">
    <form method="get" action="index.php">
        <input type="text" name="search" placeholder="Rechercher" value="<?php echo sanitize($filters['search']); ?>">
        <select name="category">
            <option value="">Toutes catégories</option>
            <?php foreach ($categories as $category): ?>
                <option value="<?php echo sanitize($category); ?>" <?php echo $filters['category'] === $category ? 'selected' : ''; ?>><?php echo sanitize($category); ?></option>
            <?php endforeach; ?>
        </select>
        <input type="number" step="0.01" name="min_price" placeholder="Prix min" value="<?php echo sanitize($filters['min_price']); ?>">
        <input type="number" step="0.01" name="max_price" placeholder="Prix max" value="<?php echo sanitize($filters['max_price']); ?>">
        <button type="submit">Filtrer</button>
        <a class="button button-secondary" href="index.php">Réinitialiser</a>
    </form>
</section>

<section class="ads-grid">
    <?php if (empty($ads)): ?>
        <p>Aucune annonce trouvée.</p>
    <?php else: ?>
        <?php foreach ($ads as $ad): ?>
            <article class="ad-card">
                <a href="ad_detail.php?id=<?php echo $ad['id']; ?>">
                    <img src="<?php echo sanitize($ad['photo']); ?>" alt="<?php echo sanitize($ad['title']); ?>">
                </a>
                <div class="card-content">
                    <h2><?php echo sanitize($ad['title']); ?></h2>
                    <p class="price"><?php echo number_format($ad['price'], 2, ',', ' '); ?> €</p>
                    <p><?php echo nl2br(sanitize($ad['description'])); ?></p>
                    <p class="meta">Catégorie: <?php echo sanitize($ad['category']); ?></p>
                    <a class="button" href="ad_detail.php?id=<?php echo $ad['id']; ?>">Voir l'annonce</a>
                </div>
            </article>
        <?php endforeach; ?>
    <?php endif; ?>
</section>
<?php include __DIR__ . '/templates/footer.php';
