<?php
/**
 * Configuración de empresa (branding frontend + PDF).
 */
require_once __DIR__ . '/database.php';

function itform_uploads_dir(): string
{
    $dir = dirname(__DIR__) . '/uploads';
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    return $dir;
}

function itform_default_config(): array
{
    return [
        'id' => null,
        'nombre_empresa' => 'ITS Panama',
        'email_soporte' => 'soporte@itspanama.net',
        'sitio_web' => 'www.itspanama.net',
        'telefono' => '',
        'direccion' => '',
        'ruc' => '',
        'logo_login' => 'logo.png',
        'logo_footer' => 'logo2.png',
        'color_primario' => '#001F3F',
        'color_secundario' => '#4CAF50',
        'tema_defecto' => 'light',
    ];
}

function itform_get_config(?PDO $db = null): array
{
    $defaults = itform_default_config();
    try {
        $db = $db ?: getDB();
        $row = $db->query('SELECT * FROM configuracion LIMIT 1')->fetch();
        if (!$row) {
            return $defaults;
        }
        return array_merge($defaults, $row);
    } catch (Throwable $e) {
        error_log('itform_get_config: ' . $e->getMessage());
        return $defaults;
    }
}

/**
 * Resuelve ruta de filesystem del logo principal (header/PDF).
 */
function itform_logo_fs_path(array $config): ?string
{
    $rel = (string) ($config['logo_login'] ?? 'logo.png');
    $rel = ltrim(str_replace(['..', '\\'], ['', '/'], $rel), '/');
    $candidates = [
        dirname(__DIR__) . '/' . $rel,
        itform_uploads_dir() . '/' . basename($rel),
        dirname(__DIR__) . '/logo_pdf.jpg',
        dirname(__DIR__) . '/logo.png',
    ];
    foreach ($candidates as $p) {
        if (is_readable($p)) {
            return $p;
        }
    }
    return null;
}

/**
 * URL relativa web del logo principal.
 */
function itform_logo_url(array $config): string
{
    $rel = (string) ($config['logo_login'] ?? 'logo.png');
    $rel = ltrim(str_replace(['..', '\\'], ['', '/'], $rel), '/');
    if ($rel !== '' && is_readable(dirname(__DIR__) . '/' . $rel)) {
        return $rel;
    }
    if (is_readable(dirname(__DIR__) . '/logo.png')) {
        return 'logo.png';
    }
    return 'logo.png';
}

function itform_footer_logo_url(array $config): string
{
    $rel = (string) ($config['logo_footer'] ?? 'logo2.png');
    $rel = ltrim(str_replace(['..', '\\'], ['', '/'], $rel), '/');
    if ($rel !== '' && is_readable(dirname(__DIR__) . '/' . $rel)) {
        return $rel;
    }
    return 'logo2.png';
}

/**
 * Convierte imagen subida a JPEG para TCPDF (sin GD en algunos hosts).
 * Si no puede convertir, devuelve la ruta original.
 */
function itform_ensure_jpeg_logo(?string $srcPath): ?string
{
    if (!$srcPath || !is_readable($srcPath)) {
        return null;
    }
    $ext = strtolower(pathinfo($srcPath, PATHINFO_EXTENSION));
    if (in_array($ext, ['jpg', 'jpeg'], true)) {
        return $srcPath;
    }
    $dest = itform_uploads_dir() . '/logo_company_pdf.jpg';

    // GD (disponible en imagen Docker)
    if (function_exists('imagecreatefromstring') && function_exists('imagejpeg')) {
        $raw = @file_get_contents($srcPath);
        if ($raw !== false) {
            $im = @imagecreatefromstring($raw);
            if ($im !== false) {
                $w = imagesx($im);
                $h = imagesy($im);
                $bg = imagecreatetruecolor(max(1, $w), max(1, $h));
                $white = imagecolorallocate($bg, 255, 255, 255);
                imagefilledrectangle($bg, 0, 0, $w, $h, $white);
                imagecopy($bg, $im, 0, 0, 0, 0, $w, $h);
                if (@imagejpeg($bg, $dest, 92)) {
                    imagedestroy($im);
                    imagedestroy($bg);
                    return $dest;
                }
                imagedestroy($im);
                imagedestroy($bg);
            }
        }
    }

    $convertCandidates = ['/usr/bin/convert', '/usr/local/bin/convert'];
    foreach ($convertCandidates as $convert) {
        if (!is_executable($convert)) {
            continue;
        }
        $cmd = escapeshellcmd($convert) . ' ' . escapeshellarg($srcPath) . ' -background white -flatten '
            . escapeshellarg($dest) . ' 2>/dev/null';
        exec($cmd, $o, $code);
        if ($code === 0 && is_readable($dest)) {
            return $dest;
        }
    }

    $pyCandidates = [
        '/opt/data/tmp/venv-pdf/bin/python',
        '/usr/bin/python3',
        '/usr/local/bin/python3',
    ];
    foreach ($pyCandidates as $py) {
        if ($py === '' || !is_executable($py)) {
            continue;
        }
        $script = 'from PIL import Image; import sys; im=Image.open(sys.argv[1]).convert("RGBA");'
            . ' bg=Image.new("RGB", im.size, (255,255,255)); bg.paste(im, mask=im.split()[-1]);'
            . ' bg.save(sys.argv[2], "JPEG", quality=92)';
        $cmd = escapeshellcmd($py) . ' -c ' . escapeshellarg($script) . ' '
            . escapeshellarg($srcPath) . ' ' . escapeshellarg($dest) . ' 2>/dev/null';
        exec($cmd, $o, $code);
        if ($code === 0 && is_readable($dest)) {
            return $dest;
        }
    }

    // Fallbacks embebidos seguros para TCPDF (sin alpha)
    foreach ([
        dirname(__DIR__) . '/logo_pdf.jpg',
        dirname(__DIR__) . '/logo2_pdf.jpg',
    ] as $fallback) {
        if (is_readable($fallback)) {
            return $fallback;
        }
    }

    // Nunca devolver PNG/WEBP a TCPDF sin GD (puede fatal error)
    if (in_array($ext, ['png', 'webp', 'gif'], true)) {
        return null;
    }
    return $srcPath;
}

/**
 * Procesa upload de logo. Retorna path relativo web o null.
 */
function itform_handle_logo_upload(array $file, string $fieldLabel = 'logo'): string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return '';
    }
    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        throw new RuntimeException("Error al subir {$fieldLabel}");
    }
    if (($file['size'] ?? 0) > 2 * 1024 * 1024) {
        throw new RuntimeException('El logo no puede superar 2 MB');
    }
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    $map = [
        'image/png' => 'png',
        'image/jpeg' => 'jpg',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
    ];
    if (!isset($map[$mime])) {
        throw new RuntimeException('Formato de logo no permitido (use PNG/JPG/WEBP)');
    }
    $ext = $map[$mime];
    $name = 'logo_company.' . $ext;
    $dest = itform_uploads_dir() . '/' . $name;
    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        // allow local copy for CLI tests
        if (!@copy($file['tmp_name'], $dest)) {
            throw new RuntimeException('No se pudo guardar el logo');
        }
    }
    @chmod($dest, 0644);
    // JPEG companion for PDF
    itform_ensure_jpeg_logo($dest);
    return 'uploads/' . $name;
}
