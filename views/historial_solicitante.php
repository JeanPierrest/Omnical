<?php include 'header.php'; ?>

<style>
    .badge-derivado   { background: #FFF3E0; color: #EA580C; border: 1px solid #FED7AA; }
    .badge-anulado    { background: #F1F5F9; color: #64748B; border: 1px solid #CBD5E1; text-decoration: line-through; }
    .badge-reactivado { background: #EDE9FE; color: #6D28D9; border: 1px solid #DDD6FE; }
    .badge-inconsistente { background: #FEF3C7; color: #92400E; border: 1px solid #FDE68A; }
    .badge-observacion { background: #E0F2FE; color: #0369A1; border: 1px solid #BAE6FD; }
    .link-codigo {
        background: none;
        border: none;
        color: var(--primary);
        font-weight: 600;
        text-decoration: underline;
        cursor: pointer;
        font-size: 14px;
        padding: 0;
    }
    .paginacion-bar {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 6px;
        margin-top: 20px;
        flex-wrap: wrap;
    }
    .pag-btn {
        padding: 7px 13px;
        border: 1px solid var(--border-color);
        border-radius: 4px;
        background: white;
        color: var(--secondary);
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
        text-decoration: none;
    }
    .pag-btn:hover:not(.disabled):not(.activo) { border-color: var(--primary); color: var(--primary); }
    .pag-btn.activo { background: var(--primary); color: white; border-color: var(--primary); }
    .pag-btn.disabled { opacity: 0.4; cursor: not-allowed; pointer-events: none; }
</style>

<div class="container">
    
    <!-- ALERTA DE TICKET CREADO -->
    <?php if (isset($_GET['exito']) && $_GET['exito'] == '1'): ?>
        <div style="background: #ECFDF5; border-left: 4px solid #10B981; padding: 15px; margin-bottom: 20px; border-radius: 4px; display: flex; justify-content: space-between; align-items: center;">
            <div>
                <strong style="color: #065F46; font-size: 15px;">¡Requerimiento Generado con Éxito!</strong>
                <p style="margin: 5px 0 0 0; color: #047857; font-size: 13px;">Tu ticket ha sido enviado a la bolsa de atención. Pronto un analista tomará tu caso.</p>
            </div>
            <button onclick="this.parentElement.style.display='none'" style="background: transparent; border: none; font-size: 20px; color: #065F46; cursor: pointer;">&times;</button>
        </div>
    <?php endif; ?>

    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h2 style="color: var(--secondary); border-left: 4px solid var(--primary); padding-left: 10px; margin: 0;">
            Mis Tickets Generados
        </h2>
        <a href="index.php?accion=nuevo" class="btn" style="background: var(--primary); color: white; text-decoration: none; font-weight: 600;">
            + Crear Nuevo Ticket
        </a>
    </div>

    <!-- FILTRO POR MES, AÑO Y BÚSQUEDA -->
    <form method="GET" action="index.php" style="display:flex; align-items:center; gap:10px; background:white; padding:12px 16px; border-radius:6px; box-shadow:var(--shadow); margin-bottom:20px; flex-wrap:wrap;">
        <input type="hidden" name="accion" value="mis_tickets">

        <label style="font-size:12px; font-weight:700; color:var(--text-muted); text-transform:uppercase;">Buscar:</label>
        <input type="text" name="busqueda" placeholder="Código de caso (ej: 2026-0057)"
               value="<?= htmlspecialchars($_GET['busqueda'] ?? '') ?>"
               style="padding:8px 10px; border:1px solid var(--border-color); border-radius:4px; font-size:13px; min-width:200px;">

        <label style="font-size:12px; font-weight:700; color:var(--text-muted); text-transform:uppercase;">Mes:</label>
        <select name="mes" style="padding:8px 10px; border:1px solid var(--border-color); border-radius:4px; font-size:13px; font-weight:600;">
            <option value="">Todos</option>
            <?php
                $nombresMeses = [
                    1=>'Enero', 2=>'Febrero', 3=>'Marzo', 4=>'Abril', 5=>'Mayo', 6=>'Junio',
                    7=>'Julio', 8=>'Agosto', 9=>'Septiembre', 10=>'Octubre', 11=>'Noviembre', 12=>'Diciembre'
                ];
                $mesSeleccionado = isset($_GET['mes']) ? (int)$_GET['mes'] : null;
                foreach ($nombresMeses as $num => $nombre):
            ?>
                <option value="<?= $num ?>" <?= ($num === $mesSeleccionado) ? 'selected' : '' ?>>
                    <?= $nombre ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label style="font-size:12px; font-weight:700; color:var(--text-muted); text-transform:uppercase;">Año:</label>
        <select name="anio" style="padding:8px 10px; border:1px solid var(--border-color); border-radius:4px; font-size:13px; font-weight:600;">
            <option value="">Todos</option>
            <?php
                $anioActual = (int)date('Y');
                $anioSeleccionado = isset($_GET['anio']) ? (int)$_GET['anio'] : null;
                for ($a = $anioActual; $a >= $anioActual - 3; $a--):
            ?>
                <option value="<?= $a ?>" <?= ($a === $anioSeleccionado) ? 'selected' : '' ?>>
                    <?= $a ?>
                </option>
            <?php endfor; ?>
        </select>

        <button type="submit" style="background:var(--primary); color:white; border:none; padding:8px 18px; border-radius:4px; font-size:13px; font-weight:700; cursor:pointer;">
            🔍 Buscar
        </button>
        <a href="index.php?accion=mis_tickets" style="background:#64748B; color:white; border:none; padding:8px 14px; border-radius:4px; font-size:13px; font-weight:700; cursor:pointer; text-decoration:none;">
            ✕ Limpiar
        </a>

        <span style="font-size:12px; color:var(--text-muted); margin-left:auto;">
            <?= $totalRegistros ?> ticket(s) encontrado(s)
        </span>
    </form>

    <table class="tabla-datos">
        <thead>
            <tr>
                <th>Código</th>
                <th>Fecha de Creación</th>
                <th>Dependencia Asignada</th>
                <th>Descripción Breve</th>
                <th>Estado Actual</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($tickets)): ?>
                <tr>
                    <td colspan="5" style="text-align: center; padding: 30px; color: #64748b;">
                        Aún no has generado ningún ticket de soporte.
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($tickets as $t): ?>
                    <?php
                        $estado = $t['ESTADO_ACTUAL'];
                        $badgeClass = match($estado) {
                            'ASIGNADO'       => 'badge-asignado',
                            'RESUELTO'       => 'badge-resuelto',
                            'DERIVADO'       => 'badge-derivado',
                            'ANULADO'        => 'badge-anulado',
                            'REACTIVADO'     => 'badge-reactivado',
                            'INCONSISTENTE'  => 'badge-inconsistente',
                            'EN OBSERVACION' => 'badge-observacion',
                            default          => 'badge-pendiente'
                        };
                        $descFull = $t['DESCRIPCION_REQUERIMIENTO'] ?? '';
                        if (is_object($descFull)) $descFull = $descFull->load();
                        $descBreve = mb_strlen($descFull) > 55 ? mb_substr($descFull, 0, 55) . '...' : $descFull;

                        $motivoAnulacion = $t['MOTIVO_ANULACION'] ?? '';
                        if (is_object($motivoAnulacion)) $motivoAnulacion = $motivoAnulacion->load();
                    ?>
                    <tr>
                        <td>
                            <button type="button" class="link-codigo" onclick="abrirModalTicket(this)">
                                <?= htmlspecialchars($t['CODIGO_TICKET']) ?>
                            </button>
                            <input type="hidden" class="d-codigo" value="<?= htmlspecialchars($t['CODIGO_TICKET']) ?>">
                            <input type="hidden" class="d-fecha" value="<?= htmlspecialchars($t['FECHA_CREACION']) ?>">
                            <input type="hidden" class="d-dependencia" value="<?= htmlspecialchars($t['DEPENDENCIA'] ?? '') ?>">
                            <input type="hidden" class="d-desc" value="<?= htmlspecialchars($descFull) ?>">
                            <input type="hidden" class="d-estado" value="<?= htmlspecialchars($estado) ?>">
                            <input type="hidden" class="d-badge" value="<?= $badgeClass ?>">
                            <input type="hidden" class="d-motivo" value="<?= htmlspecialchars($motivoAnulacion) ?>">
                            <input type="hidden" class="d-analista-actual" value="<?= htmlspecialchars($t['NOMBRE_ANALISTA_ACTUAL'] ?? '') ?>">
                            <input type="hidden" class="d-motivo-inconsistente" value="<?= htmlspecialchars($t['DESCRIPCION_MOTIVO'] ?? '') ?>">
                            <input type="hidden" class="d-obs-cierre" value="<?php $obsC = $t['OBSERVACIONES_CIERRE'] ?? ''; if (is_object($obsC)) $obsC = $obsC->load(); echo htmlspecialchars($obsC); ?>">
                        </td>
                        <td><?= htmlspecialchars($t['FECHA_CREACION']) ?></td>
                        <td><?= htmlspecialchars($t['DEPENDENCIA'] ?? '') ?></td>
                        <td style="max-width: 300px; color: #475569;">
                            <?= htmlspecialchars($descBreve) ?>
                        </td>
                        <td>
                            <span class="badge <?= $badgeClass ?>"><?= htmlspecialchars($estado) ?></span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- CONTROLES DE PAGINACIÓN -->
    <?php if ($totalPaginas > 1): ?>
        <?php
            // Preservamos los filtros de mes/año/accion al cambiar de página
            $paramsBase = [];
            if (!empty($_GET['mes']))      $paramsBase['mes']      = $_GET['mes'];
            if (!empty($_GET['anio']))     $paramsBase['anio']     = $_GET['anio'];
            if (!empty($_GET['busqueda'])) $paramsBase['busqueda'] = $_GET['busqueda'];
            $paramsBase['accion'] = 'mis_tickets';

            function construirUrlPagina($pagina, $paramsBase) {
                $params = $paramsBase;
                $params['pagina'] = $pagina;
                return 'index.php?' . http_build_query($params);
            }

            // Rango de páginas a mostrar (máximo 5 números visibles, centrados en la actual)
            $rangoInicio = max(1, $paginaActual - 2);
            $rangoFin = min($totalPaginas, $rangoInicio + 4);
            $rangoInicio = max(1, $rangoFin - 4);
        ?>
        <div class="paginacion-bar">
            <a href="<?= construirUrlPagina(1, $paramsBase) ?>"
               class="pag-btn <?= ($paginaActual <= 1) ? 'disabled' : '' ?>">« Primera</a>
            <a href="<?= construirUrlPagina(max(1, $paginaActual - 1), $paramsBase) ?>"
               class="pag-btn <?= ($paginaActual <= 1) ? 'disabled' : '' ?>">‹ Anterior</a>

            <?php for ($p = $rangoInicio; $p <= $rangoFin; $p++): ?>
                <a href="<?= construirUrlPagina($p, $paramsBase) ?>"
                   class="pag-btn <?= ($p === $paginaActual) ? 'activo' : '' ?>"><?= $p ?></a>
            <?php endfor; ?>

            <a href="<?= construirUrlPagina(min($totalPaginas, $paginaActual + 1), $paramsBase) ?>"
               class="pag-btn <?= ($paginaActual >= $totalPaginas) ? 'disabled' : '' ?>">Siguiente ›</a>
            <a href="<?= construirUrlPagina($totalPaginas, $paramsBase) ?>"
               class="pag-btn <?= ($paginaActual >= $totalPaginas) ? 'disabled' : '' ?>">Última »</a>
        </div>
        <p style="text-align:center; font-size:12px; color:var(--text-muted); margin-top:8px;">
            Página <?= $paginaActual ?> de <?= $totalPaginas ?>
        </p>
    <?php endif; ?>
</div>

<!-- MODAL DE DETALLE DEL TICKET -->
<div id="modalDetalleTicket" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.6); justify-content:center; align-items:center; z-index:2000;">
    <div style="background:white; padding:25px; border-radius:8px; width:550px; max-width:90%; box-shadow:0 10px 25px rgba(0,0,0,0.2);">
        <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:2px solid #f1f5f9; padding-bottom:10px; margin-bottom:15px;">
            <h3 style="color:var(--secondary); margin:0;">
                Ticket: <span id="dt-codigo" style="color:var(--primary);"></span>
            </h3>
            <button onclick="cerrarModalTicket()" style="background:none; border:none; font-size:24px; cursor:pointer; color:#94a3b8;">&times;</button>
        </div>

        <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px; margin-bottom:15px;">
            <div>
                <strong style="color:var(--text-muted); font-size:12px;">FECHA DE CREACIÓN</strong>
                <div id="dt-fecha" style="font-weight:600; color:#334155; margin-top:3px;"></div>
            </div>
            <div>
                <strong style="color:var(--text-muted); font-size:12px;">DEPENDENCIA</strong>
                <div id="dt-dependencia" style="font-weight:600; color:#334155; margin-top:3px;"></div>
            </div>
        </div>

        <div style="margin-bottom:15px;">
            <strong style="color:var(--secondary); font-size:13px;">Descripción del Requerimiento:</strong>
            <div id="dt-descripcion" style="background:#f8fafc; border-left:4px solid var(--primary); padding:12px; border-radius:4px; margin-top:6px; font-size:13px; color:#334155; max-height:180px; overflow-y:auto; white-space:pre-wrap; line-height:1.5;"></div>
        </div>

        <div style="margin-bottom:15px;">
            <strong style="color:var(--secondary); font-size:13px;">Estado Actual:</strong>
            <div style="margin-top:6px;">
                <span id="dt-badge" class="badge"></span>
            </div>
        </div>

        <div id="dt-bloque-analista" style="display:none; margin-bottom:15px; background:#FFF3E0; border-left:4px solid #EA580C; border-radius:4px; padding:12px 14px;">
            <strong style="color:#9A3412; font-size:13px;">🔀 Actualmente derivado a:</strong>
            <div id="dt-analista" style="color:#9A3412; font-size:13px; margin-top:3px; font-weight:600;"></div>
        </div>

        <div id="dt-bloque-motivo" style="display:none; margin-bottom:15px; background:#FEF2F2; border-left:4px solid #DC2626; border-radius:4px; padding:12px 14px;">
            <strong style="color:#991B1B; font-size:13px;">❌ Motivo de la anulación:</strong>
            <div id="dt-motivo" style="color:#7F1D1D; font-size:13px; margin-top:5px; font-style:italic;"></div>
        </div>

        <div id="dt-bloque-inconsistente" style="display:none; margin-bottom:15px; background:#FFFBEB; border-left:4px solid #F59E0B; border-radius:4px; padding:12px 14px;">
            <strong style="color:#92400E; font-size:13px;">⚠️ Motivo de rechazo:</strong>
            <div id="dt-motivo-inconsistente" style="color:#78350F; font-size:13px; margin-top:5px; font-weight:600;"></div>
            <div id="dt-obs-cierre-wrap" style="display:none; margin-top:8px;">
                <strong style="color:#92400E; font-size:12px;">Detalle del analista:</strong>
                <div id="dt-obs-cierre" style="color:#78350F; font-size:13px; margin-top:3px; font-style:italic;"></div>
            </div>
        </div>

        <div style="display:flex; justify-content:flex-end; border-top:1px solid #f1f5f9; padding-top:15px;">
            <button onclick="cerrarModalTicket()" class="btn" style="background:var(--primary); color:white;">Cerrar</button>
        </div>
    </div>
</div>

<script>
    function abrirModalTicket(boton) {
        const td = boton.closest('td');

        document.getElementById('dt-codigo').innerText = td.querySelector('.d-codigo').value;
        document.getElementById('dt-fecha').innerText = td.querySelector('.d-fecha').value;
        document.getElementById('dt-dependencia').innerText = td.querySelector('.d-dependencia').value;
        document.getElementById('dt-descripcion').innerText = td.querySelector('.d-desc').value;

        const estado = td.querySelector('.d-estado').value;
        const badgeClass = td.querySelector('.d-badge').value;
        const badge = document.getElementById('dt-badge');
        badge.innerText = estado;
        badge.className = 'badge ' + badgeClass;

        const analistaActual = td.querySelector('.d-analista-actual').value;
        const bloqueAnalista = document.getElementById('dt-bloque-analista');
        if (estado === 'DERIVADO' && analistaActual && analistaActual.trim() !== '') {
            bloqueAnalista.style.display = 'block';
            document.getElementById('dt-analista').innerText = analistaActual;
        } else {
            bloqueAnalista.style.display = 'none';
        }

        const motivo = td.querySelector('.d-motivo').value;
        const bloqueMotivo = document.getElementById('dt-bloque-motivo');
        if (estado === 'ANULADO' && motivo && motivo.trim() !== '') {
            bloqueMotivo.style.display = 'block';
            document.getElementById('dt-motivo').innerText = motivo;
        } else {
            bloqueMotivo.style.display = 'none';
        }

        const motivoInc = td.querySelector('.d-motivo-inconsistente').value;
        const obsCierre = td.querySelector('.d-obs-cierre').value;
        const bloqueInc = document.getElementById('dt-bloque-inconsistente');
        if (estado === 'INCONSISTENTE') {
            bloqueInc.style.display = 'block';
            document.getElementById('dt-motivo-inconsistente').innerText = motivoInc || 'Sin motivo tipificado registrado.';

            const wrapObs = document.getElementById('dt-obs-cierre-wrap');
            if (obsCierre && obsCierre.trim() !== '') {
                wrapObs.style.display = 'block';
                document.getElementById('dt-obs-cierre').innerText = obsCierre;
            } else {
                wrapObs.style.display = 'none';
            }
        } else {
            bloqueInc.style.display = 'none';
        }

        document.getElementById('modalDetalleTicket').style.display = 'flex';
    }

    function cerrarModalTicket() {
        document.getElementById('modalDetalleTicket').style.display = 'none';
    }
</script>

</body>
</html>