<?php 

	namespace App\Http\Controllers;

	use Illuminate\Http\Request;
	// use SimpleSoftwareIO\QrCode\Facades\QrCode;
	// use setasign\Fpdi\Fpdi;
	
	// include (base_path() . '/vendor/phpqrcode/qrlib.php');

	use Endroid\QrCode\ErrorCorrectionLevel;
	use Endroid\QrCode\LabelAlignment;
	use Endroid\QrCode\QrCode;
	use Endroid\QrCode\Response\QrCodeResponse;

	use App\Empleado;
	use App\DocumentoQR;
	use App\EmpleadoPerfil;

	use Carbon\Carbon;

	use Illuminate\Support\Facades\Crypt;

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
				
				// QrCode::format('png')->generate('Make me into a QrCode!', '../public/qrcodes/qrcode.png');

				// // Código QR
				// $pdf->Cell(($specs['width'] / 3) - ($margen_horizontal * 0.65), 40, $pdf->Image('../public/qrcodes/qrcode.svg', $pdf->GetX() + (($size_box - $width_qr) / 2), $pdf->GetY(), 40, 40), 1, '', 'C');
		
			}

			$final_pdf = $pdf->Output($dir,'F'); 

			$response = [
				"filename" => $filename,
				"path" => $path,
				"destinationPath" => 'http://' . $destinationPath . '/' . $filename
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
			
				$documento_qr = DocumentoQR::where('id_documento', $data->id_documento)->where('etiqueta', 'revisa')->first();

				$empleado = Empleado::where('usuario', $data->usuario)->first();

				$perfil = EmpleadoPerfil::where('nit', $empleado->nit)->first();

				$url = 'http://' . $_SERVER['HTTP_HOST'] . '/apis/api-documentos-iso/public/verificar_documento/' . Crypt::encrypt($data->id_documento) . '/revisa';

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
								'rol_responsable' => $perfil->id_perfil,
								'created_at' => Carbon::now(),
								'updated_at' => Carbon::now()
							]);

				return $result;

			} catch (\Throwable $th) {
				
				return $th->getMessage();

			}
			
		}

		public function aprueba($data){

			try {
			
				$documento_qr = DocumentoQR::where('id_documento', $data->id_documento)->where('etiqueta', 'aprueba')->first();

				$empleado = Empleado::where('usuario', $data->usuario)->first();

				$perfil = EmpleadoPerfil::where('nit', $empleado->nit)->first();

				$url = 'http://' . $_SERVER['HTTP_HOST'] . '/apis/api-documentos-iso/public/verificar_documento/' . Crypt::encrypt($data->id_documento) . '/aprueba';

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
							->where('etiqueta', 'aprueba')
							->update([
								'path_qr' =>'/qrcodes/' . $qr_name,
								'url_qr' => $url,
								'responsable_firma' => $empleado->usuario,
								'rol_responsable' => $perfil->id_perfil,
								'created_at' => Carbon::now(),
								'updated_at' => Carbon::now()
							]);

				return $result;

			} catch (\Throwable $th) {
				
				return $th->getMessage();

			}


		}

		public function test_qr(Request $request){

			return QRcode::png('code data text', 'filename.png');

		}

	}

?>