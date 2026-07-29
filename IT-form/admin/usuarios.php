<?php
require_once __DIR__ . '/auth.php';
requireAdmin();

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
            if ($action === 'create' || $action === 'update') {
                $username = trim((string) ($_POST['username'] ?? ''));
                $email = trim((string) ($_POST['email'] ?? ''));
                $nombre = trim((string) ($_POST['nombre_completo'] ?? ''));
                $rol = $_POST['rol'] ?? 'tecnico';
                $activo = isset($_POST['activo']) ? 1 : 0;
                $password = (string) ($_POST['password'] ?? '');
                if (!in_array($rol, ['admin', 'tecnico', 'usuario'], true)) {
                    throw new RuntimeException('Rol inválido');
                }
                if ($username === '' || $email === '' || $nombre === '') {
                    throw new RuntimeException('Campos obligatorios incompletos');
                }
                if ($action === 'create') {
                    if (strlen($password) < 8) {
                        throw new RuntimeException('Password mínimo 8 caracteres');
                    }
                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    $db->prepare(
                        'INSERT INTO usuarios (username, password_hash, email, nombre_completo, rol, activo)
                         VALUES (:u,:p,:e,:n,:r,:a)'
                    )->execute([
                        ':u' => $username, ':p' => $hash, ':e' => $email,
                        ':n' => $nombre, ':r' => $rol, ':a' => $activo,
                    ]);
                    $id = (int) $db->lastInsertId();
                    logAudit($db, currentUserId(), 'CREATE', 'usuarios', $id, "Usuario {$username}");
                    $msg = 'Usuario creado';
                } else {
                    $id = (int) ($_POST['id'] ?? 0);
                    if ($password !== '') {
                        if (strlen($password) < 8) {
                            throw new RuntimeException('Password mínimo 8 caracteres');
                        }
                        $hash = password_hash($password, PASSWORD_DEFAULT);
                        $db->prepare(
                            'UPDATE usuarios SET username=:u, email=:e, nombre_completo=:n, rol=:r, activo=:a,
                             password_hash=:p, fecha_actualizacion=:f WHERE id=:id'
                        )->execute([
                            ':u' => $username, ':e' => $email, ':n' => $nombre, ':r' => $rol,
                            ':a' => $activo, ':p' => $hash, ':f' => date('Y-m-d H:i:s'), ':id' => $id,
                        ]);
                    } else {
                        $db->prepare(
                            'UPDATE usuarios SET username=:u, email=:e, nombre_completo=:n, rol=:r, activo=:a,
                             fecha_actualizacion=:f WHERE id=:id'
                        )->execute([
                            ':u' => $username, ':e' => $email, ':n' => $nombre, ':r' => $rol,
                            ':a' => $activo, ':f' => date('Y-m-d H:i:s'), ':id' => $id,
                        ]);
                    }
                    logAudit($db, currentUserId(), 'UPDATE', 'usuarios', $id, "Usuario {$username}");
                    $msg = 'Usuario actualizado';
                }
            } elseif ($action === 'delete') {
                $id = (int) ($_POST['id'] ?? 0);
                if ($id === currentUserId()) {
                    throw new RuntimeException('No puede eliminar su propio usuario');
                }
                $db->prepare('UPDATE usuarios SET activo = 0, fecha_actualizacion = :f WHERE id = :id')
                    ->execute([':f' => date('Y-m-d H:i:s'), ':id' => $id]);
                logAudit($db, currentUserId(), 'DEACTIVATE', 'usuarios', $id, 'Usuario desactivado');
                $msg = 'Usuario desactivado';
            }
        } catch (Throwable $e) {
            error_log($e->getMessage());
            $err = $e instanceof RuntimeException ? $e->getMessage() : 'Error al guardar usuario';
        }
    }
}

$usuarios = $db->query(
    'SELECT id, username, email, nombre_completo, rol, activo, ultimo_acceso, fecha_creacion
     FROM usuarios ORDER BY id'
)->fetchAll();
$editId = isset($_GET['edit']) ? (int) $_GET['edit'] : 0;
$editUser = null;
if ($editId) {
    foreach ($usuarios as $u) {
        if ((int) $u['id'] === $editId) {
            $editUser = $u;
            break;
        }
    }
}

$pageTitle = 'Usuarios';
$activeNav = 'usuarios';
require __DIR__ . '/layout_header.php';
?>
<?php if ($msg): ?><div class="alert alert-ok"><?php echo itform_e($msg); ?></div><?php endif; ?>
<?php if ($err): ?><div class="alert alert-error"><?php echo itform_e($err); ?></div><?php endif; ?>

<div class="card">
    <div class="card-header">
        <h2><?php echo $editUser ? 'Editar usuario' : 'Nuevo usuario'; ?></h2>
    </div>
    <div class="card-body">
        <form method="post">
            <input type="hidden" name="csrf_token" value="<?php echo itform_e($csrf); ?>">
            <input type="hidden" name="action" value="<?php echo $editUser ? 'update' : 'create'; ?>">
            <?php if ($editUser): ?><input type="hidden" name="id" value="<?php echo (int) $editUser['id']; ?>"><?php endif; ?>
            <div class="form-grid">
                <div class="form-group">
                    <label>Usuario</label>
                    <input name="username" required value="<?php echo itform_e($editUser['username'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" required value="<?php echo itform_e($editUser['email'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>Nombre completo</label>
                    <input name="nombre_completo" required value="<?php echo itform_e($editUser['nombre_completo'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>Rol</label>
                    <select name="rol">
                        <?php foreach (['admin','tecnico','usuario'] as $r): ?>
                        <option value="<?php echo $r; ?>" <?php echo (($editUser['rol'] ?? '') === $r) ? 'selected' : ''; ?>><?php echo $r; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Contraseña <?php echo $editUser ? '(vacío = no cambiar)' : ''; ?></label>
                    <input type="password" name="password" <?php echo $editUser ? '' : 'required minlength="8"'; ?> autocomplete="new-password">
                </div>
                <div class="form-group">
                    <label><input type="checkbox" name="activo" <?php echo !isset($editUser['activo']) || (int)$editUser['activo'] === 1 ? 'checked' : ''; ?>> Activo</label>
                </div>
            </div>
            <p style="margin-top:16px;">
                <button class="btn btn-primary" type="submit">Guardar</button>
                <?php if ($editUser): ?><a class="btn btn-secondary" href="usuarios.php">Cancelar</a><?php endif; ?>
            </p>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header"><h2>Listado</h2></div>
    <div class="card-body">
        <table>
            <thead>
                <tr>
                    <th>ID</th><th>Usuario</th><th>Nombre</th><th>Email</th><th>Rol</th><th>Estado</th><th>Último acceso</th><th>Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($usuarios as $u): ?>
                <tr>
                    <td><?php echo (int) $u['id']; ?></td>
                    <td><?php echo itform_e($u['username']); ?></td>
                    <td><?php echo itform_e($u['nombre_completo']); ?></td>
                    <td><?php echo itform_e($u['email']); ?></td>
                    <td><span class="badge badge-<?php echo itform_e($u['rol']); ?>"><?php echo itform_e($u['rol']); ?></span></td>
                    <td><?php echo (int) $u['activo'] ? '✅ Activo' : '❌ Inactivo'; ?></td>
                    <td><?php echo itform_e($u['ultimo_acceso'] ?? 'Nunca'); ?></td>
                    <td style="display:flex;gap:6px;">
                        <a class="btn-sm btn-primary" href="usuarios.php?edit=<?php echo (int) $u['id']; ?>">✏️</a>
                        <form method="post" onsubmit="return confirm('¿Desactivar usuario?');">
                            <input type="hidden" name="csrf_token" value="<?php echo itform_e($csrf); ?>">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?php echo (int) $u['id']; ?>">
                            <button class="btn-sm btn-danger" type="submit">🗑️</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require __DIR__ . '/layout_footer.php'; ?>
