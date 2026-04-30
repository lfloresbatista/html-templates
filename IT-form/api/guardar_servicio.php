<?php
/**
 * API para guardar servicios en la base de datos
 * Maneja la creación y actualización de informes de servicio técnico
 */

session_start();
require_once __DIR__ . '/../config/database.php';

// Configurar respuesta JSON
header('Content-Type: application/json; charset=utf-8');

/**
 * Verificar si el usuario está autenticado
 */
function isAuthenticated() {
    return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
}

/**
 * Registrar acción en auditoría
 */
function logAudit($db, $userId, $action, $table, $recordId, $description) {
    try {
        $sql = "INSERT INTO auditoria (usuario_id, accion, tabla_afectada, registro_id, descripcion, ip_origen, user_agent) 
                VALUES (:user_id, :accion, :tabla, :registro_id, :descripcion, :ip, :agent)";
        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':user_id' => $userId,
            ':accion' => $action,
            ':tabla' => $table,
            ':registro_id' => $recordId,
            ':descripcion' => $description,
            ':ip' => $_SERVER['REMOTE_ADDR'] ?? null,
            ':agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
    } catch (Exception $e) {
        error_log("Error en auditoría: " . $e->getMessage());
    }
}

/**
 * Sanitizar entrada de texto
 */
function sanitizeInput($data) {
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

// Verificar método HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

// Verificar autenticación (opcional - puede comentarse para permitir sin login)
// if (!isAuthenticated()) {
//     http_response_code(401);
//     echo json_encode(['success' => false, 'error' => 'No autorizado']);
//     exit;
// }

try {
    $db = getDB();
    
    // Obtener datos del formulario
    $cliente = sanitizeInput($_POST['cliente'] ?? '');
    $fechaServicio = $_POST['fecha'] ?? date('Y-m-d H:i:s');
    $direccion = sanitizeInput($_POST['direccion'] ?? '');
    $ticket = sanitizeInput($_POST['ticket'] ?? '');
    $reporteCliente = sanitizeInput($_POST['reporte'] ?? '');
    $diagnostico = sanitizeInput($_POST['diagnostico'] ?? '');
    $trabajoRealizado = sanitizeInput($_POST['trabajoRealizado'] ?? '');
    $observaciones = sanitizeInput($_POST['observaciones'] ?? '');
    $recibidoConforme = sanitizeInput($_POST['recibidoConforme'] ?? '');
    $firmaTecnico = sanitizeInput($_POST['firmaTecnico'] ?? '');
    
    // Validar campos requeridos
    $requiredFields = [
        'cliente' => $cliente,
        'fecha' => $fechaServicio,
        'direccion' => $direccion,
        'ticket' => $ticket,
        'reporte' => $reporteCliente,
        'diagnostico' => $diagnostico,
        'trabajoRealizado' => $trabajoRealizado,
        'recibidoConforme' => $recibidoConforme,
        'firmaTecnico' => $firmaTecnico
    ];
    
    foreach ($requiredFields as $field => $value) {
        if (empty($value)) {
            throw new Exception("El campo '{$field}' es requerido");
        }
    }
    
    // Obtener ID del usuario si está autenticado
    $usuarioId = $_SESSION['user_id'] ?? null;
    
    // Insertar servicio en la base de datos
    $sql = "INSERT INTO servicios (
                cliente, fecha_servicio, direccion, ticket,
                reporte_cliente, diagnostico_tecnico, trabajo_realizado,
                observaciones, recibido_conforme, firma_tecnico,
                usuario_id, estado, pdf_generado, fecha_guardado
            ) VALUES (
                :cliente, :fecha, :direccion, :ticket,
                :reporte, :diagnostico, :trabajo,
                :observaciones, :recibido, :firma,
                :usuario_id, 'completado', FALSE, NOW()
            )";
    
    $stmt = $db->prepare($sql);
    $stmt->execute([
        ':cliente' => $cliente,
        ':fecha' => $fechaServicio,
        ':direccion' => $direccion,
        ':ticket' => $ticket,
        ':reporte' => $reporteCliente,
        ':diagnostico' => $diagnostico,
        ':trabajo' => $trabajoRealizado,
        ':observaciones' => $observaciones,
        ':recibido' => $recibidoConforme,
        ':firma' => $firmaTecnico,
        ':usuario_id' => $usuarioId
    ]);
    
    $servicioId = $db->lastInsertId();
    
    // Obtener el número de secuencia generado
    $sqlSelect = "SELECT numero_secuencia, fecha_guardado FROM servicios WHERE id = :id";
    $stmtSelect = $db->prepare($sqlSelect);
    $stmtSelect->execute([':id' => $servicioId]);
    $servicio = $stmtSelect->fetch();
    
    // Registrar en auditoría
    if ($usuarioId) {
        logAudit(
            $db, 
            $usuarioId, 
            'CREATE', 
            'servicios', 
            $servicioId, 
            "Se creó el servicio {$servicio['numero_secuencia']} para el cliente {$cliente}"
        );
    }
    
    // Respuesta exitosa
    echo json_encode([
        'success' => true,
        'message' => 'Servicio guardado exitosamente',
        'data' => [
            'id' => $servicioId,
            'numero_secuencia' => $servicio['numero_secuencia'],
            'fecha_guardado' => $servicio['fecha_guardado'],
            'cliente' => $cliente,
            'ticket' => $ticket
        ]
    ]);
    
} catch (PDOException $e) {
    error_log("Error PDO: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'error' => 'Error al guardar en la base de datos',
        'details' => $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log("Error: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false, 
        'error' => $e->getMessage()
    ]);
}
?>
