<?php 

	namespace App\Http\Controllers;

	use Illuminate\Http\Request;

	use App\Area;
	use App\EstadoDocumento;
	use App\TipoDocumento;

	class FilterController extends Controller{
	
		public function get_filters(){

			$areas = Area::where('estatus', 'A')->orderBy('codarea', 'asc')->get();

			$estados = EstadoDocumento::orderBy('estadoid', 'asc')->get();

			$tipos = TipoDocumento::orderBy('tipodocumentoid')->get();

			$response = [
				"areas" => $areas,
				"tipos" => $tipos,
				"estados" => $estados
			];

			return response()->json($response);

		}		

	}

?>