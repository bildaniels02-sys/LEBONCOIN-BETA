<?php
require_once __DIR__ . '/../includes/config.php';

$sqlFile = __DIR__ . '/../sql/migrate_admin_reports.sql';
if (!file_exists($sqlFile)) {
    echo "Fichier de migration introuvable: $sqlFile\n";
    exit(1);
}

$sql = file_get_contents($sqlFile);
if ($sql === false) {
    echo "Impossible de lire le fichier SQL.\n";
    exit(1);
}

$stmts = array_filter(array_map('trim', explode(';', $sql)));
foreach ($stmts as $stmt) {
    if ($stmt === '') continue;
    if (strpos($stmt, '--') === 0) continue;
    if (!$mysqli->query($stmt)) {
        echo "Erreur SQL: " . $mysqli->error . "\n";
        echo "Statement: " . substr($stmt, 0, 200) . "...\n";
        exit(1);
    }
}

echo "Migration admin/signalements exécutée avec succès.\n";
exit(0);
