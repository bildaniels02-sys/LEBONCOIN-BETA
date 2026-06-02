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
