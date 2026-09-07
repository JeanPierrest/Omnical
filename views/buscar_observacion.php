<?php include 'header.php'; ?>

<style>
    .badge-observacion { background: #E0F2FE; color: #0369A1; border: 1px solid #BAE6FD; }
    .buscador-obs {
        display: flex;
        align-items: center;
        gap: 10px;
        background: white;
        padding: 14px 16px;
        border-radius: 6px;
        box-shadow: var(--shadow);
        margin-bottom: 20px;
        flex-wrap: wrap;
    }
    .buscador-obs input[type="text"] { flex: 1; min-width: 200px; padding: 9px 12px; border: 1px solid var(--border-color); border-radius: 4px; font-size: 13px; }
    .buscador-obs input[type="date"] { padding: 8px 10px; border: 1px solid var(--border-color); border-radius: 4px; font-size: 13px; }
    .buscador-obs label { font-size: 12px; font-weight: 700; color: var(--text-muted); }
    .btn-buscar-obs { background: var(--primary); color: white; border: none; padding: 9px 18px; border-radius: 4px; font-size: 13px; font-weight: 700; cursor: pointer; }
    .btn-observar { background: #0369A1; color: white; border: none; font-size: 12px; padding: 6px 14px; border-radius: 4px; cursor: pointer; font-weight: 600; }
    .btn-observar:hover { opacity: 0.85; }
    .aviso-tip { font-size: 12px; color: #64748B; background: #F1F5F9; border-left: 3px solid #94A3B8; padding: 8px 12px; border-radius: 4px; margin-bottom: 20px; }
    .alerta-exito { background: #D1FAE5; border-left: 4px solid #10B981; color: #065F46; padding: 12px 16px; border-radius: 4px; margin-bottom: 20px; font-weight: 600; font-size: 14px; }
    .alerta-error { background: #FEE2E2; border-left: 4px solid #EF4444; color: #B91C1C; padding: 12px 16px; border-radius: 4px; margin-bottom: 20px; font-weight: 600; font-size: 14px; }
</style>

<div class="container">
    <h2 style="color: var(--secondary); border-left: 4px solid #0369A1; padding-left: 10px; margin-bottom: 20px;">
        🔍 Buscar Caso para Observación
    </h2>

    <?php if (isset($_GET['error'])): ?>
        <div class="alerta-error">
            ⚠️ <?php
                $er = $_GET['error'];
                if ($er === 'motivo_requerido') echo 'Debes indicar un motivo para poner el caso en observación.';
                elseif ($er === 'fallo_observacion') echo 'No se pudo marcar el caso en observación. Intenta de nuevo.';
                else echo htmlspecialchars($er);
            ?>
        </div>
    <?php endif; ?>

    <div class="aviso-tip">
        Busca cualquier caso ya <strong>Resuelto</strong> o <strong>Inconsistente</strong>, de cualquier analista, para reabrirlo en observación. El caso pasará a tu bandeja de "Mis Casos" y se notificará al analista original.
    </div>

    <form method="GET" action="index.php" class="buscador-obs">
        <input type="hidden" name="accion" value="buscar_observacion">
        <label>Buscar:</label>
        <input type="text" name="busqueda" placeholder="Código de caso o DNI"
               value="<?= htmlspecialchars($_GET['busqueda'] ?? '') ?>">
        <label>Desde:</label>
        <input type="date" name="fecha_desde" value="<?= htmlspecialchars($_GET['fecha_desde'] ?? '') ?>">
        <label>Hasta:</label>
        <input type="date" name="fecha_hasta" value="<?= htmlspecialchars($_GET['fecha_hasta'] ?? '') ?>">
        <button type="submit" class="btn-buscar-obs">🔍 Buscar</button>
    </form>

    <?php if (!empty($tickets)): ?>
        <table class="tabla-datos">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Fecha Creación</th>
                    <th>Solicitante</th>
                    <th>Dependencia</th>
                    <th>Analista</th>
                    <th>Estado</th>
                    <th>Acción</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($tickets as $t): ?>
                    <tr>
                        <td style="font-weight:600; color:var(--primary);"><?= htmlspecialchars($t['CODIGO_TICKET']) ?></td>
                        <td><?= htmlspecialchars($t['FECHA_CREACION']) ?></td>
                        <td><?= htmlspecialchars($t['NOMBRE_SOLICITANTE']) ?></td>
                        <td><?= htmlspecialchars($t['DEPENDENCIA'] ?? '') ?></td>
                        <td><?= htmlspecialchars($t['NOMBRE_ANALISTA'] ?? 'Sin asignar') ?></td>
                        <td><span class="badge badge-resuelto"><?= htmlspecialchars($t['ESTADO_ACTUAL']) ?></span></td>
                        <td>
                            <button class="btn-observar" onclick="abrirModalObservar(<?= $t['ID_REQUERIMIENTO'] ?? 'null' ?>, '<?= htmlspecialchars($t['CODIGO_TICKET']) ?>')">
                                🔍 Poner en Observación
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php elseif (!empty($_GET['busqueda']) || !empty($_GET['fecha_desde'])): ?>
        <p style="text-align:center; color:var(--text-muted); padding:30px;">No se encontraron casos Resueltos/Inconsistentes con ese criterio.</p>
    <?php endif; ?>
</div>

<!-- MODAL CONFIRMAR + MOTIVO -->
<div id="modalObservar" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.6); justify-content:center; align-items:center; z-index:2000;">
    <div style="background:white; padding:25px; border-radius:8px; width:450px; max-width:90%;">
        <h3 style="color:#0369A1; margin-top:0;">🔍 Poner en Observación <span id="obs-codigo" style="color:var(--primary);"></span></h3>
        <p id="obs-paso-confirmar" style="font-size:13px; color:var(--text-muted); margin-bottom:15px;">
            ¿Confirmas que quieres reabrir este caso para revisión? Pasará a tu bandeja y se notificará al analista original.
        </p>

        <form id="formObservar" action="index.php?accion=marcar_en_observacion" method="POST">
            <input type="hidden" name="id_requerimiento" id="obs-id-ticket">

            <div id="obs-bloque-motivo" style="display:none;">
                <label style="display:block; font-weight:600; font-size:13px; margin-bottom:6px; color:var(--secondary);">
                    Motivo de la observación (obligatorio):
                </label>
                <textarea name="motivo_observacion" id="obs-motivo" rows="3" required
                          style="width:100%; padding:10px; border:1px solid var(--border-color); border-radius:4px; box-sizing:border-box; font-family:inherit; font-size:13px;"
                          placeholder="Ej: Se detectó información incompleta en la respuesta..."></textarea>
            </div>

            <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:15px;">
                <button type="button" onclick="cerrarModalObservar()" class="btn" style="background:#94A3B8; color:white;">Cancelar</button>
                <button type="button" id="obs-btn-siguiente" onclick="siguientePasoObservar()" class="btn" style="background:#0369A1; color:white;">Sí, continuar</button>
            </div>
        </form>
    </div>
</div>

<script>
    function abrirModalObservar(id, codigo) {
        document.getElementById('obs-id-ticket').value = id;
        document.getElementById('obs-codigo').innerText = codigo;
        document.getElementById('obs-paso-confirmar').style.display = 'block';
        document.getElementById('obs-bloque-motivo').style.display = 'none';
        document.getElementById('obs-motivo').value = '';
        document.getElementById('obs-btn-siguiente').innerText = 'Sí, continuar';
        document.getElementById('modalObservar').style.display = 'flex';
    }
    function cerrarModalObservar() {
        document.getElementById('modalObservar').style.display = 'none';
    }
    function siguientePasoObservar() {
        const bloqueMotivo = document.getElementById('obs-bloque-motivo');
        if (bloqueMotivo.style.display === 'none') {
            // Paso 1 → Paso 2: mostrar el motivo
            document.getElementById('obs-paso-confirmar').style.display = 'none';
            bloqueMotivo.style.display = 'block';
            document.getElementById('obs-btn-siguiente').innerText = 'Confirmar y Enviar';
        } else {
            // Paso 2: enviar el formulario
            const form = document.getElementById('formObservar');
            if (!form.checkValidity()) { form.reportValidity(); return; }
            form.submit();
        }
    }
</script>

</body>
</html>