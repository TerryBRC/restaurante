
<?php
require_once dirname(__DIR__, 2) . '/config/Session.php';
require_once dirname(__DIR__, 2) . '/models/ConfigModel.php';
Session::init();
$userRole = Session::getUserRole();
$nombreCompleto = Session::get('nombre_completo');
$currentPage = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
$currentPage = str_replace('restaurante/', '', $currentPage);
require_once dirname(__DIR__, 2) . '/config/base_url.php';
$configModel = new ConfigModel();
$nombreApp = $configModel->get('nombre_app');
?>

<?php if (Session::isLoggedIn()): ?>

<nav class="h-100">
    <div class="position-sticky h-100">
        <!-- Header de usuario y app se movió a header.php -->
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link text-white <?php echo strpos($currentPage, 'user/perfil') !== false ? 'active' : ''; ?>" 
                    href="<?php echo BASE_URL; ?>user/perfil">
                    <i class="bi bi-person-circle"></i>
                    Mi Perfil
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-white <?php echo $currentPage === '' || $currentPage === 'index.php' ? 'active' : ''; ?>" 
                   href="<?php echo BASE_URL; ?>">
                    <i class="bi bi-speedometer2"></i>
                    Dashboard
                </a>
            </li>

            <?php if (in_array($userRole, ['Administrador', 'Cajero'])): ?>
            <li class="nav-item">
                <a class="nav-link text-white <?php echo strpos($currentPage, 'caja/apertura') !== false ? 'active' : ''; ?>" 
                   href="<?php echo BASE_URL; ?>caja/apertura">
                    <i class="bi bi-box-arrow-in-down"></i>
                    Apertura de Caja
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-white <?php echo strpos($currentPage, 'caja/cierre') !== false ? 'active' : ''; ?>" 
                   href="<?php echo BASE_URL; ?>caja/cierre">
                    <i class="bi bi-box-arrow-up"></i>
                    Cierre de Caja
                </a>
            </li>
            <?php endif; ?>

            <?php if (in_array($userRole, ['Administrador', 'Cajero'])): ?>
            <li class="nav-item">
                <a class="nav-link text-white <?php echo strpos($currentPage, 'movimientos') !== false ? 'active' : ''; ?>" 
                   href="<?php echo BASE_URL; ?>movimientos">
                    <i class="bi bi-journal-arrow-up"></i>
                    Movimientos
                </a>
            </li>
            <?php endif; ?>

            <?php if ($userRole === 'Administrador'): ?>
            <li class="nav-item">
                     <a class="nav-link text-white <?php echo strpos($currentPage, 'empleados') !== false ? 'active' : ''; ?>" 
                         href="<?php echo BASE_URL; ?>empleados">
                    <i class="bi bi-people"></i>
                    Empleados
                </a>
            </li>
            <li class="nav-item">
                     <a class="nav-link text-white <?php echo strpos($currentPage, 'productos') !== false ? 'active' : ''; ?>" 
                         href="<?php echo BASE_URL; ?>productos">
                    <i class="bi bi-box"></i>
                    Productos
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-white <?php echo strpos($currentPage, 'configuracion') !== false ? 'active' : ''; ?>" 
                    href="<?php echo BASE_URL; ?>configuracion">
                    <i class="bi bi-gear"></i>
                    Configuración
                </a>
            </li>
            <?php endif; ?>

            <?php if (in_array($userRole, ['Administrador', 'Mesero','Cajero'])): ?>
            <li class="nav-item">
                     <a class="nav-link text-white <?php echo strpos($currentPage, 'mesas') !== false ? 'active' : ''; ?>" 
                         href="<?php echo BASE_URL; ?>mesas">
                    <i class="bi bi-grid"></i>
                    Mesas
                </a>
            </li>
            <li class="nav-item">
                     <a class="nav-link text-white <?php echo strpos($currentPage, 'comandas') !== false ? 'active' : ''; ?>" 
                         href="<?php echo BASE_URL; ?>comandas">
                    <i class="bi bi-receipt"></i>
                    Comandas
                </a>
            </li>
            <?php endif; ?>

            <?php if (in_array($userRole, ['Administrador', 'Cajero'])): ?>
            <li class="nav-item">
                     <a class="nav-link text-white <?php echo strpos($currentPage, 'ventas') !== false ? 'active' : ''; ?>" 
                         href="<?php echo BASE_URL; ?>ventas">
                   <i class="bi bi-cash-coin"></i>
                   Ventas
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-white <?php echo strpos($currentPage, 'pedidos') !== false ? 'active' : ''; ?>" 
                   href="<?php echo BASE_URL; ?>pedidos">
                    <i class="bi bi-bag-check"></i>
                    Pedidos
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-white <?php echo strpos(
                    $currentPage, 'reportes') !== false ? 'active' : ''; ?>" 
                   href="<?php echo BASE_URL; ?>reportes">
                    <i class="bi bi-bar-chart"></i>
                    Reportes
                </a>
            </li>
            <?php endif; ?>

            <li class="nav-item">
                <a class="nav-link text-white" href="<?php echo BASE_URL; ?>logout.php">
                    <i class="bi bi-box-arrow-right"></i>
                    Cerrar Sesión
                </a>
            </li>

        </ul>
    </div>
</nav>
<?php endif; ?>

<?php require_once dirname(__DIR__, 2) . '/config/base_url.php'; ?>
<link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/styles.css">