export function obtenerSupervisor() {
  fetch("././model/Consultas.php?op=obtenerSupervisor")
    .then((res) =>
      !res.ok ? `Hubo un error en la respuesta : ${res.status}` : res.json()
    )
    .then((datos) => {
      let selectSupervisor = `<option value="" selected disabled> Seleccione Supervisor(a) </option>`;
      if (datos && datos.length > 0) {
        for (let i = 0; i < datos.length; i++) {
          selectSupervisor += `<option value="${datos[i].id}">${datos[i].usuario}</option>`;
        }
      }
      document.getElementById("usuario").innerHTML = selectSupervisor;
    })
    .catch((error) => {
      console.error("Ocurrió un error " + error);
    });

  return obtenerMercaderista();
}

function obtenerMercaderista() {
  let NomSupervisor = document.getElementById("usuario");
  NomSupervisor.addEventListener("change", (e) => {
    fetch(
      "././model/Consultas.php?op=mercaderistaPorSup&value=" + e.target.value
    )
      .then((res) =>
        !res.ok ? `Hubo un error en la respuesta : ${res.status}` : res.json()
      )
      .then((datos) => {
        let selectMercaderista = `<option value="TODOS" selected> Todos Los Mercaderista </option>`;
        if (datos && datos.length > 0) {
          for (let i = 0; i < datos.length; i++) {
            // value = id (no el nombre): hay mercaderistas con nombre+apellido duplicado
            // (ej. "JENNIFFER ALBAN" existe en 2 cuentas distintas) y el nombre por sí
            // solo no alcanza para distinguirlas en el combo ni en la consulta.
            selectMercaderista += `<option value="${datos[i].id}">${datos[i].mercaderista}</option>`;
          }
        }
        document.getElementById("mercaderista").innerHTML = selectMercaderista;
      })
      .catch((error) => {
        console.error("Ocurrió un error " + error);
        // Mantener la opción "TODOS" si hay error
        document.getElementById("mercaderista").innerHTML = `<option value="TODOS" selected> Todos Los Mercaderista </option>`;
      });
  });
}