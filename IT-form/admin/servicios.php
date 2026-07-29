<?php
require_once __DIR__ . '/auth.php';
requireAuth();

$db = getDB();
$msg = null;
$err = null;
$csrf = itform_csrf_token();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!itform_csrf_validate($_POST['csrf_token'] ?? null)) {
        $err = 'Token CSRF inválido';
    } else {
        $action = $_POST['action'] ?? '';
        try {
            if ($action === 'update_estado') {
                $id = (int) ($_POST['id'] ?? 0);
                $estado = $_POST['estado'] ?? '';
                $allowed = ['pendiente', 'en_proceso', 'completado', 'cancelado'];
                if ($id > 0 && in_array($estado, $allowed, true)) {
                    $db->prepare('UPDATE servicios SET estado = :e, fecha_actualizacion = :f WHERE id = :id')
                        ->execute([':e' => $estado, ':f' => date('Y-m-d H:i:s'), ':id' => $id]);
                    logAudit($db, currentUserId(), 'UPDATE', 'servicios', $id, "Estado → {$estado}");
                    $msg = 'Estado actualizado';
                }
            }
        } catch (Throwable $e) {
            error_log($e->getMessage());
            $err = 'No se pudo actualizar';
        }
    }
}

$q = trim((string) ($_GET['q'] ?? ''));
$estadoF = trim((string) ($_GET['estado'] ?? ''));
$sql = 'SELECT s.*, u.nombre_completo AS tecnico FROM servicios s LEFT JOIN usuarios u ON s.usuario_id = u.id WHERE 1=1';
$params = [];
if ($q !== '') {
    $sql .= ' AND (s.cliente LIKE :q OR s.ticket LIKE :q OR s.numero_secuencia LIKE :q)';
    $params[':q'] = '%' . $q . '%';
}
if ($estadoF !== '') {
    $sql .= ' AND s.estado = :est';
    $params[':est'] = $estadoF;
}
$sql .= ' ORDER BY s.fecha_guardado DESC LIMIT 200';
$stmt = $db->prepare($sql);
$stmt->execute($params);
$servicios = $stmt->fetchAll();

$pageTitle = 'Servicios';
$activeNav = 'servicios';
require __DIR__ . '/layout_header.php';
?>
<?php if ($msg): ?><div class="alert alert-ok"><?php echo itform_e($msg); ?></div><?php endif; ?>
<?php if ($err): ?><div class="alert alert-error"><?php echo itform_e($err); ?></div><?php endif; ?>
<div class="card">
    <div class="card-header"><h2>Listado de servicios</h2></div>
    <div class="card-body">
        <form class="filters" method="get">
            <input type="search" name="q" placeholder="Cliente, ticket, N°..." value="<?php echo itform_e($q); ?>">
            <select name="estado">
                <option value="">Todos los estados</option>
                <?php foreach (['pendiente','en_proceso','completado','cancelado'] as $e): ?>
                <option value="<?php echo $e; ?>" <?php echo $estadoF === $e ? 'selected' : ''; ?>><?php echo $e; ?></option>
                <?php endforeach; ?>
            </select>
            <button class="btn btn-primary" type="submit">Filtrar</button>
        </form>
        <table>
            <thead>
                <tr>
                    <th>N°</th><th>Cliente</th><th>Ticket</th><th>Técnico</th><th>Estado</th><th>Fecha</th><th>Acción</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($servicios as $s): ?>
                <tr>
                    <td><?php echo itform_e($s['numero_secuencia']); ?></td>
                    <td><?php echo itform_e($s['cliente']); ?></td>
                    <td><?php echo itform_e($s['ticket']); ?></td>
                    <td><?php echo itform_e($s['tecnico'] ?? 'N/A'); ?></td>
                    <td><span class="badge badge-<?php echo itform_e($s['estado']); ?>"><?php echo itform_e($s['estado']); ?></span></td>
                    <td><?php echo itform_e($s['fecha_guardado']); ?></td>
                    <td>
                        <form method="post" style="display:flex;gap:6px;align-items:center;">
                            <input type="hidden" name="csrf_token" value="<?php echo itform_e($csrf); ?>">
                            <input type="hidden" name="action" value="update_estado">
                            <input type="hidden" name="id" value="<?php echo (int) $s['id']; ?>">
                            <select name="estado">
                                <?php foreach (['pendiente','en_proceso','completado','cancelado'] as $e): ?>
                                <option value="<?php echo $e; ?>" <?php echo $s['estado'] === $e ? 'selected' : ''; ?>><?php echo $e; ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button class="btn-sm btn-primary" type="submit">OK</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$servicios): ?>
                <tr><td colspan="7" style="text-align:center;padding:30px;">Sin resultados</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require __DIR__ . '/layout_footer.php'; ?>
