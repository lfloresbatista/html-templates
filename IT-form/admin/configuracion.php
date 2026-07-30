<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../config/company.php';
requireAdmin();

$db = getDB();
$msg = null;
$err = null;
$csrf = itform_csrf_token();

// Asegurar columna ruc si falta
try {
    if (itform_db_driver() === 'sqlite') {
        $cols = [];
        foreach ($db->query('PRAGMA table_info(configuracion)') as $c) {
            $cols[] = $c['name'];
        }
        if (!in_array('ruc', $cols, true)) {
            $db->exec("ALTER TABLE configuracion ADD COLUMN ruc TEXT DEFAULT ''");
        }
    } else {
        $chk = $db->query("SHOW COLUMNS FROM configuracion LIKE 'ruc'")->fetch();
        if (!$chk) {
            $db->exec("ALTER TABLE configuracion ADD COLUMN ruc VARCHAR(100) DEFAULT ''");
        }
    }
} catch (Throwable $e) {
    error_log('migrate ruc: ' . $e->getMessage());
}

$defaultsFactory = itform_default_config();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!itform_csrf_validate($_POST['csrf_token'] ?? null)) {
        $err = 'Token CSRF inválido';
    } else {
        $action = $_POST['action'] ?? 'save';
        try {
            $current = itform_get_config($db);

            if ($action === 'restore_factory') {
                // Restaura datos de fábrica EXCEPTO logos
                $fields = [
                    'nombre_empresa' => $defaultsFactory['nombre_empresa'],
                    'email_soporte' => $defaultsFactory['email_soporte'],
                    'sitio_web' => $defaultsFactory['sitio_web'],
                    'telefono' => $defaultsFactory['telefono'],
                    'direccion' => $defaultsFactory['direccion'],
                    'ruc' => $defaultsFactory['ruc'],
                    'logo_login' => $current['logo_login'] ?? 'logo.png',
                    'logo_footer' => $current['logo_footer'] ?? 'logo2.png',
                    'color_primario' => $defaultsFactory['color_primario'],
                    'color_secundario' => $defaultsFactory['color_secundario'],
                    'tema_defecto' => $defaultsFactory['tema_defecto'],
                ];
                $msg = 'Configuración restaurada de fábrica (logos conservados)';
            } else {
                $hex = static function (string $v, string $fallback): string {
                    $v = trim($v);
                    if (!preg_match('/^#[0-9A-Fa-f]{6}$/', $v)) {
                        return $fallback;
                    }
                    return strtoupper($v);
                };
                $fields = [
                    'nombre_empresa' => trim((string) ($_POST['nombre_empresa'] ?? '')),
                    'email_soporte' => trim((string) ($_POST['email_soporte'] ?? '')),
                    'sitio_web' => trim((string) ($_POST['sitio_web'] ?? '')),
                    'telefono' => trim((string) ($_POST['telefono'] ?? '')),
                    'direccion' => trim((string) ($_POST['direccion'] ?? '')),
                    'ruc' => trim((string) ($_POST['ruc'] ?? '')),
                    'logo_login' => $current['logo_login'] ?? 'logo.png',
                    'logo_footer' => $current['logo_footer'] ?? 'logo2.png',
                    'color_primario' => $hex((string) ($_POST['color_primario'] ?? ''), '#001F3F'),
                    'color_secundario' => $hex((string) ($_POST['color_secundario'] ?? ''), '#4CAF50'),
                    'tema_defecto' => ($_POST['tema_defecto'] ?? 'light') === 'dark' ? 'dark' : 'light',
                ];
                if ($fields['nombre_empresa'] === '') {
                    throw new RuntimeException('Nombre de empresa requerido');
                }
                if (!empty($_FILES['logo_file']) && ($_FILES['logo_file']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                    $fields['logo_login'] = itform_handle_logo_upload($_FILES['logo_file'], 'logo principal');
                }
                if (!empty($_FILES['logo_footer_file']) && ($_FILES['logo_footer_file']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                    $fields['logo_footer'] = itform_handle_logo_upload($_FILES['logo_footer_file'], 'logo footer');
                }
                $msg = 'Configuración guardada correctamente';
            }

            $row = $db->query('SELECT id FROM configuracion LIMIT 1')->fetch();
            $params = [
                ':n' => $fields['nombre_empresa'],
                ':e' => $fields['email_soporte'],
                ':w' => $fields['sitio_web'],
                ':t' => $fields['telefono'],
                ':d' => $fields['direccion'],
                ':r' => $fields['ruc'],
                ':ll' => $fields['logo_login'],
                ':lf' => $fields['logo_footer'],
                ':cp' => $fields['color_primario'],
                ':cs' => $fields['color_secundario'],
                ':td' => $fields['tema_defecto'],
            ];
            if ($row) {
                $params[':f'] = date('Y-m-d H:i:s');
                $params[':id'] = $row['id'];
                $db->prepare(
                    'UPDATE configuracion SET
                     nombre_empresa=:n, email_soporte=:e, sitio_web=:w, telefono=:t, direccion=:d, ruc=:r,
                     logo_login=:ll, logo_footer=:lf, color_primario=:cp, color_secundario=:cs,
                     tema_defecto=:td, fecha_actualizacion=:f WHERE id=:id'
                )->execute($params);
            } else {
                $db->prepare(
                    'INSERT INTO configuracion
                     (nombre_empresa,email_soporte,sitio_web,telefono,direccion,ruc,logo_login,logo_footer,color_primario,color_secundario,tema_defecto)
                     VALUES (:n,:e,:w,:t,:d,:r,:ll,:lf,:cp,:cs,:td)'
                )->execute($params);
            }
            logAudit($db, currentUserId(), 'UPDATE', 'configuracion', 1, ($action === 'restore_factory' ? 'Restore factory' : 'Config') . ': ' . $fields['nombre_empresa']);
        } catch (Throwable $e) {
            error_log($e->getMessage());
            $err = $e instanceof RuntimeException ? $e->getMessage() : 'Error al guardar';
            $msg = null;
        }
    }
}

$config = itform_get_config($db);
$logoUrl = itform_logo_url($config);
$logoFooterUrl = itform_footer_logo_url($config);
$cp = $config['color_primario'] ?? '#001F3F';
$cs = $config['color_secundario'] ?? '#4CAF50';

$pageTitle = 'Configuración';
$activeNav = 'config';
require __DIR__ . '/layout_header.php';
?>
<?php if ($msg): ?><div class="alert alert-ok"><?php echo itform_e($msg); ?></div><?php endif; ?>
<?php if ($err): ?><div class="alert alert-error"><?php echo itform_e($err); ?></div><?php endif; ?>

<div class="card">
    <div class="card-header"><h2>Datos de la empresa</h2></div>
    <div class="card-body">
        <p style="color:#666;margin-top:0;">Estos datos se muestran en el <strong>formulario</strong> y en el <strong>PDF</strong> del informe técnico.</p>
        <form method="post" enctype="multipart/form-data" id="configForm">
            <input type="hidden" name="csrf_token" value="<?php echo itform_e($csrf); ?>">
            <input type="hidden" name="action" id="configAction" value="save">
            <div class="form-grid">
                <div class="form-group">
                    <label>Nombre de la empresa *</label>
                    <input name="nombre_empresa" required value="<?php echo itform_e($config['nombre_empresa'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>RUC / ID fiscal</label>
                    <input name="ruc" value="<?php echo itform_e($config['ruc'] ?? ''); ?>" placeholder="Ej: 8-812-1877 DV90">
                </div>
                <div class="form-group">
                    <label>Email soporte</label>
                    <input type="email" name="email_soporte" value="<?php echo itform_e($config['email_soporte'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>Sitio web</label>
                    <input name="sitio_web" value="<?php echo itform_e($config['sitio_web'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>Teléfono</label>
                    <input name="telefono" value="<?php echo itform_e($config['telefono'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>Dirección</label>
                    <input name="direccion" value="<?php echo itform_e($config['direccion'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label>Color primario</label>
                    <div class="color-field">
                        <input type="color" id="color_primario_picker" value="<?php echo itform_e($cp); ?>" aria-label="Selector color primario">
                        <input type="text" name="color_primario" id="color_primario_text" pattern="^#[0-9A-Fa-f]{6}$" maxlength="7" value="<?php echo itform_e($cp); ?>" placeholder="#001F3F">
                    </div>
                </div>
                <div class="form-group">
                    <label>Color secundario</label>
                    <div class="color-field">
                        <input type="color" id="color_secundario_picker" value="<?php echo itform_e($cs); ?>" aria-label="Selector color secundario">
                        <input type="text" name="color_secundario" id="color_secundario_text" pattern="^#[0-9A-Fa-f]{6}$" maxlength="7" value="<?php echo itform_e($cs); ?>" placeholder="#4CAF50">
                    </div>
                </div>
                <div class="form-group">
                    <label>Tema por defecto</label>
                    <select name="tema_defecto">
                        <option value="light" <?php echo (($config['tema_defecto'] ?? '') === 'light') ? 'selected' : ''; ?>>light</option>
                        <option value="dark" <?php echo (($config['tema_defecto'] ?? '') === 'dark') ? 'selected' : ''; ?>>dark</option>
                    </select>
                </div>
            </div>

            <h3 style="margin-top:28px;">Logos</h3>
            <div class="form-grid">
                <div class="form-group">
                    <label>Logo principal (header / PDF / panel)</label>
                    <div style="margin:8px 0;">
                        <img src="../<?php echo itform_e($logoUrl); ?>?t=<?php echo time(); ?>" alt="Logo actual" style="max-height:80px;max-width:220px;background:#fff;border:1px solid #ddd;padding:6px;border-radius:6px;" onerror="this.src='../logo.png'">
                    </div>
                    <input type="file" name="logo_file" accept="image/png,image/jpeg,image/webp,image/gif">
                    <small style="color:#888;">PNG/JPG/WEBP, máx. 2 MB. Actual: <?php echo itform_e($config['logo_login'] ?? ''); ?></small>
                </div>
                <div class="form-group">
                    <label>Logo footer (opcional)</label>
                    <div style="margin:8px 0;">
                        <img src="../<?php echo itform_e($logoFooterUrl); ?>?t=<?php echo time(); ?>" alt="Logo footer" style="max-height:50px;max-width:160px;background:#fff;border:1px solid #ddd;padding:6px;border-radius:6px;" onerror="this.src='../logo2.png'">
                    </div>
                    <input type="file" name="logo_footer_file" accept="image/png,image/jpeg,image/webp,image/gif">
                    <small style="color:#888;">Si no sube uno nuevo, se mantiene el actual.</small>
                </div>
            </div>

            <div class="config-actions">
                <button class="btn btn-primary" type="submit" onclick="document.getElementById('configAction').value='save'">💾 Guardar configuración</button>
                <button class="btn btn-restore" type="submit" onclick="return confirmRestore();">↺ Restaurar</button>
                <a class="btn btn-secondary" href="../index.php" target="_blank">Ver formulario</a>
            </div>
            <p class="restore-hint">
                <strong>Restaurar</strong> reinicia de fábrica nombre, contacto, colores y tema.
                <em>No elimina ni cambia los logos</em> ya cargados.
            </p>
        </form>
    </div>
</div>
<script>
(function () {
  function bindColor(pickerId, textId) {
    var p = document.getElementById(pickerId);
    var t = document.getElementById(textId);
    if (!p || !t) return;
    p.addEventListener('input', function () { t.value = p.value.toUpperCase(); });
    t.addEventListener('input', function () {
      var v = t.value.trim();
      if (/^#[0-9A-Fa-f]{6}$/.test(v)) p.value = v;
    });
    t.addEventListener('change', function () {
      var v = t.value.trim();
      if (!/^#[0-9A-Fa-f]{6}$/.test(v)) {
        t.value = p.value.toUpperCase();
      } else {
        t.value = v.toUpperCase();
        p.value = t.value;
      }
    });
  }
  bindColor('color_primario_picker', 'color_primario_text');
  bindColor('color_secundario_picker', 'color_secundario_text');
  window.confirmRestore = function () {
    if (!confirm('¿Restaurar configuración de fábrica?\n\nSe reiniciarán datos de empresa, colores y tema.\nLos logos NO se modifican.')) {
      return false;
    }
    document.getElementById('configAction').value = 'restore_factory';
    return true;
  };
})();
</script>
<?php require __DIR__ . '/layout_footer.php'; ?>
