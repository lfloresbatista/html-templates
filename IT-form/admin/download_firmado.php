<?php
/**
 * Descarga informe firmado almacenado (auth).
 */
require_once __DIR__ . '/auth.php';
requireAuth();

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    http_response_code(400);
    exit('ID inválido');
}

$db = getDB();
$stmt = $db->prepare('SELECT id, ruta_pdf, numero_secuencia, cliente FROM servicios WHERE id = :id');
$stmt->execute([':id' => $id]);
$s = $stmt->fetch();
if (!$s || empty($s['ruta_pdf'])) {
    http_response_code(404);
    exit('Archivo no disponible');
}

$rel = ltrim(str_replace(['..', '\\'], '', (string) $s['ruta_pdf']), '/');
$path = dirname(__DIR__) . '/' . $rel;
if (!is_readable($path) || !is_file($path)) {
    http_response_code(404);
    exit('Archivo no encontrado');
}

$mime = 'application/pdf';
$finfo = new finfo(FILEINFO_MIME_TYPE);
$detected = $finfo->file($path);
if ($detected) {
    $mime = $detected;
}

$name = basename($path);
header('Content-Type: ' . $mime);
header('Content-Disposition: attachment; filename="' . $name . '"');
header('Content-Length: ' . filesize($path));
header('X-Content-Type-Options: nosniff');
readfile($path);
exit;
