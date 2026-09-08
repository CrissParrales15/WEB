/**
 * Descargar tabla KPIS a Excel (FUNCIÓN GLOBAL)
 * Busca la tabla DENTRO del modal de KPIs
 * @param {string} filename - Nombre del archivo
 */
function descargarExcelKPIS(filename) {
    // console.log('Buscando tabla de KPIs para exportar...');
    
    // Buscar la tabla dentro del modal de KPIs
    var modal = document.getElementById('kpis-modal');
    if (!modal) {
        modal = document.getElementById('modalKPIS');
    }
    if (!modal) {
        modal = document.querySelector('.modal.in');
    }
    
    if (!modal) {
        alert('No se encontró el modal de KPIs');
        return;
    }
    
    // Buscar la tabla dentro del modal
    var table = modal.querySelector('table.kpis-table');
    if (!table) {
        table = modal.querySelector('table');
    }
    
    if (!table) {
        alert('No se encontró la tabla de KPIs para exportar');
        return;
    }
    
    // console.log('Tabla de KPIs encontrada:', table);
    
    // Clonar la tabla para no modificar la original
    var cloneTable = table.cloneNode(true);
    
    // Reemplazar checkboxes por texto ✓
    cloneTable.querySelectorAll('.check-box').forEach(function(el) {
        el.textContent = '✓';
    });
    
    // Crear el contenido HTML para Excel
    var html = `
        <html xmlns:o="urn:schemas-microsoft-com:office:office" 
              xmlns:x="urn:schemas-microsoft-com:office:excel" 
              xmlns="http://www.w3.org/TR/REC-html40">
        <head>
            <meta charset="UTF-8">
            <!--[if gte mso 9]>
            <xml>
                <x:ExcelWorkbook>
                    <x:ExcelWorksheets>
                        <x:ExcelWorksheet>
                            <x:Name>KPIs</x:Name>
                            <x:WorksheetOptions>
                                <x:DisplayGridlines/>
                            </x:WorksheetOptions>
                        </x:ExcelWorksheet>
                    </x:ExcelWorksheets>
                </x:ExcelWorkbook>
            </xml>
            <![endif]-->
            <style>
                table { border-collapse: collapse; width: 100%; font-family: Arial, sans-serif; font-size: 11px; }
                th { background-color: #6a0dad; color: #ffffff; padding: 8px; border: 1px solid #ddd; text-align: center; }
                td { padding: 6px 8px; border: 1px solid #ddd; text-align: center; }
                td:first-child { text-align: left; font-weight: bold; }
                td:nth-child(2) { text-align: left; }
                tr:last-child td { background-color: #f8f9fa; font-weight: bold; }
                .no-data { text-align: center; color: #999; padding: 20px; }
            </style>
        </head>
        <body>
            ${cloneTable.outerHTML}
        </body>
        </html>
    `;
    
    // Crear el blob y descargar
    var blob = new Blob([html], { type: 'application/vnd.ms-excel;charset=utf-8' });
    var link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = filename || 'KPIs_Reporte.xls';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(link.href);
}

/**
 * kpis_view.js
 * Vista de KPIs por Punto de Venta
 * Permite filtrar por fechas y mercaderista, ver lista de PDVs y sus KPIs
 */
$(document).ready(function() {
    // ============================================================
    // 1. VARIABLES GLOBALES
    // ============================================================
    
    var urlParams = new URLSearchParams(window.location.search);
    var usuarioParam = urlParams.get('usuario');
    var gestores = [];
    var puntosVenta = [];

    // ============================================================
    // 2. FUNCIONES DE UTILIDAD
    // ============================================================
    
    /**
     * Fix para WebView - Forzar scroll horizontal en tablas
     */
    function fixWebViewScroll() {
        var isWebView = window.navigator.userAgent.indexOf('wv') > -1 || 
                       window.navigator.userAgent.indexOf('WebView') > -1;
        
        if (isWebView) {
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

    /**
     * Establecer fechas por defecto (últimos 7 días)
     */
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
        
        $('#f_inicio').val(formatDate(today));
        $('#f_fin').val(formatDate(today));
    }

    // ============================================================
    // 3. FUNCIONES DE CARGA DE DATOS
    // ============================================================

    /**
     * Cargar mercaderistas según fechas seleccionadas
     * @param {string} f_inicio - Fecha inicio (YYYY-MM-DD)
     * @param {string} f_fin - Fecha fin (YYYY-MM-DD)
     */
    function cargarGestores(f_inicio, f_fin) {
        if (!usuarioParam || usuarioParam === 'null' || usuarioParam === '') {
            alert('No se ha especificado un usuario');
            $('#select_gestor').html('<option value="">No se especificó usuario</option>');
            return;
        }
        
        if (!f_inicio) f_inicio = $('#f_inicio').val();
        if (!f_fin) f_fin = $('#f_fin').val();
        
        $.ajax({
            url: '/App/XploraEcuador/mantenimiento_bases/colgate_nuevo/getters/getGestoresPorSupervisor.php',
            method: 'POST',
            data: {
                usuario: usuarioParam,
                fecha_inicio: f_inicio,
                fecha_fin: f_fin
            },
            dataType: 'json',
            success: function(data) {
                if (!data || data.length === 0) {
                    $('#select_gestor').html('<option value="">No hay mercaderistas disponibles</option>');
                    return;
                }
                
                gestores = data;
                var select = $('#select_gestor');
                select.html('<option value=""> Seleccione un mercaderista </option>');
                
                $.each(data, function(index, gestor) {
                    select.append('<option value="' + gestor.id + '">' + gestor.nombre + '</option>');
                });
            },
            error: function() {
                $('#select_gestor').html('<option value="">Error al cargar mercaderistas</option>');
            }
        });
    }

    /**
     * Buscar puntos de venta del mercaderista seleccionado
     */
    function buscarPuntosVenta() {
        var f_inicio = $('#f_inicio').val();
        var f_fin = $('#f_fin').val();
        var gestorId = $('#select_gestor').val();
        
        if (!f_inicio || !f_fin) {
            alert('Debe seleccionar fechas de inicio y fin');
            return;
        }
        
        if (!gestorId) {
            alert('Debe seleccionar un mercaderista');
            return;
        }
        
        // Buscar datos del gestor seleccionado
        var gestorNombre = '';
        var gestorUsuario = '';
        $.each(gestores, function(index, g) {
            if (g.id == gestorId) {
                gestorNombre = g.nombre;
                gestorUsuario = g.usuario || '';
            }
        });
        
        $('#loadingSpinner').show();
        $('#tablaContainer').html('');
        
        $.ajax({
            url: '/App/XploraEcuador/mantenimiento_bases/colgate_nuevo/getters/getPuntosVentaPorGestor.php',
            method: 'POST',
            data: {
                usuario: usuarioParam,
                gestor_id: gestorId,
                fecha_inicio: f_inicio,
                fecha_fin: f_fin
            },
            dataType: 'json',
            success: function(data) {
                $('#loadingSpinner').hide();
                puntosVenta = data;
                
                if (!data || data.length === 0) {
                    $('#tablaContainer').html(`
                        <div class="no-data">
                            <i class="fa fa-info-circle"></i>
                            <p>No se encontraron puntos de venta para el período seleccionado</p>
                        </div>
                    `);
                    return;
                }
                
                renderizarTablaPDV(data);
                fixWebViewScroll();
                
                // Eventos para botones Ver KPIs
                $('.btn-ver-kpis').on('click', function() {
                    var index = $(this).data('index');
                    var pdvData = puntosVenta[index];
                    var gestorNombreParaModal = pdvData.gestor_usuario || 'No disponible';
                    var supervisorNombre = 'Supervisor: ' + (usuarioParam || 'No disponible');
                    mostrarKPIS(pdvData, gestorNombreParaModal, supervisorNombre);
                });
            },
            error: function(xhr, status, error) {
                $('#loadingSpinner').hide();
                $('#tablaContainer').html(`
                    <div class="no-data" style="color:#e74c3c;">
                        <i class="fa fa-exclamation-triangle"></i>
                        <p>Error al cargar los datos: ${error}</p>
                    </div>
                `);
            }
        });
    }

    // ============================================================
    // 4. FUNCIONES DE RENDERIZADO
    // ============================================================

    /**
     * Renderizar tabla de puntos de venta
     * @param {Array} data - Lista de PDVs
     */
    function renderizarTablaPDV(data) {
        var html = `
            <div class="table-responsive-wrapper">
                <table class="table-kpis">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Punto de Venta</th>
                            <th>RE</th>
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
            var estadoClass = 'estado-atendido';
            if (pdv.estado === 'EN PROCESO') estadoClass = 'estado-proceso';
            else if (pdv.estado === 'NO VISITADO') estadoClass = 'estado-novisitado';
            else if (pdv.estado === 'JUSTIFICADO') estadoClass = 'estado-justificado';
            
            html += `
                <tr>
                    <td>${index + 1}</td>
                    <td>${pdv.nombre || pdv.pos_name || 'Sin nombre'}</td>
                    <td><span class="re-badge">${pdv.subchannel || 'N/A'}</span></td>
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
    }

    // ============================================================
    // 5. FUNCIONES DE KPIS
    // ============================================================

    /**
     * Mostrar modal de KPIs
     * @param {Object} pdvData - Datos del PDV
     * @param {string} gestorNombre - Nombre del gestor
     * @param {string} supervisorNombre - Nombre del supervisor
     */
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
        var re = pdvData.subchannel || '';
        
        $.ajax({
            url: '/App/XploraEcuador/mantenimiento_bases/colgate_nuevo/getters/getKPISRE.php',
            method: 'GET',
            data: {
                codigo: codigoPdv,
                fecha: fechaPdv,
                usuario: nombreGestor,
                re: re,
                rol: 'USUARIO'
            },
            dataType: 'json',
            success: function(data) {
                if (data.error) {
                    $('#modalKPISBody').html(`
                        <div class="alert alert-warning">
                            <i class="fa fa-exclamation-triangle"></i>
                            ${data.error}
                        </div>
                    `);
                    return;
                }
                
                var kpisContent = generarContenidoKPIS(data, pdvData, gestorNombre, supervisorNombre);
                $('#modalKPISBody').html(kpisContent);
            },
            error: function() {
                $('#modalKPISBody').html(`
                    <div class="alert alert-danger">
                        <i class="fa fa-exclamation-triangle"></i>
                        Error al cargar las categorías. Intente nuevamente.
                    </div>
                `);
            }
        });
    }

    /**
     * Generar contenido del modal KPIS con tabla dinámica
     * @param {Object} data - Datos de KPIs del backend
     * @param {Object} pdvData - Datos del PDV
     * @param {string} gestorNombre - Nombre del gestor
     * @param {string} supervisorNombre - Nombre del supervisor
     * @returns {string} HTML del contenido
     */
    function generarContenidoKPIS(data, pdvData, gestorNombre, supervisorNombre) {
        var fotos = { ENTRADA: '', SALIDA: '' };
        var kpis_categorias = [];
        var modulosActivos = data.modulos_activos || {};
        
        if (data && data.fotos) {
            fotos = data.fotos;
        }
        
        // Procesar estados
        if (data && data.estados && Array.isArray(data.estados)) {
            kpis_categorias = data.estados.map(function(estado) {
                var item = {
                    nombre: estado.categoria,
                    subcategoria: estado.subcategoria || 'N/A'
                };
                
                for (var modulo in modulosActivos) {
                    var campo = modulo.toLowerCase();
                    item[campo] = estado[campo] === true ? 1 : 0;
                }
                
                return item;
            });
        }
        
        // Si no hay categorías reportadas
        if (kpis_categorias.length === 0) {
            return generarSinCategorias(pdvData, gestorNombre, supervisorNombre, fotos);
        }
        
        return generarTablaKPIS(kpis_categorias, modulosActivos, pdvData, gestorNombre, supervisorNombre, fotos);
    }

    /**
     * Generar HTML cuando no hay categorías reportadas
     */
    function generarSinCategorias(pdvData, gestorNombre, supervisorNombre, fotos) {
        return `
            <div class="kpis-modal-content">
                <div class="kpis-main-layout">
                    <div class="kpis-left-column">
                        <div class="pdv-section">
                            <h4 class="pdv-title">PUNTO DE VENTA (${pdvData.fecha || 'Sin fecha'})</h4>
                            <h4 class="pdv-name">${pdvData.nombre || pdvData.pos_name || 'Sin nombre'}</h4>
                            <p><strong>RE:</strong> ${pdvData.subchannel || 'No disponible'}</p>
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

    /**
     * Generar tabla de KPIs con módulos dinámicos
     * CÁLCULO: % = (% categorías con ✓ + % subcategorías con ✓) / 2
     */ 
    function generarTablaKPIS(kpis_categorias, modulosActivos, pdvData, gestorNombre, supervisorNombre, fotos) {
        // Mapeo de nombres cortos para la tabla
        var nombresCortos = {
            'PRECIOS': 'PREC',
            'SURTIDO_CORRECTO': 'SURT. CORR.',
            'PRESENCIA_MINIMA': 'PRES. MIN.',
            'PROMOCION': 'PROMO',
            'VALIDACION_PROMOCION': 'VAL. PROMO',
            'PROMOCIONES_IA': 'PROMO IA',
            'MATERIAL_POP': 'MAT. POP',
            'SHARE': 'SOS',
            'HERRAMIENTAS_EXHIBICION': 'HERR. EXH.',
            'ENCUESTA_APP': 'ENCUESTA'
        };

        // Construir encabezados
        var headers = ['CATEGORÍAS', 'SUBCATEGORIA'];
        var camposModulos = [];

        for (var modulo in modulosActivos) {
            var nombreCorto = nombresCortos[modulo] || modulo.substring(0, 4);
            headers.push(nombreCorto);
            camposModulos.push(modulo.toLowerCase());
        }

        var html = `
            <div class="kpis-modal-content">
                <div class="kpis-main-layout">
                    <div class="kpis-left-column">
                        <div class="pdv-section">
                            <h4 class="pdv-title">PUNTO DE VENTA (${pdvData.fecha || 'Sin fecha'})</h4>
                            <h4 class="pdv-name">${pdvData.nombre || pdvData.pos_name || 'Sin nombre'}</h4>
                            <p><strong>RE:</strong> ${pdvData.subchannel || 'No disponible'}</p>
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
                                    ${headers.map(h => `<th>${h}</th>`).join('')}
                                </tr>
                            </thead>
                            <tbody>
        `;

        // ============================================================
        // 1. CONSTRUIR FILAS PLANAS (cada subcategoría = una fila)
        // ============================================================
        var filasPlanas = [];

        $.each(kpis_categorias, function(i, cat) {
            var nombreCategoria = cat.nombre;
            var tieneSubs = cat.tiene_subcategorias || false;
            var subcategoriasArray = [];
            
            // Obtener array de subcategorías
            if (tieneSubs && Array.isArray(cat.subcategorias) && cat.subcategorias.length > 0) {
                subcategoriasArray = cat.subcategorias;
            } else if (cat.subcategoria && cat.subcategoria !== 'N/A') {
                // Compatibilidad: si viene como string separado por comas
                subcategoriasArray = cat.subcategoria.split(',').map(function(s) { return s.trim(); });
                // Filtrar elementos vacíos
                subcategoriasArray = subcategoriasArray.filter(function(s) { return s !== ''; });
            }
            
            // Si tiene subcategorías, crear una fila por cada una
            if (subcategoriasArray.length > 0) {
                $.each(subcategoriasArray, function(j, subcat) {
                    if (subcat && subcat.trim() !== '') {
                        var fila = {
                            categoria: nombreCategoria,
                            subcategoria: subcat.trim(),
                            modulos: {}
                        };
                        
                        // Copiar valores de módulos (heredados de la categoría padre)
                        for (var campo of camposModulos) {
                            fila.modulos[campo] = cat[campo] || false;
                        }
                        
                        filasPlanas.push(fila);
                    }
                });
            } else {
                // Si no tiene subcategorías, crear una fila con "N/A"
                var fila = {
                    categoria: nombreCategoria,
                    subcategoria: 'N/A',
                    modulos: {}
                };
                
                for (var campo of camposModulos) {
                    fila.modulos[campo] = cat[campo] || false;
                }
                
                filasPlanas.push(fila);
            }
        });

        // ============================================================
        // 2. GENERAR FILAS HTML
        // ============================================================
        
        if (filasPlanas.length === 0) {
            html += `
                <tr>
                    <td colspan="${headers.length}" style="text-align:center; padding:20px; color:#999;">
                        <i class="fa fa-info-circle"></i> No hay categorías reportadas
                    </td>
                </tr>
            `;
        } else {
            $.each(filasPlanas, function(i, fila) {
                var isNA = fila.subcategoria === 'N/A';
                var row = `<tr>
                    <td style="font-weight: bold; font-size: 12px; white-space: nowrap;">${fila.categoria}</td>
                    <td style="text-align: left; font-size: 11px; color: ${isNA ? '#bbb' : '#555'}; max-width: 200px; word-wrap: break-word;">
                        ${isNA ? '<span style="font-style:italic;">N/A</span>' : fila.subcategoria}
                    </td>`;
                
                // Celdas de módulos
                for (var campo of camposModulos) {
                    var valor = fila.modulos[campo] || false;
                    row += `<td style="text-align: center;" class="kpi-indicator ${valor ? 'kpi-yes' : 'kpi-no'}">
                        ${valor ? '<span class="check-box">✓</span>' : ''}
                    </td>`;
                }
                row += `</tr>`;
                html += row;
            });
        }

        // ============================================================
        // 3. FILA DE PORCENTAJES - PROMEDIO ENTRE CATEGORÍAS Y SUBCATEGORÍAS
        //    % = (% categorías con ✓ + % subcategorías con ✓) / 2
        // ============================================================
        
        // 3.1 Obtener total de categorías únicas
        var totalCategorias = 0;
        var categoriasUnicas = {};
        
        $.each(kpis_categorias, function(i, cat) {
            var nombre = cat.nombre;
            if (!categoriasUnicas[nombre]) {
                categoriasUnicas[nombre] = true;
                totalCategorias++;
            }
        });
        
        // 3.2 Obtener total de subcategorías (filas planas)
        var totalSubcategorias = filasPlanas.length;
        
        // 3.3 Calcular porcentajes para cada módulo
        var porcentajes = {};
        
        for (var campo of camposModulos) {
            // 3.3.1 Contar categorías con ✓ en este módulo
            var categoriasConCheck = {};
            var countCategorias = 0;
            
            $.each(kpis_categorias, function(i, cat) {
                var nombre = cat.nombre;
                if (cat[campo] && !categoriasConCheck[nombre]) {
                    categoriasConCheck[nombre] = true;
                    countCategorias++;
                }
            });
            
            // 3.3.2 Contar subcategorías con ✓ en este módulo
            var countSubcategorias = 0;
            $.each(filasPlanas, function(i, fila) {
                if (fila.modulos[campo]) {
                    countSubcategorias++;
                }
            });
            
            // 3.3.3 Calcular porcentajes
            var pctCategorias = totalCategorias > 0 ? (countCategorias / totalCategorias) * 100 : 0;
            var pctSubcategorias = totalSubcategorias > 0 ? (countSubcategorias / totalSubcategorias) * 100 : 0;
            
            // 3.3.4 Promedio final
            porcentajes[campo] = (pctCategorias + pctSubcategorias) / 2;
        }
        
        // 3.4 Construir fila de porcentajes
        var rowPorcentajes = `<tr style="background:#f8f9fa; font-weight:bold; border-top:2px solid #ddd;">
            <td style="text-align:left; font-size:12px;">% CATEGORIAS REPORTADAS:</td>
            <td style="text-align:left; font-size:11px; color:#999;">% SUBCATEGORIAS REPORTADAS</td>`;
        
        for (var campo of camposModulos) {
            var valor = porcentajes[campo] || 0;
            var valorFormateado = Math.round(valor);
            rowPorcentajes += `<td style="text-align:center; font-size:13px; color:#6a0dad;">${valorFormateado}%</td>`;
        }
        rowPorcentajes += `</tr>`;
        html += rowPorcentajes;

        // ============================================================
        // 4. CERRAR TABLA
        // ============================================================
        html += `
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        `;

        // ============================================================
        // 5. BOTÓN DE DESCARGA EXCEL (FUERA DE LA TABLA)
        // ============================================================
        var pdvNombre = (pdvData.nombre || pdvData.pos_name || 'PDV').replace(/[^a-zA-Z0-9]/g, '_');
        var fechaDescarga = pdvData.fecha || new Date().toISOString().split('T')[0];
        var nombreArchivo = 'KPIs_' + pdvNombre + '_' + fechaDescarga + '.xls';

        html += `
            <div style="text-align: right; margin-top: 15px; padding: 10px 0;">
                <button onclick="descargarExcelKPIS('${nombreArchivo}')" 
                        class="btn btn-success btn-sm" 
                        style="background: #28a745; color: white; border: none; padding: 8px 20px; border-radius: 5px; cursor: pointer; font-size: 13px; display: none;">
                    <i class="fa fa-file-excel-o"></i> Descargar Excel
                </button>
            </div>
        `;

        return html;
    }

    // ============================================================
    // 6. INICIALIZACIÓN Y EVENTOS
    // ============================================================

    // Ejecutar fixes
    fixWebViewScroll();
    setDefaultDates();

    // Evento click buscar
    $('#btnBuscar').on('click', buscarPuntosVenta);

    // Permitir presionar Enter en los campos
    $('#f_inicio, #f_fin, #select_gestor').on('keypress', function(e) {
        if (e.which === 13) {
            buscarPuntosVenta();
        }
    });

    // Recargar gestores al cambiar fechas
    $('#f_inicio, #f_fin').on('change', function() {
        var f_inicio = $('#f_inicio').val();
        var f_fin = $('#f_fin').val();
        if (f_inicio && f_fin && usuarioParam && usuarioParam !== 'null' && usuarioParam !== '') {
            cargarGestores(f_inicio, f_fin);
            $('#tablaContainer').html(`
                <div class="no-data">
                    <i class="fa fa-info-circle"></i>
                    <p>Seleccione un mercaderista y presione Buscar</p>
                </div>
            `);
        }
    });

    // Cargar gestores al inicio
    if (usuarioParam && usuarioParam !== 'null' && usuarioParam !== '') {
        var f_inicio = $('#f_inicio').val();
        var f_fin = $('#f_fin').val();
        cargarGestores(f_inicio, f_fin);
    } else {
        $('#select_gestor').html('<option value="">No se especificó usuario</option>');
    }
});