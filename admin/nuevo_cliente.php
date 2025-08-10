<?php
require_once '../includes/Database.php';

session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: panel.php');
    exit();
}

$db = new Database();
$error = '';
$success = '';

// Procesar formulario
if ($_POST) {
    $nombre = trim($_POST['nombre'] ?? '');
    $ipAsignada = trim($_POST['ip_asignada'] ?? '');
    $planContratado = trim($_POST['plan_contratado'] ?? '');
    $velocidadContratada = floatval($_POST['velocidad_contratada_mbps'] ?? 0);
    
    // Validaciones
    if (empty($nombre) || empty($ipAsignada) || empty($planContratado) || $velocidadContratada <= 0) {
        $error = 'Todos los campos son obligatorios y la velocidad debe ser mayor a 0';
    } elseif (!filter_var($ipAsignada, FILTER_VALIDATE_IP)) {
        $error = 'La IP asignada no es válida';
    } else {
        try {
            // Verificar si la IP ya existe
            $clienteExistente = $db->getClienteByIP($ipAsignada);
            if ($clienteExistente) {
                $error = 'La IP ' . $ipAsignada . ' ya está asignada al cliente: ' . $clienteExistente['nombre'];
            } else {
                // Insertar nuevo cliente
                $stmt = $db->getConnection()->prepare("
                    INSERT INTO clientes (nombre, ip_asignada, plan_contratado, velocidad_contratada_mbps) 
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->execute([$nombre, $ipAsignada, $planContratado, $velocidadContratada]);
                
                $success = 'Cliente agregado correctamente';
                // Limpiar formulario
                $_POST = [];
            }
        } catch (Exception $e) {
            $error = 'Error al agregar cliente: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>➕ Nuevo Cliente - Panel ISP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .sidebar { min-height: 100vh; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .sidebar .nav-link { color: rgba(255,255,255,0.8); padding: 12px 20px; margin: 2px 0; border-radius: 8px; }
        .sidebar .nav-link:hover, .sidebar .nav-link.active { background: rgba(255,255,255,0.1); color: white; }
        .sidebar .nav-link i { margin-right: 10px; }
        .main-content { background: #f8f9fa; min-height: 100vh; }
        .card-modern { border-radius: 15px; border: none; box-shadow: 0 4px 20px rgba(0,0,0,0.08); }
        .form-control:focus { border-color: #667eea; box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25); }
        .btn-modern { border-radius: 10px; padding: 10px 20px; font-weight: 500; border: none; }
        .plan-card { border: 2px solid transparent; cursor: pointer; transition: all 0.3s ease; }
        .plan-card:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(0,0,0,0.15); }
        .plan-card.selected { border-color: #667eea; background: rgba(102, 126, 234, 0.05); }
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
                            <h1 class="h3 mb-0">➕ Nuevo Cliente</h1>
                            <p class="text-muted">Agregar un nuevo cliente al sistema</p>
                        </div>
                        <div class="d-flex gap-2">
                            <a href="clientes.php" class="btn btn-outline-secondary btn-modern">
                                <i class="bi bi-arrow-left"></i> Volver
                            </a>
                        </div>
                    </div>

                    <!-- Alertas -->
                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="bi bi-exclamation-triangle"></i> <?php echo $error; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="bi bi-check-circle"></i> <?php echo $success; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <!-- Formulario -->
                    <div class="row">
                        <div class="col-lg-8">
                            <div class="card card-modern">
                                <div class="card-header bg-transparent border-0">
                                    <h5 class="mb-0">📋 Información del Cliente</h5>
                                </div>
                                <div class="card-body">
                                    <form method="POST" id="clienteForm">
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label for="nombre" class="form-label">Nombre del Cliente *</label>
                                                <input type="text" class="form-control" id="nombre" name="nombre" 
                                                       value="<?php echo htmlspecialchars($_POST['nombre'] ?? ''); ?>" required>
                                                <div class="form-text">Nombre completo o razón social</div>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label for="ip_asignada" class="form-label">IP Asignada *</label>
                                                <input type="text" class="form-control" id="ip_asignada" name="ip_asignada" 
                                                       value="<?php echo htmlspecialchars($_POST['ip_asignada'] ?? ''); ?>" 
                                                       placeholder="192.168.1.100" required>
                                                <div class="form-text">IP única para este cliente</div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label for="plan_contratado" class="form-label">Plan Contratado *</label>
                                                <select class="form-select" id="plan_contratado" name="plan_contratado" required>
                                                    <option value="">Seleccionar plan...</option>
                                                    <option value="Plan Básico" <?php echo ($_POST['plan_contratado'] ?? '') === 'Plan Básico' ? 'selected' : ''; ?>>Plan Básico</option>
                                                    <option value="Plan Premium" <?php echo ($_POST['plan_contratado'] ?? '') === 'Plan Premium' ? 'selected' : ''; ?>>Plan Premium</option>
                                                    <option value="Plan Empresarial" <?php echo ($_POST['plan_contratado'] ?? '') === 'Plan Empresarial' ? 'selected' : ''; ?>>Plan Empresarial</option>
                                                    <option value="Plan Personalizado" <?php echo ($_POST['plan_contratado'] ?? '') === 'Plan Personalizado' ? 'selected' : ''; ?>>Plan Personalizado</option>
                                                </select>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label for="velocidad_contratada_mbps" class="form-label">Velocidad Contratada (Mbps) *</label>
                                                <input type="number" class="form-control" id="velocidad_contratada_mbps" 
                                                       name="velocidad_contratada_mbps" 
                                                       value="<?php echo htmlspecialchars($_POST['velocidad_contratada_mbps'] ?? ''); ?>" 
                                                       min="1" max="10000" step="0.1" required>
                                                <div class="form-text">Velocidad en Megabits por segundo</div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-12 mb-3">
                                                <label for="notas" class="form-label">Notas Adicionales</label>
                                                <textarea class="form-control" id="notas" name="notas" rows="3" 
                                                          placeholder="Información adicional sobre el cliente..."><?php echo htmlspecialchars($_POST['notas'] ?? ''); ?></textarea>
                                            </div>
                                        </div>

                                        <hr class="my-4">

                                        <div class="d-flex justify-content-between">
                                            <button type="button" class="btn btn-outline-secondary btn-modern" onclick="limpiarFormulario()">
                                                <i class="bi bi-arrow-clockwise"></i> Limpiar
                                            </button>
                                            <button type="submit" class="btn btn-primary btn-modern">
                                                <i class="bi bi-check-circle"></i> Guardar Cliente
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- Panel de Planes -->
                        <div class="col-lg-4">
                            <div class="card card-modern">
                                <div class="card-header bg-transparent border-0">
                                    <h5 class="mb-0">📊 Planes Disponibles</h5>
                                </div>
                                <div class="card-body">
                                    <div class="plan-card p-3 mb-3 rounded" onclick="seleccionarPlan('Plan Básico', 50)">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <h6 class="mb-1">Plan Básico</h6>
                                                <small class="text-muted">Ideal para uso doméstico</small>
                                            </div>
                                            <span class="badge bg-primary">50 Mbps</span>
                                        </div>
                                    </div>
                                    
                                    <div class="plan-card p-3 mb-3 rounded" onclick="seleccionarPlan('Plan Premium', 100)">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <h6 class="mb-1">Plan Premium</h6>
                                                <small class="text-muted">Para familias y teletrabajo</small>
                                            </div>
                                            <span class="badge bg-success">100 Mbps</span>
                                        </div>
                                    </div>
                                    
                                    <div class="plan-card p-3 mb-3 rounded" onclick="seleccionarPlan('Plan Empresarial', 500)">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <h6 class="mb-1">Plan Empresarial</h6>
                                                <small class="text-muted">Para empresas y oficinas</small>
                                            </div>
                                            <span class="badge bg-warning">500 Mbps</span>
                                        </div>
                                    </div>

                                    <div class="alert alert-info mt-3">
                                        <i class="bi bi-info-circle"></i>
                                        <strong>Tip:</strong> Haz clic en un plan para autocompletar el formulario
                                    </div>
                                </div>
                            </div>

                            <!-- Validación de IP -->
                            <div class="card card-modern mt-3">
                                <div class="card-header bg-transparent border-0">
                                    <h6 class="mb-0">🔍 Validación de IP</h6>
                                </div>
                                <div class="card-body">
                                    <div id="ipValidation">
                                        <small class="text-muted">Ingresa una IP para validar</small>
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
        // Seleccionar plan
        function seleccionarPlan(plan, velocidad) {
            document.getElementById('plan_contratado').value = plan;
            document.getElementById('velocidad_contratada_mbps').value = velocidad;
            
            // Actualizar selección visual
            document.querySelectorAll('.plan-card').forEach(card => {
                card.classList.remove('selected');
            });
            event.currentTarget.classList.add('selected');
        }

        // Validar IP en tiempo real
        document.getElementById('ip_asignada').addEventListener('input', function() {
            const ip = this.value;
            const validationDiv = document.getElementById('ipValidation');
            
            if (ip) {
                const isValid = /^(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$/.test(ip);
                
                if (isValid) {
                    validationDiv.innerHTML = '<span class="text-success"><i class="bi bi-check-circle"></i> IP válida</span>';
                } else {
                    validationDiv.innerHTML = '<span class="text-danger"><i class="bi bi-x-circle"></i> Formato de IP inválido</span>';
                }
            } else {
                validationDiv.innerHTML = '<small class="text-muted">Ingresa una IP para validar</small>';
            }
        });

        // Limpiar formulario
        function limpiarFormulario() {
            if (confirm('¿Estás seguro de limpiar el formulario?')) {
                document.getElementById('clienteForm').reset();
                document.querySelectorAll('.plan-card').forEach(card => {
                    card.classList.remove('selected');
                });
                document.getElementById('ipValidation').innerHTML = '<small class="text-muted">Ingresa una IP para validar</small>';
            }
        }

        // Validación del formulario
        document.getElementById('clienteForm').addEventListener('submit', function(e) {
            const nombre = document.getElementById('nombre').value.trim();
            const ip = document.getElementById('ip_asignada').value.trim();
            const plan = document.getElementById('plan_contratado').value;
            const velocidad = document.getElementById('velocidad_contratada_mbps').value;

            if (!nombre || !ip || !plan || !velocidad) {
                e.preventDefault();
                alert('Por favor completa todos los campos obligatorios');
                return false;
            }

            if (velocidad <= 0) {
                e.preventDefault();
                alert('La velocidad debe ser mayor a 0');
                return false;
            }
        });
    </script>
</body>
</html>
