let map = L.map("map", { zoomControl: false }).setView([-1.7061808, -80.8145347], 8);

L.tileLayer(
  "https://{s}.basemaps.cartocdn.com/rastertiles/voyager_labels_under/{z}/{x}/{y}{r}.png?key=cb1_2qfb_1_86eaf644dec98677db686f90",
  { attribution: "", subdomains: "abcd", maxZoom: 23 }
).addTo(map);

// MiniMapa lealflet JS
let url = new L.TileLayer(
  "https://{s}.basemaps.cartocdn.com/rastertiles/voyager_labels_under/{z}/{x}/{y}.png?key=cb1_2qfb_1_86eaf644dec98677db686f90",
  {
    minZoom: 0,
    maxZoom: 10,
    subdomains: "abcd",
    attribution: "Map data &copy; CARTO contributors",
  }
);

let miniMap = new L.Control.MiniMap(url, { toggleDisplay: true }).addTo(map);

L.control.zoom({
  position: 'topright'
}).addTo(map);


/* Creamos los Arreglos que tendra los Marcadores de PuntosDeVenta , Registro de Entrada y Salida 
    Radio del Punto de Venta en mts y el trazado de lineas */

const marcadores = [];
const puntoDeVenta = [];
const radius = [];
const trazadoLineas = [];
const centrado = [];

let formulario = document.getElementById("formulario");
formulario.addEventListener("submit", (e) => {

  e.preventDefault();

  for (let i = 0; i <= puntoDeVenta.length - 1; i++) map.removeLayer(puntoDeVenta[i]);

  for (let i = 0; i <= radius.length - 1; i++)   map.removeLayer(radius[i]);

  for (let i = 0; i <= marcadores.length - 1; i++) map.removeLayer(marcadores[i]);

  for (let i = 0; i <= trazadoLineas.length - 1; i++) map.removeLayer(trazadoLineas[i]);

  let datos_formulario = new FormData(formulario);
fetch("./model/Consultas.php?op=obtenerRegistros", {
    method: "POST",
    body: datos_formulario,
})
.then((res) => {
    if (!res.ok) {
        // Si la respuesta no es OK, intentamos leer el texto del error
        return res.text().then(text => {
            console.error("Error response:", text);
            throw new Error(`Error ${res.status}: ${res.statusText}`);
        });
    }
    return res.json();
})
.then((datos) => {
    console.log("Datos recibidos:", datos);
    
    // Verificar si hay error en la respuesta
    if (datos && datos.error) {
        console.error("Error del servidor:", datos.error);
        if (datos.sql) {
            console.error("SQL:", datos.sql);
        }
        Swal.fire(
            "Error!",
            "Error en la consulta: " + datos.error,
            "error"
        );
        return;
    }
      if (datos && datos.length > 0) {
        console.log("SI HAY DATOS, cantidad:", datos.length);

        let marcadorPuntoDeVenta = L.icon({
          iconUrl: 'public/icons/gps.png',
          iconSize: [32, 40], // size of the icon
          iconAnchor: [14, 40], // point of the icon which will correspond to marker's location
          popupAnchor: [0, -36]  // point from which the popup should open relative to the iconAnchor
        });

        for (let i = 0; i <= datos.length - 1; i++) {

          let codigo = datos[i].pos_id;
          let canal = datos[i].channel;
          let cliente = datos[i].customer_owner;
          let nomPuntoDeVenta = datos[i].pos_name;
          let supervisor = datos[i].supervisor;
          let direccion = datos[i].address;
          let ciudad = datos[i].city;
          let localidad = `${datos[i].region} - ${datos[i].province} - ${datos[i].city}`;
          let longitudPV = datos[i].longitud;
          let latitudPV = datos[i].latitud;
          let fotoPV = datos[i].foto;
          let mercaderista = datos[i].mercaderista;
          let fotoMe = datos[i].FotoMe
          let fecha = datos[i].fecha;
          let hora = datos[i].hora;
          let tipo = datos[i].tipo;
          let causal = datos[i].causal;
          let longitudMe = datos[i].longitude;
          let latitudMe = datos[i].latitude;
          let distancia = datos[i].distancia;

          // Agregamos un radio al Punto de Venta
          radius[i] = L.circle([latitudPV, longitudPV], { radius: distancia, color: "#FFE8DF", stroke: true })
            .addTo(map);

          // Agregamos las coordenadas del punto de venta junto al popup con informacion del punto de venta
          puntoDeVenta[i] = L.marker([latitudPV, longitudPV], { icon: marcadorPuntoDeVenta }).addTo(map).bindPopup(`
                            <b> ${nomPuntoDeVenta}  </b> </br>
                            <center> <figure> <img src="${fotoPV}" style="width:300px;margin:5px;border-radius:5px;" height="200"> </figure> </center>
                            <div>
                            <b>Codigo PDV : </b> ${codigo}   </br>
                            <b>Cliente : </b> ${cliente}   </br>
                            <b>Canal :  </b> ${canal}  </br>
                            <b> Supervisor(a) : </b> ${supervisor} </br>
                            <b>Ciudad : </b> ${ciudad}   </br>
                            <b> Direccion : </b> </br> 
                             ${direccion}  </br>
                            <b> Localidad : </b> </br> 
                             ${localidad}  </br>
                            </div>`);

          centrado.push(
            [
              puntoDeVenta[i].getLatLng().lat,
              puntoDeVenta[i].getLatLng().lng
            ])

          if (tipo == "ENTRADA") {

            let marcadorEntrada = L.AwesomeMarkers.icon({
              icon: "crosshairs",
              prefix: "fa",
              markerColor: "green",
              iconColor: "black"
            });

            let distance = map.distance(
              [latitudPV, longitudPV],
              [latitudMe, longitudMe]
            );

            let latlngs = [
              [latitudPV, longitudPV],
              [latitudMe, longitudMe]
            ];

            trazadoLineas[i] = L.polyline(latlngs, { color: "black", dashArray: '5,5' })
              .addTo(map).bindPopup(`Distancia : ${Math.floor(distance)} mts.`);

            marcadores[i] = L.marker([latitudMe, longitudMe], {
              icon: marcadorEntrada
            }).addTo(map).bindPopup(`<div>
            <center> <figure> <img src="https://luckyecuadorweb.blob.core.windows.net/app/CtaColgate/App5pgo/Inserts/${fotoMe}" style="width:300px;margin:5px;border-radius:5px;" height="200"> </figure> </center>
                                                <b> Mercaderista : </b> ${mercaderista}  </br> 
                                                <b> Codigo PDV : </b> ${codigo} </br>
                                                <b> Fecha :  </b> ${fecha} <br>
                                                <b> Hora :  </b> ${hora}  <br>
                                                <b> Tipo : </b> ${tipo}  <br>
                                                <b> Causal : </b> ${causal}  <br>
                                                <b> Distancia : </b> ${Math.floor(
              distance
            )} mts. 
                                                </div>`);
          } else {

            let marcadorSalida = L.AwesomeMarkers.icon({
              icon: "crosshairs",
              prefix: "fa",
              markerColor: "red",
              shape: 'penta',
              iconColor: "black",
            });

            let distance = map.distance(
              [latitudPV, longitudPV],
              [latitudMe, longitudMe]
            );

            let latlngs = [
              [latitudPV, longitudPV],
              [latitudMe, longitudMe]
            ];

            trazadoLineas[i] = L.polyline(latlngs, { color: "black", dashArray: '5,5' })
              .addTo(map).bindPopup(`Distancia : ${Math.floor(distance)} mts.`);

            marcadores[i] = L.marker([latitudMe, longitudMe], {
              icon: marcadorSalida,
            }).addTo(map).bindPopup(`<div>
                                <center> <figure> <img src="https://luckyecuadorweb.blob.core.windows.net/app/CtaColgate/App5pgo/Inserts/${fotoMe}" style="width:300px;margin:5px;border-radius:5px;" height="200"> </figure> </center>
                                <b> Mercaderista : </b> ${mercaderista}  </br> 
                                <b> Codigo PDV : </b> ${codigo} </br>
                                <b> Fecha :  </b> ${fecha} <br>
                                <b> Hora :  </b> ${hora}  <br>
                                <b> Tipo : </b> ${tipo}  <br>
                                <b> Causal : </b> ${causal}  <br>
                                <b> Distancia : </b> ${Math.floor(
              distance
            )} mts. 
                                </div>`);
          }
        }

        /*
        grupoMarcadores = L.featureGroup(puntoDeVenta).addTo(map);
        map.fitBounds(grupoMarcadores.getBounds()); */

        map.fitBounds([...centrado]);
      } else {
        console.log("NO HAY DATOS");
        Swal.fire(
            "Info",
            "No se encontraron registros con esa información",
            "info"
        );
    }
})
.catch((err) => {
    console.error("Error:", err);
    Swal.fire(
        "Error!",
        err.message || "No se encontraron registros con esa informacion!",
        "error"
    );
      
    });
});