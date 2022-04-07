<?php 

	namespace App\Http\Controllers;

	use Illuminate\Http\Request;

	use Endroid\QrCode\ErrorCorrectionLevel;
	use Endroid\QrCode\LabelAlignment;
	use Endroid\QrCode\QrCode;
	use Endroid\QrCode\Response\QrCodeResponse;

	use App\Empleado;
	use App\DocumentoQR;
	use App\EmpleadoPerfil;
	use App\DocumentoRevision;
	use App\ISOSeccion;
	use App\DocumentoPortal;
	use App\TipoDocumento;
	use App\RolAlterno;
	use App\ResponsableRevision;

	use Carbon\Carbon;

	use Illuminate\Support\Facades\Crypt;

	use App\Jobs\MailJob;

	use DB;

	class QRController extends Controller{

		public function obtener_firmas(Request $request){

			$signatures = $this->get_signatures_data($request->nit);

			return response()->json($signatures);

		}

		public function process_pdf(Request $request){

			$nit = '6450819-6';

			$signatures = $this->get_signatures_data($nit);

			$signatures = $signatures["firmas"];

			$ajustes = json_decode($request->settings);

			$filename = uniqid() . '.pdf';

			// Carpeta donde se almacenará el archivo
			$path = 'temp_files';

			// URL para su lectura 
			$destinationPath = $_SERVER['HTTP_HOST'] . '/apis/api-documentos-iso/public/' . $path;

			$request->file('file')->move($path, $filename);

			$dir = $path . '/' . $filename;

			$pdf = new Fpdi();

			$paginas = $pdf->setSourceFile($dir);

			// Altura estándar de línea 
			$altura_linea = 7;
		
			// Agregar cada una de las páginas al nuevo PDF que llevará los QR
			for($i = 1 ; $i<=$paginas; $i++){
		
				$tplIdx = $pdf->ImportPage($i);
		
				$specs = $pdf->getTemplateSize($tplIdx);
		
				$pdf->AddPage($specs['height'] > $specs['width'] ? 'P' : 'L', [$specs['height'], $specs['width']]);
		
				$pdf->UseTemplate($tplIdx);
		
			}

			// Establecer el tamaño de letra
			$pdf->SetFont('Arial', '', 12);

			$margen_horizontal = $ajustes->margen_horizontal;
			$margen_vertical = $ajustes->margen_horizontal;
		
			$pdf->setMargins($margen_horizontal, $margen_vertical);
		
			// Indicar el inicio de la sección de firmas en el eje Y 
			$pdf->SetY($ajustes->posicion_y);
    
			// Primera línea del cuadro de firmas 
			foreach ($signatures as $signature) {
				
				// Encabezado
				$pdf->Cell(($specs['width'] / 3) - ($margen_horizontal * 0.65), 7, $signature["tag"], 1, '', 'C');
		
			}

			// Salto de línea 
			$pdf->Ln();

			// Segunda línea con códigos QR
			foreach ($signatures as $signature) {
        
				$size_box = ($specs['width'] / 3) - ($margen_horizontal * 0.65);
				$width_qr = 40;
		
			}

			$final_pdf = $pdf->Output($dir,'F'); 

			$ssl = isset($_SERVER['HTTPS']) ? 'https://' : 'http://';

			$response = [
				"filename" => $filename,
				"path" => $path,
				"destinationPath" => $ssl . $destinationPath . '/' . $filename
			];

			return response()->json($response);

		}

		public function get_signatures_data($nit){

			$empleado = Empleado::find($nit);

			$firmas = [];

			$elabora = [
				"tag" => "Elabora",
				"name" => $empleado->nombre . ' ' . $empleado->apellido,
				"role" => null
			];

			$firmas [] = $elabora;

			$revisa = [
				"tag" => "Revisa",
				"name" => "Maura Lucrecia Chitay Fajardo",
				"role" => "Responsable del Sistema de Gestión de la Calidad"
			];

			$firmas [] = $revisa;

			$aprueba = [
				"tag" => "Aprueba",
				"name" => "Oscar Enrique Sanchez Mazariegos",
				"role" => "Representante de Dirección"
			];

			$firmas [] = $aprueba;

			$response = [
				"firmas" => $firmas,
			];

			return $response;

		}

		public function revisa($data){

			try {
			
				/*
					* Validar si es necesario crear el QR
				*/

				$documento = DocumentoRevision::find($data->id_documento);

				$tipo_documento = TipoDocumento::find($documento->tipodocumentoid);

				/* 
					* Enviar correo indicando que el documento ya fue revisado y se puede publicar 
				*/

				$responsables_revision = ResponsableRevision::select('responsable')->where('codarea', $documento->codarea)->where('modulo', 3)->groupBy('responsable')->get();

				foreach ($responsables_revision as $responsable) {
					
					/* 
						* Buscar la información del responsable
					*/

					$encargado_revision = Empleado::where('usuario', $responsable->responsable)->first();

					if ($encargado_revision) {
						
						$nombre_encargado = $encargado_revision->nombre . ' ' . $encargado_revision->apellido;

						$mail_message = "# DOCUMENTO REVISADO. \n Estimado(a) " . $nombre_encargado . " se le informa que un nuevo documento ha sido revisado y se encuentra listo para su publicación.  Los datos del documento son los siguientes:"; 

						$tipo_documento = TipoDocumento::find($documento->tipodocumentoid);
						$empleado = Empleado::where('usuario', $documento->elabora)->first();

						if ($encargado_revision->emailmuni) {
							
							$mail_data = [
								[
									"to" => $encargado_revision->emailmuni,
									"view" => "mails.confirm",
									"data" => [
										"message" => $mail_message,
										"nombre" => $documento->nombre,
										"codigo" => $documento->codigo,
										"tipo" => $tipo_documento->nombre,
										"version" => $documento->version,
										"elaborado_por" => $empleado->nombre . ' ' . $empleado->apellido
									]
								]
							];
	
							dispatch(new MailJob($mail_data));

						}

					}

				}

				if ($tipo_documento->generar_qr) {
					
					$documento_qr = DocumentoQR::where('id_documento', $data->id_documento)->where('etiqueta', 'revisa')->first();

					$empleado = Empleado::where('usuario', $data->usuario)->first();

					/*
						* Buscar si la persona tiene un rol alternativo
					*/

					$rol_alterno = RolAlterno::where('usuario', $empleado->usuario)->first();

					$perfil = EmpleadoPerfil::where('nit', $empleado->nit)->first();

					$ssl = isset($_SERVER['HTTPS']) ? 'https://' : 'http://';

					$url = $ssl . $_SERVER['HTTP_HOST'] . '/apis/api-documentos-iso/public/verificar_documento/' . Crypt::encrypt($data->id_documento) . '/revisa';

					$qrCode = new QrCode($url);
					
					$qrCode->setSize(300);
					$qrCode->setMargin(10); 
					$qrCode->setWriterByName('png');

					$qr_name = uniqid() . '.png';
					$qrCode->writeFile(base_path('public') . '/qrcodes/' . $qr_name);

					// Actualizar el registro

					$result = DB::connection('portales')
								->table('ISO_DOCUMENTO_QR')
								->where('id_documento', $data->id_documento)
								->where('etiqueta', 'revisa')
								->update([
									'path_qr' =>'/qrcodes/' . $qr_name,
									'url_qr' => $url,
									'responsable_firma' => $empleado->usuario,
									!$rol_alterno ? 'rol_responsable' : 'rol_alternativo' => !$rol_alterno ? $perfil->id_perfil : $rol_alterno->id,
									'created_at' => Carbon::now(),
									'updated_at' => Carbon::now()
								]);

					return $result;

				}

				return true;
				

			} catch (\Throwable $th) {
				
				return $th->getMessage();

			}
			
		}

		public function aprueba($data){

			try {
				
				/*
					* Validar si es necesario crear el QR
				*/

				$documento = DocumentoRevision::find($data->id_documento);

				$tipo_documento = TipoDocumento::find($documento->tipodocumentoid);

				if ($tipo_documento->generar_qr) {
					
					$documento_qr = DocumentoQR::where('id_documento', $data->id_documento)->where('etiqueta', 'aprueba')->first();

					$empleado = Empleado::where('usuario', $data->usuario)->first();

					/*
						* Buscar si la persona tiene un rol alternativo
					*/
					$rol_alterno = RolAlterno::where('usuario', $empleado->usuario)->first();

					$perfil = EmpleadoPerfil::where('nit', $empleado->nit)->first();

					$ssl = isset($_SERVER['HTTPS']) ? 'https://' : 'http://';

					$url = $ssl . $_SERVER['HTTP_HOST'] . '/apis/api-documentos-iso/public/verificar_documento/' . Crypt::encrypt($data->id_documento) . '/aprueba';

					$qrCode = new QrCode($url);
					
					$qrCode->setSize(300);
					$qrCode->setMargin(10); 
					$qrCode->setWriterByName('png');

					$qr_name = uniqid() . '.png';
					$qrCode->writeFile(base_path('public') . '/qrcodes/' . $qr_name);

					// Actualizar el registro

					DB::connection('portales')
								->table('ISO_DOCUMENTO_QR')
								->where('id_documento', $data->id_documento)
								->where('etiqueta', 'aprueba')
								->update([
									'path_qr' =>'/qrcodes/' . $qr_name,
									'url_qr' => $url,
									'responsable_firma' => $empleado->usuario,
									!$rol_alterno ? 'rol_responsable' : 'rol_alternativo' => !$rol_alterno ? $perfil->id_perfil : $rol_alterno->id,
									'created_at' => Carbon::now(),
									'updated_at' => Carbon::now()
								]);

				
				}
				
				// Generar el archivo físico con las firmas incrustadas
				$documento_revision = DocumentoRevision::find($data->id_documento);

				$request = new Request();

				$filename =  uniqid() . '_' . $documento_revision->nombre . ".pdf";

				$dir_path = __DIR__;

				$pub_path = $_SERVER['DOCUMENT_ROOT'] . '/catastro/iso/documentos/' . $filename;

				// Si el documento NO lleva QR

				if (!$tipo_documento->generar_qr) {
					
					copy($documento_revision->documento, $pub_path);

				}else{

					$request->replace([
						"id" => $data->id_documento,
						"preview" => false,
						"pub_path" => $pub_path
					]);
	
					$result_request = app('App\Http\Controllers\DetailDocumentController')->get_detail($request);

				}
				

				// Guardar en la tabla ISO_DOCUMENTOS
				$iso_seccion = ISOSeccion::where('codarea', $documento_revision->codarea)->first();

				// Buscar si ya existe algún documento con el mismo código en el portal
				$documento_portal = DocumentoPortal
									::where('codigo', $documento_revision->codigo)
									->where('deleted_at', null)
									->first();

				if ($documento_portal) {
					
					// Actualizar el documento existente
					$result_update = DB::connection('portales')
										->table('ISO_DOCUMENTOS')
										->where('codigo', $documento_revision->codigo)
										->update([
											'deleted_at' => Carbon::now()
										]);

				}

				// Crear un nuevo registro
				$documento_portal = new DocumentoPortal();
				$documento_portal->nombre = $documento_revision->codigo . ' ' .  $documento_revision->nombre;
				$documento_portal->categoriaid = $iso_seccion->seccionid;
				$documento_portal->archivo = $filename;
				$documento_portal->creado = Carbon::now();
				$documento_portal->modificado = Carbon::now();
				$documento_portal->portalid = 1;
				$documento_portal->codigo = $documento_revision->codigo;
				$documento_portal->codarea = 0;
				$documento_portal->id_documento_revision = $documento_revision->documentoid;
				$documento_portal->save();

				return $documento_portal;

			} catch (\Throwable $th) {
				
				return $th->getMessage();

			}

		}

		public function test_qr(Request $request){

			return QRcode::png('code data text', 'filename.png');

		}

	}

?>