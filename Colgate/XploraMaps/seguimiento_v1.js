$(document).ready(()=>{
    /**
     * Inicio Datos de Prueba
     */
    // let supervisor;

    // $.ajax({
    //     url: "/App/XploraEcuador/mantenimiento_bases/epsonxp/getters/getJsonGps.php",
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

    /*supervisor = [
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
                            estado: 'ATENDIDO',
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
                    cant_visitas: 1,
                    pdv: [
                        {
                            id: 1,
                            ordenTotal: 4,
                            nombre: 'PDV 1',
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
        }
    ];*/
    // console.log(supervisor);
    /**
     * Fin Datos de Prueba
     */

    let div_body = document.getElementById('div-body');

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

    thead_table_seguimiento.append(tr_1_thead_table_seguimiento);
    table_seguimiento.append(thead_table_seguimiento);

    let tbody_table_seguimiento = document.createElement('tbody');
    let tr_tbody_table_seguimiento = document.createElement('tr');

    let td_1_tr_tbody_table_seguimiento = document.createElement('td');
    td_1_tr_tbody_table_seguimiento.setAttribute('colspan','9');
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
    let __Contenedor = [];
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

    function esFormularioValido(f_inicio, f_fin) {
        if (!f_inicio) {
            alert("Debe escoger una fecha de inicio");
            return false;
        }
        if (!f_fin) {
            alert("Debe escoger una fecha fin");
            return false;
        }
        return true;
    }

    function fnOnClickBusca(f_inicio, f_fin) {
        f_inicio = $('#f_inicio').val();
        f_fin = $('#f_fin').val();

        if (esFormularioValido(f_inicio, f_fin)) {
            __Parametro = [];
            __Parametro.push(f_inicio);
            __Parametro.push(f_fin);

            // __Parametro.push($('#in-inicio').val());
            // __Parametro.push($('#in-fin').val());

            $.ajax({
                beforeSend: function (__s) {
                    tbody_table_seguimiento.innerHTML = '';
                },
                url: '/App/XploraEcuador/mantenimiento_bases/epsonxp/getters/getJsonGps.php',
                type: 'post',
                dataType: 'json',
                data: {parametros: __Parametro},
                success: function (__s) {
                    if (__s == null || __s.length == 0) {
                        alert("No hay datos a mostrar en este rango de fechas: " + f_inicio + " " + f_fin);
                        // return;
                    } else {
                        __Contenedor = __s;
                        console.log(JSON.stringify(__Contenedor));
                        fnBuildGridWithData(__Contenedor);
                    }
                },
                complete: function () {
                    fnOnClickNavbar('div-seguimiento', true);
                },
                error: function (__s) {
                    console.error(__s);
                }
            });

            // $.ajax({
            //     beforeSend: function (__s) {
            //         tbody_table_seguimiento.innerHTML = '';
            //     },
            //     //Cambiar esta sección | Inicio
            //     url: '/App/XploraEcuador/mantenimiento_bases/epsonxp/getters/getJsonGps.php',
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
        
            // tbody_table_seguimiento.innerHTML = '';
            // __Contenedor = supervisor;
            // console.log(JSON.stringify(__Contenedor));
            // fnBuildGridWithData(supervisor);
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

    function fnGoogleMapsSetMapTodos(__a, __b, __c, __d, __e) {
        var olFeature = new ol.Feature({
            id: __b + '_' + __c + '_' + __d + '_' + 0,
            geometry: new ol.geom.Point(ol.proj.transform([parseFloat(__a.lon), parseFloat(__a.lat)], 'EPSG:4326', 'EPSG:3857')),
            name: __a.nombre
        });

        var olStyle = new ol.style.Style({
            image: new ol.style.Icon(({
                src: 'http://chart.apis.google.com/chart?chst=d_map_pin_letter&chld=' + __a.ordenTotal + '|2ECC71|000000'
            }))
        });

        olFeature.setStyle(olStyle);
        GoogleMarkersList.push(olFeature);
    }

    function fnGoogleMapsSetMap(__a, __b, __c, __d, __e) {
        var olFeature = new ol.Feature({
            id: __b + '_' + __c + '_' + __d + '_' + 0,
            geometry: new ol.geom.Point(ol.proj.transform([parseFloat(__a.lon), parseFloat(__a.lat)], 'EPSG:4326', 'EPSG:3857')),
            name: __a.nombre
        });

        var olStyle = new ol.style.Style({
            image: new ol.style.Icon(({
                src: 'http://chart.apis.google.com/chart?chst=d_map_pin_letter&chld=' + __a.id + '|2ECC71|000000'
            }))
        });

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
                        if (v.estado != "NO VISITADO") {
                            fnGoogleMapsSetMapTodos(v, i, ii, iii, false);
                        }
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

            if (v.estado != "NO VISITADO") {
                fnGoogleMapsSetMap(v, __a, __b, i, false);
            }
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
        $.each(data, function (i, v) {
            let tr_supervisor = document.createElement('tr');
            tr_supervisor.className = 'tr-supervisor';
            
            let td_supervisor = document.createElement('td');
            td_supervisor.setAttribute('colspan','9');

            let a_supervisor = document.createElement('a');
            a_supervisor.setAttribute('href','javascript:');
            a_supervisor.addEventListener('click',()=>{
                fnOnClickSupervisor(v.id);
            });
            a_supervisor.innerHTML = ' ' + v.nombre;

            let i_supervisor = document.createElement('i');
            i_supervisor.className = 'fa fa-angle-double-right';
            i_supervisor.setAttribute('aria-hidden','true');
            
            a_supervisor.prepend(i_supervisor);
            td_supervisor.append(a_supervisor);
            tr_supervisor.append(td_supervisor);
            tbody_table_seguimiento.append(tr_supervisor);

            $.each(v.gestor, function (ii, iv) {
                let tr_gestor = document.createElement('tr');
                tr_gestor.className = 'tr-gie row-' + v.id;
                tr_gestor.setAttribute('style','display: none;');

                let td_gestor = document.createElement('td');
                td_gestor.setAttribute('colspan','9');
                td_gestor.innerHTML = ' ';

                let a_gestor = document.createElement('a');
                a_gestor.setAttribute('href','javascript:');
                a_gestor.addEventListener('click', ()=>{
                    fnOnClickGie(v.id,iv.id);
                });
                a_gestor.innerHTML = ' ' + iv.nombre + ' (VISITAS ' + iv.cant_visitas + ')'
                let i_gestor = document.createElement('i');
                i_gestor.className = 'fa fa-angle-right';
                i_gestor.setAttribute('aria-hidden','true');;
                a_gestor.prepend(i_gestor);
                td_gestor.append(a_gestor);

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
                tr_gestor.append(td_gestor);
                tbody_table_seguimiento.append(tr_gestor);
                $.each(iv.pdv, function (iii, iiv) {
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
                    a_td_pdv_2.innerHTML = ' ' + iiv.nombre;
                    
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

                    tbody_table_seguimiento.append(tr_pdv);
                });
            });
        });
        table_seguimiento.append(tbody_table_seguimiento);
    }
});