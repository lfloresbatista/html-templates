<?php
/**
 * Layout común del panel admin.
 * Uso: $pageTitle = '...'; $activeNav = 'dashboard'; require 'layout_header.php';
 */
if (!isset($pageTitle)) {
    $pageTitle = 'Panel';
}
if (!isset($activeNav)) {
    $activeNav = '';
}
itform_send_security_headers();
$csrf = itform_csrf_token();
$nombre = itform_e($_SESSION['nombre'] ?? '');
$rol = itform_e($_SESSION['rol'] ?? '');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo itform_e($csrf); ?>">
    <title><?php echo itform_e($pageTitle); ?> - Panel Administrativo</title>
    <link rel="stylesheet" href="../styles.css">
    <link rel="stylesheet" href="admin.css">
</head>
<body>
<aside class="sidebar">
    <div class="sidebar-header">
        <img src="../logo.png" alt="ITS Panama">
    </div>
    <ul class="sidebar-menu">
        <li><a href="index.php" class="<?php echo $activeNav === 'dashboard' ? 'active' : ''; ?>">📊 Dashboard</a></li>
        <li><a href="servicios.php" class="<?php echo $activeNav === 'servicios' ? 'active' : ''; ?>">📋 Servicios</a></li>
        <?php if (isAdmin()): ?>
        <li><a href="usuarios.php" class="<?php echo $activeNav === 'usuarios' ? 'active' : ''; ?>">👥 Usuarios</a></li>
        <li><a href="configuracion.php" class="<?php echo $activeNav === 'config' ? 'active' : ''; ?>">⚙️ Configuración</a></li>
        <li><a href="auditoria.php" class="<?php echo $activeNav === 'auditoria' ? 'active' : ''; ?>">📝 Auditoría</a></li>
        <?php endif; ?>
        <li><a href="../index.php" target="_blank">🌐 Formulario</a></li>
    </ul>
</aside>
<div class="main-content">
    <header class="top-header">
        <div class="header-title"><h1><?php echo itform_e($pageTitle); ?></h1></div>
        <div class="header-actions">
            <button type="button" class="theme-toggle" id="themeToggle" aria-label="Cambiar tema">🌙</button>
            <div class="user-menu">
                <div class="user-info">
                    <span class="name"><?php echo $nombre; ?></span>
                    <span class="role"><?php echo $rol; ?></span>
                </div>
                <form action="auth.php" method="POST" style="display:inline;">
                    <input type="hidden" name="action" value="logout">
                    <input type="hidden" name="csrf_token" value="<?php echo itform_e($csrf); ?>">
                    <button type="submit" class="btn-logout">Cerrar Sesión</button>
                </form>
            </div>
        </div>
    </header>
    <div class="dashboard-content">
