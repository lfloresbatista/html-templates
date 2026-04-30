<?php
/**
 * Gestión de Usuarios - Placeholder
 * Este archivo debe ser completado con la interfaz CRUD completa
 */

session_start();
require_once 'auth.php';
requireAdmin();

$db = getDB();

// Obtener todos los usuarios
try {
    $stmt = $db->query("SELECT id, username, email, nombre_completo, rol, activo, ultimo_acceso, fecha_creacion FROM usuarios ORDER BY id");
    $usuarios = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = "Error al cargar usuarios";
}

include 'header.php'; // Crear este archivo para el header común
?>

<div class="dashboard-content">
    <div class="card">
        <div class="card-header">
            <h2>Gestión de Usuarios</h2>
            <button class="btn btn-primary" onclick="openModal('usuario')">+ Nuevo Usuario</button>
        </div>
        <div class="card-body">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Usuario</th>
                        <th>Nombre</th>
                        <th>Email</th>
                        <th>Rol</th>
                        <th>Estado</th>
                        <th>Último Acceso</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($usuarios as $user): ?>
                    <tr>
                        <td><?php echo $user['id']; ?></td>
                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                        <td><?php echo htmlspecialchars($user['nombre_completo']); ?></td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td><span class="badge badge-<?php echo $user['rol']; ?>"><?php echo ucfirst($user['rol']); ?></span></td>
                        <td><?php echo $user['activo'] ? '✅ Activo' : '❌ Inactivo'; ?></td>
                        <td><?php echo $user['ultimo_acceso'] ? date('d/m/Y H:i', strtotime($user['ultimo_acceso'])) : 'Nunca'; ?></td>
                        <td>
                            <button class="btn btn-sm btn-edit" onclick="editUser(<?php echo $user['id']; ?>)">✏️</button>
                            <button class="btn btn-sm btn-delete" onclick="deleteUser(<?php echo $user['id']; ?>)">🗑️</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'footer.php'; // Crear este archivo para el footer común ?>
