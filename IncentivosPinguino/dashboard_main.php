<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Ejecutivo - KPI 360°</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'sans-serif'] },
                    colors: {
                        darkBg: '#0b101e',
                        cardBg: '#131826',
                        cardBorder: '#1F2937',
                        kpi1: '#00FF87',
                        kpi2: '#00BFFF',
                        kpi3: '#B026FF'
                    }
                }
            }
        }
    </script>
    <style>
        body { background-color: #0b101e; color: #ffffff; }
        .custom-scrollbar::-webkit-scrollbar { width: 6px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: #131826; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #374151; border-radius: 10px; }
        
        /* Efecto hover suave para las tarjetas clickeables */
        .kpi-clickable { transition: transform 0.2s, box-shadow 0.2s; cursor: pointer; }
        .kpi-clickable:hover { transform: translateY(-3px); box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.5); border-color: rgba(255,255,255,0.2); }

        input[type="date"]::-webkit-calendar-picker-indicator {
            filter: invert(1);
            opacity: 1;
            cursor: pointer;
        }
    </style>
</head>
<body class="p-6">

    <!-- HEADER & FILTROS -->
    <div class="flex justify-between items-start mb-6">
        <div>
            <h1 class="text-2xl font-bold text-white mb-1">Dashboard Ejecutivo</h1>
            <p class="text-sm text-gray-400" id="fecha-actualizacion">Actualizando datos...</p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            
            <!-- FILTROS DE FECHA RANGO -->
            <div class="flex items-center gap-2">
                <span class="text-xs text-gray-400">Desde:</span>
                <input type="date" id="filtro-desde" class="bg-cardBg border border-cardBorder text-gray-300 text-sm rounded-lg focus:ring-kpi1 focus:border-kpi1 block p-2 outline-none">
            </div>
            <div class="flex items-center gap-2">
                <span class="text-xs text-gray-400">Hasta:</span>
                <input type="date" id="filtro-hasta" class="bg-cardBg border border-cardBorder text-gray-300 text-sm rounded-lg focus:ring-kpi1 focus:border-kpi1 block p-2 outline-none">
            </div>

            <select id="filtro-region" class="bg-cardBg border border-cardBorder text-gray-300 text-sm rounded-lg focus:ring-kpi1 focus:border-kpi1 block p-2.5 outline-none">
                <option value="all">Todas las Regiones</option>
                <option value="COSTA">Costa</option>
                <option value="SIERRA">Sierra</option>
            </select>

            <select id="filtro-merca" class="bg-cardBg border border-cardBorder text-gray-300 text-sm rounded-lg focus:ring-kpi1 focus:border-kpi1 block p-2.5 outline-none">
                <option value="all">Todo el Equipo</option>
            </select>

            <button onclick="cargarDashboard()" class="bg-gray-800 hover:bg-gray-700 border border-gray-600 text-white px-4 py-2 rounded-lg text-sm flex items-center gap-2 transition">
                <i class="fas fa-filter"></i> Filtrar
            </button>
        </div>
    </div>

    <!-- ROW 1: TARJETAS SUPERIORES ( CLICKEABLES) -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
        
        <!-- Tarjeta KPI 1 (Visita) -->
        <div class="bg-cardBg border border-cardBorder rounded-xl p-5 shadow-lg relative overflow-hidden kpi-clickable" onclick="abrirModalKPI(1)">
            <div class="flex justify-between items-center mb-2">
                <div class="flex items-center gap-2 text-gray-400 text-xs font-bold tracking-wider">
                    <i class="fas fa-map-marker-alt text-kpi1"></i> EFECTIVIDAD DE VISITA
                </div>
                <i class="fas fa-external-link-alt text-gray-600 text-xs"></i>
            </div>
            <div class="text-4xl font-bold text-kpi1 my-3">
                <span id="kpi1-pct">0%</span>
            </div>

            <div class="flex justify-between text-xs text-gray-500 mb-2">
                <span>Puntos</span>
                <span><span id="kpi1-pts-num" class="text-kpi1 font-bold">0</span> / <span id="kpi1-meta-num">400</span> pts</span>
            </div>
            <div class="w-full bg-gray-700 h-1.5 rounded-full">
                <div id="bar-kpi1-top" class="bg-kpi1 h-1.5 rounded-full transition-all duration-1000" style="width: 0%"></div>
            </div>
        </div>

        <!-- Tarjeta KPI 2 (Ejecución) -->
        <div class="bg-cardBg border border-cardBorder rounded-xl p-5 shadow-lg kpi-clickable" onclick="abrirModalKPI(2)">
            <div class="flex justify-between items-center mb-2">
                <div class="flex items-center gap-2 text-gray-400 text-xs font-bold tracking-wider">
                    <i class="fas fa-camera text-kpi2"></i> EJECUCIÓN EN PDV
                </div>
                <i class="fas fa-external-link-alt text-gray-600 text-xs"></i>
            </div>
            <div class="text-4xl font-bold text-kpi2 my-3">
                <span id="kpi2-pct">0%</span>
            </div>

            <div class="flex justify-between text-xs text-gray-500 mb-2">
                <span>Puntos</span>
                <span><span id="kpi2-pts-num" class="text-kpi2 font-bold">0</span> / <span id="kpi2-meta-num">300</span> pts</span>
            </div>

            <div class="w-full bg-gray-700 h-1.5 rounded-full">
                <div id="bar-kpi2-top" class="bg-kpi2 h-1.5 rounded-full transition-all duration-1000" style="width: 0%"></div>
            </div>
        </div>

        <!-- Tarjeta KPI 3 (Órdenes) -->
        <div class="bg-cardBg border border-cardBorder rounded-xl p-5 shadow-lg kpi-clickable" onclick="abrirModalKPI(3)">
            <div class="flex justify-between items-center mb-2">
                <div class="flex items-center gap-2 text-gray-400 text-xs font-bold tracking-wider">
                    <i class="fas fa-shopping-cart text-kpi3"></i> ÓRDENES DE COMPRA
                </div>
                <i class="fas fa-external-link-alt text-gray-600 text-xs"></i>
            </div>
            <div class="text-4xl font-bold text-kpi3 my-3">
                <span id="kpi3-pct">0%</span>
            </div>

            <div class="flex justify-between text-xs text-gray-500 mb-2">
                <span>Puntos</span>
                <span><span id="kpi3-pts-num" class="text-kpi3 font-bold">0</span> / <span id="kpi3-meta-num">300</span> pts</span>
            </div>
            <div class="w-full bg-gray-700 h-1.5 rounded-full">
                <div id="bar-kpi3-top" class="bg-kpi3 h-1.5 rounded-full transition-all duration-1000" style="width: 0%"></div>
            </div>
        </div>
    </div>

    <!-- ROW 2: DETALLES INFERIORES -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- SCORE GLOBAL (Donut) -->
        <div class="bg-cardBg border border-cardBorder rounded-xl p-5 shadow-lg flex flex-col">
            <h2 class="text-gray-400 text-xs font-bold tracking-wider mb-6 uppercase">Score Global · Equipo</h2>
            <div class="relative w-48 h-48 mx-auto mb-6">
                <canvas id="chartDonut"></canvas>
                <div class="absolute inset-0 flex flex-col items-center justify-center">
                    <span id="donut-score" class="text-3xl font-bold text-white">0</span>
                    <span class="text-xs text-gray-500">/ 1000 pts</span>
                </div>
            </div>
            <div class="text-center mb-6">
                <span id="badge-global" class="border border-blue-500 text-blue-400 text-xs font-bold px-3 py-1 rounded">CALCULANDO</span>
                <p class="text-xs text-gray-500 mt-2">Promedio equipo este rango</p>
            </div>
            <div class="mt-auto space-y-4">
                <div>
                    <div class="flex justify-between text-xs mb-1">
                        <span class="text-gray-400">KPI 1 Visita</span>
                        <span id="mini-kpi1" class="text-kpi1 font-bold">0</span>
                    </div>
                    <div class="w-full bg-gray-700 h-1 rounded-full"><div id="mini-bar-kpi1" class="bg-kpi1 h-1 rounded-full" style="width:0%"></div></div>
                </div>
                <div>
                    <div class="flex justify-between text-xs mb-1">
                        <span class="text-gray-400">KPI 2 Ejecución</span>
                        <span id="mini-kpi2" class="text-kpi2 font-bold">0</span>
                    </div>
                    <div class="w-full bg-gray-700 h-1 rounded-full"><div id="mini-bar-kpi2" class="bg-kpi2 h-1 rounded-full" style="width:0%"></div></div>
                </div>
                <div>
                    <div class="flex justify-between text-xs mb-1">
                        <span class="text-gray-400">KPI 3 OC</span>
                        <span id="mini-kpi3" class="text-kpi3 font-bold">0</span>
                    </div>
                    <div class="w-full bg-gray-700 h-1 rounded-full"><div id="mini-bar-kpi3" class="bg-kpi3 h-1 rounded-full" style="width:0%"></div></div>
                </div>
            </div>
        </div>

        <!-- RANKING MERCADERISTAS -->
        <div class="bg-cardBg border border-cardBorder rounded-xl p-5 shadow-lg lg:col-span-1 h-[450px] flex flex-col">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-gray-400 text-xs font-bold tracking-wider uppercase">Ranking Mercaderistas</h2>
                <span class="text-kpi1 text-xs font-bold">Top 10</span>
            </div>
            <div id="contenedor-ranking" class="overflow-y-auto custom-scrollbar pr-2 flex-1 space-y-4">
                <div class="text-center text-gray-500 text-sm py-10">Cargando datos del equipo...</div>
            </div>
        </div>

        <!-- TENDENCIA SEMANAL -->
        <div class="bg-cardBg border border-cardBorder rounded-xl p-5 shadow-lg flex flex-col">
            <h2 class="text-gray-400 text-xs font-bold tracking-wider mb-4 uppercase">Tendencia Semanal (Visualización)</h2>
            <div class="flex-1 w-full relative">
                <canvas id="chartTendencia"></canvas>
            </div>
            <div class="flex justify-center gap-4 mt-4 text-xs text-gray-400">
                <div class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-kpi1"></span> Visita</div>
                <div class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-kpi2"></span> Ejecución</div>
                <div class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-kpi3"></span> OC</div>
            </div>
        </div>
    </div>

    <!-- MODAL PARA DESGLOSE DE PUNTOS POR KPI -->
    <div id="modalDesgloseKPI" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.85); z-index: 9999; align-items: center; justify-content: center;">
        <div class="bg-cardBg border border-cardBorder rounded-xl shadow-2xl flex flex-col overflow-hidden" style="width: 500px; max-width: 90%; max-height: 80vh;">
            
            <div class="flex justify-between items-center p-5 border-b border-gray-800">
                <div>
                    <h3 id="modalTitulo" class="text-white font-bold text-lg">Desglose KPI</h3>
                    <p id="modalSubtitulo" class="text-gray-400 text-xs mt-1">Puntos por Mercaderista</p>
                </div>
                <button onclick="cerrarModal()" class="text-gray-400 hover:text-white transition">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>

            <div class="overflow-y-auto custom-scrollbar p-5">
                <table class="w-full text-left text-sm">
                    <thead class="text-gray-500 text-xs uppercase bg-gray-900 rounded-lg">
                        <tr>
                            <th class="px-4 py-3 rounded-l-lg">Mercaderista</th>
                            <th class="px-4 py-3 rounded-r-lg text-right">Puntos Ganados</th>
                        </tr>
                    </thead>
                    <tbody id="modalBodyRanking">
                        <!-- Llenado dinámico -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- LOGICA JAVASCRIPT -->
    <script>
        let myDonutChart = null;
        let myLineChart = null;
        let datosRankingGlobal = []; 

        document.addEventListener('DOMContentLoaded', () => {
            const opcionesFecha = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
            document.getElementById('fecha-actualizacion').innerText = `Actualizado hoy, ${new Date().toLocaleDateString('es-ES', opcionesFecha)}`;
            
            // CONFIGURACIÓN DE FECHAS Y BLOQUEO DE FUTURO
            const hoy = new Date();
            const yyyy = hoy.getFullYear();
            const mm = String(hoy.getMonth() + 1).padStart(2, '0');
            const dd = String(hoy.getDate()).padStart(2, '0');
            const fechaHoy = `${yyyy}-${mm}-${dd}`;
            const fechaPrimerDia = `${yyyy}-${mm}-01`;

            const inputDesde = document.getElementById('filtro-desde');
            const inputHasta = document.getElementById('filtro-hasta');

            // Prevenir que seleccionen días en el futuro
            inputDesde.max = fechaHoy;
            inputHasta.max = fechaHoy;
            
            // Setear valores por defecto (Desde inicio de mes hasta hoy)
            inputDesde.value = fechaPrimerDia;
            inputHasta.value = fechaHoy;

            dibujarGraficaTendenciaMock();
            cargarDashboard();
        });

        async function cargarDashboard() {
            const region = document.getElementById('filtro-region').value;
            const mercaderista = document.getElementById('filtro-merca').value;
            let desde = document.getElementById('filtro-desde').value;
            let hasta = document.getElementById('filtro-hasta').value;

            // VALIDACIÓN: "Desde" no puede ser mayor que "Hasta"
            if (desde > hasta) {
                // alert("La fecha 'Desde' no puede ser mayor a la fecha 'Hasta'. Se ajustará el rango automáticamente.");
                hasta = desde;
                document.getElementById('filtro-hasta').value = hasta;
            }

            try {
                // ENDPOINT (Ahora enviamos desde y hasta)
                const url = `api/get_dashboard_main.php?region=${region}&mercaderista=${mercaderista}&desde=${desde}&hasta=${hasta}`;
                const response = await fetch(url);
                const data = await response.json();

                if (data.status === 'success') {
                    datosRankingGlobal = data.ranking;

                    if (mercaderista !== 'all' && data.ranking.length > 0) {
                        // Vista individual
                        actualizarTarjetasSuperioresIndividual(data.ranking[0]);
                    } else {
                        // Vista de equipo
                        actualizarTarjetasSuperiores(data.suma_real, data.metas_equipo);
                    }

                    dibujarDonutGlobal(data.global); 
                    renderizarRanking(data.ranking);
                    llenarSelectMercaderistas(data.usuarios_activos, mercaderista);
                }
            } catch (error) {
                console.error("Error conectando con la API:", error);
            }
        }

        // Nueva función para poblar el combo de mercaderistas si no está lleno
        function llenarSelectMercaderistas(usuarios, seleccionado) {
            const select = document.getElementById('filtro-merca');
            if (select.options.length > 1 && seleccionado !== 'all') return; 
            
            let opciones = `<option value="all">Todo el Equipo</option>`;
            usuarios.forEach(usr => {
                const sel = (usr === seleccionado) ? 'selected' : '';
                opciones += `<option value="${usr}" ${sel}>${usr}</option>`;
            });
            select.innerHTML = opciones;
        }


        function actualizarTarjetasSuperiores(sumaReal, metasEquipo) {
            // KPI 1
            const pct1 = metasEquipo.kpi1 > 0 ? Math.round((sumaReal.kpi1 / metasEquipo.kpi1) * 100) : 0;
            document.getElementById('kpi1-pct').innerText = pct1 + '%';
            document.getElementById('kpi1-pts-num').innerText = sumaReal.kpi1;
            document.getElementById('kpi1-meta-num').innerText = metasEquipo.kpi1;
            document.getElementById('bar-kpi1-top').style.width = pct1 + '%';

            // KPI 2
            const pct2 = metasEquipo.kpi2 > 0 ? Math.round((sumaReal.kpi2 / metasEquipo.kpi2) * 100) : 0;
            document.getElementById('kpi2-pct').innerText = pct2 + '%';
            document.getElementById('kpi2-pts-num').innerText = sumaReal.kpi2;
            document.getElementById('kpi2-meta-num').innerText = metasEquipo.kpi2;
            document.getElementById('bar-kpi2-top').style.width = pct2 + '%';

            console.log("sumaReal.kpi2:", sumaReal.kpi2);
            console.log("metasEquipo.kpi2:", metasEquipo.kpi2);
            console.log("puntaje2:", puntaje2);

            // KPI 3
            const pct3 = metasEquipo.kpi3 > 0 ? Math.round((sumaReal.kpi3 / metasEquipo.kpi3) * 100) : 0;
            document.getElementById('kpi3-pct').innerText = pct3 + '%';
            document.getElementById('kpi3-pts-num').innerText = sumaReal.kpi3;
            document.getElementById('kpi3-meta-num').innerText = metasEquipo.kpi3;
            document.getElementById('bar-kpi3-top').style.width = pct3 + '%';
        }

        function actualizarTarjetasSuperioresIndividual(user) {
            const pct1 = user.max_kpi1 > 0 ? Math.round((user.kpi1_pts / user.max_kpi1) * 100) : 0;
            document.getElementById('kpi1-pct').innerText = pct1 + '%';
            document.getElementById('kpi1-pts-num').innerText = user.kpi1_pts;
            document.getElementById('kpi1-meta-num').innerText = user.max_kpi1;
            document.getElementById('bar-kpi1-top').style.width = pct1 + '%';

            const pct2 = user.max_kpi2 > 0 ? Math.round((user.kpi2_pts / user.max_kpi2) * 100) : 0;
            document.getElementById('kpi2-pct').innerText = pct2 + '%';
            document.getElementById('kpi2-pts-num').innerText = user.kpi2_pts;
            document.getElementById('kpi2-meta-num').innerText = user.max_kpi2;
            document.getElementById('bar-kpi2-top').style.width = pct2 + '%';

            const pct3 = user.max_kpi3 > 0 ? Math.round((user.kpi3_pts / user.max_kpi3) * 100) : 0;
            document.getElementById('kpi3-pct').innerText = pct3 + '%';
            document.getElementById('kpi3-pts-num').innerText = user.kpi3_pts;
            document.getElementById('kpi3-meta-num').innerText = user.max_kpi3;
            document.getElementById('bar-kpi3-top').style.width = pct3 + '%';
        }

        function dibujarDonutGlobal(global) {
            document.getElementById('donut-score').innerText = global.total_pts;
            
            document.getElementById('mini-kpi1').innerText = global.kpi1.pts;
            document.getElementById('mini-bar-kpi1').style.width = (global.kpi1.pts / 400 * 100) + '%';
            
            document.getElementById('mini-kpi2').innerText = global.kpi2.pts;
            document.getElementById('mini-bar-kpi2').style.width = (global.kpi2.pts / 300 * 100) + '%';
            
            document.getElementById('mini-kpi3').innerText = global.kpi3.pts;
            document.getElementById('mini-bar-kpi3').style.width = (global.kpi3.pts / 300 * 100) + '%';

            let colorDonut = '#FF3B30'; let textoBadge = 'NO CUMPLE'; let claseBadge = 'border-red-500 text-red-400';
            if (global.total_pts >= 900) { colorDonut = '#00FF87'; textoBadge = 'EXCELENTE'; claseBadge = 'border-green-500 text-green-400'; }
            else if (global.total_pts >= 750) { colorDonut = '#00BFFF'; textoBadge = 'BUENO'; claseBadge = 'border-blue-500 text-blue-400'; }
            else if (global.total_pts >= 500) { colorDonut = '#FFB800'; textoBadge = 'BÁSICO'; claseBadge = 'border-yellow-500 text-yellow-400'; }

            const badgeElement = document.getElementById('badge-global');
            badgeElement.className = `border text-xs font-bold px-3 py-1 rounded ${claseBadge}`;
            badgeElement.innerText = textoBadge;

            const ctx = document.getElementById('chartDonut').getContext('2d');
            if (myDonutChart) { myDonutChart.destroy(); }

            myDonutChart = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['Logrado', 'Faltante'],
                    datasets: [{
                        data: [global.total_pts, 1000 - global.total_pts],
                        backgroundColor: [colorDonut, '#1F2937'],
                        borderWidth: 0, cutout: '85%', borderRadius: 5
                    }]
                },
                options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false }, tooltip: { enabled: false } }, animation: { animateScale: true, animateRotate: true } }
            });
        }

        function renderizarRanking(rankingList) {
            const contenedor = document.getElementById('contenedor-ranking');
            contenedor.innerHTML = ''; 

            if(rankingList.length === 0){
                contenedor.innerHTML = '<div class="text-center text-gray-500 py-10">No hay datos para este rango de fechas.</div>';
                return;
            }

            rankingList.forEach((user, index) => {
                let colorClase = 'text-red-400'; let bgBarra = '#FF3B30'; let bordeBadge = 'border-red-500';
                if (user.estado === 'EXCELENTE') { colorClase = 'text-[#00FF87]'; bgBarra = '#00FF87'; bordeBadge = 'border-[#00FF87] text-[#00FF87]'; }
                else if (user.estado === 'BUENO') { colorClase = 'text-[#00BFFF]'; bgBarra = '#00BFFF'; bordeBadge = 'border-[#00BFFF] text-[#00BFFF]'; }
                else if (user.estado === 'BÁSICO') { colorClase = 'text-[#FFB800]'; bgBarra = '#FFB800'; bordeBadge = 'border-[#FFB800] text-[#FFB800]'; }

                const porcentajeWidth = (user.total_pts / 1000) * 100;

                const rowHTML = `
                    <div class="mb-3">
                        <div class="flex items-center justify-between mb-2">
                            <div class="flex items-center gap-3">
                                <span class="text-yellow-500 font-bold w-4 text-center">${index + 1}</span>
                                <div class="w-7 h-7 rounded-full bg-gray-800 text-white flex items-center justify-center font-bold text-xs border border-gray-600">
                                    ${user.iniciales}
                                </div>
                                <span class="text-white text-sm font-semibold truncate w-32 md:w-48" title="${user.nombre}">${user.nombre}</span>
                            </div>
                            <div class="flex items-center gap-3">
                                <span class="font-bold ${colorClase}">${user.total_pts}</span>
                                <span class="border px-2 py-0.5 text-[10px] rounded ${bordeBadge} w-16 text-center">${user.estado}</span>
                            </div>
                        </div>
                        <div class="w-full bg-gray-700 h-1 rounded-full pl-8">
                            <div class="h-1 rounded-full transition-all duration-1000" style="width: ${porcentajeWidth}%; background-color: ${bgBarra}"></div>
                        </div>
                    </div>
                `;
                contenedor.insertAdjacentHTML('beforeend', rowHTML);
            });
        }

        // LÓGICA DEL MODAL
        function abrirModalKPI(numKPI) {
            const modal = document.getElementById('modalDesgloseKPI');
            const tbody = document.getElementById('modalBodyRanking');
            const titulo = document.getElementById('modalTitulo');
            const subtitulo = document.getElementById('modalSubtitulo');
            
            tbody.innerHTML = ''; 
            let llavePuntos = ''; let meta = 0; let colorHighlights = '';

            if (numKPI === 1) {
                titulo.innerHTML = '<i class="fas fa-map-marker-alt text-kpi1 mr-2"></i>Efectividad de Visita';
                subtitulo.innerText = 'Meta variable por usuario (400 o 500 pts; 20 pts diarios)';
                llavePuntos = 'kpi1_pts'; colorHighlights = '#00FF87';
            } else if (numKPI === 2) {
                titulo.innerHTML = '<i class="fas fa-camera text-kpi2 mr-2"></i>Ejecución en PDV';
                subtitulo.innerText = 'Meta variable por usuario (300 o 500 pts)';
                llavePuntos = 'kpi2_pts'; colorHighlights = '#00BFFF';
            } else if (numKPI === 3) {
                titulo.innerHTML = '<i class="fas fa-shopping-cart text-kpi3 mr-2"></i>Órdenes de Compra';
                subtitulo.innerText = 'Meta por usuario: 300 pts';
                llavePuntos = 'kpi3_pts'; colorHighlights = '#B026FF';
            }

            datosRankingGlobal.forEach(merca => {
                const meta = numKPI === 1 ? merca.max_kpi1 : numKPI === 2 ? merca.max_kpi2 : merca.max_kpi3;
                if (meta === 0) return;

                const pts = merca[llavePuntos] || 0;
                let colorTexto = pts >= (meta * 0.7) ? colorHighlights : (pts >= (meta * 0.4) ? '#FFB800' : '#FF3B30');

                const fila = `
                    <tr class="border-b border-gray-800 hover:bg-gray-800 transition">
                        <td class="px-4 py-3 font-medium text-gray-200 flex items-center gap-2">
                            <div class="w-6 h-6 rounded-full bg-gray-700 text-xs flex items-center justify-center">${merca.iniciales}</div>
                            ${merca.nombre}
                        </td>
                        <td class="px-4 py-3 text-right font-bold" style="color: ${colorTexto};">
                            ${pts} <span class="text-gray-500 text-xs font-normal">/ ${meta}</span>
                        </td>
                    </tr>
                `;
                tbody.insertAdjacentHTML('beforeend', fila);
            });

            modal.style.display = 'flex';
        }

        function cerrarModal() {
            document.getElementById('modalDesgloseKPI').style.display = 'none';
        }

        document.getElementById('modalDesgloseKPI').addEventListener('click', function(e) {
            if (e.target === this) cerrarModal();
        });

        function dibujarGraficaTendenciaMock() {
            const ctx = document.getElementById('chartTendencia').getContext('2d');
            const gradient1 = ctx.createLinearGradient(0, 0, 0, 400); gradient1.addColorStop(0, 'rgba(0, 255, 135, 0.2)'); gradient1.addColorStop(1, 'rgba(0, 255, 135, 0)');
            const gradient2 = ctx.createLinearGradient(0, 0, 0, 400); gradient2.addColorStop(0, 'rgba(0, 191, 255, 0.2)'); gradient2.addColorStop(1, 'rgba(0, 191, 255, 0)');
            const gradient3 = ctx.createLinearGradient(0, 0, 0, 400); gradient3.addColorStop(0, 'rgba(176, 38, 255, 0.2)'); gradient3.addColorStop(1, 'rgba(176, 38, 255, 0)');

            myLineChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: ['Sem 1', 'Sem 2', 'Sem 3', 'Sem 4'],
                    datasets: [
                        { label: 'Visita', data: [73, 78, 65, 85], borderColor: '#00FF87', backgroundColor: gradient1, tension: 0.4, fill: true, borderWidth: 2, pointRadius: 0 },
                        { label: 'Ejecución', data: [80, 85, 75, 92], borderColor: '#00BFFF', backgroundColor: gradient2, tension: 0.4, fill: true, borderWidth: 2, pointRadius: 0 },
                        { label: 'OC', data: [88, 92, 82, 98], borderColor: '#B026FF', backgroundColor: gradient3, tension: 0.4, fill: true, borderWidth: 2, pointRadius: 0 }
                    ]
                },
                options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { x: { grid: { display: false } }, y: { grid: { color: '#1F2937' }, min: 50, max: 100 } } }
            });
        }
    </script>
</body>
</html>