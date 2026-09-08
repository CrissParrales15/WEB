<?php include 'layout/header.php'; ?>

<style>
    /* ==========================================
       1. TARJETAS DE MÉTRICAS (KPI CARDS)
    ========================================== */
    .kpi-cards-grid { display: grid; grid-template-columns: repeat(5, 1fr); gap: 15px; margin-bottom: 25px; }
    .kpi-card {
        background: linear-gradient(145deg, var(--bg-panel) 0%, rgba(0,0,0,0.3) 100%);
        border: 1px solid var(--brand-border); border-radius: 12px; padding: 15px 20px;
        position: relative; overflow: hidden;
    }
    .kpi-card::before { content: ''; position: absolute; top: 0; left: 0; width: 4px; height: 100%; background-color: var(--brand-accent); }
    .kpi-card.success::before { background-color: #10b981; }
    .kpi-card.warning::before { background-color: #f59e0b; }
    .kpi-card.danger::before { background-color: #ef4444; }
    .kpi-card.purple::before { background-color: #8b5cf6; } 
    
    .kpi-card-title { color: var(--text-muted); font-size: 0.75rem; font-weight: bold; text-transform: uppercase; margin-bottom: 5px; letter-spacing: 0.5px; }
    .kpi-card-value { color: var(--text-main); font-size: 1.6rem; font-weight: 900; display: flex; align-items: center; gap: 8px; }
    .kpi-card-sub { color: var(--text-muted); font-size: 0.7rem; margin-top: 5px; }

    /* ==========================================
       2. PANELES Y TABS (FILTROS RÁPIDOS)
    ========================================== */
    .ranking-card { background: var(--bg-panel); border: 1px solid var(--brand-border); border-radius: 12px; padding: 20px; margin-bottom: 25px; }
    .dashboard-header-flex { display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid rgba(255,255,255,0.05); padding-bottom: 15px; margin-bottom: 15px; }
    .ranking-title { color: var(--brand-accent); font-size: 1rem; font-weight: bold; margin: 0; }
    
    .filter-tabs { display: flex; gap: 10px; }
    .tab-btn { background: rgba(255,255,255,0.05); border: 1px solid transparent; color: var(--text-muted); padding: 6px 15px; border-radius: 20px; font-size: 0.8rem; cursor: pointer; transition: all 0.2s; font-weight: 500; }
    .tab-btn:hover { background: rgba(255,255,255,0.1); }
    .tab-btn.active { background: rgba(139, 92, 246, 0.15); border-color: #8b5cf6; color: #c4b5fd; }

    /* ==========================================
       3. BARRAS DE PROGRESO Y BADGES
    ========================================== */
    .progress-bg { background-color: rgba(0,0,0,0.4); border-radius: 10px; height: 10px; width: 100%; overflow: hidden; margin-top: 6px; box-shadow: inset 0 1px 3px rgba(0,0,0,0.5); }
    .progress-bar { height: 100%; transition: width 1s cubic-bezier(0.4, 0, 0.2, 1); }
    .progress-bar.success { background: linear-gradient(90deg, #059669 0%, #10b981 100%); }
    .progress-bar.warning { background: linear-gradient(90deg, #d97706 0%, #f59e0b 100%); }
    .progress-bar.danger { background: linear-gradient(90deg, #dc2626 0%, #ef4444 100%); }
    .badge { padding: 4px 10px; border-radius: 6px; font-size: 0.75rem; font-weight: bold; letter-spacing: 0.3px; }
    .badge-success { background: rgba(16,185,129,0.1); color: #10b981; border: 1px solid rgba(16,185,129,0.2); }
    .badge-warning { background: rgba(245,158,11,0.1); color: #f59e0b; border: 1px solid rgba(245,158,11,0.2); }

    /* ==========================================
       4. SCROLL PREMIUM PARA TABLAS
    ========================================== */
    .table-scroll { max-height: 400px; overflow-y: auto; border-radius: 8px; border: 1px solid rgba(255, 255, 255, 0.05); }
    .table-scroll th { position: sticky; top: 0; background-color: #1a1a1a; z-index: 10; box-shadow: 0 2px 5px rgba(0,0,0,0.2); }
    .table-scroll::-webkit-scrollbar { width: 6px; height: 6px; }
    .table-scroll::-webkit-scrollbar-track { background: rgba(0, 0, 0, 0.2); }
    .table-scroll::-webkit-scrollbar-thumb { background: rgba(255, 255, 255, 0.15); border-radius: 10px; }


    /* MODAL STYLES */
    .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 1000; justify-content: center; align-items: center; }
    .modal-content { background: #13141b; border: 1px solid #2d2e3a; border-radius: 12px; width: 90%; max-width: 600px; padding: 25px; box-shadow: 0 10px 25px rgba(0,0,0,0.5); }
    .modal-header { display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid rgba(255,255,255,0.05); padding-bottom: 15px; margin-bottom: 20px; }
    .btn-close-modal { background: none; border: none; color: #fff; font-size: 1.5rem; cursor: pointer; }
    .btn-ver-puntos { background: rgba(139, 92, 246, 0.15); border: 1px solid #8b5cf6; color: #c4b5fd; padding: 6px 12px; border-radius: 6px; font-weight: bold; cursor: pointer; transition: 0.2s; }
    .btn-ver-puntos:hover { background: #8b5cf6; color: #fff; } 

    input[type="date"].search-bar::-webkit-calendar-picker-indicator {
        filter: invert(1);
        opacity: 1;
        cursor: pointer;
    }
</style>

<div class="kpi-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
    <div>
        <h2 style="color: var(--text-main); margin-bottom: 5px;">Panel Ejecutivo: Órdenes de Compra</h2>
        <p style="color: var(--text-muted); font-size: 0.9rem;">Visión 360° del KPI 3 (Meta >70%) | Canal: TIA</p>
    </div>
    
    <div style="display: flex; gap: 10px; align-items: center;">
        <span style="color: var(--text-muted); font-size: 13px;">Desde:</span>
        <input type="date" id="filtro-desde" class="search-bar" style="width: auto;">
        
        <span style="color: var(--text-muted); font-size: 13px;">Hasta:</span>
        <input type="date" id="filtro-hasta" class="search-bar" style="width: auto;">

        <select id="filtro-region" class="search-bar" style="width: auto; margin-left: 10px;">
            <option value="all">Todas las Regiones</option>
            <option value="COSTA">Costa</option>
            <option value="SIERRA">Sierra</option>
        </select>
        <select id="filtro-mercaderista" class="search-bar" style="width: auto;">
            <option value="all">Todos los Mercaderistas</option>
        </select>
        
        <button class="btn-action btn-refresh" onclick="comboBoxLleno = false; cargarOrdenes()" style="background: rgba(0, 255, 135, 0.1); color: #00FF87; border: 1px solid rgba(0, 255, 135, 0.3); padding: 8px 15px; border-radius: 6px; cursor: pointer;">
            ↻ Actualizar
        </button>
    </div>
</div>

<div class="kpi-cards-grid">

    <div class="kpi-card">
        <div class="kpi-card-title">Mercaderistas</div>
        <div class="kpi-card-value" id="val-equipo">
            0 <i class="fas fa-users" style="font-size: 1.1rem;"></i>
        </div>
        <div class="kpi-card-sub">Con rutas TIA asignadas</div>
    </div>
    <div class="kpi-card success">
        <div class="kpi-card-title">Cumplen Meta (300 pts)</div>
        <div class="kpi-card-value" id="val-ganadores">0 <span style="font-size: 1.1rem;"><i class="fas fa-trophy"></i></span></div>
        <div class="kpi-card-sub">Superaron el 70% de efectividad</div>
    </div>
    <div class="kpi-card warning">
        <div class="kpi-card-title">En Riesgo (< 70%)</div>
        <div class="kpi-card-value" id="val-riesgo">0 <span style="font-size: 1.1rem;"><i class="fas fa-exclamation-triangle"></i></span></div>
        <div class="kpi-card-sub">Aún no aseguran sus puntos</div>
    </div>
    <div class="kpi-card">
        <div class="kpi-card-title">Efectividad Global</div>
        <div class="kpi-card-value" id="val-efectividad">0% <span style="font-size: 1.1rem;"><i class="fas fa-chart-line"></i></span></div>
        <div class="kpi-card-sub" id="val-efectividad-sub">0 de 0 gestiones logradas</div>
    </div>
    
    <div class="kpi-card purple">
        <div class="kpi-card-title">Total Órdenes Equipo</div>
        <div class="kpi-card-value" id="val-volumen-total">0 <span style="font-size: 1.1rem;"><i class="fas fa-shopping-cart"></i></span></div>
        <div class="kpi-card-sub">Sumatoria de Órdenes Generadas</div>
    </div>

    <div class="kpi-card">
        <div class="kpi-card-title">Monto Facturado Total</div>
            <div class="kpi-card-value">
                <span id="val-monto-numero">$0</span>
                <i class="fa fa-usd" style="font-size: 1.1rem; color: #10b981;"></i>
            </div>
        <div class="kpi-card-sub">Suma de facturación en el rango</div>
    </div>
</div>

<div class="ranking-card">
    <div class="dashboard-header-flex">
        <h3 class="ranking-title">🎯 Rendimiento por Mercaderista (En rango de fechas)</h3>
        <div class="filter-tabs">
            <button class="tab-btn active" onclick="filtrarTabla('all')">Todos</button>
            <button class="tab-btn" onclick="filtrarTabla('ganadores')">🏆 Ganadores</button>
            <button class="tab-btn" onclick="filtrarTabla('riesgo')">⚠️ En Riesgo</button>
        </div>
    </div>
    
    <div class="table-scroll">
        <table style="width: 100%; text-align: left; border-collapse: collapse; font-size: 0.9rem;">
            <thead>
                <tr style="color: var(--text-muted);">

                    <th style="padding: 12px 10px;">Mercaderista</th>
                    <th style="padding: 12px 10px; text-align: center;">Gestiones Programadas</th>
                    <th style="padding: 12px 10px; text-align: center;">Gestiones Efectivas</th>
                    <th style="padding: 12px 10px; text-align: center; color: #8b5cf6;">Total Órdenes</th>
                    <th style="padding: 12px 10px; text-align: center; color: #10b981;">Monto Facturado</th>   <!-- NUEVA -->
                    <th style="padding: 12px 15px; width: 20%;">Efectividad de Cumplimiento</th>
                    <th style="padding: 12px 10px; text-align: center; color: #f59e0b;">Puntaje</th>   <!-- NUEVA -->
                    <th style="padding: 12px 10px; text-align: center; display: none;">Estado Actual</th>
                </tr>
            </thead>
            <tbody id="tabla-resumen-body"></tbody>
        </table>
    </div>
</div>

<div class="ranking-card">
    <div class="dashboard-header-flex">
        <h3 class="ranking-title">📋 Registro de Campo Diario (Operación del rango seleccionado)</h3>
    </div>
    <div class="table-scroll" style="max-height: 300px;">
        <table style="width: 100%; text-align: left; border-collapse: collapse; font-size: 0.85rem;">
            <thead>
                <tr style="color: var(--text-muted);">
                    <th style="padding: 15px 10px; width: 10%;">Fecha</th> 
                    <th style="padding: 15px 10px; width: 15%;">Hora Reporte</th>
                    <th style="padding: 15px 10px; width: 25%;">Mercaderista</th>
                    <th style="padding: 15px 10px; width: 35%;">Punto de Venta Visitado</th>
                    <th style="padding: 15px 10px; width: 15%; text-align: center;">Gestión OC</th>
                </tr>
            </thead>
            <tbody id="tabla-oc-body"></tbody>
        </table>
    </div>
</div>

<!-- MODAL HTML -->
<div id="modal-kpi3" class="modal-overlay">
    <div class="modal-content">
        <div class="modal-header">
            <div>
                <h3 id="modal-nombre" style="color: #fff; margin:0;">Mercaderista</h3>
                <p style="color: #a1a1aa; font-size: 0.8rem; margin: 5px 0 0 0;">Progreso Mensual KPI 3 (Órdenes TIA)</p>
            </div>
            <button class="btn-close-modal" onclick="cerrarModal()">×</button>
        </div>
        
        <div style="margin-bottom: 20px;">
            <div style="display: flex; justify-content: space-between; font-size: 0.85rem; margin-bottom: 5px;">
                <span id="modal-meta-label" style="color: #a1a1aa;">Meta: 300 pts</span>
                <span id="modal-puntos-txt" style="color: #8b5cf6; font-weight: bold;">0 / 300 pts</span>
            </div>
            <div class="progress-bg" style="height: 12px;">
                <div id="modal-barra-progreso" class="progress-bar purple" style="width: 0%; background: #8b5cf6;"></div>
            </div>
        </div>

        <div class="table-scroll" style="max-height: 250px;">
            <table style="width: 100%; text-align: left; border-collapse: collapse; font-size: 0.9rem;">
                <thead>
                    <tr>
                        <th style="padding: 10px 12px; color: #a1a1aa;">Día</th>
                        <th style="padding: 10px 12px; color: #a1a1aa;">Efectividad</th>
                        <th style="padding: 10px 12px; color: #a1a1aa; text-align: right;">Puntos Ganados</th>
                    </tr>
                </thead>
                <tbody id="modal-tbody"></tbody>
            </table>
        </div>
    </div>
</div>

<script>
let datosMensualesGlobales = [];

document.addEventListener('DOMContentLoaded', () => {
    // --- LÓGICA DE FECHAS ---
    const hoy = new Date();
    const yyyy = hoy.getFullYear();
    const mm = String(hoy.getMonth() + 1).padStart(2, '0');
    const dd = String(hoy.getDate()).padStart(2, '0');
    const fechaHoy = `${yyyy}-${mm}-${dd}`;
    const fechaPrimerDia = `${yyyy}-${mm}-01`;

    const inputDesde = document.getElementById('filtro-desde');
    const inputHasta = document.getElementById('filtro-hasta');

    inputDesde.max = fechaHoy;
    inputHasta.max = fechaHoy;
    
    inputDesde.value = fechaPrimerDia;
    inputHasta.value = fechaHoy;

    const selectMercaderista = document.getElementById('filtro-mercaderista');
    const selectRegion = document.getElementById('filtro-region');
    let comboBoxLleno = false;

    window.cargarOrdenes = async () => {
        let desde = inputDesde.value;
        let hasta = inputHasta.value;
        const mercaderista = selectMercaderista.value;
        const region = selectRegion.value; 
        
        if (desde > hasta) {
            // alert("La fecha 'Desde' no puede ser mayor a la fecha 'Hasta'. Se ajustará automáticamente el rango.");
            hasta = desde; 
            inputHasta.value = hasta;
        }

        document.getElementById('tabla-oc-body').innerHTML = '<tr><td colspan="5" style="padding:30px; text-align:center; color: var(--text-muted);">Cargando operaciones...</td></tr>';
        document.getElementById('tabla-resumen-body').innerHTML = '<tr><td colspan="6" style="padding:30px; text-align:center; color: var(--text-muted);">Calculando métricas...</td></tr>';

        // Enviamos el RANGO al Backend
        let url = `api/get_kpi3_oc.php?desde=${desde}&hasta=${hasta}`;
        if (region !== 'all') url += `&region=${encodeURIComponent(region)}`; 
        if (mercaderista !== 'all') url += `&mercaderista=${encodeURIComponent(mercaderista)}`;

        try {
            const response = await fetch(url);
            const res = await response.json();
            
            if (res.status === 'success') {
                datosMensualesGlobales = res.resumen_rango || []; 
                
                // actualizarTarjetasKPI(datosMensualesGlobales, res.volumen_total_global);
                actualizarTarjetasKPI(datosMensualesGlobales, res.volumen_total_global, res.monto_facturado_total);
                pintarProgresoMensual(datosMensualesGlobales); 
                pintarFeedDiario(res.detalle_diario || []);
                
                if (!comboBoxLleno || mercaderista === 'all') {
                    llenarComboBox(res.usuarios_activos || [], mercaderista);
                    comboBoxLleno = true;
                }
                
                document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
                document.querySelector('.tab-btn').classList.add('active');
            }
        } catch (error) {
            console.error("Error:", error);
            document.getElementById('tabla-resumen-body').innerHTML = '<tr><td colspan="6" style="padding:30px; text-align:center; color: #ef4444;">Error al conectar con el servidor.</td></tr>';
        }
    };

    const actualizarTarjetasKPI = (resumen, volumenGlobal, montoTotal) => {
        let totalEquipo = resumen.length;
        let ganadores = 0, riesgo = 0, sumaTotalMetas = 0, sumaTotalLogros = 0;

        resumen.forEach(data => {

            const porcentaje = data.meta_pdvs > 0 ? (data.total_ordenes / data.meta_pdvs) * 100 : 0;

            if (porcentaje >= 70) ganadores++; else riesgo++;
            
            sumaTotalMetas += data.meta_pdvs;
            sumaTotalLogros += data.pdvs_exitosos;
        });

        const efectividadGlobal = sumaTotalMetas > 0 ? Math.round((sumaTotalLogros / sumaTotalMetas) * 100) : 0;
        const totalOrdenesMostrados = datosMensualesGlobales.reduce((sum, item) => sum + (item.total_ordenes || 0), 0);

        document.getElementById('val-equipo').innerHTML = `${totalEquipo} <i class="fas fa-users" style="font-size: 1.2rem; color: #3510b9;"></i>`;
        document.getElementById('val-ganadores').innerHTML = `${ganadores} <i class="fas fa-trophy" style="font-size: 1.2rem; color: #f59e0b;"></i>`;
        document.getElementById('val-riesgo').innerHTML = `${riesgo} <i class="fas fa-exclamation-triangle" style="font-size: 1.2rem; color: #f59e0b;"></i>`;
        document.getElementById('val-efectividad').innerHTML = `${efectividadGlobal}% <i class="fas fa-chart-line" style="font-size: 1.2rem; color: #10b981;" ></i>`;
        document.getElementById('val-efectividad-sub').textContent = `${sumaTotalLogros} de ${sumaTotalMetas} logradas`;
        
        // document.getElementById('val-volumen-total').innerHTML = `${volumenGlobal !== undefined ? volumenGlobal : 0} <span style="font-size: 1.2rem;">🛒</span>`;
        // document.getElementById('val-volumen-total').innerHTML = `${totalOrdenesMostrados} <span style="font-size: 1.2rem;">🛒</span>`;
        document.getElementById('val-volumen-total').innerHTML = `${totalOrdenesMostrados} <i class="fas fa-shopping-cart" style="font-size: 1.2rem; color: #10b981;"></i>`;
        // document.getElementById('val-monto-total').innerHTML = '$' + Number(montoTotal).toLocaleString() + ' <span style="font-size: 1.1rem;">💰</span>';
        document.getElementById('val-monto-numero').textContent = '$' + Math.floor(Number(montoTotal)).toLocaleString();
    };
   
    const pintarProgresoMensual = (datosFiltrados) => {
        const tbody = document.getElementById('tabla-resumen-body');
        tbody.innerHTML = '';

        if(datosFiltrados.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" style="padding:30px; text-align:center; color: var(--text-muted);">No hay asignaciones en este rango.</td></tr>';
            return;
        }

        datosFiltrados.forEach(data => {

            const porcentaje = data.meta_pdvs > 0 ? Math.round((data.total_ordenes / data.meta_pdvs) * 100) : 0;

            let colorClase = porcentaje >= 70 ? 'success' : (porcentaje >= 40 ? 'warning' : 'danger');

            const ganoPuntos = porcentaje >= 70;
            const badge = ganoPuntos 
                ? '<span class="badge badge-success">✅ Cumple Meta</span>' 
                : `<span class="badge badge-warning">⚠️ En riesgo</span>`;

 
            const tr = `
                <tr style="border-bottom: 1px solid rgba(255,255,255,0.02);">
                    <td style="padding: 15px 10px; color: var(--text-main); font-weight: 500;"> 
                        👤 ${data.mercaderista}
                    </td>
                    <td style="padding: 15px 10px; text-align: center; color: var(--text-muted); font-size: 1.1rem;">${data.meta_pdvs}</td>
                    <td style="padding: 15px 10px; text-align: center; color: var(--brand-accent); font-weight: bold; font-size: 1.1rem;">${data.pdvs_exitosos}</td>
                    <td style="padding: 15px 10px; text-align: center; color: #c4b5fd; font-weight: bold; font-size: 1.1rem;">${data.total_ordenes || 0}</td>
                    <!-- NUEVA COLUMNA: Monto Facturado -->
                    <td style="padding: 15px 10px; text-align: center; color: #10b981; font-weight: bold; font-size: 1.1rem;">$${data.total_facturado ? Math.floor(Number(data.total_facturado)).toLocaleString() : '0'}</td>
                    <!-- Columna Efectividad (existente) -->
                    <td style="padding: 15px 15px;">
                        <div style="display: flex; justify-content: space-between; font-size: 0.75rem; margin-bottom: 4px;">
                            <span style="color: var(--text-muted);">Efectividad Global (Rango)</span>
                            <span style="font-weight: bold; color: var(--text-main);">${porcentaje}%</span>
                        </div>
                        <div class="progress-bg">
                            <div class="progress-bar ${colorClase}" style="width: ${porcentaje > 100 ? 100 : porcentaje}%;"></div>
                        </div>
                    </td>
                    <!-- NUEVA COLUMNA: Puntaje (regla de 3) -->
                    <td style="padding: 15px 10px; text-align: center; color: #f59e0b; font-weight: bold; font-size: 1.1rem;">
                        ${data.meta_pdvs > 0 ? Math.round((data.total_ordenes / data.meta_pdvs) * 300) : 0}
                    </td>
                    <td style="padding: 15px 10px; text-align: center; display:none;">
                        <div style="margin-bottom: 8px;">${badge}</div>
                        <button class="btn-ver-puntos" onclick="abrirModalPuntos(${data.id_usuario}, '${data.mercaderista}')">🏆 Ver Detalle Días</button>
                    </td>
                </tr>
            `;
            tbody.insertAdjacentHTML('beforeend', tr);
        });
    };

    window.abrirModalPuntos = async (idUsuario, nombreMercaderista) => {
        const fechaRef = document.getElementById('filtro-hasta').value; 
        const modal = document.getElementById('modal-kpi3');
        document.getElementById('modal-nombre').textContent = nombreMercaderista;
        document.getElementById('modal-tbody').innerHTML = '<tr><td colspan="4" style="text-align:center; padding: 20px;">Cargando histórico del mes...</td></tr>';
        modal.style.display = 'flex';

        try {
            const response = await fetch(`api/get_kpi3_puntos_modal.php?id_usuario=${idUsuario}&fecha=${fechaRef}`);
            const data = await response.json();
            
            if (data.status === 'success') {
                const meta = data.resumen_mensual.meta_puntos;
                document.getElementById('modal-meta-label').textContent = `Meta: ${meta} pts`;
                document.getElementById('modal-puntos-txt').textContent = `${data.resumen_mensual.puntos_actuales} / ${meta} pts`;
                document.getElementById('modal-barra-progreso').style.width = `${data.resumen_mensual.porcentaje_meta}%`;

                let filas = '';
                data.desglose.forEach(dia => {
                    const colorPunto = dia.puntos > 0 ? '#10b981' : '#ef4444';
                    filas += `
                        <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                            <td style="padding: 12px; color: #a1a1aa;">${dia.fecha}</td>
                            <td style="padding: 12px; color: #e4e4e7;">${dia.efectividad}% (${dia.visitados}/${dia.asignados})</td>
                            <td style="padding: 12px; text-align: right; color: ${colorPunto}; font-weight: bold;">+${dia.puntos} pts</td>
                        </tr>
                    `;
                });
                document.getElementById('modal-tbody').innerHTML = filas;
            }
        } catch (e) {
            document.getElementById('modal-tbody').innerHTML = '<tr><td colspan="4" style="text-align:center;">Error al cargar.</td></tr>';
        }
    };

    window.cerrarModal = () => { document.getElementById('modal-kpi3').style.display = 'none'; };

    window.filtrarTabla = (tipo) => {
        document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
        event.target.classList.add('active');

        if (tipo === 'all') pintarProgresoMensual(datosMensualesGlobales);
        // else if (tipo === 'ganadores') pintarProgresoMensual(datosMensualesGlobales.filter(d => (d.pdvs_exitosos / (d.meta_pdvs||1)) * 100 >= 70));
        // else if (tipo === 'riesgo') pintarProgresoMensual(datosMensualesGlobales.filter(d => (d.pdvs_exitosos / (d.meta_pdvs||1)) * 100 < 70));
        //! DE GESTIONES EFECTIVAS A TOTAL ORDENES
        else if (tipo === 'ganadores') pintarProgresoMensual(datosMensualesGlobales.filter(d => (d.total_ordenes / (d.meta_pdvs||1)) * 100 >= 70));
        else if (tipo === 'riesgo') pintarProgresoMensual(datosMensualesGlobales.filter(d => (d.total_ordenes / (d.meta_pdvs||1)) * 100 < 70));
    };

    const pintarFeedDiario = (diario) => {
        const tbody = document.getElementById('tabla-oc-body');
        tbody.innerHTML = '';

        if(diario.length === 0) {
            tbody.innerHTML = '<tr><td colspan="5" style="padding:30px; text-align:center; color: var(--text-muted);">No hay reportes de campo en el rango seleccionado.</td></tr>';
            return;
        }

        diario.forEach(oc => {
            const esExitoso = oc.existe_orden === 'SI';
            const colorOrden = esExitoso ? '#10b981' : 'var(--text-muted)';
            const bgBadge = esExitoso ? 'rgba(16,185,129,0.1)' : 'rgba(255,255,255,0.05)';
            const icon = esExitoso ? '✅' : '➖';

            const tr = `
                <tr style="border-bottom: 1px solid rgba(255,255,255,0.02); opacity: ${esExitoso ? '1' : '0.5'}; transition: background 0.2s;">
                    <td style="padding: 12px 10px; color: var(--brand-accent); font-size: 0.8rem;">${oc.fecha}</td>
                    <td style="padding: 12px 10px; color: var(--text-muted); font-family: monospace;">${oc.hora}</td>
                    <td style="padding: 12px 10px; color: var(--text-main); font-weight: 500;">${oc.mercaderista}</td>
                    <td style="padding: 12px 10px; color: var(--text-muted);">
                        <span style="color: var(--brand-accent); font-size:0.75rem;">${oc.pos_id} | Orden: ${oc.numero_orden || 'N/A'}</span><br>
                        ${oc.nombre_pdv}
                    </td>
                    <td style="padding: 12px 10px; text-align: center;">
                        <span style="background: ${bgBadge}; color: ${colorOrden}; padding: 4px 12px; border-radius: 6px; font-weight: bold; font-size: 0.75rem;">
                            ${icon} ${oc.existe_orden}
                        </span>
                    </td>
                </tr>
            `;
            tbody.insertAdjacentHTML('beforeend', tr);
        });
    };

    const llenarComboBox = (usuarios, seleccionado) => {
        let opciones = `<option value="all">Filtro Rápido: Todo el Equipo</option>`;
        usuarios.forEach(usr => {
            opciones += `<option value="${usr}" ${usr === seleccionado ? 'selected' : ''}>${usr}</option>`;
        });
        selectMercaderista.innerHTML = opciones;
    };

    // ELIMINADOS LOS LISTENERS 'CHANGE' DE LOS INPUTS DE FECHA
    
    selectRegion.addEventListener('change', () => { 
        comboBoxLleno = false; 
        selectMercaderista.value = 'all'; 
        cargarOrdenes(); 
    }); 
    
    selectMercaderista.addEventListener('change', cargarOrdenes);
    cargarOrdenes();
});
</script>

<?php include 'layout/footer.php'; ?>