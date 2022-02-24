<?php 

	namespace App\Http\Controllers;

	use Illuminate\Http\Request;

	use App\Area;
	use App\Empleado;
	use App\Menu;
	use App\Permiso;

	use DB;

	class PermisosController extends Controller{

		public function get_areas(Request $request){

			$areas = Area::where('estatus', 'A')->where('ISO', '1')->orderBy('descripcion', 'asc')->get();

			foreach ($areas as &$area) {
								
				$empleados = Empleado
								::where('codarea', $area->codarea)
								->where('status', 'A')
								->whereNotIn('nit', Permiso::select('usuario')->groupBy('usuario')->get()->toArray())
								->orderBy('jefe', 'desc')
								->get();

				$area->empleados = $empleados;

			}

			return response()->json($areas);

		}

		public function get_options(){

			$menu = Menu::orderBy('id', 'asc')->get();

			foreach ($menu as &$option) {
				
				$option->check = false;

			}

			return response()->json($menu);

		}

		public function save_permission(Request $request){

			$colaboradores = $request->colaboradores;
			$permisos = $request->permisos;

			foreach ($permisos as $permiso) {
				
				foreach ($colaboradores as $colaborador) {
					
					$nuevo_permiso = new Permiso();
					$nuevo_permiso->id_menu = $permiso;
					$nuevo_permiso->usuario = $colaborador;
					$nuevo_permiso->save();

				}

			}

			return response()->json($request);

		}

		public function get_permissions(){

			$permisos = Permiso::select('usuario')->groupBy('usuario')->get();

			foreach ($permisos as &$permiso) {
				
				$colaborador = Empleado::where('nit', $permiso->usuario)->first();

				$permiso->colaborador = $colaborador->nombre . ' ' . $colaborador->apellido;

				$accesos = Permiso::where('usuario', $permiso->usuario)->get();

				foreach ($accesos as &$acceso) {
					
					$option = Menu::find($acceso->id_menu);

					$acceso->option = $option;

				}

				$permiso->accesos = $accesos;

			}

			$headers = [
				[
					"text" => "Colaborador",
					"value" => "colaborador",
					"width" => '40%',
					"sortable" => false,
				],
				[
					"text" => "Accesos",
					"value" => "accesos",
					"width" => '40%',
					"sortable" => false,
					"custom" => true
				],
				[
					"text" => "Acción",
					"value" => "action",
					"width" => '20%',
					"sortable" => false,
					"align" => "right",
					"custom" => true
				]
			];

			$response = [
				"headers" => $headers,
				"items" => $permisos
			];

			return response()->json($response);

		}

		public function get_detail(Request $request){

			$colaborador = Empleado::where('nit', $request->usuario)->first();

			// Obtener la sección o coordinación del colaborador 
			$area = Area::find($colaborador->codarea);

			$colaborador->area = $area->descripcion;

			// Permisos existentes
			$asignados = Menu::whereIn('id', Permiso::select('id_menu')->where('usuario', $request->usuario)->get()->toArray())->get();

			// Accesos pendientes
			$pendientes = Menu::whereNotIn('id', Permiso::select('id_menu')->where('usuario', $request->usuario)->get()->toArray())->get();

			$response = [
				"colaborador" => $colaborador,
				"asignados" => $asignados,
				"pendientes" => $pendientes
			];

			return response()->json($response);

		}

		public function remove_add_permission(Request $request){

			// Quitar el acceso
			if ($request->action === 'remove') {

				$result = DB::connection('portales')
							->table('ISO_DOCUMENTOS_PERMISOS')
							->where('usuario', $request->usuario)
							->where('id_menu', $request->id_menu)
							->delete();

				return response()->json($request);

			}

			// Agregar el acceso
			$permiso = new Permiso();

			$permiso->usuario = $request->usuario;
			$permiso->id_menu = $request->id_menu;
			$permiso->save();

			return response()->json($permiso);

		}

	}

?>