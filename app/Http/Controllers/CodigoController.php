<?php 

	namespace App\Http\Controllers;

	use Illuminate\Http\Request;

	use App\TipoDocumento;
	use App\Empleado;
	use App\Area;
	use App\DocumentoRevision;
	use App\DocumentoPortal;

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

			$documentos_revision = DocumentoRevision::where('codigo', $request->code)
									->where('baja', '0')
									->count();

			$documentos_portal = DocumentoPortal::where('codigo', $request->code)
									->where('deleted_at', null)
									->count();

			$available = $documentos_revision == 0 && $documentos_portal == 0 ? true : false;

			$response = [
				"available" => $available,
				"type" => $available ? 'success' : 'error',
				"message" => $available ? 'El código ingresado se encuentra disponible' : 'El código asignado al documento ya ha sido utilizado. Por favor elija otro. '
 			];

			return response()->json($response);

		}

	}

?>