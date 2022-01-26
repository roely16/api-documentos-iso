<?php 

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Empleado;
use App\ImagenPerfil;
use DB;

class LoginController extends Controller{

	public function login(Request $request){

		try {
			
			$empleado = Empleado
						::where(DB::raw('UPPER(USUARIO)'), strtoupper($request->user))
						->where(DB::raw('UPPER(DESENCRIPTAR(PASS))'), strtoupper($request->password))
						->where('status', 'A')
						->first();

			// Si las credenciales son incorrectas

			if(!$empleado){

				return response()->json(['message' => 'Usuario o contraseña incorrectos.'], 401);

			}

			// Obtener la imagen de perfil del usuario
			$imagen_perfil = ImagenPerfil::where('nit', $empleado->nit)->where('idcat', 11)->first();

			$path = $imagen_perfil ? 'http://' . $_SERVER['HTTP_HOST'] . '/GestionServicios/'. $imagen_perfil->ruta : null;

			$data_response = [
				"nombre" => $empleado->nombre,
				"apellido" => $empleado->apellido,
				"codpuesto" => $empleado->codpuesto,
				"codarea" => $empleado->codarea,
				"usuario" => $empleado->usuario,
				"depende" => $empleado->depende,
				"nit" => $empleado->nit,
				"jefe" => $empleado->jefe,
				"imagen_perfil" => $path
			];

			return response()->json($data_response, 200);

		} catch (\Throwable $th) {
			
			return response()->json($th->getMessage());

		}

	}

}

?>