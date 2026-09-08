<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Si no hay sesión activa, expulsar al login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
?>
<?php include 'layout/header.php'; ?>

<style>
    /* --- ESTILOS DE TABS --- */
    .tabs-container {
        display: flex;
        border-bottom: 1px solid var(--brand-border);
        margin-bottom: 20px;
    }
    .tab-btn {
        background: none;
        border: none;
        color: var(--text-muted);
        padding: 12px 24px;
        font-size: 1rem;
        cursor: pointer;
        border-bottom: 3px solid transparent;
        transition: all 0.2s;
    }
    .tab-btn:hover { color: var(--text-main); }
    .tab-btn.active {
        color: #8b5cf6; /* Morado Lucky */
        border-bottom-color: #8b5cf6;
        font-weight: bold;
    }

    /* --- CONTENEDORES DE VISTAS --- */
    .vista-seccion { display: none; animation: fadeIn 0.3s ease; }
    .vista-seccion.active { display: block; }
    @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }

    input[type="date"].search-bar::-webkit-calendar-picker-indicator {
        filter: invert(1);
        opacity: 1;
        cursor: pointer;
    }

    /* --- ESTILOS GALERÍA (Auditoría) --- */
    .gallery-grid {
        display: grid; grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
        gap: 20px; margin-top: 20px;
    }
    .evidencia-card {
        background-color: var(--bg-panel); border: 1px solid var(--brand-border);
        border-radius: 12px; overflow: hidden; display: flex; flex-direction: column;
    }
    .card-header { padding: 15px; border-bottom: 1px solid var(--brand-border); display: flex; justify-content: space-between; }
    .img-container { display: flex; width: 100%; height: 200px; background-color: var(--bg-dark); }
    .img-box { flex: 1; position: relative; border-right: 1px solid var(--brand-border); cursor: pointer; overflow: hidden; }
    .img-box:last-child { border-right: none; }
    .img-box img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.3s; }
    .img-box:hover img { transform: scale(1.05); }
    .img-label { position: absolute; bottom: 5px; right: 5px; background: rgba(0,0,0,0.7); color: #fff; padding: 2px 8px; border-radius: 4px; font-size: 0.75rem; }
    .card-body { padding: 15px; flex: 1; }
    .btn-validar {
        width: 100%; padding: 10px; border: none; background-color: var(--bg-dark);
        color: var(--text-muted); border-top: 1px solid var(--brand-border);
        cursor: pointer; font-weight: bold; transition: all 0.2s;
    }
    .btn-validar:hover { background-color: var(--bg-hover); color: var(--text-main); }
    .btn-validar.aprobado { background-color: rgba(16,185,129,0.1); color: #10b981; }

    /* Modal Imagen */
    .img-modal {
        position: fixed; top: 0; left: 0; width: 100%; height: 100%;
        background: rgba(0,0,0,0.9); display: none; align-items: center; justify-content: center; z-index: 2000;
    }
    .img-modal.active { display: flex; }
    .img-modal img { max-width: 90%; max-height: 90%; border-radius: 8px; border: 1px solid #333; }
</style>

<!-- Cabecera y Filtros -->
<div class="kpi-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
    <div>
        <h2 style="color: var(--text-main); margin-bottom: 5px;">KPI 2 - Ejecución (Planimetría)</h2>
    </div>
    
    <!-- NUEVOS FILTROS DE FECHA RANGO -->
    <div style="display: flex; gap: 10px; align-items: center;">
        <span style="color: var(--text-muted); font-size: 13px;">Desde:</span>
        <input type="date" id="filtro-desde" class="search-bar" style="width: auto;">
        
        <span style="color: var(--text-muted); font-size: 13px;">Hasta:</span>
        <input type="date" id="filtro-hasta" class="search-bar" style="width: auto;">

        <select id="filtro-mercaderista" class="search-bar" style="width: auto; margin-left: 10px;">
            <option value="all">Todos los Mercaderistas</option>
        </select>

        <button class="btn-action btn-refresh" onclick="comboBoxLleno = false; cargarEvidencias()" style="background: rgba(0, 255, 135, 0.1); color: #00FF87; border: 1px solid rgba(0, 255, 135, 0.3); padding: 8px 15px; border-radius: 6px; cursor: pointer;">
            ↻ Actualizar
        </button>
    </div>
</div>

<!-- TABS (Pestañas de Navegación) -->
<div class="tabs-container">
    <button id="tab-cliente" class="tab-btn active" onclick="cambiarVista('cliente')">📊 Vista Cliente (Resumen)</button>
    
    <?php if (isset($_SESSION['user_rol']) && $_SESSION['user_rol'] === 'CALIFICADOR'): ?>
        <button id="tab-auditoria" class="tab-btn" onclick="cambiarVista('auditoria')">📸 Vista Auditoría (Validación)</button>
    <?php endif; ?>
</div>

<!-- ==========================================
     VISTA 1: CLIENTE (Resumen Ejecutivo)
=========================================== -->
<div id="vista-cliente" class="vista-seccion active">
    
    <!-- TARJETA: Sumatoria de Locales Correctos -->
    <div style="display: flex; gap: 20px; margin-bottom: 20px;">
        <div style="background: var(--bg-panel); border: 1px solid var(--brand-border); border-radius: 12px; padding: 20px; flex: 1; border-left: 4px solid #10b981;">
            <h3 style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 10px;">🏪 Locales con Planimetría Correcta</h3>
            <div id="kpi-card-correctas" style="font-size: 2rem; font-weight: bold; color: #10b981;">0</div>
        </div>
    </div>

    <div style="margin-bottom: 15px; color: var(--text-muted); font-size: 0.9rem;">
        Resumen de cumplimiento de planimetrías por equipo de campo en el rango de fechas seleccionado.
    </div>
    <div style="overflow-x: auto; margin-bottom: 30px;">
        <table style="width: 100%; text-align: left; border-collapse: collapse;">
            <thead>
                <tr style="color: var(--text-muted); border-bottom: 1px solid var(--brand-border); background-color: rgba(255,255,255,0.02);">
                    <th style="padding: 12px 10px;">Mercaderista</th>
                    <th style="padding: 12px 10px;">PDVs Visitados</th>
                    <th style="padding: 12px 10px;">Total Planimetrías (Evidencias)</th>
                </tr>
            </thead>
            <tbody id="tabla-cliente-body">
                <!-- Se llena por JS -->
            </tbody>
        </table>
    </div>

    <!-- GALERÍA VISTA CLIENTE (Solo aprobadas) -->
    <h3 style="color: var(--text-main); margin-bottom: 15px;">📸 Evidencias Calificadas</h3>
    <div id="galeria-cliente" class="gallery-grid">
        <!-- Se llena por JS -->
    </div>
</div>

<?php if (isset($_SESSION['user_rol']) && $_SESSION['user_rol'] === 'CALIFICADOR'): ?>
<!-- ==========================================
     VISTA 2: AUDITORÍA (Galería de Fotos)
=========================================== -->
<div id="vista-auditoria" class="vista-seccion">
    <div style="margin-bottom: 10px;">
        <span style="color: var(--text-muted); font-size: 0.9rem;">Total de evidencias a revisar en este rango: <strong id="txt-total" style="color: var(--text-main);">0</strong></span>
    </div>
    <div id="galeria-evidencias" class="gallery-grid">
        <!-- Se llena por JS -->
    </div>
</div>
<?php endif; ?>

<!-- Modal para ver imagen en grande -->
<div id="visor-img" class="img-modal" onclick="cerrarVisor()">
    <img id="img-full" src="" alt="Evidencia">
</div>

<script>
let dataCliente = []; 
let dataAuditoria = []; 

document.addEventListener('DOMContentLoaded', () => {
    // --- LÓGICA DE FECHAS (Bloqueo y Rango) ---
    const hoy = new Date();
    const yyyy = hoy.getFullYear();
    const mm = String(hoy.getMonth() + 1).padStart(2, '0');
    const dd = String(hoy.getDate()).padStart(2, '0');
    const fechaHoy = `${yyyy}-${mm}-${dd}`;
    const fechaPrimerDia = `${yyyy}-${mm}-01`;

    const inputDesde = document.getElementById('filtro-desde');
    const inputHasta = document.getElementById('filtro-hasta');

    // Bloqueamos el futuro en el calendario
    inputDesde.max = fechaHoy;
    inputHasta.max = fechaHoy;
    
    // Rango por defecto del inicio de mes hasta hoy
    inputDesde.value = fechaPrimerDia;
    inputHasta.value = fechaHoy;

    const selectMercaderista = document.getElementById('filtro-mercaderista');
    let comboBoxLleno = false;

    window.cambiarVista = (vista) => {
        document.getElementById('tab-cliente').classList.remove('active');
        if (document.getElementById('tab-auditoria')) document.getElementById('tab-auditoria').classList.remove('active');
        if (document.getElementById(`tab-${vista}`)) document.getElementById(`tab-${vista}`).classList.add('active');

        document.getElementById('vista-cliente').classList.remove('active');
        if (document.getElementById('vista-auditoria')) document.getElementById('vista-auditoria').classList.remove('active');
        if (document.getElementById(`vista-${vista}`)) document.getElementById(`vista-${vista}`).classList.add('active');
    };

    // const cargarEvidencias = async () => {
    const cargarEvidencias = async () => {
        let desde = inputDesde.value;
        let hasta = inputHasta.value;
        const mercaderista = selectMercaderista.value;
        
        // VALIDACIÓN: "Desde" no puede ser mayor que "Hasta"
        if (desde > hasta) {
            // alert("La fecha 'Desde' no puede ser mayor a la fecha 'Hasta'. Se ajustará automáticamente.");
            hasta = desde; 
            inputHasta.value = hasta;
        }

        document.getElementById('tabla-cliente-body').innerHTML = '<tr><td colspan="3" style="padding:20px; text-align:center;">Cargando...</td></tr>';
        const galeria = document.getElementById('galeria-evidencias');
        if (galeria) galeria.innerHTML = '<p style="color: var(--text-muted);">Cargando evidencias...</p>';

        // Enviamos el rango de fechas al backend
        let url = `api/get_kpi2_ejecucion.php?desde=${desde}&hasta=${hasta}`;
        if (mercaderista !== 'all') url += `&mercaderista=${encodeURIComponent(mercaderista)}`;

        try {
            const response = await fetch(url);
            const res = await response.json();
            
            if (res.status === 'success') {
                dataCliente = res.evidencias_cliente;
                dataAuditoria = res.evidencias_auditoria;
                
                const txtTotal = document.getElementById('txt-total');
                if (txtTotal) txtTotal.textContent = dataAuditoria.length;
                
                pintarVistaCliente(dataCliente);
                pintarVistaAuditoria(dataAuditoria);
                
                if (!comboBoxLleno || mercaderista === 'all') {
                    llenarComboBox(res.usuarios_activos, mercaderista);
                    comboBoxLleno = true;
                }
            }
        } catch (error) {
            console.error("Error:", error);
        }
    };

    window.cargarEvidencias = cargarEvidencias;
    window.comboBoxLleno = false;

    const llenarComboBox = (usuarios, seleccionado) => {
        let opciones = `<option value="all">Todos los Mercaderistas</option>`;
        usuarios.forEach(usr => {
            const sel = (usr === seleccionado) ? 'selected' : '';
            opciones += `<option value="${usr}" ${sel}>${usr}</option>`;
        });
        selectMercaderista.innerHTML = opciones;
    };

   const pintarVistaCliente = (evidencias) => {
        const tbody = document.getElementById('tabla-cliente-body');
        const galeriaCliente = document.getElementById('galeria-cliente');
        

        document.querySelector('#vista-cliente thead').innerHTML = `
            <tr style="color: var(--text-muted); border-bottom: 1px solid var(--brand-border); background-color: rgba(255,255,255,0.02);">
                <th style="padding: 12px 10px;">Mercaderista</th>
                <th style="padding: 12px 10px;">Locales Totales</th>
                <th style="padding: 12px 10px;">Ejecuciones (Validadas / Totales)</th>
                <th style="padding: 12px 10px; text-align: center;">Puntaje</th>
            </tr>
        `;
        
        tbody.innerHTML = '';
        galeriaCliente.innerHTML = '';

        if(evidencias.length === 0) {
            tbody.innerHTML = '<tr><td colspan="3" style="padding:20px; text-align:center; color: var(--text-muted);">Sin datos en este rango de fechas.</td></tr>';
            document.getElementById('kpi-card-correctas').textContent = '0';
            return;
        }

        const resumen = {};
        let localesCorrectos = new Set(); 


        evidencias.forEach(ev => {
            if(!resumen[ev.mercaderista]) {
                resumen[ev.mercaderista] = { 
                    pdvs: new Set(), 
                    total: 0, 
                    validadas: 0,
                    max_kpi2: ev.max_kpi2 || 300  
                };
            }
            resumen[ev.mercaderista].pdvs.add(ev.pdv);
            resumen[ev.mercaderista].total++;
            if(ev.validado === 1) {
                resumen[ev.mercaderista].validadas++;
                localesCorrectos.add(ev.pdv);
            }
        });

        document.getElementById('kpi-card-correctas').textContent = localesCorrectos.size;

        const resumenArray = Object.entries(resumen).map(([nombre, data]) => {
            const total = data.total || 0;
            const validadas = data.validadas || 0;
            const max_kpi2 = data.max_kpi2 || 300;
            const porcentajeAprobado = total > 0 ? Math.round((validadas / total) * 100) : 0;
            const puntaje = total > 0 ? Math.round((validadas / total) * max_kpi2) : 0;
            return {
                nombre,
                pdvs: data.pdvs,
                total,
                validadas,
                max_kpi2,
                porcentajeAprobado,
                puntaje
            };
        });

        // Ordenar por puntaje de mayor a menor
        resumenArray.sort((a, b) => b.puntaje - a.puntaje);

        // Pintar filas
        resumenArray.forEach(item => {
            const { nombre, pdvs, total, validadas, porcentajeAprobado, puntaje } = item;
            const colorTexto = porcentajeAprobado >= 80 ? '#10b981' : (porcentajeAprobado >= 50 ? '#f59e0b' : '#ef4444');
            
            const tr = `
                <tr style="border-bottom: 1px solid rgba(255,255,255,0.02);">
                    <td style="padding: 15px 10px; color: var(--text-main); font-weight: 500;">👤 ${nombre}</td>
                    <td style="padding: 15px 10px; color: var(--text-muted);">${pdvs.size} Locales Totales</td>
                    <td style="padding: 15px 10px; color: var(--text-muted);">
                        <span style="color: ${colorTexto}; font-weight: bold;">${validadas}</span> / ${total} 
                        <span style="font-size: 0.8rem; margin-left: 10px;">(${porcentajeAprobado}%)</span>
                    </td>
                    <td style="padding: 15px 10px; text-align: center; color: #c4b5fd; font-weight: bold;">${puntaje}</td>
                </tr>
            `;
            tbody.insertAdjacentHTML('beforeend', tr);
        });

        // Pintar Galería del Cliente (SOLO las que están validadas y sin botón)
        const aprobadas = evidencias.filter(e => e.validado === 1);
        
        if(aprobadas.length === 0) {
            galeriaCliente.innerHTML = '<p style="color: var(--text-muted); padding: 10px;">Aún no hay planimetrías calificadas en este rango.</p>';
        } else {
            aprobadas.forEach(ev => {
                const imgError = "this.onerror=null; this.src='assets/img/no-image.png';";
                const card = `
                    <div class="evidencia-card">
                        <div class="card-header">
                            <div>
                                <div style="color: var(--text-main); font-weight: bold; font-size: 0.95rem;">${ev.pdv}</div>
                                <div style="color: var(--text-muted); font-size: 0.8rem; margin-top: 4px;">👤 ${ev.mercaderista}</div>
                            </div>
                            <div style="text-align: right;">
                                <div style="color: var(--brand-accent); font-size: 0.8rem;">📅 ${ev.fecha_trabajo} | ⏱ ${ev.hora}</div>
                            </div>
                        </div>
                        
                        <div class="img-container">
                            <div class="img-box" onclick="abrirVisor('${ev.url_antes}')">
                                <img src="${ev.url_antes}" onerror="${imgError}" alt="Antes">
                                <span class="img-label">ANTES</span>
                            </div>
                            <div class="img-box" onclick="abrirVisor('${ev.url_despues}')">
                                <img src="${ev.url_despues}" onerror="${imgError}" alt="Después">
                                <span class="img-label">DESPUÉS</span>
                            </div>
                        </div>
                        
                        <div class="card-body">
                            <div style="display: flex; gap: 5px; margin-bottom: 10px;">
                                <span style="background: rgba(14,165,233,0.1); color: #0ea5e9; padding: 3px 8px; border-radius: 12px; font-size: 0.75rem; font-weight: bold;">
                                    ${ev.categoria}
                                </span>
                            </div>
                            <p style="color: var(--text-muted); font-size: 0.85rem; line-height: 1.4; margin: 0; min-height: 40px;">
                                "${ev.comentario || 'Sin comentario'}"
                            </p>
                        </div>
                        
                        <!-- BARRA DE ESTADO SIN ACCIÓN -->
                        <div style="padding: 10px; text-align: center; background-color: rgba(16,185,129,0.1); color: #10b981; font-weight: bold; border-top: 1px solid var(--brand-border); font-size: 0.85rem;">
                            ☑ PLANIMETRÍA APROBADA
                        </div>
                    </div>
                `;
                galeriaCliente.insertAdjacentHTML('beforeend', card);
            });
        }
    };

    const pintarVistaAuditoria = (evidencias) => {
        const contenedor = document.getElementById('galeria-evidencias');
        if (!contenedor) return; 
        
        contenedor.innerHTML = '';

        evidencias.forEach(ev => {
            const imgError = "this.onerror=null; this.src='assets/img/no-image.png';";
            const card = `
                <div class="evidencia-card">
                    <div class="card-header">
                        <div>
                            <div style="color: var(--text-main); font-weight: bold; font-size: 0.95rem;">${ev.pdv}</div>
                            <div style="color: var(--text-muted); font-size: 0.8rem; margin-top: 4px;">👤 ${ev.mercaderista}</div>
                        </div>
                        <div style="text-align: right;">
                            <div style="color: var(--brand-accent); font-size: 0.8rem;">📅 ${ev.fecha_trabajo} | ⏱ ${ev.hora}</div>
                        </div>
                    </div>
                    
                    <div class="img-container">
                        <div class="img-box" onclick="abrirVisor('${ev.url_antes}')">
                            <img src="${ev.url_antes}" onerror="${imgError}" alt="Antes">
                            <span class="img-label">ANTES</span>
                        </div>
                        <div class="img-box" onclick="abrirVisor('${ev.url_despues}')">
                            <img src="${ev.url_despues}" onerror="${imgError}" alt="Después">
                            <span class="img-label">DESPUÉS</span>
                        </div>
                    </div>
                    
                    <div class="card-body">
                        <div style="display: flex; gap: 5px; margin-bottom: 10px;">
                            <span style="background: rgba(14,165,233,0.1); color: #0ea5e9; padding: 3px 8px; border-radius: 12px; font-size: 0.75rem; font-weight: bold;">
                                ${ev.categoria}
                            </span>
                        </div>
                        <p style="color: var(--text-muted); font-size: 0.85rem; line-height: 1.4; margin: 0; min-height: 40px;">
                            "${ev.comentario || 'Sin comentario'}"
                        </p>
                    </div>
                    
                    <button class="btn-validar ${ev.validado === 1 ? 'aprobado' : ''}" onclick="toggleValidacion(this, ${ev.id_evidencia})">
                        ${ev.validado === 1 ? '☑ REVISADO (Aprobado)' : '☐ MARCAR REVISADO'}
                    </button>
                </div>
            `;
            contenedor.insertAdjacentHTML('beforeend', card);
        });
    };

    window.abrirVisor = (url) => {
        document.getElementById('img-full').src = url;
        document.getElementById('visor-img').classList.add('active');
    };
    window.cerrarVisor = () => {
        document.getElementById('visor-img').classList.remove('active');
        document.getElementById('img-full').src = '';
    };

    window.toggleValidacion = async (btn, idEvidencia) => {
        const esAprobado = btn.classList.contains('aprobado');
        const nuevoEstado = esAprobado ? 0 : 1; 

        if (nuevoEstado === 1) {
            btn.classList.add('aprobado');
            btn.innerHTML = '☑ REVISADO (Aprobado)';
        } else {
            btn.classList.remove('aprobado');
            btn.innerHTML = '☐ MARCAR REVISADO';
        }

        try {
            await fetch('api/post_kpi2_validar.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id_evidencia: idEvidencia, validado: nuevoEstado })
            });
            
            const indexAud = dataAuditoria.findIndex(e => e.id_evidencia === idEvidencia);
            if (indexAud !== -1) {
                dataAuditoria[indexAud].validado = nuevoEstado;
            }
            
            const indexCli = dataCliente.findIndex(e => e.id_evidencia === idEvidencia);
            if (indexCli !== -1) {
                dataCliente[indexCli].validado = nuevoEstado;
                pintarVistaCliente(dataCliente);
            }
            
        } catch (error) {
            console.error("Error guardando validación:", error);
            alert("Hubo un error de conexión al validar. Intente de nuevo.");
        }
    };

    // Listeners para los dos inputs de fecha
    // inputDesde.addEventListener('change', () => { comboBoxLleno = false; cargarEvidencias(); });
    // inputHasta.addEventListener('change', () => { comboBoxLleno = false; cargarEvidencias(); });
    
    selectMercaderista.addEventListener('change', cargarEvidencias);

    cargarEvidencias();
});
</script>

<?php include 'layout/footer.php'; ?>