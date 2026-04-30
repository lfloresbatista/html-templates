<?php
/**
 * Panel Administrativo - Dashboard Principal
 * Gestión de usuarios, configuraciones y servicios
 */

session_start();
require_once 'auth.php';

// Verificar autenticación
requireAuth();

$db = getDB();

// Obtener estadísticas
try {
    // Total de servicios
    $stmt = $db->query("SELECT COUNT(*) as total FROM servicios");
    $totalServicios = $stmt->fetch()['total'];
    
    // Servicios por estado
    $stmt = $db->query("SELECT estado, COUNT(*) as cantidad FROM servicios GROUP BY estado");
    $serviciosPorEstado = $stmt->fetchAll();
    
    // Total de usuarios
    $stmt = $db->query("SELECT COUNT(*) as total FROM usuarios WHERE activo = 1");
    $totalUsuarios = $stmt->fetch()['total'];
    
    // Servicios recientes
    $stmt = $db->query("SELECT s.*, u.nombre_completo as tecnico 
                        FROM servicios s 
                        LEFT JOIN usuarios u ON s.usuario_id = u.id 
                        ORDER BY s.fecha_guardado DESC LIMIT 10");
    $serviciosRecientes = $stmt->fetchAll();
    
    // Configuración actual
    $stmt = $db->query("SELECT * FROM configuracion LIMIT 1");
    $configuracion = $stmt->fetch();
    
} catch (PDOException $e) {
    error_log("Error al obtener estadísticas: " . $e->getMessage());
    $error = "Error al cargar datos del dashboard";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Panel Administrativo</title>
    <link rel="stylesheet" href="../styles.css">
    <style>
        :root {
            --sidebar-width: 260px;
            --header-height: 70px;
        }
        
        * {
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Arial', sans-serif;
            background: #f5f6fa;
            margin: 0;
            display: flex;
            min-height: 100vh;
        }
        
        /* Sidebar */
        .sidebar {
            width: var(--sidebar-width);
            background: var(--color-primary);
            color: white;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            transition: all 0.3s ease;
        }
        
        .sidebar-header {
            padding: 25px 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            text-align: center;
        }
        
        .sidebar-header img {
            max-width: 150px;
            height: auto;
        }
        
        .sidebar-menu {
            list-style: none;
            padding: 20px 0;
            margin: 0;
        }
        
        .sidebar-menu li {
            margin: 5px 0;
        }
        
        .sidebar-menu a {
            display: block;
            padding: 15px 25px;
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            transition: all 0.3s ease;
            border-left: 4px solid transparent;
        }
        
        .sidebar-menu a:hover,
        .sidebar-menu a.active {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            border-left-color: var(--color-secondary);
        }
        
        .sidebar-menu a i {
            margin-right: 12px;
            width: 20px;
            display: inline-block;
        }
        
        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: var(--sidebar-width);
            transition: all 0.3s ease;
        }
        
        /* Header */
        .top-header {
            height: var(--header-height);
            background: white;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 30px;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        .header-title h1 {
            margin: 0;
            font-size: 1.5rem;
            color: #333;
        }
        
        .header-actions {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .theme-toggle {
            background: #f5f6fa;
            border: none;
            padding: 10px 15px;
            border-radius: 20px;
            cursor: pointer;
            font-size: 1.2rem;
            transition: all 0.3s ease;
        }
        
        .theme-toggle:hover {
            background: #e0e0e0;
        }
        
        .user-menu {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .user-info {
            text-align: right;
        }
        
        .user-info .name {
            font-weight: 600;
            color: #333;
            display: block;
        }
        
        .user-info .role {
            font-size: 0.85rem;
            color: #666;
            text-transform: uppercase;
        }
        
        .btn-logout {
            background: #e74c3c;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-logout:hover {
            background: #c0392b;
        }
        
        /* Dashboard Content */
        .dashboard-content {
            padding: 30px;
        }
        
        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        }
        
        .stat-card .icon {
            font-size: 2.5rem;
            margin-bottom: 15px;
        }
        
        .stat-card .value {
            font-size: 2rem;
            font-weight: 700;
            color: #333;
            margin-bottom: 5px;
        }
        
        .stat-card .label {
            color: #666;
            font-size: 0.95rem;
        }
        
        .stat-card.primary { border-left: 4px solid var(--color-primary); }
        .stat-card.success { border-left: 4px solid var(--color-secondary); }
        .stat-card.warning { border-left: 4px solid #f39c12; }
        .stat-card.info { border-left: 4px solid #3498db; }
        
        /* Recent Services Table */
        .card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 25px;
        }
        
        .card-header {
            padding: 20px 25px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .card-header h2 {
            margin: 0;
            font-size: 1.2rem;
            color: #333;
        }
        
        .card-body {
            padding: 25px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        table th,
        table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #333;
        }
        
        table tr:hover {
            background: #f8f9fa;
        }
        
        .badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        
        .badge-pendiente { background: #ffeaa7; color: #d68910; }
        .badge-en_proceso { background: #74b9ff; color: #0984e3; }
        .badge-completado { background: #55efc4; color: #00b894; }
        .badge-cancelado { background: #fab1a0; color: #d63031; }
        
        /* Dark Mode */
        body.dark-mode {
            background: #1a1a2e;
        }
        
        body.dark-mode .top-header,
        body.dark-mode .card,
        body.dark-mode .stat-card {
            background: #2d2d44;
        }
        
        body.dark-mode .card-header h2,
        body.dark-mode .stat-card .value {
            color: #fff;
        }
        
        body.dark-mode table th {
            background: #1a1a2e;
            color: #fff;
        }
        
        body.dark-mode table td {
            color: #ccc;
        }
        
        body.dark-mode table tr:hover {
            background: #1a1a2e;
        }
        
        body.dark-mode .user-info .name {
            color: #fff;
        }
        
        body.dark-mode .user-info .role {
            color: #aaa;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                z-index: 1000;
            }
            
            .sidebar.active {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <img src="../logo.png" alt="ITS Panama">
        </div>
        <ul class="sidebar-menu">
            <li><a href="index.php" class="active"><i>📊</i> Dashboard</a></li>
            <li><a href="servicios.php"><i>📋</i> Servicios</a></li>
            <li><a href="usuarios.php"><i>👥</i> Usuarios</a></li>
            <li><a href="configuracion.php"><i>⚙️</i> Configuración</a></li>
            <li><a href="auditoria.php"><i>📝</i> Auditoría</a></li>
            <li><a href="../index.html" target="_blank"><i>🌐</i> Ver Formulario</a></li>
        </ul>
    </aside>
    
    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Header -->
        <header class="top-header">
            <div class="header-title">
                <h1>Dashboard</h1>
            </div>
            <div class="header-actions">
                <button class="theme-toggle" id="themeToggle">🌙</button>
                <div class="user-menu">
                    <div class="user-info">
                        <span class="name"><?php echo htmlspecialchars($_SESSION['nombre']); ?></span>
                        <span class="role"><?php echo htmlspecialchars($_SESSION['rol']); ?></span>
                    </div>
                    <form action="auth.php" method="POST" style="display:inline;">
                        <input type="hidden" name="action" value="logout">
                        <button type="submit" class="btn-logout">Cerrar Sesión</button>
                    </form>
                </div>
            </div>
        </header>
        
        <!-- Dashboard Content -->
        <div class="dashboard-content">
            <!-- Stats Cards -->
            <div class="stats-grid">
                <div class="stat-card primary">
                    <div class="icon">📋</div>
                    <div class="value"><?php echo $totalServicios ?? 0; ?></div>
                    <div class="label">Total Servicios</div>
                </div>
                <div class="stat-card success">
                    <div class="icon">👥</div>
                    <div class="value"><?php echo $totalUsuarios ?? 0; ?></div>
                    <div class="label">Usuarios Activos</div>
                </div>
                <div class="stat-card warning">
                    <div class="icon">⏳</div>
                    <div class="value">
                        <?php 
                        $pendientes = 0;
                        foreach ($serviciosPorEstado ?? [] as $estado) {
                            if ($estado['estado'] === 'pendiente') {
                                $pendientes = $estado['cantidad'];
                            }
                        }
                        echo $pendientes;
                        ?>
                    </div>
                    <div class="label">Pendientes</div>
                </div>
                <div class="stat-card info">
                    <div class="icon">✅</div>
                    <div class="value">
                        <?php 
                        $completados = 0;
                        foreach ($serviciosPorEstado ?? [] as $estado) {
                            if ($estado['estado'] === 'completado') {
                                $completados = $estado['cantidad'];
                            }
                        }
                        echo $completados;
                        ?>
                    </div>
                    <div class="label">Completados</div>
                </div>
            </div>
            
            <!-- Recent Services -->
            <div class="card">
                <div class="card-header">
                    <h2>Servicios Recientes</h2>
                    <a href="servicios.php" style="color: var(--color-primary); text-decoration: none; font-weight: 600;">Ver todos →</a>
                </div>
                <div class="card-body">
                    <table>
                        <thead>
                            <tr>
                                <th>Número</th>
                                <th>Cliente</th>
                                <th>Ticket</th>
                                <th>Técnico</th>
                                <th>Estado</th>
                                <th>Fecha</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($serviciosRecientes)): ?>
                                <?php foreach ($serviciosRecientes as $servicio): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($servicio['numero_secuencia']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($servicio['cliente']); ?></td>
                                    <td><?php echo htmlspecialchars($servicio['ticket']); ?></td>
                                    <td><?php echo htmlspecialchars($servicio['tecnico'] ?? 'N/A'); ?></td>
                                    <td>
                                        <span class="badge badge-<?php echo htmlspecialchars($servicio['estado']); ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $servicio['estado'])); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($servicio['fecha_guardado'])); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" style="text-align: center; padding: 40px;">
                                        No hay servicios registrados
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Toggle tema claro/oscuro
        const themeToggle = document.getElementById('themeToggle');
        const body = document.body;
        
        const savedTheme = localStorage.getItem('adminTheme');
        if (savedTheme === 'dark') {
            body.classList.add('dark-mode');
            themeToggle.textContent = '☀️';
        }
        
        themeToggle.addEventListener('click', () => {
            body.classList.toggle('dark-mode');
            const isDark = body.classList.contains('dark-mode');
            themeToggle.textContent = isDark ? '☀️' : '🌙';
            localStorage.setItem('adminTheme', isDark ? 'dark' : 'light');
        });
    </script>
</body>
</html>
