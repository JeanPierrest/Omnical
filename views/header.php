<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Omnicanal Soporte</title>
    <link rel="stylesheet" href="/Omnical/public/assets/css/style.css">
    <style>
        .campanita-wrap { position: relative; display: inline-block; margin-left: 10px; }
        .btn-campanita {
            background: none; border: none; color: #D9EFF7; font-size: 20px;
            cursor: pointer; position: relative; padding: 4px 8px;
        }
        .badge-notif {
            position: absolute; top: -2px; right: 0px;
            background: #DC2626; color: white; border-radius: 50%;
            font-size: 10px; font-weight: 700; min-width: 16px; height: 16px;
            display: flex; align-items: center; justify-content: center;
            padding: 0 3px;
        }
        .dropdown-notif {
            display: none; position: absolute; top: 32px; right: 0;
            background: white; border-radius: 8px; box-shadow: 0 8px 20px rgba(0,0,0,0.2);
            width: 320px; max-height: 400px; overflow-y: auto; z-index: 3000;
        }
        .dropdown-notif-header {
            padding: 12px 14px; border-bottom: 1px solid #F1F5F9;
            font-weight: 700; font-size: 13px; color: #334155;
            display: flex; justify-content: space-between; align-items: center;
        }
        .dropdown-notif-header a { font-size: 11px; color: #0064AF; text-decoration: none; font-weight: 600; }
        .notif-item {
            padding: 10px 14px; border-bottom: 1px solid #F8FAFC;
            font-size: 12px; color: #334155; cursor: pointer; text-decoration: none; display: block;
        }
        .notif-item:hover { background: #F8FAFC; }
        .notif-item.no-leida { background: #EFF6FF; font-weight: 600; }
        .notif-item .notif-fecha { font-size: 10px; color: #94A3B8; margin-top: 3px; display: block; }
        .notif-vacio { padding: 20px; text-align: center; color: #94A3B8; font-size: 12px; }
    </style>
</head>
<body>

    <div class="navbar">
        <div class="navbar-brand">🎫 Omnicanal Soporte</div>
        <div class="navbar-menu">
            <?php
                $accionActual = $_GET['accion'] ?? '';
                $rolActual    = $_SESSION['nombre_rol'] ?? '';
                $mostrarCampanita = ($rolActual === 'Mini Admin' || $rolActual === 'Mid Mini Admin');
            ?>

            <!-- Saludo con nombre del usuario -->
            <span style="color: #D9EFF7; margin-right: 15px; font-size: 13px;">
                👤 Hola, <strong><?= htmlspecialchars($_SESSION['nombre_completo'] ?? 'Usuario') ?></strong>
            </span>

            <?php if ($rolActual === 'Super Admin'): ?>
                <!-- SUPER ADMIN: Solo Dashboard -->
                <a href="index.php?accion=dashboard"
                   class="<?= ($accionActual === 'dashboard') ? 'activo' : '' ?>">
                   Dashboard
                </a>

            <?php elseif ($rolActual === 'Mini Admin'): ?>
                <!-- MINI ADMIN: Mis Casos + Historial -->
                <a href="index.php?accion=mis_casos"
                   class="<?= ($accionActual === 'mis_casos') ? 'activo' : '' ?>">
                   Mis Casos
                </a>
                <a href="index.php?accion=historial"
                   class="<?= ($accionActual === 'historial') ? 'activo' : '' ?>"
                   style="margin-left: 5px;">
                   Historial
                </a>
                <a href="index.php?accion=validaciones_pendientes"
                   class="<?= ($accionActual === 'validaciones_pendientes') ? 'activo' : '' ?>"
                   style="margin-left: 5px;">
                   Validaciones
                </a>
                <a href="index.php?accion=buscar_observacion"
                   class="<?= ($accionActual === 'buscar_observacion') ? 'activo' : '' ?>"
                   style="margin-left: 5px;">
                   🔍 Observación
                </a>
                <a href="index.php?accion=nuevo"
                   class="btn btn-primary <?= ($accionActual === 'nuevo') ? 'activo' : '' ?>"
                   style="margin-left: 5px;">
                   + Nuevo Ticket
                </a>

            <?php elseif ($rolActual === 'Mid Mini Admin'): ?>
                <!-- MID MINI ADMIN: Mi Bolsa Específica + Casos Externos + Historial -->
                <a href="index.php?accion=mi_bolsa_especifica"
                   class="<?= ($accionActual === 'mi_bolsa_especifica') ? 'activo' : '' ?>">
                   Mi Bolsa
                </a>
                
                <!-- NUEVA BANDEJA DE CASOS EXTERNOS -->
                <a href="index.php?accion=casos_externos"
                   class="<?= ($accionActual === 'casos_externos') ? 'activo' : '' ?>"
                   style="margin-left: 5px;">
                   Casos Externos ⏱️
                </a>
                
                <a href="index.php?accion=historial"
                   class="<?= ($accionActual === 'historial') ? 'activo' : '' ?>"
                   style="margin-left: 5px;">
                   Historial
                </a>
                <a href="index.php?accion=buscar_observacion"
                   class="<?= ($accionActual === 'buscar_observacion') ? 'activo' : '' ?>"
                   style="margin-left: 5px;">
                   🔍 Observación
                </a>

            <?php elseif ($rolActual === 'Solicitante'): ?>
                <!-- SOLICITANTE: Mis Tickets + Nuevo Ticket -->
                <a href="index.php?accion=mis_tickets"
                   class="<?= ($accionActual === 'mis_tickets') ? 'activo' : '' ?>">
                   Mis Tickets
                </a>
                <a href="index.php?accion=nuevo"
                   class="btn btn-primary <?= ($accionActual === 'nuevo') ? 'activo' : '' ?>"
                   style="margin-left: 5px;">
                   + Nuevo Ticket
                </a>

            <?php elseif ($rolActual === 'Visualizador'): ?>
                <!-- VISUALIZADOR: Solo lectura de todos los casos -->
                <a href="index.php?accion=visualizador"
                   class="<?= ($accionActual === 'visualizador') ? 'activo' : '' ?>">
                   👁️ Todos los Casos
                </a>

            <?php endif; ?>

            <!-- CAMPANITA DE NOTIFICACIONES (Mini Admin y Mid Mini Admin) -->
            <?php if ($mostrarCampanita): ?>
                <div class="campanita-wrap">
                    <button type="button" class="btn-campanita" onclick="toggleNotificaciones()">
                        🔔
                        <span id="badgeNotif" class="badge-notif" style="display:none;">0</span>
                    </button>
                    <div id="dropdownNotif" class="dropdown-notif">
                        <div class="dropdown-notif-header">
                            <span>Notificaciones</span>
                            <a href="javascript:void(0)" onclick="marcarTodasLeidas()">Marcar todas como leídas</a>
                        </div>
                        <div id="listaNotificaciones">
                            <div class="notif-vacio">Cargando...</div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Cerrar Sesión (todos los roles) -->
            <a href="index.php?accion=logout"
               class="btn btn-danger"
               style="margin-left: 10px;">
               Cerrar Sesión
            </a>
        </div>
    </div>

    <?php if ($mostrarCampanita): ?>
    <script>
        // ── CAMPANITA DE NOTIFICACIONES ──
        function cargarNotificaciones() {
            fetch('index.php?accion=obtener_notificaciones_ajax')
                .then(res => res.json())
                .then(data => {
                    const badge = document.getElementById('badgeNotif');
                    if (data.no_leidas > 0) {
                        badge.style.display = 'flex';
                        badge.innerText = data.no_leidas > 9 ? '9+' : data.no_leidas;
                    } else {
                        badge.style.display = 'none';
                    }

                    const lista = document.getElementById('listaNotificaciones');
                    if (!data.notificaciones || data.notificaciones.length === 0) {
                        lista.innerHTML = '<div class="notif-vacio">No tienes notificaciones.</div>';
                        return;
                    }

                    lista.innerHTML = data.notificaciones.map(n => `
                        <a href="index.php?accion=mis_casos&abrir_caso=${n.ID_REQUERIMIENTO}" class="notif-item ${n.LEIDA == 0 ? 'no-leida' : ''}">
                            ${n.MENSAJE}
                            <span class="notif-fecha">${n.FECHA_CREACION}</span>
                        </a>
                    `).join('');
                })
                .catch(() => {
                    document.getElementById('listaNotificaciones').innerHTML = '<div class="notif-vacio">Error al cargar.</div>';
                });
        }

        function toggleNotificaciones() {
            const dropdown = document.getElementById('dropdownNotif');
            const abierto = dropdown.style.display === 'block';
            dropdown.style.display = abierto ? 'none' : 'block';
            if (!abierto) cargarNotificaciones();
        }

        function marcarTodasLeidas() {
            fetch('index.php?accion=marcar_notificaciones_leidas_ajax')
                .then(res => res.json())
                .then(() => cargarNotificaciones());
        }

        // Cerrar el dropdown si se hace clic fuera
        document.addEventListener('click', function (e) {
            const wrap = document.querySelector('.campanita-wrap');
            if (wrap && !wrap.contains(e.target)) {
                document.getElementById('dropdownNotif').style.display = 'none';
            }
        });

        // Cargamos el contador al iniciar la página (sin abrir el dropdown)
        document.addEventListener('DOMContentLoaded', function () {
            fetch('index.php?accion=obtener_notificaciones_ajax')
                .then(res => res.json())
                .then(data => {
                    const badge = document.getElementById('badgeNotif');
                    if (data.no_leidas > 0) {
                        badge.style.display = 'flex';
                        badge.innerText = data.no_leidas > 9 ? '9+' : data.no_leidas;
                    }
                });
        });
    </script>
    <?php endif; ?>

    <div class="container">