<?php 

	namespace App\Http\Controllers;

	use Illuminate\Http\Request;

	use App\Menu;
	use App\Permiso;

	class MenuController extends Controller{
		
		public function get_menu(Request $request){

			$menu = Menu::whereIn('id', Permiso::select('id_menu')->where('usuario', $request->user)->get()->toArray())->orderBy('orden', 'asc')->get();

			return response()->json($menu);

		}

		public function check_access(Request $request){

			$menu_op = Menu::where('name', $request->url)->first();

			$permiso = Permiso
						::where('usuario', $request->user)
						->where('id_menu', $menu_op->id)
						->first();

			$response = [
				"access" => $permiso ? true : false,
				"editable" => $menu_op->editable == 'S' ? true : false
			];

			return response()->json($response);

		}

	}

?>