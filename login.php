<?php
require_once __DIR__ . '/includes/functions.php';

$errors = [];
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $errors[] = 'Email et mot de passe sont requis.';
    } else {
        global $mysqli;
        $final = db_prepare_sql('SELECT * FROM users WHERE email = :email', ['email' => $email]);
        $res = $mysqli->query($final);
        $user = $res ? $res->fetch_assoc() : false;
        if (!$user || !password_verify($password, $user['password_hash'])) {
            $errors[] = 'Email ou mot de passe incorrect.';
        } else {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user'] = ['id' => $user['id'], 'email' => $user['email'], 'is_admin' => $user['is_admin'] ?? 0];
            redirect('index.php');
        }
    }
}

include __DIR__ . '/templates/header.php';
?>
<section class="auth-form">
    <h2>Connexion</h2>
    <?php if ($errors): ?>
        <div class="alert alert-error">
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?php echo sanitize($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    <form method="post" action="login.php">
        <label>Email</label>
        <input type="email" name="email" required value="<?php echo sanitize($email); ?>">
        <label>Mot de passe</label>
        <input type="password" name="password" required>
        <button type="submit">Se connecter</button>
    </form>
</section>
<?php include __DIR__ . '/templates/footer.php';
