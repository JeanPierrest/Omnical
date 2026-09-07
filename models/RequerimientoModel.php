<?php
namespace Models;

// Requerimos el archivo de conexión
require_once __DIR__ . '/../config/Conexion.php';
use Config\Conexion;

class RequerimientoModel {
    private $db;

    public function __construct() {
        // Instanciamos la clase de conexión para usarla en todos los métodos
        $this->db = new Conexion();
    }

    /**
     * 1. Obtiene todos los tickets en estado PENDIENTE (La Bolsa General Omnicanal)
     */
    public function listarBolsaGeneral() {
        $conexion = $this->db->conectar();
        
        // Consulta SQL optimizada usando la estructura relacional que diseñamos
        $sql = "
            SELECT 
                r.id_requerimiento,
                r.codigo_ticket, 
                r.descripcion_requerimiento, 
                r.estado_actual, 
                TO_CHAR(r.fecha_recepcion, 'DD/MM/YYYY HH24:MI') AS fecha_ingreso,
                u.nombres || ' ' || u.apellidos AS nombre_solicitante,
                c.valor AS dependencia
            FROM requerimientos r
            INNER JOIN usuarios u ON r.id_solicitante = u.id_usuario
            INNER JOIN catalogos_maestros c ON r.id_catalogo_dependencia = c.id_catalogo
            WHERE r.estado_actual = 'PENDIENTE'
            ORDER BY r.fecha_recepcion ASC
        ";

        $stmt = oci_parse($conexion, $sql);
        
        // Ejecución limpia
        oci_execute($stmt);

        $resultados = [];
        // Añadimos OCI_RETURN_LOBS para que convierta los objetos CLOB a texto automáticamente
        while ($row = oci_fetch_array($stmt, OCI_ASSOC | OCI_RETURN_NULLS | OCI_RETURN_LOBS)) {
            $resultados[] = $row;
        }

        oci_free_statement($stmt);
        $this->db->desconectar();

        return $resultados;
    }

    /**
     * 2. Obtiene listas dinámicas desde la tabla de catálogos (Autonomía del negocio)
     */
    public function obtenerCatalogos($tipo) {
        $conexion = $this->db->conectar();
        
        $sql = "SELECT id_catalogo, valor FROM catalogos_maestros WHERE tipo_catalogo = :p_tipo AND is_deleted = 0";
        
        $stmt = oci_parse($conexion, $sql);
        
        // Binding seguro
        oci_bind_by_name($stmt, ':p_tipo', $tipo);
        
        oci_execute($stmt);
        
        $resultados = [];
        while ($row = oci_fetch_assoc($stmt)) {
            $resultados[] = $row;
        }
        
        oci_free_statement($stmt);
        $this->db->desconectar();
        
        return $resultados;
    }

    /**
     * 4. Lógica de Workflow: El Analista toma el caso (Transaccional)
     */
    public function atenderTicket($idTicket, $idAnalista) {
        $conexion = $this->db->conectar();

        try {
            // 1er Paso: Actualizamos el ticket a ASIGNADO
            $sqlUpd = "UPDATE requerimientos SET estado_actual = 'ASIGNADO' WHERE id_requerimiento = :p_id";
            $stmtUpd = oci_parse($conexion, $sqlUpd);
            oci_bind_by_name($stmtUpd, ':p_id', $idTicket);
            
            // OCI_NO_AUTO_COMMIT es vital aquí para no guardar hasta que el paso 2 termine
            $r1 = oci_execute($stmtUpd, OCI_NO_AUTO_COMMIT); 
            if (!$r1) throw new \Exception("Falló la actualización");

            // 2do Paso: Iniciamos el Segmento de Atención para contar el tiempo
            $sqlIns = "INSERT INTO segmentos_atencion 
                       (id_requerimiento, id_analista, estado_segmento, fecha_inicio_segmento) 
                       VALUES (:p_req, :p_ana, 'ASIGNADO', CURRENT_TIMESTAMP)";
            $stmtIns = oci_parse($conexion, $sqlIns);
            oci_bind_by_name($stmtIns, ':p_req', $idTicket);
            oci_bind_by_name($stmtIns, ':p_ana', $idAnalista);
            
            $r2 = oci_execute($stmtIns, OCI_NO_AUTO_COMMIT);
            if (!$r2) throw new \Exception("Falló la inserción del segmento");

            // Si ambos pasos fueron exitosos, guardamos los cambios definitivamente
            oci_commit($conexion);
            $exito = true;

        } catch (\Exception $e) {
            // Si algo falla, deshacemos todo para no dejar datos huérfanos
            oci_rollback($conexion);
            $exito = false;
        }

        @oci_free_statement($stmtUpd);
        @oci_free_statement($stmtIns);
        $this->db->desconectar();

        return $exito;
    }
    /**
     * 5. Obtiene los tickets asignados al Analista actual (Ahora trae la evidencia)
     */
    public function listarMisCasos($idAnalista) {
    $conexion = $this->db->conectar();
    
    $sql = "
        SELECT 
            r.id_requerimiento,
            r.codigo_ticket, 
            r.descripcion_requerimiento, 
            r.archivo_evidencia,
            r.tipo_documento,
            r.numero_documento,
            r.archivo_evidencia,
            r.estado_validacion,
            (
                SELECT sv2.estado_solicitud
                FROM solicitudes_validacion sv2
                WHERE sv2.id_requerimiento = r.id_requerimiento
                ORDER BY sv2.fecha_solicitud DESC
                FETCH FIRST 1 ROWS ONLY
            ) AS ultima_solicitud_estado,
            (
                SELECT sv3.comentario_rechazo
                FROM solicitudes_validacion sv3
                WHERE sv3.id_requerimiento = r.id_requerimiento
                  AND sv3.estado_solicitud = 'RECHAZADO'
                ORDER BY sv3.fecha_respuesta DESC
                FETCH FIRST 1 ROWS ONLY
            ) AS comentario_rechazo,
            sa.estado_segmento AS estado_actual,
            TO_CHAR(sa.fecha_inicio_segmento, 'DD/MM/YYYY HH24:MI') AS fecha_asignacion,
            u.nombres || ' ' || u.apellidos AS nombre_solicitante,
            c.valor AS dependencia
        FROM requerimientos r
        INNER JOIN usuarios u ON r.id_solicitante = u.id_usuario
        INNER JOIN segmentos_atencion sa ON r.id_requerimiento = sa.id_requerimiento
        INNER JOIN catalogos_maestros c ON r.id_catalogo_dependencia = c.id_catalogo
        WHERE sa.id_analista = :p_analista
          AND sa.id_segmento = (
                SELECT MAX(sa2.id_segmento) 
                FROM segmentos_atencion sa2 
                WHERE sa2.id_requerimiento = sa.id_requerimiento 
                  AND sa2.id_analista = sa.id_analista
              )
          AND (
                (sa.fecha_fin_segmento IS NULL AND sa.estado_segmento IN ('ASIGNADO','DERIVADO','REACTIVADO','RETORNO EXTERNO'))
                OR
                (sa.estado_segmento = 'ANULADO' AND r.estado_actual = 'ANULADO')
              )
        ORDER BY sa.fecha_inicio_segmento DESC
    ";

    $stmt = oci_parse($conexion, $sql);
    oci_bind_by_name($stmt, ':p_analista', $idAnalista);
    oci_execute($stmt);

    $resultados = [];
    while ($row = oci_fetch_array($stmt, OCI_ASSOC | OCI_RETURN_NULLS | OCI_RETURN_LOBS)) {
        $resultados[] = $row;
    }

    oci_free_statement($stmt);
    $this->db->desconectar();
    return $resultados;
    }
    /**
     * 6. Obtiene un ticket específico por su ID
     */
   public function obtenerTicketPorId($idTicket) {
    $conexion = $this->db->conectar();
    $sql = "SELECT 
                r.id_requerimiento,
                r.codigo_ticket,
                r.descripcion_requerimiento,
                r.estado_actual,
                r.archivo_evidencia,
                r.tipo_documento,
                r.numero_documento,
                r.id_solicitante,
                u.nombres || ' ' || u.apellidos AS nombre_solicitante,
                c.valor AS dependencia,
                cs.nombre_seguro AS tipo_seguro,
                sa_activo.estado_segmento AS estado_segmento_actual
            FROM requerimientos r
            INNER JOIN usuarios u ON r.id_solicitante = u.id_usuario
            INNER JOIN catalogos_maestros c ON r.id_catalogo_dependencia = c.id_catalogo
            LEFT JOIN catalogo_seguros cs ON r.id_seguro = cs.id_seguro
            LEFT JOIN segmentos_atencion sa_activo 
                   ON sa_activo.id_requerimiento = r.id_requerimiento 
                  AND sa_activo.fecha_fin_segmento IS NULL
            WHERE r.id_requerimiento = :p_id";
    $stmt = oci_parse($conexion, $sql);
    oci_bind_by_name($stmt, ':p_id', $idTicket);
    oci_execute($stmt);
    
    $ticket = oci_fetch_array($stmt, OCI_ASSOC | OCI_RETURN_NULLS | OCI_RETURN_LOBS);
    
    oci_free_statement($stmt);
    $this->db->desconectar();
    return $ticket;
    }

   /**
     * 7. Lógica de Workflow: Cierra el ticket (Éxito o Inconsistencia) y detiene el reloj SLA
     */
    public function resolverTicket($idTicket, $estadoFinal, $idInconsistencia, $observaciones, $archivosNuevos = []) {
    $conexion = $this->db->conectar();
    try {
        // 1. Actualizamos el ticket con el estado final y sus detalles
        //    Si hay archivos nuevos, los concatenamos al campo existente
        if (!empty($archivosNuevos)) {
            $nuevosStr = implode(',', $archivosNuevos);
            $sqlUpd = "UPDATE requerimientos 
                       SET estado_actual = :p_estado, 
                           id_inconsistencia = :p_inconsistencia, 
                           observaciones_cierre = :p_obs,
                           archivo_evidencia = archivo_evidencia || 
                               CASE WHEN archivo_evidencia IS NOT NULL THEN ',' ELSE '' END || :p_nuevos
                       WHERE id_requerimiento = :p_id";
            $stmtUpd = oci_parse($conexion, $sqlUpd);
            oci_bind_by_name($stmtUpd, ':p_nuevos', $nuevosStr);
        } else {
            $sqlUpd = "UPDATE requerimientos 
                       SET estado_actual = :p_estado, 
                           id_inconsistencia = :p_inconsistencia, 
                           observaciones_cierre = :p_obs 
                       WHERE id_requerimiento = :p_id";
            $stmtUpd = oci_parse($conexion, $sqlUpd);
        }
        
        oci_bind_by_name($stmtUpd, ':p_estado', $estadoFinal);
        oci_bind_by_name($stmtUpd, ':p_inconsistencia', $idInconsistencia);
        oci_bind_by_name($stmtUpd, ':p_obs', $observaciones);
        oci_bind_by_name($stmtUpd, ':p_id', $idTicket);
        
        $r1 = oci_execute($stmtUpd, OCI_NO_AUTO_COMMIT);
        if (!$r1) throw new \Exception("Fallo al actualizar ticket");

        // 2. Cerramos el segmento de atención marcando la FECHA_FIN
        $sqlSeg = "UPDATE segmentos_atencion 
            SET estado_segmento = :p_estado, fecha_fin_segmento = CURRENT_TIMESTAMP 
            WHERE id_requerimiento = :p_id AND fecha_fin_segmento IS NULL";
        $stmtSeg = oci_parse($conexion, $sqlSeg);
        oci_bind_by_name($stmtSeg, ':p_estado', $estadoFinal);
        oci_bind_by_name($stmtSeg, ':p_id', $idTicket);
        
        $r2 = oci_execute($stmtSeg, OCI_NO_AUTO_COMMIT);
        if (!$r2) throw new \Exception("Fallo al cerrar segmento");

        oci_commit($conexion);
        $exito = true;
    } catch (\Exception $e) {
        oci_rollback($conexion);
        $exito = false;
    }

    @oci_free_statement($stmtUpd);
    @oci_free_statement($stmtSeg);
    $this->db->desconectar();
    return $exito;
    }
   
    /**
     * 10. Obtiene un ticket específico por su Código (Ahora incluye IDs para validación)
     */
    public function obtenerTicketPorCodigo($codigo) {
        $conexion = $this->db->conectar();
        $sql = "SELECT 
                    r.id_requerimiento, r.codigo_ticket, r.descripcion_requerimiento, 
                    r.observaciones_cierre, r.estado_actual,
                    r.id_solicitante, u.nombres || ' ' || u.apellidos AS nombre_solicitante,
                    r.id_catalogo_dependencia, c.valor AS dependencia
                FROM requerimientos r
                INNER JOIN usuarios u ON r.id_solicitante = u.id_usuario
                INNER JOIN catalogos_maestros c ON r.id_catalogo_dependencia = c.id_catalogo
                WHERE r.codigo_ticket = :p_codigo";
        $stmt = oci_parse($conexion, $sql);
        oci_bind_by_name($stmt, ':p_codigo', $codigo);
        oci_execute($stmt);
        
        $ticket = oci_fetch_array($stmt, OCI_ASSOC | OCI_RETURN_NULLS | OCI_RETURN_LOBS);
        
        oci_free_statement($stmt);
        $this->db->desconectar();
        return $ticket;
    }

    /**
     * 11. Actualiza el ticket y registra la marca de tiempo de la modificación
     */
    public function actualizarTicketHistorico($idTicket, $descripcion, $observaciones, $idSolicitante = null, $idDependencia = null) {
        $conexion = $this->db->conectar();
        
        // Armamos la consulta incluyendo la fecha actual de modificación
        $sql = "UPDATE requerimientos SET 
                descripcion_requerimiento = :p_desc, 
                observaciones_cierre = :p_obs, 
                fecha_modificacion = CURRENT_TIMESTAMP";
                
        // Si se enviaron los campos opcionales para corregir, los actualizamos también
        if ($idSolicitante !== null) {
            $sql .= ", id_solicitante = :p_solicitante";
        }
        if ($idDependencia !== null) {
            $sql .= ", id_catalogo_dependencia = :p_dependencia";
        }
        
        $sql .= " WHERE id_requerimiento = :p_id";

        $stmt = oci_parse($conexion, $sql);
        
        oci_bind_by_name($stmt, ':p_desc', $descripcion);
        oci_bind_by_name($stmt, ':p_obs', $observaciones);
        
        if ($idSolicitante !== null) {
            oci_bind_by_name($stmt, ':p_solicitante', $idSolicitante);
        }
        if ($idDependencia !== null) {
            oci_bind_by_name($stmt, ':p_dependencia', $idDependencia);
        }
        
        oci_bind_by_name($stmt, ':p_id', $idTicket);

        $exito = @oci_execute($stmt, OCI_COMMIT_ON_SUCCESS);
        
        oci_free_statement($stmt);
        $this->db->desconectar();
        
        return $exito;
    }

    /**
     * 12. Obtiene lista de usuarios para el selector de edición
     */
    public function obtenerTodosLosUsuarios() {
        $conexion = $this->db->conectar();
        $sql = "SELECT id_usuario, nombres || ' ' || apellidos AS nombre_completo FROM usuarios ORDER BY nombres ASC";
        $stmt = oci_parse($conexion, $sql);
        oci_execute($stmt);
        $resultados = [];
        while ($row = oci_fetch_assoc($stmt)) { $resultados[] = $row; }
        oci_free_statement($stmt);
        $this->db->desconectar();
        return $resultados;
    }
    /**
     * 13. Obtiene el historial de tickets de un Solicitante específico
     */
    public function listarMisTicketsSolicitante($idSolicitante) {
    $conexion = $this->db->conectar();
    
    $sql = "
        SELECT 
            r.codigo_ticket, 
            c.valor AS dependencia,
            r.estado_actual,
            r.descripcion_requerimiento,
            r.motivo_anulacion,
            r.observaciones_cierre,
            ci.descripcion_motivo,
            (
                SELECT ua.nombres || ' ' || ua.apellidos
                FROM segmentos_atencion sa
                INNER JOIN usuarios ua ON sa.id_analista = ua.id_usuario
                WHERE sa.id_requerimiento = r.id_requerimiento
                  AND sa.fecha_fin_segmento IS NULL
                ORDER BY sa.id_segmento DESC
                FETCH FIRST 1 ROWS ONLY
            ) AS nombre_analista_actual,
            TO_CHAR(r.fecha_recepcion, 'DD/MM/YYYY HH24:MI') AS fecha_creacion
        FROM requerimientos r
        INNER JOIN catalogos_maestros c ON r.id_catalogo_dependencia = c.id_catalogo
        LEFT JOIN catalogo_inconsistencias ci ON r.id_inconsistencia = ci.id_inconsistencia
        WHERE r.id_solicitante = :p_solicitante
        ORDER BY r.fecha_recepcion DESC
    ";

    $stmt = oci_parse($conexion, $sql);
    oci_bind_by_name($stmt, ':p_solicitante', $idSolicitante);
    oci_execute($stmt);

    $resultados = [];
    while ($row = oci_fetch_array($stmt, OCI_ASSOC | OCI_RETURN_NULLS | OCI_RETURN_LOBS)) {
        $resultados[] = $row;
    }

    oci_free_statement($stmt);
    $this->db->desconectar();

    return $resultados;
    }
    /**
     * 14. Registra un nuevo ticket en la base de datos (Secuencial Inteligente)
     */
    public function registrarRequerimiento($idSolicitante, $idDependencia, $idSeguro, $descripcion, $archivoEvidencia = null, $idPosibleDuplicado = null, $ignoroAlerta = 0, $idTipoConsulta = null, $tipoDocumento = null, $numeroDocumento = null, $autoAsignar = false, $tipoDocumentoOtro = null, $seguroOtro = null, $consultaOtro = null) {
        $conexion = $this->db->conectar();
        
        $anio = date('Y');
        $anioCorto = date('y');
        $mes  = date('m');

        $sqlConteo = "SELECT COUNT(*) AS TOTAL FROM requerimientos WHERE anio_registro = :p_anio AND mes_registro = :p_mes";
        $stmtConteo = oci_parse($conexion, $sqlConteo);
        oci_bind_by_name($stmtConteo, ':p_anio', $anio);
        oci_bind_by_name($stmtConteo, ':p_mes', $mes);
        oci_execute($stmtConteo);
        $fila = oci_fetch_assoc($stmtConteo);
        $siguienteNumero = (int)$fila['TOTAL'] + 1;
        oci_free_statement($stmtConteo);

        // Prefijo YYMM siempre fijo (4 caracteres); el secuencial se rellena a mínimo 5 dígitos
        // pero puede crecer sin límite si el volumen del mes lo requiere — nunca rompe el parseo
        // porque el prefijo YYMM siempre ocupa las primeras 4 posiciones, sin importar el total.
        $codigoTicket = $anioCorto . $mes . str_pad($siguienteNumero, 5, '0', STR_PAD_LEFT);

        $estadoInicial = $autoAsignar ? 'ASIGNADO' : 'PENDIENTE';
        
        $sql = "INSERT INTO requerimientos 
                (codigo_ticket, id_solicitante, id_catalogo_dependencia, id_seguro, descripcion_requerimiento, 
                 estado_actual, fecha_recepcion, anio_registro, mes_registro, archivo_evidencia,
                 id_posible_duplicado, ignoro_alerta_duplicado, id_catalogo_tipo_consulta,
                 tipo_documento, numero_documento, tipo_documento_otro, seguro_otro, consulta_otro) 
                VALUES 
                (:p_codigo, :p_solicitante, :p_dependencia, :p_seguro, :p_desc, :p_estado, CURRENT_TIMESTAMP, 
                 :p_anio, :p_mes, :p_archivo, :p_duplicado, :p_ignoro, :p_tipoconsulta,
                 :p_tipodoc, :p_numdoc, :p_tipodocotro, :p_segurootro, :p_consultaotro)";
                
        $stmt = oci_parse($conexion, $sql);
        oci_bind_by_name($stmt, ':p_codigo', $codigoTicket);
        oci_bind_by_name($stmt, ':p_solicitante', $idSolicitante);
        oci_bind_by_name($stmt, ':p_dependencia', $idDependencia);
        oci_bind_by_name($stmt, ':p_seguro', $idSeguro);
        oci_bind_by_name($stmt, ':p_desc', $descripcion);
        oci_bind_by_name($stmt, ':p_estado', $estadoInicial);
        oci_bind_by_name($stmt, ':p_anio', $anio);
        oci_bind_by_name($stmt, ':p_mes', $mes);
        oci_bind_by_name($stmt, ':p_archivo', $archivoEvidencia);
        oci_bind_by_name($stmt, ':p_duplicado', $idPosibleDuplicado);
        oci_bind_by_name($stmt, ':p_ignoro', $ignoroAlerta);
        oci_bind_by_name($stmt, ':p_tipoconsulta', $idTipoConsulta);
        oci_bind_by_name($stmt, ':p_tipodoc', $tipoDocumento);
        oci_bind_by_name($stmt, ':p_numdoc', $numeroDocumento);
        oci_bind_by_name($stmt, ':p_tipodocotro', $tipoDocumentoOtro);
        oci_bind_by_name($stmt, ':p_segurootro', $seguroOtro);
        oci_bind_by_name($stmt, ':p_consultaotro', $consultaOtro);
        
        $exito = @oci_execute($stmt, OCI_NO_AUTO_COMMIT);
        
        if (!$exito) {
            $error = oci_error($stmt);
            oci_rollback($conexion);
            die("Error BD: " . htmlentities($error['message']));
        }
        oci_free_statement($stmt);

        $sqlId = "SELECT id_requerimiento FROM requerimientos WHERE codigo_ticket = :p_codigo";
        $stmtId = oci_parse($conexion, $sqlId);
        oci_bind_by_name($stmtId, ':p_codigo', $codigoTicket);
        oci_execute($stmtId);
        $row = oci_fetch_assoc($stmtId);
        $idNuevoTicket = $row['ID_REQUERIMIENTO'];
        oci_free_statement($stmtId);

        if ($autoAsignar) {
            $sqlSeg = "INSERT INTO segmentos_atencion 
                       (id_requerimiento, id_analista, estado_segmento, fecha_inicio_segmento) 
                       VALUES (:p_req, :p_ana, 'ASIGNADO', CURRENT_TIMESTAMP)";
            $stmtSeg = oci_parse($conexion, $sqlSeg);
            oci_bind_by_name($stmtSeg, ':p_req', $idNuevoTicket);
            oci_bind_by_name($stmtSeg, ':p_ana', $idSolicitante);
            $exitoSeg = @oci_execute($stmtSeg, OCI_NO_AUTO_COMMIT);
            oci_free_statement($stmtSeg);

            if (!$exitoSeg) {
                oci_rollback($conexion);
                $this->db->desconectar();
                return ['exito' => false, 'id_requerimiento' => null, 'codigo_ticket' => null];
            }
        }

        oci_commit($conexion);
        $this->db->desconectar();

        return [
            'exito' => true,
            'id_requerimiento' => $idNuevoTicket,
            'codigo_ticket' => $codigoTicket,
        ];
    }
        /**
     * Obtiene la lista de seguros para el formulario
     */
        public function obtenerSeguros() {
        $conexion = $this->db->conectar();
        $sql = "SELECT id_seguro, nombre_seguro FROM catalogo_seguros ORDER BY id_seguro ASC";
        $stmt = oci_parse($conexion, $sql);
        oci_execute($stmt);
        
        $resultado = [];
        while ($row = oci_fetch_assoc($stmt)) { 
            $resultado[] = $row; 
        }
        
        oci_free_statement($stmt);
        $this->db->desconectar();
        return $resultado;
    }
    

    /**
     * Obtiene todos los analistas activos y formatea su nombre 
     * para mostrar únicamente el primer nombre y el primer apellido.
     */
    public function obtenerAnalistas() {
        $conexion = $this->db->conectar();
        
        // Agregamos DISTINCT para evitar duplicados aunque existan en la tabla
        $sql = "SELECT DISTINCT id_usuario, nombres, apellidos FROM usuarios ORDER BY nombres ASC";
        
        $stmt = oci_parse($conexion, $sql);
        oci_execute($stmt);

        $analistas = [];
        $vistos = []; // Array auxiliar para control extra de seguridad

        while ($row = oci_fetch_array($stmt, OCI_ASSOC)) {
            $apellidosLimpios = trim($row['APELLIDOS']);
            $partesApellidos = preg_split('/\s+/', $apellidosLimpios);
            $primerApellido = $partesApellidos[0] ?? '';

            $nombresLimpios = trim($row['NOMBRES']);
            $partesNombres = preg_split('/\s+/', $nombresLimpios);
            $primerNombre = $partesNombres[0] ?? '';

            if (!empty($primerNombre) && !empty($primerApellido)) {
                $nombreCorto = ucfirst(strtolower($primerNombre)) . ' ' . ucfirst(strtolower($primerApellido));

                // Solo agregamos si el nombreCorto no ha sido procesado antes
                if (!in_array($nombreCorto, $vistos)) {
                    $analistas[] = [
                        'ID_USUARIO' => $row['ID_USUARIO'],
                        'NOMBRE_COMPLETO' => $nombreCorto
                    ];
                    $vistos[] = $nombreCorto;
                }
            }
        }

        oci_free_statement($stmt);
        $this->db->desconectar();
        return $analistas;
    }
    /**
     * Cierra el segmento actual del analista y abre uno nuevo para el analista destino
     */
    public function derivarTicket($idRequerimiento, $idAnalistaActual, $idAnalistaDestino, $observaciones, $archivosNuevos = []) {
    $conexion = $this->db->conectar();
    
    try {
        // 1. Cerrar segmento del analista actual
        $sqlCierre = "UPDATE segmentos_atencion 
                      SET fecha_fin_segmento  = CURRENT_TIMESTAMP, 
                          estado_segmento     = 'DERIVADO',
                          respuesta_resolucion = :p_obs 
                      WHERE id_requerimiento  = :p_req 
                        AND id_analista       = :p_analista 
                        AND fecha_fin_segmento IS NULL";
        $stmt1 = oci_parse($conexion, $sqlCierre);
        oci_bind_by_name($stmt1, ':p_obs',      $observaciones);
        oci_bind_by_name($stmt1, ':p_req',      $idRequerimiento);
        oci_bind_by_name($stmt1, ':p_analista', $idAnalistaActual);
        $r1 = oci_execute($stmt1, OCI_NO_AUTO_COMMIT);
        if (!$r1) throw new \Exception("Fallo al cerrar el segmento actual.");

        // 2. Crear nuevo segmento para el analista destino
        $sqlNuevo = "INSERT INTO segmentos_atencion 
                         (id_requerimiento, id_analista, estado_segmento, fecha_inicio_segmento) 
                     VALUES (:p_req, :p_destino, 'DERIVADO', CURRENT_TIMESTAMP)";
        $stmt2 = oci_parse($conexion, $sqlNuevo);
        oci_bind_by_name($stmt2, ':p_req',     $idRequerimiento);
        oci_bind_by_name($stmt2, ':p_destino', $idAnalistaDestino);
        $r2 = oci_execute($stmt2, OCI_NO_AUTO_COMMIT);
        if (!$r2) throw new \Exception("Fallo al insertar el nuevo segmento.");

        // 3. Actualizar estado del requerimiento a DERIVADO
        //    Registramos también quién derivó (id_analista_derivador), necesario
        //    para el flujo de validación de cierre posterior.
        //    Si hay archivos nuevos, se concatenan al campo existente.
        if (!empty($archivosNuevos)) {
            $nuevosStr = implode(',', $archivosNuevos);
            $sqlUpd = "UPDATE requerimientos 
                       SET estado_actual              = 'DERIVADO',
                           fecha_ultima_modificacion  = CURRENT_TIMESTAMP,
                           id_analista_derivador       = :p_derivador,
                           archivo_evidencia = archivo_evidencia || 
                               CASE WHEN archivo_evidencia IS NOT NULL THEN ',' ELSE '' END || :p_nuevos
                       WHERE id_requerimiento = :p_req2";
            $stmt3 = oci_parse($conexion, $sqlUpd);
            oci_bind_by_name($stmt3, ':p_nuevos', $nuevosStr);
            oci_bind_by_name($stmt3, ':p_derivador', $idAnalistaActual);
            oci_bind_by_name($stmt3, ':p_req2',   $idRequerimiento);
        } else {
            $sqlUpd = "UPDATE requerimientos 
                       SET estado_actual              = 'DERIVADO',
                           fecha_ultima_modificacion  = CURRENT_TIMESTAMP,
                           id_analista_derivador       = :p_derivador
                       WHERE id_requerimiento = :p_req2";
            $stmt3 = oci_parse($conexion, $sqlUpd);
            oci_bind_by_name($stmt3, ':p_derivador', $idAnalistaActual);
            oci_bind_by_name($stmt3, ':p_req2', $idRequerimiento);
        }
        $r3 = oci_execute($stmt3, OCI_NO_AUTO_COMMIT);
        if (!$r3) throw new \Exception("Fallo al actualizar estado del requerimiento.");

        oci_commit($conexion);

    } catch (\Exception $e) {
        oci_rollback($conexion);
    }

    @oci_free_statement($stmt1);
    @oci_free_statement($stmt2);
    @oci_free_statement($stmt3);
    $this->db->desconectar();
    }
    /**
     * Obtiene el catálogo de motivos de inconsistencia
     */
    public function obtenerMotivosInconsistencia() {
        $conexion = $this->db->conectar();
        $sql = "SELECT id_inconsistencia, descripcion_motivo FROM catalogo_inconsistencias ORDER BY id_inconsistencia ASC";
        $stmt = oci_parse($conexion, $sql);
        oci_execute($stmt);

        $motivos = [];
        while ($row = oci_fetch_array($stmt, OCI_ASSOC)) {
            $motivos[] = $row;
        }

        oci_free_statement($stmt);
        $this->db->desconectar();
        return $motivos;
    }
    /**
     * Obtiene las dependencias desde el catálogo maestro
     */
    /**
     * Obtiene las dependencias desde el catálogo maestro
     */
    public function obtenerDependencias() {
        $conexion = $this->db->conectar();
        
        // ¡CAMBIO AQUÍ! Usamos TIPO_CATALOGO en lugar de tipo
        $sql = "SELECT id_catalogo, valor FROM catalogos_maestros WHERE tipo_catalogo = 'DEPENDENCIA' ORDER BY valor ASC";
        
        $stmt = oci_parse($conexion, $sql);
        oci_execute($stmt);

        $dependencias = [];
        while ($row = oci_fetch_array($stmt, OCI_ASSOC)) {
            $dependencias[] = $row;
        }

        oci_free_statement($stmt);
        $this->db->desconectar();
        return $dependencias;
    }
    
    

public function obtenerTop3Rechazos() {
    $conexion = $this->db->conectar();
    $sql = "SELECT ci.descripcion_motivo, COUNT(*) AS cantidad
            FROM requerimientos r
            INNER JOIN catalogo_inconsistencias ci ON r.id_inconsistencia = ci.id_inconsistencia
            WHERE r.estado_actual = 'INCONSISTENTE'
              AND r.mes_registro  = EXTRACT(MONTH FROM CURRENT_DATE)
              AND r.anio_registro = EXTRACT(YEAR  FROM CURRENT_DATE)
            GROUP BY ci.descripcion_motivo
            ORDER BY cantidad DESC
            FETCH FIRST 3 ROWS ONLY";
    $stmt = oci_parse($conexion, $sql);
    oci_execute($stmt);
    $resultado = [];
    while ($row = oci_fetch_assoc($stmt)) { $resultado[] = $row; }
    oci_free_statement($stmt);
    $this->db->desconectar();
    return $resultado;
}

public function obtenerTop3Dependencias() {
    $conexion = $this->db->conectar();
    $sql = "SELECT cm.valor AS dependencia, COUNT(*) AS cantidad
            FROM requerimientos r
            INNER JOIN catalogos_maestros cm ON r.id_catalogo_dependencia = cm.id_catalogo
            WHERE r.mes_registro  = EXTRACT(MONTH FROM CURRENT_DATE)
              AND r.anio_registro = EXTRACT(YEAR  FROM CURRENT_DATE)
            GROUP BY cm.valor
            ORDER BY cantidad DESC
            FETCH FIRST 3 ROWS ONLY";
    $stmt = oci_parse($conexion, $sql);
    oci_execute($stmt);
    $resultado = [];
    while ($row = oci_fetch_assoc($stmt)) { $resultado[] = $row; }
    oci_free_statement($stmt);
    $this->db->desconectar();
    return $resultado;
}

public function obtenerMiniAdminsConCarga() {
    $conexion = $this->db->conectar();
    $sql = "SELECT u.id_usuario,
                   u.nombres || ' ' || u.apellidos AS nombre_completo,
                   r.nombre_rol,
                   COUNT(sa.id_segmento) AS carga_actual
            FROM usuarios u
            INNER JOIN roles r ON u.id_rol = r.id_rol
            LEFT JOIN segmentos_atencion sa
                   ON u.id_usuario = sa.id_analista
                  AND sa.fecha_fin_segmento IS NULL
            WHERE r.nombre_rol IN ('Mini Admin', 'Mid Mini Admin')
              AND u.estado = 1
            GROUP BY u.id_usuario, u.nombres, u.apellidos, r.nombre_rol
            ORDER BY u.nombres ASC";
    $stmt = oci_parse($conexion, $sql);
    oci_execute($stmt);
    $resultado = [];
    while ($row = oci_fetch_assoc($stmt)) { $resultado[] = $row; }
    oci_free_statement($stmt);
    $this->db->desconectar();
    return $resultado;
}

public function listarHistorialPorPeriodo($fechaInicio, $fechaFin) {
        $conexion = $this->db->conectar();
        $sql = "SELECT 
                    r.codigo_ticket,
                    TO_CHAR(r.fecha_recepcion, 'DD/MM/YYYY') AS fecha_creacion,
                    cm.valor AS dependencia,
                    u.nombres || ' ' || u.apellidos AS nombre_solicitante,
                    r.descripcion_requerimiento,
                    r.estado_actual,
                    r.observaciones_cierre,
                    ci.descripcion_motivo,
                    cs.nombre_seguro AS tipo_seguro,
                    TO_CHAR(r.fecha_ultima_modificacion, 'DD/MM/YYYY') AS fecha_cierre,

                    -- 1. Analista Inicial
                    (SELECT u1.nombres || ' ' || u1.apellidos FROM segmentos_atencion s1 JOIN usuarios u1 ON s1.id_analista = u1.id_usuario WHERE s1.id_requerimiento = r.id_requerimiento ORDER BY s1.id_segmento ASC FETCH FIRST 1 ROWS ONLY) AS analista_inicial,

                    -- 2. Días del Analista Inicial (Redondeado a 2 decimales)
                    (SELECT SUM(ROUND(CAST(NVL(s2.fecha_fin_segmento, CURRENT_TIMESTAMP) AS DATE) - CAST(s2.fecha_inicio_segmento AS DATE), 2))
                     FROM segmentos_atencion s2
                     WHERE s2.id_requerimiento = r.id_requerimiento
                       AND s2.id_analista = (SELECT s2_sub.id_analista FROM segmentos_atencion s2_sub WHERE s2_sub.id_requerimiento = r.id_requerimiento ORDER BY s2_sub.id_segmento ASC FETCH FIRST 1 ROWS ONLY)
                       AND s2.estado_segmento NOT LIKE '%EXTERNO%'
                    ) AS tiempo_inicial_dias,

                    -- 3. Fecha en la que el Analista Inicial derivó el caso
                    (SELECT TO_CHAR(s_der.fecha_fin_segmento, 'DD/MM/YYYY HH24:MI') FROM segmentos_atencion s_der WHERE s_der.id_requerimiento = r.id_requerimiento AND s_der.estado_segmento = 'DERIVADO' ORDER BY s_der.id_segmento ASC FETCH FIRST 1 ROWS ONLY) AS fecha_derivacion,

                    -- 4. Analista Derivado
                    (SELECT u3.nombres || ' ' || u3.apellidos FROM segmentos_atencion s3 JOIN usuarios u3 ON s3.id_analista = u3.id_usuario WHERE s3.id_requerimiento = r.id_requerimiento AND s3.id_analista != (SELECT s3_sub.id_analista FROM segmentos_atencion s3_sub WHERE s3_sub.id_requerimiento = r.id_requerimiento ORDER BY s3_sub.id_segmento ASC FETCH FIRST 1 ROWS ONLY) ORDER BY s3.id_segmento DESC FETCH FIRST 1 ROWS ONLY) AS analista_derivado,

                    -- 5. Días del Analista Derivado (Redondeado a 2 decimales)
                    (SELECT SUM(ROUND(CAST(NVL(s4.fecha_fin_segmento, CURRENT_TIMESTAMP) AS DATE) - CAST(s4.fecha_inicio_segmento AS DATE), 2))
                     FROM segmentos_atencion s4
                     WHERE s4.id_requerimiento = r.id_requerimiento
                       AND s4.id_analista != (SELECT s4_sub.id_analista FROM segmentos_atencion s4_sub WHERE s4_sub.id_requerimiento = r.id_requerimiento ORDER BY s4_sub.id_segmento ASC FETCH FIRST 1 ROWS ONLY)
                       AND s4.estado_segmento NOT LIKE '%EXTERNO%'
                    ) AS tiempo_derivado_dias,

                    -- 6. Control del Área Externa (Con Contacto)
                    (SELECT se.entidad_externa FROM segmentos_atencion se WHERE se.id_requerimiento = r.id_requerimiento AND se.estado_segmento = 'DERIVADO EXTERNO' ORDER BY se.id_segmento DESC FETCH FIRST 1 ROWS ONLY) AS entidad_externa,
                    (SELECT se.contacto_externo FROM segmentos_atencion se WHERE se.id_requerimiento = r.id_requerimiento AND se.estado_segmento = 'DERIVADO EXTERNO' ORDER BY se.id_segmento DESC FETCH FIRST 1 ROWS ONLY) AS contacto_externo,
                    (SELECT TO_CHAR(se.fecha_inicio_segmento, 'DD/MM/YYYY HH24:MI') FROM segmentos_atencion se WHERE se.id_requerimiento = r.id_requerimiento AND se.estado_segmento = 'DERIVADO EXTERNO' ORDER BY se.id_segmento DESC FETCH FIRST 1 ROWS ONLY) AS fecha_envio_externo,
                    (SELECT TO_CHAR(se.fecha_fin_segmento, 'DD/MM/YYYY HH24:MI') FROM segmentos_atencion se WHERE se.id_requerimiento = r.id_requerimiento AND se.estado_segmento = 'DERIVADO EXTERNO' ORDER BY se.id_segmento DESC FETCH FIRST 1 ROWS ONLY) AS fecha_respuesta_externa,

                    -- 7. Tiempos de Validación (En Días)
                    (SELECT TO_CHAR(MIN(sv.fecha_solicitud), 'DD/MM/YYYY HH24:MI') FROM solicitudes_validacion sv WHERE sv.id_requerimiento = r.id_requerimiento) AS fecha_solicitud_val,
                    (SELECT TO_CHAR(MAX(sv.fecha_respuesta), 'DD/MM/YYYY HH24:MI') FROM solicitudes_validacion sv WHERE sv.id_requerimiento = r.id_requerimiento) AS fecha_respuesta_val,
                    (SELECT SUM(ROUND(CAST(NVL(sv.fecha_respuesta, CURRENT_TIMESTAMP) AS DATE) - CAST(sv.fecha_solicitud AS DATE), 2)) FROM solicitudes_validacion sv WHERE sv.id_requerimiento = r.id_requerimiento) AS tiempo_validacion_dias

                FROM requerimientos r
                INNER JOIN usuarios u ON r.id_solicitante = u.id_usuario
                INNER JOIN catalogos_maestros cm ON r.id_catalogo_dependencia = cm.id_catalogo
                LEFT JOIN catalogo_inconsistencias ci ON r.id_inconsistencia = ci.id_inconsistencia
                LEFT JOIN catalogo_seguros cs ON r.id_seguro = cs.id_seguro
                WHERE TRUNC(r.fecha_recepcion) >= TO_DATE(:p_inicio, 'YYYY-MM-DD')
                  AND TRUNC(r.fecha_recepcion) <= TO_DATE(:p_fin,   'YYYY-MM-DD')
                ORDER BY r.fecha_recepcion ASC";
                
        $stmt = oci_parse($conexion, $sql);
        oci_bind_by_name($stmt, ':p_inicio', $fechaInicio);
        oci_bind_by_name($stmt, ':p_fin',    $fechaFin);
        oci_execute($stmt);
        $resultado = [];
        while ($row = oci_fetch_assoc($stmt)) { $resultado[] = $row; }
        oci_free_statement($stmt);
        $this->db->desconectar();
        return $resultado;
    }

public function listarHistorialAnualActual() {
    $inicioAnio = date('Y') . '-01-01';
    $hoy        = date('Y-m-d');
    return $this->listarHistorialPorPeriodo($inicioAnio, $hoy);
}
public function autoAsignarASeleccionados(array $idsAnalistas) {
    $conexion = $this->db->conectar();
    try {
        // Obtener todos los PENDIENTES ordenados por fecha (FIFO)
        $sqlPend = "SELECT id_requerimiento FROM requerimientos 
                    WHERE estado_actual = 'PENDIENTE' 
                    ORDER BY fecha_recepcion ASC";
        $stmtPend = oci_parse($conexion, $sqlPend);
        oci_execute($stmtPend);
        $pendientes = [];
        while ($row = oci_fetch_assoc($stmtPend)) {
            $pendientes[] = $row['ID_REQUERIMIENTO'];
        }
        oci_free_statement($stmtPend);

        if (empty($pendientes)) { return 0; }

        $total = count($pendientes);
        $contador = 0;

        foreach ($pendientes as $idReq) {
            // Round-robin entre los seleccionados
            $idAnalista = $idsAnalistas[$contador % count($idsAnalistas)];

            // Cambiar estado a ASIGNADO
            $sqlUpd = "UPDATE requerimientos 
                       SET estado_actual = 'ASIGNADO',
                           fecha_ultima_modificacion = CURRENT_TIMESTAMP
                       WHERE id_requerimiento = :p_id";
            $stmtUpd = oci_parse($conexion, $sqlUpd);
            oci_bind_by_name($stmtUpd, ':p_id', $idReq);
            oci_execute($stmtUpd, OCI_NO_AUTO_COMMIT);
            oci_free_statement($stmtUpd);

            // Crear segmento de atención
            $sqlSeg = "INSERT INTO segmentos_atencion 
                           (id_requerimiento, id_analista, estado_segmento, fecha_inicio_segmento)
                       VALUES (:p_req, :p_ana, 'ASIGNADO', CURRENT_TIMESTAMP)";
            $stmtSeg = oci_parse($conexion, $sqlSeg);
            oci_bind_by_name($stmtSeg, ':p_req', $idReq);
            oci_bind_by_name($stmtSeg, ':p_ana', $idAnalista);
            oci_execute($stmtSeg, OCI_NO_AUTO_COMMIT);
            oci_free_statement($stmtSeg);

            $contador++;
     }
        
            oci_commit($conexion);
            $this->db->desconectar();
            return $total;

        } catch (\Exception $e) {
            oci_rollback($conexion);
            $this->db->desconectar();
            return 0;
        }
    }   // ← esta llave cierra el método autoAsignarASeleccionados()

    public function obtenerTodosLosAnalistas() {
        $conexion = $this->db->conectar();
        $sql = "SELECT u.id_usuario,
                       u.nombres || ' ' || u.apellidos AS nombre_completo,
                       r.nombre_rol
                FROM usuarios u
                INNER JOIN roles r ON u.id_rol = r.id_rol
                WHERE r.nombre_rol IN ('Mini Admin', 'Mid Mini Admin')
                  AND u.estado = 1
                ORDER BY r.nombre_rol, u.nombres ASC";
        $stmt = oci_parse($conexion, $sql);
        oci_execute($stmt);
        $resultado = [];
        while ($row = oci_fetch_assoc($stmt)) { $resultado[] = $row; }
        oci_free_statement($stmt);
        $this->db->desconectar();
        return $resultado;
    }
    public function anularTicket($idRequerimiento, $idUsuarioAnulo, $motivo) {
    $conexion = $this->db->conectar();
    try {
        $sql = "UPDATE requerimientos 
                SET estado_actual      = 'ANULADO',
                    id_usuario_anulo   = :p_usuario,
                    fecha_anulacion    = CURRENT_TIMESTAMP,
                    motivo_anulacion   = :p_motivo
                WHERE id_requerimiento = :p_id
                  AND estado_actual NOT IN ('RESUELTO', 'INCONSISTENTE')";

        $stmt = oci_parse($conexion, $sql);
        oci_bind_by_name($stmt, ':p_usuario', $idUsuarioAnulo);
        oci_bind_by_name($stmt, ':p_motivo',  $motivo);
        oci_bind_by_name($stmt, ':p_id',      $idRequerimiento);
        $r = oci_execute($stmt, OCI_NO_AUTO_COMMIT);
        if (!$r) throw new \Exception("Fallo al anular ticket.");

        // Cerramos cualquier segmento activo asociado
        $sqlSeg = "UPDATE segmentos_atencion 
                   SET fecha_fin_segmento = CURRENT_TIMESTAMP,
                       estado_segmento    = 'ANULADO'
                   WHERE id_requerimiento  = :p_id2
                     AND fecha_fin_segmento IS NULL";
        $stmtSeg = oci_parse($conexion, $sqlSeg);
        oci_bind_by_name($stmtSeg, ':p_id2', $idRequerimiento);
        oci_execute($stmtSeg, OCI_NO_AUTO_COMMIT);

        oci_commit($conexion);
        $this->db->desconectar();
        return true;

    } catch (\Exception $e) {
        oci_rollback($conexion);
        $this->db->desconectar();
        return false;
    }
}
public function reactivarTicket($idRequerimiento, $idUsuarioReactivo) {
    $conexion = $this->db->conectar();
    try {
        $sql = "UPDATE requerimientos 
                SET estado_actual       = 'REACTIVADO',
                    id_usuario_reactivo = :p_usuario,
                    fecha_reactivacion  = CURRENT_TIMESTAMP
                WHERE id_requerimiento  = :p_id
                  AND estado_actual     = 'ANULADO'";
        $stmt = oci_parse($conexion, $sql);
        oci_bind_by_name($stmt, ':p_usuario', $idUsuarioReactivo);
        oci_bind_by_name($stmt, ':p_id',      $idRequerimiento);
        $r = oci_execute($stmt, OCI_NO_AUTO_COMMIT);
        if (!$r) throw new \Exception("Fallo al reactivar ticket.");

        // Nuevo segmento activo para que vuelva a "Mis Casos"
        $sqlSeg = "INSERT INTO segmentos_atencion 
                       (id_requerimiento, id_analista, estado_segmento, fecha_inicio_segmento)
                   VALUES (:p_req, :p_ana, 'REACTIVADO', CURRENT_TIMESTAMP)";
        $stmtSeg = oci_parse($conexion, $sqlSeg);
        oci_bind_by_name($stmtSeg, ':p_req', $idRequerimiento);
        oci_bind_by_name($stmtSeg, ':p_ana', $idUsuarioReactivo);
        $r2 = oci_execute($stmtSeg, OCI_NO_AUTO_COMMIT);
        if (!$r2) throw new \Exception("Fallo al crear segmento de reactivación.");

        oci_commit($conexion);
        $this->db->desconectar();
        return true;

    } catch (\Exception $e) {
        oci_rollback($conexion);
        $this->db->desconectar();
        return false;
    }
}
public function buscarCasosSimilares($idDependencia, $idSeguro, $descripcion) {
    $conexion = $this->db->conectar();
    
    $sql = "SELECT id_requerimiento, codigo_ticket, descripcion_requerimiento,
                   u.nombres || ' ' || u.apellidos AS nombre_solicitante,
                   TO_CHAR(r.fecha_recepcion, 'DD/MM/YYYY HH24:MI') AS fecha_creacion
            FROM requerimientos r
            INNER JOIN usuarios u ON r.id_solicitante = u.id_usuario
            WHERE r.id_catalogo_dependencia = :p_dep
              AND (r.id_seguro = :p_seguro OR :p_seguro IS NULL)
              AND r.fecha_recepcion >= (CURRENT_TIMESTAMP - INTERVAL '48' HOUR)
              AND r.estado_actual NOT IN ('ANULADO')
            ORDER BY r.fecha_recepcion DESC
            FETCH FIRST 10 ROWS ONLY";
    
    $stmt = oci_parse($conexion, $sql);
    oci_bind_by_name($stmt, ':p_dep',    $idDependencia);
    oci_bind_by_name($stmt, ':p_seguro', $idSeguro);
    oci_execute($stmt);
    
    $candidatos = [];
    while ($row = oci_fetch_array($stmt, OCI_ASSOC | OCI_RETURN_NULLS | OCI_RETURN_LOBS)) {
        $candidatos[] = $row;
    }
    oci_free_statement($stmt);
    $this->db->desconectar();
    
    $palabrasNuevas = $this->extraerPalabrasClave($descripcion);
    $similares = [];
    
    foreach ($candidatos as $c) {
        $descCandidato = is_object($c['DESCRIPCION_REQUERIMIENTO']) 
            ? $c['DESCRIPCION_REQUERIMIENTO']->load() 
            : $c['DESCRIPCION_REQUERIMIENTO'];
        
        $palabrasCandidato = $this->extraerPalabrasClave($descCandidato);
        $coincidencias = count(array_intersect($palabrasNuevas, $palabrasCandidato));
        
        if ($coincidencias >= 2) {
            $c['DESCRIPCION_REQUERIMIENTO'] = $descCandidato;
            $c['COINCIDENCIAS'] = $coincidencias;
            $similares[] = $c;
        }
    }
    
    usort($similares, fn($a, $b) => $b['COINCIDENCIAS'] <=> $a['COINCIDENCIAS']);
    
    return array_slice($similares, 0, 3);
}

private function extraerPalabrasClave($texto) {
    $texto = mb_strtolower($texto, 'UTF-8');
    $texto = preg_replace('/[^a-záéíóúñ0-9\s]/u', '', $texto);
    $palabras = explode(' ', $texto);
    return array_unique(array_filter($palabras, fn($p) => mb_strlen($p) > 3));
}
public function obtenerTiposConsulta() {
    $conexion = $this->db->conectar();
    $sql = "SELECT id_catalogo, valor 
            FROM catalogos_maestros 
            WHERE tipo_catalogo = 'TIPO_CONSULTA' 
              AND is_deleted = 0
            ORDER BY valor ASC";
    $stmt = oci_parse($conexion, $sql);
    oci_execute($stmt);
    $resultado = [];
    while ($row = oci_fetch_assoc($stmt)) { $resultado[] = $row; }
    oci_free_statement($stmt);
    $this->db->desconectar();
    return $resultado;
}
public function obtenerCasosDerivadosPorMi($idAnalista) {
    $conexion = $this->db->conectar();
    
    $sql = "
        SELECT 
            r.id_requerimiento,
            r.codigo_ticket,
            r.descripcion_requerimiento,
            r.estado_actual AS estado_ticket_actual,
            TO_CHAR(sa.fecha_fin_segmento, 'DD/MM/YYYY HH24:MI') AS fecha_derivacion,
            u.nombres || ' ' || u.apellidos AS nombre_solicitante,
            c.valor AS dependencia,
            (
                SELECT ud.nombres || ' ' || ud.apellidos
                FROM segmentos_atencion sad
                INNER JOIN usuarios ud ON sad.id_analista = ud.id_usuario
                WHERE sad.id_requerimiento = r.id_requerimiento
                  AND sad.id_segmento > sa.id_segmento
                ORDER BY sad.id_segmento ASC
                FETCH FIRST 1 ROWS ONLY
            ) AS nombre_analista_destino
        FROM requerimientos r
        INNER JOIN usuarios u ON r.id_solicitante = u.id_usuario
        INNER JOIN catalogos_maestros c ON r.id_catalogo_dependencia = c.id_catalogo
        INNER JOIN segmentos_atencion sa ON r.id_requerimiento = sa.id_requerimiento
        WHERE sa.id_analista = :p_analista
          AND sa.estado_segmento = 'DERIVADO'
          AND sa.fecha_fin_segmento IS NOT NULL
        ORDER BY sa.fecha_fin_segmento DESC
    ";

    $stmt = oci_parse($conexion, $sql);
    oci_bind_by_name($stmt, ':p_analista', $idAnalista);
    oci_execute($stmt);

    $resultados = [];
    while ($row = oci_fetch_array($stmt, OCI_ASSOC | OCI_RETURN_NULLS | OCI_RETURN_LOBS)) {
        $resultados[] = $row;
    }
    oci_free_statement($stmt);
    $this->db->desconectar();
    return $resultados;
}
public function listarHistorialPorAnalista($idAnalista, $anio, $mes, $busqueda = null, $fechaDesde = null, $fechaHasta = null) {
    $conexion = $this->db->conectar();
    
    $hayBusqueda = !empty($busqueda);
    $hayRangoFechas = !empty($fechaDesde) && !empty($fechaHasta);
    
    $condicionBusqueda = '';
    if ($hayBusqueda) {
        $condicionBusqueda = " AND (r.numero_documento LIKE :p_busqueda OR r.codigo_ticket LIKE :p_busqueda2) ";
    }
    
    // Sin búsqueda: mostramos casos cerrados por Carlos, Y también los que le
    // derivaron y todavía tiene abiertos (para que "Recibido de" aparezca siempre)
    $condicionEstado = $hayBusqueda 
        ? "" 
        : " AND (
              (sa.estado_segmento IN ('RESUELTO', 'INCONSISTENTE', 'EN OBSERVACION', 'DERIVADO') AND sa.fecha_fin_segmento IS NOT NULL)
              OR
              (sa.estado_segmento = 'DERIVADO' AND sa.fecha_fin_segmento IS NULL)
          ) ";
    
    if ($hayRangoFechas) {
        $condicionFecha = " AND TRUNC(NVL(sa.fecha_fin_segmento, r.fecha_recepcion)) BETWEEN TO_DATE(:p_desde, 'YYYY-MM-DD') AND TO_DATE(:p_hasta, 'YYYY-MM-DD') ";
    } else {
        $condicionFecha = " AND EXTRACT(YEAR FROM NVL(sa.fecha_fin_segmento, r.fecha_recepcion)) = :p_anio
                             AND EXTRACT(MONTH FROM NVL(sa.fecha_fin_segmento, r.fecha_recepcion)) = :p_mes ";
    }
    
    $sql = "
        SELECT 
            r.codigo_ticket, 
            c.valor AS dependencia,
            u.nombres || ' ' || u.apellidos AS nombre_solicitante,
            ua.nombres || ' ' || ua.apellidos AS nombre_analista, 
            r.tipo_documento,
            r.numero_documento,
            (
                SELECT ua_prev.nombres || ' ' || ua_prev.apellidos
                FROM segmentos_atencion sa_prev
                INNER JOIN usuarios ua_prev ON sa_prev.id_analista = ua_prev.id_usuario
                WHERE sa_prev.id_requerimiento = r.id_requerimiento
                  AND sa_prev.id_segmento < sa.id_segmento
                  AND sa_prev.id_analista != sa.id_analista
                ORDER BY sa_prev.id_segmento DESC
                FETCH FIRST 1 ROWS ONLY
            ) AS nombre_derivador,
            (
                SELECT u_dest.nombres || ' ' || u_dest.apellidos 
                FROM segmentos_atencion seg_dest
                INNER JOIN usuarios u_dest ON seg_dest.id_analista = u_dest.id_usuario
                WHERE seg_dest.id_requerimiento = r.id_requerimiento
                  AND seg_dest.id_segmento > sa.id_segmento
                ORDER BY seg_dest.id_segmento ASC
                FETCH FIRST 1 ROWS ONLY
            ) AS analista_derivado, 
            cs.nombre_seguro AS tipo_seguro, 
            r.estado_actual,
            r.descripcion_requerimiento,
            r.observaciones_cierre,
            r.archivo_evidencia,
            ci.descripcion_motivo,
            TO_CHAR(r.fecha_recepcion, 'DD/MM/YYYY HH24:MI') AS fecha_creacion,
            TO_CHAR(sa.fecha_fin_segmento, 'DD/MM/YYYY HH24:MI') AS fecha_cierre,
            TO_CHAR(r.fecha_modificacion, 'DD/MM/YYYY HH24:MI') AS fecha_modificacion,
            CASE WHEN sa.fecha_fin_segmento IS NOT NULL 
                 THEN ROUND((CAST(sa.fecha_fin_segmento AS DATE) - CAST(r.fecha_recepcion AS DATE)) * 24 * 60)
                 ELSE NULL 
            END AS minutos_resolucion
        FROM requerimientos r
        INNER JOIN usuarios u ON r.id_solicitante = u.id_usuario
        INNER JOIN segmentos_atencion sa ON r.id_requerimiento = sa.id_requerimiento
        INNER JOIN usuarios ua ON sa.id_analista = ua.id_usuario 
        INNER JOIN catalogos_maestros c ON r.id_catalogo_dependencia = c.id_catalogo
        LEFT JOIN catalogo_inconsistencias ci ON r.id_inconsistencia = ci.id_inconsistencia
        LEFT JOIN catalogo_seguros cs ON r.id_seguro = cs.id_seguro
        WHERE sa.id_analista = :p_analista
          $condicionEstado
          $condicionFecha
          $condicionBusqueda
        ORDER BY NVL(sa.fecha_fin_segmento, r.fecha_recepcion) DESC
    ";

    $stmt = oci_parse($conexion, $sql);
    oci_bind_by_name($stmt, ':p_analista', $idAnalista);
    
    if ($hayRangoFechas) {
        oci_bind_by_name($stmt, ':p_desde', $fechaDesde);
        oci_bind_by_name($stmt, ':p_hasta', $fechaHasta);
    } else {
        oci_bind_by_name($stmt, ':p_anio', $anio);
        oci_bind_by_name($stmt, ':p_mes', $mes);
    }
    
    if ($hayBusqueda) {
        $busquedaLike = '%' . $busqueda . '%';
        oci_bind_by_name($stmt, ':p_busqueda', $busquedaLike);
        oci_bind_by_name($stmt, ':p_busqueda2', $busquedaLike);
    }
    
    oci_execute($stmt);

    $resultados = [];
    while ($row = oci_fetch_array($stmt, OCI_ASSOC | OCI_RETURN_NULLS | OCI_RETURN_LOBS)) {
        $resultados[] = $row;
    }

    oci_free_statement($stmt);
    $this->db->desconectar();
    return $resultados;
}

public function solicitarValidacion($idRequerimiento, $idAnalistaSolicita, $estadoPropuesto, $idInconsistencia, $observaciones, $archivosAdjuntos = []) {
    $conexion = $this->db->conectar();
    try {
        $sqlBuscar = "SELECT id_analista_derivador FROM requerimientos WHERE id_requerimiento = :p_id";
        $stmtBuscar = oci_parse($conexion, $sqlBuscar);
        oci_bind_by_name($stmtBuscar, ':p_id', $idRequerimiento);
        oci_execute($stmtBuscar);
        $fila = oci_fetch_assoc($stmtBuscar);
        oci_free_statement($stmtBuscar);

        $idAprueba = $fila['ID_ANALISTA_DERIVADOR'] ?? null;
        if (!$idAprueba) throw new \Exception("No se encontró analista derivador.");

        $archivosStr = !empty($archivosAdjuntos) ? implode(',', $archivosAdjuntos) : null;

        $sqlIns = "INSERT INTO solicitudes_validacion 
                       (id_requerimiento, id_analista_solicita, id_analista_aprueba, 
                        estado_propuesto, id_inconsistencia, observaciones, archivos_adjuntos)
                   VALUES 
                       (:p_req, :p_solicita, :p_aprueba, :p_estado, :p_inc, :p_obs, :p_archivos)";
        $stmtIns = oci_parse($conexion, $sqlIns);
        oci_bind_by_name($stmtIns, ':p_req', $idRequerimiento);
        oci_bind_by_name($stmtIns, ':p_solicita', $idAnalistaSolicita);
        oci_bind_by_name($stmtIns, ':p_aprueba', $idAprueba);
        oci_bind_by_name($stmtIns, ':p_estado', $estadoPropuesto);
        oci_bind_by_name($stmtIns, ':p_inc', $idInconsistencia);
        oci_bind_by_name($stmtIns, ':p_obs', $observaciones);
        oci_bind_by_name($stmtIns, ':p_archivos', $archivosStr);
        $r1 = oci_execute($stmtIns, OCI_NO_AUTO_COMMIT);
        if (!$r1) throw new \Exception("Fallo al crear solicitud.");
        oci_free_statement($stmtIns);

        $sqlUpd = "UPDATE requerimientos SET estado_validacion = 'EN_VALIDACION' WHERE id_requerimiento = :p_id2";
        $stmtUpd = oci_parse($conexion, $sqlUpd);
        oci_bind_by_name($stmtUpd, ':p_id2', $idRequerimiento);
        $r2 = oci_execute($stmtUpd, OCI_NO_AUTO_COMMIT);
        if (!$r2) throw new \Exception("Fallo al marcar en validación.");
        oci_free_statement($stmtUpd);

        oci_commit($conexion);
        $exito = true;
    } catch (\Exception $e) {
        oci_rollback($conexion);
        $exito = false;
    }
    $this->db->desconectar();
    return $exito;
}

public function listarValidacionesPendientes($idAnalistaAprueba) {
    $conexion = $this->db->conectar();
    $sql = "SELECT sv.id_solicitud, sv.id_requerimiento, sv.estado_propuesto, sv.observaciones,
                   TO_CHAR(sv.fecha_solicitud, 'DD/MM/YYYY HH24:MI') AS fecha_solicitud,
                   r.codigo_ticket, r.descripcion_requerimiento,
                   us.nombres || ' ' || us.apellidos AS nombre_solicita,
                   ci.descripcion_motivo
            FROM solicitudes_validacion sv
            INNER JOIN requerimientos r ON sv.id_requerimiento = r.id_requerimiento
            INNER JOIN usuarios us ON sv.id_analista_solicita = us.id_usuario
            LEFT JOIN catalogo_inconsistencias ci ON sv.id_inconsistencia = ci.id_inconsistencia
            WHERE sv.id_analista_aprueba = :p_analista
              AND sv.estado_solicitud = 'PENDIENTE'
            ORDER BY sv.fecha_solicitud ASC";
    $stmt = oci_parse($conexion, $sql);
    oci_bind_by_name($stmt, ':p_analista', $idAnalistaAprueba);
    oci_execute($stmt);
    $resultado = [];
    while ($row = oci_fetch_array($stmt, OCI_ASSOC | OCI_RETURN_NULLS | OCI_RETURN_LOBS)) { $resultado[] = $row; }
    oci_free_statement($stmt);
    $this->db->desconectar();
    return $resultado;
}

public function aprobarValidacion($idSolicitud) {
    $conexion = $this->db->conectar();
    try {
        $sqlSel = "SELECT id_requerimiento, estado_propuesto, id_inconsistencia, observaciones 
                   FROM solicitudes_validacion WHERE id_solicitud = :p_sol";
        $stmtSel = oci_parse($conexion, $sqlSel);
        oci_bind_by_name($stmtSel, ':p_sol', $idSolicitud);
        oci_execute($stmtSel);
        $sol = oci_fetch_array($stmtSel, OCI_ASSOC | OCI_RETURN_NULLS | OCI_RETURN_LOBS);
        oci_free_statement($stmtSel);
        if (!$sol) throw new \Exception("Solicitud no encontrada.");

        $idReq = $sol['ID_REQUERIMIENTO'];
        $estadoFinal = $sol['ESTADO_PROPUESTO'];
        $idInc = $sol['ID_INCONSISTENCIA'];
        $obs = $sol['OBSERVACIONES'];

        // Cerramos el requerimiento con lo propuesto
        $sqlUpdReq = "UPDATE requerimientos 
                      SET estado_actual = :p_estado, id_inconsistencia = :p_inc,
                          observaciones_cierre = :p_obs, estado_validacion = 'APROBADO'
                      WHERE id_requerimiento = :p_id";
        $stmtUpdReq = oci_parse($conexion, $sqlUpdReq);
        oci_bind_by_name($stmtUpdReq, ':p_estado', $estadoFinal);
        oci_bind_by_name($stmtUpdReq, ':p_inc', $idInc);
        oci_bind_by_name($stmtUpdReq, ':p_obs', $obs);
        oci_bind_by_name($stmtUpdReq, ':p_id', $idReq);
        $r1 = oci_execute($stmtUpdReq, OCI_NO_AUTO_COMMIT);
        if (!$r1) throw new \Exception("Fallo al cerrar requerimiento.");
        oci_free_statement($stmtUpdReq);

        // Cerramos el segmento activo
        $sqlSeg = "UPDATE segmentos_atencion SET estado_segmento = :p_estado2, fecha_fin_segmento = CURRENT_TIMESTAMP
                   WHERE id_requerimiento = :p_id2 AND fecha_fin_segmento IS NULL";
        $stmtSeg = oci_parse($conexion, $sqlSeg);
        oci_bind_by_name($stmtSeg, ':p_estado2', $estadoFinal);
        oci_bind_by_name($stmtSeg, ':p_id2', $idReq);
        $r2 = oci_execute($stmtSeg, OCI_NO_AUTO_COMMIT);
        if (!$r2) throw new \Exception("Fallo al cerrar segmento.");
        oci_free_statement($stmtSeg);

        // Marcamos la solicitud como aprobada
        $sqlUpdSol = "UPDATE solicitudes_validacion 
                      SET estado_solicitud = 'APROBADO', fecha_respuesta = CURRENT_TIMESTAMP
                      WHERE id_solicitud = :p_sol2";
        $stmtUpdSol = oci_parse($conexion, $sqlUpdSol);
        oci_bind_by_name($stmtUpdSol, ':p_sol2', $idSolicitud);
        $r3 = oci_execute($stmtUpdSol, OCI_NO_AUTO_COMMIT);
        if (!$r3) throw new \Exception("Fallo al actualizar solicitud.");
        oci_free_statement($stmtUpdSol);

        oci_commit($conexion);
        $exito = true;
    } catch (\Exception $e) {
        oci_rollback($conexion);
        $exito = false;
    }
    $this->db->desconectar();
    return $exito;
}

public function rechazarValidacion($idSolicitud, $comentarioRechazo) {
    $conexion = $this->db->conectar();
    try {
        $sqlSel = "SELECT id_requerimiento FROM solicitudes_validacion WHERE id_solicitud = :p_sol";
        $stmtSel = oci_parse($conexion, $sqlSel);
        oci_bind_by_name($stmtSel, ':p_sol', $idSolicitud);
        oci_execute($stmtSel);
        $sol = oci_fetch_assoc($stmtSel);
        oci_free_statement($stmtSel);
        if (!$sol) throw new \Exception("Solicitud no encontrada.");
        $idReq = $sol['ID_REQUERIMIENTO'];

        // Regresamos el requerimiento a estado DERIVADO (vuelve a Pedro) con comentario
        $sqlUpdReq = "UPDATE requerimientos 
                SET estado_validacion = NULL
                WHERE id_requerimiento = :p_id";
        $stmtUpdReq = oci_parse($conexion, $sqlUpdReq);
        oci_bind_by_name($stmtUpdReq, ':p_id', $idReq);
        $r1 = oci_execute($stmtUpdReq, OCI_NO_AUTO_COMMIT);
        if (!$r1) throw new \Exception("Fallo al actualizar requerimiento.");
        oci_free_statement($stmtUpdReq);

        $sqlUpdSol = "UPDATE solicitudes_validacion 
                      SET estado_solicitud = 'RECHAZADO', comentario_rechazo = :p_com,
                          fecha_respuesta = CURRENT_TIMESTAMP
                      WHERE id_solicitud = :p_sol2";
        $stmtUpdSol = oci_parse($conexion, $sqlUpdSol);
        oci_bind_by_name($stmtUpdSol, ':p_com', $comentarioRechazo);
        oci_bind_by_name($stmtUpdSol, ':p_sol2', $idSolicitud);
        $r2 = oci_execute($stmtUpdSol, OCI_NO_AUTO_COMMIT);
        if (!$r2) throw new \Exception("Fallo al actualizar solicitud.");
        oci_free_statement($stmtUpdSol);

        oci_commit($conexion);
        $exito = true;
    } catch (\Exception $e) {
        oci_rollback($conexion);
        $exito = false;
    }
    $this->db->desconectar();
    return $exito;
}


public function obtenerHistorialValidaciones($idRequerimiento) {
    $conexion = $this->db->conectar();
    $sql = "SELECT sv.id_solicitud, sv.estado_propuesto, sv.observaciones, 
               sv.estado_solicitud, sv.comentario_rechazo, sv.archivos_adjuntos,
               TO_CHAR(sv.fecha_solicitud, 'DD/MM/YYYY HH24:MI') AS fecha_solicitud,
               TO_CHAR(sv.fecha_respuesta, 'DD/MM/YYYY HH24:MI') AS fecha_respuesta,
               us.nombres || ' ' || us.apellidos AS nombre_solicita,
               ua.nombres || ' ' || ua.apellidos AS nombre_aprueba
        FROM solicitudes_validacion sv
        INNER JOIN usuarios us ON sv.id_analista_solicita = us.id_usuario
        INNER JOIN usuarios ua ON sv.id_analista_aprueba = ua.id_usuario
        WHERE sv.id_requerimiento = :p_id
        ORDER BY sv.fecha_solicitud ASC";
    $stmt = oci_parse($conexion, $sql);
    oci_bind_by_name($stmt, ':p_id', $idRequerimiento);
    oci_execute($stmt);
    $resultado = [];
    while ($row = oci_fetch_array($stmt, OCI_ASSOC | OCI_RETURN_NULLS | OCI_RETURN_LOBS)) { $resultado[] = $row; }
    oci_free_statement($stmt);
    $this->db->desconectar();
    return $resultado;
}
public function listarTodosLosCasos($busqueda = null, $fechaDesde = null, $fechaHasta = null) {
    $conexion = $this->db->conectar();
    
    $condicionBusqueda = '';
    if (!empty($busqueda)) {
        $condicionBusqueda = " AND r.codigo_ticket LIKE :p_busqueda ";
    }

    $condicionFecha = '';
    if (!empty($fechaDesde) && !empty($fechaHasta)) {
        $condicionFecha = " AND TRUNC(r.fecha_recepcion) BETWEEN TO_DATE(:p_desde, 'YYYY-MM-DD') AND TO_DATE(:p_hasta, 'YYYY-MM-DD') ";
    }

    // El límite de 200 solo aplica cuando NO hay ningún filtro activo.
    // Si hay búsqueda o rango de fechas, se muestran TODOS los resultados que coincidan.
    $hayFiltro = !empty($busqueda) || (!empty($fechaDesde) && !empty($fechaHasta));
    $limite = $hayFiltro ? '' : 'FETCH FIRST 200 ROWS ONLY';

    $sql = "
       SELECT 
            r.id_requerimiento,
            r.codigo_ticket,
            r.descripcion_requerimiento,
            r.estado_actual,
            c.valor AS dependencia,
            us.nombres || ' ' || us.apellidos AS nombre_solicitante,
            ua.nombres || ' ' || ua.apellidos AS nombre_analista,
            TO_CHAR(r.fecha_recepcion, 'DD/MM/YYYY HH24:MI') AS fecha_creacion,
            TO_CHAR(sa.fecha_fin_segmento, 'DD/MM/YYYY HH24:MI') AS fecha_cierre
        FROM requerimientos r
        INNER JOIN usuarios us ON r.id_solicitante = us.id_usuario
        INNER JOIN catalogos_maestros c ON r.id_catalogo_dependencia = c.id_catalogo
        LEFT JOIN segmentos_atencion sa 
            ON sa.id_requerimiento = r.id_requerimiento
            AND sa.id_segmento = (
                    SELECT MAX(sa2.id_segmento) 
                    FROM segmentos_atencion sa2 
                    WHERE sa2.id_requerimiento = r.id_requerimiento
                )
        LEFT JOIN usuarios ua ON sa.id_analista = ua.id_usuario
        WHERE 1=1
        $condicionBusqueda
        $condicionFecha
        ORDER BY r.fecha_recepcion DESC
        $limite
    ";

    $stmt = oci_parse($conexion, $sql);
    if (!empty($busqueda)) {
        $busquedaLike = '%' . $busqueda . '%';
        oci_bind_by_name($stmt, ':p_busqueda', $busquedaLike);
    }
    if (!empty($fechaDesde) && !empty($fechaHasta)) {
        oci_bind_by_name($stmt, ':p_desde', $fechaDesde);
        oci_bind_by_name($stmt, ':p_hasta', $fechaHasta);
    }
    oci_execute($stmt);

    $resultados = [];
    while ($row = oci_fetch_array($stmt, OCI_ASSOC | OCI_RETURN_NULLS | OCI_RETURN_LOBS)) {
        $resultados[] = $row;
    }
    oci_free_statement($stmt);
    $this->db->desconectar();
    return $resultados;
}
    /**
     * Pausa el SLA del analista y envía el caso a una entidad externa (Ej. GCTIC)
     */
    public function derivarAExterno($idRequerimiento, $idAnalistaActual, $entidadExterna, $contactoExterno, $observaciones, $archivoEvidencia = null, $fechaHoraEnvio = null) {
    $conexion = $this->db->conectar();
    try {
        // 1. Cerramos el segmento actual
        $sqlCierre = "UPDATE segmentos_atencion 
                      SET fecha_fin_segmento = CURRENT_TIMESTAMP, 
                          estado_segmento = 'DERIVADO EXTERNO',
                          respuesta_resolucion = :p_obs 
                      WHERE id_requerimiento = :p_req 
                        AND id_analista = :p_analista 
                        AND fecha_fin_segmento IS NULL";
        $stmt1 = oci_parse($conexion, $sqlCierre);
        oci_bind_by_name($stmt1, ':p_obs', $observaciones);
        oci_bind_by_name($stmt1, ':p_req', $idRequerimiento);
        oci_bind_by_name($stmt1, ':p_analista', $idAnalistaActual);
        $r1 = oci_execute($stmt1, OCI_NO_AUTO_COMMIT);
        if (!$r1) throw new \Exception("Fallo al cerrar segmento actual");

        // 2. Creamos el segmento externo, usando la hora manual de envío del correo
        //    (no CURRENT_TIMESTAMP) para no penalizar al analista por el desfase
        //    entre cuándo envió el correo y cuándo lo registró en el sistema.
        $sqlNuevo = "INSERT INTO segmentos_atencion 
                       (id_requerimiento, id_analista, estado_segmento, fecha_inicio_segmento, entidad_externa, contacto_externo, archivo_adjunto) 
                     VALUES (:p_req, :p_analista, 'DERIVADO EXTERNO', TO_TIMESTAMP(:p_fecha_hora, 'YYYY-MM-DD HH24:MI'), :p_entidad, :p_contacto, :p_archivo)";
        $stmt2 = oci_parse($conexion, $sqlNuevo);
        oci_bind_by_name($stmt2, ':p_req', $idRequerimiento);
        oci_bind_by_name($stmt2, ':p_analista', $idAnalistaActual);
        oci_bind_by_name($stmt2, ':p_fecha_hora', $fechaHoraEnvio);
        oci_bind_by_name($stmt2, ':p_entidad', $entidadExterna);
        oci_bind_by_name($stmt2, ':p_contacto', $contactoExterno);
        oci_bind_by_name($stmt2, ':p_archivo', $archivoEvidencia);
        $r2 = oci_execute($stmt2, OCI_NO_AUTO_COMMIT);
        if (!$r2) throw new \Exception("Fallo al crear segmento externo");

        // 3. Actualizamos estado general
        $sqlUpd = "UPDATE requerimientos 
                   SET estado_actual = 'DERIVADO EXTERNO',
                       fecha_ultima_modificacion = CURRENT_TIMESTAMP
                   WHERE id_requerimiento = :p_req";
        $stmt3 = oci_parse($conexion, $sqlUpd);
        oci_bind_by_name($stmt3, ':p_req', $idRequerimiento);
        $r3 = oci_execute($stmt3, OCI_NO_AUTO_COMMIT);
        if (!$r3) throw new \Exception("Fallo al actualizar estado");

        oci_commit($conexion);
        $exito = true;
    } catch (\Exception $e) {
        oci_rollback($conexion);
        $exito = false;
    }

    @oci_free_statement($stmt1);
    @oci_free_statement($stmt2);
    @oci_free_statement($stmt3);
    $this->db->desconectar();
    return $exito;
}

/**
     * Lista los casos que están en pausa esperando respuesta de un externo
     */

    public function listarCasosExternos($idAnalista) {
        $conexion = $this->db->conectar();
        $sql = "
            SELECT 
                r.id_requerimiento,
                r.codigo_ticket, 
                r.descripcion_requerimiento, 
                sa.entidad_externa,
                TO_CHAR(sa.fecha_inicio_segmento, 'DD/MM/YYYY HH24:MI') AS fecha_derivacion,
                u.nombres || ' ' || u.apellidos AS nombre_solicitante,
                c.valor AS dependencia
            FROM requerimientos r
            INNER JOIN usuarios u ON r.id_solicitante = u.id_usuario
            INNER JOIN catalogos_maestros c ON r.id_catalogo_dependencia = c.id_catalogo
            INNER JOIN segmentos_atencion sa ON r.id_requerimiento = sa.id_requerimiento
            WHERE sa.id_analista = :p_analista
              AND sa.estado_segmento = 'DERIVADO EXTERNO'
              AND sa.fecha_fin_segmento IS NULL
            ORDER BY sa.fecha_inicio_segmento DESC
        ";

        $stmt = oci_parse($conexion, $sql);
        oci_bind_by_name($stmt, ':p_analista', $idAnalista);
        oci_execute($stmt);

        $resultados = [];
        while ($row = oci_fetch_array($stmt, OCI_ASSOC | OCI_RETURN_NULLS | OCI_RETURN_LOBS)) {
            $resultados[] = $row;
        }

        oci_free_statement($stmt);
        $this->db->desconectar();
        return $resultados;
    }

    /**
     * Registra la respuesta de GCTIC usando la hora manual y reanuda el SLA del analista
     */
    public function registrarRespuestaExterna($idRequerimiento, $idAnalista, $fechaHoraManual, $comentarios, $archivo = null) {
        $conexion = $this->db->conectar();
        try {
            $sql1 = "UPDATE segmentos_atencion 
                     SET fecha_fin_segmento = TO_TIMESTAMP(:p_fecha_hora, 'YYYY-MM-DD HH24:MI'), 
                         respuesta_resolucion = :p_obs,
                         archivo_adjunto = CASE WHEN :p_arch IS NOT NULL THEN :p_arch ELSE archivo_adjunto END
                     WHERE id_requerimiento = :p_req 
                       AND estado_segmento = 'DERIVADO EXTERNO' 
                       AND fecha_fin_segmento IS NULL";
            $stmt1 = oci_parse($conexion, $sql1);
            oci_bind_by_name($stmt1, ':p_fecha_hora', $fechaHoraManual);
            oci_bind_by_name($stmt1, ':p_obs', $comentarios);
            oci_bind_by_name($stmt1, ':p_arch', $archivo);
            oci_bind_by_name($stmt1, ':p_req', $idRequerimiento);
            $r1 = oci_execute($stmt1, OCI_NO_AUTO_COMMIT);
            if (!$r1) throw new \Exception("Fallo al cerrar segmento externo");

            // Reanudamos el tiempo de Pedro con el estado RETORNO EXTERNO
            $sql2 = "INSERT INTO segmentos_atencion 
                       (id_requerimiento, id_analista, estado_segmento, fecha_inicio_segmento) 
                     VALUES (:p_req, :p_analista, 'RETORNO EXTERNO', TO_TIMESTAMP(:p_fecha_hora2, 'YYYY-MM-DD HH24:MI'))";
            $stmt2 = oci_parse($conexion, $sql2);
            oci_bind_by_name($stmt2, ':p_req', $idRequerimiento);
            oci_bind_by_name($stmt2, ':p_analista', $idAnalista);
            oci_bind_by_name($stmt2, ':p_fecha_hora2', $fechaHoraManual);
            $r2 = oci_execute($stmt2, OCI_NO_AUTO_COMMIT);
            if (!$r2) throw new \Exception("Fallo al abrir segmento");

            // Regresamos el ticket a estado RETORNO EXTERNO en la tabla principal
            $sql3 = "UPDATE requerimientos 
                     SET estado_actual = 'RETORNO EXTERNO', 
                         fecha_ultima_modificacion = CURRENT_TIMESTAMP 
                     WHERE id_requerimiento = :p_req";
            $stmt3 = oci_parse($conexion, $sql3);
            oci_bind_by_name($stmt3, ':p_req', $idRequerimiento);
            $r3 = oci_execute($stmt3, OCI_NO_AUTO_COMMIT);
            if (!$r3) throw new \Exception("Fallo al actualizar estado");

            oci_commit($conexion);
            $exito = true;
        } catch (\Exception $e) {
            oci_rollback($conexion);
            $exito = false;
        }

        @oci_free_statement($stmt1); @oci_free_statement($stmt2); @oci_free_statement($stmt3);
        $this->db->desconectar();
        return $exito;
    }

    /**
     * Recupera el ticket a la bandeja sin respuesta, reanudando el tiempo desde AHORA
     */
    public function recuperarExterno($idRequerimiento, $idAnalista) {
        $conexion = $this->db->conectar();
        try {
            $sql1 = "UPDATE segmentos_atencion 
                     SET fecha_fin_segmento = CURRENT_TIMESTAMP, 
                         respuesta_resolucion = 'Recuperado a bandeja. Sin respuesta externa.' 
                     WHERE id_requerimiento = :p_req AND estado_segmento = 'DERIVADO EXTERNO' AND fecha_fin_segmento IS NULL";
            $stmt1 = oci_parse($conexion, $sql1);
            oci_bind_by_name($stmt1, ':p_req', $idRequerimiento);
            oci_execute($stmt1, OCI_NO_AUTO_COMMIT);

            $sql2 = "INSERT INTO segmentos_atencion (id_requerimiento, id_analista, estado_segmento, fecha_inicio_segmento) 
                     VALUES (:p_req, :p_analista, 'RETORNO EXTERNO', CURRENT_TIMESTAMP)";
            $stmt2 = oci_parse($conexion, $sql2);
            oci_bind_by_name($stmt2, ':p_req', $idRequerimiento);
            oci_bind_by_name($stmt2, ':p_analista', $idAnalista);
            oci_execute($stmt2, OCI_NO_AUTO_COMMIT);

            $sql3 = "UPDATE requerimientos SET estado_actual = 'RETORNO EXTERNO', fecha_ultima_modificacion = CURRENT_TIMESTAMP WHERE id_requerimiento = :p_req";
            $stmt3 = oci_parse($conexion, $sql3);
            oci_bind_by_name($stmt3, ':p_req', $idRequerimiento);
            oci_execute($stmt3, OCI_NO_AUTO_COMMIT);

            oci_commit($conexion);
            $exito = true;
        } catch (\Exception $e) {
            oci_rollback($conexion);
            $exito = false;
        }
        $this->db->desconectar();
        return $exito;
    }

    public function obtenerFechaInicioExterno($idRequerimiento) {
    $conexion = $this->db->conectar();
    $sql = "SELECT TO_CHAR(fecha_inicio_segmento, 'YYYY-MM-DD HH24:MI') AS fecha_inicio
            FROM segmentos_atencion
            WHERE id_requerimiento = :p_id
              AND estado_segmento = 'DERIVADO EXTERNO'
              AND fecha_fin_segmento IS NULL";
    $stmt = oci_parse($conexion, $sql);
    oci_bind_by_name($stmt, ':p_id', $idRequerimiento);
    oci_execute($stmt);
    $fila = oci_fetch_assoc($stmt);
    oci_free_statement($stmt);
    $this->db->desconectar();
    return $fila['FECHA_INICIO'] ?? null;
}

public function obtenerKPIGeneral() {
    $conexion = $this->db->conectar();
    $sql = "SELECT
                (SELECT COUNT(*) FROM requerimientos WHERE estado_actual = 'PENDIENTE') AS total_pendiente,
                (SELECT COUNT(*) FROM requerimientos WHERE estado_actual = 'RESUELTO') AS total_resuelto,
                (SELECT COUNT(*) FROM requerimientos WHERE estado_actual = 'INCONSISTENTE') AS total_inconsistente,
                (SELECT COUNT(*) FROM requerimientos WHERE estado_actual = 'EN OBSERVACION') AS total_en_observacion
            FROM DUAL";
    $stmt = oci_parse($conexion, $sql);
    oci_execute($stmt);
    $resultado = oci_fetch_assoc($stmt);
    oci_free_statement($stmt);
    $this->db->desconectar();
    return $resultado;
}
public function obtenerIdPorCodigo($codigoTicket) {
    $conexion = $this->db->conectar();
    $sql = "SELECT id_requerimiento FROM requerimientos WHERE codigo_ticket = :p_codigo";
    $stmt = oci_parse($conexion, $sql);
    oci_bind_by_name($stmt, ':p_codigo', $codigoTicket);
    oci_execute($stmt);
    $fila = oci_fetch_assoc($stmt);
    oci_free_statement($stmt);
    $this->db->desconectar();
    return $fila['ID_REQUERIMIENTO'] ?? null;
}

public function actualizarArchivoEvidencia($idRequerimiento, $archivosStr) {
    $conexion = $this->db->conectar();
    $sql = "UPDATE requerimientos SET archivo_evidencia = :p_archivos WHERE id_requerimiento = :p_id";
    $stmt = oci_parse($conexion, $sql);
    oci_bind_by_name($stmt, ':p_archivos', $archivosStr);
    oci_bind_by_name($stmt, ':p_id', $idRequerimiento);
    $exito = oci_execute($stmt, OCI_COMMIT_ON_SUCCESS);
    oci_free_statement($stmt);
    $this->db->desconectar();
    return $exito;
}
public function obtenerCodigoPorId($idRequerimiento) {
    $conexion = $this->db->conectar();
    $sql = "SELECT codigo_ticket FROM requerimientos WHERE id_requerimiento = :p_id";
    $stmt = oci_parse($conexion, $sql);
    oci_bind_by_name($stmt, ':p_id', $idRequerimiento);
    oci_execute($stmt);
    $fila = oci_fetch_assoc($stmt);
    oci_free_statement($stmt);
    $this->db->desconectar();
    return $fila['CODIGO_TICKET'] ?? null;
}
public function obtenerHistorialExterno($idRequerimiento) {
    $conexion = $this->db->conectar();
    $sql = "SELECT sa.estado_segmento, sa.entidad_externa, sa.contacto_externo, sa.archivo_adjunto,
                   TO_CHAR(sa.fecha_inicio_segmento, 'DD/MM/YYYY HH24:MI') AS fecha_inicio,
                   TO_CHAR(sa.fecha_fin_segmento, 'DD/MM/YYYY HH24:MI') AS fecha_fin,
                   sa.respuesta_resolucion,
                   ua.nombres || ' ' || ua.apellidos AS nombre_analista
            FROM segmentos_atencion sa
            INNER JOIN usuarios ua ON sa.id_analista = ua.id_usuario
            WHERE sa.id_requerimiento = :p_id
              AND sa.estado_segmento IN ('DERIVADO EXTERNO', 'RETORNO EXTERNO')
            ORDER BY sa.id_segmento ASC";
    $stmt = oci_parse($conexion, $sql);
    oci_bind_by_name($stmt, ':p_id', $idRequerimiento);
    oci_execute($stmt);
    $resultado = [];
    while ($row = oci_fetch_array($stmt, OCI_ASSOC | OCI_RETURN_NULLS | OCI_RETURN_LOBS)) { $resultado[] = $row; }
    oci_free_statement($stmt);
    $this->db->desconectar();
    return $resultado;
}

public function listarMisTicketsSolicitantePaginado($idSolicitante, $pagina = 1, $porPagina = 15, $mes = null, $anio = null, $busqueda = null) {
    $conexion = $this->db->conectar();
    
    $offset = ($pagina - 1) * $porPagina;
    
    $condicionFecha = '';
    if (!empty($mes) && !empty($anio)) {
        $condicionFecha = " AND EXTRACT(MONTH FROM r.fecha_recepcion) = :p_mes 
                             AND EXTRACT(YEAR FROM r.fecha_recepcion) = :p_anio ";
    }

    $condicionBusqueda = '';
    if (!empty($busqueda)) {
        $condicionBusqueda = " AND r.codigo_ticket LIKE :p_busqueda ";
    }

    // 1. Contamos el total para calcular cuántas páginas hay
    $sqlTotal = "SELECT COUNT(*) AS TOTAL 
                 FROM requerimientos r 
                 WHERE r.id_solicitante = :p_solicitante $condicionFecha $condicionBusqueda";
    $stmtTotal = oci_parse($conexion, $sqlTotal);
    oci_bind_by_name($stmtTotal, ':p_solicitante', $idSolicitante);
    if (!empty($mes) && !empty($anio)) {
        oci_bind_by_name($stmtTotal, ':p_mes', $mes);
        oci_bind_by_name($stmtTotal, ':p_anio', $anio);
    }
    if (!empty($busqueda)) {
        $busquedaLike = '%' . $busqueda . '%';
        oci_bind_by_name($stmtTotal, ':p_busqueda', $busquedaLike);
    }
    oci_execute($stmtTotal);
    $filaTotal = oci_fetch_assoc($stmtTotal);
    $totalRegistros = (int)$filaTotal['TOTAL'];
    oci_free_statement($stmtTotal);

    // 2. Traemos solo la página pedida
    $sql = "
        SELECT 
            r.codigo_ticket, 
            c.valor AS dependencia,
            r.estado_actual,
            r.descripcion_requerimiento,
            r.motivo_anulacion,
            (
                SELECT ua.nombres || ' ' || ua.apellidos
                FROM segmentos_atencion sa
                INNER JOIN usuarios ua ON sa.id_analista = ua.id_usuario
                WHERE sa.id_requerimiento = r.id_requerimiento
                  AND sa.fecha_fin_segmento IS NULL
                ORDER BY sa.id_segmento DESC
                FETCH FIRST 1 ROWS ONLY
            ) AS nombre_analista_actual,
            TO_CHAR(r.fecha_recepcion, 'DD/MM/YYYY HH24:MI') AS fecha_creacion
        FROM requerimientos r
        INNER JOIN catalogos_maestros c ON r.id_catalogo_dependencia = c.id_catalogo
        WHERE r.id_solicitante = :p_solicitante2
          $condicionFecha
          $condicionBusqueda
        ORDER BY r.fecha_recepcion DESC
        OFFSET :p_offset ROWS FETCH NEXT :p_porpagina ROWS ONLY
    ";

    $stmt = oci_parse($conexion, $sql);
    oci_bind_by_name($stmt, ':p_solicitante2', $idSolicitante);
    if (!empty($mes) && !empty($anio)) {
        oci_bind_by_name($stmt, ':p_mes', $mes);
        oci_bind_by_name($stmt, ':p_anio', $anio);
    }
    if (!empty($busqueda)) {
        oci_bind_by_name($stmt, ':p_busqueda', $busquedaLike);
    }
    oci_bind_by_name($stmt, ':p_offset', $offset);
    oci_bind_by_name($stmt, ':p_porpagina', $porPagina);
    oci_execute($stmt);

    $resultados = [];
    while ($row = oci_fetch_array($stmt, OCI_ASSOC | OCI_RETURN_NULLS | OCI_RETURN_LOBS)) {
        $resultados[] = $row;
    }
    oci_free_statement($stmt);
    $this->db->desconectar();

    return [
        'tickets' => $resultados,
        'total' => $totalRegistros,
        'total_paginas' => ceil($totalRegistros / $porPagina),
        'pagina_actual' => $pagina,
    ];
}

public function marcarEnObservacion($idRequerimiento, $idUsuarioObservo, $motivo) {
    $conexion = $this->db->conectar();
    try {
        // 1. Averiguamos quién tenía el segmento activo (el analista original a notificar)
        $sqlBuscar = "SELECT sa.id_analista 
                      FROM segmentos_atencion sa
                      WHERE sa.id_requerimiento = :p_id
                        AND sa.fecha_fin_segmento IS NULL
                      ORDER BY sa.id_segmento DESC
                      FETCH FIRST 1 ROWS ONLY";
        $stmtBuscar = oci_parse($conexion, $sqlBuscar);
        oci_bind_by_name($stmtBuscar, ':p_id', $idRequerimiento);
        oci_execute($stmtBuscar);
        $fila = oci_fetch_assoc($stmtBuscar);
        oci_free_statement($stmtBuscar);
        $idAnalistaOriginal = $fila['ID_ANALISTA'] ?? null;

        // 2. Cerramos cualquier segmento que siga abierto (por si acaso)
        $sqlCierre = "UPDATE segmentos_atencion 
                      SET fecha_fin_segmento = CURRENT_TIMESTAMP
                      WHERE id_requerimiento = :p_id
                        AND fecha_fin_segmento IS NULL";
        $stmtCierre = oci_parse($conexion, $sqlCierre);
        oci_bind_by_name($stmtCierre, ':p_id', $idRequerimiento);
        oci_execute($stmtCierre, OCI_NO_AUTO_COMMIT);
        oci_free_statement($stmtCierre);

        // 3. Creamos el nuevo segmento EN OBSERVACION, asignado a quien lo reabrió
        $sqlSeg = "INSERT INTO segmentos_atencion 
                       (id_requerimiento, id_analista, estado_segmento, fecha_inicio_segmento) 
                   VALUES (:p_req, :p_ana, 'EN OBSERVACION', CURRENT_TIMESTAMP)";
        $stmtSeg = oci_parse($conexion, $sqlSeg);
        oci_bind_by_name($stmtSeg, ':p_req', $idRequerimiento);
        oci_bind_by_name($stmtSeg, ':p_ana', $idUsuarioObservo);
        $r1 = oci_execute($stmtSeg, OCI_NO_AUTO_COMMIT);
        if (!$r1) throw new \Exception("Fallo al crear segmento de observación.");
        oci_free_statement($stmtSeg);

        // 4. Actualizamos el ticket
        $sqlUpd = "UPDATE requerimientos 
                   SET estado_actual = 'EN OBSERVACION',
                       motivo_observacion = :p_motivo,
                       id_usuario_observo = :p_observo,
                       fecha_observacion = CURRENT_TIMESTAMP,
                       fecha_ultima_modificacion = CURRENT_TIMESTAMP
                   WHERE id_requerimiento = :p_req2";
        $stmtUpd = oci_parse($conexion, $sqlUpd);
        oci_bind_by_name($stmtUpd, ':p_motivo', $motivo);
        oci_bind_by_name($stmtUpd, ':p_observo', $idUsuarioObservo);
        oci_bind_by_name($stmtUpd, ':p_req2', $idRequerimiento);
        $r2 = oci_execute($stmtUpd, OCI_NO_AUTO_COMMIT);
        if (!$r2) throw new \Exception("Fallo al actualizar el ticket.");
        oci_free_statement($stmtUpd);

        // 5. Creamos la notificación para el analista original (si existe y es distinto de quien observa)
        if ($idAnalistaOriginal && $idAnalistaOriginal != $idUsuarioObservo) {
            $sqlCodigo = "SELECT codigo_ticket FROM requerimientos WHERE id_requerimiento = :p_id2";
            $stmtCodigo = oci_parse($conexion, $sqlCodigo);
            oci_bind_by_name($stmtCodigo, ':p_id2', $idRequerimiento);
            oci_execute($stmtCodigo);
            $filaCod = oci_fetch_assoc($stmtCodigo);
            $codigoTicket = $filaCod['CODIGO_TICKET'] ?? '';
            oci_free_statement($stmtCodigo);

            $mensaje = "Tu caso $codigoTicket fue puesto en observación para revisión.";

            $sqlNotif = "INSERT INTO notificaciones 
                             (id_usuario_destino, tipo, id_requerimiento, mensaje) 
                         VALUES (:p_dest, 'CASO_EN_OBSERVACION', :p_req3, :p_msg)";
            $stmtNotif = oci_parse($conexion, $sqlNotif);
            oci_bind_by_name($stmtNotif, ':p_dest', $idAnalistaOriginal);
            oci_bind_by_name($stmtNotif, ':p_req3', $idRequerimiento);
            oci_bind_by_name($stmtNotif, ':p_msg', $mensaje);
            $r3 = oci_execute($stmtNotif, OCI_NO_AUTO_COMMIT);
            if (!$r3) throw new \Exception("Fallo al crear notificación.");
            oci_free_statement($stmtNotif);
        }

        oci_commit($conexion);
        $exito = true;
    } catch (\Exception $e) {
        oci_rollback($conexion);
        $exito = false;
        
    }

    $this->db->desconectar();
    return $exito;
}

public function obtenerNotificaciones($idUsuario) {
    $conexion = $this->db->conectar();
    $sql = "SELECT n.id_notificacion, n.mensaje, n.leida, n.id_requerimiento,
                   r.codigo_ticket,
                   TO_CHAR(n.fecha_creacion, 'DD/MM/YYYY HH24:MI') AS fecha_creacion
            FROM notificaciones n
            INNER JOIN requerimientos r ON n.id_requerimiento = r.id_requerimiento
            WHERE n.id_usuario_destino = :p_usuario
            ORDER BY n.fecha_creacion DESC
            FETCH FIRST 20 ROWS ONLY";
    $stmt = oci_parse($conexion, $sql);
    oci_bind_by_name($stmt, ':p_usuario', $idUsuario);
    oci_execute($stmt);
    $resultado = [];
    while ($row = oci_fetch_assoc($stmt)) { $resultado[] = $row; }
    oci_free_statement($stmt);
    $this->db->desconectar();
    return $resultado;
}

public function contarNotificacionesNoLeidas($idUsuario) {
    $conexion = $this->db->conectar();
    $sql = "SELECT COUNT(*) AS TOTAL FROM notificaciones WHERE id_usuario_destino = :p_usuario AND leida = 0";
    $stmt = oci_parse($conexion, $sql);
    oci_bind_by_name($stmt, ':p_usuario', $idUsuario);
    oci_execute($stmt);
    $fila = oci_fetch_assoc($stmt);
    oci_free_statement($stmt);
    $this->db->desconectar();
    return (int)($fila['TOTAL'] ?? 0);
}

public function marcarNotificacionesLeidas($idUsuario) {
    $conexion = $this->db->conectar();
    $sql = "UPDATE notificaciones SET leida = 1 WHERE id_usuario_destino = :p_usuario AND leida = 0";
    $stmt = oci_parse($conexion, $sql);
    oci_bind_by_name($stmt, ':p_usuario', $idUsuario);
    $exito = oci_execute($stmt, OCI_COMMIT_ON_SUCCESS);
    oci_free_statement($stmt);
    $this->db->desconectar();
    return $exito;
}

} // ← cierra la clase RequerimientoModel
?>