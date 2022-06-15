<?php 

	namespace App\Http\Controllers;

	use Illuminate\Http\Request;

	use App\DocumentoRevision;
	use App\TipoDocumento;
	use App\EstadoDocumento;
	use App\ResponsableRevision;
	use App\Area;
	use App\Menu;

	use Illuminate\Support\Facades\DB;

	class CheckController extends Controller{

		public function fetch_documents_check(Request $request){

			/* 
				* Para determinar los estados se debe de realizar una consulta en base al módulo al que el usuario esta accediendo.

				* En el módulo de Verificación se mostrarán aquellos documentos que requieren QR y que están en estado (1, 2)

				* En el módulo de Verificación de Forma pasan todos los documentos bajo las siguientes condiciones:
					- Documentos QR cuando ya han sido verificados, es decir, están en estado (4)
					- Documentos sin QR se deberán de mostrar desde que son subidos, es decir, están en estado (1,2)
			*/

			// * Validar las áreas de las cuales se deben de obtener los documentos

			$areas = [];
					
			if ($request->area) {
				
				$areas [] = $request->area;

			}else{

				$menu = Menu::where('name', $request->module)->first();

				$areas = Area::select('codarea')
							->where('estatus', 'A')
							->whereIn('codarea', ResponsableRevision::select('codarea')
													->where('responsable', $request->usuario)
													->where('modulo', $menu->id)
													->get()
													->toArray()
							)
							->orderBy('codarea', 'asc')
							->get()
							->pluck('codarea')
							->toArray();
							
			}

			$str_areas = implode(",", $areas);

			if (count($areas) == 0) {
				
				$str_areas = 'null';
			}

			if ($request->module === 'admin') {
				
				$sql = "SELECT *
						FROM iso_documentos_revision
						WHERE baja = '0'
						AND deleted_at IS NULL
						AND codarea IN ($str_areas)
						AND PARENT_DOCUMENTOID IS NULL
						ORDER BY documentoid DESC";
				
			}elseif ($request->module === 'revision_forma') {
				
				$sql = "SELECT *
						FROM iso_documentos_revision
						WHERE (
							tipodocumentoid IN (
								SELECT tipodocumentoid
								FROM iso_tipos_documentos
								WHERE generar_qr IS NOT NULL
							)
							AND estadoid = 4
							AND deleted_at IS NULL
							AND baja = '0'
							AND codarea IN ($str_areas)
						)
						OR (
							tipodocumentoid IN (
								SELECT tipodocumentoid
								FROM iso_tipos_documentos
								WHERE generar_qr IS NULL
							)
							AND estadoid IN (1, 2)
							AND deleted_at IS NULL
							AND baja = '0'
							AND codarea IN ($str_areas)
						)";

			}else{

				$sql = "SELECT *
						FROM iso_documentos_revision
						WHERE tipodocumentoid IN (
							SELECT tipodocumentoid
							FROM iso_tipos_documentos
							WHERE generar_qr IS NOT NULL
						)
						AND estadoid IN (1,2)
						AND deleted_at IS NULL
						AND baja = '0'
						AND codarea IN ($str_areas)";

			}

			$documentos_revision = DB::connection('portales')->select($sql);

			foreach ($documentos_revision as &$documento) {
				
				// Validar si no tiene versiones

				$versiones = DocumentoRevision::where('parent_documentoid', $documento->documentoid)->orderBy('documentoid', 'desc')->get();

				if ($versiones->count() > 0) {
					
					$child_document = $versiones[0];

					$estado = EstadoDocumento::find($child_document->estadoid);

				}else{

					$estado = EstadoDocumento::find($documento->estadoid);

				}

				$documento->estado = $estado;
				$documento->versiones = $versiones;

				$tipo_documento = TipoDocumento::find($documento->tipodocumentoid);

				$documento->tipo_documento = $tipo_documento ? $tipo_documento->nombre : null;

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