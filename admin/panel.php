<?php
require_once '../includes/Database.php';

// Verificación básica de seguridad (en producción usar autenticación real)
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    // Redirigir a login o mostrar formulario simple
    if ($_POST['password'] === 'admin123') { // Cambiar por contraseña real
        $_SESSION['admin_logged_in'] = true;
    } elseif ($_POST['password'] !== '') {
        $error = 'Contraseña incorrecta';
    }
}

$db = new Database();
$estadisticasISP = $db->getEstadisticasISP();
$clientes = $db->getAllClientes();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🏢 Panel ISP - Speed Test Server</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .sidebar { min-height: 100vh; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .sidebar .nav-link { color: rgba(255,255,255,0.8); padding: 12px 20px; margin: 2px 0; border-radius: 8px; }
        .sidebar .nav-link:hover, .sidebar .nav-link.active { background: rgba(255,255,255,0.1); color: white; }
        .sidebar .nav-link i { margin-right: 10px; }
        .main-content { background: #f8f9fa; min-height: 100vh; }
        .stat-card { background: white; border-radius: 15px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); border: none; }
        .stat-icon { width: 60px; height: 60px; border-radius: 15px; display: flex; align-items: center; justify-content: center; font-size: 24px; }
        .bg-gradient-primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .bg-gradient-success { background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); }
        .bg-gradient-warning { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
        .bg-gradient-info { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); }
        .table-modern { background: white; border-radius: 15px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.08); }
        .table-modern th { background: #f8f9fa; border: none; font-weight: 600; color: #495057; }
        .table-modern td { border: none; border-bottom: 1px solid #f1f3f4; vertical-align: middle; }
        .btn-modern { border-radius: 10px; padding: 10px 20px; font-weight: 500; border: none; }
        .card-modern { border-radius: 15px; border: none; box-shadow: 0 4px 20px rgba(0,0,0,0.08); }
        .login-container { min-height: 100vh; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); display: flex; align-items: center; justify-content: center; }
        .login-card { background: white; border-radius: 20px; box-shadow: 0 20px 40px rgba(0,0,0,0.1); }
    </style>
</head>
<body>
    <?php if (!isset($_SESSION['admin_logged_in'])): ?>
        <div class="login-container">
            <div class="login-card p-5" style="width: 400px;">
                <div class="text-center mb-4">
                    <i class="bi bi-building text-primary" style="font-size: 3rem;"></i>
                    <h2 class="mt-3 text-dark">Panel ISP</h2>
                    <p class="text-muted">Acceso administrativo</p>
                </div>
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                <form method="POST">
                    <div class="mb-3">
                        <label for="password" class="form-label">Contraseña</label>
                        <input type="password" class="form-control form-control-lg" id="password" name="password" required>
                    </div>
                    <button type="submit" class="btn btn-primary btn-modern w-100 btn-lg">Acceder</button>
                </form>
            </div>
        </div>
    <?php else: ?>
        <div class="container-fluid">
            <div class="row">
                <!-- Sidebar -->
                <div class="col-md-3 col-lg-2 px-0">
                    <div class="sidebar p-3">
                        <div class="text-center mb-4">
                            <i class="bi bi-building text-white" style="font-size: 2rem;"></i>
                            <h5 class="text-white mt-2">ISP Admin</h5>
                        </div>
                        <nav class="nav flex-column">
                            <a class="nav-link active" href="panel.php">
                                <i class="bi bi-speedometer2"></i> Dashboard
                            </a>
                            <a class="nav-link" href="clientes.php">
                                <i class="bi bi-people"></i> Clientes
                            </a>
                            <a class="nav-link" href="tests.php">
                                <i class="bi bi-graph-up"></i> Tests
                            </a>
                            <a class="nav-link" href="reportes.php">
                                <i class="bi bi-file-earmark-text"></i> Reportes
                            </a>
                            <a class="nav-link" href="configuracion.php">
                                <i class="bi bi-gear"></i> Configuración
                            </a>
                            <hr class="text-white-50">
                            <a class="nav-link" href="?logout=1">
                                <i class="bi bi-box-arrow-right"></i> Salir
                            </a>
                        </nav>
                    </div>
                </div>

                <!-- Main Content -->
                <div class="col-md-9 col-lg-10">
                    <div class="main-content p-4">
                        <!-- Header -->
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div>
                                <h1 class="h3 mb-0">Dashboard</h1>
                                <p class="text-muted">Resumen general del sistema</p>
                            </div>
                            <div class="d-flex gap-2">
                                <a href="nuevo_cliente.php" class="btn btn-primary btn-modern">
                                    <i class="bi bi-plus-circle"></i> Nuevo Cliente
                                </a>
                                <a href="reportes.php" class="btn btn-outline-primary btn-modern">
                                    <i class="bi bi-download"></i> Exportar
                                </a>
                            </div>
                        </div>

                        <!-- Estadísticas -->
                        <div class="row mb-4">
                            <div class="col-xl-3 col-md-6 mb-3">
                                <div class="stat-card p-4">
                                    <div class="d-flex align-items-center">
                                        <div class="stat-icon bg-gradient-primary text-white me-3">
                                            <i class="bi bi-people"></i>
                                        </div>
                                        <div>
                                            <h3 class="mb-0"><?php echo $estadisticasISP['total_clientes'] ?? 0; ?></h3>
                                            <p class="text-muted mb-0">Clientes Activos</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-3 col-md-6 mb-3">
                                <div class="stat-card p-4">
                                    <div class="d-flex align-items-center">
                                        <div class="stat-icon bg-gradient-success text-white me-3">
                                            <i class="bi bi-speedometer2"></i>
                                        </div>
                                        <div>
                                            <h3 class="mb-0"><?php echo $estadisticasISP['total_tests_hoy'] ?? 0; ?></h3>
                                            <p class="text-muted mb-0">Tests Hoy</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-3 col-md-6 mb-3">
                                <div class="stat-card p-4">
                                    <div class="d-flex align-items-center">
                                        <div class="stat-icon bg-gradient-warning text-white me-3">
                                            <i class="bi bi-arrow-up"></i>
                                        </div>
                                        <div>
                                            <h3 class="mb-0"><?php echo round($estadisticasISP['velocidad_promedio_general'] ?? 0, 1); ?></h3>
                                            <p class="text-muted mb-0">Velocidad Promedio</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-3 col-md-6 mb-3">
                                <div class="stat-card p-4">
                                    <div class="d-flex align-items-center">
                                        <div class="stat-icon bg-gradient-info text-white me-3">
                                            <i class="bi bi-clock"></i>
                                        </div>
                                        <div>
                                            <h3 class="mb-0"><?php echo round($estadisticasISP['latencia_promedio_general'] ?? 0, 1); ?></h3>
                                            <p class="text-muted mb-0">Latencia Promedio</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Clientes Recientes -->
                        <div class="row">
                            <div class="col-12">
                                <div class="card card-modern">
                                    <div class="card-header bg-transparent border-0 d-flex justify-content-between align-items-center">
                                        <h5 class="mb-0">👥 Clientes Activos</h5>
                                        <a href="clientes.php" class="btn btn-sm btn-outline-primary">Ver Todos</a>
                                    </div>
                                    <div class="card-body p-0">
                                        <div class="table-responsive">
                                            <table class="table table-modern mb-0">
                                                <thead>
                                                    <tr>
                                                        <th>Cliente</th>
                                                        <th>IP</th>
                                                        <th>Plan</th>
                                                        <th>Velocidad</th>
                                                        <th>Estado</th>
                                                        <th>Acciones</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach (array_slice($clientes, 0, 5) as $cliente): ?>
                                                    <tr>
                                                        <td>
                                                            <div class="d-flex align-items-center">
                                                                <div class="avatar-sm bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                                                                    <?php echo strtoupper(substr($cliente['nombre'], 0, 1)); ?>
                                                                </div>
                                                                <div>
                                                                    <h6 class="mb-0"><?php echo htmlspecialchars($cliente['nombre']); ?></h6>
                                                                    <small class="text-muted">ID: <?php echo $cliente['id']; ?></small>
                                                                </div>
                                                            </div>
                                                        </td>
                                                        <td><code class="bg-light px-2 py-1 rounded"><?php echo $cliente['ip_asignada']; ?></code></td>
                                                        <td>
                                                            <span class="badge bg-light text-dark"><?php echo htmlspecialchars($cliente['plan_contratado']); ?></span>
                                                        </td>
                                                        <td>
                                                            <strong><?php echo $cliente['velocidad_contratada_mbps']; ?> Mbps</strong>
                                                        </td>
                                                        <td>
                                                            <?php if ($cliente['activo']): ?>
                                                                <span class="badge bg-success">Activo</span>
                                                            <?php else: ?>
                                                                <span class="badge bg-danger">Inactivo</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <div class="btn-group btn-group-sm">
                                                                <a href="cliente.php?id=<?php echo $cliente['id']; ?>" class="btn btn-outline-primary">
                                                                    <i class="bi bi-eye"></i>
                                                                </a>
                                                                <a href="editar_cliente.php?id=<?php echo $cliente['id']; ?>" class="btn btn-outline-warning">
                                                                    <i class="bi bi-pencil"></i>
                                                                </a>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Actividad Reciente -->
                        <div class="row mt-4">
                            <div class="col-12">
                                <div class="card card-modern">
                                    <div class="card-header bg-transparent border-0">
                                        <h5 class="mb-0">📊 Actividad Reciente</h5>
                                    </div>
                                    <div class="card-body">
                                        <div id="realtime-monitor">
                                            <div class="text-center py-4">
                                                <div class="spinner-border text-primary" role="status">
                                                    <span class="visually-hidden">Cargando...</span>
                                                </div>
                                                <p class="mt-2 text-muted">Actualizando datos en tiempo real...</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
        <script>
            // Actualización en tiempo real cada 30 segundos
            setInterval(() => {
                fetch('api/estadisticas_isp.php')
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Actualizar estadísticas en tiempo real
                            console.log('Datos actualizados:', data);
                        }
                    })
                    .catch(error => console.error('Error:', error));
            }, 30000);
        </script>
    <?php endif; ?>
</body>
</html>
