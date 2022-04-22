<?php 

	namespace App\Http\Controllers;

	use Illuminate\Http\Request;

	use App\ISOSeccion;
	use App\DocumentoPortal;

	use Carbon\Carbon;

	use DB;

	class DepurationController extends Controller{

		public function get_iso_sections(){

			$secciones = ISOSeccion::where('portalid', 1)->where('posicion', 1)->get();

			foreach ($secciones as &$seccion) {
				
				$seccion->selected = false;

			}

			$response = [
				"secciones" => $secciones
			];

			return response()->json($response);

		}

		public function get_section_documents(Request $request){

			$documentos_portal = DocumentoPortal::where('categoriaid', $request->seccionid)
									->where('deleted_at', null)
									->orderBy('nombre', 'asc')
									->get();

			foreach ($documentos_portal as $documento) {
				
				$documento->checked = false;

			}

			$response = [
				"documentos" => $documentos_portal
			];

			return response()->json($response);

		}

		public function delete_documents_portal(Request $request){

			$documents = $request->documents;

			foreach ($documents as &$document) {

				$result = DB::connection('portales')->table('iso_documentos')->where('documentoid', $document["documentoid"])->update([
					"deleted_at" => Carbon::now()
				]);

			}

			$response = [
				"status" => 200,
				"message" => "El documento ha sido eliminado exitosamente!"
			];

			return response()->json($response);

		}

	}

?>