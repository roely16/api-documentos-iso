
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-1BmE4kWBq78iYhFldvKuhfTAU6auU8tT94WrHftjDbrCEXSU1oBoqyl2QvZ6jIW3" crossorigin="anonymous">
	<title>Verificar QR</title>
</head>
<body>
	
	<div class="container">
		<div class="row mt-4">
			<div class="col">
				<h1 class="text-center">
					Verificación de QR
				</h1>
			</div>
		</div>
		<div class="mt-4 row justify-content-center">

			<div class="col-12 col-md-6">
				<div class="card">
					<div class="card-body">

					<div class="mb-4 row justify-content-center">
						<div class="col-12 col-md-8">
							<img src="../../assets/img/DCAI_ISO.png" class="img-fluid" alt="...">
						</div>
					</div>

					<table class="table table-striped">
						<tbody>
							<tr>
								<th>
									Documento: 
								</th>
								<td>
									{{ $documento->nombre }}

								</td>
							</tr>
							<tr>
								<th>
									Versión: 
								</th>
								<td>
									{{ $documento->version }}
								</td>
							</tr>
							<tr>
								<th>
									Firma: 
								</th>
								<td>
									{{ $empleado->nombre }} {{ $empleado->apellido }}
								</td>
							</tr>
							<tr>
								<th>
									Cargo: 
								</th>
								<td>
									{{ $perfil->nombre }}
								</td>
							</tr>
							<tr>
								<th>
									Rol: 
								</th>
								<td>
									{{ ucfirst($qr->etiqueta) }}
								</td>
							</tr>
							<tr>
								<th>
									Fecha y Hora: 
								</th>
								<td>
									{{ $qr->created_at->format('d/m/Y h:m:i') }}
								</td>
							</tr>
						</tbody>
					</table>

					</div>
				</div>
			</div>

		</div>
	</div>

</body>
</html>