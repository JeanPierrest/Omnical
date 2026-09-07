<?php include 'header.php'; ?>

<style>
    .filtro-fecha-bar {
        display: flex;
        align-items: center;
        gap: 10px;
        background: white;
        padding: 12px 16px;
        border-radius: 6px;
        box-shadow: var(--shadow);
        margin-bottom: 20px;
        flex-wrap: wrap;
    }
    .filtro-fecha-bar label {
        font-size: 12px;
        font-weight: 700;
        color: var(--text-muted);
        text-transform: uppercase;
        margin-right: 4px;
    }
    .filtro-fecha-bar select {
        padding: 8px 10px;
        border: 1px solid var(--border-color);
        border-radius: 4px;
        font-size: 13px;
        font-weight: 600;
        color: var(--text-main);
    }
    .btn-buscar-periodo {
        background: var(--primary);
        color: white;
        border: none;
        padding: 8px 18px;
        border-radius: 4px;
        font-size: 13px;
        font-weight: 700;
        cursor: pointer;
    }
    .btn-buscar-periodo:hover { opacity: 0.9; }
    .btn-limpiar-periodo {
        background: #64748B;
        color: white;
        border: none;
        padding: 8px 14px;
        border-radius: 4px;
        font-size: 13px;
        font-weight: 700;
        cursor: pointer;
        text-decoration: none;
        display: inline-block;
    }
    .btn-limpiar-periodo:hover { opacity: 0.85; }
    .info-tip-rango {
        font-size: 12px;
        color: #92400E;
        background: #FFFBEB;
        border-left: 3px solid #F59E0B;
        padding: 8px 12px;
        border-radius: 4px;
        margin-bottom: 20px;
    }
    .periodo-actual-tag {
        margin-left: auto;
        font-size: 12px;
        color: var(--text-muted);
        font-style: italic;
    }
    .badge-derivado   { background: #FFF3E0; color: #EA580C; border: 1px solid #FED7AA; }
    .badge-anulado    { background: #F1F5F9; color: #64748B; border: 1px solid #CBD5E1; text-decoration: line-through; }
    .badge-reactivado { background: #EDE9FE; color: #6D28D9; border: 1px solid #DDD6FE; }
    .badge-pausado    { background: #EEF2FF; color: #4338CA; border: 1px solid #C7D2FE; }
    .badge-retorno    { background: #FCE7F3; color: #BE185D; border: 1px solid #FBCFE8; }

    .archivos-grupo {
        margin-bottom: 10px;
    }
    .archivos-grupo strong {
        font-size: 11px;
        color: var(--text-muted);
        text-transform: uppercase;
        display: block;
        margin-bottom: 4px;
    }
    .archivos-links { display: flex; flex-wrap: wrap; gap: 6px; }
    .archivo-link {
        background: #3B82F6;
        color: white;
        text-decoration: none;
        padding: 4px 10px;
        border-radius: 4px;
        font-size: 11px;
        font-weight: 600;
    }
</style>

<div class="container">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h2 style="color: var(--secondary); border-left: 4px solid var(--primary); padding-left: 10px; margin: 0;">
            Mi Historial de Casos
        </h2>
        
        <div style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;" id="filtros-estados">
            <button class="btn btn-filter activo" data-estado="TODOS" onclick="filtrarTabla('TODOS', this)" style="background: var(--primary); color: white;">Todos</button>
            <button class="btn btn-filter" data-estado="RESUELTO" onclick="filtrarTabla('RESUELTO', this)" style="background: white; color: var(--text-main); border: 1px solid var(--border-color);">Resueltos</button>
            <button class="btn btn-filter" data-estado="INCONSISTENTE" onclick="filtrarTabla('INCONSISTENTE', this)" style="background: white; color: var(--text-main); border: 1px solid var(--border-color);">Inconsistentes</button>
            <button class="btn btn-filter" data-estado="PENDIENTE" onclick="filtrarTabla('PENDIENTE', this)" style="background: white; color: var(--text-main); border: 1px solid var(--border-color);">Pendientes</button>
            <button class="btn btn-filter" data-estado="ASIGNADO" onclick="filtrarTabla('ASIGNADO', this)" style="background: white; color: var(--text-main); border: 1px solid var(--border-color);">Asignados</button>
            <button class="btn btn-filter" data-estado="DERIVADO" onclick="filtrarTabla('DERIVADO', this)" style="background: white; color: var(--text-main); border: 1px solid var(--border-color);">Derivados</button>
            <button class="btn btn-filter" data-estado="ANULADO" onclick="filtrarTabla('ANULADO', this)" style="background: white; color: var(--text-main); border: 1px solid var(--border-color);">Anulados</button>
        </div>
    </div>

    <div style="margin-bottom: 15px; display:flex; align-items:center; gap:8px;">
        <span style="font-size:13px; color:var(--text-muted); font-weight:600;">Dependencia:</span>
        <select id="filtroDependenciaHist" onchange="filtrarDependenciaHist(this.value)"
                style="padding:7px 12px; border:1px solid var(--border-color); border-radius:20px; font-size:12px; font-weight:600; color:var(--secondary); min-width:240px;">
            <option value="todas">🏢 Todas las dependencias</option>
            <?php if (!empty($dependencias)): ?>
                <?php foreach ($dependencias as $dep): ?>
                    <option value="<?= htmlspecialchars($dep['VALOR']) ?>"><?= htmlspecialchars($dep['VALOR']) ?></option>
                <?php endforeach; ?>
            <?php endif; ?>
        </select>
    </div>

    <!-- FILTRO POR MES Y AÑO -->
    <form method="GET" action="index.php" class="filtro-fecha-bar">
        <input type="hidden" name="accion" value="historial">

        <label>Mes:</label>
        <select name="mes">
            <?php
                $nombresMeses = [
                    1=>'Enero', 2=>'Febrero', 3=>'Marzo', 4=>'Abril', 5=>'Mayo', 6=>'Junio',
                    7=>'Julio', 8=>'Agosto', 9=>'Septiembre', 10=>'Octubre', 11=>'Noviembre', 12=>'Diciembre'
                ];
                $mesSeleccionado = isset($_GET['mes']) ? (int)$_GET['mes'] : (int)date('m');
                foreach ($nombresMeses as $num => $nombre):
            ?>
                <option value="<?= $num ?>" <?= ($num === $mesSeleccionado) ? 'selected' : '' ?>>
                    <?= $nombre ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label>Año:</label>
        <select name="anio">
            <?php
                $anioActual = (int)date('Y');
                $anioSeleccionado = isset($_GET['anio']) ? (int)$_GET['anio'] : $anioActual;
                for ($a = $anioActual; $a >= $anioActual - 3; $a--):
            ?>
                <option value="<?= $a ?>" <?= ($a === $anioSeleccionado) ? 'selected' : '' ?>>
                    <?= $a ?>
                </option>
            <?php endfor; ?>
        </select>

        <label>DNI / Código:</label>
        <input type="text" name="busqueda" placeholder="Buscar por DNI o CASO-XXXX"
               value="<?= htmlspecialchars($_GET['busqueda'] ?? '') ?>"
               style="padding:8px 10px; border:1px solid var(--border-color); border-radius:4px; font-size:13px; min-width:180px;">

        <label>Desde:</label>
        <input type="date" name="fecha_desde" id="inputFechaDesde"
               value="<?= htmlspecialchars($_GET['fecha_desde'] ?? '') ?>"
               style="padding:8px 10px; border:1px solid var(--border-color); border-radius:4px; font-size:13px;">

        <label>Hasta:</label>
        <input type="date" name="fecha_hasta" id="inputFechaHasta"
               value="<?= htmlspecialchars($_GET['fecha_hasta'] ?? '') ?>"
               style="padding:8px 10px; border:1px solid var(--border-color); border-radius:4px; font-size:13px;">

        <button type="submit" class="btn-buscar-periodo">🔍 Buscar</button>
        <a href="index.php?accion=historial" class="btn-limpiar-periodo">✕ Limpiar</a>

        <?php if (empty($_GET['busqueda']) && empty($_GET['fecha_desde']) && empty($_GET['fecha_hasta']) && $mesSeleccionado === (int)date('m') && $anioSeleccionado === $anioActual): ?>
            <span class="periodo-actual-tag">Mostrando el mes actual</span>
        <?php endif; ?>
    </form>

    <div class="info-tip-rango">
        💡 Si usas <strong>Desde/Hasta</strong>, ese rango tiene prioridad sobre el filtro de Mes/Año.
    </div>

    <p style="font-size: 12px; color: var(--text-muted); margin-bottom: 15px;">
    Mostrando <strong id="kpi-total"><?= count($tickets) ?></strong> caso(s).
    </p>

    <table class="tabla-datos">
        <thead>
            <tr>
                <th>CÓDIGO</th>
                <th>SOLICITANTE</th>
                <th>DEPENDENCIA</th>
                <th>ANALISTA</th>
                <th>FECHA CREACIÓN</th>
                <th>FECHA CIERRE</th>
                <th>ÚLTIMA MODIF.</th>
                <th>RECIBIDO DE</th>
                <th>DERIVADO A</th>
                <th>TIEMPO ATENCIÓN</th>
                <th>ESTADO FINAL</th>
            </tr>
        </thead>
        <tbody id="cuerpo-tabla">
            <?php if (empty($tickets)): ?>
                <tr>
                    <td colspan="11" style="text-align: center; padding: 20px;">
                        No tienes casos registrados en este período.
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($tickets as $t): ?>
                    <tr class="fila-caso" data-estado="<?= htmlspecialchars($t['ESTADO_ACTUAL'] ?? '') ?>" data-minutos="<?= htmlspecialchars($t['MINUTOS_RESOLUCION'] ?? '0') ?>" data-dependencia="<?= htmlspecialchars($t['DEPENDENCIA'] ?? '') ?>">
                        
                        <td style="font-weight: 600;">
                            <a href="javascript:void(0)" onclick="abrirModalDetalles(this)" style="color: var(--primary); text-decoration: underline;">
                                <?= htmlspecialchars($t['CODIGO_TICKET'] ?? '') ?>
                            </a>
                            
                            <input type="hidden" class="d-solicitante" value="<?= htmlspecialchars($t['NOMBRE_SOLICITANTE'] ?? '') ?>">
                            <input type="hidden" class="d-dependencia" value="<?= htmlspecialchars($t['DEPENDENCIA'] ?? '') ?>">
                            <input type="hidden" class="d-analista" value="<?= htmlspecialchars($t['NOMBRE_ANALISTA'] ?? 'No asignado') ?>">
                            <input type="hidden" class="d-derivado" value="<?= htmlspecialchars($t['ANALISTA_DERIVADO'] ?? '-') ?>">
                            <input type="hidden" class="d-estado" value="<?= htmlspecialchars($t['ESTADO_ACTUAL'] ?? '') ?>">
                            <input type="hidden" class="d-desc" value="<?= htmlspecialchars($t['DESCRIPCION_REQUERIMIENTO'] ?? 'Sin descripción.') ?>">
                            <input type="hidden" class="d-obs" value="<?= htmlspecialchars($t['OBSERVACIONES_CIERRE'] ?? 'Sin observaciones registradas.') ?>">
                            <input type="hidden" class="d-motivo" value="<?= htmlspecialchars($t['DESCRIPCION_MOTIVO'] ?? '') ?>">
                            <input type="hidden" class="d-modificacion" value="<?= htmlspecialchars($t['FECHA_MODIFICACION'] ?? '') ?>">
                            <input type="hidden" class="d-codigo" value="<?= htmlspecialchars($t['CODIGO_TICKET'] ?? '') ?>">
                            <input type="hidden" class="d-archivos" value="<?= htmlspecialchars($t['ARCHIVO_EVIDENCIA'] ?? '') ?>">
                        </td>
                        
                        <td><?= htmlspecialchars($t['NOMBRE_SOLICITANTE'] ?? '') ?></td>
                        <td><?= htmlspecialchars($t['DEPENDENCIA'] ?? '') ?></td>
                        
                        <td style="font-weight: 500; color: #334155;"><?= htmlspecialchars($t['NOMBRE_ANALISTA'] ?? 'No asignado') ?></td>
                        
                        <td><?= htmlspecialchars($t['FECHA_CREACION'] ?? '') ?></td>
                        <td><?= htmlspecialchars($t['FECHA_CIERRE'] ?? '') ?></td>
                        
                        <td style="font-size: 12px; color: #64748b;">
                            <?= !empty($t['FECHA_MODIFICACION']) ? htmlspecialchars($t['FECHA_MODIFICACION']) : '-' ?>
                        </td>

                        <td style="font-size: 13px; color: #4338CA; font-weight: 500;">
                            <?= !empty($t['NOMBRE_DERIVADOR']) ? htmlspecialchars($t['NOMBRE_DERIVADOR']) : '-' ?>
                        </td>

                        <td style="font-size: 13px; color: #D97706; font-weight: 500;">
                            <?= !empty($t['ANALISTA_DERIVADO']) ? htmlspecialchars($t['ANALISTA_DERIVADO']) : '-' ?>
                        </td>

                        <td><span style="background: #ECFDF5; color: #059669; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: 600;"><?= htmlspecialchars($t['MINUTOS_RESOLUCION'] ?? '0') ?> min</span></td>
                        <td>
                            <?php 
                                $estado = $t['ESTADO_ACTUAL'] ?? '';
                                $badgeClass = match($estado) {
                                    'RESUELTO'      => 'badge-resuelto',
                                    'INCONSISTENTE' => 'badge-pendiente',
                                    'EN OBSERVACION'=> 'badge-asignado',
                                    'PENDIENTE'     => 'badge-pendiente',
                                    'ASIGNADO'      => 'badge-asignado',
                                    'DERIVADO'      => 'badge-derivado',
                                    'ANULADO'       => 'badge-anulado',
                                    'REACTIVADO'    => 'badge-reactivado',
                                    'DERIVADO EXTERNO' => 'badge-pausado',
                                    'RETORNO EXTERNO'  => 'badge-retorno',
                                    default         => 'badge-pendiente'
                                };
                            ?>
                            <span class="badge <?= $badgeClass ?>"><?= htmlspecialchars($estado) ?></span>
                            <?php if ($estado === 'DERIVADO' && !empty($t['ANALISTA_DERIVADO'])): ?>
                                <div style="font-size:11px; color:#D97706; margin-top:3px; font-weight:600;">
                                    → <?= htmlspecialchars($t['ANALISTA_DERIVADO']) ?>
                                </div>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- ============================================== -->
<!-- MODAL DE DETALLES DEL CASO                     -->
<!-- ============================================== -->
<div id="modalDetalleCaso" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); justify-content: center; align-items: center; z-index: 2000;">
    <div style="background: white; padding: 25px; border-radius: 8px; width: 650px; max-width: 90%; box-shadow: 0 10px 25px rgba(0,0,0,0.2);">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #f1f5f9; padding-bottom: 10px; margin-bottom: 20px;">
            <div>
                <h3 style="color: var(--secondary); margin: 0;" id="m-titulo">Detalle del Caso</h3>
                <small id="m-fecha-modif" style="color: #F59E0B; font-weight: 600; display: none; margin-top: 4px;">Editado el: <span></span></small>
            </div>
            <button onclick="cerrarModalDetalle()" style="background: none; border: none; font-size: 24px; cursor: pointer; color: #94a3b8;">&times;</button>
        </div>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px; margin-bottom: 20px; font-size: 13px;">
            <div><strong style="color: var(--text-muted);">Solicitante:</strong> <br><span id="m-solicitante" style="font-weight: 600; font-size: 14px;"></span></div>
            <div><strong style="color: var(--text-muted);">Dependencia:</strong> <br><span id="m-dependencia" style="font-weight: 600; font-size: 14px;"></span></div>
            <div><strong style="color: var(--text-muted);">Analista a Cargo:</strong> <br><span id="m-analista" style="font-weight: 600; font-size: 14px; color: #10B981;"></span></div>
        </div>

        <div style="margin-bottom: 15px; font-size: 13px;">
            <strong style="color: var(--text-muted);">Derivado a:</strong> <span id="m-derivado" style="font-weight: 600; font-size: 14px; color: #D97706;">-</span>
        </div>
        
        <div style="margin-bottom: 15px;">
            <strong style="color: var(--secondary); font-size: 14px;">Descripción del Usuario:</strong>
            <div id="m-desc" style="background: #f8fafc; padding: 12px; border-radius: 6px; margin-top: 5px; font-size: 14px; color: #334155; max-height: 120px; overflow-y: auto; white-space: pre-wrap;"></div>
        </div>

        <div id="m-bloque-motivo" style="display: none; margin-bottom: 15px; padding: 12px; background: #FEF2F2; border-left: 4px solid var(--danger); border-radius: 4px;">
            <strong style="color: var(--danger); font-size: 14px;">Motivo de Inconsistencia (Rechazo):</strong>
            <div id="m-motivo" style="color: #991B1B; font-weight: 600; margin-top: 5px;"></div>
        </div>

        <div style="margin-bottom: 20px;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <strong style="color: var(--secondary); font-size: 14px;">Notas de Cierre:</strong>
                <span id="m-estado" style="font-weight: 600; font-size: 12px; padding: 2px 8px; border-radius: 10px; background: #E2E8F0;"></span>
            </div>
            <div id="m-obs" style="background: #f8fafc; padding: 12px; border-radius: 6px; margin-top: 5px; font-size: 14px; color: #334155; min-height: 60px; white-space: pre-wrap;"></div>
        </div>

        <!-- ARCHIVOS ADJUNTOS AGRUPADOS -->
        <div id="m-bloque-archivos" style="display: none; margin-bottom: 20px; border-top: 1px solid #f1f5f9; padding-top: 15px;">
            <strong style="color: var(--secondary); font-size: 14px; display:block; margin-bottom:10px;">📎 Archivos Adjuntos:</strong>
            <div id="m-grupo-solicitante" class="archivos-grupo" style="display:none;">
                <strong>Adjuntado por el Solicitante</strong>
                <div class="archivos-links"></div>
            </div>
            <div id="m-grupo-derivacion" class="archivos-grupo" style="display:none;">
                <strong>Adjuntado en una Derivación</strong>
                <div class="archivos-links"></div>
            </div>
            <div id="m-grupo-cierre" class="archivos-grupo" style="display:none;">
                <strong>Adjuntado al Cerrar el Caso</strong>
                <div class="archivos-links"></div>
            </div>
            <div id="m-grupo-validacion" class="archivos-grupo" style="display:none;">
                <strong>Adjuntado al Solicitar Validación</strong>
                <div class="archivos-links"></div>
            </div>
            <div id="m-grupo-externo" class="archivos-grupo" style="display:none;">
                <strong>Adjuntado en Derivación Externa</strong>
                <div class="archivos-links"></div>
            </div>
        </div>

        <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 10px;">
            <button onclick="editarCasoActual()" class="btn" style="background: var(--primary); color: white; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer; font-weight: 600;">
                Editar Información
            </button>
            <button onclick="cerrarModalDetalle()" class="btn" style="background: #DC2626; color: white; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer; font-weight: 600;">
                Cerrar Detalle
            </button>
        </div>
    </div>
</div>

<script>
    function abrirModalDetalles(elementoEnlace) {
        const td = elementoEnlace.parentElement;
        
        const codigo = elementoEnlace.innerText.trim();
        const solicitante = td.querySelector('.d-solicitante').value;
        const dependencia = td.querySelector('.d-dependencia').value;
        const analista = td.querySelector('.d-analista').value;
        const derivado = td.querySelector('.d-derivado').value;
        const estado = td.querySelector('.d-estado').value;
        const desc = td.querySelector('.d-desc').value;
        const obs = td.querySelector('.d-obs').value;
        const motivo = td.querySelector('.d-motivo').value;
        const modificacion = td.querySelector('.d-modificacion').value;
        const codigoTicket = td.querySelector('.d-codigo').value;
        const archivos = td.querySelector('.d-archivos').value;

        document.getElementById('m-titulo').innerText = "Expediente: " + codigo;
        document.getElementById('m-solicitante').innerText = solicitante;
        document.getElementById('m-dependencia').innerText = dependencia;
        document.getElementById('m-analista').innerText = analista;
        document.getElementById('m-derivado').innerText = derivado;
        document.getElementById('m-estado').innerText = "ESTADO: " + estado;
        document.getElementById('m-desc').innerText = desc;
        document.getElementById('m-obs').innerText = obs;

        const labelModif = document.getElementById('m-fecha-modif');
        if (modificacion && modificacion !== '') {
            labelModif.style.display = 'block';
            labelModif.querySelector('span').innerText = modificacion;
        } else {
            labelModif.style.display = 'none';
        }

        const bloqueMotivo = document.getElementById('m-bloque-motivo');
        if(estado === 'INCONSISTENTE' && motivo !== '') {
            bloqueMotivo.style.display = 'block';
            document.getElementById('m-motivo').innerText = motivo;
        } else {
            bloqueMotivo.style.display = 'none';
        }

        // ── ARCHIVOS ADJUNTOS AGRUPADOS POR PREFIJO ──
        const grupos = {
            'EV_':  { id: 'm-grupo-solicitante', archivos: [] },
            'DER_': { id: 'm-grupo-derivacion',  archivos: [] },
            'RES_': { id: 'm-grupo-cierre',      archivos: [] },
            'VAL_': { id: 'm-grupo-validacion',  archivos: [] },
            'EXT_': { id: 'm-grupo-externo',     archivos: [] } // cubre EXT_ y EXT_RET_
        };

        // Ocultamos todos los grupos y limpiamos antes de repoblar
        Object.values(grupos).forEach(g => {
            const cont = document.getElementById(g.id);
            cont.style.display = 'none';
            cont.querySelector('.archivos-links').innerHTML = '';
        });

        const bloqueArchivos = document.getElementById('m-bloque-archivos');
        if (archivos && archivos.trim() !== '' && codigoTicket) {
            const listaArchivos = archivos.split(',').map(a => a.trim()).filter(a => a !== '');

            listaArchivos.forEach(nombreArchivo => {
                let prefijoEncontrado = null;
                for (const prefijo of Object.keys(grupos)) {
                    if (nombreArchivo.startsWith(prefijo)) { prefijoEncontrado = prefijo; break; }
                }
                if (!prefijoEncontrado) prefijoEncontrado = 'EV_'; // fallback por si acaso

                grupos[prefijoEncontrado].archivos.push(nombreArchivo);
            });

            let huboArchivos = false;
            Object.values(grupos).forEach(g => {
                if (g.archivos.length === 0) return;
                huboArchivos = true;
                const cont = document.getElementById(g.id);
                cont.style.display = 'block';
                const linksDiv = cont.querySelector('.archivos-links');
                g.archivos.forEach((arch, idx) => {
                    const esPdf = arch.toLowerCase().endsWith('.pdf');
                    const esDoc = arch.toLowerCase().endsWith('.doc') || arch.toLowerCase().endsWith('.docx');
                    const icono = esPdf ? '📄' : (esDoc ? '📝' : '🖼️');
                    const a = document.createElement('a');
                    a.href = '/Omnical/public/uploads/' + codigoTicket + '/' + arch;
                    a.target = '_blank';
                    a.className = 'archivo-link';
                    a.innerText = icono + ' Archivo ' + (idx + 1);
                    linksDiv.appendChild(a);
                });
            });

            bloqueArchivos.style.display = huboArchivos ? 'block' : 'none';
        } else {
            bloqueArchivos.style.display = 'none';
        }

        document.getElementById('modalDetalleCaso').style.display = 'flex';
    }

    function cerrarModalDetalle() {
        document.getElementById('modalDetalleCaso').style.display = 'none';
    }

    function editarCasoActual() {
        const titulo = document.getElementById('m-titulo').innerText;
        const codigo = titulo.replace("Expediente: ", "").trim();
        window.location.href = "index.php?accion=editar_historico&codigo=" + codigo;
    }

   function actualizarKPIs() {
    const filas = document.querySelectorAll('.fila-caso');
    let total = 0;
    filas.forEach(fila => {
        if (fila.style.display !== 'none') {
            total++;
        }
    });
    document.getElementById('kpi-total').innerText = total;
}

    let filtroEstadoHistActual = 'TODOS';
    let filtroDependenciaHistActual = 'todas';

    function filtrarTabla(estadoRequerido, botonClickeado) {
        filtroEstadoHistActual = estadoRequerido;
        document.querySelectorAll('.btn-filter').forEach(btn => {
            btn.style.background = 'white'; btn.style.color = 'var(--text-main)';
        });
        botonClickeado.style.background = 'var(--primary)'; botonClickeado.style.color = 'white';
        aplicarFiltrosCombinadosHist();
    }

    function filtrarDependenciaHist(dependencia) {
        filtroDependenciaHistActual = dependencia;
        aplicarFiltrosCombinadosHist();
    }

    function aplicarFiltrosCombinadosHist() {
        const filas = document.querySelectorAll('.fila-caso');
        filas.forEach(fila => {
            const estadoFila = fila.getAttribute('data-estado');
            const dependenciaFila = fila.getAttribute('data-dependencia');
            const coincideEstado = (filtroEstadoHistActual === 'TODOS' || estadoFila === filtroEstadoHistActual);
            const coincideDependencia = (filtroDependenciaHistActual === 'todas' || dependenciaFila === filtroDependenciaHistActual);
            fila.style.display = (coincideEstado && coincideDependencia) ? '' : 'none';
        });
        actualizarKPIs();
    }

    document.addEventListener('DOMContentLoaded', actualizarKPIs);
</script>

</body>
</html>