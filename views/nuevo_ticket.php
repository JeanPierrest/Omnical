<?php include 'header.php'; ?>

    <style>
        .formulario-caja { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); max-width: 500px; margin: 20px auto; border-top: 4px solid #0064AF; }
        .form-group { margin-bottom: 20px; }
        label { display: block; font-weight: bold; color: #0064AF; margin-bottom: 8px; font-size: 13px; }
        select, textarea { width: 100%; padding: 10px; border: 1px solid #CBD5E1; border-radius: 4px; box-sizing: border-box; font-family: 'Segoe UI', Tahoma, sans-serif; }
        .btn-enviar { background-color: #0064AF; color: white; border: none; padding: 12px 15px; border-radius: 4px; cursor: pointer; font-weight: bold; width: 100%; font-size: 14px; transition: 0.2s;}
        .btn-enviar:hover { background-color: #004D86; }

        /* ── ALERTA DE POSIBLES DUPLICADOS ── */
        #alertaSimilares {
            display: none;
            background: #FFFBEB;
            border: 1px solid #FDE68A;
            border-left: 4px solid #F59E0B;
            border-radius: 6px;
            padding: 14px 16px;
            margin-bottom: 20px;
            font-size: 13px;
        }
        #alertaSimilares h4 {
            margin: 0 0 8px 0;
            color: #92400E;
            font-size: 13px;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .similar-item {
            background: white;
            border: 1px solid #FDE68A;
            border-radius: 4px;
            padding: 10px 12px;
            margin-bottom: 8px;
        }
        .similar-item:last-child { margin-bottom: 0; }
        .similar-item .sim-codigo { font-weight: 700; color: #0064AF; font-size: 13px; }
        .similar-item .sim-meta { color: #92400E; font-size: 11px; margin-top: 2px; }
        .similar-item .sim-desc { color: #475569; font-size: 12px; margin-top: 4px; line-height: 1.4; }
        #alertaSimilares .aviso-continuar {
            font-size: 11px;
            color: #92400E;
            margin-top: 10px;
            font-style: italic;
        }
        #spinnerBuscando {
            display: none;
            font-size: 12px;
            color: var(--text-muted);
            margin-top: 6px;
        }
        .contador-caracteres {
            font-size: 11px;
            color: #64748b;
            text-align: right;
            margin-top: 4px;
        }
        .contador-caracteres.limite-cerca { color: #D97706; font-weight: 600; }
        .contador-caracteres.limite-superado { color: #DC2626; font-weight: 700; }

        .archivo-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #F1F5F9;
            border: 1px solid #CBD5E1;
            border-radius: 4px;
            padding: 6px 10px;
            margin-top: 6px;
            font-size: 12px;
            color: #334155;
        }
        .archivo-item .btn-quitar-archivo {
            background: #DC2626;
            color: white;
            border: none;
            border-radius: 3px;
            width: 20px;
            height: 20px;
            cursor: pointer;
            font-size: 12px;
            line-height: 1;
            flex-shrink: 0;
        }
        .btn-limpiar-archivos {
            background: #64748B;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 11px;
            cursor: pointer;
            margin-top: 8px;
        }
    </style>

    <div class="formulario-caja">
        <h2 style="color: #0064AF; margin-top: 0; margin-bottom: 20px;">Registrar Nuevo Requerimiento</h2>

        <!-- ALERTA DE CASOS SIMILARES -->
        <div id="alertaSimilares">
            <h4>⚠️ Encontramos casos similares recientes</h4>
            <div id="listaSimilares"></div>
            <div class="aviso-continuar">
                Si tu caso es diferente, puedes continuar y enviarlo de todas formas.
            </div>
        </div>

        <form action="index.php?accion=guardar" method="POST" enctype="multipart/form-data" id="formNuevoTicket" onsubmit="return prepararEnvio(this)">

            <!-- Campos ocultos para registrar si el usuario ignoró la alerta -->
            <input type="hidden" name="id_posible_duplicado" id="inputIdDuplicado" value="">
            <input type="hidden" name="ignoro_alerta_duplicado" id="inputIgnoroAlerta" value="0">

            <div class="form-group">
                <label>Dependencia de Origen:</label>
                <select name="id_dependencia" id="selectDependencia" required onchange="dispararBusqueda()">
                    <option value="">-- Seleccione una dependencia --</option>
                    <?php foreach ($dependencias as $dep): ?>
                        <option value="<?= htmlspecialchars($dep['ID_CATALOGO']) ?>">
                            <?= htmlspecialchars($dep['VALOR']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Tipo de Documento:</label>
                <select name="tipo_documento" id="selectTipoDocumento" required onchange="actualizarLimiteDocumento()">
                    <option value="">-- Seleccione el tipo de documento --</option>
                    <option value="DNI" data-max="8">DNI</option>
                    <option value="CE" data-max="9">Carnet de Extranjería</option>
                    <option value="RUC" data-max="11">RUC (persona jurídica)</option>
                    <option value="OTRO" data-max="15">Otros</option>
                </select>
                <div id="bloqueTipoDocOtro" style="display:none; margin-top:10px;">
                    <input type="text" name="tipo_documento_otro_texto" id="inputTipoDocOtro" maxlength="50"
                           placeholder="Especifique el tipo de documento (ej: Pasaporte, Partida de Nacimiento)"
                           style="width:100%; padding:10px; border:1px solid #F59E0B; border-radius:4px; box-sizing:border-box;">
                </div>
            </div>

            <div class="form-group">
                <label>Número de Documento:</label>
                <input type="text" name="numero_documento" id="inputNumeroDocumento" required
                       maxlength="15" placeholder="Seleccione primero el tipo de documento" disabled
                       autocomplete="off"
                       style="width:100%; padding:10px; border:1px solid #CBD5E1; border-radius:4px; box-sizing:border-box;">
                <small id="avisoLimiteDocumento" style="color:#64748b; font-size:11px; display:block; margin-top:4px;"></small>
            </div>

            <div class="form-group">
                <label>Tipo de Seguro:</label>
                <select name="id_seguro" id="selectSeguro" required onchange="toggleSeguroOtro(); dispararBusqueda();">
                    <option value="">-- Seleccione el tipo de seguro --</option>
                    <?php if(!empty($seguros)): ?>
                        <?php foreach ($seguros as $seguro): ?>
                            <option value="<?= htmlspecialchars($seguro['ID_SEGURO']) ?>"
                                    data-nombre="<?= htmlspecialchars($seguro['NOMBRE_SEGURO']) ?>">
                                <?= htmlspecialchars($seguro['NOMBRE_SEGURO']) ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    <option value="OTRO">Otros</option>
                </select>
                <div id="bloqueSeguroOtro" style="display:none; margin-top:10px;">
                    <input type="text" name="seguro_otro_texto" id="inputSeguroOtro" maxlength="150"
                           placeholder="Especifique el tipo de seguro"
                           style="width:100%; padding:10px; border:1px solid #F59E0B; border-radius:4px; box-sizing:border-box;">
                </div>
            </div>

            <!-- NUEVO CAMPO: TIPO DE CONSULTA (autocompletar) -->
            <div class="form-group" style="position:relative;">
                <label>Tipo de Consulta:</label>
                <input type="text" id="inputTipoConsultaTexto" autocomplete="off"
                       placeholder="Escribe para buscar (ej: SAS, cronograma, vigencia...)"
                       style="width:100%; padding:10px; border:1px solid #CBD5E1; border-radius:4px; box-sizing:border-box;"
                       onkeyup="filtrarTipoConsulta()" onfocus="filtrarTipoConsulta()">
                <input type="hidden" name="id_tipo_consulta" id="selectTipoConsulta" required>
                <div id="listaTipoConsulta"
                     style="display:none; position:absolute; z-index:50; background:white; border:1px solid #CBD5E1; border-top:none; border-radius:0 0 4px 4px; max-height:220px; overflow-y:auto; width:100%; box-shadow:0 4px 8px rgba(0,0,0,0.08);">
                </div>
                <small id="avisoTipoConsulta" style="display:none; color:#DC2626; font-size:11px; margin-top:4px;">
                    Selecciona una opción de la lista.
                </small>
                <div id="bloqueConsultaOtro" style="display:none; margin-top:10px;">
                    <input type="text" name="consulta_otro_texto" id="inputConsultaOtro" maxlength="200"
                           placeholder="Especifique el tipo de consulta"
                           style="width:100%; padding:10px; border:1px solid #F59E0B; border-radius:4px; box-sizing:border-box;">
                </div>
            </div>

            <div class="form-group">
                <label>Descripción del Problema:</label>
                <textarea name="descripcion" id="textareaDescripcion" rows="5" required
                          maxlength="1500"
                          placeholder="Detalle su requerimiento aquí..."
                          oninput="dispararBusqueda(); actualizarContadorDescripcion();"></textarea>
                <div id="contadorDescripcion" class="contador-caracteres">0 / 1500 caracteres</div>
                <div id="spinnerBuscando">🔍 Buscando casos similares...</div>
            </div>

            <div class="form-group">
                <label>Evidencia Adjunta (Máximo 4 archivos - PDF, JPG, PNG, DOC):</label>
                <input type="file" name="evidencias[]" id="inputArchivos" multiple accept=".pdf, .jpg, .jpeg, .png, .doc, .docx" style="padding: 8px; border: 1px dashed #CBD5E1; background: #f8fafc; width: 100%;">
                <small style="color: #64748b; font-size: 11px; display: block; margin-top: 5px;">Mantén presionada la tecla CTRL para seleccionar varios archivos al mismo tiempo.</small>
                <div id="listaArchivosSeleccionados"></div>
            </div>

            <button type="submit" id="btnCrearTicket" class="btn-enviar">Generar Ticket</button>
        </form>
    </div>

    <!-- ============================================================== -->
    <!-- MODAL DE ÉXITO PANTALLA COMPLETA (SIN LIBRERÍAS)               -->
    <!-- ============================================================== -->
    <?php if (isset($_GET['exito']) && $_GET['exito'] == 1): ?>
        <div id="modalExitoBloqueante" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 9999; display: flex; align-items: center; justify-content: center; backdrop-filter: blur(3px);">
            <div style="background: white; padding: 40px 50px; border-radius: 12px; text-align: center; box-shadow: 0 20px 40px rgba(0,0,0,0.4); max-width: 450px; width: 90%; animation: aparecer 0.3s ease-out;">
                <div style="font-size: 70px; margin-bottom: 10px; line-height: 1;">✅</div>
                <h2 style="color: #047857; margin-top: 0; margin-bottom: 15px; font-size: 24px;">¡Ticket Creado con Éxito!</h2>
                <p style="color: #475569; font-size: 15px; margin-bottom: 30px; line-height: 1.5;">
                    Tu requerimiento ha sido registrado correctamente. Un analista revisará tu caso pronto.
                </p>
                <a href="index.php?accion=mis_tickets" style="display:inline-block; background:#0064AF; color:white; padding:12px 30px; border-radius:6px; text-decoration:none; font-weight:bold;">
                    Ver Mis Tickets
                </a>
            </div>
        </div>
        <style>
            @keyframes aparecer { from { opacity: 0; transform: scale(0.9); } to { opacity: 1; transform: scale(1); } }
        </style>
    <?php endif; ?>

    <script>
        // ── DATOS PARA EL AUTOCOMPLETAR DE TIPO DE CONSULTA ──
        const tiposConsultaData = [
            <?php if (!empty($tiposConsulta)): ?>
                <?php foreach ($tiposConsulta as $tc): ?>
                    { id: <?= (int)$tc['ID_CATALOGO'] ?>, texto: <?= json_encode($tc['VALOR'], JSON_UNESCAPED_UNICODE) ?> },
                <?php endforeach; ?>
            <?php endif; ?>
        ];

        // ── PUNTO 4: LÍMITE DINÁMICO DE CARACTERES SEGÚN TIPO DE DOCUMENTO ──
        function actualizarLimiteDocumento() {
            const select = document.getElementById('selectTipoDocumento');
            const input = document.getElementById('inputNumeroDocumento');
            const aviso = document.getElementById('avisoLimiteDocumento');
            const opcionSeleccionada = select.options[select.selectedIndex];
            const bloqueOtro = document.getElementById('bloqueTipoDocOtro');
            const inputOtro = document.getElementById('inputTipoDocOtro');

            if (select.value === 'OTRO') {
                bloqueOtro.style.display = 'block';
                inputOtro.required = true;
            } else {
                bloqueOtro.style.display = 'none';
                inputOtro.required = false;
                inputOtro.value = '';
            }

            if (!select.value) {
                input.disabled = true;
                input.value = '';
                input.placeholder = 'Seleccione primero el tipo de documento';
                aviso.innerText = '';
                return;
            }

            const maxLen = opcionSeleccionada.getAttribute('data-max') || '15';
            input.disabled = false;
            input.maxLength = maxLen;
            input.value = '';

            if (select.value === 'DNI') {
                input.placeholder = 'Ingrese los 8 dígitos del DNI';
                aviso.innerText = 'El DNI debe tener exactamente 8 dígitos.';
                input.setAttribute('pattern', '\\d{8}');
                input.setAttribute('inputmode', 'numeric');
            } else if (select.value === 'CE') {
                input.placeholder = 'Ingrese los 9 caracteres del Carnet de Extranjería';
                aviso.innerText = 'El Carnet de Extranjería debe tener 9 caracteres.';
                input.removeAttribute('pattern');
                input.removeAttribute('inputmode');
            } else if (select.value === 'RUC') {
                input.placeholder = 'Ingrese los 11 dígitos del RUC';
                aviso.innerText = 'El RUC debe tener exactamente 11 dígitos.';
                input.setAttribute('pattern', '\\d{11}');
                input.setAttribute('inputmode', 'numeric');
            } else {
                input.placeholder = 'Ingrese el número de documento';
                aviso.innerText = 'Máximo 15 caracteres.';
                input.removeAttribute('pattern');
                input.removeAttribute('inputmode');
            }
        }

        // ── PUNTO 5: MOSTRAR TEXTBOX "OTROS" EN TIPO DE SEGURO ──
        function toggleSeguroOtro() {
            const select = document.getElementById('selectSeguro');
            const bloque = document.getElementById('bloqueSeguroOtro');
            const inputOtro = document.getElementById('inputSeguroOtro');
            if (select.value === 'OTRO') {
                bloque.style.display = 'block';
                inputOtro.required = true;
            } else {
                bloque.style.display = 'none';
                inputOtro.required = false;
                inputOtro.value = '';
            }
        }

        // ── PUNTO 7: CONTADOR DE CARACTERES EN DESCRIPCIÓN ──
        function actualizarContadorDescripcion() {
            const textarea = document.getElementById('textareaDescripcion');
            const contador = document.getElementById('contadorDescripcion');
            const longitud = textarea.value.length;
            const limite = 1500;

            contador.innerText = longitud + ' / ' + limite + ' caracteres';
            contador.classList.remove('limite-cerca', 'limite-superado');
            if (longitud >= limite) {
                contador.classList.add('limite-superado');
            } else if (longitud >= limite * 0.9) {
                contador.classList.add('limite-cerca');
            }
        }

        // ── PUNTO 8: GESTIÓN DE ARCHIVOS SELECCIONADOS (QUITAR INDIVIDUAL / LIMPIAR TODO) ──
        let dtArchivos = new DataTransfer(); // buffer manejable de archivos

        document.getElementById('inputArchivos').addEventListener('change', function () {
            // Agregamos los archivos recién elegidos al buffer (sin perder los ya seleccionados)
            for (const file of this.files) {
                if (dtArchivos.items.length >= 4) break;
                dtArchivos.items.add(file);
            }
            if (dtArchivos.items.length > 4) {
                alert('Has superado el límite. Solo puedes adjuntar un máximo de 4 archivos por caso.');
            }
            // Recortamos a 4 si se pasó
            while (dtArchivos.items.length > 4) {
                dtArchivos.items.remove(dtArchivos.items.length - 1);
            }
            this.files = dtArchivos.files;
            renderizarListaArchivos();
        });

        function renderizarListaArchivos() {
            const contenedor = document.getElementById('listaArchivosSeleccionados');
            contenedor.innerHTML = '';

            if (dtArchivos.items.length === 0) return;

            for (let i = 0; i < dtArchivos.files.length; i++) {
                const file = dtArchivos.files[i];
                const div = document.createElement('div');
                div.className = 'archivo-item';
                div.innerHTML = `
                    <span>📎 ${file.name}</span>
                    <button type="button" class="btn-quitar-archivo" onclick="quitarArchivo(${i})">✕</button>
                `;
                contenedor.appendChild(div);
            }

            const btnLimpiar = document.createElement('button');
            btnLimpiar.type = 'button';
            btnLimpiar.className = 'btn-limpiar-archivos';
            btnLimpiar.innerText = '🗑️ Quitar todos';
            btnLimpiar.onclick = limpiarTodosArchivos;
            contenedor.appendChild(btnLimpiar);
        }

        function quitarArchivo(indice) {
            const nuevoDt = new DataTransfer();
            for (let i = 0; i < dtArchivos.files.length; i++) {
                if (i !== indice) nuevoDt.items.add(dtArchivos.files[i]);
            }
            dtArchivos = nuevoDt;
            document.getElementById('inputArchivos').files = dtArchivos.files;
            renderizarListaArchivos();
        }

        function limpiarTodosArchivos() {
            dtArchivos = new DataTransfer();
            document.getElementById('inputArchivos').files = dtArchivos.files;
            renderizarListaArchivos();
        }

        function filtrarTipoConsulta() {
            const input = document.getElementById('inputTipoConsultaTexto');
            const lista = document.getElementById('listaTipoConsulta');
            const texto = input.value.trim().toLowerCase();

            document.getElementById('avisoTipoConsulta').style.display = 'none';

            let resultados = tiposConsultaData;
            if (texto.length > 0) {
                resultados = tiposConsultaData.filter(t => t.texto.toLowerCase().includes(texto));
            }
            resultados = resultados.slice(0, 30);

            if (resultados.length === 0) {
                lista.innerHTML = '<div style="padding:10px; font-size:13px; color:#94A3B8;">Sin coincidencias.</div>';
            } else {
                lista.innerHTML = resultados.map(t => `
                    <div class="opcion-tipo-consulta"
                         data-id="${t.id}"
                         data-texto="${t.texto.replace(/"/g, '&quot;')}"
                         onclick="seleccionarTipoConsulta(this)"
                         style="padding:9px 12px; font-size:13px; color:#334155; cursor:pointer; border-bottom:1px solid #F1F5F9;"
                         onmouseover="this.style.background='#F1F5F9'" onmouseout="this.style.background='white'">
                        ${t.texto}
                    </div>
                `).join('');
            }

            // Opción fija "Otros" siempre visible al final de la lista
            const optOtro = document.createElement('div');
            optOtro.setAttribute('data-id', 'OTRO');
            optOtro.setAttribute('data-texto', 'Otros');
            optOtro.style.cssText = 'padding:9px 12px; font-size:13px; color:#D97706; font-weight:700; cursor:pointer; border-top:2px solid #F1F5F9;';
            optOtro.innerText = '+ Otros (especificar)';
            optOtro.onclick = function () { seleccionarTipoConsulta(this); };
            lista.appendChild(optOtro);

            lista.style.display = 'block';
        }

        function seleccionarTipoConsulta(elemento) {
            const id = elemento.getAttribute('data-id');
            const texto = elemento.getAttribute('data-texto');
            document.getElementById('inputTipoConsultaTexto').value = texto;
            document.getElementById('selectTipoConsulta').value = id;
            document.getElementById('listaTipoConsulta').style.display = 'none';

            const bloqueOtro = document.getElementById('bloqueConsultaOtro');
            const inputOtro = document.getElementById('inputConsultaOtro');
            if (id === 'OTRO') {
                bloqueOtro.style.display = 'block';
                inputOtro.required = true;
            } else {
                bloqueOtro.style.display = 'none';
                inputOtro.required = false;
                inputOtro.value = '';
            }

            dispararBusqueda();
        }

        document.addEventListener('click', function (e) {
            const contenedor = document.getElementById('inputTipoConsultaTexto').closest('.form-group');
            if (!contenedor.contains(e.target)) {
                document.getElementById('listaTipoConsulta').style.display = 'none';
            }
        });

        document.getElementById('inputTipoConsultaTexto').addEventListener('blur', function () {
            setTimeout(() => {
                const textoActual = this.value.trim();
                const idSeleccionado = document.getElementById('selectTipoConsulta').value;
                if (idSeleccionado === 'OTRO') return; // "Otros" es válido aunque el texto no coincida con el catálogo
                const coincide = tiposConsultaData.some(t => t.id == idSeleccionado && t.texto === textoActual);
                if (textoActual !== '' && !coincide) {
                    document.getElementById('avisoTipoConsulta').style.display = 'block';
                    document.getElementById('selectTipoConsulta').value = '';
                }
            }, 200);
        });

        // ── BÚSQUEDA DE CASOS SIMILARES (con debounce) ──
        let timeoutBusqueda = null;
        let hayAlertaVisible = false;

        function dispararBusqueda() {
            clearTimeout(timeoutBusqueda);
            timeoutBusqueda = setTimeout(buscarSimilares, 800);
        }

        function buscarSimilares() {
            const idDependencia = document.getElementById('selectDependencia').value;
            const idSeguro      = document.getElementById('selectSeguro').value;
            const descripcion   = document.getElementById('textareaDescripcion').value.trim();

            if (!idDependencia || descripcion.length < 15) {
                ocultarAlerta();
                return;
            }

            document.getElementById('spinnerBuscando').style.display = 'block';

            const formData = new FormData();
            formData.append('id_dependencia', idDependencia);
            formData.append('id_seguro', idSeguro);
            formData.append('descripcion', descripcion);

            fetch('index.php?accion=buscar_similares_ajax', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                document.getElementById('spinnerBuscando').style.display = 'none';
                if (data.similares && data.similares.length > 0) {
                    mostrarAlerta(data.similares);
                } else {
                    ocultarAlerta();
                }
            })
            .catch(() => {
                document.getElementById('spinnerBuscando').style.display = 'none';
            });
        }

        function mostrarAlerta(similares) {
            const lista = document.getElementById('listaSimilares');
            lista.innerHTML = '';

            similares.forEach(s => {
                const desc = s.DESCRIPCION_REQUERIMIENTO || '';
                const descCorta = desc.length > 80 ? desc.substring(0, 80) + '...' : desc;

                const div = document.createElement('div');
                div.className = 'similar-item';
                div.innerHTML = `
                    <div class="sim-codigo">${s.CODIGO_TICKET}</div>
                    <div class="sim-meta">Creado el ${s.FECHA_CREACION} por ${s.NOMBRE_SOLICITANTE}</div>
                    <div class="sim-desc">"${descCorta}"</div>
                `;
                lista.appendChild(div);
            });

            document.getElementById('inputIdDuplicado').value = similares[0].ID_REQUERIMIENTO;

            document.getElementById('alertaSimilares').style.display = 'block';
            hayAlertaVisible = true;
        }

        function ocultarAlerta() {
            document.getElementById('alertaSimilares').style.display = 'none';
            document.getElementById('inputIdDuplicado').value = '';
            hayAlertaVisible = false;
        }

        // ── ENVÍO DEL FORMULARIO ──
        function prepararEnvio(formulario) {
            if (!document.getElementById('selectTipoConsulta').value) {
                alert('Debes seleccionar un Tipo de Consulta válido de la lista.');
                document.getElementById('inputTipoConsultaTexto').focus();
                return false;
            }

            const tipoDoc = document.getElementById('selectTipoDocumento').value;
            const numDoc = document.getElementById('inputNumeroDocumento').value.trim();
            if (tipoDoc === 'DNI' && numDoc.length !== 8) {
                alert('El DNI debe tener exactamente 8 dígitos.');
                return false;
            }
            if (tipoDoc === 'CE' && numDoc.length !== 9) {
                alert('El Carnet de Extranjería debe tener 9 caracteres.');
                return false;
            }
            if (tipoDoc === 'RUC' && numDoc.length !== 11) {
                alert('El RUC debe tener exactamente 11 dígitos.');
                return false;
            }

            if (hayAlertaVisible) {
                document.getElementById('inputIgnoroAlerta').value = '1';
            }

            const btn = document.getElementById('btnCrearTicket');
            btn.innerText = 'Enviando...';
            btn.style.opacity = '0.7';
            btn.style.pointerEvents = 'none';
            setTimeout(() => { btn.disabled = true; }, 10);

            return true;
        }
    </script>

</div>
</body>
</html>