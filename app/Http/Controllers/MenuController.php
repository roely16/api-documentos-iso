<?php 

	namespace App\Http\Controllers;

	use Illuminate\Http\Request;

	use App\Menu;

	class MenuController extends Controller{
		
		public function get_menu(Request $request){

			$menu = Menu::all();

			return response()->json($menu);

		}

	}

?>