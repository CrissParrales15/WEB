
function descargarExcelKPIS(filename) {
    console.log('Buscando tabla de KPIs para exportar...');
    
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
    
    console.log('Tabla de KPIs encontrada:', table);
    
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

let __Contenedor = []; 
let data_nombre;   
let data_pdv;      
let data_visitados;
let data_proceso;
let data_pendientes;
let data_efectividad;
let data_supervisor;
let data_gestores;



$(document).ready(()=>{
    /**
     * Inicio Datos de Prueba
     */
    let supervisor;
    // let __Contenedor = [];
    
    // $.ajax({
    //     url: "/App/XploraEcuador/mantenimiento_bases/colgate_nuevo/getters/getJsonGps.php",
    //     method: "POST",
    //     data: null,
    //     beforeSend: function(){
    //         // $("#loading").css("display","block");
    //     },
    //     success: function (data) {
    //         console.log(jQuery.parseJSON(data));
    //         supervisor = jQuery.parseJSON(data);
    //     },
    //     error: function(XMLHttpRequest, textStatus, errorThrown) {
    //         alert("Status: " + textStatus); alert("Error: " + errorThrown);
    //     }
    // });

   /* supervisor = [
        {
            id: 1,
            nombre: 'SUPERVISOR 1',
            gestor: [
                {
                    id: 2,
                    nombre: 'GESTOR 1',
                    cant_visitas: 3,
                    pdv: [
                        {
                            id: 1,//YA
                            ordenTotal: 1,//AUTOGENERAR
                            nombre: 'PDV 1',//FALTA
                            fecha: '12-07-2022',//YA
                            h_ini: '07:00:00',//YA
                            h_fin: '07:30:00',//YA
                            duracion: 30,//FALTA
                            distancia: 4,//FALTA
                            estado: 'ATENDIDO',//HACER JOIN
                            tipo_relevo: 'INTERNO',//FALTA
                            direccion: 'Circunvalación Sur # 409 y, Ébanos, Guayaquil 090507, Ecuador',//FALTA
                            lon: '-79.9098888888889',//FALTA
                            lat: '-2.1725833',//FALTA
                            foto_no_visita: ''
                        },
                        {
                            id: 2,
                            ordenTotal: 2,
                            nombre: 'PDV 2',
                            fecha: '12-07-2022',
                            h_ini: '07:40:00',
                            h_fin: '08:00:00',
                            duracion: 20,
                            distancia: 3,
                            estado: 'EN PROCESO',
                            tipo_relevo: 'INTERNO',
                            direccion: 'Circunvalación Sur # 409 y, Ébanos, Guayaquil 090507, Ecuador',
                            lon: '-79.90659596841016',
                            lat: '-2.1732918158761834',
                            foto_no_visita: ''
                        },
                        {
                            id: 3,
                            ordenTotal: 3,
                            nombre: 'PDV 3',
                            fecha: '12-07-2022',
                            h_ini: '08:10:00',
                            h_fin: '08:20:00',
                            duracion: 10,
                            distancia: 20,
                            estado: 'ATENDIDO',
                            tipo_relevo: 'EXTERNO',
                            direccion: 'Circunvalación Sur # 409 y, Ébanos, Guayaquil 090507, Ecuador',
                            lon: '-79.9069497129506',
                            lat: '-2.171658553117453',
                            foto_no_visita: ''
                        }
                    ]
                },
                {
                    id: 3,
                    nombre: 'GESTOR 2',
                    cant_visitas: 2,
                    pdv: [
                        {
                            id: 1,
                            ordenTotal: 4,
                            nombre: 'PDV 1',
                            fecha: '12-07-2022',
                            h_ini: '10:00:00',
                            h_fin: '10:30:00',
                            duracion: 30,
                            distancia: 6,
                            estado: 'ATENDIDO',
                            tipo_relevo: 'INTERNO',
                            direccion: 'Circunvalación Sur # 409 y, Ébanos, Guayaquil 090507, Ecuador',
                            lon: '-79.7956529',
                            lat: '-2.2417569',
                            foto_no_visita: ''
                        },
                        {
                            id: 2,
                            ordenTotal: 4,
                            nombre: 'PDV 2',
                            fecha: '12-07-2022',
                            h_ini: '07:00:00',
                            h_fin: '07:30:00',
                            duracion: 30,
                            distancia: 4,
                            estado: 'ATENDIDO',
                            tipo_relevo: 'INTERNO',
                            direccion: 'Circunvalación Sur # 409 y, Ébanos, Guayaquil 090507, Ecuador',
                            lon: '-79.8956529',
                            lat: '-2.2417569',
                            foto_no_visita: ''
                        }
                    ]
                }
            ]
        },
        {
            id: 4,
            nombre: 'SUPERVISOR 2',
            gestor: [
                {
                    id: 5,
                    nombre: 'GESTOR 1',
                    cant_visitas: 2,
                    pdv: [
                        {
                            id: 1,
                            ordenTotal: 5,
                            nombre: 'PDV 1',
                            fecha: '12-07-2022',
                            h_ini: '07:00:00',
                            h_fin: '07:30:00',
                            duracion: 30,
                            distancia: 4,
                            estado: 'ATENDIDO',
                            tipo_relevo: 'INTERNO',
                            direccion: 'Circunvalación Sur # 409 y, Ébanos, Guayaquil 090507, Ecuador',
                            lon: '-79.9062611',
                            lat: '-2.1262867',
                            foto_no_visita: ''
                        },
                        {
                            id: 2,
                            ordenTotal: 6,
                            nombre: 'PDV 2',
                            fecha: '12-07-2022',
                            h_ini: '07:40:00',
                            h_fin: '08:00:00',
                            duracion: 20,
                            distancia: 3,
                            estado: 'ATENDIDO',
                            tipo_relevo: 'INTERNO',
                            direccion: 'Circunvalación Sur # 409 y, Ébanos, Guayaquil 090507, Ecuador',
                            lon: '-79.9430582',
                            lat: '-2.1764459',
                            foto_no_visita: ''
                        }
                    ]
                },
                {
                    id: 6,
                    nombre: 'GESTOR 2',
                    cant_visitas: 2,
                    pdv: [
                        {
                            id: 1,
                            ordenTotal: 7,
                            nombre: 'PDV 1',
                            fecha: '12-07-2022',
                            h_ini: '07:00:00',
                            h_fin: '07:30:00',
                            duracion: 30,
                            distancia: 4,
                            estado: 'ATENDIDO',
                            tipo_relevo: 'INTERNO',
                            direccion: 'Circunvalación Sur # 409 y, Ébanos, Guayaquil 090507, Ecuador',
                            lon: '-79.8638238',
                            lat: '-2.1416744',
                            foto_no_visita: ''
                        },
                        {
                            id: 2,
                            ordenTotal: 8,
                            nombre: 'PDV 2',
                            fecha: '12-07-2022',
                            h_ini: '08:10:00',
                            h_fin: '08:20:00',
                            duracion: 10,
                            distancia: 20,
                            estado: 'ATENDIDO',
                            tipo_relevo: 'EXTERNO',
                            direccion: 'Circunvalación Sur # 409 y, Ébanos, Guayaquil 090507, Ecuador',
                            lon: '-79.8976363',
                            lat: '-2.142037',
                            foto_no_visita: ''
                        }
                    ]
                }
            ]
        },
        {
            id: 7,
            nombre: 'SUPERVISOR 3',
            gestor: [
                {
                    id: 8,
                    nombre: 'GESTOR 1',
                    cant_visitas: 2,
                    pdv: [
                        {
                            id: 1,
                            ordenTotal: 5,
                            nombre: 'PDV 1',
                            fecha: '12-07-2022',
                            h_ini: '07:00:00',
                            h_fin: '07:30:00',
                            duracion: 30,
                            distancia: 4,
                            estado: 'EN PROCESO',
                            tipo_relevo: 'INTERNO',
                            direccion: 'Circunvalación Sur # 409 y, Ébanos, Guayaquil 090507, Ecuador',
                            lon: '-79.9062611',
                            lat: '-2.1262867',
                            foto_no_visita: ''
                        },
                        {
                            id: 2,
                            ordenTotal: 6,
                            nombre: 'PDV 2',
                            fecha: '12-07-2022',
                            h_ini: '07:40:00',
                            h_fin: '08:00:00',
                            duracion: 20,
                            distancia: 3,
                            estado: 'ATENDIDO',
                            tipo_relevo: 'INTERNO',
                            direccion: 'Circunvalación Sur # 409 y, Ébanos, Guayaquil 090507, Ecuador',
                            lon: '-79.9430582',
                            lat: '-2.1764459',
                            foto_no_visita: ''
                        }
                    ]
                },
                {
                    id: 9,
                    nombre: 'GESTOR 2',
                    cant_visitas: 2,
                    pdv: [
                        {
                            id: 1,
                            ordenTotal: 7,
                            nombre: 'PDV 1',
                            fecha: '12-07-2022',
                            h_ini: '17:00:00',
                            h_fin: '17:30:00',
                            duracion: 30,
                            distancia: 4,
                            estado: 'NO VISITADO',
                            tipo_relevo: 'INTERNO',
                            direccion: 'Circunvalación Sur # 409 y, Ébanos, Guayaquil 090507, Ecuador',
                            lon: '-79.8638238',
                            lat: '-2.5416744',
                            foto_no_visita: ''
                        },
                        {
                            id: 2,
                            ordenTotal: 8,
                            nombre: 'PDV 2',
                            fecha: '12-07-2022',
                            h_ini: '08:10:00',
                            h_fin: '08:20:00',
                            duracion: 10,
                            distancia: 20,
                            estado: 'NO VISITADO',
                            tipo_relevo: 'EXTERNO',
                            direccion: 'Circunvalación Sur # 409 y, Ébanos, Guayaquil 090507, Ecuador',
                            lon: '-79.8576363',
                            lat: '-2.142037',
                            foto_no_visita: ''
                        }
                    ]
                }
            ]
        }
    ];*/
    // console.log(supervisor);
    /**
     * Fin Datos de Prueba
     */

    let div_body = document.getElementById('div-body');
    data_nombre = document.getElementById('data_nombre');
    data_pdv = document.getElementById('data_pdv');
    data_visitados = document.getElementById('data_visitados')
    data_proceso = document.getElementById('data_proceso')
    data_pendientes = document.getElementById('data_pendientes')
    data_efectividad = document.getElementById('data_efectividad')
    data_supervisor = document.getElementById('data_supervisores')
    data_gestores = document.getElementById('data_gestores')


    function mostrarEfectividadGeneral() { 
        var suma_pdv = 0;
        var suma_atendidos = 0;
        var suma_proceso = 0;
        var suma_pendiente = 0;
        var suma_gestores = 0;
        var cant_supervisores = __Contenedor.length;

        $.each(__Contenedor, function (i, v) {
            if (!v.gestor || !Array.isArray(v.gestor)) return true;
            suma_gestores += Object.keys(v.gestor).length;
            $.each(v.gestor, function (ii, iv) {
                $.each(iv.pdv, function (iii, iiv) {
                    if (iiv.estado === 'ATENDIDO') suma_atendidos++;
                    else if (iiv.estado === 'EN PROCESO') suma_proceso++;
                    else if (iiv.estado === 'NO VISITADO') suma_pendiente++;
                    suma_pdv++;
                });
            });
        });

        var efectividad = suma_pdv > 0 ? (suma_atendidos / suma_pdv) * 100 : 0;

        data_supervisor.innerHTML = ' ' + cant_supervisores + ' ';
        data_visitados.innerHTML = '' + suma_atendidos + '';
        data_proceso.innerHTML = '' + suma_proceso + '';
        data_pendientes.innerHTML = '' + suma_pendiente + '';
        data_pdv.innerHTML = ' ' + suma_pdv + ' ';
        data_gestores.innerHTML = ' ' + suma_gestores + ' ';
        data_efectividad.innerHTML = ' ' + efectividad.toFixed(1) + '%';
        // data_efectividad.innerHTML = ' ' + efectividad.toFixed(1) + '% <i class="fa fa-info-circle" style="font-size:14px; margin-left:4px; cursor:help;" title="Efectividad = (PDVs ATENDIDOS / Total PDVs Asignados) * 100"></i>';
        // data_nombre.innerHTML = ' TODOS';
        data_nombre.innerHTML = ' TODOS';

        // Semáforo general
        var semaforo_general = 'background-color:';
        if (efectividad < 50) semaforo_general += '#FF2D00';
        else if (efectividad >= 50 && efectividad < 85) semaforo_general += '#F7FF00';
        else semaforo_general += '#4DFF00';

        let spanExistente = data_efectividad.querySelector('span.dot');
        if (!spanExistente) {
            let span_efectividad = document.createElement('span');
            span_efectividad.className = 'dot';
            span_efectividad.setAttribute('style', semaforo_general + ';margin: 3px 0px;float: right;');
            data_efectividad.append(span_efectividad);
        } else {
            spanExistente.setAttribute('style', semaforo_general + ';margin: 3px 0px;float: right;');
        }
    }

    // Obtener Cuenta 
    let url = window.location.search;
    let obtenerParametro = new URLSearchParams(url);
    let cuenta = obtenerParametro.get('cuenta');


    div_body.addEventListener('click', (e)=>{
        if (!$(e.target).is('#navbar-vertical, #navbar-vertical *,.navbar-toggle-left,.navbar-toggle-left *,#btn-group-toggle,#btn-group-toggle *,#navbar-vertical-filtro, #navbar-vertical-filtro *')) {
            fnOnClickNavbar('navbar-vertical-filtro', false);
        }
    });

    let div_btn_group_toggle = document.createElement('div');
    div_btn_group_toggle.className = 'btn-group-vertical';
    div_btn_group_toggle.setAttribute('style','position: fixed; z-index: 1; left: 10px; top: 10px;');
    div_btn_group_toggle.setAttribute('aria-label','Botones de navegacion');
    div_btn_group_toggle.setAttribute('role','group');
    div_btn_group_toggle.id = 'btn-group-toggle';

    let btn_ver_filtro = document.createElement('button');
    btn_ver_filtro.className = 'btn btn-default';
    btn_ver_filtro.addEventListener('click', ()=>{
        fnOnClickNavbar('navbar-vertical-filtro',null);
    });

    let i_search = document.createElement('i');
    i_search.className = 'fa fa-search';

    btn_ver_filtro.append(i_search);
    div_btn_group_toggle.append(btn_ver_filtro);

    let btn_ver_grilla = document.createElement('button');
    btn_ver_grilla.className = 'btn btn-default';
    btn_ver_grilla.addEventListener('click', ()=>{
        fnOnClickNavbar('div-seguimiento',null);
    });

    let i_table = document.createElement('i');
    i_table.className = 'fa fa-table';

    btn_ver_grilla.append(i_table);
    div_btn_group_toggle.append(btn_ver_grilla);
    div_body.append(div_btn_group_toggle);
    
    // INICIO | GRILLA SEGUIMIENTO
    let div_seguimiento = document.createElement('div');
    div_seguimiento.className = 'col-xs-12 col-sm-6 animated slideInLeft';
    div_seguimiento.id = 'div-seguimiento';

    let div_panel_seguimiento = document.createElement('div');
    div_panel_seguimiento.className = 'panel panel-default';

    let div_head_panel_seguimiento = document.createElement('div');
    div_head_panel_seguimiento.className = 'panel-heading';
    div_head_panel_seguimiento.setAttribute('style','padding: 5px 15px;');

    let a_head_panel_seguimiento = document.createElement('a');
    a_head_panel_seguimiento.setAttribute('href','javascript:');
    a_head_panel_seguimiento.addEventListener('click', ()=>{
        fnOnClickNavbar('div-seguimiento',false);
    });

    let i_a_head_panel_seguimiento = document.createElement('i');
    i_a_head_panel_seguimiento.className = 'fa fa-map-o';

    a_head_panel_seguimiento.append(i_a_head_panel_seguimiento);
    a_head_panel_seguimiento.append(' SEGUMIENTO GPS');

    let btn_marker_todos_gestor = document.createElement('button');
    btn_marker_todos_gestor.className = 'btn btn-primary btn-xs';
    btn_marker_todos_gestor.setAttribute('style','padding: 0px 10px;float: right;');
    btn_marker_todos_gestor.addEventListener('click', ()=>{
        fnOnClickMarkerTodosGie();
    });

    let i_btn_marker_todos_gestor = document.createElement('i');
    i_btn_marker_todos_gestor.className = 'fa fa-map-marker';
    btn_marker_todos_gestor.append(i_btn_marker_todos_gestor);

    a_head_panel_seguimiento.append(btn_marker_todos_gestor);

    div_head_panel_seguimiento.append(a_head_panel_seguimiento);
    div_panel_seguimiento.append(div_head_panel_seguimiento);
    
    let table_seguimiento = document.createElement('table');
    table_seguimiento.className = 'table table-bordered';
    table_seguimiento.id = 'tbl-seguimiento';

    let thead_table_seguimiento = document.createElement('thead');
    let tr_1_thead_table_seguimiento = document.createElement('tr');

    let th_1_tr_thead_table_seguimiento = document.createElement('th');
    th_1_tr_thead_table_seguimiento.className = 'text-center hidden-xs';
    th_1_tr_thead_table_seguimiento.innerHTML = 'N°';
    tr_1_thead_table_seguimiento.append(th_1_tr_thead_table_seguimiento);

    let th_2_tr_thead_table_seguimiento = document.createElement('th');
    th_2_tr_thead_table_seguimiento.className = 'text-center';
    th_2_tr_thead_table_seguimiento.innerHTML = 'P.D.V.';
    tr_1_thead_table_seguimiento.append(th_2_tr_thead_table_seguimiento);

    let th_9_tr_thead_table_seguimiento = document.createElement('th');
    th_9_tr_thead_table_seguimiento.className = 'text-center';
    th_9_tr_thead_table_seguimiento.innerHTML = 'FECHA';
    tr_1_thead_table_seguimiento.append(th_9_tr_thead_table_seguimiento);

    let th_3_tr_thead_table_seguimiento = document.createElement('th');
    th_3_tr_thead_table_seguimiento.className = 'text-center hidden-xs';
    th_3_tr_thead_table_seguimiento.innerHTML = 'H. INI';
    tr_1_thead_table_seguimiento.append(th_3_tr_thead_table_seguimiento);

    let th_4_tr_thead_table_seguimiento = document.createElement('th');
    th_4_tr_thead_table_seguimiento.className = 'text-center hidden-xs';
    th_4_tr_thead_table_seguimiento.innerHTML = 'H. FIN';
    tr_1_thead_table_seguimiento.append(th_4_tr_thead_table_seguimiento);

    let th_5_tr_thead_table_seguimiento = document.createElement('th');
    th_5_tr_thead_table_seguimiento.className = 'text-center';
    th_5_tr_thead_table_seguimiento.setAttribute('title','GESTION');

    let i_th_5_tr_thead_table_seguimiento = document.createElement('i');
    i_th_5_tr_thead_table_seguimiento.className = 'fa fa-clock-o fa-1x';
    th_5_tr_thead_table_seguimiento.append(i_th_5_tr_thead_table_seguimiento);

    tr_1_thead_table_seguimiento.append(th_5_tr_thead_table_seguimiento);

    let th_6_tr_thead_table_seguimiento = document.createElement('th');
    th_6_tr_thead_table_seguimiento.className = 'text-center hidden-xs';
    th_6_tr_thead_table_seguimiento.setAttribute('title','DISTANCIA');

    let i_th_6_tr_thead_table_seguimiento = document.createElement('i');
    i_th_6_tr_thead_table_seguimiento.className = 'fa fa-street-view fa-1x';
    th_6_tr_thead_table_seguimiento.append(i_th_6_tr_thead_table_seguimiento);
    
    tr_1_thead_table_seguimiento.append(th_6_tr_thead_table_seguimiento);

    let th_7_tr_thead_table_seguimiento = document.createElement('th');
    th_7_tr_thead_table_seguimiento.className = 'text-center';
    th_7_tr_thead_table_seguimiento.innerHTML = 'ESTADO';
    tr_1_thead_table_seguimiento.append(th_7_tr_thead_table_seguimiento);

    let th_8_tr_thead_table_seguimiento = document.createElement('th');
    th_8_tr_thead_table_seguimiento.className = 'text-center';
    th_8_tr_thead_table_seguimiento.innerHTML = 'TIPO DE RELEVO';
    tr_1_thead_table_seguimiento.append(th_8_tr_thead_table_seguimiento);

    let th_10_tr_thead_table_seguimiento = document.createElement('th');
    th_10_tr_thead_table_seguimiento.className = 'text-center';
    th_10_tr_thead_table_seguimiento.innerHTML = 'KPIS';
    tr_1_thead_table_seguimiento.append(th_10_tr_thead_table_seguimiento);

    thead_table_seguimiento.append(tr_1_thead_table_seguimiento);
    table_seguimiento.append(thead_table_seguimiento);

    let tbody_table_seguimiento = document.createElement('tbody');
    let tr_tbody_table_seguimiento = document.createElement('tr');

    let td_1_tr_tbody_table_seguimiento = document.createElement('td');
    td_1_tr_tbody_table_seguimiento.setAttribute('colspan','10');
    td_1_tr_tbody_table_seguimiento.innerHTML = 'SIN REGISTROS';
    tr_tbody_table_seguimiento.append(td_1_tr_tbody_table_seguimiento);

    tbody_table_seguimiento.append(tr_tbody_table_seguimiento);
    table_seguimiento.append(tbody_table_seguimiento);

    div_panel_seguimiento.append(table_seguimiento);
    div_seguimiento.append(div_panel_seguimiento);
    div_body.append(div_seguimiento);
    // FIN | GRILLA SEGUIMIENTO

    // INICIO | MAP
    let div_gmap = document.createElement('div');
    div_gmap.id = 'gmap';
    div_gmap.setAttribute('style','width: 100%; height: 100%;');

    div_body.append(div_gmap);
    // FIN | MAP

    // INICIO | MAP POPUP
    let div_modal_gmap_popup = document.createElement('div');
    div_modal_gmap_popup.className = 'modal fade';
    div_modal_gmap_popup.id = 'gmap-popup';
    div_modal_gmap_popup.setAttribute('tabindex','0');
    div_modal_gmap_popup.setAttribute('role','dialog');
    div_modal_gmap_popup.setAttribute('aria-labelledby','ModalGmapPopup');

    //! MODAL KPIS 15/12/2025
    let div_modal_kpis = document.createElement('div');
    div_modal_kpis.className = 'modal fade text-center';
    div_modal_kpis.id = 'kpis-modal';
    div_modal_kpis.setAttribute('tabindex','-1');
    div_modal_kpis.setAttribute('role','dialog');
    // MODAL CUBRE TODA LA PANTALL
    div_modal_kpis.innerHTML = '<div class="modal-dialog modal-lg" role="document"><div class="modal-content"><div class="modal-header"><button type="button" class="close" data-dismiss="modal">&times;</button><h5 class="modal-title">CARTILLA GENERAL DEL RELEVO DE PUNTO DE VENTA</h5></div><div class="modal-body" style="text-align:center;"></div></div></div>';
    // MODAL APARECE ALADO
    
    div_body.append(div_modal_kpis);

    let div_document_modal_gmap_popup = document.createElement('div');
    div_document_modal_gmap_popup.setAttribute('role','document');

    let btn_dismiss_modal = document.createElement('button');
    btn_dismiss_modal.className = 'btn btn-default btn-xs';
    btn_dismiss_modal.setAttribute('data-dismiss','modal');
    btn_dismiss_modal.setAttribute('style','position: absolute; top: 10px; right: 10px;');
    
    let i_btn_dismiss_modal = document.createElement('i');
    i_btn_dismiss_modal.className = 'fa fa-close';
    btn_dismiss_modal.append(i_btn_dismiss_modal);

    div_document_modal_gmap_popup.append(btn_dismiss_modal);
    
    let div_thumbnail_document_modal_gmap_popup = document.createElement('div');
    div_thumbnail_document_modal_gmap_popup.className = 'thumbnail';

    let span_thumbnail_document_modal_gmap_popup = document.createElement('span');
    div_thumbnail_document_modal_gmap_popup.append(span_thumbnail_document_modal_gmap_popup);

    let img_thumbnail = document.createElement('img');
    img_thumbnail.setAttribute('data-holder-rendered','true');
    img_thumbnail.setAttribute('src','https://www.xplora.com.pe/xplora/Img/shop.png');
    div_thumbnail_document_modal_gmap_popup.append(img_thumbnail);

    let div_caption_thumbnail = document.createElement('div');
    div_caption_thumbnail.className = 'caption';
    div_caption_thumbnail.setAttribute('style','font-size: 11px;');
    div_thumbnail_document_modal_gmap_popup.append(div_caption_thumbnail);

    let div_gallery_thumbnail = document.createElement('div');
    div_gallery_thumbnail.className = 'gallery';
    div_gallery_thumbnail.setAttribute('style','display: none;');
    div_gallery_thumbnail.setAttribute('itemscope','');
    div_gallery_thumbnail.setAttribute('itemtype','http://schema.org/ImageGallery');
    div_thumbnail_document_modal_gmap_popup.append(div_gallery_thumbnail);
    
    div_document_modal_gmap_popup.append(div_thumbnail_document_modal_gmap_popup);
    div_modal_gmap_popup.append(div_document_modal_gmap_popup);
    div_body.append(div_modal_gmap_popup);
    // FIN | MAP POPUP

    // INICIO | FILTRO
    let nav_filtro = document.createElement('nav');
    nav_filtro.id = 'navbar-vertical-filtro';
    nav_filtro.setAttribute('role','navigation');
    nav_filtro.className = 'navbar navbar-default animated slideInRight';
    
    let div_container_filtro = document.createElement('div')
    div_container_filtro.className = 'container-fluid';
    div_container_filtro.setAttribute('style','overflow: auto;overflow-x: hidden;height: 100%;padding-right: 0px;padding-left: 0px;');

    let div_page_header = document.createElement('div')
    div_page_header.className = 'page-header';

    let h5_div_page_header = document.createElement('h5');

    let a_h5_div_page_header = document.createElement('a');
    a_h5_div_page_header.setAttribute('href','javascript:');

    let i_a_h5_div_page_header = document.createElement('i');
    i_a_h5_div_page_header.className = 'fa fa-filter';

    a_h5_div_page_header.innerHTML = ' Filtro';
    a_h5_div_page_header.prepend(i_a_h5_div_page_header);

    h5_div_page_header.append(a_h5_div_page_header);
    div_page_header.append(h5_div_page_header);
    div_container_filtro.append(div_page_header);

    let div_page_body = document.createElement('div');
    div_page_body.className = 'page-body';

    let div_container_page_filtro = document.createElement('div');
    div_container_page_filtro.className = 'container-fluid';

    let div_form_filtro = document.createElement('div');
    div_form_filtro.id = 'nuevo';
    div_form_filtro.setAttribute('role','form');
    
    let div_form_group_1 = document.createElement('div');
    div_form_group_1.className = 'form-group form-group-sm';

    let label_form_group_1 = document.createElement('label');
    label_form_group_1.className= 'control-label';
    label_form_group_1.setAttribute('for','f_inicio');
    label_form_group_1.innerHTML = 'Fecha Inicio';
    div_form_group_1.append(label_form_group_1);

    let input_form_group_1 = document.createElement('input');
    input_form_group_1.id = 'f_inicio';
    input_form_group_1.className = 'form-control';
    input_form_group_1.setAttribute('type','date');
    div_form_group_1.append(input_form_group_1);

    div_form_filtro.append(div_form_group_1);
    
    let div_form_group_2 = document.createElement('div');
    div_form_group_2.className = 'form-group form-group-sm';

    let label_form_group_2 = document.createElement('label');
    label_form_group_2.className= 'control-label';
    label_form_group_2.setAttribute('for','f_fin');
    label_form_group_2.innerHTML = 'Fecha Fin';
    div_form_group_2.append(label_form_group_2);

    let input_form_group_2 = document.createElement('input');
    input_form_group_2.id = 'f_fin';
    input_form_group_2.className = 'form-control';
    input_form_group_2.setAttribute('type','date');
    div_form_group_2.append(input_form_group_2);

    div_form_filtro.append(div_form_group_2);


    //AGREGANDO FILTRO DE CANALES

    let div_form_group_canal = document.createElement('div');
    div_form_group_canal.className = 'form-group form-group-sm';

    let label_form_group_canal = document.createElement('label');
    label_form_group_canal.className = 'control-label';
    label_form_group_canal.setAttribute('for', 'f_canal');
    label_form_group_canal.innerHTML = 'Canal';
    div_form_group_canal.append(label_form_group_canal);

    let input_form_group_canal = document.createElement('select');
    input_form_group_canal.id = 'f_canal';
    input_form_group_canal.className = 'form-control';
    //input_form_group_canal.setAttribute('type','date');
    div_form_group_canal.append(input_form_group_canal);

    div_form_filtro.append(div_form_group_canal);
    cargarCanales(cuenta);

    // FILTRO DE CADENA

    let div_form_group_cadena = document.createElement("div");
    div_form_group_cadena.className = "form-group form-group-sm";

    let label_form_group_cadena = document.createElement("label");
    label_form_group_cadena.className = "control-label";
    label_form_group_cadena.setAttribute("for", "f_cadena");
    label_form_group_cadena.innerHTML = "RE";
    div_form_group_cadena.append(label_form_group_cadena);

    let input_form_group_cadena = document.createElement("select");
    input_form_group_cadena.id = "f_cadena";
    input_form_group_cadena.className = "form-control";
    div_form_group_cadena.append(input_form_group_cadena);

    let option_1 = document.createElement("option");
    option_1.setAttribute("value","");
    option_1.setAttribute("selected","selected");
    option_1.setAttribute("disabled","disabled");
    option_1.innerHTML = "Seleccione";
    input_form_group_cadena.append(option_1);

    div_form_filtro.append(div_form_group_cadena);

    input_form_group_canal.addEventListener("change", (e) => {
        let canal = e.target.value;
        cargarCadena(/*cuenta,*/ canal);
    })

    // cargarProvincia(/*cuenta,*/ 'TODOS', 'TODOS');
    input_form_group_cadena.addEventListener("change", (e) => {
        let canal = $("#f_canal").val();
        let cadena = e.target.value;
        cargarProvincia(/*cuenta,*/ canal, cadena);
    })




    // FILTRO DE PROVINCIA


    let div_form_group_provincia = document.createElement("div");
    div_form_group_provincia.className = "form-group form-group-sm";

    let label_form_group_provincia = document.createElement("label");
    label_form_group_provincia.className = "control-label";
    label_form_group_provincia.setAttribute("for", "f_provincia");
    label_form_group_provincia.innerHTML = "Provincia";
    div_form_group_provincia.append(label_form_group_provincia);

    let input_form_group_provincia = document.createElement("select");
    input_form_group_provincia.id = "f_provincia";
    input_form_group_provincia.className = "form-control";

    let option_default_prov = document.createElement("option");
    option_default_prov.setAttribute("value", "");
    option_default_prov.setAttribute("selected", "selected");
    option_default_prov.setAttribute("disabled", "disabled");
    option_default_prov.innerHTML = "Seleccione";
    input_form_group_provincia.append(option_default_prov);

    div_form_group_provincia.append(input_form_group_provincia);

    div_form_filtro.append(div_form_group_provincia);
    //cargarProvincia(cuenta);


    /////////


    
    let div_form_group_3 = document.createElement('div');
    div_form_group_3.className = 'form-group form-group-sm';

    let button_form_group_1 = document.createElement('button');
    button_form_group_1.className = 'btn btn-primary btn-sm btn-block';
    button_form_group_1.innerHTML = ' Buscar';

    button_form_group_1.addEventListener('click', ()=>{
        fnOnClickBusca();
    });

    let i_button_form_group_1 = document.createElement('i');
    i_button_form_group_1.className = 'fa fa-search';
    button_form_group_1.prepend(i_button_form_group_1);

    div_form_group_3.append(button_form_group_1);

    div_form_filtro.append(div_form_group_3);
    
    div_container_page_filtro.append(div_form_filtro);
    div_page_body.append(div_container_page_filtro);
    div_container_filtro.append(div_page_body);

    nav_filtro.append(div_container_filtro);
    div_body.append(nav_filtro);
    // FIN | FILTRO

    /*
    -----------------------
    Inicio de funcionalidad
    -----------------------
    */
    let GoogleMaps;
    let vectorLayer = [];
    let indicadorMaps = 0;
    let myVar1 = null;
    let myVar2 = null;
    // let __Contenedor = [];
    let marker;
    let contenedorusp_geolocation_gestor = [];
    let locations = [];
    let GoogleMapsPolyline;
    let GoogleMarkersList = [];
    let olLayerVector = {};
    let latitude = '';
    let longitude = '';
    let olSourceVector = {};
    let GoogleMarkersListGeneral = [];
    let olLayerVectorGeneral = {};
    let olSourceVectorGeneral = {};
    let contenedorusp_geolocation_gestores = [];
    let gestorlocation;

    GoogleMaps = new ol.Map({
        layers: [new ol.layer.Tile({ source: new ol.source.OSM({ wrapX: true }) })],
        target: div_gmap,
        controls: ol.control.defaults({
            attributionOptions: {
                collapsible: false
            }
        })
    });

    GoogleMaps.getView().setCenter(ol.proj.transform([-78.662, -1.790], 'EPSG:4326', 'EPSG:3857'));
    GoogleMaps.getView().setZoom(7);
    setResizeGoogleMaps();
    function setResizeGoogleMaps() {
        $('#gmap').height($(window).height());
        GoogleMaps.updateSize();
    };
    
    $(window).resize(function () {
        setResizeGoogleMaps();
    });

    function fnOnClickNavbar(__a, __b) {
        var $navBar = $('#' + __a),
            $hasAnimated,
            $classAnimated = { classIn: '', classOut: '' };
    
        if (__a == 'navbar-vertical-filtro') {
            $classAnimated.classIn = 'animated slideInRight';
            $classAnimated.classOut = 'animated slideOutRight';
        } else {
            $classAnimated.classIn = 'animated slideInLeft';
            $classAnimated.classOut = 'animated slideOutLeft';
        }
    
        if ($navBar.hasClass($classAnimated.classIn)) {
            $hasAnimated = true;
        } else {
            $hasAnimated = false;
        }
    
        $navBar.removeClass($classAnimated.classIn);
        $navBar.removeClass($classAnimated.classOut);
    
        if (__b == true) { $navBar.addClass($classAnimated.classIn); }
        else if (__b == false) { $navBar.addClass($classAnimated.classOut); }
        else if (__b == null) {
            if ($hasAnimated == true) {
                $navBar.addClass($classAnimated.classOut);
            } else {
                $navBar.addClass($classAnimated.classIn);
            }
        }
    }

    function esFormularioValido(f_inicio, f_fin, canal, cadena, provincia) {
        if (!f_inicio) {
            alert("Debe escoger una fecha de inicio");
            return false;
        }
        if (!f_fin) {
            alert("Debe escoger una fecha fin");
            return false;
        }
        // CORREGIDO: Validar que no esté vacío o sea "Seleccione"
        if (!canal || canal === "" || canal === "Seleccione") {
            alert("Debe escoger un canal");
            return false;
        }
        if (!cadena || cadena === "" || cadena === "Seleccione") {
            alert("Debe escoger un RE");
            return false;
        }
        if (!provincia || provincia === "" || provincia === "Seleccione") {
            alert("Debe escoger una provincia");
            return false;
        }
        return true;
    }

    //////////////FUNCION CARGAR CANALES
    function cargarCanales(/*cuenta*/) {
        $.ajax({
            url: "/App/XploraEcuador/mantenimiento_bases/colgate_nuevo/getters/getCanalesTest.php",
            // url: "pinguino/getters/getCanales.php",
            method: "get",
            // data: { cuenta: cuenta },
            beforeSend: function () {
                // $("#loading").css("display","block");
            },
            success: function (data) {
                /* console.log(jQuery.parseJSON(data));
                supervisor = jQuery.parseJSON(data); */
                /*  for(let i = 0;i<data.length;i++){
                    option = document.createElement('option');
                    option.setAttribute('value', data[i]);
                    option.appendChild(document.createTextNode(data[i]));
                    input_form_group_canal.appendChild(option);
                }  */
                $('#f_canal').html(data);
                $('#f_canal').val();
                $('#f_provincia').val();

                //console.log(data);
            },
            error: function (XMLHttpRequest, textStatus, errorThrown) {
                alert("Status: " + textStatus); alert("Error: " + errorThrown);
            }
        });
    }

    //////////////FUNCION CARGAR CANALES
    function cargarCadena(/*cuenta,*/ canal) {

        $.ajax({
            url: "/App/XploraEcuador/mantenimiento_bases/colgate_nuevo/getters/getCadenasNewTest.php",
            // url: "pinguino/getters/getCadenas.php",
            method: "get",
            data: { canal: canal/*, cuenta: cuenta*/ },
            beforeSend: function () {
                // $("#loading").css("display","block");
            },
            success: function (data) {
                /* console.log(jQuery.parseJSON(data));
                supervisor = jQuery.parseJSON(data); */
                /*  for(let i = 0;i<data.length;i++){
                    option = document.createElement('option');
                    option.setAttribute('value', data[i]);
                    option.appendChild(document.createTextNode(data[i]));
                    input_form_group_canal.appendChild(option);
                }  */
                $('#f_cadena').html(data);


                //console.log(data);
            },
            error: function (XMLHttpRequest, textStatus, errorThrown) {
                alert("Status: " + textStatus); alert("Error: " + errorThrown);
            }
        });
    }

    //////////////FUNCION CARGAR PROVINCIA
    function cargarProvincia(/*cuenta,*/ canal, cadena) {

        $.ajax({
            url: "/App/XploraEcuador/mantenimiento_bases/colgate_nuevo/getters/getProvinciasNewTest.php",
            // url: "pinguino/getters/getProvincias.php",
            method: "get",
            data: { /*cuenta : cuenta,*/ canal : canal, cadena : cadena },
            beforeSend: function () {
                // $("#loading").css("display","block");
            },
            success: function (data) {
                /* console.log(jQuery.parseJSON(data));
                supervisor = jQuery.parseJSON(data); */
                /*  for(let i = 0;i<data.length;i++){
                    option = document.createElement('option');
                    option.setAttribute('value', data[i]);
                    option.appendChild(document.createTextNode(data[i]));
                    input_form_group_canal.appendChild(option);
                }  */
                $('#f_provincia').html(data);


                //console.log(data);
            },
            error: function (XMLHttpRequest, textStatus, errorThrown) {
                alert("Status: " + textStatus); alert("Error: " + errorThrown);
            }
        });
    }

    function normalizarTexto(texto) {
        // Reemplazar espacios no rompibles por espacios normales
        return texto.replace(/\u00A0/g, ' ').trim();
    }

    //#region  KPIS - 15/12/2025
    function obtenerCategoriasReportadas(pdvData, usuario, callback) {
        // console.log("=== DEBUG: PDV Data completo ===");
        // console.log(JSON.stringify(pdvData, null, 2));
        
        var codigoPdv = pdvData.codigo || pdvData.CODIGO || pdvData.id || pdvData.pos_id;
        // console.log("Código a usar:", codigoPdv);

        var re = pdvData.subchannel || pdvData.RE || '';
        // console.log("RE (subchannel):", re);
        
        var usuarioNormalizado = normalizarTexto(usuario);
        
        var dataToSend = {
            codigo: codigoPdv,
            fecha: pdvData.fecha,
            usuario: usuarioNormalizado,
            re: re,
            rol: 'USUARIO'
        };
        
        // console.log("=== DATOS ENVIADOS A getKPISRE.php ===");
        // console.log("Parámetros:", dataToSend);
        
        $.ajax({
            url: "/App/XploraEcuador/mantenimiento_bases/colgate_nuevo/getters/getKPISRE.php",
            method: "GET",
            data: dataToSend,
            dataType: 'json',
            success: function(data) {
                // console.log("=== RESPUESTA RECIBIDA ===");
                // console.log("Datos completos:", data);
                // console.log("modulos_activos:", data.modulos_activos);
                // console.log("estados:", data.estados);
                
                //! Asegurar que los estados tengan TODOS los campos de módulos activos
                if (data && data.estados && Array.isArray(data.estados) && data.modulos_activos) {
                    // Para cada categoría, asegurar que tenga todos los campos de módulos activos
                    data.estados = data.estados.map(function(estado) {
                        var nuevoEstado = { ...estado };
                        // Asegurar que cada módulo activo esté presente en el estado
                        for (var modulo in data.modulos_activos) {
                            var campo = modulo.toLowerCase();
                            // Si el campo no existe o es undefined, asignar false
                            if (nuevoEstado[campo] === undefined) {
                                nuevoEstado[campo] = false;
                            }
                        }
                        return nuevoEstado;
                    });
                }
                
                callback(null, data);
            },
            error: function(xhr, status, error) {
                console.error("=== ERROR EN PETICIÓN ===");
                console.error("Status:", status);
                console.error("Error:", error);
                console.error("Respuesta del servidor:", xhr.responseText);
                callback(error, []);
            }
        });
    }

    function fnOnClickBusca(f_inicio, f_fin) {
        f_inicio = $('#f_inicio').val();
        f_fin = $('#f_fin').val();
        canal = $("#f_canal").val();
        cadena = $("#f_cadena").val();
        provincia = $("#f_provincia").val();


        if (esFormularioValido(f_inicio, f_fin, canal, cadena, provincia)) {

            let spanExistente = data_efectividad.querySelector('span.dot');
            if (spanExistente) {
                spanExistente.remove();
            }

            __Parametro = [];
            __Parametro.push(f_inicio);
            __Parametro.push(f_fin);
            // __Parametro.push(cuenta);
            __Parametro.push(canal);
            __Parametro.push(cadena);
            __Parametro.push(provincia);

            // __Parametro.push($('#in-inicio').val());
            // __Parametro.push($('#in-fin').val());

            // $.ajax({
            //      beforeSend: function (__s) {
            //          tbody_table_seguimiento.innerHTML = '';
            //      },
            //      url: 'https://webecuador.azurewebsites.net/App/XploraEcuador/mantenimiento_bases/colgate_nuevo/getters/getJsonGps.php',
            //      type: 'post',
            //      dataType: 'json',
            //      data: {parametros: __Parametro},
            //      success: function (__s) {
            //          if (__s == null || __s.length == 0) {
            //              alert("No hay datos a mostrar en este rango de fechas: " + f_inicio + " " + f_fin);
            //              // return;
            //          } else {
            //              __Contenedor = __s;
            //              console.log(JSON.stringify(__Contenedor));
            //              fnBuildGridWithData(__Contenedor);
            //          }
            //      },
            //      complete: function () {
            //          fnOnClickNavbar('div-seguimiento', true);
            //      },
            //      error: function (__s) {
            //          console.error(__s);
            //      }
            // });

            $.ajax({
                beforeSend: function (__s) {
                    tbody_table_seguimiento.innerHTML = '';
                },
                url: '/App/XploraEcuador/mantenimiento_bases/colgate_nuevo/getters/getJsonGpsTest.php',
                type: 'post',
                dataType: 'json',
                data: {parametros: __Parametro},
                success: function (__s) {
                    // VALIDAR LA RESPUESTA
                    if (__s == null || __s.length == 0) {
                        alert("No hay datos a mostrar en este rango de fechas: " + f_inicio + " - " + f_fin);
                        tbody_table_seguimiento.innerHTML = '<tr><td colspan="10" class="text-center">No se encontraron registros para las fechas seleccionadas</td></tr>';
                        // Limpiar el contenedor
                        __Contenedor = [];
                        return;
                    }
                    
                    __Contenedor = __s;
                    // console.log("Datos recibidos:", __Contenedor);
                    fnBuildGridWithData(__Contenedor);
                },
                complete: function () {
                    fnOnClickNavbar('div-seguimiento', true);
                },
                error: function (__s) {
                    console.error("Error en la consulta:", __s);
                    tbody_table_seguimiento.innerHTML = '<tr><td colspan="10" class="text-center text-danger">Error al cargar los datos</td></tr>';
                }
            });

            // $.ajax({
            //     beforeSend: function (__s) {
            //         tbody_table_seguimiento.innerHTML = '';
            //     },
            //     //Cambiar esta sección | Inicio
            //     url: '/App/XploraEcuador/mantenimiento_bases/colgate_nuevo/getters/getJsonGps.php',
            //     //Cambiar esta sección | Fin
            //     type: 'post',
            //     dataType: 'json',
            //     //Cambiar esta sección | Inicio
            //     data: '',
            //     //Cambiar esta sección | Fin
            //     success: function (__s) {
            //         if (__s == null || __s.length == 0) {
            //             return;
            //         }
            //         __Contenedor = __s;
            //         fnBuildGridWithData(supervisor);
            //     },
            //     complete: function () {
            //         fnOnClickNavbar('div-seguimiento', true);
            //     },
            //     error: function (__s) {
            //         console.error(__s);
            //     }
            // });
        
            /* tbody_table_seguimiento.innerHTML = '';
             __Contenedor = supervisor;
             console.log(JSON.stringify(__Contenedor));
             fnBuildGridWithData(supervisor); */
        }
    }

    function fnOnClickSupervisor(__a) {
        if ($('.row-' + __a).is(':visible')) {
            $('.row-' + __a).hide();
        } else {
            $('.row-' + __a).show();
        }
    
        $('.tr-pdv.row-' + __a).hide();
    }

    function fnOnClickGie(__a, __b) {
        if ($('.row-' + __a + '-' + __b).is(':visible')) {
            $('.row-' + __a + '-' + __b).hide();
        } else {
            $('.row-' + __a + '-' + __b).show();
        }
    }

    function fnGoogleMapsRemovesMarker() {
        if (olLayerVector) {
            GoogleMaps.removeLayer(olLayerVector);
            olLayerVector = {}

        }

        GoogleMarkersList = [];
    }

    function fnGoogleMapsRemovesMarkerGeneral() {
        if (olLayerVectorGeneral) {
            GoogleMaps.removeLayer(olLayerVectorGeneral);
        }

        GoogleMarkersListGeneral = [];
    }

    function createStyle(estado, ordenTotal) {
        var color;
        switch (estado) {
            case "NO VISITADO":
                color = '#E82E2E';
                break;
            case "ATENDIDO":
                color = '#3CDE23';
                break;
            case "EN PROCESO":
                color = '#FFEF3B';
                break;
            case "JUSTIFICADO":
                color = '#6555CA';
                break;
            default:
                color = '#000000';
        }

        return new ol.style.Style({
            image: new ol.style.Icon({
                src: 'data:image/svg+xml;charset=utf-8,' + encodeURIComponent('<svg xmlns="http://www.w3.org/2000/svg" width="32" height="48"><path fill="' + color + '" d="M16 0C7.2 0 0 7.2 0 16c0 7.2 12.8 16 16 16s16-8.8 16-16c0-8.8-7.2-16-16-16z"/><text x="50%" y="30%" dominant-baseline="middle" text-anchor="middle" fill="white" font-size="14">' + ordenTotal + '</text></svg>'),
                scale: 1
            })
        });
    }
    
    function fnGoogleMapsSetMapTodos(__a, __b, __c, __d, __e) {
        var olFeature = new ol.Feature({
            id: __b + '_' + __c + '_' + __d + '_' + 0,
            geometry: new ol.geom.Point(ol.proj.transform([parseFloat(__a.lon), parseFloat(__a.lat)], 'EPSG:4326', 'EPSG:3857')),
            name: __a.nombre
        });
    
        var olStyle = createStyle(__a.estado, __a.ordenTotal);
    
        olFeature.setStyle(olStyle);
        GoogleMarkersList.push(olFeature);
    }
    

    function fnGoogleMapsSetMap(__a, __b, __c, __d, __e) {
        var olFeature = new ol.Feature({
            id: __b + '_' + __c + '_' + __d + '_' + 0,
            geometry: new ol.geom.Point(ol.proj.transform([parseFloat(__a.lon), parseFloat(__a.lat)], 'EPSG:4326', 'EPSG:3857')),
            name: __a.nombre
        });

        var olStyle = createStyle(__a.estado, __a.id);

        olFeature.setStyle(olStyle);
        GoogleMarkersList.push(olFeature);
    }

    function fnOpenLayerAddLayer() {
        olSourceVector = {};
        olSourceVector = new ol.source.Vector({
            features: GoogleMarkersList,
            wrapX: false
        });
        olLayerVector = new ol.layer.Vector({
            source: olSourceVector
        });
        GoogleMaps.addLayer(olLayerVector);

        GoogleMaps.getViewport().addEventListener('click', function (e) {
            var parametro = [],
                coordenadas = [],
                feature = GoogleMaps.forEachFeatureAtPixel(GoogleMaps.getEventPixel(e), function (feature, layer) {
                     return feature;
                });

            if (feature) {
                parametro = feature.get('id').split('_');
                fnOnClickMarkerDetalle(parametro[0], parametro[1], parametro[2], parametro[3]);
            }


        });
    }

    function fnGoogleMapsAutoCenter() {
        var vSourceVector = olSourceVector.getExtent();

        if (olLayerVector.getSource().getFeatures().length > 0) {

            GoogleMaps.getView().fit(vSourceVector, GoogleMaps.getSize());
            if (GoogleMaps.getView().getZoom() > 15) GoogleMaps.getView().setZoom(15);

        }

        fnOnClickNavbar('div-seguimiento', false);

    }

    function fnOnClickMarkerPdv(__a, __b, __c) {
        var __oPdv = [];
        __oPdv = __Contenedor[__a].gestor[__b].pdv[__c];
        GoogleMaps.removeLayer(olLayerVector);
        GoogleMaps.removeLayer(vectorLayer);
        fnGoogleMapsRemovesMarker();
        fnGoogleMapsRemovesMarkerGeneral();
        fnGoogleMapsSetMap(__oPdv, __a, __b, __c, true);
        fnOpenLayerAddLayer();
        fnGoogleMapsAutoCenter();
    }

    function fnOnClickMarkerTodosGie() {
        if (__Contenedor.length == 0) {
            alert('Es necesario realizar una consulta para mostrar todas las marcaciones!')
        } else {
            clearInterval(myVar1);
            myVar1 = null;
            __indicadorGeneral = 0;
            __indicadorMenuIzquierdo = 0;
            gestorlocation = '';
            indicador = 0;
            GoogleMaps.removeLayer(vectorLayer);
            fnGoogleMapsRemovesMarker();
            fnGoogleMapsRemovesMarkerGeneral();

            for (let i = 0; i < __Contenedor.length; i++) {
                for (let ii = 0; ii < __Contenedor[i].gestor.length; ii++) {
                    $.each(__Contenedor[i].gestor[ii].pdv, function (iii, v) {
                       // if (v.estado != "NO VISITADO") {
                            fnGoogleMapsSetMapTodos(v, i, ii, iii, false);
                      //  }
                    });
                }
            }

            fnOpenLayerAddLayer();
            fnGoogleMapsAutoCenter();    
        }
    }

    function fnOnClickMarkerGie(__a, __b, __c) {

        clearInterval(myVar2);
        myVar2 = null;
        __indicadorGeneral = 0;
        __indicadorMenuIzquierdo = 0;
        gestorlocation = '';
        indicador = 0;
        GoogleMaps.removeLayer(vectorLayer);
        fnGoogleMapsRemovesMarker();
        fnGoogleMapsRemovesMarkerGeneral();

        $.each(__Contenedor[__a].gestor[__b].pdv, function (i, v) {

            //if (v.estado != "NO VISITADO") {
                fnGoogleMapsSetMap(v, __a, __b, i, false);
            //}
        });
        fnOpenLayerAddLayer();
        fnGoogleMapsAutoCenter();
    }

    function fnOnClickMarkerDetalle(__a, __b, __c, __d) {
        var __oPdv = [],
            __Caption = $('#gmap-popup > div > .thumbnail > span'),
            __Button = $('#gmap-popup > div > .thumbnail > button'),
            __Content = $('#gmap-popup > div > .thumbnail > .caption'),
            __Gallery = $('#gmap-popup > div > .thumbnail > .gallery');
            
        __Button.hide();
        __Content.show();
        __Gallery.hide();
    
        __Content.empty();
        __Gallery.empty();
    
        __oPdv = __Contenedor[__a].gestor[__b].pdv[__c];
        
        __Caption.html('<a href="javascript:window.open(\'https://www.google.com/maps?q&layer=c&cbll=' + __oPdv.lat + ',' + __oPdv.lon + '&cbp=12,0,0,0,0&z=18\', \'\', \'width=430,height=650,left=0,top=0,location=no\');"><i class="fa fa-street-view" style="color:#f1c40f;"></i> ' + __oPdv.nombre + '</a>');
    
        __Content.append('<p><b>Dirección:</b> ' + __oPdv.direccion + '</p>');
        __Content.append('<p><b>Fecha:</b> ' + __oPdv.fecha + ' de ' + __oPdv.h_ini + ' a ' + __oPdv.h_fin + ' (gestión ' + __oPdv.duracion + ' min).</p>');
        __Content.append('<p><b>Distancia:</b> ' + __oPdv.distancia + '</p>');
        __Content.append('<p><b>Estado:</b> ' + __oPdv.estado + '</p>');
        if (__oPdv.foto_no_visita.length == 0) {
            
        } else {
            __Content.append('<p><b>Motivo no visita:</b>'+ __oPdv.foto_no_visita +' </p>');
        }
    
        $('#gmap-popup').modal('show');
    }

    function fnBuildGridWithData(data){

        if (!data || !Array.isArray(data) || data.length === 0) {
            console.warn("No hay datos para mostrar");
            // Mostrar mensaje en la tabla
            tbody_table_seguimiento.innerHTML = '<tr><td colspan="10" class="text-center">No se encontraron registros para las fechas seleccionadas</td></tr>';
            return;  // Salir de la función
        }
        var suma_pdv = 0;
        var suma_atendidos = 0;
        var suma_proceso = 0;
        var suma_pendiente = 0;
        var efectividad = 0
        var suma_gestores = 0;

        //COLOCANTO DATA SUPERVISORES
        let cant_supervisores = Object.keys(data).length;
        data_supervisor.innerHTML = ' ' + cant_supervisores + ' ';

        $.each(data, function (i, v) {
            if (!v.gestor || !Array.isArray(v.gestor)) {
                console.warn("Gestor no encontrado para supervisor:", v);
                return true; // Continuar con el siguiente
            }
            let cant_gestores = Object.keys(v.gestor).length;

            let tr_supervisor = document.createElement('tr');
            tr_supervisor.className = 'tr-supervisor';
            
            let td_supervisor = document.createElement('td');
            td_supervisor.setAttribute('colspan','10');

            let a_supervisor = document.createElement('a');
            a_supervisor.setAttribute('href','javascript:');
            a_supervisor.addEventListener('click',(e)=>{
                e.stopPropagation();
                var isVisible = $('.row-' + v.id).is(':visible');
                fnOnClickSupervisor(v.id);

                if (isVisible) {
                    // Se cerró -> mostrar general
                    mostrarEfectividadGeneral();
                } else {
                    //! SEMAFORIZACIÓN GENERAL
                    suma_pdv = 0;
                    suma_atendidos = 0;
                    suma_proceso = 0;
                    suma_pendiente = 0;
                    efectividad = 0
                    suma_gestores = 1;
                    cant_supervisores = 1;

                    let spanExistente = data_efectividad.querySelector('span.dot');
                    if (spanExistente) {
                        spanExistente.remove();
                    }

                    $.each(v.gestor, function (ii, iv) {
                        $.each(iv.pdv, function (iii, iiv) {
                            if (iiv.estado === 'ATENDIDO') {
                                suma_atendidos += 1;
                            }
                            else if (iiv.estado === 'EN PROCESO') {
                                suma_proceso += 1;
                            }
                            else if (iiv.estado === 'NO VISITADO') {
                                suma_pendiente += 1;
                            }

                            suma_pdv = suma_atendidos + suma_proceso + suma_pendiente;
                            data_visitados.innerHTML = '' + suma_atendidos + '';
                            data_proceso.innerHTML = '' + suma_proceso + '';
                            data_pendientes.innerHTML = '' + suma_pendiente + '';

                            data_pdv.innerHTML = ' ' + suma_pdv + ' ';

                            table_seguimiento.append(tbody_table_seguimiento);
                            efectividad = (suma_atendidos / suma_pdv) * 100;
                            data_gestores.innerHTML = ' ' + suma_gestores + ' ';
                            data_efectividad.innerHTML = ' ' + efectividad.toFixed(1) + '%';
                            // data_efectividad.innerHTML = ' ' + efectividad.toFixed(1) + '% <i class="fa fa-info-circle" style="font-size:14px; margin-left:4px; cursor:help;" title="Efectividad = (PDVs ATENDIDOS / Total PDVs Asignados) * 100"></i>';
                            data_supervisor.innerHTML = ' ' + cant_supervisores + ' ';
                            
                            // SEMÁFORO GENERAL
                            var semaforo_general = 'background-color:';
                            if (efectividad < 50) {
                                semaforo_general += '#FF2D00'; //ROJO
                            }
                            if (efectividad >= 50 && efectividad < 85) {
                                semaforo_general += '#F7FF00'; //AMARILLO
                            }
                            if (efectividad >= 85) {
                                semaforo_general += '#4DFF00'; //VERDE
                            }

                            let span_efectividad = document.createElement('span');
                            span_efectividad.className = 'dot';
                            span_efectividad.setAttribute('style', semaforo_general += ';margin: 3px 0px;float: right;');

                            data_efectividad.append(span_efectividad);
                        });
                    });
                }
            });
            a_supervisor.innerHTML = ' ' + v.nombre;


            let sup_efectividad = 0;
            let sup_atendidos = 0;
            let sup_total = 0;

            $.each(v.gestor, function (ii, iv) {
                $.each(iv.pdv, function (iii, iiv) {
                    if (iiv.estado === 'ATENDIDO' /*|| iiv.estado === 'EN PROCESO'*/) {
                        sup_atendidos += 1;
                    }
                    sup_total += 1;
                });
            });
            sup_efectividad = sup_total > 0 ? (sup_atendidos / sup_total) * 100 : 0;

            var semaforo_style = 'background-color:';
            if (sup_efectividad < 50) {
                semaforo_style += '#FF2D00'; //ROJO
            } else if (sup_efectividad >= 50 && sup_efectividad < 85) {
                semaforo_style += '#F7FF00'; //AMARILLO
            } else if (sup_efectividad >= 85) {
                semaforo_style += '#4DFF00'; //VERDE
            }

            let span_supervisor = document.createElement('span');
            span_supervisor.className = 'dot';
            span_supervisor.setAttribute('style', semaforo_style + ';margin: 0px 30px;float: right;');

            let i_supervisor = document.createElement('i');
            i_supervisor.className = 'fa fa-angle-double-right';
            i_supervisor.setAttribute('aria-hidden','true');
            
            a_supervisor.prepend(i_supervisor);
            a_supervisor.innerHTML += ' ' + v.nombre + ' (' + sup_efectividad.toFixed(1) + '% PDVs VISITADOS)';
            // a_supervisor.innerHTML += ' ' + v.nombre + ' (' + sup_efectividad.toFixed(1) + '% PDVs VISITADOS) <i class="fa fa-info-circle" style="font-size:12px; margin-left:4px; cursor:help;" title="Efectividad = (PDVs ATENDIDOS / Total PDVs Asignados) * 100"></i>';
            td_supervisor.append(a_supervisor);
            td_supervisor.append(span_supervisor); 
            tr_supervisor.append(td_supervisor);
            tbody_table_seguimiento.append(tr_supervisor);

            $.each(v.gestor, function (ii, iv) {
                let tr_gestor = document.createElement('tr');
                tr_gestor.className = 'tr-gie row-' + v.id;
                tr_gestor.setAttribute('style','display: none;');

                let td_gestor = document.createElement('td');
                td_gestor.setAttribute('colspan','10');
                td_gestor.innerHTML = ' ';

                let a_gestor = document.createElement('a');
                a_gestor.setAttribute('href','javascript:');
                a_gestor.addEventListener('click', ()=>{
                    fnOnClickGie(v.id,iv.id);
                    //! SEMAFORIZACIÓN GENERAL
                    suma_pdv = 0;
                    suma_atendidos = 0;
                    suma_proceso = 0;
                    suma_pendiente = 0;
                    efectividad = 0;
                    suma_gestores = 1;
                    cant_supervisores = 1;

                    $.each(iv.pdv, function (iii, iiv) {
                        if (iiv.estado === 'ATENDIDO') {
                            suma_atendidos += 1;
                        }
                        else if (iiv.estado === 'EN PROCESO') {
                            suma_proceso += 1;
                        }
                        else if (iiv.estado === 'NO VISITADO') {
                            suma_pendiente += 1;
                        }

                        suma_pdv = iv.cant_visitas;
                        data_visitados.innerHTML = '' + suma_atendidos + '';
                        data_proceso.innerHTML = '' + suma_proceso + '';
                        data_pendientes.innerHTML = '' + suma_pendiente + '';

                        data_pdv.innerHTML = ' ' + suma_pdv + ' ';

                        table_seguimiento.append(tbody_table_seguimiento);
                        efectividad = (suma_atendidos / suma_pdv) * 100;
                        data_gestores.innerHTML = ' ' + suma_gestores + ' ';
                        data_efectividad.innerHTML = ' ' + efectividad.toFixed(1) + '%';
                        // data_efectividad.innerHTML = ' ' + efectividad.toFixed(1) + '% <i class="fa fa-info-circle" style="font-size:14px; margin-left:4px; cursor:help;" title="Efectividad = (PDVs ATENDIDOS / Total PDVs Asignados) * 100"></i>';
                        data_supervisor.innerHTML = ' ' + cant_supervisores + ' ';

                        // SEMÁFORO GENERAL
                        var semaforo_general = 'background-color:';
                        if (efectividad < 50) {
                            semaforo_general += '#FF2D00'; //ROJO
                        }
                        if (efectividad >= 50 && efectividad < 85) {
                            semaforo_general += '#F7FF00'; //AMARILLO
                        }
                        if (efectividad >= 85) {
                            semaforo_general += '#4DFF00'; //VERDE
                        }

                        // Eliminar semáforo anterior si existe
                        let spanExistente = data_efectividad.querySelector('span.dot');
                        if (spanExistente) {
                            spanExistente.remove();
                        }

                        let span_efectividad = document.createElement('span');
                        span_efectividad.className = 'dot';
                        span_efectividad.setAttribute('style', semaforo_general + ';margin: 3px 0px;float: right;');
                        data_efectividad.append(span_efectividad);
                    });
                    
                });





     

                a_gestor.innerHTML = ' ' + iv.nombre + ' (VISITAS ' + iv.cant_visitas + ')'
                let ges_efectividad = 0;
                let ges_atendidos = 0;
                let ges_total = iv.cant_visitas || 0;
                
                $.each(iv.pdv, function (iii, iiv) {
                    if (iiv.estado === 'ATENDIDO') {
                        ges_atendidos += 1;
                    }
                });
                ges_efectividad = ges_total > 0 ? (ges_atendidos / ges_total) * 100 : 0;

                // SEMÁFORO DEL GESTOR
                var semaforo_style_gestor = 'background-color:';
                if (ges_efectividad < 50) {
                    semaforo_style_gestor += '#FF2D00'; //ROJO
                } else if (ges_efectividad >= 50 && ges_efectividad < 85) {
                    semaforo_style_gestor += '#F7FF00'; //AMARILLO
                } else if (ges_efectividad >= 85) {
                    semaforo_style_gestor += '#4DFF00'; //VERDE
                }

                let span_gestor = document.createElement('span');
                span_gestor.className = 'dot';
                // span_gestor.setAttribute('style', semaforo_style_gestor + ';margin: 0px 30px;float: right;');
                // span_gestor.setAttribute('style', semaforo_style_gestor + ';margin: 0px 5px 0px 10px;float: right;');
                span_gestor.setAttribute('style', semaforo_style_gestor + ';margin: 0px 10px 0px 5px;float: right;');
                // ========== FIN SEMÁFORO GESTOR ==========

                let i_gestor = document.createElement('i');
                i_gestor.className = 'fa fa-angle-right';
                i_gestor.setAttribute('aria-hidden','true');
                a_gestor.prepend(i_gestor);
                a_gestor.innerHTML += ' ' + iv.nombre + ' (VISITAS ' + iv.cant_visitas + ') (' + ges_efectividad.toFixed(1) + '% PDVs VISITADOS)';

                // td_gestor.append(a_gestor);
                // td_gestor.append(span_gestor); // <--- SEMÁFORO DEL GESTOR

                let btn_marker_gestor = document.createElement('button');
                btn_marker_gestor.className = 'btn btn-success btn-xs';
                btn_marker_gestor.setAttribute('style','padding: 0px 10px;float: right;');
                btn_marker_gestor.addEventListener('click', ()=>{
                    fnOnClickMarkerGie(i,ii,iv.id);
                });

                let i_btn_marker_gestor = document.createElement('i');
                i_btn_marker_gestor.className = 'fa fa-map-marker';
                btn_marker_gestor.append(i_btn_marker_gestor);

                td_gestor.append(btn_marker_gestor);
                td_gestor.append(span_gestor);
                td_gestor.append(a_gestor);
                tr_gestor.append(td_gestor);
                tbody_table_seguimiento.append(tr_gestor);


                $.each(iv.pdv, function (iii, iiv) {
    // console.log(iiv.estado);

    if(iiv.estado === 'ATENDIDO'){
        suma_atendidos += 1;
    }
    else if(iiv.estado === 'EN PROCESO'){
        suma_proceso += 1;
    } 
    else if(iiv.estado === 'NO VISITADO'){
        suma_pendiente += 1;
    }
    
    let tr_pdv = document.createElement('tr');
    tr_pdv.className = 'tr-pdv row-' + v.id + ' row-' + v.id + '-' + iv.id;
    tr_pdv.setAttribute('style','display: none;');

    let td_pdv_1 = document.createElement('td');
    td_pdv_1.className = 'text-center hidden-xs';
    td_pdv_1.innerHTML = iiv.id;
    tr_pdv.append(td_pdv_1);

    let td_pdv_2 = document.createElement('td');
    
    let a_td_pdv_2 = document.createElement('a');
    a_td_pdv_2.setAttribute('href','javascript:');
    a_td_pdv_2.addEventListener('click',()=>{
        fnOnClickMarkerPdv(i,ii,iii);
    });
    a_td_pdv_2.innerHTML = ' ' + iiv.nombre + ' -- ' + iiv.visual ;
    
    let i_a_td_pdv_2 = document.createElement('i');
    i_a_td_pdv_2.className = 'fa fa-map-marker';
    i_a_td_pdv_2.setAttribute('style','color:#2ECC71');
    a_td_pdv_2.prepend(i_a_td_pdv_2);

    td_pdv_2.append(a_td_pdv_2);
    tr_pdv.append(td_pdv_2);

    let td_pdv_3 = document.createElement('td');
    td_pdv_3.className = 'text-center hidden-xs';
    td_pdv_3.innerHTML = iiv.fecha;
    tr_pdv.append(td_pdv_3);

    let td_pdv_4 = document.createElement('td');
    td_pdv_4.className = 'text-center hidden-xs';
    td_pdv_4.innerHTML = iiv.h_ini;
    tr_pdv.append(td_pdv_4);

    let td_pdv_5 = document.createElement('td');
    td_pdv_5.className = 'text-center hidden-xs';
    td_pdv_5.innerHTML = iiv.h_fin;
    tr_pdv.append(td_pdv_5);

    let td_pdv_6 = document.createElement('td');
    td_pdv_6.className = 'text-center';
    td_pdv_6.innerHTML = iiv.duracion;
    tr_pdv.append(td_pdv_6);

    let td_pdv_7 = document.createElement('td');
    td_pdv_7.className = 'text-center hidden-xs';
    td_pdv_7.innerHTML = iiv.distancia;
    tr_pdv.append(td_pdv_7);

    let td_pdv_8 = document.createElement('td');
    td_pdv_8.className = 'text-center hidden-xs';
    td_pdv_8.innerHTML = iiv.estado;
    tr_pdv.append(td_pdv_8);

    let td_pdv_9 = document.createElement('td');
    td_pdv_9.innerHTML = iiv.tipo_relevo;
    tr_pdv.append(td_pdv_9);

    //#region  KPIS - 15/12/2025
    let td_pdv_10 = document.createElement('td');
    td_pdv_10.style.width = '100px'; 
    td_pdv_10.style.minWidth = '100px';
    let btn_kpis = document.createElement('button');
    btn_kpis.className = 'btn btn-kpis btn-xs';
    btn_kpis.setAttribute('title','Ver KPIs');
    btn_kpis.innerHTML = '<i class="fa fa-clipboard"></i> KPIS';

    //#region  MODAL KPIS
    btn_kpis.addEventListener('click', () => {
        // Obtener datos
        var pdvData = iiv;
        var gestorData = iv;
        var supervisorData = v;
        // console.log("Datos del gestor:", gestorData);
        // console.log("Nombre del gestor:", gestorData.nombre);
        // console.log("Datos del PDV:", pdvData);
        // console.log("subchannel:", pdvData.subchannel);
        // console.log("ID PDV:", pdvData.id);
        // console.log("Código PDV:", pdvData.codigo);
        // console.log("Fecha:", pdvData.fecha);

        // Mostrar loading
        $('#kpis-modal .modal-body').html(`
            <div class="text-center" style="padding: 40px;">
                <i class="fa fa-spinner fa-spin fa-3x"></i>
                <p>Cargando información de KPIs ${pdvData.nombre}</p>
            </div>
        `);
        $('#kpis-modal').modal('show');

        // Usar la función
        obtenerCategoriasReportadas(pdvData, gestorData.nombre, function(error, data) {
            if (error) {
                $('#kpis-modal .modal-body').html(`
                    <div class="alert alert-danger">
                        <i class="fa fa-exclamation-triangle"></i>
                        Error al cargar las categorías: ${error}
                    </div>
                `);
                return;
            }

            // console.log("Datos recibidos:", data);
            
            let kpis_categorias = [];
            let fotos = { ENTRADA: '', SALIDA: '' };

            if (data && data.fotos) {
                fotos = data.fotos;
            }
            
            // Dentro del event listener del botón KPIS, después de recibir data:

            if (data && data.estados && Array.isArray(data.estados)) {
                // Obtener los módulos activos para saber qué campos esperar
                var modulosActivos = data.modulos_activos || {};
                var subcategorias = data.subcategorias || {};
                
                // Mapear estados asegurando que todos los campos de módulos activos estén presentes
                kpis_categorias = data.estados.map(function(estado) {
                    var item = {
                        nombre: estado.categoria,
                        subcategoria: estado.subcategoria || ''
                    };
                    
                    // Agregar cada módulo activo al item
                    for (var modulo in modulosActivos) {
                        var campo = modulo.toLowerCase();
                        // Si el estado tiene el campo, usarlo, si no, false
                        item[campo] = estado[campo] === true ? 1 : 0;
                    }
                    
                    return item;
                });
                
                // console.log("=== KPIS CATEGORIAS PROCESADAS ===");
                // console.log("Módulos activos:", modulosActivos);
                // console.log("Subcategorías:", subcategorias);
                // console.log("kpis_categorias:", kpis_categorias);
            }

            // Si no hay categorías reportadas
            if (kpis_categorias.length === 0) {
                let kpisContent = `
                    <div class="kpis-modal-content">
                        <div class="kpis-main-layout">
                            <div class="kpis-left-column">
                                <div class="pdv-section">
                                    <h4 class="pdv-title">PUNTO DE VENTA (${pdvData.fecha})</h4>
                                    <h4 class="pdv-name">${pdvData.nombre || 'Sin nombre'}</h4>
                                </div>
                                <div class="gestor-section">
                                    <div class="gestor-header"><strong>DATOS GENERALES DEL GESTOR</strong></div>
                                    <div class="gestor-details">
                                        <p><strong>Gestor:</strong> ${gestorData.nombre || 'No disponible'}</p>
                                        <p><strong>Supervisor Responsable:</strong> ${supervisorData.nombre || 'No disponible'}</p>
                                    </div>
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
                        <div class="kpis-table-section">
                            <div class="alert alert-info">
                                <i class="fa fa-info-circle"></i>
                                No se encontraron categorías reportadas para este PDV en la fecha ${pdvData.fecha}
                            </div>
                        </div>
                    </div>
                `;
                $('#kpis-modal .modal-body').html(kpisContent);
                return;
            }


            // Obtener los módulos activos de la respuesta
            var modulosActivos = data.modulos_activos || {};
            
            // Construir encabezados dinámicamente
            var headers = ['CATEGORÍAS', 'SUBCATEGORIA'];
            var camposModulos = [];

            // Mapeo de nombres cortos para mostrar en la tabla
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
                // 'ALMUERZO': 'ALMUERZO',
                'ENCUESTA_APP': 'ENCUESTA'
            };

            // Agregar cada módulo activo como columna
            for (var modulo in modulosActivos) {
                var nombreCorto = nombresCortos[modulo] || modulo.substring(0, 4);
                headers.push(nombreCorto);
                camposModulos.push(modulo.toLowerCase());
            }

    
            // Construir el HTML de la tabla dinámicamente
            let kpisContent = `
                <div class="kpis-modal-content">
                    <div class="kpis-main-layout">
                        <div class="kpis-left-column">
                            <div class="pdv-section">
                                <h4 class="pdv-title">PUNTO DE VENTA (${pdvData.fecha})</h4>
                                <h4 class="pdv-name">${pdvData.nombre || 'Sin nombre'}</h4>
                            </div>
                            <div class="gestor-section">
                                <div class="gestor-header"><strong>DATOS GENERALES DEL GESTOR</strong></div>
                                <div class="gestor-details">
                                    <p><strong>Gestor:</strong> ${gestorData.nombre || 'No disponible'}</p>
                                    <p><strong>Supervisor Responsable:</strong> ${supervisorData.nombre || 'No disponible'}</p>
                                    <p><strong>RE:</strong> ${pdvData.subchannel || 'No disponible'}</p>
                                </div>
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
                        <div class="kpis-table-container">
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

            kpis_categorias.forEach(function(cat) {
                var nombreCategoria = cat.nombre;
                var subcategoriasArray = [];
                
                // Obtener array de subcategorías
                if (cat.subcategoria && cat.subcategoria !== 'N/A' && cat.subcategoria !== '') {
                    // Si viene como string separado por comas
                    subcategoriasArray = cat.subcategoria.split(',').map(function(s) { return s.trim(); });
                    // Filtrar elementos vacíos
                    subcategoriasArray = subcategoriasArray.filter(function(s) { return s !== ''; });
                }
                
                // Si tiene subcategorías, crear una fila por cada una
                if (subcategoriasArray.length > 0) {
                    subcategoriasArray.forEach(function(subcat) {
                        if (subcat && subcat.trim() !== '') {
                            var fila = {
                                categoria: nombreCategoria,
                                subcategoria: subcat.trim(),
                                modulos: {}
                            };
                            
                            // Copiar valores de módulos (heredados de la categoría padre)
                            camposModulos.forEach(function(campo) {
                                fila.modulos[campo] = cat[campo] || false;
                            });
                            
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
                    
                    camposModulos.forEach(function(campo) {
                        fila.modulos[campo] = cat[campo] || false;
                    });
                    
                    filasPlanas.push(fila);
                }
            });

            // ============================================================
            // 2. GENERAR FILAS HTML
            // ============================================================

            if (filasPlanas.length === 0) {
                kpisContent += `
                    <tr>
                        <td colspan="${headers.length}" style="text-align:center; padding:20px; color:#999;">
                            <i class="fa fa-info-circle"></i> No hay categorías reportadas
                        </td>
                    </tr>
                `;
            } else {
                filasPlanas.forEach(function(fila) {
                    var isNA = fila.subcategoria === 'N/A';
                    var row = `<tr>
                        <td style="font-weight: bold; font-size: 12px; white-space: nowrap;">${fila.categoria}</td>
                        <td style="text-align: left; font-size: 11px; color: ${isNA ? '#bbb' : '#555'}; max-width: 200px; word-wrap: break-word;">
                            ${isNA ? '<span style="font-style:italic;">N/A</span>' : fila.subcategoria}
                        </td>`;
                    
                    // Celdas de módulos
                    camposModulos.forEach(function(campo) {
                        var valor = fila.modulos[campo] || false;
                        row += `<td style="text-align: center;" class="kpi-indicator ${valor ? 'kpi-yes' : 'kpi-no'}">
                            ${valor ? '<span class="check-box">✓</span>' : ''}
                        </td>`;
                    });
                    row += `</tr>`;
                    kpisContent += row;
                });
            }

            // ============================================================
            // 3. FILA DE PORCENTAJES - PROMEDIO ENTRE CATEGORÍAS Y SUBCATEGORÍAS
            // ============================================================

            // 3.1 Obtener total de categorías únicas
            var categoriasUnicas = {};
            var totalCategorias = 0;
            kpis_categorias.forEach(function(cat) {
                if (!categoriasUnicas[cat.nombre]) {
                    categoriasUnicas[cat.nombre] = true;
                    totalCategorias++;
                }
            });

            // 3.2 Obtener total de subcategorías (filas planas)
            var totalSubcategorias = filasPlanas.length;

            // 3.3 Calcular porcentajes para cada módulo
            var porcentajes = {};

            camposModulos.forEach(function(campo) {
                // 3.3.1 Contar categorías con ✓ en este módulo
                var categoriasConCheck = {};
                var countCategorias = 0;
                
                kpis_categorias.forEach(function(cat) {
                    if (cat[campo] && !categoriasConCheck[cat.nombre]) {
                        categoriasConCheck[cat.nombre] = true;
                        countCategorias++;
                    }
                });
                
                // 3.3.2 Contar subcategorías con ✓ en este módulo
                var countSubcategorias = 0;
                filasPlanas.forEach(function(fila) {
                    if (fila.modulos[campo]) {
                        countSubcategorias++;
                    }
                });
                
                // 3.3.3 Calcular porcentajes
                var pctCategorias = totalCategorias > 0 ? (countCategorias / totalCategorias) * 100 : 0;
                var pctSubcategorias = totalSubcategorias > 0 ? (countSubcategorias / totalSubcategorias) * 100 : 0;
                
                // 3.3.4 Promedio final
                porcentajes[campo] = (pctCategorias + pctSubcategorias) / 2;
            });

            // 3.4 Construir fila de porcentajes
            var rowPorcentajes = `<tr style="background-color: #f8f9fa; font-weight: bold; border-top: 2px solid #ddd;">
                <td style="text-align: left; font-weight: bold;">% CATEGORIAS REPORTADAS:</td>
                <td style="text-align: left; color: #999;">% SUBCATEGORIAS REPORTADAS</td>`;

            camposModulos.forEach(function(campo) {
                var valor = porcentajes[campo] || 0;
                rowPorcentajes += `<td style="text-align: center;">${Math.round(valor)}%</td>`;
            });
            rowPorcentajes += `</tr>`;
            kpisContent += rowPorcentajes;

            // Cerrar la tabla
            kpisContent += `
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            `;

            // ============================================================
            // AGREGAR BOTÓN DE DESCARGA EXCEL (antes de asignar al modal)
            // ============================================================
            var pdvNombre = (pdvData.nombre || pdvData.pos_name || 'PDV').replace(/[^a-zA-Z0-9]/g, '_');
            var fechaDescarga = pdvData.fecha || new Date().toISOString().split('T')[0];
            var nombreArchivo = 'KPIs_' + pdvNombre + '_' + fechaDescarga + '.xls';

            kpisContent += `
                <div style="text-align: right; margin-top: 15px; padding: 10px 0;">
                    <button onclick="descargarExcelKPIS('${nombreArchivo}')" 
                            class="btn btn-success btn-sm" 
                            style="background: #28a745; color: white; border: none; padding: 8px 20px; border-radius: 5px; cursor: pointer; font-size: 13px;">
                        <i class="fa fa-file-excel-o"></i> Descargar Excel
                    </button>
                </div>
            `;

            $('#kpis-modal .modal-body').html(kpisContent);
        });
    });
    td_pdv_10.append(btn_kpis);
    tr_pdv.append(td_pdv_10);

    //#endregion

    tbody_table_seguimiento.append(tr_pdv);
});
                // suma_pdv = suma_pdv + iv.cant_visitas;  
                suma_pdv += iv.pdv.length;
                data_visitados.innerHTML = ''+ suma_atendidos +'';
                data_proceso.innerHTML = ''+ suma_proceso +'';
                data_pendientes.innerHTML = ''+ suma_pendiente +'';
            });
            data_pdv.innerHTML = ' ' + suma_pdv + ' ';
            suma_gestores = suma_gestores + cant_gestores;
        });
        table_seguimiento.append(tbody_table_seguimiento);
        efectividad = (suma_atendidos / suma_pdv)*100;
        // $efectividad = $suma_pdv > 0 ? ($suma_atendidos / $suma_pdv) * 100 : 0;
        data_gestores.innerHTML = ' ' + suma_gestores + ' ';
        data_efectividad.innerHTML = ' '+ efectividad.toFixed(1) + '%';

        var semaforo_general = 'background-color:';
        if (efectividad < 50) {
            semaforo_general += '#FF2D00'; //ROJO
        }
        if (efectividad >= 50 && efectividad < 85) {
            semaforo_general += '#F7FF00'; //AMARILLO
        }
        if (efectividad >= 85) {
            semaforo_general += '#4DFF00'; //VERDE
        }

        // Verificar si ya existe un span en data_efectividad para no duplicar
        let spanExistente = data_efectividad.querySelector('span.dot');
        if (!spanExistente) {
            let span_efectividad = document.createElement('span');
            span_efectividad.className = 'dot';
            span_efectividad.setAttribute('style', semaforo_general + ';margin: 3px 0px;float: right;');
            data_efectividad.append(span_efectividad);
        } else {
            // Actualizar el color si ya existe
            spanExistente.setAttribute('style', semaforo_general + ';margin: 3px 0px;float: right;');
        }
    }
});
