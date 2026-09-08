<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Si no hay sesión activa, expulsar al login inmediatamente
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Obtenemos el nombre del archivo actual para marcar el menú activo
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Incentivos Pinguino | Dashboard</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <!-- Font Awesome 6 (gratuito) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

    <!-- SIDEBAR -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <div class="hide-on-collapse">
                <div class="brand-text">XPLORA</div>
                <div class="brand-sub">Incentivos Pro</div>
            </div>
        </div>
        
        <nav class="sidebar-nav">
            <a href="index.php" class="nav-link <?= ($current_page == 'index.php') ? 'active' : '' ?>">
                <span class="nav-icon">📊</span>
                <span class="hide-on-collapse">Dashboard</span>
            </a>
            
            <a href="kpi1_visita.php" class="nav-link <?= ($current_page == 'kpi1_visita.php') ? 'active' : '' ?>">
                <span class="nav-icon">📍</span>
                <span class="hide-on-collapse">KPI 1 · Visita</span>
            </a>
            
            <a href="kpi2_ejecucion.php" class="nav-link <?= ($current_page == 'kpi2_ejecucion.php') ? 'active' : '' ?>">
                <span class="nav-icon">📸</span>
                <span class="hide-on-collapse">KPI 2 · Ejecución</span>
            </a>
            
            <a href="kpi3_oc.php" class="nav-link <?= ($current_page == 'kpi3_oc.php') ? 'active' : '' ?>">
                <span class="nav-icon">🛒</span>
                <span class="hide-on-collapse">KPI 3 · OC</span>
            </a>
        </nav>

        <!-- PANEL DE USUARIO Y LOGOUT -->
        <div class="sidebar-footer" style="display: flex; align-items: center; gap: 10px; padding: 15px; border-top: 1px solid rgba(255,255,255,0.05); margin-top: auto;">
            
            <!-- Inicial del Usuario (Avatar) -->
            <div class="user-avatar" style="width: 35px; height: 35px; border-radius: 50%; background-color: #<?= $_SESSION['user_color'] ?? '8b5cf6' ?>; display: flex; align-items: center; justify-content: center; font-weight: bold; color: white;">
                <?= substr($_SESSION['nombre_completo'] ?? 'U', 0, 1) ?>
            </div>
            
            <!-- Info del Usuario -->
            <div class="hide-on-collapse" style="flex: 1; overflow: hidden;">
                <div style="font-size: 0.85rem; font-weight: bold; color: var(--text-main); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                    <?= $_SESSION['nombre_completo'] ?? 'Usuario' ?>
                </div>
                <div style="font-size: 0.7rem; color: var(--text-muted); text-transform: uppercase;">
                    <?= $_SESSION['user_rol'] ?? 'Rol' ?>
                </div>
            </div>

            <!-- Botón de Salir -->
            <a href="logout.php" class="hide-on-collapse" title="Cerrar Sesión" style="color: #ef4444; font-size: 1.2rem; text-decoration: none; transition: transform 0.2s;">
                🚪
            </a>
        </div>
    </aside>

    <!-- MAIN WRAPPER -->
    <div class="main-wrapper">
        
        <!-- TOPBAR -->
        <header class="topbar">
            <!-- <div class="topbar-left">
                <button class="toggle-btn" id="sidebarToggle">☰</button>
                <input type="text" class="search-bar" placeholder="Buscar PDV, zona, etc...">
            </div> -->
            <!-- <div class="topbar-right">
                <span style="color: var(--brand-accent); font-size: 0.9rem; font-weight: 500;">● En línea</span>
            </div> -->
        </header>

        <!-- INICIO DEL CONTENIDO -->
        <main class="main-content">