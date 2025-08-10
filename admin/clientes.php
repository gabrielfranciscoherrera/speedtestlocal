<?php
require_once '../includes/Database.php';

session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: panel.php');
    exit();
}

$db = new Database();
$clientes = $db->getAllClientes();

// Procesar acciones
if ($_POST['action'] ?? '' === 'toggle_status') {
    $clienteId = $_POST['cliente_id'] ?? 0;
    $nuevoEstado = $_POST['nuevo_estado'] ?? 0;
    
    try {
        $stmt = $db->getConnection()->prepare("UPDATE clientes SET activo = ? WHERE id = ?");
        $stmt->execute([$nuevoEstado, $clienteId]);
        header('Location: clientes.php?success=1');
        exit();
    } catch (Exception $e) {
        $error = 'Error actualizando cliente';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>👥 Gestión de Clientes - Panel ISP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .sidebar { min-height: 100vh; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .sidebar .nav-link { color: rgba(255,255,255,0.8); padding: 12px 20px; margin: 2px 0; border-radius: 8px; }
        .sidebar .nav-link:hover, .sidebar .nav-link.active { background: rgba(255,255,255,0.1); color: white; }
        .sidebar .nav-link i { margin-right: 10px; }
        .main-content { background: #f8f9fa; min-height: 100vh; }
        .card-modern { border-radius: 15px; border: none; box-shadow: 0 4px 20px rgba(0,0,0,0.08); }
        .table-modern { background: white; border-radius: 15px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.08); }
        .table-modern th { background: #f8f9fa; border: none; font-weight: 600; color: #495057; }
        .table-modern td { border: none; border-bottom: 1px solid #f1f3f4; vertical-align: middle; }
        .btn-modern { border-radius: 10px; padding: 10px 20px; font-weight: 500; border: none; }
        .search-box { background: white; border-radius: 15px; padding: 20px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); margin-bottom: 20px; }
    </style>
</head>
<body>
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
                        <a class="nav-link" href="panel.php">
                            <i class="bi bi-speedometer2"></i> Dashboard
                        </a>
                        <a class="nav-link active" href="clientes.php">
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
                        <a class="nav-link" href="panel.php?logout=1">
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
                            <h1 class="h3 mb-0">👥 Gestión de Clientes</h1>
                            <p class="text-muted">Administra todos los clientes del ISP</p>
                        </div>
                        <div class="d-flex gap-2">
                            <a href="nuevo_cliente.php" class="btn btn-primary btn-modern">
                                <i class="bi bi-plus-circle"></i> Nuevo Cliente
                            </a>
                            <a href="importar_clientes.php" class="btn btn-outline-success btn-modern">
                                <i class="bi bi-upload"></i> Importar
                            </a>
                        </div>
                    </div>

                    <!-- Alertas -->
                    <?php if (isset($_GET['success'])): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="bi bi-check-circle"></i> Cliente actualizado correctamente
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <!-- Búsqueda y Filtros -->
                    <div class="search-box">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                                    <input type="text" class="form-control" id="searchInput" placeholder="Buscar cliente...">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <select class="form-select" id="filterPlan">
                                    <option value="">Todos los planes</option>
                                    <option value="Plan Básico">Plan Básico</option>
                                    <option value="Plan Premium">Plan Premium</option>
                                    <option value="Plan Empresarial">Plan Empresarial</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <select class="form-select" id="filterStatus">
                                    <option value="">Todos los estados</option>
                                    <option value="1">Activo</option>
                                    <option value="0">Inactivo</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <button class="btn btn-outline-secondary w-100" onclick="limpiarFiltros()">
                                    <i class="bi bi-arrow-clockwise"></i> Limpiar
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Tabla de Clientes -->
                    <div class="card card-modern">
                        <div class="card-header bg-transparent border-0 d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Lista de Clientes (<?php echo count($clientes); ?>)</h5>
                            <div class="d-flex gap-2">
                                <button class="btn btn-sm btn-outline-primary" onclick="exportarClientes()">
                                    <i class="bi bi-download"></i> Exportar
                                </button>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-modern mb-0" id="clientesTable">
                                    <thead>
                                        <tr>
                                            <th>Cliente</th>
                                            <th>IP Asignada</th>
                                            <th>Plan</th>
                                            <th>Velocidad</th>
                                            <th>Estado</th>
                                            <th>Último Test</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($clientes as $cliente): ?>
                                        <tr data-cliente="<?php echo htmlspecialchars(strtolower($cliente['nombre'])); ?>" 
                                            data-plan="<?php echo htmlspecialchars($cliente['plan_contratado']); ?>"
                                            data-status="<?php echo $cliente['activo']; ?>">
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
                                            <td>
                                                <code class="bg-light px-2 py-1 rounded"><?php echo $cliente['ip_asignada']; ?></code>
                                            </td>
                                            <td>
                                                <span class="badge bg-light text-dark"><?php echo htmlspecialchars($cliente['plan_contratado']); ?></span>
                                            </td>
                                            <td>
                                                <strong><?php echo $cliente['velocidad_contratada_mbps']; ?> Mbps</strong>
                                            </td>
                                            <td>
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" 
                                                           <?php echo $cliente['activo'] ? 'checked' : ''; ?>
                                                           onchange="toggleClienteStatus(<?php echo $cliente['id']; ?>, this.checked)">
                                                    <label class="form-check-label">
                                                        <?php if ($cliente['activo']): ?>
                                                            <span class="badge bg-success">Activo</span>
                                                        <?php else: ?>
                                                            <span class="badge bg-danger">Inactivo</span>
                                                        <?php endif; ?>
                                                    </label>
                                                </div>
                                            </td>
                                            <td>
                                                <small class="text-muted">Hace 2 horas</small>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="cliente.php?id=<?php echo $cliente['id']; ?>" class="btn btn-outline-primary" title="Ver Detalles">
                                                        <i class="bi bi-eye"></i>
                                                    </a>
                                                    <a href="editar_cliente.php?id=<?php echo $cliente['id']; ?>" class="btn btn-outline-warning" title="Editar">
                                                        <i class="bi bi-pencil"></i>
                                                    </a>
                                                    <a href="tests_cliente.php?id=<?php echo $cliente['id']; ?>" class="btn btn-outline-info" title="Ver Tests">
                                                        <i class="bi bi-graph-up"></i>
                                                    </a>
                                                    <button class="btn btn-outline-danger" title="Eliminar" onclick="confirmarEliminar(<?php echo $cliente['id']; ?>)">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
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
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Búsqueda y filtros
        document.getElementById('searchInput').addEventListener('input', filtrarClientes);
        document.getElementById('filterPlan').addEventListener('change', filtrarClientes);
        document.getElementById('filterStatus').addEventListener('change', filtrarClientes);

        function filtrarClientes() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const planFilter = document.getElementById('filterPlan').value;
            const statusFilter = document.getElementById('filterStatus').value;
            const rows = document.querySelectorAll('#clientesTable tbody tr');

            rows.forEach(row => {
                const cliente = row.getAttribute('data-cliente');
                const plan = row.getAttribute('data-plan');
                const status = row.getAttribute('data-status');
                
                const matchesSearch = cliente.includes(searchTerm);
                const matchesPlan = !planFilter || plan === planFilter;
                const matchesStatus = !statusFilter || status === statusFilter;

                if (matchesSearch && matchesPlan && matchesStatus) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        function limpiarFiltros() {
            document.getElementById('searchInput').value = '';
            document.getElementById('filterPlan').value = '';
            document.getElementById('filterStatus').value = '';
            filtrarClientes();
        }

        function toggleClienteStatus(clienteId, nuevoEstado) {
            if (confirm('¿Estás seguro de cambiar el estado del cliente?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="toggle_status">
                    <input type="hidden" name="cliente_id" value="${clienteId}">
                    <input type="hidden" name="nuevo_estado" value="${nuevoEstado ? 1 : 0}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        function confirmarEliminar(clienteId) {
            if (confirm('¿Estás seguro de eliminar este cliente? Esta acción no se puede deshacer.')) {
                // Implementar eliminación
                console.log('Eliminando cliente:', clienteId);
            }
        }

        function exportarClientes() {
            // Implementar exportación
            alert('Función de exportación en desarrollo');
        }
    </script>
</body>
</html>
