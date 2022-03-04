<?php 

	namespace App\Http\Controllers;

	use Illuminate\Http\Request;

	use App\Area;
	use App\EstadoDocumento;
	use App\TipoDocumento;
	use App\ResponsableRevision;
	use App\Menu;

	class FilterController extends Controller{
	
		public function get_filters(Request $request){

			// Obtener el módulo en el que se encuentra el usuario
			$menu = Menu::where('name', $request->module)->first();

			$areas = Area::where('estatus', 'A')
							->whereIn('codarea', ResponsableRevision
													::select('codarea')
													->where('responsable', $request->usuario)
													->where('modulo', $menu->id)
													->get()
													->toArray()
							)
							->orderBy('codarea', 'asc')
							->get();

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