<?php 

	namespace App\Http\Controllers;

	use Illuminate\Http\Request;

	use App\DocumentoRevision;
	use App\TipoDocumento;
	use App\EstadoDocumento;
	use App\ResponsableRevision;
	use App\Area;
	use App\Menu;

	use DB;

	class PublicationController extends Controller{

		public function fetch_documents(Request $request){

			/*
				* Validar registro por registro si tiene un documento hijo y el estado de este documento
			*/

			if ($request->area) {

				$documentos_revision = DocumentoRevision
										::where('CODAREA', $request->area)
										->whereIn('ESTADOID', [4])
										->orderBy('DOCUMENTOID', 'desc')
										->get();

			}else{

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
										::whereIn('ESTADOID', [4])
										->whereIn('CODAREA', $areas)
										->orderBy('DOCUMENTOID', 'desc')
										->get();

			}

			$documentos = [];

			foreach ($documentos_revision as &$documento) {
				
				/*
					* Validar si el documento depende de otro y asignar este como su ID
				*/

				if ($documento->parent_documentoid) {
					
					$documento->documentoid = $documento->parent_documentoid;

				}

				$tipo_documento = TipoDocumento::find($documento->tipodocumentoid);

				$documento->tipo_documento = $tipo_documento ? $tipo_documento->nombre : null;

				$estado = EstadoDocumento::find($documento->estadoid);

				$documento->estado = $estado;

				$area = Area::find($documento->codarea);

				$documento->seccion = $area->descripcion;

				/* 
					* Validar si la versión superior esta en un estado que no sea Autorizado o Publicado
				*/

				// Validar si no tiene versiones 
				$versiones = DocumentoRevision::where('parent_documentoid', $documento->documentoid)->orderBy('documentoid', 'desc')->get();

				$documento->versiones = $versiones;

				if ($versiones->count() > 0) {
					
					$child_document = $versiones[0];

					if ($child_document->estadoid == 4 || $child_document->estadoid == 5) {
						
						$documentos [] = $documento;

					}

				}else{

					$documentos [] = $documento;

				}

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
				"items" => $documentos,
				"headers" => $headers
			];

			return response()->json($response, 200);

		}

	}

?>