<?php 

	namespace App\Http\Controllers;

	use Illuminate\Http\Request;

	use App\DocumentoRevision;
	use App\TipoDocumento;
	use App\EstadoDocumento;
	use App\ResponsableRevision;
	use App\Area;
	use App\Menu;

	class CheckController extends Controller{

		public function fetch_documents_check(Request $request){

			if ($request->area) {
				
				$documentos_revision = DocumentoRevision::where('CODAREA', $request->area)->where('PARENT_DOCUMENTOID', null)->where('BAJA', '0')->orderBy('DOCUMENTOID', 'desc')->get();

			}else{

				// Obtener las áreas o secciones asignadas
				$menu = Menu::where('name', $request->module)->first();

				$areas = Area::select('codarea')
							->where('estatus', 'A')
							->whereIn('codarea', ResponsableRevision
													::select('codarea')
													->where('responsable', $request->usuario)
													->where('modulo', $menu->id)
													->get()
													->toArray()
							)
							->orderBy('codarea', 'asc')
							->get()
							->toArray();

				$documentos_revision = DocumentoRevision
										::where('PARENT_DOCUMENTOID', null)
										->whereIn('CODAREA', $areas)
										->where('BAJA', '0')
										->orderBy('DOCUMENTOID', 'desc')
										->get();

			}

			foreach ($documentos_revision as &$documento) {
				
				$tipo_documento = TipoDocumento::find($documento->tipodocumentoid);

				$documento->tipo_documento = $tipo_documento ? $tipo_documento->nombre : null;

				// Validar si no tiene versiones 
				$versiones = DocumentoRevision::where('parent_documentoid', $documento->documentoid)->orderBy('documentoid', 'desc')->get();

				if ($versiones->count() > 0) {
					
					$child_document = $versiones[0];

					$estado = EstadoDocumento::find($child_document->estadoid);

				}else{

					$estado = EstadoDocumento::find($documento->estadoid);

				}

				$documento->estado = $estado;

				$area = Area::find($documento->codarea);

				$documento->seccion = $area->descripcion;

			}

			$headers = [
				[
					"text" => "ID",
					"value" => "documentoid",
					"width" => '5%',
					"sortable" => false,
				],
				[
					"text" => "Código",
					"value" => "codigo",
					"sortable" => false,
					"width" => "10%"
				],
				[
					"text" => "Nombre",
					"value" => "nombre",
					"sortable" => false,
					"width" => "30%"
				],
				[
					"text" => "Sección o Coordinación",
					"value" => "seccion",
					"sortable" => false,
					"width" => "20%"
				],
				[
					"text" => "Tipo",
					"value" => "tipo_documento",
					"sortable" => false,
					"width" => "15%",
					"custom" => true
				],
				[
					"text" => "Estado",
					"value" => "estado",
					"sortable" => false,
					"width" => "15%",
					"custom" => true
				],
				[
					"text" => "Acción",
					"value" => "action",
					"sortable" => false,
					"width" => "10%",
					"align" => "right",
					"custom" => true
				]
			];

			$response = [
				"items" => $documentos_revision,
				"headers" => $headers
			];

			return response()->json($response, 200);

		}

	}

?>