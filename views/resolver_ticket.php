<?php include 'header.php'; ?>

<style>
    .formulario-caja {
        background: white;
        padding: 0;
        border-radius: 8px;
        box-shadow: var(--shadow);
        max-width: 580px;
        margin: 20px auto;
        border-top: 4px solid var(--primary);
        overflow: hidden;
    }
    .caja-titulo { padding: 25px 30px 15px 30px; }
    .caja-titulo h2 { color: var(--secondary); margin: 0 0 5px 0; }
    .caja-titulo p  { color: var(--text-muted); margin: 0; font-size: 13px; }

    .tabs-bar { display: flex; border-bottom: 2px solid var(--border-color); }
    .tab-btn {
        flex: 1;
        padding: 14px 10px;
        text-align: center;
        background: #F8FAFC;
        border: none;
        cursor: pointer;
        font-size: 13px;
        font-weight: 700;
        color: var(--text-muted);
        transition: all 0.2s;
        border-bottom: 3px solid transparent;
    }
    .tab-btn.activo-cerrar  { background: white; color: #059669; border-bottom-color: #10B981; }
    .tab-btn.activo-derivar { background: white; color: #D97706; border-bottom-color: #D97706; }
    .tab-btn:hover:not(.activo-cerrar):not(.activo-derivar) { background: #F1F5F9; }

    .tab-contenido { padding: 25px 30px 30px 30px; }
    .tab-panel { display: none; }
    .tab-panel.visible { display: block; }

    .form-group { margin-bottom: 18px; }
    label { display: block; font-weight: 600; color: var(--secondary); margin-bottom: 8px; font-size: 13px; }
    textarea, select {
        width: 100%;
        padding: 10px;
        border: 1px solid var(--border-color);
        border-radius: 4px;
        box-sizing: border-box;
        font-family: inherit;
        font-size: 14px;
    }
    .bloque-motivo { display: none; }

    .btn-accion {
        border: none;
        padding: 13px 15px;
        border-radius: 4px;
        cursor: pointer;
        font-weight: 700;
        width: 100%;
        font-size: 14px;
        transition: opacity 0.2s;
    }
    .btn-accion:hover { opacity: 0.85; }
    .btn-resolver { background: #10B981; color: white; }
    .btn-derivar  { background: #D97706; color: white; }

    .info-tip {
        font-size: 12px;
        color: var(--text-muted);
        background: #F8FAFC;
        border-left: 3px solid var(--border-color);
        padding: 8px 12px;
        border-radius: 4px;
        margin-bottom: 18px;
    }
    .info-tip.naranja { border-left-color: #D97706; background: #FFFBEB; color: #92400E; }

    input[type="file"].file-derivar {
        padding: 8px;
        border: 1px dashed #F59E0B;
        background: #FFFBEB;
        width: 100%;
        box-sizing: border-box;
        border-radius: 4px;
        font-size: 13px;
    }
    input[type="file"].file-cerrar {
        padding: 8px;
        border: 1px dashed #10B981;
        background: #ECFDF5;
        width: 100%;
        box-sizing: border-box;
        border-radius: 4px;
        font-size: 13px;
    }
</style>

<?php
    // Si el caso llegó por derivación o retornó de un externo, el cierre normal se reemplaza 
    // por una solicitud de validación dirigida a quien lo derivó inicialmente.
    $estadoActual = $ticket['ESTADO_SEGMENTO_ACTUAL'] ?? '';
    $esCasoDerivado = in_array($estadoActual, ['DERIVADO', 'RETORNO EXTERNO']);
    $puedeDerivarNormal = ($estadoActual !== 'RETORNO EXTERNO');
    
    $accionFormCerrar = $esCasoDerivado ? 'solicitar_validacion' : 'guardar_resolucion';
?>

<div class="formulario-caja">

    <div class="caja-titulo">
        <h2>Gestión del Caso</h2>
        <p>Ticket en atención: <strong style="color:var(--primary);"><?= htmlspecialchars($ticket['CODIGO_TICKET']) ?></strong></p>
        <?php if (!empty($ticket['NUMERO_DOCUMENTO'])): ?>
            <p style="margin-top:4px;">Documento: <strong style="color:var(--secondary);"><?= htmlspecialchars(($ticket['TIPO_DOCUMENTO'] ?? '') . ' ' . $ticket['NUMERO_DOCUMENTO']) ?></strong></p>
        <?php endif; ?>
    </div>

    <?php if (!empty($historialValidaciones)): ?>
        <div style="margin: 0 30px 20px 30px;">
            <button type="button" onclick="toggleHistorialValidaciones()" id="btnToggleHistorial"
                    style="background:#F1F5F9; border:1px solid var(--border-color); color:var(--secondary); font-size:13px; font-weight:700; padding:10px 14px; border-radius:6px; cursor:pointer; width:100%; text-align:left; display:flex; justify-content:space-between; align-items:center;">
                <span>🗂️ Ver historial de validaciones (<?= count($historialValidaciones) ?>)</span>
                <span id="iconoHistorial">▼</span>
            </button>

            <div id="bloqueHistorialValidaciones" style="display:none; margin-top:10px; border:1px solid var(--border-color); border-radius:6px; overflow:hidden;">
                <?php foreach ($historialValidaciones as $h): ?>
                    <?php
                        $esResuelto = $h['ESTADO_PROPUESTO'] === 'RESUELTO';
                        $obsHist = $h['OBSERVACIONES'] ?? '';
                        if (is_object($obsHist)) $obsHist = $obsHist->load();
                    ?>
                    <div style="padding:12px 14px; border-bottom:1px solid var(--border-color); background:white;">
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px;">
                            <span style="font-size:12px; font-weight:700; color:var(--secondary);">
                                📤 <?= htmlspecialchars($h['NOMBRE_SOLICITA']) ?> solicitó validación
                            </span>
                            <span style="font-size:11px; color:var(--text-muted);"><?= htmlspecialchars($h['FECHA_SOLICITUD']) ?></span>
                        </div>
                        <div style="font-size:12px; color:#334155; margin-bottom:6px;">
                            Propuso: <strong style="color:<?= $esResuelto ? '#059669' : '#B45309' ?>;"><?= htmlspecialchars($h['ESTADO_PROPUESTO']) ?></strong>
                            — "<?= htmlspecialchars(mb_strlen($obsHist) > 100 ? mb_substr($obsHist, 0, 100) . '...' : $obsHist) ?>"
                        </div>

                        <?php if (!empty($h['ARCHIVOS_ADJUNTOS'])): ?>
                            <div style="display:flex; flex-wrap:wrap; gap:6px; margin-bottom:6px;">
                                <?php foreach (explode(',', $h['ARCHIVOS_ADJUNTOS']) as $idx => $arch): ?>
                                    <a href="/Omnical/public/uploads/<?= htmlspecialchars($ticket['CODIGO_TICKET']) ?>/<?= htmlspecialchars(trim($arch)) ?>" target="_blank"
                                       style="background:#3B82F6; color:white; text-decoration:none; padding:4px 10px; border-radius:4px; font-size:11px; font-weight:600;">
                                        <?= strtolower(pathinfo($arch, PATHINFO_EXTENSION)) === 'pdf' ? '📄' : '🖼️' ?> Archivo <?= $idx + 1 ?>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($h['ESTADO_SOLICITUD'] === 'APROBADO'): ?>
                            <div style="font-size:12px; color:#059669; font-weight:600;">
                                ✅ <?= htmlspecialchars($h['NOMBRE_APRUEBA']) ?> aprobó (<?= htmlspecialchars($h['FECHA_RESPUESTA']) ?>)
                            </div>
                        <?php elseif ($h['ESTADO_SOLICITUD'] === 'RECHAZADO'): ?>
                            <div style="font-size:12px; color:#DC2626; font-weight:600;">
                                ❌ <?= htmlspecialchars($h['NOMBRE_APRUEBA']) ?> rechazó (<?= htmlspecialchars($h['FECHA_RESPUESTA']) ?>)
                            </div>
                            <div style="font-size:12px; color:#7F1D1D; margin-top:4px; font-style:italic; background:#FEF2F2; padding:6px 10px; border-radius:4px;">
                                "<?= htmlspecialchars($h['COMENTARIO_RECHAZO'] ?? '') ?>"
                            </div>
                        <?php else: ?>
                            <div style="font-size:12px; color:#0369A1; font-weight:600;">
                                ⏳ Pendiente de respuesta
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- TABS -->
    <div class="tabs-bar">
        <button type="button" class="tab-btn activo-cerrar" id="tabBtnCerrar" onclick="cambiarTab('cerrar')">
            <?= $esCasoDerivado ? '📤 Solicitar Validación' : '✅ Cerrar Caso' ?>
        </button>
        
        <?php if ($puedeDerivarNormal): ?>
        <button type="button" class="tab-btn" id="tabBtnDerivar" onclick="cambiarTab('derivar')">🔀 Derivar Caso</button>
        <?php endif; ?>

        <?php if ($_SESSION['nombre_rol'] === 'Mid Mini Admin'): ?>
        <button type="button" class="tab-btn" id="tabBtnExterno" onclick="cambiarTab('externo')">
            🏢 Derivar a Externo
        </button>
        <?php endif; ?>
    </div>

    <div class="tab-contenido">

        <!-- ═══════════ FORMULARIO CERRAR / SOLICITAR VALIDACIÓN ═══════════ -->
        <form action="index.php?accion=<?= $accionFormCerrar ?>" method="POST" enctype="multipart/form-data" id="formCerrar" class="tab-panel visible" data-tab="cerrar">
            <input type="hidden" name="id_requerimiento" value="<?= $ticket['ID_REQUERIMIENTO'] ?>">
            <input type="hidden" name="accion_tipo" value="cerrar">

            <?php if ($esCasoDerivado): ?>
                <div class="info-tip naranja">
                    ℹ️ Este caso te fue derivado. Al enviar, se solicitará validación a quien te lo derivó antes de cerrarse definitivamente.
                </div>
            <?php endif; ?>

            <div class="form-group">
                <label>Estado Final:</label>
                <select name="estado_final" id="estadoFinal" required onchange="toggleMotivo(this.value)">
                    <option value="">-- Seleccione una opción --</option>
                    <option value="RESUELTO">✅ Resuelto con Éxito</option>
                    <option value="INCONSISTENTE">❌ Rechazar por Inconsistencia</option>
                </select>
            </div>

            <div class="form-group bloque-motivo" id="bloqueMotivo">
                <label style="color:var(--danger);">Motivo de Rechazo (Obligatorio):</label>
                <select name="id_inconsistencia" id="selectMotivo">
                    <option value="">-- Seleccione un motivo --</option>
                    <?php foreach ($catalogosMotivos as $m): ?>
                        <option value="<?= $m['ID_INCONSISTENCIA'] ?>">
                            <?= htmlspecialchars($m['DESCRIPCION_MOTIVO']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Adjuntar Evidencia de la Solución (Opcional - PDF, JPG, PNG):</label>
                <input type="file" name="evidencias_cierre[]" id="inputArchivosCierre" multiple
                       accept=".pdf, .jpg, .jpeg, .png, .doc, .docx" class="file-cerrar">
                <small style="color:#065F46; font-size:11px; display:block; margin-top:5px;">
                    Máximo 5 archivos (captura de pantalla, comprobante, etc.)
                </small>
            </div>

            <div class="form-group">
                <label>Observaciones / Notas de Cierre:</label>
                <textarea name="observaciones" rows="4" required
                          placeholder="Detalle del resultado del caso..."></textarea>
            </div>

            <button type="button" class="btn-accion btn-resolver" onclick="confirmarCerrar()">
                <?= $esCasoDerivado ? '📤 Solicitar Validación de Cierre' : '✅ Guardar y Cerrar Caso' ?>
            </button>
        </form>

        <!-- ═══════════ FORMULARIO DERIVAR ═══════════ -->
        <form action="index.php?accion=guardar_resolucion" method="POST" enctype="multipart/form-data" id="formDerivar" class="tab-panel" data-tab="derivar">
            <input type="hidden" name="id_requerimiento" value="<?= $ticket['ID_REQUERIMIENTO'] ?>">
            <input type="hidden" name="accion_tipo" value="derivar">

            <div class="info-tip naranja">
                ℹ️ El caso se moverá a la bolsa del analista seleccionado. Debes explicar el motivo en observaciones.
            </div>

            <div class="form-group">
                <label>Analista Destino:</label>
                <select name="id_analista_destino" id="selectDerivar" required>
                    <option value="">-- Seleccionar analista --</option>

                    <?php
                        $miniAdmins = array_filter($analistas, fn($a) =>
                            $a['NOMBRE_ROL'] === 'Mini Admin' && $a['ID_USUARIO'] != $_SESSION['id_usuario']
                        );
                        $midMiniAdmins = array_filter($analistas, fn($a) =>
                            $a['NOMBRE_ROL'] === 'Mid Mini Admin'
                        );
                    ?>

                    <?php if (!empty($miniAdmins)): ?>
                        <optgroup label="── Mini Admin ──">
                            <?php foreach ($miniAdmins as $a): ?>
                                <option value="<?= $a['ID_USUARIO'] ?>">
                                    <?= htmlspecialchars($a['NOMBRE_COMPLETO']) ?>
                                </option>
                            <?php endforeach; ?>
                        </optgroup>
                    <?php endif; ?>

                    <?php if (!empty($midMiniAdmins)): ?>
                        <optgroup label="── Mid Mini Admin ──">
                            <?php foreach ($midMiniAdmins as $a): ?>
                                <option value="<?= $a['ID_USUARIO'] ?>">
                                    <?= htmlspecialchars($a['NOMBRE_COMPLETO']) ?>
                                </option>
                            <?php endforeach; ?>
                        </optgroup>
                    <?php endif; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Adjuntar Archivo Adicional (Opcional - PDF, JPG, PNG):</label>
                <input type="file" name="evidencias_derivacion[]" id="inputArchivosDerivar" multiple
                       accept=".pdf, .jpg, .jpeg, .png, .doc, .docx" class="file-derivar">
                <small style="color:#92400E; font-size:11px; display:block; margin-top:5px;">
                    Máximo 5 archivos adicionales para dar más contexto al analista destino.
                </small>
            </div>

            <div class="form-group">
                <label>Observaciones (Motivo de la Derivación):</label>
                <textarea name="observaciones" rows="4" required
                          placeholder="Explica por qué derivas este caso..."></textarea>
            </div>

            <button type="button" class="btn-accion btn-derivar" onclick="confirmarDerivar()">
                🔀 Confirmar Derivación
            </button>
        </form>

       <!-- ═══════════ FORMULARIO DERIVAR A EXTERNO (GCTIC, Proveedores) ═══════════ -->
        <form action="index.php?accion=derivar_externo" method="POST" enctype="multipart/form-data" id="formExterno" class="tab-panel" data-tab="externo">
            <input type="hidden" name="id_requerimiento" value="<?= $ticket['ID_REQUERIMIENTO'] ?>">

            <div class="info-tip" style="border-left-color: #4338CA; background: #EEF2FF; color: #312E81;">
                ℹ️ El reloj SLA de este caso se pausará y pasará a tu bandeja de "Casos Externos" hasta que registres la respuesta.
            </div>

            <div class="form-group">
                <label>Área o Entidad Externa (Ej. GCTIC, Proveedor):</label>
                <input type="text" name="entidad_externa" required placeholder="Escribe el nombre del área o persona..." 
                       style="width: 100%; padding: 10px; border: 1px solid var(--border-color); border-radius: 4px; font-size: 14px;">
            </div>

            <div class="form-group" style="margin-top: 15px;">
                <label>Persona de Contacto (Opcional):</label>
                <input type="text" name="contacto_externo" class="form-control" placeholder="Ej. Juan Pérez - Soporte Nivel 2">
            </div>

            <div class="form-group">
                <label>Fecha de Envío del Correo:</label>
                <input type="date" name="fecha_envio" required class="form-control" value="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d') ?>">
            </div>

            <div class="form-group">
                <label>Hora de Envío (24h):</label>
                <div style="display:flex; gap:8px; align-items:center;">
                    <select name="hora_envio_hh" required style="flex:1; padding:8px; border:1px solid var(--border-color); border-radius:4px;">
                        <?php for ($h = 0; $h <= 23; $h++): ?>
                            <option value="<?= str_pad($h, 2, '0', STR_PAD_LEFT) ?>" <?= ($h == (int)date('H')) ? 'selected' : '' ?>>
                                <?= str_pad($h, 2, '0', STR_PAD_LEFT) ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                    <span style="font-weight:700; color:var(--text-muted);">:</span>
                    <select name="hora_envio_mm" required style="flex:1; padding:8px; border:1px solid var(--border-color); border-radius:4px;">
                        <?php for ($m = 0; $m <= 59; $m++): ?>
                            <option value="<?= str_pad($m, 2, '0', STR_PAD_LEFT) ?>" <?= ($m == (int)date('i')) ? 'selected' : '' ?>>
                                <?= str_pad($m, 2, '0', STR_PAD_LEFT) ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>
                <small style="color:var(--text-muted); font-size:11px; display:block; margin-top:4px;">
                    Hora real en que enviaste el correo (no cuando lo registras aquí). Formato 24h.
                </small>
            </div>

            <div class="form-group">
                <label>Adjuntar Evidencia del Envío (Correo enviado - Opcional):</label>
                <input type="file" name="evidencia_externa" accept=".pdf, .jpg, .jpeg, .png, .doc, .docx" 
                       style="padding: 8px; border: 1px dashed #4338CA; background: #EEF2FF; width: 100%; border-radius: 4px; font-size: 13px;">
            </div>

            <div class="form-group">
                <label>Observaciones (Motivo de la consulta):</label>
                <textarea name="observaciones" rows="4" required
                          placeholder="Explica qué se está solicitando a esta área externa..."></textarea>
            </div>

            <button type="button" class="btn-accion" style="background: #4338CA; color: white;" onclick="confirmarExterno()">
                🏢 Pausar Tiempo y Derivar a Externo
            </button>
        </form>

    </div>

    <div style="text-align:center; padding: 0 30px 20px 30px;">
        <a href="index.php?accion=mis_casos" style="color:var(--text-muted); font-size:13px; text-decoration:none;">
           ← Cancelar y volver a Mis Casos
        </a>
    </div>
</div>

<!-- MODAL DE CONFIRMACIÓN -->
<div id="modalConfirm" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); justify-content:center; align-items:center; z-index:1000;">
    <div style="background:white; padding:25px; border-radius:8px; width:400px; max-width:90%; text-align:center; box-shadow:0 4px 12px rgba(0,0,0,0.15);">
        <h3 style="color:var(--secondary); margin-top:0;">⚠️ Confirmación</h3>
        <p id="textoConfirm" style="color:var(--text-main); font-size:14px; margin:15px 0;"></p>
        <div style="display:flex; justify-content:center; gap:15px; margin-top:20px;">
            <button onclick="cerrarConfirm()" class="btn" style="background:#94A3B8; color:white;">
                Cancelar
            </button>
            <button onclick="ejecutarEnvio()" id="btnConfirmarAccion" class="btn btn-primary">
                Sí, confirmar
            </button>
        </div>
    </div>
</div>

<script>
    let formActivo = null;

    // ── TOGGLE HISTORIAL DE VALIDACIONES ──
    function toggleHistorialValidaciones() {
        const bloque = document.getElementById('bloqueHistorialValidaciones');
        const icono = document.getElementById('iconoHistorial');
        const abierto = bloque.style.display !== 'none';
        bloque.style.display = abierto ? 'none' : 'block';
        icono.innerText = abierto ? '▼' : '▲';
    }

    // ── CAMBIO DE TABS ──
    function cambiarTab(tab) {
        document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('visible'));
        document.querySelector('[data-tab="' + tab + '"]').classList.add('visible');

        document.getElementById('tabBtnCerrar').classList.remove('activo-cerrar');
        document.getElementById('tabBtnDerivar').classList.remove('activo-derivar');
        
        const tabBtnExt = document.getElementById('tabBtnExterno');
        if (tabBtnExt) tabBtnExt.classList.remove('activo-externo');

        if (tab === 'cerrar') {
            document.getElementById('tabBtnCerrar').classList.add('activo-cerrar');
        } else if (tab === 'derivar') {
            document.getElementById('tabBtnDerivar').classList.add('activo-derivar');
        } else if (tab === 'externo') {
            if (tabBtnExt) tabBtnExt.classList.add('activo-externo');
        }
    }

    // ── CONFIRMACIÓN: EXTERNO ──
    function confirmarExterno() {
        const form = document.getElementById('formExterno');
        if (!form.checkValidity()) { form.reportValidity(); return; }

        const entidad = form.querySelector('[name="entidad_externa"]').value;

        formActivo = form;
        document.getElementById('textoConfirm').innerText =
            '¿Confirmas derivar el caso a ' + entidad + '?\n\nEl tiempo del SLA se pausará hasta que registres su respuesta.';
        document.getElementById('modalConfirm').style.display = 'flex';
    }

    // ── MOSTRAR/OCULTAR MOTIVO DE RECHAZO ──
    function toggleMotivo(valor) {
        const bloque = document.getElementById('bloqueMotivo');
        const select = document.getElementById('selectMotivo');
        if (valor === 'INCONSISTENTE') {
            bloque.style.display = 'block';
            select.required = true;
        } else {
            bloque.style.display = 'none';
            select.required = false;
            select.value = '';
        }
    }

    // ── VALIDACIÓN MÁXIMO 2 ARCHIVOS (CERRAR) ──
    document.getElementById('inputArchivosCierre').addEventListener('change', function () {
        if (this.files.length > 2) {
            alert('Solo puedes adjuntar un máximo de 2 archivos adicionales.');
            this.value = '';
        }
    });

    // ── VALIDACIÓN MÁXIMO 2 ARCHIVOS (DERIVAR) ──
    document.getElementById('inputArchivosDerivar').addEventListener('change', function () {
        if (this.files.length > 2) {
            alert('Solo puedes adjuntar un máximo de 2 archivos adicionales al derivar.');
            this.value = '';
        }
    });

    // ── CONFIRMACIÓN: CERRAR ──
    function confirmarCerrar() {
        const form = document.getElementById('formCerrar');
        if (!form.checkValidity()) { form.reportValidity(); return; }

        const estado = document.getElementById('estadoFinal').value;
        if (estado === 'INCONSISTENTE' && !document.getElementById('selectMotivo').value) {
            alert('Debes seleccionar un motivo de rechazo.');
            return;
        }

        formActivo = form;
        const mensajeConfirm = <?= $esCasoDerivado ? 'true' : 'false' ?>
            ? '¿Confirmas enviar este caso a validación como ' + estado + '? Se notificará a quien te lo derivó.'
            : '¿Confirmas procesar este caso como ' + estado + '?';
        document.getElementById('textoConfirm').innerText = mensajeConfirm;
        document.getElementById('modalConfirm').style.display = 'flex';
    }

    // ── CONFIRMACIÓN: DERIVAR ──
    function confirmarDerivar() {
        const form = document.getElementById('formDerivar');
        if (!form.checkValidity()) { form.reportValidity(); return; }

        const select = document.getElementById('selectDerivar');
        const nombreDestino = select.options[select.selectedIndex].text;

        formActivo = form;
        document.getElementById('textoConfirm').innerText =
            '¿Confirmas derivar este caso a ' + nombreDestino + '?';
        document.getElementById('modalConfirm').style.display = 'flex';
    }

    function cerrarConfirm() {
        document.getElementById('modalConfirm').style.display = 'none';
        formActivo = null;
    }

    function ejecutarEnvio() {
        if (!formActivo) return;
        const btn = document.getElementById('btnConfirmarAccion');
        btn.innerText     = 'Procesando...';
        btn.disabled      = true;
        btn.style.opacity = '0.7';
        formActivo.submit();
    }
</script>

</div>
</body>
</html>