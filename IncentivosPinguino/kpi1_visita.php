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
    input[type="date"]::-webkit-calendar-picker-indicator {
        filter: invert(1);
        opacity: 1;
        cursor: pointer;
    }
</style>

<!-- 1. Librerías de Mapa (Leaflet) -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<!-- Controles de Filtro -->
<div class="kpi-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
    <div>
        <h2 style="color: var(--text-main); margin-bottom: 5px; display: flex; align-items: center; gap: 15px;">
            KPI 1 - Efectividad de Visita
            <!-- Nuevo botón dinámico para la vista Micro -->
            <button id="btn-puntos-header" class="btn-puntos" style="display: none;">
                🏆 Ver Puntos Mensuales
            </button>
        </h2>
        <p style="color: var(--text-muted); font-size: 0.9rem;" id="subtitle-info">Cargando datos...</p>
    </div>
    
    <!-- NUEVOS FILTROS DE FECHA RANGO -->
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
            <option value="all">Cargando equipo...</option>
        </select>
    </div>
</div>

<!-- 2. Tarjetas de Resumen (UX Improvement) -->
<div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 20px;">
    <div style="background-color: var(--bg-panel); border: 1px solid var(--brand-border); border-radius: 12px; padding: 20px; display: flex; align-items: center; gap: 15px;">
        <div style="width: 50px; height: 50px; border-radius: 50%; background-color: rgba(255,255,255,0.05); display: flex; align-items: center; justify-content: center; font-size: 1.5rem;">📍</div>
        <div>
            <div style="color: var(--text-muted); font-size: 0.85rem; letter-spacing: 1px;">PDVs ASIGNADOS</div>
            <div id="stat-asignados" style="font-size: 1.5rem; font-weight: bold; color: var(--text-main);">0</div>
        </div>
    </div>
    <div style="background-color: var(--bg-panel); border: 1px solid var(--brand-border); border-radius: 12px; padding: 20px; display: flex; align-items: center; gap: 15px;">
        <div style="width: 50px; height: 50px; border-radius: 50%; background-color: rgba(16,185,129,0.1); display: flex; align-items: center; justify-content: center; font-size: 1.5rem;">✅</div>
        <div>
            <div style="color: var(--text-muted); font-size: 0.85rem; letter-spacing: 1px;">PDVs VISITADOS</div>
            <div id="stat-visitados" style="font-size: 1.5rem; font-weight: bold; color: #10b981;">0</div>
        </div>
    </div>
    <div style="background-color: var(--bg-panel); border: 1px solid var(--brand-border); border-radius: 12px; padding: 20px; display: flex; align-items: center; gap: 15px;">
        <div style="width: 50px; height: 50px; border-radius: 50%; background-color: rgba(14,165,233,0.1); display: flex; align-items: center; justify-content: center; font-size: 1.5rem;">📊</div>
        <div>
            <div style="color: var(--text-muted); font-size: 0.85rem; letter-spacing: 1px;">EFECTIVIDAD</div>
            <div id="stat-efectividad" style="font-size: 1.5rem; font-weight: bold; color: #0ea5e9;">0%</div>
        </div>
    </div>
</div>

<!-- Grid Principal (Mapa y Tabla) -->
<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
    
    <!-- TARJETA MAPA -->
    <div style="background-color: var(--bg-panel); border: 1px solid var(--brand-border); border-radius: 12px; padding: 20px; height: 450px; display: flex; flex-direction: column;">
        <div style="display: flex; justify-content: space-between; margin-bottom: 15px;">
            <h3 style="font-size: 1rem; color: var(--text-muted); letter-spacing: 1px;">MAPA DE COBERTURA</h3>
            <span style="color: var(--brand-accent); font-size: 0.85rem;">● En línea</span>
        </div>
        <!-- Contenedor real del mapa -->
        <div id="map" style="flex: 1; border-radius: 8px; z-index: 1;"></div>
    </div>

    <!-- TARJETA DETALLE PDV -->
    <div style="background-color: var(--bg-panel); border: 1px solid var(--brand-border); border-radius: 12px; padding: 20px; height: 450px; overflow-y: auto;">
        <div style="display: flex; justify-content: space-between; margin-bottom: 15px;">
            <h3 style="font-size: 1rem; color: var(--text-muted); letter-spacing: 1px;">RENDIMIENTO / DETALLE</h3>
        </div>
        
        <table style="width: 100%; text-align: left; border-collapse: collapse; font-size: 0.9rem;">
            <thead>
                <tr style="color: var(--text-muted); border-bottom: 1px solid var(--brand-border);">
                    <!-- Se inyecta por JS -->
                </tr>
            </thead>
            <tbody id="tabla-body">
                <!-- Se inyecta por JS -->
            </tbody>
        </table>
    </div>
</div>

<!-- ESTRUCTURA DEL MODAL DE PUNTOS -->
<div class="modal-overlay" id="puntosModal">
    <div class="modal-content">
        <div class="modal-header">
            <div>
                <h3 style="color: var(--text-main); margin: 0;" id="modal-mercaderista-nombre">Nombre</h3>
                <small style="color: var(--text-muted);">Progreso Mensual de Puntos</small>
            </div>
            <button class="modal-close" onclick="cerrarModal()">&times;</button>
        </div>
        <div class="modal-body">
            
            <!-- Barra de Progreso Mensual -->
            <div style="margin-bottom: 20px;">
                <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                    <span id="modal-meta-label" style="font-size: 0.9rem; color: var(--text-muted);">Meta: 400 pts</span>
                    <span id="modal-puntos-txt" style="font-weight: bold; color: #8b5cf6;">0 / 400 pts</span>
                </div>
                <div style="background-color: var(--bg-dark); border-radius: 10px; height: 16px; width: 100%; overflow: hidden;">
                    <div id="modal-barra-puntos" style="background-color: #8b5cf6; height: 100%; width: 0%; transition: width 0.8s ease;"></div>
                </div>
            </div>

            <!-- Tabla de Desglose Diario -->
            <table style="width: 100%; text-align: left; border-collapse: collapse; font-size: 0.85rem;">
                <thead>
                    <tr style="color: var(--text-muted); border-bottom: 1px solid var(--brand-border);">
                        <th style="padding: 8px;">Día</th>
                        <th style="padding: 8px;">Efectividad</th>
                        <th style="padding: 8px; text-align: right;">Puntos Ganados</th>
                    </tr>
                </thead>
                <tbody id="modal-tabla-dias">
                    <!-- Dinámico -->
                </tbody>
            </table>

        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
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
    const selectRegion = document.getElementById('filtro-region');
    
    let comboBoxLleno = false; 

    // --- CONFIGURACIÓN DEL MAPA ---
    const map = L.map('map').setView([-1.8312, -78.1834], 6);
    
    L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png?key=cb1_2qfb_1_86eaf644dec98677db686f90', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OSM</a>',
        subdomains: 'abcd',
        maxZoom: 19
    }).addTo(map);

    let mapMarkers = []; 

    // --- CONSUMO DE LA API ---
    const cargarDatosKPI = async () => {
        let desde = inputDesde.value;
        let hasta = inputHasta.value;
        const mercaderistaId = selectMercaderista.value;
        const region = selectRegion.value;
        
        // VALIDACIÓN: "Desde" no puede ser mayor que "Hasta"
        if (desde > hasta) {
            // alert("La fecha 'Desde' no puede ser mayor a la fecha 'Hasta'. Se ajustará automáticamente el rango.");
            hasta = desde; 
            inputHasta.value = hasta;
        }

        // Construcción de URL con Rango de Fechas
        let url = `api/get_kpi1_visita.php?desde=${desde}&hasta=${hasta}`;
        
        if (region !== 'all') {
            url += `&region=${region}`;
        }
        if (mercaderistaId !== 'all') {
            url += `&id_usuario=${mercaderistaId}`;
        }

        try {
            const response = await fetch(url);
            const res = await response.json();
            
            if (res.status === 'success') {
                actualizarVista(res);
                if (!comboBoxLleno || mercaderistaId === 'all') {
                    llenarComboBox(res.mercaderistas_activos, mercaderistaId);
                    comboBoxLleno = true;
                }
            } else {
                console.error("Error desde el servidor:", res.message);
            }
        } catch (error) {
            console.error("Error de conexión:", error);
        }
    };

    const llenarComboBox = (mercaderistas, idSeleccionado) => {
        let opciones = `<option value="all">Todos los Mercaderistas (Macro)</option>`;
        for (const [id, nombre] of Object.entries(mercaderistas)) {
            const selected = id === idSeleccionado ? 'selected' : '';
            opciones += `<option value="${id}" ${selected}>${nombre}</option>`;
        }
        selectMercaderista.innerHTML = opciones;
    };

    const actualizarVista = (data) => {
        const { kpi_resumen, detalle_tabla, tipo_vista, data_mapa } = data;

        // 1. Actualizar Tarjetas Top
        document.getElementById('stat-asignados').textContent = kpi_resumen.total_asignado;
        document.getElementById('stat-visitados').textContent = kpi_resumen.total_visitado;
        document.getElementById('stat-efectividad').textContent = `${kpi_resumen.efectividad_porcentaje}%`;

        // 2. Actualizar Tabla
        const thead = document.querySelector('table thead tr');
        const btnHeaderPuntos = document.getElementById('btn-puntos-header');

        if (tipo_vista === 'macro') {
            btnHeaderPuntos.style.display = 'none';
            document.getElementById('subtitle-info').textContent = 'Vista Global - Rango de Fechas';
            
            thead.innerHTML = `
                <th style="padding: 10px 5px;">Mercaderista</th>
                <th style="padding: 10px 5px;">Asignados</th>
                <th style="padding: 10px 5px;">Visitados</th>
                <th style="padding: 10px 5px;">Efectividad</th>
                <th style="padding: 10px 5px;">Estado</th>
                <th style="padding: 10px 5px; text-align: center;">Acción</th>
            `;
        } else {
            btnHeaderPuntos.style.display = 'inline-block';
            const selectMerc = document.getElementById('filtro-mercaderista');
            const idSeleccionado = selectMerc.value;
            const nombreSeleccionado = selectMerc.options[selectMerc.selectedIndex].text;
            
            btnHeaderPuntos.onclick = () => abrirModalPuntos(idSeleccionado, nombreSeleccionado);
            document.getElementById('subtitle-info').textContent = 'Vista Detalle - Histórico del Rango';
            
            thead.innerHTML = `
                <th style="padding: 10px 5px;">Fecha</th>
                <th style="padding: 10px 5px;">PDV</th>
                <th style="padding: 10px 5px;">Zona</th>
                <th style="padding: 10px 5px;">Check-In</th>
                <th style="padding: 10px 5px;">Check-Out</th>
                <th style="padding: 10px 5px;">T. Real</th>
                <th style="padding: 10px 5px;">Estado</th>
            `;
        }

        const tbody = document.getElementById('tabla-body');
        tbody.innerHTML = ''; 
        
        detalle_tabla.forEach(fila => {
            let colorEstado = '#10b981'; 
            if (fila.estado === 'Excedido') colorEstado = '#f59e0b'; 
            if (fila.estado === 'No visitado') colorEstado = '#ef4444'; 

            let tr = '';
            if (tipo_vista === 'macro') {
                tr = `
                    <tr style="border-bottom: 1px solid rgba(255,255,255,0.02);">
                        <td style="padding: 12px 5px; color: var(--text-main); font-weight: 500;">${fila.col1}</td>
                        <td style="padding: 12px 5px; color: var(--text-muted);">${fila.col2}</td>
                        <td style="padding: 12px 5px; color: var(--text-muted);">${fila.col3}</td>
                        <td style="padding: 12px 5px; color: var(--text-main); font-weight: bold;">${fila.col5}</td>
                        <td style="padding: 12px 5px; color: ${colorEstado};">
                            ${fila.estado === 'OK' ? '⊙ OK' : '⊗ Alerta'}
                        </td>
                        <td style="padding: 12px 5px; text-align: center;">
                            <button class="btn-puntos" onclick="abrirModalPuntos('${fila.id_mercaderista}', '${fila.col1}')">🏆 Ver Puntos</button>
                        </td>
                    </tr>
                `;
            } else {
                tr = `
                    <tr style="border-bottom: 1px solid rgba(255,255,255,0.02);">
                        <!-- Columna de Fecha inyectada -->
                        <td style="padding: 12px 5px; color: var(--brand-accent); font-size: 0.8rem;">${fila.fecha_visita}</td>
                        <td style="padding: 12px 5px; color: var(--text-main); font-weight: 500; font-size: 0.85rem;">${fila.col1}</td>
                        <td style="padding: 12px 5px; color: var(--text-muted); font-size: 0.8rem;">${fila.col2}</td>
                        <td style="padding: 12px 5px; color: var(--text-muted);">${fila.col3}</td>
                        <td style="padding: 12px 5px; color: var(--text-muted);">${fila.col4}</td>
                        <td style="padding: 12px 5px; color: var(--text-main); font-weight: bold;">${fila.col5}</td>
                        <td style="padding: 12px 5px; color: ${colorEstado};">
                            ${fila.estado === 'OK' ? '⊙ OK' : (fila.estado === 'Excedido' ? '⚠' : '⊗ Error')}
                        </td>
                    </tr>
                `;
            }
            tbody.insertAdjacentHTML('beforeend', tr);
        });

        // 3. ACTUALIZAR MAPA
        mapMarkers.forEach(marker => map.removeLayer(marker));
        mapMarkers = [];
        
        let bounds = [];

        data_mapa.forEach(punto => {
            if(punto.lat && punto.lng) {
                let colorHex = '#ef4444'; 
                if (punto.estado === 'OK') colorHex = '#10b981'; 
                if (punto.estado === 'Excedido') colorHex = '#f59e0b'; 
                
                const circle = L.circleMarker([punto.lat, punto.lng], {
                    radius: 6,
                    fillColor: colorHex,
                    color: colorHex,
                    weight: 1,
                    opacity: 1,
                    fillOpacity: 0.8
                }).addTo(map);

                circle.bindPopup(`
                    <div style="color: #333; font-family: sans-serif;">
                        <strong>${punto.nombre}</strong><br>
                        Fecha: <b>${punto.fecha}</b><br>
                        Mercaderista: ${punto.mercaderista}<br>
                        Estado: <span style="color: ${colorHex}; font-weight:bold;">${punto.estado}</span>
                    </div>
                `);

                mapMarkers.push(circle);
                bounds.push([punto.lat, punto.lng]);
            }
        });

        if (bounds.length > 0) {
            map.fitBounds(bounds, { padding: [30, 30] });
        }
    };

    // Listeners de Cambios
    inputDesde.addEventListener('change', () => { comboBoxLleno = false; cargarDatosKPI(); });
    inputHasta.addEventListener('change', () => { comboBoxLleno = false; cargarDatosKPI(); });
    
    selectRegion.addEventListener('change', () => {
        comboBoxLleno = false; 
        selectMercaderista.value = 'all'; 
        cargarDatosKPI();
    });

    selectMercaderista.addEventListener('change', cargarDatosKPI);

    cargarDatosKPI();

    window.abrirModalPuntos = async (idMercaderista, nombreMercaderista) => {

        const fechaDesde = document.getElementById('filtro-desde').value;
const fechaReferencia = document.getElementById('filtro-hasta').value;
        document.getElementById('modal-mercaderista-nombre').textContent = nombreMercaderista;
        document.getElementById('puntosModal').classList.add('active');
        
        document.getElementById('modal-tabla-dias').innerHTML = '<tr><td colspan="3" style="text-align:center; padding: 20px;">Calculando mes...</td></tr>';
        
        try {

            const response = await fetch(`api/get_kpi1_puntos.php?id_usuario=${idMercaderista}&desde=${fechaDesde}&hasta=${fechaReferencia}&fecha=${fechaReferencia}`);
            const res = await response.json();
            
            if (res.status === 'success') {
                const puntos = res.resumen_mensual.puntos_actuales;
                const meta = res.resumen_mensual.meta_puntos;
                const porcentaje = res.resumen_mensual.porcentaje_meta;
                
                document.getElementById('modal-meta-label').textContent = `Meta: ${meta} pts`;
                document.getElementById('modal-puntos-txt').textContent = `${puntos} / ${meta} pts`;
                document.getElementById('modal-barra-puntos').style.width = `${porcentaje}%`;

                const tbody = document.getElementById('modal-tabla-dias');
                tbody.innerHTML = '';
                
                res.desglose.forEach(dia => {
                    const colorPuntos = dia.puntos > 0 ? '#10b981' : '#ef4444'; 
                    
                    const tr = `
                        <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                            <td style="padding: 10px 8px; color: var(--text-main);">${dia.fecha}</td>
                            <td style="padding: 10px 8px; color: var(--text-muted);">${dia.efectividad}% (${dia.visitados}/${dia.asignados})</td>
                            <td style="padding: 10px 8px; text-align: right; font-weight: bold; color: ${colorPuntos};">
                                +${dia.puntos} pts
                            </td>
                        </tr>
                    `;
                    tbody.insertAdjacentHTML('beforeend', tr);
                });
            }
        } catch (error) {
            console.error("Error cargando puntos:", error);
        }
    };

    window.cerrarModal = () => {
        document.getElementById('puntosModal').classList.remove('active');
    };

    document.getElementById('puntosModal').addEventListener('click', (e) => {
        if (e.target.id === 'puntosModal') cerrarModal();
    });
});
</script>

<?php include 'layout/footer.php'; ?>