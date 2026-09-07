<?php
date_default_timezone_set('America/Lima');
session_start();

require_once __DIR__ . '/../controllers/RequerimientoController.php';
require_once __DIR__ . '/../controllers/AuthController.php';

use Controllers\RequerimientoController;
use Controllers\AuthController;

$reqController = new RequerimientoController();
$authController = new AuthController();

$accion = $_GET['accion'] ?? 'default';

// =====================================================
// 1. RUTAS PÚBLICAS (No requieren sesión)
// =====================================================
if ($accion === 'login') {
    $authController->mostrarLogin();
    exit;
}
if ($accion === 'procesar_login') {
    $authController->procesarLogin();
    exit;
}

// =====================================================
// 2. GUARDIA DE SEGURIDAD - Sin sesión al login
// =====================================================
if (!isset($_SESSION['id_usuario'])) {
    header("Location: index.php?accion=login");
    exit;
}

// Usamos el NOMBRE del rol, no el número — así no importa qué ID asignó Oracle
$rolActual = $_SESSION['nombre_rol'] ?? '';

// =====================================================
// 3. RUTAS PROTEGIDAS
// =====================================================
switch ($accion) {

    // -------------------------------------------------
    // LOGOUT (Todos los roles)
    // -------------------------------------------------
    case 'logout':
        $authController->logout();
        break;

    // -------------------------------------------------
    // RUTAS COMUNES (Todos los roles autenticados)
    // -------------------------------------------------
    case 'nuevo':
    case 'guardar':
    case 'buscar_similares_ajax':
        if ($accion === 'nuevo')                 $reqController->mostrarFormulario();
        if ($accion === 'guardar')                $reqController->guardarTicket();
        if ($accion === 'buscar_similares_ajax')  $reqController->buscarCasosSimilaresAjax();
        break;

    // -------------------------------------------------
    // RUTAS EXCLUSIVAS: SUPER ADMIN
    // -------------------------------------------------
    case 'dashboard':
    case 'auto_asignar_seleccionados':
    case 'exportar_excel_periodo':
    case 'exportar_excel_anual':
        if ($rolActual !== 'Super Admin') {
            header("Location: index.php");
            exit;
        }
        if ($accion === 'dashboard')                  $reqController->mostrarDashboardAdmin();
        if ($accion === 'auto_asignar_seleccionados') $reqController->autoAsignarASeleccionados();
        if ($accion === 'exportar_excel_periodo')     $reqController->exportarExcelPeriodo();
        if ($accion === 'exportar_excel_anual')       $reqController->exportarExcelAnual();
        break;

    // -------------------------------------------------
    // RUTA EXCLUSIVA: VISUALIZADOR (solo lectura)
    // -------------------------------------------------
    case 'visualizador':
        if ($rolActual !== 'Visualizador') {
            header("Location: index.php");
            exit;
        }
        $reqController->mostrarVisualizador();
        break;

    // -------------------------------------------------
    // RUTAS EXCLUSIVAS: MINI ADMIN / MID MINI ADMIN
    // -------------------------------------------------
    case 'mis_casos':
    case 'resolver':
    case 'guardar_resolucion':
    case 'ver_evidencia':
    case 'anular_ticket':
    case 'reactivar_ticket':
    case 'solicitar_validacion':
    case 'validaciones_pendientes':
    case 'aprobar_validacion':
    case 'rechazar_validacion':
    case 'obtener_historial_validaciones_ajax':
    case 'buscar_observacion':
    case 'marcar_en_observacion':
    case 'obtener_notificaciones_ajax':
    case 'marcar_notificaciones_leidas_ajax':
        if ($rolActual !== 'Mini Admin' && $rolActual !== 'Mid Mini Admin') {
        header("Location: index.php");
        exit;
    }
        if ($accion === 'mis_casos')                          $reqController->mostrarMisCasos();
        if ($accion === 'resolver')                           $reqController->mostrarResolucion();
        if ($accion === 'guardar_resolucion')                 $reqController->guardarResolucion();
        if ($accion === 'ver_evidencia')                      $reqController->verEvidencia();
        if ($accion === 'anular_ticket')                       $reqController->anularTicket();
        if ($accion === 'reactivar_ticket')                    $reqController->reactivarTicket();
        if ($accion === 'solicitar_validacion')                $reqController->solicitarValidacion();
        if ($accion === 'validaciones_pendientes')             $reqController->mostrarValidacionesPendientes();
        if ($accion === 'aprobar_validacion')                  $reqController->aprobarValidacion();
        if ($accion === 'rechazar_validacion')                 $reqController->rechazarValidacion();
        if ($accion === 'obtener_historial_validaciones_ajax') $reqController->obtenerHistorialValidacionesAjax();
        if ($accion === 'buscar_observacion')                  $reqController->mostrarBusquedaObservacion();
        if ($accion === 'marcar_en_observacion')               $reqController->marcarEnObservacion();
        if ($accion === 'obtener_notificaciones_ajax')         $reqController->obtenerNotificacionesAjax();
        if ($accion === 'marcar_notificaciones_leidas_ajax')   $reqController->marcarNotificacionesLeidasAjax();
        break;

    // -------------------------------------------------
    // RUTAS EXCLUSIVAS: MID MINI ADMIN
    // -------------------------------------------------
    case 'mi_bolsa_especifica':
    case 'resolver_derivado':
    case 'guardar_resolucion_derivado':
    case 'derivar_externo': 
    case 'casos_externos':
    case 'registrar_respuesta_externa': 
    case 'recuperar_externo':           
        if ($rolActual !== 'Mid Mini Admin') {
            header("Location: index.php");
            exit;
        }
        if ($accion === 'mi_bolsa_especifica')         $reqController->mostrarMiBolsaEspecifica();
        if ($accion === 'resolver_derivado')           $reqController->mostrarResolucionDerivado();
        if ($accion === 'guardar_resolucion_derivado') $reqController->guardarResolucionDerivado();
        if ($accion === 'derivar_externo')             $reqController->derivarExterno();
        if ($accion === 'casos_externos')              $reqController->mostrarCasosExternos();
        if ($accion === 'registrar_respuesta_externa') $reqController->registrarRespuestaExterna(); 
        if ($accion === 'recuperar_externo')           $reqController->recuperarExterno();          
        break;

    // -------------------------------------------------
    // RUTAS EXCLUSIVAS: SOLICITANTE
    // -------------------------------------------------
    case 'mis_tickets':
        if ($rolActual !== 'Solicitante') {
            header("Location: index.php");
            exit;
        }
        $reqController->mostrarHistorialSolicitante();
        break;

    // -------------------------------------------------
    // RUTAS COMPARTIDAS: MINI ADMIN + MID MINI ADMIN
    // -------------------------------------------------
    case 'historial':
    case 'editar_historico':
    case 'guardar_edicion_historico':
    case 'reenviar_respaldo':
        if ($rolActual !== 'Mini Admin' && $rolActual !== 'Mid Mini Admin') {
            header("Location: index.php");
            exit;
        }
        if ($accion === 'historial')                 $reqController->mostrarHistorial();
        if ($accion === 'editar_historico')          $reqController->mostrarEditarHistorico();
        if ($accion === 'guardar_edicion_historico') $reqController->guardarEdicionHistorico();
        if ($accion === 'reenviar_respaldo')         $reqController->reenviarRespaldo();
        break;

    // -------------------------------------------------
    // DEFAULT: Redirigir según rol a su pantalla inicial
    // -------------------------------------------------
    default:
        switch ($rolActual) {
            case 'Super Admin':
                $reqController->mostrarDashboardAdmin();
                break;
            case 'Mini Admin':
                $reqController->mostrarMisCasos();
                break;
            case 'Mid Mini Admin':
                $reqController->mostrarMiBolsaEspecifica();
                break;
            case 'Solicitante':
                $reqController->mostrarHistorialSolicitante();
                break;
            case 'Visualizador':
                $reqController->mostrarVisualizador();
                break;
            default:
                $authController->logout();
                break;
        }
        break;
}
?>