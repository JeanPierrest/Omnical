<?php include 'header.php'; ?>

<style>
    .badge-derivado   { background: #FFF3E0; color: #EA580C; border: 1px solid #FED7AA; }
    .badge-anulado    { background: #F1F5F9; color: #64748B; border: 1px solid #CBD5E1; text-decoration: line-through; }
    .badge-reactivado { background: #EDE9FE; color: #6D28D9; border: 1px solid #DDD6FE; }
    .badge-pausado    { background: #EEF2FF; color: #4338CA; border: 1px solid #C7D2FE; }
    .badge-retorno    { background: #FCE7F3; color: #BE185D; border: 1px solid #FBCFE8; }

    .buscador-bar {
        display: flex;
        align-items: center;
        gap: 10px;
        background: white;
        padding: 14px 16px;
        border-radius: 6px;
        box-shadow: var(--shadow);
        margin-bottom: 15px;
        flex-wrap: wrap;
    }
    .buscador-bar input[type="text"] {
        flex: 1;
        min-width: 200px;
        padding: 9px 12px;
        border: 1px solid var(--border-color);
        border-radius: 4px;
        font-size: 13px;
    }
    .buscador-bar input[type="date"] {
        padding: 8px 10px;
        border: 1px solid var(--border-color);
        border-radius: 4px;
        font-size: 13px;
    }
    .buscador-bar label {
        font-size: 12px;
        font-weight: 700;
        color: var(--text-muted);
    }
    .btn-buscar-viz {
        background: var(--primary);
        color: white;
        border: none;
        padding: 9px 18px;
        border-radius: 4px;
        font-size: 13px;
        font-weight: 700;
        cursor: pointer;
    }
    .btn-buscar-viz:hover { opacity: 0.9; }
    .btn-limpiar-viz {
        background: #64748B;
        color: white;
        border: none;
        padding: 9px 14px;
        border-radius: 4px;
        font-size: 13px;
        font-weight: 700;
        cursor: pointer;
        text-decoration: none;
    }
    .btn-limpiar-viz:hover { opacity: 0.85; }
    .aviso-solo-lectura {
        font-size: 12px;
        color: #64748B;
        background: #F1F5F9;
        border-left: 3px solid #94A3B8;
        padding: 8px 12px;
        border-radius: 4px;
        margin-bottom: 20px;
    }
    .aviso-limite {
        font-size: 12px;
        color: var(--text-muted);
        margin-bottom: 15px;
    }
    .filtro-bar-viz {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 15px;
        flex-wrap: wrap;
    }
    .filtro-bar-viz span { font-size: 13px; color: var(--text-muted); font-weight: 600; }
    .btn-filtro-viz {
        padding: 6px 14px;
        border-radius: 20px;
        border: 2px solid var(--border-color);
        background: white;
        font-size: 12px;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.2s;
        color: var(--text-muted);
    }
    .btn-filtro-viz.activo { border-color: var(--primary); background: var(--primary); color: white; }
    .btn-filtro-viz:hover:not(.activo) { border-color: var(--primary); color: var(--primary); }
    .select-dependencia-viz {
        padding: 7px 12px;
        border: 1px solid var(--border-color);
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
        color: var(--secondary);
        min-width: 240px;
        background: white;
    }
    .link-codigo-viz {
        background: none;
        border: none;
        color: var(--primary);
        font-weight: 600;
        text-decoration: underline;
        cursor: pointer;
        font-size: 13px;
        padding: 0;
    }
</style>

<div class="container">
    <h2 style="color: var(--secondary); border-left: 4px solid #64748B; padding-left: 10px; margin-bottom: 20px;">
        👁️ Visualizador General de Casos
    </h2>

    <div class="aviso-solo-lectura">
        Modo solo lectura. Este panel muestra todos los casos de todos los analistas, en cualquier estado.
    </div>

    <form method="GET" action="index.php" class="buscador-bar">
        <input type="hidden" name="accion" value="visualizador">
        <input type="text" name="busqueda" placeholder="Buscar por número de caso (ej: 2026-0057 o CASO-0049)"
               value="<?= htmlspecialchars($_GET['busqueda'] ?? '') ?>">

        <label>Desde:</label>
        <input type="date" name="fecha_desde" value="<?= htmlspecialchars($_GET['fecha_desde'] ?? '') ?>">

        <label>Hasta:</label>
        <input type="date" name="fecha_hasta" value="<?= htmlspecialchars($_GET['fecha_hasta'] ?? '') ?>">

        <button type="submit" class="btn-buscar-viz">🔍 Buscar</button>
        <a href="index.php?accion=visualizador" class="btn-limpiar-viz">✕ Limpiar</a>
    </form>

    <?php
        $hayFiltroVista = !empty($_GET['busqueda']) || (!empty($_GET['fecha_desde']) && !empty($_GET['fecha_hasta']));
    ?>
    <p class="aviso-limite">
        Mostrando <strong id="contadorVisible"><?= count($tickets) ?></strong> caso(s)<?= $hayFiltroVista ? ' que coinciden con tu búsqueda' : ' más recientes (máximo 200 — usa el buscador o un rango de fechas para ver todos)' ?>.
    </p>

    <!-- FILTRO POR ESTADO -->
    <div class="filtro-bar-viz">
        <span>Estado:</span>
        <button class="btn-filtro-viz activo" data-filtro="estado" data-valor="todos" onclick="filtrarEstadoViz('todos', this)">📋 Todos</button>
        <button class="btn-filtro-viz" data-filtro="estado" data-valor="PENDIENTE" onclick="filtrarEstadoViz('PENDIENTE', this)">🟡 Pendientes</button>
        <button class="btn-filtro-viz" data-filtro="estado" data-valor="ASIGNADO" onclick="filtrarEstadoViz('ASIGNADO', this)">🔵 Asignados</button>
        <button class="btn-filtro-viz" data-filtro="estado" data-valor="DERIVADO" onclick="filtrarEstadoViz('DERIVADO', this)">🟠 Derivados</button>
        <button class="btn-filtro-viz" data-filtro="estado" data-valor="RESUELTO" onclick="filtrarEstadoViz('RESUELTO', this)">🟢 Resueltos</button>
        <button class="btn-filtro-viz" data-filtro="estado" data-valor="INCONSISTENTE" onclick="filtrarEstadoViz('INCONSISTENTE', this)">🔴 Inconsistentes</button>
        <button class="btn-filtro-viz" data-filtro="estado" data-valor="ANULADO" onclick="filtrarEstadoViz('ANULADO', this)">⚪ Anulados</button>
        <button class="btn-filtro-viz" data-filtro="estado" data-valor="DERIVADO EXTERNO" onclick="filtrarEstadoViz('DERIVADO EXTERNO', this)">🏢 En Pausa Externa</button>
        <button class="btn-filtro-viz" data-filtro="estado" data-valor="RETORNO EXTERNO" onclick="filtrarEstadoViz('RETORNO EXTERNO', this)">🔄 Retorno Externo</button>
    </div>

    <!-- FILTRO POR DEPENDENCIA -->
    <div class="filtro-bar-viz">
        <span>Dependencia:</span>
        <select id="filtroDependencia" class="select-dependencia-viz" onchange="filtrarDependenciaViz(this.value)">
            <option value="todas">🏢 Todas las dependencias</option>
            <?php if (!empty($dependencias)): ?>
                <?php foreach ($dependencias as $dep): ?>
                    <option value="<?= htmlspecialchars($dep['VALOR']) ?>"><?= htmlspecialchars($dep['VALOR']) ?></option>
                <?php endforeach; ?>
            <?php endif; ?>
        </select>
    </div>

    <table class="tabla-datos" id="tablaVisualizador">
        <thead>
            <tr>
                <th>Código</th>
                <th>Fecha Creación</th>
                <th>Dependencia</th>
                <th>Solicitante</th>
                <th>Descripción Breve</th>
                <th>Analista a Cargo</th>
                <th>Fecha Cierre</th>
                <th>Estado</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($tickets)): ?>
                <tr>
                    <td colspan="8" style="text-align:center; padding:30px; color:var(--text-muted);">
                        No se encontraron casos.
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($tickets as $t): ?>
                    <?php
                        $estado = $t['ESTADO_ACTUAL'];
                        $badgeClass = match($estado) {
                            'ASIGNADO'         => 'badge-asignado',
                            'DERIVADO'         => 'badge-derivado',
                            'ANULADO'          => 'badge-anulado',
                            'REACTIVADO'       => 'badge-reactivado',
                            'RESUELTO'         => 'badge-resuelto',
                            'DERIVADO EXTERNO' => 'badge-pausado',
                            'RETORNO EXTERNO'  => 'badge-retorno',
                            default            => 'badge-pendiente'
                        };
                        $desc = $t['DESCRIPCION_REQUERIMIENTO'] ?? '';
                        if (is_object($desc)) $desc = $desc->load();
                        $descBreve = mb_strlen($desc) > 50 ? mb_substr($desc, 0, 50) . '...' : $desc;
                        $dependenciaFila = $t['DEPENDENCIA'] ?? '';
                    ?>
                    <tr data-estado="<?= htmlspecialchars($estado) ?>" data-dependencia="<?= htmlspecialchars($dependenciaFila) ?>">
                        <td>
                            <button type="button" class="link-codigo-viz" onclick="abrirModalViz(this)">
                                <?= htmlspecialchars($t['CODIGO_TICKET']) ?>
                            </button>
                            <input type="hidden" class="dv-codigo" value="<?= htmlspecialchars($t['CODIGO_TICKET']) ?>">
                            <input type="hidden" class="dv-fecha" value="<?= htmlspecialchars($t['FECHA_CREACION']) ?>">
                            <input type="hidden" class="dv-dependencia" value="<?= htmlspecialchars($dependenciaFila) ?>">
                            <input type="hidden" class="dv-solicitante" value="<?= htmlspecialchars($t['NOMBRE_SOLICITANTE']) ?>">
                            <input type="hidden" class="dv-analista" value="<?= htmlspecialchars($t['NOMBRE_ANALISTA'] ?? 'Sin asignar') ?>">
                            <input type="hidden" class="dv-fecha-cierre" value="<?= !empty($t['FECHA_CIERRE']) ? htmlspecialchars($t['FECHA_CIERRE']) : '-' ?>">
                            <input type="hidden" class="dv-desc" value="<?= htmlspecialchars($desc) ?>">
                            <input type="hidden" class="dv-estado" value="<?= htmlspecialchars($estado) ?>">
                            <input type="hidden" class="dv-badge" value="<?= $badgeClass ?>">
                        </td>
                        <td><?= htmlspecialchars($t['FECHA_CREACION']) ?></td>
                        <td><?= htmlspecialchars($dependenciaFila) ?></td>
                        <td><?= htmlspecialchars($t['NOMBRE_SOLICITANTE']) ?></td>
                        <td style="max-width:280px;"><?= htmlspecialchars($descBreve) ?></td>
                        <td><?= htmlspecialchars($t['NOMBRE_ANALISTA'] ?? 'Sin asignar') ?></td>
                        <td><?= !empty($t['FECHA_CIERRE']) ? htmlspecialchars($t['FECHA_CIERRE']) : '-' ?></td>
                        <td><span class="badge <?= $badgeClass ?>"><?= htmlspecialchars($estado) ?></span></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- MODAL DE DETALLE -->
<div id="modalDetalleViz" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.6); justify-content:center; align-items:center; z-index:2000;">
    <div style="background:white; padding:25px; border-radius:8px; width:600px; max-width:90%; box-shadow:0 10px 25px rgba(0,0,0,0.2);">
        <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:2px solid #f1f5f9; padding-bottom:10px; margin-bottom:15px;">
            <h3 style="color:var(--secondary); margin:0;">
                Caso: <span id="dv-codigo-titulo" style="color:var(--primary);"></span>
            </h3>
            <button onclick="cerrarModalViz()" style="background:none; border:none; font-size:24px; cursor:pointer; color:#94a3b8;">&times;</button>
        </div>

        <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px; margin-bottom:15px;">
            <div>
                <strong style="color:var(--text-muted); font-size:12px;">FECHA DE CREACIÓN</strong>
                <div id="dv-fecha-mostrar" style="font-weight:600; color:#334155; margin-top:3px;"></div>
            </div>
            <div>
                <strong style="color:var(--text-muted); font-size:12px;">DEPENDENCIA</strong>
                <div id="dv-dependencia-mostrar" style="font-weight:600; color:#334155; margin-top:3px;"></div>
            </div>
            <div>
                <strong style="color:var(--text-muted); font-size:12px;">SOLICITANTE</strong>
                <div id="dv-solicitante-mostrar" style="font-weight:600; color:#334155; margin-top:3px;"></div>
            </div>
            <div>
                <strong style="color:var(--text-muted); font-size:12px;">ANALISTA A CARGO</strong>
                <div id="dv-analista-mostrar" style="font-weight:600; color:#334155; margin-top:3px;"></div>
            </div>
        </div>

        <div style="margin-bottom:15px;">
            <strong style="color:var(--secondary); font-size:13px;">Descripción del Requerimiento:</strong>
            <div id="dv-desc-mostrar" style="background:#f8fafc; border-left:4px solid var(--primary); padding:12px; border-radius:4px; margin-top:6px; font-size:13px; color:#334155; max-height:180px; overflow-y:auto; white-space:pre-wrap; line-height:1.5;"></div>
        </div>

        <div style="display:flex; justify-content:space-between; align-items:center;">
            <div>
                <strong style="color:var(--text-muted); font-size:12px;">FECHA DE CIERRE</strong>
                <div id="dv-fecha-cierre-mostrar" style="font-weight:600; color:#334155; margin-top:3px;"></div>
            </div>
            <div>
                <span id="dv-badge-mostrar" class="badge"></span>
            </div>
        </div>

        <div style="display:flex; justify-content:flex-end; border-top:1px solid #f1f5f9; padding-top:15px; margin-top:15px;">
            <button onclick="cerrarModalViz()" class="btn" style="background:var(--primary); color:white;">Cerrar</button>
        </div>
    </div>
</div>

<script>
    let filtroEstadoActual = 'todos';
    let filtroDependenciaActual = 'todas';

    function filtrarEstadoViz(estado, boton) {
        filtroEstadoActual = estado;
        document.querySelectorAll('.btn-filtro-viz').forEach(b => b.classList.remove('activo'));
        boton.classList.add('activo');
        aplicarFiltrosCombinadosViz();
    }

    function filtrarDependenciaViz(dependencia) {
        filtroDependenciaActual = dependencia;
        aplicarFiltrosCombinadosViz();
    }

    function aplicarFiltrosCombinadosViz() {
        let visibles = 0;
        document.querySelectorAll('#tablaVisualizador tbody tr[data-estado]').forEach(tr => {
            const coincideEstado = (filtroEstadoActual === 'todos' || tr.dataset.estado === filtroEstadoActual);
            const coincideDependencia = (filtroDependenciaActual === 'todas' || tr.dataset.dependencia === filtroDependenciaActual);
            const mostrar = coincideEstado && coincideDependencia;
            tr.style.display = mostrar ? '' : 'none';
            if (mostrar) visibles++;
        });
        document.getElementById('contadorVisible').innerText = visibles;
    }

    // ── MODAL DE DETALLE ──
    function abrirModalViz(boton) {
        const td = boton.closest('td');
        document.getElementById('dv-codigo-titulo').innerText = td.querySelector('.dv-codigo').value;
        document.getElementById('dv-fecha-mostrar').innerText = td.querySelector('.dv-fecha').value;
        document.getElementById('dv-dependencia-mostrar').innerText = td.querySelector('.dv-dependencia').value;
        document.getElementById('dv-solicitante-mostrar').innerText = td.querySelector('.dv-solicitante').value;
        document.getElementById('dv-analista-mostrar').innerText = td.querySelector('.dv-analista').value;
        document.getElementById('dv-fecha-cierre-mostrar').innerText = td.querySelector('.dv-fecha-cierre').value;
        document.getElementById('dv-desc-mostrar').innerText = td.querySelector('.dv-desc').value;

        const estado = td.querySelector('.dv-estado').value;
        const badgeClass = td.querySelector('.dv-badge').value;
        const badge = document.getElementById('dv-badge-mostrar');
        badge.innerText = estado;
        badge.className = 'badge ' + badgeClass;

        document.getElementById('modalDetalleViz').style.display = 'flex';
    }

    function cerrarModalViz() {
        document.getElementById('modalDetalleViz').style.display = 'none';
    }
</script>

</body>
</html>