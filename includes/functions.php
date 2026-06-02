<?php
require_once __DIR__ . '/config.php';

function redirect($url) {
    header('Location: ' . $url);
    exit;
}

function flash($key, $message = null) {
    if ($message !== null) {
        $_SESSION['flash'][$key] = $message;
        return null;
    }
    if (!empty($_SESSION['flash'][$key])) {
        $msg = $_SESSION['flash'][$key];
        unset($_SESSION['flash'][$key]);
        return $msg;
    }
    return null;
}

function getCategories() {
    return ['Immobilier', 'Véhicule', 'Maison', 'Multimédia', 'Loisirs', 'Autres'];
}

function sanitize($value) {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function uploadPhoto($file, &$error = null) {
    if (!isset($file['name']) || $file['name'] === '') {
        $error = 'Aucune photo n\'a été sélectionnée.';
        return null;
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $error = match ($file['error']) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'Le fichier est trop volumineux.',
            UPLOAD_ERR_PARTIAL => 'Le fichier n\'a été téléchargé que partiellement.',
            UPLOAD_ERR_NO_FILE => 'Aucun fichier envoyé.',
            UPLOAD_ERR_NO_TMP_DIR => 'Répertoire temporaire manquant.',
            UPLOAD_ERR_CANT_WRITE => 'Impossible d\'écrire le fichier sur le disque.',
            UPLOAD_ERR_EXTENSION => 'Une extension a stoppé le téléchargement.',
            default => 'Erreur lors de l\'envoi du fichier.',
        };
        return null;
    }
    if ($file['size'] > 5 * 1024 * 1024) {
        $error = 'Le fichier est trop volumineux (max 5 Mo).';
        return null;
    }
    $tmpFile = $file['tmp_name'];
    $info = @getimagesize($tmpFile);
    $derivedExtension = null;

    if ($info !== false) {
        $allowedTypes = [
            IMAGETYPE_JPEG => 'jpg',
            IMAGETYPE_PNG => 'png',
            IMAGETYPE_GIF => 'gif',
            IMAGETYPE_BMP => 'bmp',
            IMAGETYPE_WEBP => 'webp',
            IMAGETYPE_TIFF_II => 'tif',
            IMAGETYPE_TIFF_MM => 'tif',
            IMAGETYPE_ICO => 'ico',
        ];
        if (isset($allowedTypes[$info[2]])) {
            $derivedExtension = $allowedTypes[$info[2]];
        }
    } else {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $tmpFile);
        finfo_close($finfo);
        if ($mime === 'image/svg+xml') {
            $derivedExtension = 'svg';
        }
    }

    if ($derivedExtension === null) {
        $error = 'Le fichier n\'est pas une image valide.';
        return null;
    }

    $originalExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'tif', 'tiff', 'svg', 'ico'];
    if ($originalExtension !== '' && in_array($originalExtension, $allowedExtensions, true)) {
        $extension = $originalExtension;
    } else {
        $extension = $derivedExtension;
    }

    $uploadDir = __DIR__ . '/../uploads';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    $filename = uniqid('photo_', true) . '.' . $extension;
    $destination = $uploadDir . '/' . $filename;
    if (move_uploaded_file($file['tmp_name'], $destination)) {
        return 'uploads/' . $filename;
    }
    $error = 'Impossible de déplacer le fichier uploadé.';
    return null;
}

/**
 * Send a message for an ad: find or create a conversation, then insert message.
 */
function sendMessageForAd($ad_id, $from_user_id, $to_user_id, $content) {
    global $mysqli;
    // try to find existing conversation
    $sql = db_prepare_sql('SELECT id FROM conversations WHERE ad_id = :ad_id AND ((user_one = :u1 AND user_two = :u2) OR (user_one = :u2 AND user_two = :u1)) LIMIT 1', ['ad_id' => $ad_id, 'u1' => $from_user_id, 'u2' => $to_user_id]);
    $res = $mysqli->query($sql);
    $conv = $res ? $res->fetch_assoc() : null;
    if (!$conv) {
        $create = db_prepare_sql('INSERT INTO conversations (ad_id, user_one, user_two) VALUES (:ad_id, :u1, :u2)', ['ad_id' => $ad_id, 'u1' => $from_user_id, 'u2' => $to_user_id]);
        $mysqli->query($create);
        $conv_id = $mysqli->insert_id;
    } else {
        $conv_id = $conv['id'];
    }

    $sql = db_prepare_sql('INSERT INTO messages (sender_id, ad_id, conversation_id, content, is_read) VALUES (:sender_id, :ad_id, :conv_id, :content, 0)', ['sender_id' => $from_user_id, 'ad_id' => $ad_id, 'conv_id' => $conv_id, 'content' => $content]);
    return $mysqli->query($sql);
}
