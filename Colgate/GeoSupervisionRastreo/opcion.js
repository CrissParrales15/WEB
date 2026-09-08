const db = getDatabase();

onValue(ref(db, 'locations/'), (snapshot) => {

    if (mercaderista == 'all') {

        let datos = snapshot.val();
        let counter = 0;
        
        //eliminar marcadores
        if (marcadorRT != null) {
            for (let e = 0; e < marcadorRT.length; e++) {
                map.removeLayer(marcadorRT[e]);
                console.log(marcadorRT[e])
            }
            marcadorRT = [];
        }

        //mostrar marcadores
        for (let i = 0; i < mercPorSupervisor.length; i++) {
            let MercName = mercPorSupervisor[i];
    
            if (datos[MercName] != null) {
    
              let lng = datos[MercName].longitude;
              let lat = datos[MercName].latitude;
              let nombre = datos[MercName].user;
              let hora = new Date(datos[MercName].time);
              let time = hora.toLocaleTimeString();
              let fecha = hora.toLocaleDateString();
    
              marcadorRT[counter] = L.marker([lat, lng], { icon: userIcon }).addTo(map).bindPopup(
                `<p> 
                <b>Gestor:</b> ${nombre} 
                <br><b>Fecha:</b> ${fecha} 
                <br><b>Hora:</b> ${time} 
                </p>`
              );
              counter++;
            } //end if
          } //end for


    } else {

      onValue(ref(db, 'locations/' + mercaderista), (snapshot) => {
        console.log(marcadorRT)
        console.log(mercaderista)
        if (marcadorRT[0] != null) {
          map.removeLayer(marcadorRT[0]);
          marcadorRT = [];
        }
  
        if (snapshot.exists()) {
  
          let datos = snapshot.val();
          let lng = datos.longitude;
          let lat = datos.latitude;
          let nombre = datos.user;
          let hora = new Date(datos.time);
          let time = hora.toLocaleTimeString();
          let fecha = hora.toLocaleDateString();
          marcadorRT[0] = L.marker([lat, lng], { icon: userIcon }).addTo(map).bindPopup(
            `<p> 
                <b>Gestor:</b> ${nombre} 
                <br><b>Fecha:</b> ${fecha}
                <br><b>Hora:</b> ${time} 
                </p>`
          );
  
        } else {
          console.log("No se tiene rastreo en tiempo real");
        }
        console.log(marcadorRT[0])
      })

    }

})
