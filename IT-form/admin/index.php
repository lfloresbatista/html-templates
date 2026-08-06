<?php
require_once __DIR__ . '/auth.php';
requireAuth();

$db = getDB();
$error = null;
$totalServicios = 0;
$totalUsuarios = 0;
$serviciosPorEstado = [];
$serviciosRecientes = [];

try {
    $totalServicios = (int) $db->query('SELECT COUNT(*) FROM servicios')->fetchColumn();
    $totalUsuarios = (int) $db->query('SELECT COUNT(*) FROM usuarios WHERE activo = 1')->fetchColumn();
    $serviciosPorEstado = $db->query('SELECT estado, COUNT(*) AS cantidad FROM servicios GROUP BY estado')->fetchAll();
    $serviciosRecientes = $db->query(
        'SELECT s.*, u.nombre_completo AS tecnico
         FROM servicios s
         LEFT JOIN usuarios u ON s.usuario_id = u.id
         ORDER BY s.fecha_guardado DESC LIMIT 10'
    )->fetchAll();
} catch (Throwable $e) {
    error_log($e->getMessage());
    $error = 'Error al cargar datos del dashboard';
}

$pendientes = 0;
$revision = 0;
$completados = 0;
foreach ($serviciosPorEstado as $row) {
    if ($row['estado'] === 'pendiente') {
        $pendientes = (int) $row['cantidad'];
    }
    if ($row['estado'] === 'revision') {
        $revision = (int) $row['cantidad'];
    }
    if ($row['estado'] === 'completado') {
        $completados = (int) $row['cantidad'];
    }
}

$pageTitle = 'Dashboard';
$activeNav = 'dashboard';
require __DIR__ . '/layout_header.php';
?>
<?php if ($error): ?><div class="alert alert-error"><?php echo itform_e($error); ?></div><?php endif; ?>
<div class="stats-grid">
    <div class="stat-card primary"><div class="value"><?php echo $totalServicios; ?></div><div class="label">Total Servicios</div></div>
    <div class="stat-card success"><div class="value"><?php echo $totalUsuarios; ?></div><div class="label">Usuarios Activos</div></div>
    <div class="stat-card warning"><div class="value"><?php echo $pendientes; ?></div><div class="label">Pendientes</div></div>
    <div class="stat-card revision"><div class="value"><?php echo $revision; ?></div><div class="label">En Revisión</div></div>
    <div class="stat-card info"><div class="value"><?php echo $completados; ?></div><div class="label">Completados</div></div>
</div>
<div class="card">
    <div class="card-header">
        <h2>Servicios Recientes</h2>
        <a href="servicios.php">Ver todos →</a>
    </div>
    <div class="card-body">
        <table>
            <thead>
                <tr>
                    <th>Número</th><th>Cliente</th><th>Ticket</th><th>Técnico</th><th>Estado</th><th>Fecha</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($serviciosRecientes): foreach ($serviciosRecientes as $s): ?>
                <tr>
                    <td><strong><?php echo itform_e($s['numero_secuencia']); ?></strong></td>
                    <td><?php echo itform_e($s['cliente']); ?></td>
                    <td><?php echo itform_e($s['ticket']); ?></td>
                    <td><?php echo itform_e($s['tecnico'] ?? 'N/A'); ?></td>
                    <td><span class="badge badge-<?php echo itform_e($s['estado']); ?>"><?php echo itform_e(str_replace('_', ' ', $s['estado'])); ?></span></td>
                    <td><?php echo itform_e($s['fecha_guardado']); ?></td>
                </tr>
            <?php endforeach; else: ?>
                <tr><td colspan="6" style="text-align:center;padding:40px;">No hay servicios registrados</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require __DIR__ . '/layout_footer.php'; ?>
