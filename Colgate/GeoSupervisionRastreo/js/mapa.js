const CARTO_API_KEY = "cb1_2qfb_1_86eaf644dec98677db686f90"; // solicitada en https://carto.com/basemaps/apikey/

var map = L.map('map',{zoomControl: false}).setView([-1.3045874434767268, -79.2372944728496], 8);
L.tileLayer(
    `https://{s}.basemaps.cartocdn.com/rastertiles/voyager_labels_under/{z}/{x}/{y}{r}.png?key=${CARTO_API_KEY}`,
    { attribution: "", subdomains: "abcd", maxZoom: 25 }
  ).addTo(map);

var pdvIcon = L.icon({
    iconUrl: 'icons/gps.png',
    iconSize: [32, 40], // size of the icon
    iconAnchor: [14, 40], // point of the icon which will correspond to marker's location
    popupAnchor: [0, -36]  // point from which the popup should open relative to the iconAnchor
});

var userIcon = L.icon({
    iconUrl: 'icons/rastreo.png',  // <--------- CAMBIO DE NOMBRE
    iconSize: [25, 35],            // <--------- CAMBIO DE VALORES
    iconAnchor: [12, 35],          // <--------- CAMBIO DE VALORES
    popupAnchor: [0, -32],
});

var RtIcon = L.icon({
  iconUrl: 'icons/pin.png',
  iconSize: [35, 35], 
  iconAnchor: [17, 35], 
  popupAnchor: [0, -32] 
});

L.control.zoom({
 position : 'topright'
}).addTo(map)

//MiniMapa lealflet JS
let url = new L.TileLayer(
    `https://{s}.basemaps.cartocdn.com/rastertiles/voyager_labels_under/{z}/{x}/{y}.png?key=${CARTO_API_KEY}`,
    {
      minZoom: 0,
      maxZoom: 10,
      attribution: "Map data &copy; CARTO contributors",
    }
  );
let miniMap = new L.Control.MiniMap(url, { toggleDisplay: true }).addTo(map);
