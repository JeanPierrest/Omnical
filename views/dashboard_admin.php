<?php include 'header.php'; ?>

<style>
    /* ── KPI Cards ── */
    .kpi-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 20px;
        margin-bottom: 30px;
    }
    .kpi-card {
        background: var(--card-bg);
        border-radius: var(--radius);
        box-shadow: var(--shadow);
        padding: 24px 20px;
        text-align: center;
        border-top: 4px solid var(--primary);
        transition: transform 0.2s;
    }
    .kpi-card:hover { transform: translateY(-3px); }
    .kpi-card.amarillo { border-top-color: #F59E0B; }
    .kpi-card.verde    { border-top-color: #10B981; }
    .kpi-card.rojo     { border-top-color: #EF4444; }
    .kpi-card.morado   { border-top-color: #8B5CF6; }
    .kpi-numero {
        font-size: 48px;
        font-weight: 800;
        line-height: 1;
        margin-bottom: 8px;
    }
    .kpi-card.amarillo .kpi-numero { color: #D97706; }
    .kpi-card.verde    .kpi-numero { color: #059669; }
    .kpi-card.rojo     .kpi-numero { color: #DC2626; }
    .kpi-card.morado   .kpi-numero { color: #7C3AED; }
    .kpi-label {
        font-size: 12px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: var(--text-muted);
    }

    /* ── Sección de paneles ── */
    .panel-grid-2 {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
        margin-bottom: 30px;
    }
    .panel-grid-2-1 {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
        margin-bottom: 30px;
    }
    .panel {
        background: var(--card-bg);
        border-radius: var(--radius);
        box-shadow: var(--shadow);
        overflow: hidden;
    }
    .panel-header {
        padding: 15px 20px;
        font-size: 13px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: white;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .panel-header.azul   { background: var(--primary); }
    .panel-header.verde  { background: #059669; }
    .panel-header.morado { background: #7C3AED; }
    .panel-body { padding: 20px; }

    /* ── Top 3 ── */
    .top3-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px 0;
        border-bottom: 1px solid var(--border-color);
    }
    .top3-item:last-child { border-bottom: none; }
    .top3-medalla {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 800;
        font-size: 14px;
        flex-shrink: 0;
    }
    .medalla-1 { background: #FEF3C7; color: #B45309; }
    .medalla-2 { background: #F1F5F9; color: #64748B; }
    .medalla-3 { background: #FEF3C7; color: #B45309; opacity: 0.6; }
    .top3-texto { flex: 1; font-size: 13px; color: var(--text-main); font-weight: 500; }
    .top3-cantidad {
        font-size: 20px;
        font-weight: 800;
        color: var(--primary);
    }
    .top3-vacio {
        text-align: center;
        padding: 20px;
        color: var(--text-muted);
        font-style: italic;
        font-size: 13px;
    }

    /* ── Excel ── */
    .excel-seccion { margin-bottom: 20px; }
    .excel-seccion h4 {
        font-size: 13px;
        font-weight: 700;
        color: var(--secondary);
        margin: 0 0 10px 0;
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .excel-row {
        display: flex;
        gap: 10px;
        align-items: flex-end;
    }
    .excel-campo { flex: 1; }
    .excel-campo label {
        display: block;
        font-size: 11px;
        font-weight: 600;
        color: var(--text-muted);
        margin-bottom: 5px;
        text-transform: uppercase;
    }
    .excel-campo input[type="date"] {
        width: 100%;
        padding: 8px 10px;
        border: 1px solid var(--border-color);
        border-radius: 4px;
        font-size: 13px;
        box-sizing: border-box;
    }
    .btn-excel {
        background: #16A34A;
        color: white;
        border: none;
        padding: 9px 16px;
        border-radius: 4px;
        font-size: 13px;
        font-weight: 700;
        cursor: pointer;
        white-space: nowrap;
        transition: opacity 0.2s;
        text-decoration: none;
        display: inline-block;
    }
    .btn-excel:hover { opacity: 0.85; }
    .divider { border: none; border-top: 1px solid var(--border-color); margin: 15px 0; }

    /* ── Auto-Asignar ── */
    .analista-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px 0;
        border-bottom: 1px solid var(--border-color);
    }
    .analista-item:last-child { border-bottom: none; }
    .analista-check { width: 18px; height: 18px; cursor: pointer; accent-color: var(--primary); }
    .analista-nombre { flex: 1; font-size: 14px; font-weight: 600; color: var(--text-main); }
    .analista-carga {
        font-size: 12px;
        background: #E0E7FF;
        color: #3730A3;
        padding: 3px 10px;
        border-radius: 20px;
        font-weight: 700;
    }
    .btn-asignar {
        width: 100%;
        background: #7C3AED;
        color: white;
        border: none;
        padding: 13px;
        border-radius: 6px;
        font-size: 14px;
        font-weight: 700;
        cursor: pointer;
        margin-top: 20px;
        transition: opacity 0.2s;
    }
    .btn-asignar:hover { opacity: 0.9; }
    .btn-asignar:disabled { background: #94A3B8; cursor: not-allowed; opacity: 1; }
    .alerta-exito {
        background: #D1FAE5;
        border-left: 4px solid #10B981;
        color: #065F46;
        padding: 12px 16px;
        border-radius: 4px;
        margin-bottom: 20px;
        font-weight: 600;
        font-size: 14px;
    }
    .alerta-error {
        background: #FEE2E2;
        border-left: 4px solid #EF4444;
        color: #B91C1C;
        padding: 12px 16px;
        border-radius: 4px;
        margin-bottom: 20px;
        font-weight: 600;
        font-size: 14px;
    }
</style>

<!-- ── ALERTAS ── -->
<?php if (isset($_GET['exito'])): ?>
    <div class="alerta-exito">
        ✅ <?= htmlspecialchars($_GET['exito']) ?> casos asignados exitosamente entre los analistas seleccionados.
    </div>
<?php endif; ?>
<?php if (isset($_GET['error'])): ?>
    <div class="alerta-error">
        ⚠️ <?php
            $err = $_GET['error'];
            if ($err === 'sin_seleccion')    echo 'Debes seleccionar al menos un analista.';
            elseif ($err === 'sin_pendientes') echo 'No hay casos pendientes para asignar.';
            else echo htmlspecialchars($err);
        ?>
    </div>
<?php endif; ?>

<!-- ── FILA 1: KPI CARDS ── -->
<div class="kpi-grid">
    <div class="kpi-card amarillo">
        <div class="kpi-numero"><?= $kpis['TOTAL_PENDIENTE'] ?? 0 ?></div>
        <div class="kpi-label">⏳ Pendientes</div>
    </div>
    <div class="kpi-card verde">
        <div class="kpi-numero"><?= $kpis['TOTAL_RESUELTO'] ?? 0 ?></div>
        <div class="kpi-label">✅ Resueltos</div>
    </div>
    <div class="kpi-card rojo">
        <div class="kpi-numero"><?= $kpis['TOTAL_INCONSISTENTE'] ?? 0 ?></div>
        <div class="kpi-label">❌ Inconsistentes</div>
    </div>
    <div class="kpi-card morado">
        <div class="kpi-numero"><?= $kpis['TOTAL_EN_OBSERVACION'] ?? 0 ?></div>
        <div class="kpi-label">🔍 En Observación</div>
    </div>
</div>

<!-- ── FILA 2: TOP 3 RECHAZOS | TOP 3 DEPENDENCIAS ── -->
<div class="panel-grid-2">

    <!-- TOP 3: MOTIVOS DE RECHAZO -->
    <div class="panel">
        <div class="panel-header rojo" style="background:#DC2626;">
            ❌ Top 3 Motivos de Rechazo (Mes Actual)
        </div>
        <div class="panel-body">
            <?php if (!empty($top3Rechazos)): ?>
                <?php foreach ($top3Rechazos as $i => $item): ?>
                    <div class="top3-item">
                        <div class="top3-medalla medalla-<?= $i + 1 ?>"><?= $i + 1 ?></div>
                        <div class="top3-texto"><?= htmlspecialchars($item['DESCRIPCION_MOTIVO'] ?? 'Sin motivo') ?></div>
                        <div class="top3-cantidad"><?= $item['CANTIDAD'] ?></div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="top3-vacio">Sin rechazos este mes 🎉</div>
            <?php endif; ?>
        </div>
    </div>

    <!-- TOP 3: DEPENDENCIAS CON MÁS CASOS -->
    <div class="panel">
        <div class="panel-header azul">
            📊 Top 3 Dependencias con Más Casos (Mes Actual)
        </div>
        <div class="panel-body">
            <?php if (!empty($top3Dependencias)): ?>
                <?php foreach ($top3Dependencias as $i => $item): ?>
                    <div class="top3-item">
                        <div class="top3-medalla medalla-<?= $i + 1 ?>"><?= $i + 1 ?></div>
                        <div class="top3-texto"><?= htmlspecialchars($item['DEPENDENCIA'] ?? 'Sin dependencia') ?></div>
                        <div class="top3-cantidad"><?= $item['CANTIDAD'] ?></div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="top3-vacio">Sin datos este mes</div>
            <?php endif; ?>
        </div>
    </div>

</div>

<!-- ── FILA 3: EXCEL | AUTO-ASIGNAR ── -->
<div class="panel-grid-2-1">

    <!-- DESCARGAS EXCEL -->
    <div class="panel">
        <div class="panel-header verde">
            📥 Descargar Reportes Excel
        </div>
        <div class="panel-body">

            <!-- Descarga por período -->
            <div class="excel-seccion">
                <h4>📅 Por Período Específico</h4>
                <form action="index.php?accion=exportar_excel_periodo" method="POST">
                    <div class="excel-row">
                        <div class="excel-campo">
                            <label>Desde</label>
                            <input type="date" name="fecha_inicio" required
                                   value="<?= date('Y-m-01') ?>">
                        </div>
                        <div class="excel-campo">
                            <label>Hasta</label>
                            <input type="date" name="fecha_fin" required
                                   value="<?= date('Y-m-d') ?>">
                        </div>
                        <button type="submit" class="btn-excel">⬇ Descargar</button>
                    </div>
                </form>
            </div>

            <hr class="divider">

            <!-- Descarga año actual -->
            <div class="excel-seccion">
                <h4>📆 Año Actual (<?= date('Y') ?>)</h4>
                <p style="font-size:12px; color:var(--text-muted); margin:0 0 10px 0;">
                    Desde el 01/01/<?= date('Y') ?> hasta hoy (<?= date('d/m/Y') ?>)
                </p>
                <form action="index.php?accion=exportar_excel_anual" method="POST">
                    <button type="submit" class="btn-excel" style="width:100%;">
                        ⬇ Descargar Año Completo
                    </button>
                </form>
            </div>

        </div>
    </div>

    <!-- AUTO-ASIGNAR -->
    <div class="panel">
        <div class="panel-header morado">
            ⚡ Auto-Asignar Casos Pendientes
        </div>
        <div class="panel-body">
            <p style="font-size:13px; color:var(--text-muted); margin:0 0 15px 0;">
                Selecciona a quién(es) deseas distribuir los 
                <strong style="color:var(--primary);"><?= $kpis['TOTAL_PENDIENTE'] ?? 0 ?></strong> 
                casos pendientes:
            </p>

            <form action="index.php?accion=auto_asignar_seleccionados" method="POST" id="formAsignar">
                <?php if (!empty($miniAdmins)): ?>
                    <?php foreach ($miniAdmins as $analista): ?>
                        <div class="analista-item">
                            <input type="checkbox"
                                   class="analista-check check-analista"
                                   name="analistas[]"
                                   value="<?= $analista['ID_USUARIO'] ?>"
                                   onchange="validarSeleccion()">
                            <span class="analista-nombre">
                                <?= htmlspecialchars($analista['NOMBRE_COMPLETO']) ?>
                                <small style="color:var(--text-muted); font-weight:400;">
                                    (<?= $analista['NOMBRE_ROL'] ?>)
                                </small>
                            </span>
                            <span class="analista-carga">
                                <?= $analista['CARGA_ACTUAL'] ?> casos
                            </span>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="top3-vacio">No hay analistas disponibles.</div>
                <?php endif; ?>

                <button type="submit"
                        id="btnAsignar"
                        class="btn-asignar"
                        disabled
                        onclick="return confirmarAsignacion()">
                    ⚡ Auto-Asignar a Seleccionados
                </button>
            </form>
        </div>
    </div>

</div>

<script>
    // Habilita el botón solo si hay al menos 1 checkbox marcado
    function validarSeleccion() {
        const checks = document.querySelectorAll('.check-analista:checked');
        document.getElementById('btnAsignar').disabled = checks.length === 0;
    }

    // Confirmación antes de asignar
    function confirmarAsignacion() {
        const checks = document.querySelectorAll('.check-analista:checked');
        const nombres = Array.from(checks).map(c => {
            return c.closest('.analista-item')
                    .querySelector('.analista-nombre')
                    .innerText.trim().split('\n')[0];
        });
        const pendientes = <?= $kpis['TOTAL_PENDIENTE'] ?? 0 ?>;

        if (pendientes === 0) {
            alert('No hay casos pendientes para asignar.');
            return false;
        }

        return confirm(
            '¿Confirmas distribuir ' + pendientes + ' casos pendientes entre:\n' +
            nombres.join('\n') + '?'
        );
    }
    // Confirmación antes de descargar Excel por período
    document.querySelector('form[action*="exportar_excel_periodo"]')
    .addEventListener('submit', function(e) {
        const desde = this.querySelector('[name="fecha_inicio"]').value;
        const hasta = this.querySelector('[name="fecha_fin"]').value;
        if (!confirm('¿Confirmas descargar el reporte del ' + desde + ' al ' + hasta + '?')) {
            e.preventDefault();
        }
    });

// Confirmación antes de descargar Excel anual
    document.querySelector('form[action*="exportar_excel_anual"]')
    .addEventListener('submit', function(e) {
        if (!confirm('¿Confirmas descargar el reporte completo del año <?= date('Y') ?>?\n\nEsta acción puede tardar unos segundos.')) {
            e.preventDefault();
        }
    });
</script>

</div>
</body>
</html>