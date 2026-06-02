<?php
require_once __DIR__ . '/includes/functions.php';

$errors = [];
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '') {
        $errors[] = 'L\'email est requis.';
    }
    if ($password === '') {
        $errors[] = 'Le mot de passe est requis.';
    } elseif (strlen($password) < 10) {
        $errors[] = 'Le mot de passe doit contenir au moins 10 caractères.';
    }

    if (empty($errors)) {
        global $mysqli;
        $final = db_prepare_sql('SELECT id FROM users WHERE email = :email', ['email' => $email]);
        $res = $mysqli->query($final);
        $existing = $res ? $res->fetch_assoc() : false;
        if ($existing) {
            $errors[] = 'Cet email est déjà utilisé.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
                // ensure new users are not admins by default
                $sql = db_prepare_sql('INSERT INTO users (email, password_hash, is_admin) VALUES (:email, :password_hash, :is_admin)', ['email' => $email, 'password_hash' => $hash, 'is_admin' => 0]);
            $mysqli->query($sql);
            flash('success', 'Inscription réussie. Vous pouvez maintenant vous connecter.');
            redirect('login.php');
        }
    }
}

include __DIR__ . '/templates/header.php';
?>
<section class="auth-form">
    <h2>Créer un compte</h2>
    <?php if ($errors): ?>
        <div class="alert alert-error">
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?php echo sanitize($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    <form method="post" action="register.php">
        <label>Email</label>
        <input type="email" name="email" required value="<?php echo sanitize($email); ?>">
        <label>Mot de passe</label>
        <input type="password" name="password" required>
        <button type="submit">S'inscrire</button>
    </form>
</section>
<?php include __DIR__ . '/templates/footer.php';
