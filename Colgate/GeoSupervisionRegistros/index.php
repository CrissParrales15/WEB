<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Geo Supervision WEB</title>

    <!--Bootstrap CSS-->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0-beta1/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-0evHe/X+R7YkIZDRvuzKMRqM+OrBnVFBL6DOitfPri4tjfHxaWutUpFmBp4vmVor" crossorigin="anonymous">

    <!-- Leaflet CSS-->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.8.0/dist/leaflet.css"
        integrity="sha512-hoalWLoI8r4UszCkZ5kL8vayOGVae1oxXe/2A4AO6J9+580uKHDO3JdHb7NzwwzK5xr/Fs0W40kiNHxM9vyTtQ=="
        crossorigin="" />

    <!--MiniMap Leaflet JS-->
    <link rel="stylesheet" href="public/js/MiniMap/Control.MiniMap.min.css">

    <!--Font Awesome-->
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.8.1/css/all.css">


    <!--Awesome Markers Leaflet JS -->
    <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/Leaflet.awesome-markers/2.0.2/leaflet.awesome-markers.css">

    <!-- Estilos CSS-->
    <link rel="stylesheet" href="public/css/main.css">



</head>

<body>
    <main>
        <h1 class="title" style='background-color:#397fa7'>Geo Supervision WEB - Registros Entradas y Salidas</h1>
        <div id="map"></div>

        <form id="formulario">

            <div class="form-group">
                <div class="justify-content-center m-2">
                    <label class="form-label col-6 p-0" for="">Supervisor(a) : </label>
                    <select class="form-select form-select-sm col-6" name="usuario" id="usuario" required>
                        <option selected disabled value="">Seleccione Supervisor(a)</option>
                    </select>
                </div>
                <div class="justify-content-center m-2">
                    <label class="form-label align-self-start col-6 p-0" for="">Mercaderista : </label>
                    <select class="form-select form-select-sm col-6" name='mercaderista' id="mercaderista">
                        <option selected value="TODOS">Todos Los Mercaderista</option>
                    </select>
                </div>
                <div class="justify-content-center m-2">
                    <label class="form-label col-4 p-0" for="">Fecha : </label>
                    <input type="date" name="fecha" id="fecha" class="form-control form-control-sm" required>
                </div>

                <div class="row m-3">
                    <button type="submit" id="buscar" style='background-color:#397fa7'
                        class="btn text-light buscar">Buscar </button>
                </div>

        </form>

        </div>

        </div>

    </main>




    <footer>

        <!-- leaflet JS -->
        <script src="https://unpkg.com/leaflet@1.8.0/dist/leaflet.js"
            integrity="sha512-BB3hKbKWOc9Ez/TAwyWxNXeoV9c1v6FIeYiBieIWkpLjauysF18NzgR1MBNBXf8/KABdlkX68nAhlwcDFLGPCQ=="
            crossorigin=""></script>

        <!-- MiniMap Leaflet JS -->
        <script src="public/js/MiniMap/Control.MiniMap.min.js"></script>

        <!--Awesome Markers Leaflet JS -->
        <script
            src="https://cdnjs.cloudflare.com/ajax/libs/Leaflet.awesome-markers/2.0.2/leaflet.awesome-markers.min.js"
            integrity="sha512-8BqQ2RH4L4sQhV41ZB24fUc1nGcjmrTA6DILV/aTPYuUzo+wBdYdp0fvQ76Sxgf36p787CXF7TktWlcxu/zyOg=="
            crossorigin="anonymous" referrerpolicy="no-referrer"></script>

        <!-- LeafletJS Awesome Markers -->
        <script
            src="https://cdnjs.cloudflare.com/ajax/libs/Leaflet.awesome-markers/2.0.2/leaflet.awesome-markers.js"></script>

        <!-- Boostrap JS -->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0-beta1/dist/js/bootstrap.bundle.min.js"
            integrity="sha384-pprn3073KE6tl6bjs2QrFaJGz5/SUsLqktiwsUTF55Jfv3qYSDhgCecCxMW52nD2"
            crossorigin="anonymous"></script>

        <!-- SweetAlert -->
        <script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script>

        <!--Main JS-->
        <script type="module" src="public/main.js"></script>
        <!--Mapa JS-->
        <script src="public/mapa.js"></script>

    </footer>


</body>

</html>