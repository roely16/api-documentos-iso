<?php 

	namespace App\Http\Controllers;

	use Illuminate\Http\Request;

	use Illuminate\Support\Facades\Crypt;

	use App\DocumentoQR;
	use App\DocumentoRevision;
	use App\Empleado;
	use App\Perfil;
	use App\RolAlterno;

	class CheckQRController extends Controller{
		
		public function check_qr($id, $tag){

			try {
				
				$id_documento = Crypt::decrypt($id);

				$documento_qr = DocumentoQR::where('id_documento', $id_documento)->where('etiqueta', $tag)->first();

				$documento = DocumentoRevision::find($id_documento);

				$empleado = Empleado::where('usuario', $documento_qr->responsable_firma)->first();

				$perfil = !$documento_qr->rol_alternativo ? Perfil::find($documento_qr->rol_responsable) : RolAlterno::find($documento_qr->rol_alternativo);

				$response = [
					"documento" => $documento,
					"qr" => $documento_qr,
					"empleado" => $empleado,
					"perfil" => $perfil
				];

				return view('check_qr', $response);

			} catch (\Throwable $th) {
				
				return response($th->getMessage(), 404);

			}

			
		}

	}

?>