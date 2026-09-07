<?php include 'header.php'; ?>

<div class="card-box" style="max-width: 800px; margin: 0 auto;">
    <h2 style="color: var(--secondary); margin-top: 0; border-left: 4px solid #F59E0B; padding-left: 10px;">
        Editar Información Histórica
    </h2>
    <p style="color: var(--text-muted); font-size: 14px; margin-bottom: 20px;">
        Expediente: <strong style="color: var(--primary);"><?= htmlspecialchars($ticket['CODIGO_TICKET']) ?></strong> 
        <span class="badge" style="background: #e2e8f0; color: #475569; margin-left: 10px;"><?= htmlspecialchars($ticket['ESTADO_ACTUAL']) ?></span>
    </p>

    <form action="index.php?accion=guardar_edicion_historico" method="POST">
        <input type="hidden" name="id_requerimiento" value="<?= $ticket['ID_REQUERIMIENTO'] ?>">
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
            
            <!-- LÓGICA DE BLOQUEO INTELIGENTE -->
            <?php if ($ticket['ID_SOLICITANTE'] == $_SESSION['id_usuario']): ?>
                <!-- CASO 1: El analista lo creó, puede editarlo -->
                <div class="form-group">
                    <label for="id_solicitante" style="color: var(--primary); font-weight: bold;">Solicitante (Corregir):</label>
                    <select name="id_solicitante" id="id_solicitante" class="form-control" required style="border-color: var(--primary);">
                        <?php foreach($usuarios as $u): ?>
                            <option value="<?= $u['ID_USUARIO'] ?>" <?= ($u['ID_USUARIO'] == $ticket['ID_SOLICITANTE']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($u['NOMBRE_COMPLETO']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="id_dependencia" style="color: var(--primary); font-weight: bold;">Dependencia (Corregir):</label>
                    <select name="id_dependencia" id="id_dependencia" class="form-control" required style="border-color: var(--primary);">
                        <?php foreach($dependencias as $d): ?>
                            <option value="<?= $d['ID_CATALOGO'] ?>" <?= ($d['ID_CATALOGO'] == $ticket['ID_CATALOGO_DEPENDENCIA']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($d['VALOR']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php else: ?>
                <!-- CASO 2: El usuario lo creó, los datos están blindados -->
                <div class="form-group">
                    <label for="solicitante_display">Solicitante (Bloqueado):</label>
                    <input type="text" id="solicitante_display" class="form-control" value="<?= htmlspecialchars($ticket['NOMBRE_SOLICITANTE']) ?>" readonly style="background: #f1f5f9; color: #64748b; cursor: not-allowed;">
                    <small style="color: #94a3b8; font-size: 11px;">Registrado por el usuario</small>
                </div>
                <div class="form-group">
                    <label for="dependencia_display">Dependencia (Bloqueada):</label>
                    <input type="text" id="dependencia_display" class="form-control" value="<?= htmlspecialchars($ticket['DEPENDENCIA']) ?>" readonly style="background: #f1f5f9; color: #64748b; cursor: not-allowed;">
                </div>
            <?php endif; ?>

        </div>

        <div class="form-group">
            <label for="descripcion">Descripción del Usuario (Requerimiento):</label>
            <textarea name="descripcion" id="descripcion" rows="3" class="form-control" required><?= htmlspecialchars($ticket['DESCRIPCION_REQUERIMIENTO'] ?? '') ?></textarea>
        </div>

        <div class="form-group">
            <label for="observaciones">Notas de Cierre del Analista:</label>
            <textarea name="observaciones" id="observaciones" rows="3" class="form-control" required><?= htmlspecialchars($ticket['OBSERVACIONES_CIERRE'] ?? '') ?></textarea>
        </div>

        <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 25px;">
            <!-- Botón Cancelar actualizado a color rojo (#DC2626) -->
            <a href="index.php?accion=historial" class="btn" style="background: #DC2626; color: white; text-decoration: none;">Cancelar</a>
            <button type="submit" class="btn" style="background: #F59E0B; color: white; font-weight: 600;">Guardar Cambios</button>
        </div>
    </form>
</div>

</body>
</html>