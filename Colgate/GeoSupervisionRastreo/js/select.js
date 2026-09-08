
//SELECT SUPERVISOR

fetch("./mysql/consult_all.php?dv=consultarSupervisor")

  .then((res) => {
    if (!res.ok) {
      throw new Error("Hubo un error en la respuesta");
    }
    return res.json();
  })
  .then((datos) => {
    //adheriendo al select del html
    let selectSupervisor = `<option value="" selected disabled> Seleccione Supervisor </option>`;
    if (datos.length > 0) {
      for (let i = 0; i < datos.length; i++) {
        selectSupervisor += `<option value="${datos[i].supervisor}">${datos[i].supervisor}</option>`;
      }
    }
    document.getElementById("supervisor").innerHTML = selectSupervisor;
  })
  .catch((error) => {
    console.error("Ocurrió un error " + error);
  });

//SELECT MERCADERISTA

var mercPorSupervisor = [];

document.getElementById('supervisor').addEventListener('change', () => { mercPorSupervisor = []})
document.getElementById('supervisor').addEventListener('change', selectMercaderista)

function selectMercaderista() {
  //supervisor seleccionado
  var sprv = document.getElementById('supervisor').value;

  fetch('./mysql/consult_all.php?dv=consultarMercaderista&sprv=' + sprv)
    .then((res) => {
      if (!res.ok) {
        throw new Error("Hubo un error en la respuesta");
      }
      return res.json();
    })
    .then((datos) => {
      //adheriendo al select del html
      let selectMercaderista = `<option value="all"> Todos los mercaderistas </option>`;
      if (datos.length > 0) {
        for (let i = 0; i < datos.length; i++) {
          selectMercaderista += `<option value="${datos[i].mercaderista}">${datos[i].mercaderista}</option>`;
          mercPorSupervisor[i] = datos[i].mercaderista
        }
      }
      document.getElementById("mercaderista").innerHTML = selectMercaderista;
    })
    .catch((error) => {
      console.error("Ocurrió un error " + error);
    });
}



