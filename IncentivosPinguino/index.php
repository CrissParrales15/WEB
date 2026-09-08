<?php include 'layout/header.php'; ?>

<!-- Chart.js para las gráficas -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
    /* VARIABLES DE COLOR DEL DASHBOARD */
    :root {
        --kpi1-color: #00FF87; 
        --kpi2-color: #00A3FF; 
        --kpi3-color: #B958FF; 
        --bg-card: #151A25;
        --border-color: #2A3143;
        --text-main: #FFFFFF;
        --text-muted: #8E96A8;
    }

    .dash-container { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; color: var(--text-main); }
    
    /* Header del Dashboard */
    .dash-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
    .dash-title h1 { font-size: 24px; font-weight: 700; margin: 0 0 5px 0; }
    .dash-title p { color: var(--text-muted); font-size: 13px; margin: 0; }
    
    .dash-filters { display: flex; gap: 10px; align-items: center; }
    .dash-select { background: #1E2536; border: 1px solid var(--border-color); color: white; padding: 8px 15px; border-radius: 6px; outline: none; }
    .btn-action { background: #1E2536; border: 1px solid var(--border-color); color: white; padding: 8px 15px; border-radius: 6px; cursor: pointer; transition: 0.2s; }
    .btn-action:hover { background: #2A3143; }
    .btn-refresh { background: rgba(0, 255, 135, 0.1); color: var(--kpi1-color); border: 1px solid rgba(0, 255, 135, 0.3); }

    /* Grid Superior (Tarjetas 3 columnas) */
    .kpi-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 20px; }
    
    .kpi-card { 
        background: var(--bg-card); 
        border: 1px solid var(--border-color); 
        border-radius: 12px; 
        padding: 20px; 
        height: 160px; 
        display: flex; 
        flex-direction: column; 
        justify-content: space-between; 
        cursor: pointer; 
        transition: transform 0.2s, box-shadow 0.2s;
    }
    .kpi-card:hover { transform: translateY(-5px); box-shadow: 0 8px 20px rgba(0,0,0,0.4); }

    input[type="date"]::-webkit-calendar-picker-indicator {
        filter: invert(1);
        opacity: 1;
        cursor: pointer;
    }
    
    .kpi-header { display: flex; justify-content: space-between; align-items: center; color: var(--text-muted); font-size: 12px; font-weight: 600; letter-spacing: 1px; }
    .kpi-value { font-size: 36px; font-weight: bold; margin-top: 10px; margin-bottom: 10px; }
    .kpi-value span { font-size: 18px; color: var(--text-muted); font-weight: normal; }
    
    .kpi-footer { display: flex; justify-content: space-between; font-size: 12px; color: var(--text-muted); margin-bottom: 8px; }
    
    .progress-bg { background: #2A3143; height: 6px; border-radius: 3px; width: 100%; }
    .progress-bar { height: 6px; border-radius: 3px; transition: width 1s ease-in-out; }

    .kpi1-text { color: var(--kpi1-color); }
    .kpi1-bg { background: var(--kpi1-color); box-shadow: 0 0 10px rgba(0,255,135,0.4); }
    
    .kpi2-text { color: var(--kpi2-color); }
    .kpi2-bg { background: var(--kpi2-color); box-shadow: 0 0 10px rgba(0,163,255,0.4); }
    
    .kpi3-text { color: var(--kpi3-color); }
    .kpi3-bg { background: var(--kpi3-color); box-shadow: 0 0 10px rgba(185,88,255,0.4); }

    /* Paneles Inferiores */
    .bottom-grid { display: grid; grid-template-columns: 1fr 1.3fr 1.3fr; gap: 20px; }
    .panel-card { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 12px; padding: 20px; height: 480px; display: flex; flex-direction: column; }
    .panel-title { color: var(--text-muted); font-size: 12px; font-weight: 600; letter-spacing: 1px; margin-bottom: 20px; text-transform: uppercase; }
    .chart-wrapper { position: relative; flex-grow: 1; width: 100%; min-height: 0; }

    /* Score Global */
    .donut-container { position: relative; width: 180px; height: 180px; margin: 0 auto 20px; }
    .donut-text { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); text-align: center; }
    .donut-text h2 { margin: 0; font-size: 28px; font-weight: bold; }
    .donut-text p { margin: 0; font-size: 11px; color: var(--text-muted); }
    .score-badge { display: table; margin: 0 auto 20px; padding: 4px 12px; border-radius: 4px; font-size: 12px; font-weight: bold; border: 1px solid; }

    .mini-bar-group { margin-bottom: 15px; }
    .mini-bar-labels { display: flex; justify-content: space-between; font-size: 12px; margin-bottom: 5px; color: var(--text-muted); }
    .mini-bar-bg { background: #2A3143; height: 4px; border-radius: 2px; }

    /* Ranking */
    .ranking-list { flex-grow: 1; overflow-y: auto; padding-right: 5px; }
    .ranking-list::-webkit-scrollbar { width: 4px; }
    .ranking-list::-webkit-scrollbar-thumb { background: #3f4963; border-radius: 2px; }
    .rank-item { margin-bottom: 15px; }
    .rank-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; }
    .rank-left { display: flex; align-items: center; gap: 12px; }
    .rank-pos { color: #FFB800; font-weight: bold; width: 15px; }
    .rank-avatar { width: 30px; height: 30px; border-radius: 50%; background: #1E2D2A; color: var(--kpi1-color); display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: bold; }
    .rank-name { font-size: 13px; font-weight: 500; }
    .rank-right { display: flex; align-items: center; gap: 15px; }
    .rank-pts { font-weight: bold; font-size: 14px; }
    .rank-status { font-size: 9px; font-weight: bold; padding: 3px 8px; border-radius: 4px; border: 1px solid; width: 70px; text-align: center; }
    
    .text-excelente { color: var(--kpi1-color); }
    .text-bueno { color: var(--kpi2-color); }
    .text-basico { color: #FFB800; }
    .text-nocumple { color: #FF3B30; }

    .estado-excelente { color: var(--kpi1-color); border-color: rgba(0,255,135,0.3); }
    .estado-bueno { color: var(--kpi2-color); border-color: rgba(0,163,255,0.3); }
    .estado-basico { color: #FFB800; border-color: rgba(255,184,0,0.3); }
    .estado-nocumple { color: #FF3B30; border-color: rgba(255,59,48,0.3); }

    /* ESTILOS DEL MODAL */
    .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.85); z-index: 9999; align-items: center; justify-content: center; backdrop-filter: blur(5px); }
    .modal-box { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 12px; width: 550px; max-width: 95%; max-height: 85vh; display: flex; flex-direction: column; overflow: hidden; box-shadow: 0 10px 40px rgba(0,0,0,0.8); }
    .modal-header { display: flex; justify-content: space-between; padding: 20px 25px; border-bottom: 1px solid var(--border-color); background: rgba(255,255,255,0.02); }
    .modal-close { background: none; border: none; color: var(--text-muted); font-size: 24px; cursor: pointer; transition: 0.2s; }
    .modal-close:hover { color: white; }
    .modal-body { overflow-y: auto; padding: 0 25px 20px 25px; }
    .modal-body::-webkit-scrollbar { width: 6px; }
    .modal-body::-webkit-scrollbar-thumb { background: #3f4963; border-radius: 3px; }
</style>

<div class="dash-container">
    
    <!-- HEADER -->
    <div class="dash-header">
        <div class="dash-title">
            <h1>Dashboard Ejecutivo</h1>
            <p id="dash-date-text">Cargando rango de fechas...</p>
        </div>
        <div class="dash-filters">
            <span style="color: var(--text-muted); font-size: 13px;">Desde:</span>
            <input type="date" class="dash-select" id="filter-desde" onchange="cargarDataDashboard()">
            
            <span style="color: var(--text-muted); font-size: 13px; margin-left: 10px;">Hasta:</span>
            <input type="date" class="dash-select" id="filter-hasta" onchange="cargarDataDashboard()">

            <select class="dash-select" id="filter-region" onchange="cargarDataDashboard()" style="margin-left: 10px;">
                <option value="all">Todas las Regiones</option>
                <option value="COSTA">Costa</option>
                <option value="SIERRA">Sierra</option>
            </select>
            <select class="dash-select" id="filter-merca" onchange="cargarDataDashboard()">
                <option value="all">Todo el Equipo</option>
            </select>
            
            <button class="btn-action btn-refresh" onclick="cargarDataDashboard()">↻ Actualizar</button>
        </div>
    </div>

    <!-- TARJETAS SUPERIORES  -->
    <div class="kpi-grid">
        <!-- KPI 1 -->
        <div class="kpi-card" onclick="abrirDesgloseKPI(1)">
            <div class="kpi-header"><span>📍 EFECTIVIDAD DE VISITA</span> <span style="font-size:10px;">(Click para detalles)</span></div>
            <div class="kpi-value kpi1-text"><span id="val-pct-1">0</span><span> %</span></div>
            <div>
                <div class="kpi-footer">
                    <span>Puntos</span>
                    <span class="kpi1-text"><b id="val-pts-1">0</b> / <span id="val-meta-1">400</span> pts</span>
                </div>
                <div class="progress-bg"><div class="progress-bar kpi1-bg" id="bar-top-1" style="width: 0%;"></div></div>
            </div>
        </div>
        
        <!-- KPI 2 -->
        <div class="kpi-card" onclick="abrirDesgloseKPI(2)">
            <div class="kpi-header"><span>📸 EJECUCIÓN EN PDV</span> <span style="font-size:10px;">(Click para detalles)</span></div>
            <div class="kpi-value kpi2-text"><span id="val-pct-2">0</span><span> %</span></div>
            <div>
                <div class="kpi-footer">
                    <span>Puntos</span>
                    <span class="kpi2-text"><b id="val-pts-2">0</b> / <span id="val-meta-2">300</span> pts</span>
                </div>
                <div class="progress-bg"><div class="progress-bar kpi2-bg" id="bar-top-2" style="width: 0%;"></div></div>
            </div>
        </div>

        <!-- KPI 3 -->
        <div class="kpi-card" onclick="abrirDesgloseKPI(3)">
            <div class="kpi-header"><span>🛒 ÓRDENES DE COMPRA</span> <span style="font-size:10px;">(Click para detalles)</span></div>
            <div class="kpi-value kpi3-text"><span id="val-pct-3">0</span><span> %</span></div>
            <div>
                <div class="kpi-footer">
                    <span>Puntos</span>
                    <span class="kpi3-text"><b id="val-pts-3">0</b> / <span id="val-meta-3">300</span> pts</span>
                </div>
                <div class="progress-bg"><div class="progress-bar kpi3-bg" id="bar-top-3" style="width: 0%;"></div></div>
            </div>
        </div>
    </div>

    <!-- PANELES INFERIORES -->
    <div class="bottom-grid">
        
        <!-- PANEL SCORE GLOBAL -->
        <div class="panel-card">
            <div class="panel-title">SCORE GLOBAL · EQUIPO</div>
            
            <div class="donut-container">
                <canvas id="donutChart"></canvas>
                <div class="donut-text">
                    <h2 id="donut-total" class="kpi1-text">0</h2>
                    <p>/1000 pts</p>
                </div>
            </div>
            
            <div class="score-badge estado-bueno" id="global-badge">BUENO</div>
            <p style="text-align:center; font-size:11px; color:var(--text-muted); margin-bottom: 20px;">Promedio en rango</p>

            <div class="mini-bar-group">
                <div class="mini-bar-labels"><span>KPI 1 Visita</span><span class="kpi1-text" id="mini-val-1">0</span></div>
                <div class="mini-bar-bg"><div class="progress-bar kpi1-bg" id="mini-bar-1" style="width: 0%;"></div></div>
            </div>
            <div class="mini-bar-group">
                <div class="mini-bar-labels"><span>KPI 2 Ejecución</span><span class="kpi2-text" id="mini-val-2">0</span></div>
                <div class="mini-bar-bg"><div class="progress-bar kpi2-bg" id="mini-bar-2" style="width: 0%;"></div></div>
            </div>
            <div class="mini-bar-group">
                <div class="mini-bar-labels"><span>KPI 3 OC</span><span class="kpi3-text" id="mini-val-3">0</span></div>
                <div class="mini-bar-bg"><div class="progress-bar kpi3-bg" id="mini-bar-3" style="width: 0%;"></div></div>
            </div>
        </div>

        <!-- PANEL RANKING -->
        <div class="panel-card">
            <div class="panel-title" style="display:flex; justify-content:space-between;">
                <span>RANKING MERCADERISTAS</span>
                <span class="kpi1-text" id="ranking-month-label">Rango</span>
            </div>
            <div class="ranking-list" id="ranking-container">
                <p style="color:var(--text-muted); text-align:center; margin-top:50px;">Cargando ranking...</p>
            </div>
        </div>

        <!-- PANEL TENDENCIA SEMANAL -->
        <div class="panel-card">
            <div class="panel-title">TENDENCIA SEMANAL DEL MES</div>
            <div class="chart-wrapper">
                <canvas id="lineChart"></canvas>
            </div>
            <div style="display:flex; gap:15px; font-size:11px; margin-top:15px; justify-content:center; color:var(--text-muted);">
                <div style="display:flex; align-items:center; gap:5px;"><div style="width:8px;height:8px;border-radius:50%;background:var(--kpi1-color);"></div> Visita</div>
                <div style="display:flex; align-items:center; gap:5px;"><div style="width:8px;height:8px;border-radius:50%;background:var(--kpi2-color);"></div> Ejecución</div>
                <div style="display:flex; align-items:center; gap:5px;"><div style="width:8px;height:8px;border-radius:50%;background:var(--kpi3-color);"></div> OC</div>
            </div>
        </div>

    </div>
</div>

<!-- MODAL DE DESGLOSE INTERACTIVO -->
<div id="modalDesgloseKPI" class="modal-overlay">
    <div class="modal-box">
        <div class="modal-header">
            <div>
                <h3 id="tituloModalKPI" style="color: white; margin: 0; font-size: 1.2rem;">Desglose KPI</h3>
                <p id="subtituloModalKPI" style="color: var(--text-muted); font-size: 0.85rem; margin: 5px 0 0 0;">Meta por usuario: 0 pts</p>
            </div>
            <button class="modal-close" onclick="document.getElementById('modalDesgloseKPI').style.display='none'">&times;</button>
        </div>
        <div class="modal-body">
            <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 0.9rem; margin-top: 10px;">
                <thead style="position: sticky; top: 0; background: var(--bg-card);">
                    <tr style="color: var(--text-muted); border-bottom: 1px solid var(--border-color);">
                        <th style="padding: 15px 10px;">Mercaderista</th>
                        <th style="padding: 15px 10px; text-align: right;">Puntos Obtenidos</th>
                    </tr>
                </thead>
                <tbody id="bodyModalKPI">
                    <!-- Filas dinámicas -->
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    let donutChartInstance = null;
    let lineChartInstance = null;
    let datosRankingGlobal = []; 

    document.addEventListener("DOMContentLoaded", () => {
        const hoy = new Date();
        const yyyy = hoy.getFullYear();
        const mm = String(hoy.getMonth() + 1).padStart(2, '0');
        const dd = String(hoy.getDate()).padStart(2, '0');
        const fechaHoy = `${yyyy}-${mm}-${dd}`;
        const fechaPrimerDia = `${yyyy}-${mm}-01`;

        const inputDesde = document.getElementById('filter-desde');
        const inputHasta = document.getElementById('filter-hasta');

        inputDesde.max = fechaHoy;
        inputHasta.max = fechaHoy;
        
        inputDesde.value = fechaPrimerDia;
        // inputHasta.value = fechaHoy;
        const ultimoDiaMes = new Date(yyyy, mm, 0).getDate(); 
        const fechaUltimoDia = `${yyyy}-${mm}-${ultimoDiaMes}`;
        inputHasta.value = fechaUltimoDia;

        cargarDataDashboard();
    });

    async function cargarDataDashboard() {
        let desde = document.getElementById('filter-desde').value;
        let hasta = document.getElementById('filter-hasta').value;
        const region = document.getElementById('filter-region').value;
        const mercaSelect = document.getElementById('filter-merca');
        const mercaderista = mercaSelect.value;

        if (desde > hasta) {
            // alert("La fecha 'Desde' no puede ser mayor a la fecha 'Hasta'. Se ajustará automáticamente el rango.");
            hasta = desde;
            document.getElementById('filter-hasta').value = hasta;
        }

        const fDesde = new Date(desde + "T00:00:00");
        const fHasta = new Date(hasta + "T00:00:00");
        
        document.getElementById('dash-date-text').innerText = `Rango: ${fDesde.toLocaleDateString('es-ES')} a ${fHasta.toLocaleDateString('es-ES')} · Actualizado ahora`;
        document.getElementById('ranking-month-label').innerText = 'Rango Filtrado';

        try {
            const response = await fetch(`api/get_dashboard_main.php?desde=${desde}&hasta=${hasta}&region=${region}&mercaderista=${mercaderista}`);
            const data = await response.json();

            if (data.status === 'success') {
                
                datosRankingGlobal = data.ranking;

                const esIndividual = (mercaderista !== 'all' && data.ranking.length > 0);

                if (esIndividual) {
                    // Vista individual: usar datos reales del usuario
                    const user = data.ranking[0];
                    actualizarTarjetasIndividual(user);

                    // Datos para el donut (reales del usuario)
                    const donutData = {
                        total_pts: user.kpi1_pts + user.kpi2_pts + user.kpi3_pts,
                        max_pts: user.max_kpi1 + user.max_kpi2 + user.max_kpi3,
                        kpi1: user.kpi1_pts,
                        kpi2: user.kpi2_pts,
                        kpi3: user.kpi3_pts,
                        max_kpi1: user.max_kpi1,
                        max_kpi2: user.max_kpi2,
                        max_kpi3: user.max_kpi3,
                        porcentaje: (user.max_kpi1 + user.max_kpi2 + user.max_kpi3) > 0 
                            ? Math.round(((user.kpi1_pts + user.kpi2_pts + user.kpi3_pts) / (user.max_kpi1 + user.max_kpi2 + user.max_kpi3)) * 100) 
                            : 0
                    };
                    actualizarScoreGlobal(data.global, donutData);

                } else {
                    // Vista de equipo: calcular promedios reales
                    const cantidad = data.usuarios_activos.length;
                    // Filtrar usuarios que tienen KPI3 (max_kpi3 > 0)
                    const usuariosConKPI3 = data.ranking.filter(u => u.max_kpi3 > 0);
                    const cantidadKPI3 = usuariosConKPI3.length || 1;


                    if (cantidad === 0) {
                        // Sin datos, mostrar ceros
                        const donutData = {
                            total_pts: 0,
                            max_pts: 1000,
                            kpi1: 0,
                            kpi2: 0,
                            kpi3: 0,
                            max_kpi1: 0,
                            max_kpi2: 0,
                            max_kpi3: 0,
                            porcentaje: 0
                        };
                        actualizarScoreGlobal(data.global, donutData);
                    } else {
                        // Puntajes reales con regla de tres
                        const puntajeKpi1 = data.metas_equipo.kpi1 > 0 ? Math.round((data.suma_real.kpi1 / data.metas_equipo.kpi1) * 400) : 0;
                        const puntajeKpi2 = data.metas_equipo.kpi2 > 0 ? Math.round((data.suma_real.kpi2 / data.metas_equipo.kpi2) * 300) : 0;
                        const puntajeKpi3 = data.metas_equipo.kpi3 > 0 ? Math.round((data.suma_real.kpi3 / data.metas_equipo.kpi3) * 300) : 0;

                        const totalProm = puntajeKpi1 + puntajeKpi2 + puntajeKpi3;
                        const maxProm = 400 + 300 + 300; 

                        const donutData = {
                            total_pts: totalProm,
                            max_pts: maxProm,
                            kpi1: puntajeKpi1,
                            kpi2: puntajeKpi2,
                            kpi3: puntajeKpi3,
                            max_kpi1: 400,
                            max_kpi2: 300,
                            max_kpi3: 300,
                            porcentaje: maxProm > 0 ? Math.round((totalProm / maxProm) * 100) : 0
                        };
                        actualizarScoreGlobal(data.global, donutData);
                    }


                    actualizarTarjetasEquipo(data.suma_real, data.metas_equipo);
                }
                
                renderizarRanking(data.ranking);
                dibujarGraficoSemanas(data.tendencia_semanal, data.etiquetas_semanas);

                // Llenar combo de mercaderistas si es necesario
                if (mercaderista === 'all' && mercaSelect.options.length === 1) {
                    data.usuarios_activos.forEach(user => {
                        const opt = document.createElement('option');
                        opt.value = user;
                        opt.textContent = user;
                        mercaSelect.appendChild(opt);
                    });
                }
            } else {
                alert("Error al cargar el dashboard: " + data.message);
            }
        } catch (error) {
            console.error("Error en petición AJAX", error);
        }
    }

    function actualizarTarjetasEquipo(sumaReal, metasEquipo) {
        // KPI 1
        const puntaje1 = metasEquipo.kpi1 > 0 ? Math.round((sumaReal.kpi1 / metasEquipo.kpi1) * 400) : 0;
        const meta1 = 400; 
        const pct1 = meta1 > 0 ? Math.min(100, Math.round((puntaje1 / meta1) * 100)) : 0;
        document.getElementById('val-pct-1').innerText = pct1;
        document.getElementById('val-pts-1').innerText = puntaje1;
        document.getElementById('val-meta-1').innerText = meta1;
        document.getElementById('bar-top-1').style.width = pct1 + '%';

        // KPI 2
        const puntaje2 = metasEquipo.kpi2 > 0 ? Math.round((sumaReal.kpi2 / metasEquipo.kpi2) * 300) : 0;
        const meta2 = 300;
        const pct2 = meta2 > 0 ? Math.min(100, Math.round((puntaje2 / meta2) * 100)) : 0;
        document.getElementById('val-pct-2').innerText = pct2;
        document.getElementById('val-pts-2').innerText = puntaje2;
        document.getElementById('val-meta-2').innerText = meta2;
        document.getElementById('bar-top-2').style.width = pct2 + '%';

        // KPI 3
        const puntaje3 = metasEquipo.kpi3 > 0 ? Math.round((sumaReal.kpi3 / metasEquipo.kpi3) * 300) : 0;
        const meta3 = 300;
        const pct3 = meta3 > 0 ? Math.min(100, Math.round((puntaje3 / meta3) * 100)) : 0;
        document.getElementById('val-pct-3').innerText = pct3;
        document.getElementById('val-pts-3').innerText = puntaje3;
        document.getElementById('val-meta-3').innerText = meta3;
        document.getElementById('bar-top-3').style.width = pct3 + '%';
    }

    function actualizarTarjetasIndividual(user) {
    // KPI 1
        const pct1 = user.max_kpi1 > 0 ? Math.round((user.kpi1_pts / user.max_kpi1) * 100) : 0;
        document.getElementById('val-pct-1').innerText = pct1;
        document.getElementById('val-pts-1').innerText = user.kpi1_pts;
        document.getElementById('val-meta-1').innerText = user.max_kpi1;
        document.getElementById('bar-top-1').style.width = pct1 + '%';

        // KPI 2
        const pct2 = user.max_kpi2 > 0 ? Math.round((user.kpi2_pts / user.max_kpi2) * 100) : 0;
        document.getElementById('val-pct-2').innerText = pct2;
        document.getElementById('val-pts-2').innerText = user.kpi2_pts;
        document.getElementById('val-meta-2').innerText = user.max_kpi2;
        document.getElementById('bar-top-2').style.width = pct2 + '%';

        // KPI 3
        const pct3 = user.max_kpi3 > 0 ? Math.round((user.kpi3_pts / user.max_kpi3) * 100) : 0;
        document.getElementById('val-pct-3').innerText = pct3;
        document.getElementById('val-pts-3').innerText = user.kpi3_pts;
        document.getElementById('val-meta-3').innerText = user.max_kpi3;
        document.getElementById('bar-top-3').style.width = pct3 + '%';
    }

    function actualizarScoreGlobal(global, donutReal) {
        // --- BADGE: usar global.total_pts (ponderado) ---
        const total = global.total_pts;
        const badge = document.getElementById('global-badge');
        let colorClase = 'estado-nocumple', text = 'NO CUMPLE', mainColor = '#FF3B30';
        if (total >= 900) { colorClase = 'estado-excelente'; text = 'EXCELENTE'; mainColor = '#00FF87'; }
        else if (total >= 750) { colorClase = 'estado-bueno'; text = 'BUENO'; mainColor = '#00A3FF'; }
        else if (total >= 500) { colorClase = 'estado-basico'; text = 'BÁSICO'; mainColor = '#FFB800'; }
        
        badge.className = 'score-badge ' + colorClase;
        badge.innerText = text;
        document.getElementById('donut-total').style.color = mainColor;

        // --- DONUT Y MINI-BARRAS: usar donutReal ---
        const donutTotal = donutReal.total_pts;
        const donutMax = donutReal.max_pts || 1000;
        document.getElementById('donut-total').innerText = donutTotal;

        // Mini-barras
        document.getElementById('mini-val-1').innerText = donutReal.kpi1;
        document.getElementById('mini-bar-1').style.width = (donutReal.max_kpi1 > 0 ? (donutReal.kpi1 / donutReal.max_kpi1) * 100 : 0) + '%';
        
        document.getElementById('mini-val-2').innerText = donutReal.kpi2;
        document.getElementById('mini-bar-2').style.width = (donutReal.max_kpi2 > 0 ? (donutReal.kpi2 / donutReal.max_kpi2) * 100 : 0) + '%';

        document.getElementById('mini-val-3').innerText = donutReal.kpi3;
        document.getElementById('mini-bar-3').style.width = (donutReal.max_kpi3 > 0 ? (donutReal.kpi3 / donutReal.max_kpi3) * 100 : 0) + '%';

        // Gráfico donut
        const ctx = document.getElementById('donutChart').getContext('2d');
        if (donutChartInstance) donutChartInstance.destroy();

        const faltante = donutMax - donutTotal;
        donutChartInstance = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Logrado', 'Faltante'],
                datasets: [{
                    data: [donutTotal, faltante < 0 ? 0 : faltante],
                    backgroundColor: [mainColor, '#1E2536'],
                    borderWidth: 0,
                    cutout: '85%'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false }, tooltip: { enabled: false } },
                animation: { duration: 1500, easing: 'easeOutQuart' }
            }
        });
    }

    // ---------- RANKING ----------
    function renderizarRanking(ranking) {
        const container = document.getElementById('ranking-container');
        container.innerHTML = '';

        if(ranking.length === 0) {
            container.innerHTML = '<p style="color:var(--text-muted); text-align:center;">No hay datos en el rango seleccionado.</p>';
            return;
        }

        ranking.forEach((user, idx) => {
            let colorClase = 'estado-nocumple', textClase = 'text-nocumple', barColor = '#FF3B30';
            if (user.estado === 'EXCELENTE') { colorClase = 'estado-excelente'; textClase = 'text-excelente'; barColor = '#00FF87'; }
            else if (user.estado === 'BUENO') { colorClase = 'estado-bueno'; textClase = 'text-bueno'; barColor = '#00A3FF'; }
            else if (user.estado === 'BÁSICO') { colorClase = 'estado-basico'; textClase = 'text-basico'; barColor = '#FFB800'; }

            const w_pct = (user.total_pts / 1000) * 100;

            const html = `
                <div class="rank-item">
                    <div class="rank-header">
                        <div class="rank-left">
                            <span class="rank-pos">${idx + 1}</span>
                            <div class="rank-avatar">${user.iniciales}</div>
                            <span class="rank-name" title="${user.nombre}">${user.nombre.substring(0,25)}</span>
                        </div>
                        <div class="rank-right">
                            <span class="rank-pts ${textClase}">${user.total_pts}</span>
                            <span class="rank-status ${colorClase}">${user.estado}</span>
                        </div>
                    </div>
                    <div class="progress-bg" style="height:4px;"><div class="progress-bar" style="width:${w_pct}%; background:${barColor}; height:4px;"></div></div>
                </div>
            `;
            container.innerHTML += html;
        });
    }

    function dibujarGraficoSemanas(tendencia) {
        const ctx = document.getElementById('lineChart').getContext('2d');
        if (lineChartInstance) lineChartInstance.destroy();

        const dataKpi1 = tendencia.kpi1.map(v => v !== null ? Math.min(100, v) : null);
        const dataKpi2 = tendencia.kpi2.map(v => v !== null ? Math.min(100, v) : null);
        const dataKpi3 = tendencia.kpi3.map(v => v !== null ? Math.min(100, v) : null);

        lineChartInstance = new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['Semana 1', 'Semana 2', 'Semana 3', 'Total Periodo'],
                labels: ['Sem 1 (1-7)', 'Sem 2 (1-14)', 'Sem 3 (1-21)', 'Total Periodo'],
                datasets: [
                    {
                        label: 'Visita',
                        data: dataKpi1,
                        borderColor: '#00FF87',
                        backgroundColor: 'transparent',
                        tension: 0.4,
                        borderWidth: 3,
                        pointRadius: 4,
                        pointBackgroundColor: '#00FF87',
                        spanGaps: false  
                    },
                    {
                        label: 'Ejecución',
                        data: dataKpi2,
                        borderColor: '#00A3FF',
                        backgroundColor: 'transparent',
                        tension: 0.4,
                        borderWidth: 3,
                        pointRadius: 4,
                        pointBackgroundColor: '#00A3FF',
                        spanGaps: false
                    },
                    {
                        label: 'OC',
                        data: dataKpi3,
                        borderColor: '#B958FF',
                        backgroundColor: 'transparent',
                        tension: 0.4,
                        borderWidth: 3,
                        pointRadius: 4,
                        pointBackgroundColor: '#B958FF',
                        spanGaps: false
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                if (context.parsed.y === null) return null;
                                const valorReal = tendencia[context.dataset.label === 'Visita' ? 'kpi1' : 
                                                        context.dataset.label === 'Ejecución' ? 'kpi2' : 'kpi3'][context.dataIndex];
                                return context.dataset.label + ': ' + valorReal + '%';
                            }
                        }
                    }
                },
                scales: {
                    x: { grid: { display: false, drawBorder: false }, ticks: { color: '#8E96A8', font: {size: 11} } },
                    y: { min: 0, max: 100, grid: { color: '#1E2536', drawBorder: false }, ticks: { color: '#8E96A8', font: {size: 11}, stepSize: 20 } }
                }
            }
        });
    }
    // ---------- MODAL ----------
    window.abrirDesgloseKPI = function(kpiId) {
        if (!datosRankingGlobal || datosRankingGlobal.length === 0) return;

        const modal = document.getElementById('modalDesgloseKPI');
        const tbody = document.getElementById('bodyModalKPI');
        const titulo = document.getElementById('tituloModalKPI');
        const subtitulo = document.getElementById('subtituloModalKPI');
        
        tbody.innerHTML = ''; 
        let llavePts = '', colorMeta = '';

        if (kpiId === 1) {
            titulo.innerText = '📍 Desglose: Efectividad de Visita';
            subtitulo.innerText = 'Meta variable según el rol del usuario (400 pts o 500 pts)';
            llavePts = 'kpi1_pts'; colorMeta = 'var(--kpi1-color)';
        } else if (kpiId === 2) {
            titulo.innerText = '📸 Desglose: Ejecución en PDV';
            subtitulo.innerText = 'Meta variable según el rol del usuario (300 pts o 500 pts)';
            llavePts = 'kpi2_pts'; colorMeta = 'var(--kpi2-color)';
        } else if (kpiId === 3) {
            titulo.innerText = '🛒 Desglose: Órdenes de Compra';
            subtitulo.innerText = 'Meta general: 300 pts (Regla: >70% diario = +15 pts. Canal TIA)';
            llavePts = 'kpi3_pts'; colorMeta = 'var(--kpi3-color)';
        }

        const rankingOrdenado = [...datosRankingGlobal].sort((a, b) => b[llavePts] - a[llavePts]);

        rankingOrdenado.forEach(merca => {
            const puntos = merca[llavePts] || 0;
            
            let metaMerca = 0;
            if (kpiId === 1) metaMerca = merca.max_kpi1;
            if (kpiId === 2) metaMerca = merca.max_kpi2;
            if (kpiId === 3) metaMerca = merca.max_kpi3;

            if (metaMerca === 0) return;

            const porcentaje = metaMerca > 0 ? (puntos / metaMerca) * 100 : 0;
            
            let colorTexto = colorMeta;
            if (porcentaje < 50) colorTexto = '#FF3B30'; 
            else if (porcentaje < 80) colorTexto = '#FFB800'; 

            const fila = `
                <tr style="border-bottom: 1px solid var(--border-color); transition: background 0.2s;">
                    <td style="padding: 15px 10px; color: white; font-weight: 500;">
                        ${merca.nombre}
                    </td>
                    <td style="padding: 15px 10px; text-align: right; font-weight: bold; font-size: 1.1rem; color: ${colorTexto};">
                        ${puntos} <span style="font-size: 0.8rem; color: var(--text-muted); font-weight: normal;">/ ${metaMerca}</span>
                    </td>
                </tr>
            `;
            tbody.insertAdjacentHTML('beforeend', fila);
        });

        modal.style.display = 'flex';
    };
</script>

<?php include 'layout/footer.php'; ?>