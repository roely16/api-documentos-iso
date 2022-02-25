<?php 

	namespace App\Http\Controllers;

	use Illuminate\Http\Request;

	use App\TipoDocumento;
	use App\Empleado;
	use App\Area;

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

	}

?>