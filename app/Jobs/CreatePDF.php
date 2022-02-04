<?php 

	namespace App\Jobs;

	use setasign\Fpdi\Fpdi;
	
	use DB;

	use App\Empleado;
	use App\TipoDocumento;
	use App\EstadoDocumento;
	use App\Perfil;
	use App\EmpleadoPerfil;

	use Endroid\QrCode\ErrorCorrectionLevel;
	use Endroid\QrCode\LabelAlignment;
	use Endroid\QrCode\QrCode;
	use Endroid\QrCode\Response\QrCodeResponse;

	use Illuminate\Support\Facades\Storage;

	class CreatePDF extends Job{

		protected $data;

		/**
		 * Create a new job instance.
		 *
		 * @return void
		 */
		
		public function __construct($data){
			
			$this->data = $data;


		}

		/**
		 * Execute the job.
		 *
		 * @return void
		 */
		public function handle(){

			$documento = (object) $this->data->documento;

			$ajustes = (object) $this->data->ajustes;

			$pdf = new Fpdi();
			$paginas = $pdf->setSourceFile($this->data->file_path);

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

			// Primera línea del recruadro de firmas
			$primera_linea = [
				[
					"label" => "Elabora"
				],
				[
					"label" => "Revisa"
				],
				[
					"label" => "Aprueba"
				]
			];

			foreach ($primera_linea as $item) {
				
				// Encabezado
				$pdf->Cell(($specs['width'] / 3) - ($ajustes->margen_horizontal * 0.65), 7, $item["label"], 1, '', 'C');
		
			}

			// Salto de línea 
			$pdf->Ln();

			$segunda_linea = [
				[
					"qr" => true,
					"data" => null,
				],
				[
					"qr" => false,
					"data" => null,
				],
				[
					"qr" => false,
					"data" => null,
				]
			];

			// Segunda línea con códigos QR
			foreach ($segunda_linea as $item) {

				$size_box = ($specs['width'] / 3) - ($ajustes->margen_horizontal * 0.65);
				$width_qr = 40;

				if ($item["qr"]) {
					
					$qrCode = new QrCode('https://udicat.muniguate.com');
					$qrCode->setSize(300);
					$qrCode->setMargin(10); 
					$qrCode->setWriterByName('png');

					$qr_name = uniqid() . '.png';
					$qrCode->writeFile(base_path('public') . '/qrcodes/' . $qr_name);

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

			}

			// Salto de línea 
			$pdf->Ln();

			// Buscar nombre de la persona que elabora
			$elabora = Empleado::find($documento->elabora);

			$tercera_linea = [$elabora->nombre . ' ' . $elabora->apellido, null, null];

			// Tercera línea con nombre de quien elabora
			foreach ($tercera_linea as $item) {

				$x = $pdf->GetX();
				$y = $pdf->GetY();
		
				$pdf->Rect($x, $y, ($specs['width'] / 3) - ($ajustes->margen_horizontal * 0.65), $altura_linea * $ajustes->lineas_nombre);
		
				$pdf->MultiCell(($specs['width'] / 3) - ($ajustes->margen_horizontal * 0.65), 7, utf8_decode($item), 0, 'C');
		
				$pdf->SetXY($x + ($specs['width'] / 3) - ($ajustes->margen_horizontal * 0.65) , $y);
		
			}

			// Salto de línea
			$pdf->Ln();
    
			$pdf->SetXY($pdf->GetX(), $y + $altura_linea * $ajustes->lineas_nombre);

			$empleado_perfil = EmpleadoPerfil
								::select('rrhh_perfil.nombre')
								->join('rrhh_perfil', 'rh_empleado_perfil.id_perfil', '=', 'rrhh_perfil.id')
								->where('nit', $elabora->nit)
								->first();

			// Cuarta línea
			$cuarta_linea = [
				$empleado_perfil ? $empleado_perfil->nombre : null, null, null
			];

			foreach ($cuarta_linea as $item) {

				$x = $pdf->GetX();
				$y = $pdf->GetY();
		
				$pdf->Rect($x, $y, ($specs['width'] / 3) - ($ajustes->margen_horizontal * 0.65), $altura_linea * $ajustes->lineas_puesto);
		
				$pdf->MultiCell(($specs['width'] / 3) - ($ajustes->margen_horizontal * 0.65), 7, utf8_decode($item), 0, 'C');
		
				$pdf->SetXY($x + ($specs['width'] / 3) - ($ajustes->margen_horizontal * 0.65) , $y);
		
			}

			// Generá el PDF final
			$final_pdf = $pdf->Output($this->data->file_path,'F');

			return 'test';

		}

		public function getResponse(){

			return $this->data;

		}
	}

?>