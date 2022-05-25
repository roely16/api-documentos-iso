<?php 

	namespace App\Http\Controllers;

	use Illuminate\Http\Request;

	use App\DocumentoRevision;
	use App\Empleado;
	use App\TipoDocumento;
	use App\EstadoDocumento;
	use App\Perfil;
	use App\EmpleadoPerfil;
	use App\DocumentoQR;
	use App\RolAlterno;
	use App\ResponsableRevision;

	use setasign\Fpdi\Fpdi;
	
	use Illuminate\Support\Facades\DB;

	use Endroid\QrCode\QrCode;

	use Illuminate\Support\Facades\Crypt;
	use Carbon\Carbon;

	use App\Jobs\MailJob;

	class UploadDocumentController extends Controller{
		
		public function get_documents_revision(Request $request){

			try {
				
				$empleado = Empleado::find($request->nit);

				$documentos_revision = DocumentoRevision::where(function($query) use ($empleado){
											$query->where('codarea', $empleado->codarea)
													->orWhere('usuarioid', $empleado->usuario);
										})
										->where('PARENT_DOCUMENTOID', null)
										->where('BAJA', '0')
										->where('DELETED_AT', NULL)
										->orderBy('DOCUMENTOID', 'desc')
										->get();

				foreach ($documentos_revision as &$documento) {
					
					$tipo_documento = TipoDocumento::find($documento->tipodocumentoid);

					$documento->tipo_documento = $tipo_documento ? $tipo_documento->nombre : null;

					// Validar si no tiene versiones 
					$versiones = DocumentoRevision::where('parent_documentoid', $documento->documentoid)->orderBy('documentoid', 'desc')->get();

					if ($versiones->count() > 0) {
						
						$child_document = $versiones[0];

						$estado = EstadoDocumento::find($child_document->estadoid);

					}else{

						$estado = EstadoDocumento::find($documento->estadoid);

					}

					//$documento->nombre = utf8_encode($documento->nombre);

					$documento->estado = $estado;
					$documento->versiones = $versiones;

				}

				$headers = [
					[
						"text" => "ID",
						"value" => "documentoid",
						"width" => '5%',
						"sortable" => false,
					],
					[
						"text" => "Código",
						"value" => "codigo",
						"sortable" => false,
						"width" => "30%"
					],
					[
						"text" => "Nombre",
						"value" => "nombre",
						"sortable" => false,
						"width" => "30%"
					],
					[
						"text" => "Tipo",
						"value" => "tipo_documento",
						"sortable" => false,
						"width" => "15%",
						"custom" => true
					],
					[
						"text" => "Estado",
						"value" => "estado",
						"sortable" => false,
						"width" => "15%",
						"custom" => true
					],
					[
						"text" => "Acción",
						"value" => "action",
						"sortable" => false,
						"width" => "10%",
						"align" => "right",
						"custom" => true
					]
				];

				$response = [
					"items" => $documentos_revision,
					"headers" => $headers
				];

				return response()->json($response, 200);

			} catch (\Throwable $th) {
				
				return response()->json($th->getMessage(), 400);

			}

		}

		public function get_form_create(Request $request){

			$tipos_documento = TipoDocumento::orderBy('tipodocumentoid', 'asc')->get();

			foreach ($tipos_documento as &$tipo) {
				
				$tipo->nombre = $tipo->nombre . ' (' . $tipo->nomenclatura . ')';

			}

			$tipo_almacenamiento = ["Físico", "Digital"];

			$empleado = Empleado::find($request->nit);

			$colaboradores = Empleado
								::where('codarea', $empleado->codarea)
								->where('status', 'A')
								->select(DB::raw("CONCAT(nombre, CONCAT(' ', apellido)) as nombre, nit"))
								->get();

			$response = [
				"tipos_documento" => $tipos_documento,
				"tipos_almacenamiento" => $tipo_almacenamiento,
				"colaboradores" => $colaboradores
			];
			
			return response()->json($response, 200);

		}

		public function upload_document(Request $request){

			$ssl = isset($_SERVER['HTTPS']) ? 'https://' : 'http://';

			$documento = json_decode($request->documento);

			$ajustes = json_decode($request->settings);

			// Si Save es verdadero
			$save = json_decode($request->save);

			$filename = 'preview.pdf';

			// Carpeta donde se almacenará el archivo
			$path = 'temp_files';

			// URL para su lectura 
			$destinationPath = $path;

			$file = $request->file('file');

			move_uploaded_file($request->file('file_preview'), $path . '/' . $filename);

			// Información del Empleado que Elabora

			$empleado = Empleado::find($documento->elabora);

			/*
				* Buscar el rol alternativo
			*/
			$rol_alterno = RolAlterno::where('usuario', $empleado->usuario)->first();

			$empleado_perfil = EmpleadoPerfil
								::select('rrhh_perfil.id', 'rrhh_perfil.nombre')
								->join('rrhh_perfil', 'rh_empleado_perfil.id_perfil', '=', 'rrhh_perfil.id')
								->where('nit', $empleado->nit)
								->first();

			if (!$save) {
				
				$job_data = (object) [
					"documento" => $documento,
					"ajustes" => $ajustes,
					"file_path" => $path . '/' . $filename,
					"output_path" => $path . '/' . $filename,
					"qr" => [
						[
							"tag" => "elabora",
							"label" => "Elabora",
							"qr" => true,
							"url" => "https://udicat.muniguate.com/?id=" . uniqid(),
							"responsable" => $empleado->nombre. ' ' . $empleado->apellido,
							"rol" => $rol_alterno  ? $rol_alterno->rol : ($empleado_perfil ? $empleado_perfil->nombre : null),
							"qr_path" => null,
							"nit" => $empleado->nit,
							"usuario" => $empleado->usuario,
							"perfil" => $empleado_perfil ? $empleado_perfil->id : null
						],
						[
							"tag" => "revisa",
							"label" => "Revisa",
							"qr" => false,
							"url" => null,
							"responsable" => null,
							"rol" => null,
							"qr_path" => null
						],
						[
							"tag" => "aprueba",
							"label" => "Aprueba",
							"qr" => false,
							"url" => null,
							"responsable" => null,
							"rol" => null,
							"qr_path" => null
						]
					]
				];

				$result = $this->create_pdf($job_data);

			}

			if ($save) {

				// Subir documento en PDF
				$pdf_name = $request->file('file')->getClientOriginalName();
				$pdf_path = 'documentos/' . uniqid() . '_' . $pdf_name;
				
				move_uploaded_file($request->file('file'), $pdf_path);

				// Subir documento original
				$original_name = $request->file('original')->getClientOriginalName();
				$original_path = 'documentos/' . uniqid() . '_' . $original_name;

				move_uploaded_file($request->file('original'), $original_path);

				$documento_revision = new DocumentoRevision();

				$documento_revision->parent_documentoid = $documento->parent_documentoid;

				// Validar si el documento es una nueva versión
				if ($documento->parent_documentoid) {
					
					$version_padre = DocumentoRevision::find($documento->parent_documentoid);

					$documento_revision->codigo = $version_padre->codigo;
					$documento_revision->nombre = $version_padre->nombre;
					$documento_revision->tipodocumentoid = $version_padre->tipodocumentoid;

				}else{

					$documento_revision->codigo = $documento->codigo;
					$documento_revision->nombre = $documento->nombre;
					$documento_revision->tipodocumentoid = $documento->tipo_documento;

				}

				
				/*
					* Se deberá de determinar si el tipo de documento requiere QR
				*/

				$tipo_documento = TipoDocumento::find($documento_revision->tipodocumentoid);

				$documento_revision->version = $documento->version;
				$documento_revision->estadoid = 1;
				$documento_revision->codarea = $documento->usuario == 'OSANCHEZ' ? 7 : $empleado->codarea;
				$documento_revision->usuarioid = $documento->usuario;
				$documento_revision->elabora = $empleado->usuario;
				
				$documento_revision->comentarios = $documento->comentarios;

				$documento_revision->documento = $pdf_path;
				$documento_revision->nombre_pdf = $pdf_name;

				$documento_revision->documento_original = $original_path;
				$documento_revision->nombre_original = $original_name;

				// Timestamps
				$documento_revision->created_at = Carbon::now();
				$documento_revision->updated_at = Carbon::now();
				
				if ($tipo_documento->generar_qr) {
					
					$documento_revision->posicion_vertical = $ajustes->posicion_vertical;
					$documento_revision->margen_horizontal = $ajustes->margen_horizontal;

				}

				$documento_revision->save();

				if ($tipo_documento->generar_qr) {
					
					$job_data = (object) [
						"documento" => $documento,
						"ajustes" => $ajustes,
						"file_path" => $path . '/' . $filename,
						"output_path" => $path . '/' . $filename,
						"qr" => [
							[
								"tag" => "elabora",
								"label" => "Elabora",
								"qr" => true,
								"url" => 'http://udicat.muniguate.com/apis/api-documentos-iso/public/verificar_documento/' . Crypt::encrypt($documento_revision->DOCUMENTOID) . '/elabora',
								"responsable" => $empleado->nombre. ' ' . $empleado->apellido,
								"rol" => $rol_alterno  ? $rol_alterno->rol : ($empleado_perfil ? $empleado_perfil->nombre : null),
								"qr_path" => null,
								"nit" => $empleado->nit,
								"usuario" => $empleado->usuario,
								"perfil" => $empleado_perfil ? $empleado_perfil->id : null
							],
							[
								"tag" => "revisa",
								"label" => "Revisa",
								"qr" => false,
								"url" => null,
								"responsable" => null,
								"rol" => null,
								"qr_path" => null
							],
							[
								"tag" => "aprueba",
								"label" => "Aprueba",
								"qr" => false,
								"url" => null,
								"responsable" => null,
								"rol" => null,
								"qr_path" => null
							]
						]
					];
		
					$result = $this->create_pdf($job_data);
	
					$result_qr = $result["qr"];

					// Registrar la información de cada QR generado 
					foreach ($result_qr as $item) {
						
						$documento_qr = new DocumentoQR();
	
						$documento_qr->id_documento = $documento_revision->DOCUMENTOID;
						$documento_qr->etiqueta = $item["tag"];
	
						if ($item["qr"]) {
							
							$documento_qr->path_qr = $item["qr_path"];
							$documento_qr->url_qr = $item["url"];
							$documento_qr->responsable_firma = $item["usuario"];

							if ($rol_alterno) {
								
								$documento_qr->rol_alternativo = $rol_alterno->id;

							}else{

								$documento_qr->rol_responsable = $item["perfil"];

							}
							
						}
	
						$documento_qr->save();
	
					}

				}

				$response = [
					"status" => 200,
					"data" => [
						"documento" => $documento_revision
					]
				];
				
				/*
					* Buscar a los responsables de realizar la revisión en base al proceso 
				*/

				$responsables_revision = ResponsableRevision::select('responsable')->where('codarea', $documento_revision->codarea)->where('modulo', 2)->groupBy('responsable')->get();

				/*
					* Por cada responsable enviar un correo electrónico 
				*/

				foreach ($responsables_revision as $responsable) {
					
					/* 
						* Buscar la información del responsable
					*/

					$encargado_revision = Empleado::where('usuario', $responsable->responsable)->first();

					if ($encargado_revision) {
						
						$nombre_encargado = $encargado_revision->nombre . ' ' . $encargado_revision->apellido;

						$mail_message = "# NUEVO DOCUMENTO. \n Estimado(a) " . $nombre_encargado . " se le informa que se ha subido un nuevo documento en la plataforma de Documentos ISO.  Los datos del documento agregado son los siguientes:"; 

						if ($documento_revision->parent_documentoid) {
							
							// Si el documento es una nueva versión
							$mail_message = "# NUEVA VERSIÓN. \n Estimado(a) " . $nombre_encargado . " se le informa que se ha subido una nueva versión del documento **".$documento_revision->nombre."** a la plataforma de Documentos ISO.  Los datos del documento agregado son los siguientes:"; 

						}

						$tipo_documento = TipoDocumento::find($documento_revision->tipodocumentoid);
						$empleado = Empleado::where('usuario', $documento_revision->elabora)->first();

						if ($encargado_revision->emailmuni) {
							
							$mail_data = [
								[
									"to" => $encargado_revision->emailmuni,
									"view" => "mails.confirm",
									"data" => [
										"message" => $mail_message,
										"nombre" => $documento_revision->nombre,
										"codigo" => $documento_revision->codigo,
										"tipo" => $tipo_documento->nombre,
										"version" => $documento_revision->version,
										"elaborado_por" => $empleado->nombre . ' ' . $empleado->apellido
									]
								]
							];
	
							$result = $this->send_mail($mail_data);

						}

					}

				}
				

				return response()->json($response, 200);

			}

			$response = [
				"path_preview" => $destinationPath . '/' . $filename . '#page=99999',
				"documento" => $documento,
				"ajustes" => $ajustes,
				//"qr" => $result,
				"error_pdf" => $result["status"] == 100 ? true : false
			];


			return response()->json($response);

		}

		public function create_pdf($data){

			$ajustes = (object) $data->ajustes;
			$qr = (object) $data->qr;

			try {
				
				$pdf = new Fpdi();
				$paginas = $pdf->setSourceFile($data->file_path);

			} catch (\Throwable $th) {
				
				$response = [
					"status" => 100,
					"error" => $th->getMessage()
				];

				return $response;

			}
			
			$altura_linea = 7;

			// Agregar cada  una de las páginas del PDF
			for($i = 1 ; $i <= $paginas; $i++){
		
				$tplIdx = $pdf->ImportPage($i);
		
				$specs = $pdf->getTemplateSize($tplIdx);
		
				$pdf->AddPage($specs['height'] > $specs['width'] ? 'P' : 'L', [$specs['height'], $specs['width']]);
		
				$pdf->UseTemplate($tplIdx);
		
			}
			
			// Establecer tamaño de letra y fuente
			$pdf->SetFont('Arial', '', 12);

			// Establece margenes iniciales
			$pdf->setMargins($ajustes->margen_horizontal, 15);
		
			// Indicar el inicio de la sección de firmas en el eje Y 
			$pdf->SetY($ajustes->posicion_vertical);

			foreach ($qr as $item) {
				
				// Encabezado
				$pdf->Cell(($specs['width'] / 3) - ($ajustes->margen_horizontal * 0.65), 7, $item["label"], 1, '', 'C');
		
			}

			// Salto de línea 
			$pdf->Ln();

			// Segunda línea con códigos QR
			foreach ($qr as &$item) {

				$size_box = ($specs['width'] / 3) - ($ajustes->margen_horizontal * 0.65);
				$width_qr = 40;

				if ($item["qr"]) {
					
					$qrCode = new QrCode($item["url"]);
					$qrCode->setSize(300);
					$qrCode->setMargin(10); 
					$qrCode->setWriterByName('png');

					$qr_name = uniqid() . '.png';
					$qrCode->writeFile(base_path('public') . '/qrcodes/' . $qr_name);

					$item["qr_path"] = '/qrcodes/' . $qr_name;

					$pdf->Cell(
						($specs['width'] / 3) - ($ajustes->margen_horizontal * 0.65), 
						40, 
						$pdf->Image(base_path('public') . '/qrcodes/' . $qr_name, $pdf->GetX() + (($size_box - $width_qr) / 2), $pdf->GetY(), 40, 40), 
						1, 
						'', 
						'C'
					);

				}else{

					$pdf->Cell(
						($specs['width'] / 3) - ($ajustes->margen_horizontal * 0.65), 
						40, 
						'', 
						1, 
						'', 
						'C'
					);
					
				}

				$x = $pdf->GetX();

			}

			unset($item);

			// Salto de línea 
			$pdf->Ln();

			// Obtener la altura en base al texto de mayor longitud

			$alturas_tercera_linea = [];

			foreach ($qr as $item) {
				
				$line_height = 7;
				$width = (($specs['width'] / 3) - ($ajustes->margen_horizontal + 30));
				$text = $item["responsable"];    
				$height = ((($pdf->GetStringWidth($text) / $width)) * $line_height);

				$alturas_tercera_linea [] = $height;

			}

			$y = 0;

			// Tercera línea con nombre de quien elabora
			foreach ($qr as $item) {

				$x = $pdf->GetX();
				$y = $pdf->GetY();
				
				// Determinar la altura de la línea
				
				$pdf->Rect($x, $y, ($specs['width'] / 3) - ($ajustes->margen_horizontal * 0.65), max($alturas_tercera_linea));
				
				$pdf->MultiCell(($specs['width'] / 3) - ($ajustes->margen_horizontal * 0.65), 7, utf8_decode($item["responsable"]), 0, 'C');
		
				$pdf->SetXY($x + ($specs['width'] / 3) - ($ajustes->margen_horizontal * 0.65) , $y);
		
			}

			// Salto de línea
			$pdf->Ln();
    
			$pdf->SetXY($pdf->GetX(), $alturas_tercera_linea ? $y + max($alturas_tercera_linea) : $y);

			// Obtener la altura en base al texto de mayor longitud

			$alturas_cuarta_linea = [];

			foreach ($qr as $item) {
				
				$line_height = 7;
				$width = (($specs['width'] / 3) - ($ajustes->margen_horizontal + 30));
				$text = $item["rol"];    
				$height = ((($pdf->GetStringWidth($text) / $width)) * $line_height);

				$alturas_cuarta_linea [] = $height;

			}

			foreach ($qr as $item) {

				$x = $pdf->GetX();
				$y = $pdf->GetY();
		
				$pdf->Rect($x, $y, ($specs['width'] / 3) - ($ajustes->margen_horizontal * 0.65), max($alturas_cuarta_linea));
		
				$pdf->MultiCell(($specs['width'] / 3) - ($ajustes->margen_horizontal * 0.65), 7, utf8_decode($item["rol"]), 0, 'C');
		
				$pdf->SetXY($x + ($specs['width'] / 3) - ($ajustes->margen_horizontal * 0.65) , $y);
		
			}

			// Generá el PDF final
			$final_pdf = $pdf->Output($data->output_path, 'F');

			$response = [
				"status" => 200,
				"qr" => $qr
			];

			return $response;

		}

		public function send_mail($data){

			dispatch(new MailJob($data));

		}

	}

?>