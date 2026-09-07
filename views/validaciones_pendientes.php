<?php include 'header.php'; ?>

<style>
    .val-card {
        background: white;
        border-radius: 8px;
        box-shadow: var(--shadow);
        padding: 20px;
        margin-bottom: 15px;
        border-left: 4px solid #7C3AED;
    }
    .val-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 12px;
    }
    .val-codigo { font-weight: 700; color: var(--primary); font-size: 16px; }
    .val-fecha { font-size: 12px; color: var(--text-muted); }
    .val-solicita { font-size: 13px; color: var(--text-muted); margin-bottom: 10px; }
    .val-propuesta {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 700;
        margin-bottom: 10px;
    }
    .val-propuesta.resuelto { background: #D1FAE5; color: #065F46; }
    .val-propuesta.inconsistente { background: #FEF3C7; color: #92400E; }
    .val-desc-box {
        background: #F8FAFC;
        border-left: 3px solid var(--border-color);
        padding: 10px 14px;
        border-radius: 4px;
        font-size: 13px;
        color: #334155;
        margin-bottom: 15px;
        white-space: pre-wrap;
    }
    .val-motivo-box {
        background: #FEF2F2;
        border-left: 3px solid var(--danger);
        padding: 10px 14px;
        border-radius: 4px;
        font-size: 13px;
        color: #991B1B;
        margin-bottom: 15px;
        font-weight: 600;
    }
    .val-acciones { display: flex; gap: 10px; justify-content: flex-end; }
    .btn-aprobar {
        background: #10B981; color: white; border: none;
        padding: 9px 18px; border-radius: 4px; font-weight: 700;
        font-size: 13px; cursor: pointer;
    }
    .btn-aprobar:hover { opacity: 0.85; }
    .btn-rechazar {
        background: #DC2626; color: white; border: none;
        padding: 9px 18px; border-radius: 4px; font-weight: 700;
        font-size: 13px; cursor: pointer;
    }
    .btn-rechazar:hover { opacity: 0.85; }
    .vacio-validaciones {
        text-align: center;
        padding: 50px 20px;
        color: var(--text-muted);
    }
    .alerta-exito {
        background: #D1FAE5; border-left: 4px solid #10B981; color: #065F46;
        padding: 12px 16px; border-radius: 4px; margin-bottom: 20px; font-weight: 600; font-size: 14px;
    }
    .alerta-error {
        background: #FEE2E2; border-left: 4px solid #EF4444; color: #B91C1C;
        padding: 12px 16px; border-radius: 4px; margin-bottom: 20px; font-weight: 600; font-size: 14px;
    }
</style>

<div class="container">

    <?php if (isset($_GET['exito'])): ?>
        <div class="alerta-exito">
            ✅ <?php
                $ex = $_GET['exito'];
                if ($ex === 'aprobado') echo 'Validación aprobada. El caso quedó cerrado.';
                elseif ($ex === 'rechazado') echo 'Validación rechazada. El caso regresó al analista.';
                else echo htmlspecialchars($ex);
            ?>
        </div>
    <?php endif; ?>
    <?php if (isset($_GET['error'])): ?>
        <div class="alerta-error">
            ⚠️ <?php
                $er = $_GET['error'];
                if ($er === 'comentario_requerido') echo 'Debes indicar un motivo para rechazar.';
                else echo htmlspecialchars($er);
            ?>
        </div>
    <?php endif; ?>

    <h2 style="color: var(--secondary); border-left: 4px solid #7C3AED; padding-left: 10px; margin-bottom: 20px;">
        Validaciones Pendientes
    </h2>

    <?php if (empty($solicitudes)): ?>
        <div class="vacio-validaciones">
            <p style="font-size: 40px; margin: 0;">✅</p>
            <p>No tienes solicitudes de validación pendientes.</p>
        </div>
    <?php else: ?>
        <?php foreach ($solicitudes as $s): ?>
            <?php
                $desc = $s['DESCRIPCION_REQUERIMIENTO'] ?? '';
                if (is_object($desc)) $desc = $desc->load();
                $obs = $s['OBSERVACIONES'] ?? '';
                if (is_object($obs)) $obs = $obs->load();
                $esResuelto = $s['ESTADO_PROPUESTO'] === 'RESUELTO';
            ?>
            <div class="val-card">
                <div class="val-header">
                    <div>
                        <div class="val-codigo"><?= htmlspecialchars($s['CODIGO_TICKET']) ?></div>
                        <div class="val-solicita">Solicitado por: <strong><?= htmlspecialchars($s['NOMBRE_SOLICITA']) ?></strong></div>
                    </div>
                    <div class="val-fecha"><?= htmlspecialchars($s['FECHA_SOLICITUD']) ?></div>
                </div>

                <span class="val-propuesta <?= $esResuelto ? 'resuelto' : 'inconsistente' ?>">
                    <?= $esResuelto ? '✅ Propone: RESUELTO' : '❌ Propone: INCONSISTENTE' ?>
                </span>

                <?php if (!$esResuelto && !empty($s['DESCRIPCION_MOTIVO'])): ?>
                    <div class="val-motivo-box">
                        Motivo de rechazo: <?= htmlspecialchars($s['DESCRIPCION_MOTIVO']) ?>
                    </div>
                <?php endif; ?>

                <div>
                    <strong style="font-size: 12px; color: var(--text-muted);">DESCRIPCIÓN ORIGINAL DEL CASO:</strong>
                    <div class="val-desc-box"><?= htmlspecialchars(mb_strlen($desc) > 200 ? mb_substr($desc, 0, 200) . '...' : $desc) ?></div>
                </div>

                <div>
                    <strong style="font-size: 12px; color: var(--text-muted);">OBSERVACIONES DE <?= htmlspecialchars(strtoupper($s['NOMBRE_SOLICITA'])) ?>:</strong>
                    <div class="val-desc-box"><?= htmlspecialchars($obs) ?></div>
                </div>

                <?php if (!empty($s['HISTORIAL_EXTERNO'])): ?>
                    <div style="margin-bottom:15px; background:#EEF2FF; border-left:4px solid #4338CA; border-radius:4px; padding:12px 14px;">
                        <strong style="color:#312E81; font-size:12px;">🏢 Este caso pasó por una entidad externa:</strong>
                        <?php foreach ($s['HISTORIAL_EXTERNO'] as $ext): ?>
                            <?php
                                $obsExt = $ext['RESPUESTA_RESOLUCION'] ?? '';
                                if (is_object($obsExt)) $obsExt = $obsExt->load();
                            ?>
                            <div style="margin-top:8px; padding-top:8px; border-top:1px solid #C7D2FE; font-size:12px; color:#312E81;">
                                <?php if ($ext['ESTADO_SEGMENTO'] === 'DERIVADO EXTERNO'): ?>
                                    📤 Enviado a <strong><?= htmlspecialchars($ext['ENTIDAD_EXTERNA'] ?? '') ?></strong>
                                    <?php if (!empty($ext['CONTACTO_EXTERNO'])): ?>
                                        (Contacto: <?= htmlspecialchars($ext['CONTACTO_EXTERNO']) ?>)
                                    <?php endif; ?>
                                    el <?= htmlspecialchars($ext['FECHA_INICIO']) ?>
                                <?php else: ?>
                                    ✅ Respuesta recibida el <?= htmlspecialchars($ext['FECHA_INICIO']) ?>
                                    <?php if (!empty($obsExt)): ?>
                                        — "<?= htmlspecialchars(mb_strlen($obsExt) > 100 ? mb_substr($obsExt, 0, 100) . '...' : $obsExt) ?>"
                                    <?php endif; ?>
                                <?php endif; ?>

                                <?php if (!empty($ext['ARCHIVO_ADJUNTO'])): ?>
                                    <div style="margin-top:6px;">
                                        <a href="/Omnical/public/uploads/<?= htmlspecialchars($s['CODIGO_TICKET']) ?>/<?= htmlspecialchars($ext['ARCHIVO_ADJUNTO']) ?>" target="_blank"
                                           style="background:#4338CA; color:white; text-decoration:none; padding:4px 10px; border-radius:4px; font-size:11px; font-weight:600;">
                                            <?= strtolower(pathinfo($ext['ARCHIVO_ADJUNTO'], PATHINFO_EXTENSION)) === 'pdf' ? '📄' : '🖼️' ?> Ver evidencia
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <button type="button" onclick="toggleHistorial(<?= $s['ID_REQUERIMIENTO'] ?? 0 ?>, '<?= htmlspecialchars($s['CODIGO_TICKET']) ?>', this)"
                        style="background:#F1F5F9; border:1px solid var(--border-color); color:var(--secondary); font-size:12px; font-weight:700; padding:8px 12px; border-radius:6px; cursor:pointer; width:100%; text-align:left; margin-bottom:15px;">
                    🗂️ Ver historial de este caso ▼
                </button>
                <div class="historial-contenedor" id="historial-<?= $s['ID_REQUERIMIENTO'] ?? 0 ?>" style="display:none; margin-bottom:15px;"></div>

                <div class="val-acciones">
                    <button onclick="abrirModalRechazar(<?= $s['ID_SOLICITUD'] ?>, '<?= htmlspecialchars($s['CODIGO_TICKET']) ?>')" class="btn-rechazar">
                        ❌ Rechazar
                    </button>
                    <button onclick="confirmarAprobar(<?= $s['ID_SOLICITUD'] ?>, '<?= htmlspecialchars($s['CODIGO_TICKET']) ?>')" class="btn-aprobar">
                        ✅ Aprobar y Cerrar
                    </button>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

</div>

<!-- MODAL RECHAZAR -->
<div id="modalRechazar" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.6); justify-content:center; align-items:center; z-index:2000;">
    <div style="background:white; padding:25px; border-radius:8px; width:450px; max-width:90%; box-shadow:0 10px 25px rgba(0,0,0,0.2);">
        <h3 style="color:#DC2626; margin-top:0;">❌ Rechazar Validación <span id="rc-codigo" style="color:var(--primary);"></span></h3>
        <p style="font-size:13px; color:var(--text-muted); margin-bottom:15px;">
            El caso regresará al analista para que lo corrija y vuelva a solicitar validación.
        </p>
        <form id="formRechazar" action="index.php?accion=rechazar_validacion" method="POST">
            <input type="hidden" name="id_solicitud" id="rc-id-solicitud">
            <div class="form-group">
                <label style="display:block; font-weight:600; font-size:13px; margin-bottom:6px; color:var(--secondary);">
                    Motivo del rechazo (obligatorio):
                </label>
                <textarea name="comentario_rechazo" id="rc-comentario" rows="3" required
                          style="width:100%; padding:10px; border:1px solid var(--border-color); border-radius:4px; box-sizing:border-box; font-family:inherit; font-size:13px;"
                          placeholder="Explica qué debe corregir el analista..."></textarea>
            </div>
            <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:15px;">
                <button type="button" onclick="cerrarModalRechazar()" class="btn" style="background:#94A3B8; color:white;">Cancelar</button>
                <button type="button" onclick="enviarRechazo()" class="btn" style="background:#DC2626; color:white;">Confirmar Rechazo</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL APROBAR -->
<div id="modalAprobar" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.6); justify-content:center; align-items:center; z-index:2000;">
    <div style="background:white; padding:25px; border-radius:8px; width:400px; max-width:90%; text-align:center; box-shadow:0 10px 25px rgba(0,0,0,0.2);">
        <h3 style="color:#059669; margin-top:0;">✅ Aprobar Caso <span id="ap-codigo" style="color:var(--primary);"></span></h3>
        <p style="font-size:13px; color:var(--text-muted); margin:15px 0;">
            El caso se cerrará definitivamente con el estado propuesto.
        </p>
        <form id="formAprobar" action="index.php?accion=aprobar_validacion" method="POST">
            <input type="hidden" name="id_solicitud" id="ap-id-solicitud">
            <div style="display:flex; justify-content:center; gap:10px;">
                <button type="button" onclick="cerrarModalAprobar()" class="btn" style="background:#94A3B8; color:white;">Cancelar</button>
                <button type="button" onclick="enviarAprobacion()" class="btn" style="background:#10B981; color:white;">Sí, Aprobar</button>
            </div>
        </form>
    </div>
</div>

<script>
    function toggleHistorial(idRequerimiento, codigoTicket, boton) {
        const contenedor = document.getElementById('historial-' + idRequerimiento);

        if (contenedor.style.display !== 'none') {
            contenedor.style.display = 'none';
            boton.innerHTML = '🗂️ Ver historial de este caso ▼';
            return;
        }

        // Si ya se cargó antes, solo mostramos de nuevo sin volver a pedir
        if (contenedor.dataset.cargado === '1') {
            contenedor.style.display = 'block';
            boton.innerHTML = '🗂️ Ocultar historial ▲';
            return;
        }

        contenedor.innerHTML = '<p style="font-size:12px; color:var(--text-muted); padding:8px;">Cargando...</p>';
        contenedor.style.display = 'block';
        boton.innerHTML = '🗂️ Ocultar historial ▲';

        fetch('index.php?accion=obtener_historial_validaciones_ajax&id_requerimiento=' + idRequerimiento)
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
                            html += `<a href="/Omnical/public/uploads/${codigoTicket}/${archTrim}" target="_blank" style="background:#3B82F6; color:white; text-decoration:none; padding:3px 8px; border-radius:4px; font-size:10px; font-weight:600;">${esPdf ? '📄' : '🖼️'} Archivo ${idx + 1}</a>`;                        });
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

    function abrirModalRechazar(idSolicitud, codigo) {
        document.getElementById('rc-id-solicitud').value = idSolicitud;
        document.getElementById('rc-codigo').innerText = codigo;
        document.getElementById('rc-comentario').value = '';
        document.getElementById('modalRechazar').style.display = 'flex';
    }
    function cerrarModalRechazar() {
        document.getElementById('modalRechazar').style.display = 'none';
    }
    function enviarRechazo() {
        const form = document.getElementById('formRechazar');
        if (!form.checkValidity()) { form.reportValidity(); return; }
        form.submit();
    }

    function confirmarAprobar(idSolicitud, codigo) {
        document.getElementById('ap-id-solicitud').value = idSolicitud;
        document.getElementById('ap-codigo').innerText = codigo;
        document.getElementById('modalAprobar').style.display = 'flex';
    }
    function cerrarModalAprobar() {
        document.getElementById('modalAprobar').style.display = 'none';
    }
    function enviarAprobacion() {
        document.getElementById('formAprobar').submit();
    }
</script>

</body>
</html>