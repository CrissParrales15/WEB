<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mapa</title>
    <!--leaflet CSS-->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.8.0/dist/leaflet.css" integrity="sha512-hoalWLoI8r4UszCkZ5kL8vayOGVae1oxXe/2A4AO6J9+580uKHDO3JdHb7NzwwzK5xr/Fs0W40kiNHxM9vyTtQ==" crossorigin=""/>    
    <!--Boostrap-->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0-beta1/dist/css/bootstrap.min.css">
    <!--Style-->
    <link rel="stylesheet" href="css/mapa.css">
    <!--MiniMap CSS-->
    <link rel="stylesheet" href="MiniMap/Control.MiniMap.min.css">
    <!--SweetAlert-->
    <script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>
    <!--Cluster Marker-->
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.1.0/dist/MarkerCluster.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.1.0/dist/MarkerCluster.Default.css" />
</head>

<body>
    <main>
        <h1 class="title">Geo Supervisón WEB - Rastreos</h1>
        <div id="map"></div>
        <div class="form-group">
            <div class="row justify-content-evenly m-2">
                <label class="form-label col-4 p-0" for="">Supervisor : </label>
                <select class="form-select form-select-sm col-6" id="supervisor"></select>
            </div>
            <div class="row justify-content-evenly m-2">
                <label class="form-label col-4 p-0" for="">Mercaderista : </label>
                <select class="form-select form-select-sm col-6" id="mercaderista">
                    <option value="all" selected disabled>Todos los mercaderistas</option>
                </select>
            </div>

            <div class="row justify-content-evenly m-2">
                <label class="form-label col-4 p-0" for="">Fecha : </label>
                <input type="date" name="fecha" id="fecha" class="form-control form-control-sm" required>
            </div>

            <div class="row justify-content-evenly m-2">
                <label class="form-label col-4 p-0" for="">Hora Desde : </label>
                <input type="time" name="horaI" id="horaI" class="form-control form-control-sm" required>
            </div>

            <div class="row justify-content-evenly m-2 mb-3">
                <label class="form-label col-4 p-0" for="">Hora Hasta : </label>
                <input type="time" name="horaF" id="horaF" class="form-control form-control-sm " required>
            </div>

            <div class="row justify-content-evenly m-2">
                <button type="submit" id="button" class="btn btn-primary btn-sm" style="background-color:#397fa7;">Buscar</button>
            </div>
        </div>

        <div class="btn-group">
            <button type="button" id="btn_enfocar" > <img src="icons/enfocar.png" height ="36" width="36" style=" border-radius: 6px;"></button>
        </div>
    </main>

    <!--leaflet js-->
    <script src="https://unpkg.com/leaflet@1.8.0/dist/leaflet.js" integrity="sha512-BB3hKbKWOc9Ez/TAwyWxNXeoV9c1v6FIeYiBieIWkpLjauysF18NzgR1MBNBXf8/KABdlkX68nAhlwcDFLGPCQ==" crossorigin=""></script>
    <!--Boostrap js-->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0-beta1/dist/js/bootstrap.bundle.min.js" integrity="sha384-pprn3073KE6tl6bjs2QrFaJGz5/SUsLqktiwsUTF55Jfv3qYSDhgCecCxMW52nD2" crossorigin="anonymous"></script>
    <!--MiniMap js-->
    <script type="text/javascript" src="MiniMap/Control.MiniMap.min.js"></script>
    <!--Cluster Marker js-->
    <script src="https://unpkg.com/leaflet.markercluster@1.1.0/dist/leaflet.markercluster.js"></script>

    <script type="text/javascript" src="js/mapa.js"></script>
    <script type="text/javascript" src="js/select.js"></script>
    <script type="module" src="js/marcadores.js"></script>

</body>

</html>