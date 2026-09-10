var modal = document.querySelector('.detalles'),
  modalTest = document.querySelector('.detalle-test'),
  modalImagen = document.querySelector('.detalle-imagen'),
  tableDetalle = document.querySelector('#tb_detalle_test tbody'),
  tableDetalleImagen = document.querySelector('#tb_detalle_imagen tbody'),
  divMensaje = document.querySelector('#mensaje'),
  selectAlumno = document.getElementById('selectAlumno'),
  selectTest = document.getElementById('selectTest'),
  inputFecha = document.getElementById('filtroFecha'),
  inputFecha2 = document.getElementById('filtroFecha2'),
  tabla_detalle_resultados,
  tabla_resultados_generales,
  tabla_resultados_ev;

  let parametro = new URLSearchParams(document.location.search);
  //let cuenta = parametro.get("cuenta"); 
  let cuenta = 'colgate'; 
  console.log(cuenta);

function mostrarModal() {
  modal.classList.add('modal--show');



  //let newHtml = "<section class='detaller'><div class='modal__container'><form class='form-horizontal' action='' method='post' name='frmCSVImport' id='frmCSVImport' enctype='multipart/form-data'><div class='input-row'><h2 class='modal__title'>Cargar Excel</h2><input type='file' name='file' id='file' accept='.csv'><div class='btn-bar botones' style='margin-top: 2%;'><button type='submit' id='submit' name='import' class='btn ok-btn'>Subir</button><button type='button' class='modal_close btn cancel-btn' onclick='cerrarModal()'>Cerrar</button></div></div></form></div></section>";

  let newHtml = "<section class='detaller'><div class='modal__container'><form class='form-horizontal' action='cargar_alumnos.php' method='post' name='frmCSVImport' id='frmCSVImport' enctype='multipart/form-data'><div class='input-row'><input type='file' name='file' id='file' accept='.csv'></div></form></div></section>";

  /* Swal.fire({
      title: 'Cargar Excel',
      html: newHtml,
      showCancelButton: true,
      confirmButtonColor: '#1FCE80',
      cancelButtonColor: '#FF4748',
      confirmButtonText: 'Subir',
  }).then((result) => {
    if (result.isConfirmed) {
      document.getElementById("frmCSVImport").dispatchEvent(new CustomEvent('submit', {cancelable: true}));
      //console.log('s: ' + n);
    }
  }) */
}

function cerrarModalDetalle() {
  modalTest.classList.remove('modal--show');
  divMensaje.innerHTML = ''
  setTimeout(() => { tableDetalle.innerHTML = ''; }, 1000);
}

function cerrarModal() {
  modal.classList.remove('modal--show');

  //limpiar los campos
  $('input[name=file').val('');
  $('input[name=categoria').val(''); 
  $('input[id=materialFile').val('');
  $('input[id=file').val('');

  //desbloquear boton subir
  $("#submit").removeClass("hidden");
  $("#loading-btn").addClass("hidden");

}


tabla_resultados_generales = $('#tb_resultados').DataTable({
  dom: 'QfBrtip',
  ajax: {
    "url": "../../get_resultados_generales.php",
    "type": "POST",
    "data": function () {
      let fecha = inputFecha2.value;
      let filter_data = {
        fecha: fecha,
        cuenta:cuenta
      };
      return filter_data;
    },
  },
  buttons: ['excel'],
  language: {
    search: "Búsqueda:",
    info: "Mostrando página _PAGE_ de _PAGES_",
    loadingRecords: "Cargando...",
    infoFiltered: "(Registros filtrados de _MAX_ )",
    paginate: {
      first: "Primero",
      previous: "Anterior",
      next: "Siguiente",
      last: "Ultimo"
    },
    emptyTable: "No hay Registros"
  },
  initComplete: function () {
    var $buttons = $('.dt-buttons').hide();
    $('#btnExportar').on('click', function () {
      var btnClass = ".buttons-excel";
      if (btnClass) $buttons.find(btnClass).click();
    })
    $('#tb_resultados_filter').addClass('sss');
    $('#tb_resultados_filter input[type="search"]').addClass('input-search');
  }
})


tabla_resultados_ev = $('#tb_resultados_evaluaciones').DataTable({
  dom: 'QfBrtip',
  ajax: {
    "url": "../../get_resultados_ev.php",
    "type": "POST",
    "data": function () {
      // let fecha = inputFecha.value;
      let fecha2 = inputFecha2.value;
      let filter_data = {
        fechaHasta: fecha2,
        cuenta:cuenta
      };
      return filter_data;
    },
  },
  buttons: [{
    extend: 'excelHtml5',
      text: 'Excel',
    exportOptions: {
      columns: [1, 2, 3, 4, 5, 6, 7, 8, 9, 10]//para que no se descargue la columna Acción
    }
  }
  ],
  language: {
    search: "Búsqueda:",
    info: "Mostrando página _PAGE_ de _PAGES_",
    loadingRecords: "Cargando...",
    infoFiltered: "(Registros filtrados de _MAX_ )",
    paginate: {
      first: "Primero",
      previous: "Anterior",
      next: "Siguiente",
      last: "Ultimo"
    },
    emptyTable: "No hay Registros"
  },
  initComplete: function () {
    var $buttons = $('.dt-buttons').hide();
    $('#btnExportar').on('click', function () {
      var btnClass = ".buttons-excel";
      if (btnClass) $buttons.find(btnClass).click();
    })
    $('#tb_resultados_evaluaciones_filter').addClass('sss');
    $('#tb_resultados_evaluaciones_filter input[type="search"]').addClass('input-search');
  }
})


function setSheetName(xlsx, name) {
  // Changes tab title for sheet.
  //Params:
  //  xlsx: xlxs worksheet object.
  //  name: name for sheet.

  if (name.length > 0) {
    var source = xlsx.xl['workbook.xml'].getElementsByTagName('sheet')[0];
    source.setAttribute('name', name);
  }
}


//TABLA TEST
$('#tb_repositorioTest').DataTable({
  sScrollX: '100%',
  dom: 'QfBrtip',
    ajax: {
    "url": "../../get_test.php",
    "type": "POST",
    "data": function () {
      let filter_data = {
        cuenta: cuenta
      };
      return filter_data;
    },
  },
  buttons: [
    {
      extend: 'excelHtml5',
      text: 'Excel',
      customize: function (xlsx) {
        var sheet = xlsx.xl.worksheets['sheet1.xml'];
        var source = xlsx.xl['workbook.xml'].getElementsByTagName('sheet')[0];
        source.setAttribute('name', "repositorio_test");
        // Busca la columna I que es "activo"
        $('row c[r^="I"]', sheet).each(function () {
          // Get the value
          if ($('is t', this).text() == 'NO') {
            $('is t', this).text(0);
          } else if ($('is t', this).text() == 'SI') {
            $('is t', this).text(1);
          }
        });
      },
      exportOptions: {
        columns: [1, 2, 3, 4, 5, 6, 7, 8,9,10,11,13]//para que no se descargue la columna Acción
      }

    }
  ],
  /*aoColumnDefs: [{
    "render": function () {
      return '<div style="text-align:center" class="btn-mostrar-detalle"><span class="material-symbols-outlined">visibility</span></div>';
    },
    "aTargets": 12
  }],*/
  order: [[0, "desc"]],
  language: {
    search: "Búsqueda:",
    info: "Mostrando página _PAGE_ de _PAGES_",
    loadingRecords: "Cargando...",
    infoFiltered: "(Registros filtrados de _MAX_ )",
    paginate: {
      first: "Primero",
      previous: "Anterior",
      next: "Siguiente",
      last: "Ultimo"
    },
    emptyTable: "No hay Registros"
  },
  initComplete: function () {
    var $buttons = $('.dt-buttons').hide();
    $('#btnExportar').on('click', function () {
      var btnClass = ".buttons-excel";
      if (btnClass) $buttons.find(btnClass).click();
    })
    $('#tb_repositorioTest_filter').addClass('sss');
    $('#tb_repositorioTest_filter input[type="search"]').addClass('input-search');
  }
})



//TABLA ALUMNOS
$('#userTable').DataTable({
  dom: 'QfBrtip',
  ajax: {
    "url": "../../get_alumnos.php",
    "type": "POST",
    "data": function () {
      let filter_data = {
        cuenta: cuenta
      };
      console.log(filter_data);
      return filter_data;
    },
  },
  buttons: [
    {
      extend: 'excel',
      exportOptions: { columns: [1,2,3,4] }
    }
  ],
  aoColumnDefs: [{
    target: 4,
    visible: false,
    searchable: false
  }],
  order: [[2, "asc"]],
  language: {
    search: "Búsqueda:",
    info: "Mostrando página _PAGE_ de _PAGES_",
    loadingRecords: "Cargando...",
    infoFiltered: "(Registros filtrados de _MAX_ )",
    paginate: {
      first: "Primero",
      previous: "Anterior",
      next: "Siguiente",
      last: "Ultimo"
    },
    emptyTable: "No hay Registros"
  },
  initComplete: function () {
    var $buttons = $('.dt-buttons').hide();
    $('#btnExportar').on('click', function () {
      var btnClass = ".buttons-excel";
      if (btnClass) $buttons.find(btnClass).click();
    })
    $('#userTable_filter').addClass('sss');
    $('#userTable_filter input[type="search"]').addClass('input-search');
  }
})


//TABLA REVISION PREGUNTAS
$('#revisionTable').DataTable({
  dom: 'QfBrtip',
  ajax: {
    "url": "../../get_preguntas_abiertas.php",
    "type": "POST",
    "data": function () {
      let filter_data = {
        cuenta: cuenta
      };
      console.log(filter_data);
      return filter_data;
    },
  },
  buttons: [
    {
      extend: 'excel',
      exportOptions: { columns: [1,2,3,4] }
    }
  ],
  aoColumnDefs: [{
    target: [0,8,9,10] ,
    visible: false,
    searchable: false
  }],
  order: [[2, "asc"]],
  language: {
    search: "Búsqueda:",
    info: "Mostrando página _PAGE_ de _PAGES_",
    loadingRecords: "Cargando...",
    infoFiltered: "(Registros filtrados de _MAX_ )",
    paginate: {
      first: "Primero",
      previous: "Anterior",
      next: "Siguiente",
      last: "Ultimo"
    },
    emptyTable: "No hay Registros"
  },
  initComplete: function () {
    var $buttons = $('.dt-buttons').hide();
    $('#btnExportar').on('click', function () {
      var btnClass = ".buttons-excel";
      if (btnClass) $buttons.find(btnClass).click();
    })
    $('#revisionTable_filter').addClass('sss');
    $('#revisionTable_filter input[type="search"]').addClass('input-search');
  }
})


//TABLA REVISADAS
$('#revisadasTable').DataTable({
  dom: 'rtip',
  ajax: {
    "url": "../../get_preguntas_revisadas.php",
    "type": "POST",
    "data": function () {
      let filter_data = {
        cuenta: cuenta
      };
      console.log(filter_data);
      return filter_data;
    },
  },
  buttons: [
    {
      extend: 'excel',
      exportOptions: { columns: [1,2,3,4] }
    }
  ],
  aoColumnDefs: [{
    target: [0,11,12,13] ,
    visible: false,
    searchable: false
  }],
  order: [[2, "asc"]],
  language: {
    search: "Búsqueda:",
    info: "Mostrando página _PAGE_ de _PAGES_",
    loadingRecords: "Cargando...",
    infoFiltered: "(Registros filtrados de _MAX_ )",
    paginate: {
      first: "Primero",
      previous: "Anterior",
      next: "Siguiente",
      last: "Ultimo"
    },
    emptyTable: "No hay Registros"
  },
  initComplete: function () {
    var $buttons = $('.dt-buttons').hide();
    $('#btnExportar').on('click', function () {
      var btnClass = ".buttons-excel";
      if (btnClass) $buttons.find(btnClass).click();
    })
    $('#revisadasTable_filter').addClass('sss');
    $('#revisadasTable_filter input[type="search"]').addClass('input-search');
  }
})


//TABLA ASIGNACION
$('#tb_asignacion').DataTable({
  dom: 'Qfrtip',
  ajax: {
    "url": "../../get_asignaciones.php",
    "type": "POST",
    "data": function () {
      let filter_data = {
        cuenta: cuenta
      };
      return filter_data;
    },
  },
  pageLength: 50,
  language: {
    search: "Búsqueda:",
    info: "Mostrando página _PAGE_ de _PAGES_",
    loadingRecords: "Cargando...",
    infoFiltered: "(Registros filtrados de _MAX_ )",
    paginate: {
      first: "Primero",
      previous: "Anterior",
      next: "Siguiente",
      last: "Ultimo"
    },
    emptyTable: "No hay Registros"
  },
  initComplete: function () {
    $('#tb_asignacion_filter').addClass('sss');
    $('#tb_asignacion_filter input[type="search"]').addClass('input-search');
  }
})



// let btn_eliminar = $("<button>").attr("id", "btn_eliminar").text("Eliminar Registros").addClass('btn btn-primary');
// $('#tb_asignacion_filter').append(btn_eliminar);






//TABLA MATERIALES
$('#tb_materiales').DataTable({
  dom: 'QfBrtip',
  ajax: {
    "url": "../../get_materiales.php",
    "type": "POST",
    "data": function () {
      let filter_data = {
        cuenta: cuenta
      };
      return filter_data;
    },
  },
  buttons: ['excel'],
  fixedHeader: true,
  language: {
    search: "Búsqueda:",
    info: "Mostrando página _PAGE_ de _PAGES_",
    loadingRecords: "Cargando...",
    infoFiltered: "(Registros filtrados de _MAX_ )",
    paginate: {
      first: "Primero",
      previous: "Anterior",
      next: "Siguiente",
      last: "Ultimo"
    },
    emptyTable: "No hay Registros",
    order: [[0, "desc"]],
  },
  initComplete: function () {
    var $buttons = $('.dt-buttons').hide();
    $('#btnExportar').on('click', function () {
      var btnClass = ".buttons-excel";
      if (btnClass) $buttons.find(btnClass).click();
    })
    $('#tb_materiales_filter').addClass('sss');
    $('#tb_materiales_filter input[type="search"]').addClass('input-search');
  }
})


//TABLA IMAGENES
var tablaImagenes = $('#tb_imagenes').DataTable({
  dom: 'QfBrtip',
  ajax: {
    "url": "./getters/get_imagenes.php",
    "type": "POST",
    "data": function () {
      let filter_data = {
        cuenta: cuenta
      };
      return filter_data;
    },
  },
  buttons: [
    {
      extend: 'excel',
      exportOptions: { columns: [0, 2, 3, 4, 5] }
    }
  ],
  fixedHeader: true,
  aoColumnDefs: [{
    target: 5,
    visible: false,
    searchable: false
  }],
  language: {
    search: "Búsqueda:",
    info: "Mostrando página _PAGE_ de _PAGES_",
    loadingRecords: "Cargando...",
    infoFiltered: "(Registros filtrados de _MAX_ )",
    paginate: {
      first: "Primero",
      previous: "Anterior",
      next: "Siguiente",
      last: "Ultimo"
    },
    emptyTable: "No hay Registros"
  },
  initComplete: function () {
    var $buttons = $('.dt-buttons').hide();
    $('#btnExportar').on('click', function () {
      var btnClass = ".buttons-excel";
      if (btnClass) $buttons.find(btnClass).click();
    })
    $('#tb_imagenes_filter').addClass('sss');
    $('#tb_imagenes_filter input[type="search"]').addClass('input-search');
  }
})

//REPOSITORIO CATEGORIA EVALUACION DEMOSTRACION
$("#categoriasTable").DataTable({
  dom: 'QfBrtip',
  ajax: {
    "url": "../../get_categorias_ev.php",
    "type": "POST",
    "data": function () {
      let filter_data = {
        cuenta: cuenta
      };
      return filter_data;
    },
  },
  buttons: [
    {
      extend: 'excel',
      exportOptions: { columns: [1] }
    }
  ],
  fixedHeader: true,
  aoColumnDefs: [{
    target: 3,
    visible: false,
    searchable: false
  }],
  language: {
    search: "Búsqueda:",
    info: "Mostrando página _PAGE_ de _PAGES_",
    loadingRecords: "Cargando...",
    infoFiltered: "(Registros filtrados de _MAX_ )",
    paginate: {
      first: "Primero",
      previous: "Anterior",
      next: "Siguiente",
      last: "Ultimo"
    },
    emptyTable: "No hay Registros"
  },
  initComplete: function () {
    var $buttons = $('.dt-buttons').hide();
    $('#btnExportar').on('click', function () {
      var btnClass = ".buttons-excel";
      if (btnClass) $buttons.find(btnClass).click();
    })
    $('#categoriasTable_filter').addClass('sss');
    $('#categoriasTable_filter input[type="search"]').addClass('input-search');
  }
})


//REPOSITORIO EVALUACION DEMOSTRACION
$("#evaluacionTable").DataTable({
  dom: 'QfBrtip',
  ajax: {
    "url": "../../get_evaluaciones.php",
    "type": "POST",
    "data": function () {
      let filter_data = {
        cuenta: cuenta
      };
      return filter_data;
    },
  },
  buttons: [
    {
      extend: 'excel',
      exportOptions: { columns: [1,2,3,4] }
    }
  ],
  fixedHeader: true,
  aoColumnDefs: [{
    target: 5,
    visible: false,
    searchable: false
  }],
  language: {
    search: "Búsqueda:",
    info: "Mostrando página _PAGE_ de _PAGES_",
    loadingRecords: "Cargando...",
    infoFiltered: "(Registros filtrados de _MAX_ )",
    paginate: {
      first: "Primero",
      previous: "Anterior",
      next: "Siguiente",
      last: "Ultimo"
    },
    emptyTable: "No hay Registros"
  },
  initComplete: function () {
    var $buttons = $('.dt-buttons').hide();
    $('#btnExportar').on('click', function () {
      var btnClass = ".buttons-excel";
      if (btnClass) $buttons.find(btnClass).click();
    })
    $('#evaluacionTable_filter').addClass('sss');
    $('#evaluacionTable_filter input[type="search"]').addClass('input-search');
  }
})


//REPOSITORIO PDV EVALUACION 
$("#pdvsTable").DataTable({
  dom: 'QfBrtip',
  ajax: {
    "url": "../../get_pdvs_ev.php",
    "type": "POST",
    "data": function () {
      let filter_data = {
        cuenta: cuenta
      };
      return filter_data;
    },
  },
  buttons: [
    {
      extend: 'excel',
      exportOptions: { columns: [1,2] }
    }
  ],
  fixedHeader: true,
  aoColumnDefs: [{
    target: 4,
    visible: false,
    searchable: false
  }],
  language: {
    search: "Búsqueda:",
    info: "Mostrando página _PAGE_ de _PAGES_",
    loadingRecords: "Cargando...",
    infoFiltered: "(Registros filtrados de _MAX_ )",
    paginate: {
      first: "Primero",
      previous: "Anterior",
      next: "Siguiente",
      last: "Ultimo"
    },
    emptyTable: "No hay Registros"
  },
  initComplete: function () {
    var $buttons = $('.dt-buttons').hide();
    $('#btnExportar').on('click', function () {
      var btnClass = ".buttons-excel";
      if (btnClass) $buttons.find(btnClass).click();
    })
    $('#pdvsTable_filter').addClass('sss');
    $('#pdvsTable_filter input[type="search"]').addClass('input-search');
  }
})


let generateQuoteBtn = document.querySelector('#generate-quote');
let quoteText = document.querySelector('#quote-text');
let quoteAuthor = document.querySelector('#quote-author');

let handleCopyClick = document.querySelector('#copy-quote');


/* handleCopyClick.addEventListener('click',function(){
  let text = 'ss';
  let author = 'ss';
  //navigator.clipboard.writeText(`${text} ${author}`);
  console.log('copaido');
  alert('Quote by ${author} copied to clipboard!');
}) */

$(document).on("click", "#btn_copiar", function () {

  let g = $(this).closest('tr');
  let ids = tablaImagenes.row(g).data();

  let link = ids[5];
  navigator.clipboard.writeText(`${link}`);

  Swal.fire({
    title: 'Enlace Copiado',
    timer: 450,
    showCancelButton: false,
    showConfirmButton: false,
  })

});



//TABLA CARGAR PREGUNTAS
$('#tb_repositorioPreguntas').DataTable({
  sScrollX: '100%',
  dom: 'QfBrtip',
  ajax: {
    "url": "../../get_preguntas.php",
    "type": "POST",
    "data": function () {
      let filter_data = {
        cuenta: cuenta
      };
      return filter_data;
    },
  },
  // buttons: ['excel'],
  buttons: [
    {
      extend: 'excelHtml5',
      text: 'Excel',
      exportOptions: {
        columns: [1, 2, 3, 4, 5, 6, 7, 8, 9],
        format: {
          body: function (data, row, column, node) {


            if (data.length > 5) {
              var inicio_cadena = data.substring(1, 4);
             
              if (inicio_cadena == "img") {
                var inicio = data.search('https');
                var fin = data.search('">');
                data = data.substring(inicio, fin);
              }
            }


            // var inicio_cadena = data.substring(1, 4);
            // if (inicio_cadena == "img") {
            //   var inicio = data.search('https');
            //   var fin = data.search('">');
            //   console.log(inicio, fin);
            //   data = data.substring(inicio, fin)
            // }
            //Obtener solo el link de la imagen
            // Strip $ from salary column to make it numeric
            return data;
          }
        }
      }
    }
  ],
  language: {
    search: "Búsqueda:",
    info: "Mostrando página _PAGE_ de _PAGES_",
    loadingRecords: "Cargando...",
    infoFiltered: "(Registros filtrados de _MAX_ )",
    paginate: {
      first: "Primero",
      previous: "Anterior",
      next: "Siguiente",
      last: "Ultimo"
    },
    emptyTable: "No hay Registros"
  },
  initComplete: function () {
    var $buttons = $('.dt-buttons').hide();
    $('#btnExportar').on('click', function () {
      var btnClass = ".buttons-excel";
      if (btnClass) $buttons.find(btnClass).click();
    })
    $('#tb_repositorioPreguntas_filter').addClass('sss');
    $('#tb_repositorioPreguntas_filter input[type="search"]').addClass('input-search');
  },
  // aoColumnDefs:[
  //   {"sWidth": "100px", "aTargets": [2]},
  //   {"sWidth": "100px", "aTargets": [3]},
  //   {"sWidth": "100px", "aTargets": [4]},
  //   {"sWidth": "100px", "aTargets": [5]},
  // ]
})

//TABLA DETALLE RESULTADOS
tabla_detalle_resultados = $('#tb_insertPreguntas').DataTable({
  sScrollX: '100%',
  dom: 'QfBrtip',
  ajax: {
    "url": "../../get_detalle_resultados.php",
    "type": "POST",
    "data": function () {
      let alumno = selectAlumno.value;
      let test = selectTest.value;
      let fecha = inputFecha.value;

      let filter_data = {
        alumno: alumno,
        test: test,
        fecha: fecha,
        cuenta: cuenta
      };
      return filter_data;
    },
  },
  buttons: ['excel'],
  language: {
    search: "Búsqueda:",
    info: "Mostrando página _PAGE_ de _PAGES_",
    loadingRecords: "Cargando...",
    infoFiltered: "(Registros filtrados de _MAX_ )",
    paginate: {
      first: "Primero",
      previous: "Anterior",
      next: "Siguiente",
      last: "Ultimo"
    },
    emptyTable: "No hay Registros"
  },
  initComplete: function () {
    var $buttons = $('.dt-buttons').hide();
    $('#btnExportar').on('click', function () {
      var btnClass = ".buttons-excel";
      if (btnClass) $buttons.find(btnClass).click();
    })
    $('#tb_insertPreguntas_filter').addClass('sss');
    $('#tb_insertPreguntas_filter input[type="search"]').addClass('input-search');
  }
})




$(document).on("click", ".btn-mostrar-detalle", function () {
  fila = $(this).closest("tr");
  id = fila.find('td:eq(0)').text();

  fetch("../../get_detalle_test.php?id=" + id+'&cuenta=' + cuenta)
    .then((res) => {
      if (!res.ok) {
        throw new Error("Error en la respuesta");
      }
      return res.json();
    })
    .then((datos) => {

      modalTest.classList.add('modal--show');
      if (datos != 'NO DATA') {
        for (let i = 0; i < datos.length; i++) {
          var fila = tableDetalle.insertRow();
          let celda_id = fila.insertCell();
          let celda_pregunta = fila.insertCell();
          let celda_respuesta = fila.insertCell();
          let celda_opta = fila.insertCell();
          let celda_optb = fila.insertCell();
          let celda_optc = fila.insertCell();
          let celda_tiempo = fila.insertCell();
        //  let celda_test = fila.insertCell();

          let texto_id = document.createTextNode(datos[i].question_id);
          let texto_pregunta = document.createTextNode(datos[i].question);
          let texto_respuesta = document.createTextNode(datos[i].answer);
          let texto_opta = document.createTextNode(datos[i].opta);
          let texto_optb = document.createTextNode(datos[i].optb);
          let texto_optc = document.createTextNode(datos[i].optc);
          let texto_tiempo = document.createTextNode(datos[i].tiempo);
        //  let texto_test = document.createTextNode(datos[i].test_id);

          celda_id.appendChild(texto_id);
          celda_pregunta.appendChild(texto_pregunta);
          celda_respuesta.appendChild(texto_respuesta);
          celda_opta.appendChild(texto_opta);
          celda_optb.appendChild(texto_optb);
          celda_optc.appendChild(texto_optc);
          celda_tiempo.appendChild(texto_tiempo);
        //  celda_test.appendChild(texto_test);

        }
      } else {
        divMensaje.innerHTML = 'No se encontraron registros'
      }

    });
})



function doSearch(tableId) {
  const tableReg = document.getElementById(tableId);
  const searchText = document.getElementById('search').value.toLowerCase();
  let total = 0;

  // Recorremos todas las filas con contenido de la tabla
  for (let i = 1; i < tableReg.rows.length; i++) {
    // Si el td tiene la clase "noSearch" no se busca en su cntenido
    if (tableReg.rows[i].classList.contains("noSearch")) {
      continue;
    }

    let found = false;
    const cellsOfRow = tableReg.rows[i].getElementsByTagName('td');
    // Recorremos todas las celdas
    for (let j = 0; j < cellsOfRow.length && !found; j++) {
      const compareWith = cellsOfRow[j].innerHTML.toLowerCase();
      // Buscamos el texto en el contenido de la celda
      if (searchText.length == 0 || compareWith.indexOf(searchText) > -1) {
        found = true;
        total++;
      }
    }
    if (found) {
      tableReg.rows[i].style.display = '';
    } else {
      // si no ha encontrado ninguna coincidencia, esconde la
      // fila de la tabla
      tableReg.rows[i].style.display = 'none';
    }
  }

  // mostramos las coincidencias
  const lastTR = tableReg.rows[tableReg.rows.length - 1];
  const td = lastTR.querySelector("td");
  lastTR.classList.remove("hide", "red");
  if (searchText == "") {
    lastTR.classList.add("hide");
  } else if (total) {
    // td.innerHTML = "Se ha encontrado " + total + " coincidencia" + ((total > 1) ? "s" : "");
  } else {
    lastTR.classList.add("red");
    // td.innerHTML = "No se han encontrado coincidencias";
  }
}

/*$(document).ready(function () {
  $("#frmCSVImport").on("submit", function () {

    $("#response").attr("class", "");
    $("#response").html("");
    var fileType = ".csv";
    var regex = new RegExp("([a-zA-ZÁ-ÿ0-9\s_\\.()\-:])+(" + fileType + ")$");
    if (!regex.test($("#file").val().toLowerCase())) {
      $("#response").addClass("error");
      $("#response").addClass("display-block");
      $("#response").html("Invalid File. Upload : <b>" + fileType + "</b> Files.");
      console.log('d: ' + $("#file").val());
      return false;
    }
    return true;
  });
});*/


/*
let btn_filtrar_fecha = document.getElementById("itemBotonFiltrarFecha");
let tabla_resultados_generales = document.getElementById("tb_resultados");

btn_filtrar.addEventListener('click', function(){
    tabla_resultados_generales.ajax.reload();
});*/