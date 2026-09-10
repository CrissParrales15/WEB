$(document).ready(function () {
	$("#get_mail").click(function() {
		$.ajax({
			url: "get_table_mail.php",
			method: "POST",
			data: null,
			beforeSend: function(){
				$("#loading").css("display","block");
			},
			success: function (data) {
				$("#loading").css("display","none");
				$("#data-result-mail").html(data);
				$('#mail-table').DataTable( {
					"scrollX": true
				});
			},
			error: function(XMLHttpRequest, textStatus, errorThrown) {
				alert("Status: " + textStatus); alert("Error: " + errorThrown);
			}
		});
	});

	var buttonClicked = "";
	$("#frmCSVImport button[type = 'submit']").click(function (e) {
		buttonClicked = $(this).attr("id");
	});

	$('#frmCSVImport').on("submit", function (e) {
		e.preventDefault(); //form will not submitted
		// alert(buttonClicked);
		if (buttonClicked=="import_mail"){// REALIZAR IMPORTACION CSV - CORREOS
			buttonClicked = "";
			$.ajax({
				url: "cargar_correo.php",
				method: "POST",
				data: new FormData(this),
				contentType: false,          // The content type used when sending data to the server.  
				cache: false,                // To unable request pages to be cached  
				processData: false,          // To send DOMDocument or non processed data file it is set to false  
				beforeSend: function(){
					$("#loading").css("display","block");
				}, 
				success: function (data) {
					$("#loading").css("display","none");
					var id = parseInt(data);
					switch(id) {
						case 1:
							Swal.fire({
								title: "Correos",
								text: "La tabla fue actualizada",
								type: "success"
							});
							$('#frmCSVImport')[0].reset();
							break;
						case 2:
							Swal.fire({
								title: "Correos",
								text: "Error de actualización!",
								type: "error"
							});
							break;
					}
				}, 
				error: function(XMLHttpRequest, textStatus, errorThrown) { 
					alert("Status: " + textStatus); alert("Error: " + errorThrown); 
				}       
			});
		} else if (buttonClicked=="send_mail") {// ENVIAR CORREO
			buttonClicked = "";
			$.ajax({
				url: "send_mail.php",
				method: "POST",
				data: new FormData(this),
				contentType: false,          // The content type used when sending data to the server.  
				cache: false,                // To unable request pages to be cached  
				processData: false,          // To send DOMDocument or non processed data file it is set to false  
				beforeSend: function(){
					$("#loading").css("display","block");
				}, 
				success: function (data) {
					$("#loading").css("display","none");
					console.log("DATA: " + data);
					var id = parseInt(data);
					switch(id) {
						case 1:
							Swal.fire({
								title: "Correo",
								text: "Error de envío",
								type: "error"
							});
							$('#frmCSVImport')[0].reset();
							break;
						case 2:
							Swal.fire({
								title: "Correo",
								text: "Enviado correctamente",
								type: "success"
							});
							break;
						case 3:
								Swal.fire({
									title: "Correo",
									text: "Error de envío",
									type: "error"
								});
								break;
					}
				}, 
				error: function(XMLHttpRequest, textStatus, errorThrown) { 
					alert("Status: " + textStatus); alert("Error: " + errorThrown); 
				}       
			});
		}
	});
}); 