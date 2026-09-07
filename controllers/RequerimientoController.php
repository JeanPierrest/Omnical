<?php
namespace Controllers;

require_once __DIR__ . '/../models/RequerimientoModel.php';
use Models\RequerimientoModel;

class RequerimientoController {
    private $modelo;

    public function __construct() {
        $this->modelo = new RequerimientoModel();
    }


   public function mostrarFormulario() {
    $dependencias   = $this->modelo->obtenerDependencias();
    $seguros        = $this->modelo->obtenerSeguros();
    $tiposConsulta  = $this->modelo->obtenerTiposConsulta();
    require_once __DIR__ . '/../views/nuevo_ticket.php';
}

    public function guardarTicket() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $idSolicitante = $_SESSION['id_usuario'];
            
            // Evaluamos si el que crea el ticket es un Analista
            $rolActual = $_SESSION['nombre_rol'] ?? '';
            $autoAsignar = ($rolActual === 'Mini Admin' || $rolActual === 'Mid Mini Admin');

            $idDependencia = isset($_POST['id_dependencia']) ? (int)$_POST['id_dependencia'] : (isset($_POST['dependencia']) ? (int)$_POST['dependencia'] : 0);
            $idSeguro = isset($_POST['id_seguro']) && $_POST['id_seguro'] !== '' ? (int)$_POST['id_seguro'] : null;
            $idTipoConsulta = isset($_POST['id_tipo_consulta']) && $_POST['id_tipo_consulta'] !== '' ? (int)$_POST['id_tipo_consulta'] : null;
            $descripcion = $_POST['descripcion_requerimiento'] ?? ($_POST['descripcion'] ?? '');

            $idPosibleDuplicado = !empty($_POST['id_posible_duplicado']) ? (int)$_POST['id_posible_duplicado'] : null;
            $ignoroAlerta = isset($_POST['ignoro_alerta_duplicado']) && $_POST['ignoro_alerta_duplicado'] === '1' ? 1 : 0;

            $tipoDocumento = trim($_POST['tipo_documento'] ?? '');
            $numeroDocumento = trim($_POST['numero_documento'] ?? '');
            $tipoDocumentoOtro = trim($_POST['tipo_documento_otro_texto'] ?? '') ?: null;
            $seguroOtro = trim($_POST['seguro_otro_texto'] ?? '') ?: null;
            $consultaOtro = trim($_POST['consulta_otro_texto'] ?? '') ?: null;

            if ($idDependencia <= 0 || empty($descripcion)) {
                header("Location: index.php?accion=nuevo&error=faltan_datos");
                exit;
            }

            // 1. Registramos el ticket PRIMERO, sin archivos, para obtener su código real
            $resultado = $this->modelo->registrarRequerimiento(
            $idSolicitante, $idDependencia, $idSeguro, $descripcion, null, 
            $idPosibleDuplicado, $ignoroAlerta, $idTipoConsulta, $tipoDocumento, $numeroDocumento, $autoAsignar,
            $tipoDocumentoOtro, $seguroOtro, $consultaOtro
            );

            if (!$resultado['exito']) {
                header("Location: index.php?accion=nuevo&error=fallo_registro");
                exit;
            }

            $idTicket = $resultado['id_requerimiento'];
            $codigoTicket = $resultado['codigo_ticket'];

            // 2. Ahora que tenemos el código real, subimos los archivos a su subcarpeta
            $nombresArchivos = [];
            if (isset($_FILES['evidencias']) && !empty($_FILES['evidencias']['name'][0])) {
                $carpetaTicket = __DIR__ . '/../public/uploads/' . $codigoTicket . '/';
                if (!is_dir($carpetaTicket)) {
                    mkdir($carpetaTicket, 0755, true);
                }

                $totalArchivos = count($_FILES['evidencias']['name']);
                $totalArchivos = $totalArchivos > 5 ? 5 : $totalArchivos;

                for ($i = 0; $i < $totalArchivos; $i++) {
                    if ($_FILES['evidencias']['error'][$i] === UPLOAD_ERR_OK) {
                        $nombreTmp = $_FILES['evidencias']['tmp_name'][$i];
                        $nombreOriginal = $_FILES['evidencias']['name'][$i];

                        $extension = strtolower(pathinfo($nombreOriginal, PATHINFO_EXTENSION));
                        $extensionesPermitidas = ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx'];

                        if (in_array($extension, $extensionesPermitidas)) {
                            $nombreFinal = 'EV_' . time() . '_' . rand(10, 99) . '_' . $i . '.' . $extension;
                            $rutaDestino = $carpetaTicket . $nombreFinal;

                            if (move_uploaded_file($nombreTmp, $rutaDestino)) {
                                $nombresArchivos[] = $nombreFinal;
                            }
                        }
                    }
                }

                // 3. Si hubo archivos, actualizamos el campo con la lista final
                if (!empty($nombresArchivos)) {
                    $archivosGuardar = implode(',', $nombresArchivos);
                    $this->modelo->actualizarArchivoEvidencia($idTicket, $archivosGuardar);
                }
            }

            // 4. Redirección inteligente (igual que antes)
            if ($autoAsignar) {
                $destino = ($rolActual === 'Mid Mini Admin') ? 'mi_bolsa_especifica' : 'mis_casos';
                header("Location: index.php?accion=" . $destino . "&exito=Ticket creado y auto-asignado a tu bandeja");
            } else {
                header("Location: index.php?accion=nuevo&exito=1");
            }
            exit;
        }
    }

   public function mostrarHistorialSolicitante() {
    $idSolicitante = $_SESSION['id_usuario'];
    
    $pagina = isset($_GET['pagina']) ? max(1, (int)$_GET['pagina']) : 1;
    $porPagina = 15;
    $mes = isset($_GET['mes']) && $_GET['mes'] !== '' ? (int)$_GET['mes'] : null;
    $anio = isset($_GET['anio']) && $_GET['anio'] !== '' ? (int)$_GET['anio'] : null;
    $busqueda = trim($_GET['busqueda'] ?? '');

    $resultado = $this->modelo->listarMisTicketsSolicitantePaginado($idSolicitante, $pagina, $porPagina, $mes, $anio, $busqueda);
    
    $tickets = $resultado['tickets'];
    $totalPaginas = $resultado['total_paginas'];
    $paginaActual = $resultado['pagina_actual'];
    $totalRegistros = $resultado['total'];

    require_once __DIR__ . '/../views/historial_solicitante.php';
    }

   

    public function mostrarMisCasos() {
    $idAnalista = $_SESSION['id_usuario'];
    $tickets = $this->modelo->listarMisCasos($idAnalista);
    $derivadosPorMi = $this->modelo->obtenerCasosDerivadosPorMi($idAnalista);
    require_once __DIR__ . '/../views/mis_casos.php';
}

    public function mostrarResolucion() {
    $idRequerimiento  = (int)($_GET['id'] ?? 0);
    $ticket           = $this->modelo->obtenerTicketPorId($idRequerimiento);
    $catalogosMotivos = $this->modelo->obtenerMotivosInconsistencia();
    $analistas        = $this->modelo->obtenerTodosLosAnalistas();
    $historialValidaciones = $this->modelo->obtenerHistorialValidaciones($idRequerimiento);
    require_once __DIR__ . '/../views/resolver_ticket.php';
}

   public function guardarResolucion() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $idTicket = (int)$_POST['id_requerimiento'];
        $observaciones = $_POST['observaciones'] ?? '';

        // Obtenemos el código del ticket una sola vez, lo usamos en ambas ramas
        $codigoTicket = $this->modelo->obtenerCodigoPorId($idTicket);
        $carpetaTicket = __DIR__ . '/../public/uploads/' . $codigoTicket . '/';
        if (!is_dir($carpetaTicket)) {
            mkdir($carpetaTicket, 0755, true);
        }

        // 1. Verificamos si la petición viene del botón de derivar
        if (isset($_POST['accion_tipo']) && $_POST['accion_tipo'] === 'derivar') {
            $idAnalistaDestino = isset($_POST['id_analista_destino']) ? (int)$_POST['id_analista_destino'] : 0;
            $idAnalistaActual = $_SESSION['id_usuario'];

            // Restricción: no puede derivarse a sí mismo
            if ($idAnalistaDestino === $idAnalistaActual) {
                header("Location: index.php?accion=resolver&id=" . $idTicket . "&error=auto_derivacion");
                exit;
            }

            // ── Manejo de archivos opcionales en derivación (máx. 5) ──
            $nombresArchivosDerivacion = [];
            if (isset($_FILES['evidencias_derivacion']) && !empty($_FILES['evidencias_derivacion']['name'][0])) {
                $totalArchivos = count($_FILES['evidencias_derivacion']['name']);
                $totalArchivos = $totalArchivos > 5 ? 5 : $totalArchivos;

                for ($i = 0; $i < $totalArchivos; $i++) {
                    if ($_FILES['evidencias_derivacion']['error'][$i] === UPLOAD_ERR_OK) {
                        $nombreTmp = $_FILES['evidencias_derivacion']['tmp_name'][$i];
                        $nombreOriginal = $_FILES['evidencias_derivacion']['name'][$i];
                        $extension = strtolower(pathinfo($nombreOriginal, PATHINFO_EXTENSION));
                        $extensionesPermitidas = ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx'];

                        if (in_array($extension, $extensionesPermitidas)) {
                            // Prefijo DER_ para diferenciarlos de los del solicitante (EV_)
                            $nombreFinal = 'DER_' . time() . '_' . rand(10, 99) . '_' . $i . '.' . $extension;
                            $rutaDestino = $carpetaTicket . $nombreFinal;

                            if (move_uploaded_file($nombreTmp, $rutaDestino)) {
                                $nombresArchivosDerivacion[] = $nombreFinal;
                            }
                        }
                    }
                }
            }

            if ($idAnalistaDestino > 0) {
                $this->modelo->derivarTicket($idTicket, $idAnalistaActual, $idAnalistaDestino, $observaciones, $nombresArchivosDerivacion);
            }

            header("Location: index.php?accion=mis_casos");
            exit;
        }

        // 2. Control de cierre por Estado Final
        $estadoFinal = $_POST['estado_final'] ?? '';

        // RESTRICCIÓN: Si seleccionan PENDIENTE, no se permite procesar el cierre
        if ($estadoFinal === 'PENDIENTE') {
            header("Location: index.php?accion=resolver&id=" . $idTicket . "&error=estado_pendiente");
            exit;
        }

        $idInconsistencia = !empty($_POST['id_inconsistencia']) ? (int)$_POST['id_inconsistencia'] : null;

        // ── Manejo de archivos opcionales al cerrar normalmente (máx. 5) ──
        $nombresArchivosCierre = [];
        if (isset($_FILES['evidencias_cierre']) && !empty($_FILES['evidencias_cierre']['name'][0])) {
            $totalArchivos = count($_FILES['evidencias_cierre']['name']);
            $totalArchivos = $totalArchivos > 5 ? 5 : $totalArchivos;

            for ($i = 0; $i < $totalArchivos; $i++) {
                if ($_FILES['evidencias_cierre']['error'][$i] === UPLOAD_ERR_OK) {
                    $nombreTmp = $_FILES['evidencias_cierre']['tmp_name'][$i];
                    $nombreOriginal = $_FILES['evidencias_cierre']['name'][$i];
                    $extension = strtolower(pathinfo($nombreOriginal, PATHINFO_EXTENSION));
                    $extensionesPermitidas = ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx'];

                    if (in_array($extension, $extensionesPermitidas)) {
                        $nombreFinal = 'RES_' . time() . '_' . rand(10, 99) . '_' . $i . '.' . $extension;
                        $rutaDestino = $carpetaTicket . $nombreFinal;

                        if (move_uploaded_file($nombreTmp, $rutaDestino)) {
                            $nombresArchivosCierre[] = $nombreFinal;
                        }
                    }
                }
            }
        }

        $this->modelo->resolverTicket($idTicket, $estadoFinal, $idInconsistencia, $observaciones, $nombresArchivosCierre);

        header("Location: index.php?accion=mis_casos");
        exit;
    }
}
    public function mostrarHistorial() {
    $idAnalista = $_SESSION['id_usuario'];
    
    $anio     = isset($_GET['anio']) ? (int)$_GET['anio'] : (int)date('Y');
    $mes      = isset($_GET['mes'])  ? (int)$_GET['mes']  : (int)date('m');
    $busqueda = trim($_GET['busqueda'] ?? '');
    $fechaDesde = trim($_GET['fecha_desde'] ?? '');
    $fechaHasta = trim($_GET['fecha_hasta'] ?? '');
    
    $tickets = $this->modelo->listarHistorialPorAnalista($idAnalista, $anio, $mes, $busqueda, $fechaDesde, $fechaHasta);
    $dependencias = $this->modelo->obtenerDependencias();
    require_once __DIR__ . '/../views/historial.php';
    }

    public function mostrarEditarHistorico() {
        if (isset($_GET['codigo'])) {
            $codigo = $_GET['codigo'];
            $ticket = $this->modelo->obtenerTicketPorCodigo($codigo);
            
            if ($ticket) {
                $dependencias = $this->modelo->obtenerCatalogos('DEPENDENCIA');
                $usuarios = $this->modelo->obtenerTodosLosUsuarios();
                require_once __DIR__ . '/../views/editar_historico.php';
            } else {
                header("Location: index.php?accion=historial");
            }
        }
    }

    public function guardarEdicionHistorico() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $idTicket = (int)$_POST['id_requerimiento'];
            $descripcion = $_POST['descripcion'];
            $observaciones = $_POST['observaciones'];
            
            $idSolicitante = isset($_POST['id_solicitante']) ? (int)$_POST['id_solicitante'] : null;
            $idDependencia = isset($_POST['id_dependencia']) ? (int)$_POST['id_dependencia'] : null;
            
            $this->modelo->actualizarTicketHistorico($idTicket, $descripcion, $observaciones, $idSolicitante, $idDependencia);

            header("Location: index.php?accion=historial");
            exit;
        }
    }

    public function autoAsignarTicket() {
        if (isset($_GET['id'])) {
            $idTicket = (int)$_GET['id'];
            $idAdmin = $_SESSION['id_usuario']; // El administrador activo (Juan)
            
            // 1. El algoritmo evalúa quién tiene menos carga
            $idSubAdmin = $this->modelo->obtenerAnalistaMenosCarga($idAdmin);
            
            if ($idSubAdmin > 0) {
                // 2. Le asignamos el ticket automáticamente al analista ganador
                $this->modelo->atenderTicket($idTicket, $idSubAdmin);
                header("Location: index.php?accion=bolsa");
            } else {
                // Si por error no hay sub-admins disponibles, regresamos con error
                header("Location: index.php?accion=bolsa&error=sin_analistas");
            }
            exit;
        }
    }
    public function mostrarDashboardAdmin() {
    $kpis            = $this->modelo->obtenerKPIGeneral();
    $top3Rechazos    = $this->modelo->obtenerTop3Rechazos();
    $top3Dependencias = $this->modelo->obtenerTop3Dependencias();
    $miniAdmins      = $this->modelo->obtenerMiniAdminsConCarga();
    require_once __DIR__ . '/../views/dashboard_admin.php';
}

public function autoAsignarASeleccionados() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $ids = isset($_POST['analistas']) ? array_map('intval', $_POST['analistas']) : [];
        if (empty($ids)) {
            header("Location: index.php?accion=dashboard&error=sin_seleccion");
            exit;
        }
        $cantidad = $this->modelo->autoAsignarASeleccionados($ids);
        if ($cantidad > 0) {
            header("Location: index.php?accion=dashboard&exito=" . $cantidad);
        } else {
            header("Location: index.php?accion=dashboard&error=sin_pendientes");
        }
        exit;
    }
}

public function exportarExcelPeriodo() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $fechaInicio = $_POST['fecha_inicio'] ?? date('Y-m-01');
        $fechaFin    = $_POST['fecha_fin']    ?? date('Y-m-d');
        $tickets     = $this->modelo->listarHistorialPorPeriodo($fechaInicio, $fechaFin);
        $nombreArchivo = 'Reporte_' . $fechaInicio . '_al_' . $fechaFin . '.csv';
        $this->generarCSV($tickets, $nombreArchivo);
    }
}

public function exportarExcelAnual() {
    $tickets       = $this->modelo->listarHistorialAnualActual();
    $nombreArchivo = 'Reporte_Anual_' . date('Y') . '.csv';
    $this->generarCSV($tickets, $nombreArchivo);
}

// Método privado compartido para generar el CSV
    private function generarCSV(array $tickets, string $nombreArchivo) {
        if (ob_get_length()) ob_clean();
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $nombreArchivo);
        header('Pragma: no-cache');
        header('Expires: 0');

        $salida = fopen('php://output', 'w');
        fputs($salida, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM para Excel

        // Cabeceras exactas (¡Aquí está CONTACTO EXTERNO!)
        fputcsv($salida, [
            'N° REQUERIMIENTO', 'MES', 'FECHA RECEPCIÓN', 'SOLICITANTE', 'DEPENDENCIA',
            'DESCRIPCIÓN', 
            'ANALISTA INICIAL', 'TIEMPO INICIAL (DÍAS)', 'FECHA DERIVACIÓN',
            'ANALISTA DERIVADO', 'TIEMPO DERIVADO (DÍAS)',
            'ENTIDAD EXTERNA', 'CONTACTO EXTERNO', 'FECHA ENVÍO EXTERNO', 'FECHA RESPUESTA EXTERNA',
            'FECHA SOLICITUD VAL.', 'FECHA RESPUESTA VAL.', 'TIEMPO VALIDACIÓN (DÍAS)',
            'RESPUESTA FINAL', 'FECHA CIERRE', 'ESTADO', 'TIPO SEGURO'
        ], ';');

        $meses = [
            '01'=>'ENERO','02'=>'FEBRERO','03'=>'MARZO','04'=>'ABRIL',
            '05'=>'MAYO','06'=>'JUNIO','07'=>'JULIO','08'=>'AGOSTO',
            '09'=>'SEPTIEMBRE','10'=>'OCTUBRE','11'=>'NOVIEMBRE','12'=>'DICIEMBRE'
        ];

        foreach ($tickets as $t) {
            $mesNum  = substr($t['FECHA_CREACION'] ?? '', 3, 2);
            $mesText = $meses[$mesNum] ?? '';
            
            $descripcion = is_object($t['DESCRIPCION_REQUERIMIENTO']) 
                ? $t['DESCRIPCION_REQUERIMIENTO']->load() 
                : ($t['DESCRIPCION_REQUERIMIENTO'] ?? '');
            
            $observaciones = is_object($t['OBSERVACIONES_CIERRE']) 
                ? $t['OBSERVACIONES_CIERRE']->load() 
                : ($t['OBSERVACIONES_CIERRE'] ?? '');

            $respuesta = !empty($t['DESCRIPCION_MOTIVO'])
                ? 'RECHAZO: ' . $t['DESCRIPCION_MOTIVO'] . ' | ' . $observaciones
                : $observaciones;

            // Datos alineados 1 a 1 con las cabeceras
            fputcsv($salida, [
                $t['CODIGO_TICKET'],
                $mesText,
                $t['FECHA_CREACION'],
                $t['NOMBRE_SOLICITANTE'],
                $t['DEPENDENCIA'],
                $descripcion,
                $t['ANALISTA_INICIAL'] ?? 'Sin asignar',
                $t['TIEMPO_INICIAL_DIAS'] ?? '0',
                $t['FECHA_DERIVACION'] ?? '-',
                $t['ANALISTA_DERIVADO'] ?? '-',
                $t['TIEMPO_DERIVADO_DIAS'] ?? '0',
                $t['ENTIDAD_EXTERNA'] ?? '-',
                $t['CONTACTO_EXTERNO'] ?? '-',
                $t['FECHA_ENVIO_EXTERNO'] ?? '-',
                $t['FECHA_RESPUESTA_EXTERNA'] ?? '-',
                $t['FECHA_SOLICITUD_VAL'] ?? '-',
                $t['FECHA_RESPUESTA_VAL'] ?? '-',
                $t['TIEMPO_VALIDACION_DIAS'] ?? '0',
                $respuesta,
                $t['FECHA_CIERRE'] ?? '',
                $t['ESTADO_ACTUAL'],
                $t['TIPO_SEGURO'] ?? ''
            ], ';');
        }

        fclose($salida);
        exit;
    }

public function mostrarMiBolsaEspecifica() {
    $idAnalista = $_SESSION['id_usuario'];
    $tickets = $this->modelo->listarMisCasos($idAnalista);
    $derivadosPorMi = $this->modelo->obtenerCasosDerivadosPorMi($idAnalista);
    require_once __DIR__ . '/../views/mi_bolsa_especifica.php';
}

public function mostrarResolucionDerivado() {
    // TODO: pendiente
    header("Location: index.php?accion=mi_bolsa_especifica");
    exit;
}

public function guardarResolucionDerivado() {
    // TODO: pendiente
    header("Location: index.php?accion=mi_bolsa_especifica");
    exit;
}

public function verEvidencia() {
    // TODO: pendiente
    header("Location: index.php?accion=mis_casos");
    exit;
}

public function reenviarRespaldo() {
    // TODO: pendiente
    header("Location: index.php?accion=historial");
    exit;
}
public function anularTicket() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $idTicket  = (int)$_POST['id_requerimiento'];
        $motivo    = trim($_POST['motivo_anulacion'] ?? '');
        $idUsuario = $_SESSION['id_usuario'];

        if (empty($motivo)) {
            header("Location: index.php?accion=mis_casos&error=motivo_requerido");
            exit;
        }

        $ok = $this->modelo->anularTicket($idTicket, $idUsuario, $motivo);

        if ($ok) {
            header("Location: index.php?accion=mis_casos&exito=anulado");
        } else {
            header("Location: index.php?accion=mis_casos&error=fallo_anular");
        }
        exit;
    }
}
public function reactivarTicket() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $idTicket  = (int)$_POST['id_requerimiento'];
        $idUsuario = $_SESSION['id_usuario'];

        $ok = $this->modelo->reactivarTicket($idTicket, $idUsuario);

        if ($ok) {
            header("Location: index.php?accion=mis_casos&exito=reactivado");
        } else {
            header("Location: index.php?accion=mis_casos&error=fallo_reactivar");
        }
        exit;
    }
}
public function buscarCasosSimilaresAjax() {
    header('Content-Type: application/json');
    $idDependencia = (int)($_POST['id_dependencia'] ?? 0);
    $idSeguro      = !empty($_POST['id_seguro']) ? (int)$_POST['id_seguro'] : null;
    $descripcion   = $_POST['descripcion'] ?? '';

    if ($idDependencia <= 0 || mb_strlen($descripcion) < 15) {
        echo json_encode(['similares' => []]);
        exit;
    }

    $similares = $this->modelo->buscarCasosSimilares($idDependencia, $idSeguro, $descripcion);
    echo json_encode(['similares' => $similares]);
    exit;
}

public function solicitarValidacion() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $idTicket = (int)$_POST['id_requerimiento'];
        $idAnalista = $_SESSION['id_usuario'];
        $estadoPropuesto = $_POST['estado_final'] ?? '';
        $idInconsistencia = !empty($_POST['id_inconsistencia']) ? (int)$_POST['id_inconsistencia'] : null;
        $observaciones = $_POST['observaciones'] ?? '';

        // Procesar archivos opcionales (mismo campo que usa el cierre normal)
        $nombresArchivos = [];
        if (isset($_FILES['evidencias_cierre']) && !empty($_FILES['evidencias_cierre']['name'][0])) {
            $codigoTicket = $this->modelo->obtenerCodigoPorId($idTicket);
            $carpetaTicket = __DIR__ . '/../public/uploads/' . $codigoTicket . '/';
            if (!is_dir($carpetaTicket)) {
                mkdir($carpetaTicket, 0755, true);
            }

            $totalArchivos = count($_FILES['evidencias_cierre']['name']);
            $totalArchivos = $totalArchivos > 5 ? 5 : $totalArchivos;

            for ($i = 0; $i < $totalArchivos; $i++) {
                if ($_FILES['evidencias_cierre']['error'][$i] === UPLOAD_ERR_OK) {
                    $nombreTmp = $_FILES['evidencias_cierre']['tmp_name'][$i];
                    $nombreOriginal = $_FILES['evidencias_cierre']['name'][$i];
                    $extension = strtolower(pathinfo($nombreOriginal, PATHINFO_EXTENSION));
                    $extensionesPermitidas = ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx'];

                    if (in_array($extension, $extensionesPermitidas)) {
                        $nombreFinal = 'VAL_' . time() . '_' . rand(10, 99) . '_' . $i . '.' . $extension;
                        $rutaDestino = $carpetaTicket . $nombreFinal;

                        if (move_uploaded_file($nombreTmp, $rutaDestino)) {
                            $nombresArchivos[] = $nombreFinal;
                        }
                    }
                }
            }
        }

        $ok = $this->modelo->solicitarValidacion($idTicket, $idAnalista, $estadoPropuesto, $idInconsistencia, $observaciones, $nombresArchivos);

        if ($ok) {
            header("Location: index.php?accion=mi_bolsa_especifica&exito=validacion_solicitada");
        } else {
            header("Location: index.php?accion=mi_bolsa_especifica&error=fallo_validacion");
        }
        exit;
    }
}

public function mostrarValidacionesPendientes() {
    $idAnalista = $_SESSION['id_usuario'];
    $solicitudes = $this->modelo->listarValidacionesPendientes($idAnalista);

    // Para cada solicitud, agregamos su historial de casos externos (si tuvo alguno)
    foreach ($solicitudes as &$s) {
        $s['HISTORIAL_EXTERNO'] = $this->modelo->obtenerHistorialExterno($s['ID_REQUERIMIENTO']);
    }
    unset($s); // buena práctica al terminar un foreach por referencia

    require_once __DIR__ . '/../views/validaciones_pendientes.php';
}

public function aprobarValidacion() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $idSolicitud = (int)$_POST['id_solicitud'];
        $ok = $this->modelo->aprobarValidacion($idSolicitud);

        if ($ok) {
            header("Location: index.php?accion=validaciones_pendientes&exito=aprobado");
        } else {
            header("Location: index.php?accion=validaciones_pendientes&error=fallo_aprobar");
        }
        exit;
    }
}

public function rechazarValidacion() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $idSolicitud = (int)$_POST['id_solicitud'];
        $comentario = trim($_POST['comentario_rechazo'] ?? '');

        if (empty($comentario)) {
            header("Location: index.php?accion=validaciones_pendientes&error=comentario_requerido");
            exit;
        }

        $ok = $this->modelo->rechazarValidacion($idSolicitud, $comentario);

        if ($ok) {
            header("Location: index.php?accion=validaciones_pendientes&exito=rechazado");
        } else {
            header("Location: index.php?accion=validaciones_pendientes&error=fallo_rechazar");
        }
        exit;
    }
}

public function obtenerHistorialValidacionesAjax() {
    header('Content-Type: application/json');
    $idRequerimiento = (int)($_GET['id_requerimiento'] ?? 0);
    if ($idRequerimiento <= 0) {
        echo json_encode(['historial' => []]);
        exit;
    }
    $historial = $this->modelo->obtenerHistorialValidaciones($idRequerimiento);
    echo json_encode(['historial' => $historial]);
    exit;
}
public function mostrarVisualizador() {
    $busqueda = trim($_GET['busqueda'] ?? '');
    $fechaDesde = trim($_GET['fecha_desde'] ?? '');
    $fechaHasta = trim($_GET['fecha_hasta'] ?? '');
    $tickets = $this->modelo->listarTodosLosCasos($busqueda, $fechaDesde, $fechaHasta);
    $dependencias = $this->modelo->obtenerDependencias();
    require_once __DIR__ . '/../views/visualizador.php';
}

public function derivarExterno() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $idTicket = (int)$_POST['id_requerimiento'];
        $idAnalistaActual = $_SESSION['id_usuario'];
        $entidadExterna = trim($_POST['entidad_externa'] ?? '');
        $contactoExterno = trim($_POST['contacto_externo'] ?? ''); 
        $observaciones = trim($_POST['observaciones'] ?? '');

        // ── Fecha/hora manual de cuándo se envió el correo ──
        $fechaEnvio = trim($_POST['fecha_envio'] ?? '');
        $horaHH = trim($_POST['hora_envio_hh'] ?? '');
        $horaMM = trim($_POST['hora_envio_mm'] ?? '');
        $fechaHoraEnvio = $fechaEnvio . ' ' . $horaHH . ':' . $horaMM;

        // ── Validaciones (mismo patrón que registrarRespuestaExterna) ──
        if (empty($fechaEnvio) || $horaHH === '' || $horaMM === '') {
            header("Location: index.php?accion=resolver&id=" . $idTicket . "&error=fecha_requerida");
            exit;
        }

        $timestampIngresado = strtotime($fechaHoraEnvio);
        if ($timestampIngresado === false) {
            header("Location: index.php?accion=resolver&id=" . $idTicket . "&error=fecha_invalida");
            exit;
        }

        if ($timestampIngresado > time()) {
            header("Location: index.php?accion=resolver&id=" . $idTicket . "&error=fecha_futura");
            exit;
        }

        // Procesar el archivo opcional (correo o captura de pantalla)
        $nombreArchivoFinal = null;
        if (isset($_FILES['evidencia_externa']) && $_FILES['evidencia_externa']['error'] === UPLOAD_ERR_OK) {
            $codigoTicket = $this->modelo->obtenerCodigoPorId($idTicket);
            $carpetaTicket = __DIR__ . '/../public/uploads/' . $codigoTicket . '/';
            if (!is_dir($carpetaTicket)) {
                mkdir($carpetaTicket, 0755, true);
            }

            $nombreTmp = $_FILES['evidencia_externa']['tmp_name'];
            $nombreOriginal = $_FILES['evidencia_externa']['name'];
            $extension = strtolower(pathinfo($nombreOriginal, PATHINFO_EXTENSION));
            $extensionesPermitidas = ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx'];

            if (in_array($extension, $extensionesPermitidas)) {
                $nombreArchivoFinal = 'EXT_' . time() . '_' . rand(10, 99) . '.' . $extension;
                $rutaDestino = $carpetaTicket . $nombreArchivoFinal;
                move_uploaded_file($nombreTmp, $rutaDestino);
            }
        }

        $exito = $this->modelo->derivarAExterno($idTicket, $idAnalistaActual, $entidadExterna, $contactoExterno, $observaciones, $nombreArchivoFinal, $fechaHoraEnvio);

        if ($exito) {
            header("Location: index.php?accion=casos_externos&exito=derivado_externo");
        } else {
            header("Location: index.php?accion=mis_casos&error=fallo_externo");
        }
        exit;
    }
}

public function mostrarCasosExternos() {
        $idAnalista = $_SESSION['id_usuario'];
        $tickets = $this->modelo->listarCasosExternos($idAnalista);
        require_once __DIR__ . '/../views/casos_externos.php';
}
public function registrarRespuestaExterna() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $idTicket = (int)$_POST['id_requerimiento'];
        $idAnalista = $_SESSION['id_usuario'];
        $fecha = trim($_POST['fecha_respuesta'] ?? '');
        $horaHH = trim($_POST['hora_hh'] ?? '00');
        $horaMM = trim($_POST['hora_mm'] ?? '00');
        $hora = $horaHH . ':' . $horaMM;
        $comentarios = trim($_POST['comentarios'] ?? '');
        
        $fechaHoraManual = $fecha . ' ' . $hora;

        // ── VALIDACIÓN 1: formato válido y campos presentes ──
        if (empty($fecha) || empty($hora)) {
            header("Location: index.php?accion=casos_externos&error=fecha_requerida");
            exit;
        }

        $timestampIngresado = strtotime($fechaHoraManual);
        if ($timestampIngresado === false) {
            header("Location: index.php?accion=casos_externos&error=fecha_invalida");
            exit;
        }

        // ── VALIDACIÓN 2: no puede ser una fecha futura ──
        if ($timestampIngresado > time()) {
            header("Location: index.php?accion=casos_externos&error=fecha_futura");
            exit;
        }

        // ── VALIDACIÓN 3: no puede ser anterior a cuando se derivó a la entidad externa ──
        $fechaInicioExterno = $this->modelo->obtenerFechaInicioExterno($idTicket);
        if ($fechaInicioExterno) {
            $timestampInicio = strtotime($fechaInicioExterno);
            if ($timestampIngresado < $timestampInicio) {
                header("Location: index.php?accion=casos_externos&error=fecha_anterior_derivacion");
                exit;
            }
        }
        
        $nombreArchivoFinal = null;
        if (isset($_FILES['evidencia_retorno']) && $_FILES['evidencia_retorno']['error'] === UPLOAD_ERR_OK) {
            $codigoTicket = $this->modelo->obtenerCodigoPorId($idTicket);
            $carpetaTicket = __DIR__ . '/../public/uploads/' . $codigoTicket . '/';
            if (!is_dir($carpetaTicket)) {
                mkdir($carpetaTicket, 0755, true);
            }

            $nombreTmp = $_FILES['evidencia_retorno']['tmp_name'];
            $nombreOriginal = $_FILES['evidencia_retorno']['name'];
            $extension = strtolower(pathinfo($nombreOriginal, PATHINFO_EXTENSION));
            
            if (in_array($extension, ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx'])) {
                $nombreArchivoFinal = 'EXT_RET_' . time() . '_' . rand(10, 99) . '.' . $extension;
                $rutaDestino = $carpetaTicket . $nombreArchivoFinal;
                move_uploaded_file($nombreTmp, $rutaDestino);
            }
        }

        $exito = $this->modelo->registrarRespuestaExterna($idTicket, $idAnalista, $fechaHoraManual, $comentarios, $nombreArchivoFinal);
        
        if ($exito) {
            header("Location: index.php?accion=mi_bolsa_especifica&exito=tiempo_reanudado");
        } else {
            header("Location: index.php?accion=casos_externos&error=fallo_reanudacion");
        }
        exit;
    }
}
    public function recuperarExterno() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $idTicket = (int)$_POST['id_requerimiento'];
            $idAnalista = $_SESSION['id_usuario'];
            
            $exito = $this->modelo->recuperarExterno($idTicket, $idAnalista);
            
            if ($exito) {
                header("Location: index.php?accion=mi_bolsa_especifica&exito=recuperado");
            } else {
                header("Location: index.php?accion=casos_externos&error=fallo_recuperacion");
            }
            exit;
        }
    }

    public function mostrarBusquedaObservacion() {
    $busqueda = trim($_GET['busqueda'] ?? '');
    $fechaDesde = trim($_GET['fecha_desde'] ?? '');
    $fechaHasta = trim($_GET['fecha_hasta'] ?? '');
    
    $tickets = [];
    if (!empty($busqueda) || (!empty($fechaDesde) && !empty($fechaHasta))) {
        // Reutilizamos listarTodosLosCasos() pero filtramos solo a RESUELTO/INCONSISTENTE
        $todos = $this->modelo->listarTodosLosCasos($busqueda, $fechaDesde, $fechaHasta);
        $tickets = array_filter($todos, fn($t) => in_array($t['ESTADO_ACTUAL'], ['RESUELTO', 'INCONSISTENTE']));
    }
    
    require_once __DIR__ . '/../views/buscar_observacion.php';
}

public function marcarEnObservacion() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        
        $idTicket = (int)$_POST['id_requerimiento'];
        $idUsuario = $_SESSION['id_usuario'];
        $motivo = trim($_POST['motivo_observacion'] ?? '');

        if (empty($motivo)) {
            header("Location: index.php?accion=buscar_observacion&error=motivo_requerido");
            exit;
        }

        $ok = $this->modelo->marcarEnObservacion($idTicket, $idUsuario, $motivo);

        if ($ok) {
            header("Location: index.php?accion=mis_casos&exito=en_observacion");
        } else {
            header("Location: index.php?accion=buscar_observacion&error=fallo_observacion");
        }
        exit;
    }
}

public function obtenerNotificacionesAjax() {
    header('Content-Type: application/json');
    $idUsuario = $_SESSION['id_usuario'];
    $notificaciones = $this->modelo->obtenerNotificaciones($idUsuario);
    $noLeidas = $this->modelo->contarNotificacionesNoLeidas($idUsuario);
    echo json_encode(['notificaciones' => $notificaciones, 'no_leidas' => $noLeidas]);
    exit;
}

public function marcarNotificacionesLeidasAjax() {
    header('Content-Type: application/json');
    $idUsuario = $_SESSION['id_usuario'];
    $ok = $this->modelo->marcarNotificacionesLeidas($idUsuario);
    echo json_encode(['exito' => $ok]);
    exit;
}

}
?>