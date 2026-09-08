$(document).ready(function() {
    // Obtener parámetros de la URL
    var urlParams = new URLSearchParams(window.location.search);
    var usuarioParam = urlParams.get('usuario');
    
    console.log("=== kpis_view.js ===");
    console.log("usuarioParam:", usuarioParam);
    
    var gestores = [];
    var puntosVenta = [];
    
    // ✅ FIX PARA WEBVIEW - Forzar scroll en tablas
    function fixWebViewScroll() {
        var isWebView = window.navigator.userAgent.indexOf('wv') > -1 || 
                       window.navigator.userAgent.indexOf('WebView') > -1;
        
        if (isWebView) {
            console.log("📱 Ejecutando en WebView - Aplicando fixes");
            
            $('body').css({
                'overflow-x': 'auto',
                '-webkit-overflow-scrolling': 'touch'
            });
            
            $('.table-responsive-wrapper').each(function() {
                $(this).css({
                    'overflow-x': 'auto',
                    '-webkit-overflow-scrolling': 'touch',
                    'display': 'block',
                    'width': '100%'
                });
            });
        }
    }
    
    // Ejecutar fix
    fixWebViewScroll();
    
    // Establecer fechas por defecto
    function setDefaultDates() {
        var today = new Date();
        var sevenDaysAgo = new Date(today);
        sevenDaysAgo.setDate(today.getDate() - 7);
        
        var formatDate = function(date) {
            var year = date.getFullYear();
            var month = String(date.getMonth() + 1).padStart(2, '0');
            var day = String(date.getDate()).padStart(2, '0');
            return year + '-' + month + '-' + day;
        };
        
        $('#f_inicio').val(formatDate(sevenDaysAgo));
        $('#f_fin').val(formatDate(today));
        
        console.log("Fechas por defecto - Inicio:", formatDate(sevenDaysAgo), "Fin:", formatDate(today));
    }
    setDefaultDates();
    
    // Cargar mercaderistas
    function cargarGestores() {
        console.log("Cargando gestores para usuario:", usuarioParam);
        
        if (!usuarioParam || usuarioParam === 'null' || usuarioParam === '') {
            alert('No se ha especificado un usuario');
            console.error("ERROR: usuario no especificado");
            $('#select_gestor').html('<option value="">No se especificó usuario</option>');
            return;
        }
        
        var url = '/App/XploraEcuador/mantenimiento_bases/colgate_nuevo/getters/getGestoresPorSupervisor.php';
        console.log("URL getGestoresPorSupervisor:", url);
        
        var data = {
            usuario: usuarioParam
        };
        
        console.log("Datos enviados:", data);
        
        $.ajax({
            url: url,
            method: 'POST',
            data: data,
            dataType: 'json',
            success: function(data) {
                console.log("Respuesta getGestoresPorSupervisor:", data);
                console.log("Cantidad de gestores:", data ? data.length : 0);
                
                if (!data || data.length === 0) {
                    console.warn("No se encontraron gestores para usuario:", usuarioParam);
                    $('#select_gestor').html('<option value="">No hay mercaderistas disponibles</option>');
                    return;
                }
                
                gestores = data;
                var select = $('#select_gestor');
                select.html('<option value="">-- Seleccione un mercaderista --</option>');
                
                $.each(data, function(index, gestor) {
                    console.log("Agregando gestor:", gestor.id, gestor.nombre, gestor.usuario);
                    select.append('<option value="' + gestor.id + '">' + gestor.nombre + '</option>');
                });
            },
            error: function(xhr, status, error) {
                console.error("Error al cargar gestores:", error);
                console.error("Status:", status);
                console.error("Response:", xhr.responseText);
                $('#select_gestor').html('<option value="">Error al cargar mercaderistas</option>');
            }
        });
    }
    
    // Buscar puntos de venta
    function buscarPuntosVenta() {
        var f_inicio = $('#f_inicio').val();
        var f_fin = $('#f_fin').val();
        var gestorId = $('#select_gestor').val();
        
        console.log("=== buscarPuntosVenta ===");
        console.log("f_inicio:", f_inicio);
        console.log("f_fin:", f_fin);
        console.log("gestorId:", gestorId);
        console.log("usuarioParam:", usuarioParam);
        
        if (!f_inicio || !f_fin) {
            alert('Debe seleccionar fechas de inicio y fin');
            return;
        }
        
        if (!gestorId) {
            alert('Debe seleccionar un mercaderista');
            return;
        }
        
        // Buscar el nombre del gestor seleccionado
        var gestorNombre = '';
        var gestorUsuario = '';
        $.each(gestores, function(index, g) {
            if (g.id == gestorId) {
                gestorNombre = g.nombre;
                gestorUsuario = g.usuario || '';
                console.log("Gestor seleccionado:", g);
            }
        });
        
        console.log("Gestor nombre:", gestorNombre);
        console.log("Gestor usuario:", gestorUsuario);
        
        $('#loadingSpinner').show();
        $('#tablaContainer').html('');
        
        var url = '/App/XploraEcuador/mantenimiento_bases/colgate_nuevo/getters/getPuntosVentaPorGestor.php';
        console.log("URL getPuntosVentaPorGestor:", url);
        
        var data = {
            usuario: usuarioParam,
            gestor_id: gestorId,
            fecha_inicio: f_inicio,
            fecha_fin: f_fin
        };
        
        console.log("Datos enviados:", data);
        
        $.ajax({
            url: url,
            method: 'POST',
            data: data,
            dataType: 'json',
            success: function(data) {
                console.log("Respuesta getPuntosVentaPorGestor:", data);
                console.log("Cantidad de PDVs:", data ? data.length : 0);
                
                $('#loadingSpinner').hide();
                puntosVenta = data;
                
                if (!data || data.length === 0) {
                    console.warn("No se encontraron PDVs para el período seleccionado");
                    $('#tablaContainer').html(`
                        <div class="no-data">
                            <i class="fa fa-info-circle"></i>
                            <p>No se encontraron puntos de venta para el período seleccionado</p>
                        </div>
                    `);
                    return;
                }
                
                // ✅ TABLA ENVOLVIDA EN CONTENEDOR RESPONSIVE
                var html = `
                    <div class="table-responsive-wrapper">
                        <table class="table-kpis">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Punto de Venta</th>
                                    <th>Fecha</th>
                                    <th>Hora Inicio</th>
                                    <th>Hora Fin</th>
                                    <th>Estado</th>
                                    <th>Acción</th>
                                </tr>
                            </thead>
                            <tbody>
                `;
                
                $.each(data, function(index, pdv) {
                    console.log("PDV:", index, pdv.nombre, pdv.fecha, pdv.estado);
                    
                    var estadoClass = 'estado-atendido';
                    if (pdv.estado === 'EN PROCESO') estadoClass = 'estado-proceso';
                    else if (pdv.estado === 'NO VISITADO') estadoClass = 'estado-novisitado';
                    else if (pdv.estado === 'JUSTIFICADO') estadoClass = 'estado-justificado';
                    
                    html += `
                        <tr>
                            <td>${index + 1}</td>
                            <td>${pdv.nombre || pdv.pos_name || 'Sin nombre'}</td>
                            <td>${pdv.fecha || '-'}</td>
                            <td>${pdv.h_ini || '-'}</td>
                            <td>${pdv.h_fin || '-'}</td>
                            <td><span class="estado-badge ${estadoClass}">${pdv.estado || 'PENDIENTE'}</span></td>
                            <td>
                                <button class="btn-ver-kpis" data-index="${index}">
                                    <i class="fa fa-clipboard"></i> Ver KPIs
                                </button>
                            </td>
                        </tr>
                    `;
                });
                
                html += `
                            </tbody>
                        </table>
                    </div>
                `;
                
                $('#tablaContainer').html(html);
                
                // ✅ Forzar scroll en WebView después de cargar la tabla
                setTimeout(function() {
                    fixWebViewScroll();
                }, 100);
                
                // Eventos para botones Ver KPIs
                $('.btn-ver-kpis').on('click', function() {
                    var index = $(this).data('index');
                    var pdvData = puntosVenta[index];

                    console.log("=== VER KPIs ===");
                    console.log("PDV seleccionado:", pdvData);
                    console.log("Código:", pdvData.codigo);
                    console.log("Fecha:", pdvData.fecha);
                    console.log("Gestor nombre:", pdvData.gestor_nombre);
                    console.log("Gestor usuario:", pdvData.gestor_usuario);
                    var gestorNombreParaModal = pdvData.gestor_usuario || 'No disponible';
                    var supervisorNombre = 'Supervisor: ' + (usuarioParam || 'No disponible');
                    
                    mostrarKPIS(pdvData, gestorNombreParaModal, supervisorNombre);
                });
            },
            error: function(xhr, status, error) {
                console.error("Error en getPuntosVentaPorGestor:", error);
                console.error("Status:", status);
                console.error("Response:", xhr.responseText);
                
                $('#loadingSpinner').hide();
                $('#tablaContainer').html(`
                    <div class="no-data" style="color:#e74c3c;">
                        <i class="fa fa-exclamation-triangle"></i>
                        <p>Error al cargar los datos: ${error}</p>
                        <small>${xhr.responseText}</small>
                    </div>
                `);
            }
        });
    }
    
    // Mostrar modal de KPIS
    function mostrarKPIS(pdvData, gestorNombre, supervisorNombre) {
        $('#modalKPISBody').html(`
            <div class="text-center" style="padding: 40px;">
                <i class="fa fa-spinner fa-spin fa-3x"></i>
                <p>Cargando KPIs para ${pdvData.nombre || pdvData.pos_name || 'PDV'}...</p>
            </div>
        `);
        $('#modalKPIS').modal('show');
        
        var codigoPdv = pdvData.codigo || pdvData.pos_id || pdvData.id;
        var fechaPdv = pdvData.fecha || '';
        var nombreGestor = pdvData.gestor_usuario || gestorNombre || '';

        var url = '/App/XploraEcuador/mantenimiento_bases/colgate_nuevo/getters/getKPIS.php';
        
        console.log("Llamando a getKPIS.php con:", {
            codigo: codigoPdv,
            fecha: fechaPdv,
            usuario: nombreGestor
        });
        
        $.ajax({
            url: url,
            method: 'GET',
            data: {
                codigo: codigoPdv,
                fecha: fechaPdv,
                usuario: nombreGestor
            },
            dataType: 'json',
            success: function(data) {
                console.log("Respuesta getKPIS:", data);
                var kpisContent = generarContenidoKPIS(data, pdvData, gestorNombre, supervisorNombre);
                $('#modalKPISBody').html(kpisContent);
            },
            error: function(xhr, status, error) {
                console.error("Error en getKPIS:", error);
                console.error("Status:", status);
                console.error("Response:", xhr.responseText);
                
                $('#modalKPISBody').html(`
                    <div class="alert alert-danger">
                        <i class="fa fa-exclamation-triangle"></i>
                        Error al cargar las categorías: ${error}
                        <br><small>Respuesta: ${xhr.responseText}</small>
                    </div>
                `);
            }
        });
    }
    
    // Generar contenido del modal KPIS
    function generarContenidoKPIS(data, pdvData, gestorNombre, supervisorNombre) {
        var fotos = { ENTRADA: '', SALIDA: '' };
        var kpis_categorias = [];
        
        if (data && data.fotos) {
            fotos = data.fotos;
        }
        
        if (data && data.estados && Array.isArray(data.estados)) {
            kpis_categorias = data.estados.map(function(estado) {
                return {
                    nombre: estado.categoria,
                    tipo_cat: estado.tipo_cat || '',
                    prec: estado.precio ? 1 : 0,
                    presn: estado.presencia ? 1 : 0,
                    sos: estado.shareshelf ? 1 : 0,
                    promos: estado.promocion ? 1 : 0,
                    exh: estado.exhibicion ? 1 : 0
                };
            });
        }
        
        if (kpis_categorias.length === 0) {
            return `
                <div class="kpis-modal-content">
                    <div class="kpis-main-layout">
                        <div class="kpis-left-column">
                            <div class="pdv-section">
                                <h4 class="pdv-title">PUNTO DE VENTA (${pdvData.fecha || 'Sin fecha'})</h4>
                                <h4 class="pdv-name">${pdvData.nombre || pdvData.pos_name || 'Sin nombre'}</h4>
                            </div>
                            <div class="gestor-section">
                                <div class="gestor-header"><strong>DATOS GENERALES DEL GESTOR</strong></div>
                                <p><strong>Gestor:</strong> ${gestorNombre || 'No disponible'}</p>
                                <p><strong>Supervisor Responsable:</strong> ${supervisorNombre || 'No disponible'}</p>
                            </div>
                        </div>
                        <div class="kpis-right-column">
                            <div class="fotos-container">
                                <div class="foto-item">
                                    <h5 class="foto-label">INGRESO</h5>
                                    <div class="foto-box">
                                        ${fotos.ENTRADA ? `<img src="${fotos.ENTRADA}" class="foto-img">` : '<p class="foto-placeholder">No disponible</p>'}
                                    </div>
                                </div>
                                <div class="foto-item">
                                    <h5 class="foto-label">SALIDA</h5>
                                    <div class="foto-box">
                                        ${fotos.SALIDA ? `<img src="${fotos.SALIDA}" class="foto-img">` : '<p class="foto-placeholder">No disponible</p>'}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <hr class="kpis-divider">
                    <div class="alert alert-info">
                        <i class="fa fa-info-circle"></i>
                        No se encontraron categorías reportadas para este PDV en la fecha ${pdvData.fecha || 'seleccionada'}
                    </div>
                </div>
            `;
        }
        
        // Calcular porcentajes
        var totalCategorias = kpis_categorias.length;
        var precCount = 0, presnCount = 0, sosCount = 0, promosCount = 0, exhCount = 0;
        
        $.each(kpis_categorias, function(i, cat) {
            if (cat.prec) precCount++;
            if (cat.presn) presnCount++;
            if (cat.sos) sosCount++;
            if (cat.promos) promosCount++;
            if (cat.exh) exhCount++;
        });
        
        var precPorcentaje = totalCategorias > 0 ? Math.round((precCount / totalCategorias) * 100) : 0;
        var presnPorcentaje = totalCategorias > 0 ? Math.round((presnCount / totalCategorias) * 100) : 0;
        var sosPorcentaje = totalCategorias > 0 ? Math.round((sosCount / totalCategorias) * 100) : 0;
        var promosPorcentaje = totalCategorias > 0 ? Math.round((promosCount / totalCategorias) * 100) : 0;
        var exhPorcentaje = totalCategorias > 0 ? Math.round((exhCount / totalCategorias) * 100) : 0;
        
        var html = `
            <div class="kpis-modal-content">
                <div class="kpis-main-layout">
                    <div class="kpis-left-column">
                        <div class="pdv-section">
                            <h4 class="pdv-title">PUNTO DE VENTA (${pdvData.fecha || 'Sin fecha'})</h4>
                            <h4 class="pdv-name">${pdvData.nombre || pdvData.pos_name || 'Sin nombre'}</h4>
                        </div>
                        <div class="gestor-section">
                            <div class="gestor-header"><strong>DATOS GENERALES DEL GESTOR</strong></div>
                            <p><strong>Gestor:</strong> ${gestorNombre || 'No disponible'}</p>
                            <p><strong>Supervisor Responsable:</strong> ${supervisorNombre || 'No disponible'}</p>
                        </div>
                    </div>
                    <div class="kpis-right-column">
                        <div class="fotos-container">
                            <div class="foto-item">
                                <h5 class="foto-label">INGRESO</h5>
                                <div class="foto-box">
                                    ${fotos.ENTRADA ? `<img src="${fotos.ENTRADA}" class="foto-img">` : '<p class="foto-placeholder">Foto entrada</p>'}
                                </div>
                            </div>
                            <div class="foto-item">
                                <h5 class="foto-label">SALIDA</h5>
                                <div class="foto-box">
                                    ${fotos.SALIDA ? `<img src="${fotos.SALIDA}" class="foto-img">` : '<p class="foto-placeholder">Foto salida</p>'}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <hr class="kpis-divider">
                <div class="kpis-table-section">
                    <div class="table-responsive-wrapper">
                        <table class="kpis-table">
                            <thead>
                                <tr>
                                    <th>CATEGORÍAS</th>
                                    <th>TIPO CAT</th>
                                    <th>PREC</th>
                                    <th>PRESN</th>
                                    <th>SOS</th>
                                    <th>PROMOS</th>
                                    <th>EXH</th>
                                </tr>
                            </thead>
                            <tbody>
        `;
        
        $.each(kpis_categorias, function(i, cat) {
            html += `
                <tr>
                    <td style="font-weight:bold;">${cat.nombre}</td>
                    <td>${cat.tipo_cat || ''}</td>
                    <td class="kpi-indicator ${cat.prec ? 'kpi-yes' : 'kpi-no'}">${cat.prec ? '<span class="check-box">✓</span>' : ''}</td>
                    <td class="kpi-indicator ${cat.presn ? 'kpi-yes' : 'kpi-no'}">${cat.presn ? '<span class="check-box">✓</span>' : ''}</td>
                    <td class="kpi-indicator ${cat.sos ? 'kpi-yes' : 'kpi-no'}">${cat.sos ? '<span class="check-box">✓</span>' : ''}</td>
                    <td class="kpi-indicator ${cat.promos ? 'kpi-yes' : 'kpi-no'}">${cat.promos ? '<span class="check-box">✓</span>' : ''}</td>
                    <td class="kpi-indicator ${cat.exh ? 'kpi-yes' : 'kpi-no'}">${cat.exh ? '<span class="check-box">✓</span>' : ''}</td>
                </tr>
            `;
        });
        
        html += `
            <tr style="background:#f8f9fa; font-weight:bold; border-top:2px solid #ddd;">
                <td style="text-align:left;">% DE CATEGORIAS REPORTADAS:</td>
                <td>-</td>
                <td>${precPorcentaje}%</td>
                <td>${presnPorcentaje}%</td>
                <td>${sosPorcentaje}%</td>
                <td>${promosPorcentaje}%</td>
                <td>${exhPorcentaje}%</td>
            </tr>
        `;
        
        html += `</tbody></table></div></div></div>`;
        
        return html;
    }
    
    // Evento click buscar
    $('#btnBuscar').on('click', buscarPuntosVenta);
    
    // Permitir presionar Enter en los campos
    $('#f_inicio, #f_fin, #select_gestor').on('keypress', function(e) {
        if (e.which === 13) {
            buscarPuntosVenta();
        }
    });
    
    // Cargar gestores al inicio
    if (usuarioParam && usuarioParam !== 'null' && usuarioParam !== '') {
        cargarGestores();
    } else {
        console.warn("No se especificó usuario en la URL");
        $('#select_gestor').html('<option value="">No se especificó usuario</option>');
    }
});