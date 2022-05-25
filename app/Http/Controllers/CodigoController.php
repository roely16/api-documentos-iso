<?php 

	namespace App\Http\Controllers;

	use Illuminate\Http\Request;

	use App\TipoDocumento;
	use App\Empleado;
	use App\Area;
	use App\DocumentoRevision;
	use App\DocumentoPortal;
	use App\ISOSeccion;

	class CodigoController extends Controller{
		
		public function get_acronimo_tipo(Request $request){

			$tipo_documento = TipoDocumento::find($request->tipo_documento);

			return response()->json($tipo_documento->nomenclatura);

		}

		public function get_acronimo_seccion(Request $request){

			$colaborador = Empleado::where('nit', $request->colaborador)->first();

			$area = Area::find($colaborador->codarea);

			return response()->json($area->acronimo_calidad);

		}

		public function check_code(Request $request){

			// Buscar por sección en Documentos ISO
			$iso_seccion = ISOSeccion::where('codarea', $request->codarea)->first();

			if ($request->edit) {
				
				$documentos_revision = DocumentoRevision::where('codigo', $request->code)
										->where('baja', '0')
										->where('documentoid', '!=', $request->id)
										->where('deleted_at', null)
										->count();

				$available = $documentos_revision == 0 ? true : false;

				$response = [
					"available" => $available,
					"type" => $available ? 'success' : 'error',
					"message" => $available ? 'El código ' .$request->code. ' se encuentra disponible' : 'El código ' .$request->code. ' ya ha sido utilizado. Por favor elija otro. '
				];

				return response()->json($response);

			}

			$documentos_revision = DocumentoRevision::where('codigo', $request->code)
									->where('baja', '0')
									->where('deleted_at', null)
									->count();

			// if ($documentos_revision > 0) {
				
			// 	$documentos_portal = DocumentoPortal::where('codigo', $request->code)
			// 						->where('categoriaid', $iso_seccion->seccionid)
			// 						->where('deleted_at', null)
			// 						->count();

			// }

			$available = $documentos_revision == 0 ? true : false;

			$response = [
				"available" => $available,
				"type" => $available ? 'success' : 'error',
				"message" => $available ? 'El código ' .$request->code. ' se encuentra disponible' : 'El código ' .$request->code. ' ya ha sido utilizado. Por favor elija otro. '
 			];

			return response()->json($response);

		}

	}

?>