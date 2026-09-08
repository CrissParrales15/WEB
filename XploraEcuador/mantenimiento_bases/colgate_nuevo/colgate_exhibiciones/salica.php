<!DOCTYPE html>
<html>

<head>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-1BmE4kWBq78iYhFldvKuhfTAU6auU8tT94WrHftjDbrCEXSU1oBoqyl2QvZ6jIW3" crossorigin="anonymous">
    <script src="https://code.jquery.com/jquery-3.2.1.slim.min.js" integrity="sha384-KJ3o2DKtIkvYIK3UENzmM7KCkRr/rE9/Qpg6aAZGJwFDMVNA/GpGFF93hXpG5KkN" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.12.9/dist/umd/popper.min.js" integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/js/bootstrap.min.js" integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl" crossorigin="anonymous"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    
    <!-- DATATABLE -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.1.3/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    <script src="https://code.jquery.com/jquery-3.5.1.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    
    <link rel="stylesheet" href="/App/XploraEcuador/assets/plugins/sweetalert2/sweetalert2.min.css">
    <script src="/App/XploraEcuador/assets/plugins/sweetalert2/sweetalert2.all.min.js"></script>
</head>

<body style="padding-bottom: 25px;">
    <div id="loading" style=" display: none;position: fixed; width: 100%; height: 100%; top: 0px; left: 0px; z-index: 999999; overflow: auto; background-color: rgba(0, 0, 0, 0.498039);">
        <img style="position: absolute; top: 50% !important; left: 50% !important;" src="/App/XploraEcuador/mantenimiento_validacion_bases/assets/dist/img/loader_1.gif">
    </div>

    <div class="container">
        <h2>Envío de correos</h2>

        <form class="form-horizontal" method="post" name="frmCSVImport" id="frmCSVImport" enctype="multipart/form-data">
            <div class="row">
                <div class="card">
                    <h5 class="card-header">Correos</h5>
                    <div class="card-body">
                        <h5 class="card-title">Escoger archivo CSV</h5>
                        <input type="file" name="file_mail" id="file_mail" accept=".csv">
                        <button type="submit" id="import_mail" name="import_mail" value="import_mail" class="btn btn-primary btn-submit form-control-file">Importar Correos</button>
                        <button type="button" id="get_mail" name="get_mail" class="btn btn-primary" data-toggle="modal" data-target="#modalMail">
                            Última información cargada
                        </button>
                    </div>
                </div>
            </div>
            </br>
            <div class="row">
                <button type="submit" id="send_mail" name="send_mail" value="send_mail" class="btn btn-success btn-submit form-control-file">Enviar Correo</button>
            </div>
        </form>

        <!-- Modal Correos -->
        <div class="modal fade bd-example-modal-lg" id="modalMail" tabindex="-1" role="dialog" aria-labelledby="modalMailTitle" aria-hidden="true" data-target=".bd-example-modal-lg">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalMailTitle">Correos</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div id="data-result-mail" name="data-result-mail">
                            <?php include('get_table_mail.php'); ?>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="/App/XploraEcuador/mantenimiento_bases/salica/js/upload.js"></script>
</body>

</html>