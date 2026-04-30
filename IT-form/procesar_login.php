<?php
/**
 * Procesamiento de inicio de sesión seguro
 * Maneja autenticación con protección básica contra ataques
 */

// Configuración de seguridad
error_reporting(E_ALL);
ini_set('display_errors', 0); // No mostrar errores en producción
session_start();

// Prevenir ataques de fuerza bruta (rate limiting básico)
$maxAttempts = 5;
$lockoutTime = 300; // 5 minutos

// Obtener IP del cliente
$clientIP = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$attemptFile = __DIR__ . '/.login_attempts';

/**
 * Verifica si la IP está bloqueada temporalmente
 * @return bool True si está bloqueada
 */
function isLockedOut($attemptFile, $clientIP, $maxAttempts, $lockoutTime) {
    if (!file_exists($attemptFile)) {
        return false;
    }
    
    $attempts = json_decode(file_get_contents($attemptFile), true) ?? [];
    
    if (!isset($attempts[$clientIP])) {
        return false;
    }
    
    $userData = $attempts[$clientIP];
    $timeSinceLastAttempt = time() - $userData['last_attempt'];
    
    if ($userData['attempts'] >= $maxAttempts && $timeSinceLastAttempt < $lockoutTime) {
        return true;
    }
    
    // Resetear contador si pasó el tiempo de bloqueo
    if ($timeSinceLastAttempt >= $lockoutTime) {
        unset($attempts[$clientIP]);
        file_put_contents($attemptFile, json_encode($attempts));
    }
    
    return false;
}

/**
 * Registra un intento de login fallido
 */
function logFailedAttempt($attemptFile, $clientIP) {
    $attempts = file_exists($attemptFile) ? json_decode(file_get_contents($attemptFile), true) : [];
    
    if (!isset($attempts[$clientIP])) {
        $attempts[$clientIP] = ['attempts' => 0, 'last_attempt' => 0];
    }
    
    $attempts[$clientIP]['attempts']++;
    $attempts[$clientIP]['last_attempt'] = time();
    
    file_put_contents($attemptFile, json_encode($attempts));
}

/**
 * Limpia los intentos después de login exitoso
 */
function clearAttempts($attemptFile, $clientIP) {
    $attempts = file_exists($attemptFile) ? json_decode(file_get_contents($attemptFile), true) : [];
    
    if (isset($attempts[$clientIP])) {
        unset($attempts[$clientIP]);
        file_put_contents($attemptFile, json_encode($attempts));
    }
}

// Verificar método de solicitud
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Método no permitido');
}

// Verificar si hay token CSRF (opcional, para mayor seguridad)
// if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
//     http_response_code(403);
//     exit('Token CSRF inválido');
// }

// Sanitizar entradas
$usuario = isset($_POST['usuario']) ? trim($_POST['usuario']) : '';
$contrasena = isset($_POST['contrasena']) ? $_POST['contrasena'] : '';

// Validaciones básicas
if (empty($usuario) || empty($contrasena)) {
    $_SESSION['login_error'] = 'Usuario y contraseña son requeridos';
    header('Location: login.html?error=1');
    exit;
}

// Verificar bloqueo por múltiples intentos
if (isLockedOut($attemptFile, $clientIP, $maxAttempts, $lockoutTime)) {
    $_SESSION['login_error'] = 'Demasiados intentos fallidos. Intente en 5 minutos.';
    header('Location: login.html?error=locked');
    exit;
}

// Leer credenciales almacenadas
$secretFile = __DIR__ . '/secret';
if (!file_exists($secretFile)) {
    error_log('Archivo secret no encontrado');
    $_SESSION['login_error'] = 'Error de configuración del sistema';
    header('Location: login.html?error=1');
    exit;
}

$contrasenaGuardada = trim(file_get_contents($secretFile));

// Verificar credenciales usando comparación segura
if (hash_equals($usuario, 'itspanama') && hash_equals($contrasena, $contrasenaGuardada)) {
    // Login exitoso
    clearAttempts($attemptFile, $clientIP);
    
    // Regenerar ID de sesión para prevenir fijación de sesión
    session_regenerate_id(true);
    
    // Establecer variables de sesión
    $_SESSION['logged_in'] = true;
    $_SESSION['username'] = $usuario;
    $_SESSION['login_time'] = time();
    
    // Redirigir al formulario
    header('Location: index.html');
    exit;
} else {
    // Login fallido
    logFailedAttempt($attemptFile, $clientIP);
    
    // Mensaje genérico para no revelar información
    $_SESSION['login_error'] = 'Credenciales incorrectas';
    header('Location: login.html?error=1');
    exit;
}
?>
