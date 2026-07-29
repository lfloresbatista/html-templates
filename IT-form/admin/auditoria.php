<?php
require_once __DIR__ . '/auth.php';
requireAdmin();

$db = getDB();
$rows = $db->query(
    'SELECT a.*, u.username
     FROM auditoria a
     LEFT JOIN usuarios u ON a.usuario_id = u.id
     ORDER BY a.fecha_registro DESC
     LIMIT 300'
)->fetchAll();

$pageTitle = 'Auditoría';
$activeNav = 'auditoria';
require __DIR__ . '/layout_header.php';
?>
<div class="card">
    <div class="card-header"><h2>Registro de auditoría</h2></div>
    <div class="card-body">
        <table>
            <thead>
                <tr>
                    <th>Fecha</th><th>Usuario</th><th>Acción</th><th>Tabla</th><th>ID</th><th>Descripción</th><th>IP</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $r): ?>
                <tr>
                    <td><?php echo itform_e($r['fecha_registro']); ?></td>
                    <td><?php echo itform_e($r['username'] ?? '—'); ?></td>
                    <td><?php echo itform_e($r['accion']); ?></td>
                    <td><?php echo itform_e($r['tabla_afectada'] ?? ''); ?></td>
                    <td><?php echo itform_e((string) ($r['registro_id'] ?? '')); ?></td>
                    <td><?php echo itform_e($r['descripcion'] ?? ''); ?></td>
                    <td><?php echo itform_e($r['ip_origen'] ?? ''); ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$rows): ?>
                <tr><td colspan="7" style="text-align:center;padding:30px;">Sin eventos</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require __DIR__ . '/layout_footer.php'; ?>
