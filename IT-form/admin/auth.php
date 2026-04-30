<?php
/**
 * Sistema de autenticación seguro para el panel administrativo
 * Maneja login, logout y verificación de sesiones
 */

session_start();

// Configuración de seguridad
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) ? 1 : 0);

require_once __DIR__ . '/../config/database.php';

/**
 * Verificar si el usuario está autenticado
 */
function isAuthenticated() {
    return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
}

/**
 * Verificar si el usuario es administrador
 */
function isAdmin() {
    return isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin';
}

/**
 * Redirigir si no está autenticado
 */
function requireAuth() {
    if (!isAuthenticated()) {
        header('Location: login.php');
        exit;
    }
}

/**
 * Redirigir si no es admin
 */
function requireAdmin() {
    requireAuth();
    if (!isAdmin()) {
        http_response_code(403);
        die("Acceso denegado. Se requieren privilegios de administrador.");
    }
}

/**
 * Registrar intento de login fallido
 */
function logFailedAttempt($db, $username) {
    try {
        $sql = "UPDATE usuarios SET intentos_fallidos = intentos_fallidos + 1 
                WHERE username = :username";
        $stmt = $db->prepare($sql);
        $stmt->execute([':username' => $username]);
    } catch (Exception $e) {
        error_log("Error al registrar intento fallido: " . $e->getMessage());
    }
}

/**
 * Resetear intentos fallidos después de login exitoso
 */
function resetFailedAttempts($db, $userId) {
    try {
        $sql = "UPDATE usuarios SET intentos_fallidos = 0, bloqueado_hasta = NULL, ultimo_acceso = NOW() 
                WHERE id = :id";
        $stmt = $db->prepare($sql);
        $stmt->execute([':id' => $userId]);
    } catch (Exception $e) {
        error_log("Error al resetear intentos: " . $e->getMessage());
    }
}

/**
 * Verificar si la cuenta está bloqueada
 */
function isAccountLocked($db, $username) {
    try {
        $sql = "SELECT bloqueado_hasta FROM usuarios WHERE username = :username";
        $stmt = $db->prepare($sql);
        $stmt->execute([':username' => $username]);
        $user = $stmt->fetch();
        
        if ($user && $user['bloqueado_hasta']) {
            $lockTime = strtotime($user['bloqueado_hasta']);
            if ($lockTime > time()) {
                return true;
            }
        }
        return false;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Bloquear cuenta temporalmente
 */
function lockAccount($db, $username) {
    try {
        $lockUntil = date('Y-m-d H:i:s', strtotime('+15 minutes'));
        $sql = "UPDATE usuarios SET bloqueado_hasta = :hasta WHERE username = :username";
        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':hasta' => $lockUntil,
            ':username' => $username
        ]);
    } catch (Exception $e) {
        error_log("Error al bloquear cuenta: " . $e->getMessage());
    }
}

/**
 * Procesar login
 */
function processLogin($username, $password) {
    try {
        $db = getDB();
        
        // Verificar si la cuenta está bloqueada
        if (isAccountLocked($db, $username)) {
            return ['success' => false, 'error' => 'Cuenta bloqueada temporalmente. Intente en 15 minutos.'];
        }
        
        // Buscar usuario
        $sql = "SELECT id, username, password_hash, email, nombre_completo, rol, activo 
                FROM usuarios WHERE username = :username";
        $stmt = $db->prepare($sql);
        $stmt->execute([':username' => $username]);
        $user = $stmt->fetch();
        
        if (!$user) {
            logFailedAttempt($db, $username);
            return ['success' => false, 'error' => 'Credenciales incorrectas'];
        }
        
        // Verificar si la cuenta está activa
        if (!$user['activo']) {
            return ['success' => false, 'error' => 'Cuenta desactivada. Contacte al administrador.'];
        }
        
        // Verificar contraseña
        if (!password_verify($password, $user['password_hash'])) {
            logFailedAttempt($db, $username);
            
            // Verificar si debe bloquearse
            $sqlCheck = "SELECT intentos_fallidos FROM usuarios WHERE username = :username";
            $stmtCheck = $db->prepare($sqlCheck);
            $stmtCheck->execute([':username' => $username]);
            $attempts = $stmtCheck->fetch();
            
            if ($attempts['intentos_fallidos'] >= 5) {
                lockAccount($db, $username);
                return ['success' => false, 'error' => 'Demasiados intentos fallidos. Cuenta bloqueada por 15 minutos.'];
            }
            
            return ['success' => false, 'error' => 'Credenciales incorrectas'];
        }
        
        // Login exitoso
        resetFailedAttempts($db, $user['id']);
        
        // Regenerar ID de sesión
        session_regenerate_id(true);
        
        // Establecer variables de sesión
        $_SESSION['logged_in'] = true;
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['nombre'] = $user['nombre_completo'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['rol'] = $user['rol'];
        $_SESSION['login_time'] = time();
        
        return [
            'success' => true, 
            'message' => 'Login exitoso',
            'redirect' => 'index.php'
        ];
        
    } catch (PDOException $e) {
        error_log("Error de login: " . $e->getMessage());
        return ['success' => false, 'error' => 'Error del sistema'];
    }
}

/**
 * Cerrar sesión
 */
function logout() {
    session_unset();
    session_destroy();
    
    // Eliminar cookie de sesión
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }
}

// Manejar solicitudes POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'login') {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (empty($username) || empty($password)) {
            echo json_encode(['success' => false, 'error' => 'Usuario y contraseña son requeridos']);
            exit;
        }
        
        $result = processLogin($username, $password);
        echo json_encode($result);
        exit;
    }
    
    if ($action === 'logout') {
        logout();
        header('Location: login.php');
        exit;
    }
}
?>
