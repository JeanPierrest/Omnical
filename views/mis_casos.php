<?php include 'header.php'; ?>

<style>
    .badge-derivado   { background: #FFF3E0; color: #EA580C; border: 1px solid #FED7AA; }
    .badge-anulado    { background: #F1F5F9; color: #64748B; border: 1px solid #CBD5E1; text-decoration: line-through; }
    .badge-reactivado { background: #EDE9FE; color: #6D28D9; border: 1px solid #DDD6FE; }
    .badge-validacion { background: #E0F2FE; color: #0369A1; border: 1px solid #BAE6FD; }
    .badge-rechazado  { background: #FEE2E2; color: #B91C1C; border: 1px solid #FECACA; font-weight: 800; }
    .btn-en-espera {
        background: #E2E8F0; color: #64748B; border: none; font-size: 12px;
        padding: 6px 14px; border-radius: 4px; font-weight: 600; cursor: not-allowed;
    }

    .filtro-bar {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 15px;
        flex-wrap: wrap;
    }
    .filtro-bar span { font-size: 13px; color: var(--text-muted); font-weight: 600; }
    .btn-filtro {
        padding: 7px 16px;
        border-radius: 20px;
        border: 2px solid var(--border-color);
        background: white;
        font-size: 12px;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.2s;
        color: var(--text-muted);
    }
    .btn-filtro.activo { border-color: var(--primary); background: var(--primary); color: white; }
    .btn-filtro:hover:not(.activo) { border-color: var(--primary); color: var(--primary); }
    .contador-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: #EA580C;
        color: white;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 800;
        padding: 2px 8px;
        margin-left: 5px;
    }
    .contador-badge.gris { background: #64748B; }
    .contador-badge.morado { background: #7C3AED; }

    .acciones-container { display: flex; gap: 6px; flex-wrap: wrap; }
    .btn-anular {
        background: #475569; color: white; border: none; font-size: 12px;
        padding: 6px 14px; border-radius: 4px; cursor: pointer; font-weight: 600;
    }
    .btn-anular:hover { opacity: 0.85; }
    .btn-reactivar {
        background: #7C3AED; color: white; border: none; font-size: 12px;
        padding: 6px 14px; border-radius: 4px; cursor: pointer; font-weight: 600;
    }
    .btn-reactivar:hover { opacity: 0.85; }

    .alerta-exito {
        background: #D1FAE5; border-left: 4px solid #10B981; color: #065F46;
        padding: 12px 16px; border-radius: 4px; margin-bottom: 20px; font-weight: 600; font-size: 14px;
    }
    .alerta-error {
        background: #FEE2E2; border-left: 4px solid #EF4444; color: #B91C1C;
        padding: 12px 16px; border-radius: 4px; margin-bottom: 20px; font-weight: 600; font-size: 14px;
    }

    /* Tabla de solo lectura: derivados por mí */
    .badge-solo-lectura {
        background: #EEF2FF; color: #4338CA; border: 1px solid #C7D2FE;
        padding: 5px 10px; border-radius: 20px; font-size: 11px; font-weight: 700;
    }
</style>

<div class="container">

    <?php if (isset($_GET['exito'])): ?>
        <div class="alerta-exito">
            ✅ <?php
                $ex = $_GET['exito'];
                if ($ex === 'anulado')    echo 'El caso fue anulado correctamente.';
                elseif ($ex === 'reactivado') echo 'El caso fue reactivado y vuelve a tu bandeja.';
                else echo htmlspecialchars($ex);
            ?>
        </div>
    <?php endif; ?>
    <?php if (isset($_GET['error'])): ?>
        <div class="alerta-error">
            ⚠️ <?php
                $er = $_GET['error'];
                if ($er === 'motivo_requerido')   echo 'Debes indicar un motivo para anular el caso.';
                elseif ($er === 'fallo_anular')    echo 'No se pudo anular el caso. Intenta de nuevo.';
                elseif ($er === 'fallo_reactivar') echo 'No se pudo reactivar el caso. Intenta de nuevo.';
                else echo htmlspecialchars($er);
            ?>
        </div>
    <?php endif; ?>

    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
        <h2 style="color: var(--secondary); border-left: 4px solid var(--primary); padding-left: 10px; margin: 0;">
            Mis Casos Asignados
        </h2>
        <span style="font-size: 13px; color: var(--text-muted);">
            Total: <strong id="contadorVisible">0</strong> casos
        </span>
    </div>

    <!-- BARRA DE FILTROS -->
    <div class="filtro-bar">
        <span>Mostrar:</span>
        <button class="btn-filtro activo" onclick="filtrar('todos', this)">📋 Todos</button>
        <button class="btn-filtro" onclick="filtrar('ASIGNADO', this)">🔵 Asignados</button>

        <button class="btn-filtro" onclick="filtrar('DERIVADO', this)">
            🟠 Derivados a mí
            <?php $totalDerivados = count(array_filter($tickets, fn($t) => $t['ESTADO_ACTUAL'] === 'DERIVADO'));
                  if ($totalDerivados > 0): ?>
                <span class="contador-badge"><?= $totalDerivados ?></span>
            <?php endif; ?>
        </button>

        <button class="btn-filtro" onclick="mostrarDerivadosPorMi()" id="btnDerivadosPorMi">
            🔁 Derivados por mí
            <?php if (!empty($derivadosPorMi)): ?>
                <span class="contador-badge morado"><?= count($derivadosPorMi) ?></span>
            <?php endif; ?>
        </button>

        <button class="btn-filtro" onclick="filtrar('ANULADO', this)">
            ⚪ Anulados
            <?php $totalAnulados = count(array_filter($tickets, fn($t) => $t['ESTADO_ACTUAL'] === 'ANULADO'));
                  if ($totalAnulados > 0): ?>
                <span class="contador-badge gris"><?= $totalAnulados ?></span>
            <?php endif; ?>
        </button>
    </div>

    <!-- TABLA PRINCIPAL: MIS CASOS -->
    <table class="tabla-datos" id="tablaCasos">
        <thead>
            <tr>
                <th>Código</th>
                <th>Fecha de Asignación</th>
                <th>Dependencia</th>
                <th>Solicitante</th>
                <th>Descripción Breve</th>
                <th>Estado</th>
                <th>Acción</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($tickets)): ?>
                <tr>
                    <td colspan="7" style="text-align:center; padding:30px; color:var(--text-muted);">
                        No tienes casos asignados actualmente.
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($tickets as $t): ?>
                    <?php
                        $estado = $t['ESTADO_ACTUAL'];
                        $enValidacion = ($t['ESTADO_VALIDACION'] ?? '') === 'EN_VALIDACION';
                        $fueRechazado = !$enValidacion && ($t['ULTIMA_SOLICITUD_ESTADO'] ?? '') === 'RECHAZADO';
                        $badgeClass = $enValidacion 
                            ? 'badge-validacion' 
                            : ($fueRechazado ? 'badge-rechazado' : match($estado) {
                                'ASIGNADO'   => 'badge-asignado',
                                'DERIVADO'   => 'badge-derivado',
                                'ANULADO'    => 'badge-anulado',
                                'REACTIVADO' => 'badge-reactivado',
                                default      => 'badge-pendiente'
                            });
                        $etiquetaEstado = $enValidacion ? 'En Validación' : ($fueRechazado ? '⚠ Rechazado' : $estado);
                        $desc = $t['DESCRIPCION_REQUERIMIENTO'] ?? '';
                        if (is_object($desc)) $desc = $desc->load();
                        $descBreve = mb_strlen($desc) > 45 ? mb_substr($desc, 0, 45) . '...' : $desc;
                    ?>
                    <tr data-estado="<?= $estado ?>">
                        <td style="font-weight:600; color:var(--primary);">
                            <?= htmlspecialchars($t['CODIGO_TICKET']) ?>
                        </td>
                        <td><?= htmlspecialchars($t['FECHA_ASIGNACION']) ?></td>
                        <td><?= htmlspecialchars($t['DEPENDENCIA'] ?? '') ?></td>
                        <td><?= htmlspecialchars($t['NOMBRE_SOLICITANTE']) ?></td>
                        <td style="max-width:250px;">
                            <?= htmlspecialchars($descBreve) ?>
                            <input type="hidden" class="d-id-ticket"     value="<?= $t['ID_REQUERIMIENTO'] ?>">
                            <input type="hidden" class="d-codigo"       value="<?= htmlspecialchars($t['CODIGO_TICKET']) ?>">
                            <input type="hidden" class="d-desc-completa" value="<?= htmlspecialchars($desc) ?>">
                            <input type="hidden" class="d-solicitante"  value="<?= htmlspecialchars($t['NOMBRE_SOLICITANTE']) ?>">
                            <input type="hidden" class="d-dependencia"  value="<?= htmlspecialchars($t['DEPENDENCIA'] ?? '') ?>">
                            <input type="hidden" class="d-archivo"      value="<?= htmlspecialchars($t['ARCHIVO_EVIDENCIA'] ?? '') ?>">
                            <input type="hidden" class="d-documento"    value="<?= htmlspecialchars(trim(($t['TIPO_DOCUMENTO'] ?? '') . ' ' . ($t['NUMERO_DOCUMENTO'] ?? ''))) ?>">
                            <input type="hidden" class="d-rechazo"      value="<?= htmlspecialchars($fueRechazado ? ($t['COMENTARIO_RECHAZO'] ?? '') : '') ?>">
                        </td>
                        <td>
                            <span class="badge <?= $badgeClass ?>"><?= htmlspecialchars($etiquetaEstado) ?></span>
                        </td>
                        <td>
                            <div class="acciones-container">
                                <?php if ($enValidacion): ?>
                                    <button class="btn-en-espera" disabled title="Esperando respuesta de quien te lo derivó">
                                        ⏳ En espera
                                    </button>
                                <?php elseif ($estado === 'ANULADO'): ?>
                                    <button onclick="abrirModalReactivar(this)" class="btn-reactivar">
                                        ♻️ Reactivar
                                    </button>
                                <?php else: ?>
                                    <button onclick="abrirModalVistaPrevia(this)" class="btn btn-success" style="font-size:12px; padding:6px 14px;">
                                        Resolver
                                    </button>
                                    <button onclick="abrirModalAnular(this)" class="btn-anular">
                                        🚫 Anular
                                    </button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- TABLA SECUNDARIA: DERIVADOS POR MÍ (oculta por defecto, solo lectura) -->
    <table class="tabla-datos" id="tablaDerivadosPorMi" style="display:none;">
        <thead>
            <tr>
                <th>Código</th>
                <th>Fecha de Derivación</th>
                <th>Dependencia</th>
                <th>Solicitante</th>
                <th>Derivado a</th>
                <th>Estado Actual del Caso</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($derivadosPorMi)): ?>
                <tr>
                    <td colspan="6" style="text-align:center; padding:30px; color:var(--text-muted);">
                        No has derivado ningún caso todavía.
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($derivadosPorMi as $d): ?>
                    <tr>
                        <td style="font-weight:600; color:var(--primary);">
                            <?= htmlspecialchars($d['CODIGO_TICKET']) ?>
                        </td>
                        <td><?= htmlspecialchars($d['FECHA_DERIVACION']) ?></td>
                        <td><?= htmlspecialchars($d['DEPENDENCIA'] ?? '') ?></td>
                        <td><?= htmlspecialchars($d['NOMBRE_SOLICITANTE']) ?></td>
                        <td><?= htmlspecialchars($d['NOMBRE_ANALISTA_DESTINO'] ?? '—') ?></td>
                        <td>
                            <span class="badge-solo-lectura"><?= htmlspecialchars($d['ESTADO_TICKET_ACTUAL']) ?></span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

</div>

<!-- MODAL VISTA PREVIA (Resolver) -->
<div id="modalVistaPrevia" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.6); justify-content:center; align-items:center; z-index:2000;">
    <div style="background:white; padding:25px; border-radius:8px; width:600px; max-width:90%; box-shadow:0 10px 25px rgba(0,0,0,0.2);">
        <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:2px solid #f1f5f9; padding-bottom:10px; margin-bottom:15px;">
            <h3 style="color:var(--secondary); margin:0;">Vista Previa: <span id="vp-codigo" style="color:var(--primary);"></span></h3>
            <button onclick="cerrarModalVP()" style="background:none; border:none; font-size:24px; cursor:pointer; color:#94a3b8;">&times;</button>
        </div>
        <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:15px; margin-bottom:15px;">
            <div><strong style="color:var(--text-muted); font-size:12px;">SOLICITANTE</strong>
                <div id="vp-solicitante" style="font-weight:600; color:#334155; margin-top:3px;"></div></div>
            <div><strong style="color:var(--text-muted); font-size:12px;">DEPENDENCIA</strong>
                <div id="vp-dependencia" style="font-weight:600; color:#334155; margin-top:3px;"></div></div>
            <div><strong style="color:var(--text-muted); font-size:12px;">DOCUMENTO</strong>
                <div id="vp-documento" style="font-weight:600; color:#334155; margin-top:3px;"></div></div>
        </div>
        <div style="margin-bottom:15px;">
            <strong style="color:var(--secondary); font-size:13px;">Descripción del Requerimiento:</strong>
            <div id="vp-descripcion" style="background:#f8fafc; border-left:4px solid var(--primary); padding:12px; border-radius:4px; margin-top:6px; font-size:13px; color:#334155; max-height:180px; overflow-y:auto; white-space:pre-wrap; line-height:1.5;"></div>
        </div>
        <div id="vp-bloque-rechazo" style="display:none; margin-bottom:15px; background:#FEF2F2; border-left:4px solid #DC2626; border-radius:4px; padding:12px 14px;">
            <strong style="color:#991B1B; font-size:13px;">❌ Solicitud rechazada — motivo:</strong>
            <div id="vp-rechazo" style="color:#7F1D1D; font-size:13px; margin-top:5px; font-style:italic;"></div>
        </div>

        <div id="vp-bloque-archivo" style="display:none; margin-bottom:15px;">
            <strong style="color:var(--secondary); font-size:13px;">Evidencia Adjunta:</strong>
            <div id="vp-enlaces-contenedor" style="display:flex; flex-wrap:wrap; gap:8px; margin-top:8px;"></div>
        </div>
        <div id="vp-bloque-historial-toggle" style="display:none; margin-bottom:15px;">
            <button type="button" onclick="toggleHistorialVP()" id="btnToggleHistorialVP"
                    style="background:#F1F5F9; border:1px solid var(--border-color); color:var(--secondary); font-size:12px; font-weight:700; padding:8px 12px; border-radius:6px; cursor:pointer; width:100%; text-align:left;">
                🗂️ Ver historial de validaciones ▼
            </button>
            <div id="vp-historial-contenedor" style="display:none; margin-top:8px;"></div>
        </div>

        <div style="display:flex; justify-content:flex-end; gap:10px; border-top:1px solid #f1f5f9; padding-top:15px;">
            <button onclick="cerrarModalVP()" class="btn" style="background:#DC2626; color:white;">Cancelar</button>
            <button onclick="irAResolucion()" class="btn btn-success">Ir a Formulario de Resolución →</button>
        </div>
    </div>
</div>

<!-- MODAL ANULAR -->
<div id="modalAnular" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.6); justify-content:center; align-items:center; z-index:2000;">
    <div style="background:white; padding:25px; border-radius:8px; width:450px; max-width:90%; box-shadow:0 10px 25px rgba(0,0,0,0.2);">
        <h3 style="color:#475569; margin-top:0;">🚫 Anular Caso <span id="an-codigo" style="color:var(--primary);"></span></h3>
        <p style="font-size:13px; color:var(--text-muted); margin-bottom:15px;">
            Esta acción marcará el caso como anulado. Quedará registrado quién lo anuló y por qué, y podrá reactivarse después si fue un error.
        </p>
        <form id="formAnular" action="index.php?accion=anular_ticket" method="POST">
            <input type="hidden" name="id_requerimiento" id="an-id-ticket">
            <div class="form-group">
                <label style="display:block; font-weight:600; font-size:13px; margin-bottom:6px; color:var(--secondary);">
                    Motivo de la anulación (obligatorio):
                </label>
                <textarea name="motivo_anulacion" id="an-motivo" rows="3" required
                          style="width:100%; padding:10px; border:1px solid var(--border-color); border-radius:4px; box-sizing:border-box; font-family:inherit; font-size:13px;"
                          placeholder="Ej: Caso duplicado, creado por error..."></textarea>
            </div>
            <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:15px;">
                <button type="button" onclick="cerrarModalAnular()" class="btn" style="background:#94A3B8; color:white;">Cancelar</button>
                <button type="button" onclick="confirmarAnular()" class="btn" style="background:#475569; color:white;">Anular Caso</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL REACTIVAR -->
<div id="modalReactivar" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.6); justify-content:center; align-items:center; z-index:2000;">
    <div style="background:white; padding:25px; border-radius:8px; width:420px; max-width:90%; box-shadow:0 10px 25px rgba(0,0,0,0.2); text-align:center;">
        <h3 style="color:#7C3AED; margin-top:0;">♻️ Reactivar Caso <span id="re-codigo" style="color:var(--primary);"></span></h3>
        <p style="font-size:13px; color:var(--text-muted); margin:15px 0;">
            El caso volverá a "Mis Casos" con estado <strong>REACTIVADO</strong> y quedará registrado quién lo reactivó.
        </p>
        <form id="formReactivar" action="index.php?accion=reactivar_ticket" method="POST">
            <input type="hidden" name="id_requerimiento" id="re-id-ticket">
            <div style="display:flex; justify-content:center; gap:10px; margin-top:10px;">
                <button type="button" onclick="cerrarModalReactivar()" class="btn" style="background:#94A3B8; color:white;">Cancelar</button>
                <button type="button" onclick="confirmarReactivar()" class="btn" style="background:#7C3AED; color:white;">Sí, Reactivar</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL CONFIRMACIÓN FINAL (anti-accidente) -->
<div id="modalConfirmFinal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); justify-content:center; align-items:center; z-index:3000;">
    <div style="background:white; padding:25px; border-radius:8px; width:380px; max-width:90%; text-align:center; box-shadow:0 4px 12px rgba(0,0,0,0.2);">
        <h3 style="color:var(--secondary); margin-top:0;">⚠️ Última confirmación</h3>
        <p id="textoConfirmFinal" style="font-size:14px; color:var(--text-main); margin:15px 0;"></p>
        <div style="display:flex; justify-content:center; gap:15px;">
            <button onclick="cerrarConfirmFinal()" class="btn" style="background:#94A3B8; color:white;">Cancelar</button>
            <button onclick="ejecutarEnvioFinal()" id="btnConfirmFinal" class="btn btn-primary">Sí, continuar</button>
        </div>
    </div>
</div>

<script>
    let idTicketActivo   = null;
    let codigoTicketActivo = null;
    let formPendienteEnvio = null;

    // ── FILTRO (tabla principal) ──
    function filtrar(estado, boton) {
        // Aseguramos que la tabla de derivados por mí esté oculta y la principal visible
        document.getElementById('tablaCasos').style.display = 'table';
        document.getElementById('tablaDerivadosPorMi').style.display = 'none';

        document.querySelectorAll('.btn-filtro').forEach(b => b.classList.remove('activo'));
        boton.classList.add('activo');

        let visibles = 0;
        document.querySelectorAll('#tablaCasos tbody tr[data-estado]').forEach(tr => {
            const mostrar = estado === 'todos' || tr.dataset.estado === estado;
            tr.style.display = mostrar ? '' : 'none';
            if (mostrar) visibles++;
        });
        document.getElementById('contadorVisible').innerText = visibles;
    }

    // ── TOGGLE: DERIVADOS POR MÍ (tabla aparte, solo lectura) ──
    function mostrarDerivadosPorMi() {
        document.querySelectorAll('.btn-filtro').forEach(b => b.classList.remove('activo'));
        document.getElementById('btnDerivadosPorMi').classList.add('activo');

        document.getElementById('tablaCasos').style.display = 'none';
        document.getElementById('tablaDerivadosPorMi').style.display = 'table';

        const totalFilas = document.querySelectorAll('#tablaDerivadosPorMi tbody tr').length;
        document.getElementById('contadorVisible').innerText = totalFilas;
    }

    document.addEventListener('DOMContentLoaded', () => {
        document.getElementById('contadorVisible').innerText =
            document.querySelectorAll('#tablaCasos tbody tr[data-estado]').length;
    });

    // ── MODAL VISTA PREVIA / RESOLVER ──
    function abrirModalVistaPrevia(boton) {
        const tr = boton.closest('tr');
        idTicketActivo = tr.querySelector('.d-id-ticket').value;
        codigoTicketActivo = tr.querySelector('.d-codigo').value;
        document.getElementById('vp-codigo').innerText      = tr.querySelector('.d-codigo').value;
        document.getElementById('vp-solicitante').innerText = tr.querySelector('.d-solicitante').value;
        document.getElementById('vp-dependencia').innerText = tr.querySelector('.d-dependencia').value;
        document.getElementById('vp-documento').innerText   = tr.querySelector('.d-documento').value || '—';
        document.getElementById('vp-descripcion').innerText = tr.querySelector('.d-desc-completa').value;

        const rechazo = tr.querySelector('.d-rechazo').value;
        const bloqueRechazo = document.getElementById('vp-bloque-rechazo');
        const bloqueToggle = document.getElementById('vp-bloque-historial-toggle');
        if (rechazo && rechazo.trim() !== '') {
            bloqueRechazo.style.display = 'block';
            document.getElementById('vp-rechazo').innerText = rechazo;
            bloqueToggle.style.display = 'block';
        } else {
            bloqueRechazo.style.display = 'none';
            bloqueToggle.style.display = 'none';
        }
        document.getElementById('vp-historial-contenedor').innerHTML = '';
        document.getElementById('vp-historial-contenedor').style.display = 'none';
        document.getElementById('vp-historial-contenedor').dataset.cargado = '';
        document.getElementById('btnToggleHistorialVP').innerHTML = '🗂️ Ver historial de validaciones ▼';

        const archivo = tr.querySelector('.d-archivo').value;
        const bloqueArchivo = document.getElementById('vp-bloque-archivo');
        const contenedor    = document.getElementById('vp-enlaces-contenedor');
        contenedor.innerHTML = '';
        if (archivo && archivo.trim() !== '') {
            bloqueArchivo.style.display = 'block';
            archivo.split(',').forEach((arch, i) => {
                const a = document.createElement('a');
                const codigoTicket = tr.querySelector('.d-codigo').value;
                a.href = '/Omnical/public/uploads/' + codigoTicket + '/' + arch.trim();
                a.target = '_blank';
                a.className = 'btn';
                a.style = 'background:#3B82F6; color:white; text-decoration:none; padding:6px 14px; font-size:12px;';
                a.innerText = (arch.toLowerCase().endsWith('.pdf') ? '📄' : '🖼️') + ' Archivo ' + (i + 1);
                contenedor.appendChild(a);
            });
        } else {
            bloqueArchivo.style.display = 'none';
        }
        document.getElementById('modalVistaPrevia').style.display = 'flex';
    }
    function cerrarModalVP() {
        document.getElementById('modalVistaPrevia').style.display = 'none';
        idTicketActivo = null;
    }
    function irAResolucion() {
        if (idTicketActivo) window.location.href = 'index.php?accion=resolver&id=' + idTicketActivo;
    }

    // ── HISTORIAL DE VALIDACIONES EN EL MODAL ──
    function toggleHistorialVP() {
        const contenedor = document.getElementById('vp-historial-contenedor');
        const btn = document.getElementById('btnToggleHistorialVP');

        if (contenedor.style.display !== 'none') {
            contenedor.style.display = 'none';
            btn.innerHTML = '🗂️ Ver historial de validaciones ▼';
            return;
        }

        if (contenedor.dataset.cargado === '1') {
            contenedor.style.display = 'block';
            btn.innerHTML = '🗂️ Ocultar historial ▲';
            return;
        }

        contenedor.innerHTML = '<p style="font-size:12px; color:var(--text-muted); padding:8px;">Cargando...</p>';
        contenedor.style.display = 'block';
        btn.innerHTML = '🗂️ Ocultar historial ▲';

        fetch('index.php?accion=obtener_historial_validaciones_ajax&id_requerimiento=' + idTicketActivo)
            .then(res => res.json())
            .then(data => {
                if (!data.historial || data.historial.length === 0) {
                    contenedor.innerHTML = '<p style="font-size:12px; color:var(--text-muted); padding:8px;">Sin historial previo.</p>';
                    return;
                }

                let html = '<div style="border:1px solid var(--border-color); border-radius:6px; overflow:hidden;">';
                data.historial.forEach(h => {
                    const esResuelto = h.ESTADO_PROPUESTO === 'RESUELTO';
                    const colorProp = esResuelto ? '#059669' : '#B45309';
                    let obs = h.OBSERVACIONES || '';
                    if (obs.length > 100) obs = obs.substring(0, 100) + '...';

                    html += `
                        <div style="padding:10px 12px; border-bottom:1px solid var(--border-color); background:#F8FAFC;">
                            <div style="display:flex; justify-content:space-between; font-size:11px; font-weight:700; color:var(--secondary); margin-bottom:4px;">
                                <span>📤 ${h.NOMBRE_SOLICITA} solicitó</span>
                                <span style="color:var(--text-muted); font-weight:400;">${h.FECHA_SOLICITUD}</span>
                            </div>
                            <div style="font-size:12px; color:#334155; margin-bottom:4px;">
                                Propuso: <strong style="color:${colorProp};">${h.ESTADO_PROPUESTO}</strong> — "${obs}"
                            </div>`;

                    if (h.ARCHIVOS_ADJUNTOS) {
                        html += '<div style="display:flex; flex-wrap:wrap; gap:5px; margin-bottom:4px;">';
                        h.ARCHIVOS_ADJUNTOS.split(',').forEach((arch, idx) => {
                            const archTrim = arch.trim();
                            const esPdf = archTrim.toLowerCase().endsWith('.pdf');
                            html += `<a href="/Omnical/public/uploads/${codigoTicketActivo}/${archTrim}" target="_blank" style="background:#3B82F6; color:white; text-decoration:none; padding:3px 8px; border-radius:4px; font-size:10px; font-weight:600;">${esPdf ? '📄' : '🖼️'} Archivo ${idx + 1}</a>`;                        });
                        html += '</div>';
                    }

                    if (h.ESTADO_SOLICITUD === 'APROBADO') {
                        html += `<div style="font-size:11px; color:#059669; font-weight:600;">✅ ${h.NOMBRE_APRUEBA} aprobó (${h.FECHA_RESPUESTA})</div>`;
                    } else if (h.ESTADO_SOLICITUD === 'RECHAZADO') {
                        html += `
                            <div style="font-size:11px; color:#DC2626; font-weight:600;">❌ ${h.NOMBRE_APRUEBA} rechazó (${h.FECHA_RESPUESTA})</div>
                            <div style="font-size:11px; color:#7F1D1D; margin-top:3px; font-style:italic; background:#FEF2F2; padding:5px 8px; border-radius:4px;">"${h.COMENTARIO_RECHAZO || ''}"</div>`;
                    } else {
                        html += `<div style="font-size:11px; color:#0369A1; font-weight:600;">⏳ Pendiente</div>`;
                    }

                    html += `</div>`;
                });
                html += '</div>';

                contenedor.innerHTML = html;
                contenedor.dataset.cargado = '1';
            })
            .catch(() => {
                contenedor.innerHTML = '<p style="font-size:12px; color:#DC2626; padding:8px;">Error al cargar el historial.</p>';
            });
    }

    // ── MODAL ANULAR ──
    function abrirModalAnular(boton) {
        const tr = boton.closest('tr');
        document.getElementById('an-id-ticket').value = tr.querySelector('.d-id-ticket').value;
        document.getElementById('an-codigo').innerText = tr.querySelector('.d-codigo').value;
        document.getElementById('an-motivo').value = '';
        document.getElementById('modalAnular').style.display = 'flex';
    }
    function cerrarModalAnular() {
        document.getElementById('modalAnular').style.display = 'none';
    }
    function confirmarAnular() {
        const form = document.getElementById('formAnular');
        if (!form.checkValidity()) { form.reportValidity(); return; }

        const codigo = document.getElementById('an-codigo').innerText;
        formPendienteEnvio = form;
        document.getElementById('textoConfirmFinal').innerText =
            '¿Confirmas anular el caso ' + codigo + '? Esta acción se puede revertir luego reactivándolo.';
        document.getElementById('modalAnular').style.display = 'none';
        document.getElementById('modalConfirmFinal').style.display = 'flex';
    }

    // ── MODAL REACTIVAR ──
    function abrirModalReactivar(boton) {
        const tr = boton.closest('tr');
        document.getElementById('re-id-ticket').value = tr.querySelector('.d-id-ticket').value;
        document.getElementById('re-codigo').innerText = tr.querySelector('.d-codigo').value;
        document.getElementById('modalReactivar').style.display = 'flex';
    }
    function cerrarModalReactivar() {
        document.getElementById('modalReactivar').style.display = 'none';
    }
    function confirmarReactivar() {
        const form = document.getElementById('formReactivar');
        const codigo = document.getElementById('re-codigo').innerText;
        formPendienteEnvio = form;
        document.getElementById('textoConfirmFinal').innerText =
            '¿Confirmas reactivar el caso ' + codigo + '? Volverá a tu bandeja de casos activos.';
        document.getElementById('modalReactivar').style.display = 'none';
        document.getElementById('modalConfirmFinal').style.display = 'flex';
    }

    // ── CONFIRMACIÓN FINAL COMPARTIDA ──
    function cerrarConfirmFinal() {
        document.getElementById('modalConfirmFinal').style.display = 'none';
        formPendienteEnvio = null;
    }
        function ejecutarEnvioFinal() {
        if (!formPendienteEnvio) return;
        const btn = document.getElementById('btnConfirmFinal');
        btn.innerText = 'Procesando...';
        btn.disabled  = true;
        btn.style.opacity = '0.7';
        formPendienteEnvio.submit();
    }

    // ── AUTO-APERTURA DEL MODAL DESDE NOTIFICACIÓN (?abrir_caso=ID) ──
    document.addEventListener('DOMContentLoaded', function () {
        const params = new URLSearchParams(window.location.search);
        const idAAbrir = params.get('abrir_caso');
        if (!idAAbrir) return;

        const filaObjetivo = Array.from(document.querySelectorAll('#tablaCasos tbody tr')).find(tr => {
            const inputId = tr.querySelector('.d-id-ticket');
            return inputId && inputId.value === idAAbrir;
        });

        if (filaObjetivo) {
            const btnResolver = filaObjetivo.querySelector('button[onclick*="abrirModalVistaPrevia"]');
            if (btnResolver) {
                abrirModalVistaPrevia(btnResolver);
            }
        }

        const nuevaUrl = window.location.pathname + '?accion=mis_casos';
        window.history.replaceState({}, '', nuevaUrl);
    });
</script>

</div>
</body>
</html>