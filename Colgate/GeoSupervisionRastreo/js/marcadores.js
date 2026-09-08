import { getDatabase, ref, onValue } from "https://www.gstatic.com/firebasejs/9.8.3/firebase-database.js"
import { app } from "./../firebase/conexion.js"

let marcadoresPdv = [];
let marcadoresR = [];
let radios = [];
let marcadorRT = [];
let arrayColores = [];

let $botonBuscar = document.getElementById("button");
let $botonEnfocar = document.getElementById("btn_enfocar");

const main = function (e) {
  borrarMarcadores();
  e.preventDefault();

  //parametros para filtrar
  let supervisor = document.getElementById("supervisor").value;
  let mercaderista = document.getElementById("mercaderista").value;
  let fecha = document.getElementById("fecha").value;
  let horaI = document.getElementById("horaI").value;
  let horaF = document.getElementById("horaF").value;

  //MARCADORES PUNTO DE VENTA
  fetch('./mysql/consult_all.php?dv=consultarPdv&merc=' + mercaderista + '&sprv=' + supervisor + '&fecha=' + fecha)
    .then((res) => {
      if (!res.ok) {
        throw new Error("Hubo un error en la respuesta");
      }
      return res.json();
    })
    .then((datos) => {
      mostrarPuntosVenta(datos);
    });

  //OBTENER COLORES 
  fetch('./mysql/consult_all.php?dv=obtenerColores')
    .then((res) => {
      if (!res.ok) {
        throw new Error("Hubo un error en la respuesta");
      }
      return res.json();
    })
    .then((datos) => arrayColores = [...datos]);


  //MARCADORES DE RASTREO

  fetch('./mysql/consult_all.php?dv=consultarRastreo&merc=' + mercaderista +
    '&sprv=' + supervisor + '&fecha=' + fecha + '&horaI=' + horaI + '&horaF=' + horaF)
    .then((res) => {
      if (!res.ok) {
        throw new Error("Hubo un error en la respuesta");
      }
      return res.json();
    })
    .then((datos) => {
      mostrarRastreos(datos);

      //MARCADORES EN TIEMPO REAL
      const db = getDatabase();
      onValue(ref(db, 'locations/'), (snapshot) => {

        let datos = snapshot.val();
        let counter = 0;

        //consultar todos los mercaderistas
        if (mercaderista == 'all') {

          borrarMarcadoresExistentes();

          if (snapshot.exists()) {
            counter = mostrarTiempoReal(datos, counter);
          } else {
            console.log("No se tiene rastreo en tiempo real");
          }
        }
        //consultar un solo mercaderista
        else {
          borrarMarcadoresExistentes();

          if (snapshot.exists()) {
            mostrarTiempoRealUno(snapshot, mercaderista);
          } else {
            console.log("No se tiene rastreo en tiempo real");
          }
        }
      }, (error) => {
        // Si Firebase falla (ej. base desactivada), no rompemos el resto del flujo
        console.warn("No se pudo conectar al rastreo en tiempo real (Firebase):", error);
      });
    });

  //  $botonBuscar.disabled = true;
}

const btnEnfocar = function () {
  if (marcadorRT.length === 0) {
    console.warn("No hay marcadores de tiempo real para enfocar (Firebase puede estar desactivado o sin datos).");
    return;
  }
  let marcadoresTiempoReal = L.featureGroup(marcadorRT).addTo(map);
  map.fitBounds(marcadoresTiempoReal.getBounds(), { maxZoom: 15 });
}

const buscarEnfocar = function () {
  let enfocar = document.getElementById('mercaderista').value;
  if (enfocar != 'all') {
    if (marcadorRT.length === 0) {
      console.warn("No hay marcadores de tiempo real para enfocar (Firebase puede estar desactivado o sin datos).");
      return;
    }
    let marcadoresTiempoReal = L.featureGroup(marcadorRT).addTo(map);
    map.fitBounds(marcadoresTiempoReal.getBounds(), { maxZoom: 15 });
  }
}

const borrarMarcadores = function () {
  for (let e = 0; e < marcadoresPdv.length; e++) {
    map.removeLayer(marcadoresPdv[e]);
  }
  for (let i = 0; i < radios.length; i++) {
    map.removeLayer(radios[i]);
  }
  for (let e = 0; e < marcadoresR.length; e++) {
    map.removeLayer(marcadoresR[e]);
  }
  for (let e = 0; e < marcadorRT.length; e++) {
    map.removeLayer(marcadorRT[e]);
  }
  for (let e = arrayColores.length; e < 0; e--) {
    arrayColores.slice(e);
  }

  marcadoresR.length = 0;
  marcadoresPdv.length = 0;
  radios.length = 0;
  marcadorRT.length = 0;
  arrayColores.length = 0;
};

$botonBuscar.addEventListener("click", borrarMarcadores);
$botonBuscar.addEventListener("click", main);
$botonEnfocar.addEventListener("click", btnEnfocar);
$botonBuscar.addEventListener("click", buscarEnfocar);

const mostrarTiempoRealUno = function (snapshot, mercaderista) {
  let datos = snapshot.val();
  if (!datos[mercaderista]) {
    console.log("No hay dato en tiempo real para:", mercaderista);
    return;
  }
  let nombre = datos[mercaderista].user;
  if (mercaderista == nombre) {
    let lng = datos[mercaderista].longitude;
    let lat = datos[mercaderista].latitude;
    let hora = new Date(datos[mercaderista].time);
    let time = hora.toLocaleTimeString();
    let fecha = hora.toLocaleDateString();
    let color = '';

    for (let i = 0; i < arrayColores.length; i++) {
      if (arrayColores[i].user === nombre) {
        color = arrayColores[i].color;
        if (color.slice(0, 1) !== '#') color = '#' + color;
        break;
      }
    }

    const circulo = `            
    width: 15px;
    height: 15px;
    border: none;
    color: white;
    background-color: ${color};
    border-radius: 50% 50% 50% 50%;
    z-index: 10;
    transform: rotate(225deg);
    transform: translate(-8px,6px);
    `;

    const linea = `
    height: 25px;
    width: 0px;
    border: 0.1px solid ${color};
    background-color: ${color};
    `;

    let RtIconM = L.divIcon({
      className: "tiempo-real-icon",
      iconSize: [35, 35],
      iconAnchor: [17, 35],
      popupAnchor: [0, -32],
      html: `<div class="contenedor">
               <div style="${circulo}" class="circulo"></div>
               <div style="${linea}" class="linea"></div>
             </div>`
    });

    marcadorRT[0] = L.marker([lat, lng], { icon: RtIconM }).addTo(map).bindPopup(
      `<p> 
        <b>Gestor:</b> ${nombre} 
        <br><b>Fecha:</b> ${fecha}
        <br><b>Hora:</b> ${time} 
      </p>`
    );

    marcadorRT[0].on('mouseover', function (e) {
      this.openPopup();
    });
    marcadorRT[0].on('mouseout', function (e) {
      this.closePopup();
    });
    marcadorRT[0].on('click', function (e) {
      this.openPopup();
    });
  }
}

const mostrarTiempoReal = function (datos, counter) {
  for (let i = 0; i < mercPorSupervisor.length; i++) {
    let MercName = mercPorSupervisor[i];
    if (datos[MercName] != null) {
      let lng = datos[MercName].longitude;
      let lat = datos[MercName].latitude;
      let nombre = datos[MercName].user;
      let hora = new Date(datos[MercName].time);
      let time = hora.toLocaleTimeString();
      let fecha = hora.toLocaleDateString();
      let color = '';

      for (let i = 0; i < arrayColores.length; i++) {
        if (arrayColores[i].user === MercName) {
          color = arrayColores[i].color;
          if (color.slice(0, 1) !== '#') color = '#' + color;
          break;
        }
      }

      const circulo = `            
        width: 15px;
        height: 15px;
        border: none;
        color: white;
        background-color: ${color};
        border-radius: 50% 50% 50% 50%;
        z-index: 10;
        transform: rotate(225deg);
        transform: translate(-8px,6px);
        `;

      const linea = `
        height: 25px;
        width: 0px;
        border: 0.1px solid ${color};
        background-color: ${color};
        `;

      let RtIconTR = L.divIcon({
        className: "tiempo-real-icon",
        iconSize: [35, 35],
        iconAnchor: [17, 35],
        popupAnchor: [0, -32],
        html: `<div class="contenedor">
                <div style="${circulo}" class="circulo"></div>
                <div style="${linea}" class="linea"></div>
              </div>`
      });

      marcadorRT[counter] = L.marker([lat, lng], { icon: RtIconTR }).addTo(map).bindPopup(
        `<p> 
          <b>Gestor:</b> ${nombre} 
          <br><b>Fecha:</b> ${fecha} 
          <br><b>Hora:</b> ${time} 
        </p>`
      );

      marcadorRT[counter].on('mouseover', function (e) {
        this.openPopup();
      });
      marcadorRT[counter].on('mouseout', function (e) {
        this.closePopup();
      });
      marcadorRT[counter].on('click', function (e) {
        this.openPopup();
      });
      counter++;
    }
  }
  return counter;
}

const borrarMarcadoresExistentes = function () {
  if (marcadorRT != null) {
    for (let e = 0; e < marcadorRT.length; e++) {
      map.removeLayer(marcadorRT[e]);
    }
    marcadorRT = [];
  }
}

const mostrarRastreos = function (datos) {
  console.log("Datos de rastreo recibidos:", datos);

  if (datos == 'cero registros') {
    swal("No existen registros", "Asegúrese de haber llenado todos los parámetros de búsqueda (fecha, hora desde, hora hasta)", "warning");
  }
  else {
    let contador = 0;
    let nombreB = '';

    for (let i = 0; i < datos.length; i++) {
      let lng = datos[i].longitud;
      let lat = datos[i].latitud;
      let nombre = datos[i].mercaderista;
      let fecha = datos[i].fecha;
      let hora = datos[i].hora;
      let color = '7360A6';
      // let color = datos[i].color;

      if (lat == null || lng == null) {
        console.warn("Registro sin coordenadas válidas, se omite:", datos[i]);
        continue;
      }

      if (color.slice(0, 1) !== '#') color = '#' + color;
      if (nombreB != nombre) contador = 0;

      let indice = contador++;

      const circleStyles = `
        width: 20px;
        height: 20px;
        border: none;
        color: white;
        font-family:monospace;
        text-align:center;
        font-size:12px;
        background-color: ${color};
        border-radius: 50% 50% 50% 50%;
        transform: rotate(225deg);
        transform: translate(5px,3px);
        position: relative;
        z-index:20;
            `;

      const marcador_svg = `
            transform: rotate(180deg);
            stroke: ${color};
            position: absolute;`;

      let rastreoIcon = L.divIcon({
        className: "number-icon",
        iconSize: [28, 40],
        iconAnchor: [14, 40],
        popupAnchor: [0, -37],
        html: `<div style="${circleStyles}"  class="tear">${indice}</div> 
          <div style="position:relative; transform:translate(-15px,-23px);" class="contenedor">
                      <svg class="marcador" width="2rem" style="${marcador_svg}" viewbox="0 0 30 42">
                       <path fill="#FFF"  stroke-width="2"
                         d="M15 3
                       Q16.5 6.8 25 18
                       A12.8 12.8 0 1 1 5 18
                       Q13.5 6.8 15 3z" />
                      </svg>
                      
                </div>`,
      });

      marcadoresR[i] = L.marker([lat, lng], { icon: rastreoIcon }).addTo(map).bindPopup(
        `<p> 
              <h3 style="text-align:center;"> ${indice}</h3>
              <b>Gestor:</b> ${nombre} 
              <br><b>Fecha:</b> ${fecha} 
              <br><b>Hora:</b> ${hora} 
              </p>`
      );
      nombreB = nombre;
    }
  }
}

const mostrarPuntosVenta = function (datos) {
  console.log("Datos de PDV recibidos:", datos);

  for (let i = 0; i < datos.length; i++) {
    let lng = datos[i].longitud;
    let lat = datos[i].latitud;
    let nombre = datos[i].pos_name;
    let dir = datos[i].address;
    let supervisor = datos[i].supervisor;
    let foto = datos[i].foto;
    let codigo = datos[i].pos_id;
    let ciudad = datos[i].city;
    let distancia = datos[i].distancia;

    if (lat == null || lng == null) {
      console.warn("PDV sin coordenadas válidas, se omite:", datos[i]);
      continue;
    }

    marcadoresPdv[i] = L.marker([lat, lng], { icon: pdvIcon }).addTo(map).bindPopup(
      `<img src="${foto}" align='center' width=300></img>
            <p>
            <div align='center'><b>${nombre}</b></div>
            <br><b>Código PDV:</b> ${codigo}
            <br><b>Dirección:</b> ${dir} 
            <br><b>Supervisor:</b> ${supervisor}
            <br><b>Ciudad:</b> ${ciudad}
            </p>`
    );

    radios[i] = L.circle([lat, lng], {
      color: '#FFE8DF',
      fillColor: '#FFE8DF',
      fillOpacity: 0.2,
      radius: distancia
    }).addTo(map);
  }
}