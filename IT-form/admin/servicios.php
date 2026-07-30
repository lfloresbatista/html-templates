<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../config/pdf_report.php';
requireAuth();

$db = getDB();
$msg = null;
$err = null;
$csrf = itform_csrf_token();

$firmadosDir = dirname(__DIR__) . '/storage/firmados';
if (!is_dir($firmadosDir)) {
    @mkdir($firmadosDir, 0750, true);
}
if (!is_file($firmadosDir . '/.htaccess')) {
    @file_put_contents($firmadosDir . '/.htaccess', "Require all denied\n");
}

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
            } elseif ($action === 'upload_firmado') {
                $id = (int) ($_POST['id'] ?? 0);
                if ($id <= 0) {
                    throw new RuntimeException('Servicio inválido');
                }
                $stmt = $db->prepare('SELECT * FROM servicios WHERE id = :id');
                $stmt->execute([':id' => $id]);
                $svc = $stmt->fetch();
                if (!$svc) {
                    throw new RuntimeException('Servicio no encontrado');
                }
                $file = $_FILES['informe_firmado'] ?? null;
                if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                    throw new RuntimeException('Seleccione un PDF firmado');
                }
                if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
                    throw new RuntimeException('Error al subir el archivo');
                }
                if (($file['size'] ?? 0) > 12 * 1024 * 1024) {
                    throw new RuntimeException('El PDF no puede superar 12 MB');
                }
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mime = $finfo->file($file['tmp_name']);
                $okMime = in_array($mime, ['application/pdf', 'application/x-pdf'], true);
                $head = file_get_contents($file['tmp_name'], false, null, 0, 5);
                if (!$okMime && $head !== '%PDF-') {
                    throw new RuntimeException('Solo se permiten archivos PDF');
                }
                $basename = itform_signed_pdf_basename($svc);
                $destRel = 'storage/firmados/' . $basename;
                $dest = dirname(__DIR__) . '/' . $destRel;
                // si ya existe, sobrescribe
                if (!move_uploaded_file($file['tmp_name'], $dest)) {
                    throw new RuntimeException('No se pudo guardar el PDF firmado');
                }
                @chmod($dest, 0640);
                $db->prepare(
                    'UPDATE servicios SET pdf_generado = 1, ruta_pdf = :r, fecha_actualizacion = :f WHERE id = :id'
                )->execute([
                    ':r' => $destRel,
                    ':f' => date('Y-m-d H:i:s'),
                    ':id' => $id,
                ]);
                logAudit($db, currentUserId(), 'UPLOAD', 'servicios', $id, 'Informe firmado: ' . $basename);
                $msg = 'Informe firmado subido: ' . $basename;
            }
        } catch (Throwable $e) {
            error_log($e->getMessage());
            $err = $e instanceof RuntimeException ? $e->getMessage() : 'No se pudo completar la acción';
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
                    <th>N°</th><th>Cliente</th><th>Ticket</th><th>Técnico</th><th>Estado</th><th>Fecha</th><th>Informe</th><th>Acción</th>
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
                    <td class="actions-stack">
                        <a class="btn-sm btn-secondary" href="print_servicio.php?id=<?php echo (int) $s['id']; ?>" target="_blank" title="Reimprimir PDF del proyecto">🖨 Reimprimir</a>
                        <?php if (!empty($s['ruta_pdf'])): ?>
                        <a class="btn-sm btn-primary" href="download_firmado.php?id=<?php echo (int) $s['id']; ?>" title="Descargar PDF firmado">📄 Firmado</a>
                        <?php else: ?>
                        <span class="muted">Sin firmado</span>
                        <?php endif; ?>
                        <form method="post" enctype="multipart/form-data" class="upload-firmado">
                            <input type="hidden" name="csrf_token" value="<?php echo itform_e($csrf); ?>">
                            <input type="hidden" name="action" value="upload_firmado">
                            <input type="hidden" name="id" value="<?php echo (int) $s['id']; ?>">
                            <input type="file" name="informe_firmado" accept="application/pdf,.pdf" required>
                            <button class="btn-sm btn-ok" type="submit" title="Sube el PDF firmado; se renombra a …-FIRMADO.pdf">⬆ Subir firmado</button>
                        </form>
                    </td>
                    <td>
                        <form method="post" style="display:flex;gap:6px;align-items:center;flex-wrap:wrap;">
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
                <tr><td colspan="8" style="text-align:center;padding:30px;">Sin resultados</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
        <p class="hint-firmado">El archivo firmado se guarda como <code>NUMERO_CLIENTE-FIRMADO.pdf</code> (ej. <code>2026-07-0001_INSTITUTO-FIRMADO.pdf</code>).</p>
    </div>
</div>
<?php require __DIR__ . '/layout_footer.php'; ?>
