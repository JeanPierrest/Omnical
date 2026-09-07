<?php include 'header.php'; ?>

<style>
    .badge-pausado { background: #EEF2FF; color: #4338CA; border: 1px solid #C7D2FE; }
    .btn-respuesta { background: #10B981; color: white; padding: 6px 12px; font-size: 12px; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; }
    .btn-respuesta:hover { opacity: 0.85; }
    .btn-recuperar { background: #64748B; color: white; padding: 6px 12px; font-size: 12px; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; }
    .btn-recuperar:hover { opacity: 0.85; }
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
                if ($ex === 'tiempo_reanudado') echo 'Respuesta registrada. El tiempo del caso fue reanudado correctamente.';
                elseif ($ex === 'recuperado') echo 'El caso fue recuperado a tu bandeja principal.';
                else echo htmlspecialchars($ex);
            ?>
        </div>
    <?php endif; ?>
    <?php if (isset($_GET['error'])): ?>
        <div class="alerta-error">
            ⚠️ <?php
                $er = $_GET['error'];
                if ($er === 'fecha_requerida')            echo 'Debes ingresar fecha y hora de la respuesta.';
                elseif ($er === 'fecha_invalida')          echo 'El formato de fecha/hora no es válido.';
                elseif ($er === 'fecha_futura')            echo 'La fecha de respuesta no puede ser posterior al momento actual.';
                elseif ($er === 'fecha_anterior_derivacion') echo 'La fecha de respuesta no puede ser anterior a cuando se derivó el caso a la entidad externa.';
                elseif ($er === 'fallo_reanudacion')       echo 'No se pudo registrar la respuesta. Intenta de nuevo.';
                elseif ($er === 'fallo_recuperar')         echo 'No se pudo recuperar el caso. Intenta de nuevo.';
                else echo htmlspecialchars($er);
            ?>
        </div>
    <?php endif; ?>

    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h2 style="color: var(--secondary); border-left: 4px solid #4338CA; padding-left: 10px; margin: 0;">
            Bandeja de Casos Externos (En Pausa) ⏱️
        </h2>
        <span style="font-size: 13px; color: var(--text-muted);">
            Total Pausados: <strong><?= count($tickets) ?></strong>
        </span>
    </div>

    <div class="info-tip" style="background: #EEF2FF; border-left: 4px solid #4338CA; padding: 12px; border-radius: 4px; font-size: 13px; color: #312E81; margin-bottom: 20px;">
        ℹ️ El tiempo SLA de los casos en esta bandeja está <strong>pausado</strong>. Cuando registres la respuesta ingresando la fecha y hora manual, el sistema descontará el tiempo que demoró la entidad externa automáticamente.
    </div>

    <table class="tabla-datos">
        <thead>
            <tr>
                <th>Código</th>
                <th>Enviado el</th>
                <th>Entidad Externa</th>
                <th>Dependencia</th>
                <th>Descripción Breve</th>
                <th>Estado</th>
                <th style="text-align: center;">Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($tickets)): ?>
                <tr>
                    <td colspan="7" style="text-align: center; padding: 30px; color: var(--text-muted);">
                        No tienes casos en espera de respuesta externa.
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($tickets as $t): ?>
                    <?php
                        $desc = $t['DESCRIPCION_REQUERIMIENTO'] ?? '';
                        if (is_object($desc)) $desc = $desc->load();
                        $descBreve = mb_strlen($desc) > 40 ? mb_substr($desc, 0, 40) . '...' : $desc;
                    ?>
                    <tr>
                        <td style="font-weight: 600; color: var(--primary);"><?= htmlspecialchars($t['CODIGO_TICKET']) ?></td>
                        <td><?= htmlspecialchars($t['FECHA_DERIVACION']) ?></td>
                        <td style="font-weight: bold; color: #4338CA;">🏢 <?= htmlspecialchars($t['ENTIDAD_EXTERNA']) ?></td>
                        <td><?= htmlspecialchars($t['DEPENDENCIA'] ?? '') ?></td>
                        <td style="max-width: 250px;"><?= htmlspecialchars($descBreve) ?></td>
                        <td><span class="badge badge-pausado">EN PAUSA EXTERNA</span></td>
                        <td>
                            <div style="display: flex; gap: 5px; justify-content: center;">
                                <button class="btn-respuesta" onclick="abrirModalRespuesta(<?= $t['ID_REQUERIMIENTO'] ?>, '<?= $t['CODIGO_TICKET'] ?>')">✅ Con Respuesta</button>
                                <button class="btn-recuperar" onclick="abrirModalRecuperar(<?= $t['ID_REQUERIMIENTO'] ?>, '<?= $t['CODIGO_TICKET'] ?>')">↩️ Sin Respuesta</button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- MODAL: CON RESPUESTA (REANUDAR TIEMPO) -->
<div id="modalRespuesta" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.6); justify-content:center; align-items:center; z-index:2000;">
    <div style="background:white; padding:25px; border-radius:8px; width:450px; max-width:90%; box-shadow:0 10px 25px rgba(0,0,0,0.2);">
        <h3 style="color:#10B981; margin-top:0;">✅ Registrar Respuesta Externa</h3>
        <p style="font-size:13px; color:var(--text-muted); margin-bottom:15px;">
            Ticket: <strong id="res-codigo" style="color:var(--primary);"></strong><br>
            Ingresa la fecha y hora exacta en la que recibiste el correo de respuesta para que el sistema reanude tu tiempo correctamente.
        </p>

        <form action="index.php?accion=registrar_respuesta_externa" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="id_requerimiento" id="res-id-ticket">
            
            <div class="form-group">
                <label>Fecha de Respuesta:</label>
                <input type="date" name="fecha_respuesta" required class="form-control" value="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d') ?>">
            </div>

            <div class="form-group">
                <label>Hora de Respuesta (24h):</label>
                <div style="display:flex; gap:8px; align-items:center;">
                    <select name="hora_hh" required style="flex:1; padding:8px; border:1px solid var(--border-color); border-radius:4px;">
                        <?php for ($h = 0; $h <= 23; $h++): ?>
                            <option value="<?= str_pad($h, 2, '0', STR_PAD_LEFT) ?>" <?= ($h == (int)date('H')) ? 'selected' : '' ?>>
                                <?= str_pad($h, 2, '0', STR_PAD_LEFT) ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                    <span style="font-weight:700; color:var(--text-muted);">:</span>
                    <select name="hora_mm" required style="flex:1; padding:8px; border:1px solid var(--border-color); border-radius:4px;">
                        <?php for ($m = 0; $m <= 59; $m++): ?>
                            <option value="<?= str_pad($m, 2, '0', STR_PAD_LEFT) ?>" <?= ($m == (int)date('i')) ? 'selected' : '' ?>>
                                <?= str_pad($m, 2, '0', STR_PAD_LEFT) ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>
                <small style="color:var(--text-muted); font-size:11px; display:block; margin-top:4px;">
                    Formato de 24 horas — ej: 14:30 es 2:30 de la tarde.
                </small>
            </div>

            <div class="form-group">
                <label>Adjuntar Evidencia (Captura del correo - Opcional):</label>
                <input type="file" name="evidencia_retorno" accept=".pdf, .jpg, .jpeg, .png, .doc, .docx" class="form-control" style="background:#ECFDF5; border-color:#10B981;">
            </div>

            <div class="form-group">
                <label>Comentario / Detalle de la respuesta (Opcional):</label>
                <textarea name="comentarios" rows="3" class="form-control" placeholder="Anota lo que te indicaron..."></textarea>
            </div>

            <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:15px;">
                <button type="button" onclick="cerrarModalRespuesta()" class="btn" style="background:#94A3B8; color:white;">Cancelar</button>
                <button type="submit" class="btn" style="background:#10B981; color:white;">Guardar y Reanudar Caso</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL: SIN RESPUESTA (RECUPERAR) -->
<div id="modalRecuperar" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.6); justify-content:center; align-items:center; z-index:2000;">
    <div style="background:white; padding:25px; border-radius:8px; width:400px; max-width:90%; text-align:center;">
        <h3 style="color:var(--secondary); margin-top:0;">↩️ Recuperar Caso</h3>
        <p style="font-size:13px; color:var(--text-muted); margin:15px 0;">
            ¿Deseas recuperar el ticket <strong id="rec-codigo"></strong> a tu bandeja principal?<br><br>
            El tiempo volverá a correr desde este instante sin descontar nada, ya que la entidad externa no respondió.
        </p>

        <form action="index.php?accion=recuperar_externo" method="POST">
            <input type="hidden" name="id_requerimiento" id="rec-id-ticket">
            <div style="display:flex; justify-content:center; gap:10px;">
                <button type="button" onclick="cerrarModalRecuperar()" class="btn" style="background:#94A3B8; color:white;">Cancelar</button>
                <button type="submit" class="btn" style="background:#64748B; color:white;">Sí, recuperar a mi bandeja</button>
            </div>
        </form>
    </div>
</div>

<script>
    function abrirModalRespuesta(id, codigo) {
        document.getElementById('res-id-ticket').value = id;
        document.getElementById('res-codigo').innerText = codigo;
        document.getElementById('modalRespuesta').style.display = 'flex';
    }
    function cerrarModalRespuesta() {
        document.getElementById('modalRespuesta').style.display = 'none';
    }

    function abrirModalRecuperar(id, codigo) {
        document.getElementById('rec-id-ticket').value = id;
        document.getElementById('rec-codigo').innerText = codigo;
        document.getElementById('modalRecuperar').style.display = 'flex';
    }
    function cerrarModalRecuperar() {
        document.getElementById('modalRecuperar').style.display = 'none';
    }
</script>

</body>
</html>