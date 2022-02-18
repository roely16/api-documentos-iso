<?php 

	namespace App\Http\Controllers;

	use Illuminate\Http\Request;

	use App\DocumentoRevision;
	use App\Empleado;
	use App\EstadoDocumento;
	use App\TipoDocumento;
	use App\BitacoraDocumento;
	use App\BitacoraAdjunto;

	use DB;
	use Carbon\Carbon;

	class DocumentVersionController extends Controller{
		
		public function get_versions(Request $request){

			$versiones = DocumentoRevision
						::select(DB::raw("
											documentoid, 
											codigo, 
											nombre, 
											version, 
											estadoid, 
											usuarioid, 
											elabora,
											tipodocumentoid,
											TO_CHAR(created_at, 'DD/MM/YYYY HH24:MI:SS') as created_at"
										))
						->where('documentoid', $request->id)
						->orWhere('parent_documentoid', $request->id)
						->orderBy('documentoid', 'desc')
						->get();

			$en_revision = 0;

			foreach ($versiones as &$version) {
				
				if ($version->estadoid == 1 || $version->estadoid == 2) {
					
					$en_revision++;

				}

				$empleado = Empleado::where('usuario', $version->elabora)->first();

				$version->elaborado_por = $empleado->nombre . ' ' . $empleado->apellido;

				$estado = EstadoDocumento::find($version->estadoid);

				$version->estado = $estado;

				$tipo_documento = TipoDocumento::find($version->tipodocumentoid);

				$version->tipo_documento = $tipo_documento->nombre;

				// Estados disponibles
				$estados = EstadoDocumento::where('estadoid', '!=', $version->estadoid)->orderBy('estadoid', 'asc')->get();

				$version->estados = $estados;

			}

			// Items
			$items = $versiones;

			// Headers
			$headers = [
				[
					"value" => "documentoid",
					"text" => "ID",
					"sortable" => false,
					"width" => "10%"
				],
				[
					"value" => "version",
					"text" => "Versión",
					"sortable" => false,
					"width" => "10%"
				],
				[
					"value" => "elaborado_por",
					"text" => "Elaborado Por",
					"sortable" => false,
					"width" => "30%"
				],
				[
					"value" => "estado",
					"text" => "Estado",
					"sortable" => false,
					"width" => "20%",
					"custom" => true
				],
				[
					"value" => "created_at",
					"text" => "Fecha",
					"sortable" => false,
					"width" => "20%"
				],
				[
					"value" => "action",
					"text" => "Acción",
					"align" => "right",
					"sortable" => false,
					"width" => "10%",
					"custom" => true
				]
			];
			
			$response = [
				"items" => $items,
				"headers" => $headers,
				"allow_create_version" => $en_revision > 0 ? false : true
			];

			return response()->json($response);

		}

		public function change_state(Request $request){

			$data = json_decode($request->data);

			$bitacora = new BitacoraDocumento();
			$bitacora->fecha = Carbon::now();
			$bitacora->usuarioid = $data->usuario;
			$bitacora->documentoid = $data->id;
			$bitacora->text_comentario = $data->comentario;

			// Validar si existe un cambio de estado
			if ($data->cambio_estado) {
				
				$bitacora->estado_anterior = $data->estado_anterior;
				$bitacora->estado_actual = $data->cambio_estado;

				$result = DB::connection('portales')->table('ISO_DOCUMENTOS_REVISION')
								->where('DOCUMENTOID', $data->id)
								->update([
									'estadoid' => $data->cambio_estado,
									'updated_at' => Carbon::now()
								]);

				// Buscar el estado y verificar si debe de ejecutar una función especifica 
				$estado = EstadoDocumento::find($data->cambio_estado);

				if ($estado->function_cr) {
					
					$data_ = (object) [
						"id_documento" => $data->id,
						"usuario" => $data->usuario
					];

					$datos = app("App\Http\Controllers" . $estado->controller)->{$estado->function_cr}($data_);

				}

			}

			$bitacora->save();

			for ($i=1; $i <= $data->number_files ; $i++) { 
				
				$filename = uniqid() . '_' . $request->file('file'.$i)->getClientOriginalName();

				$request->file('file'.$i)->move('documentos', $filename);

				$bitacora_adjunto = new BitacoraAdjunto();
				$bitacora_adjunto->bitacoraid = $bitacora->BITACORAID;
				$bitacora_adjunto->path = 'documentos/' . $filename;
				$bitacora_adjunto->nombre = $request->file('file'.$i)->getClientOriginalName();
				$bitacora_adjunto->save();

			}

			return response()->json($data);

		}

		public function get_bitacora(Request $request){

			$bitacora = BitacoraDocumento
							::select(DB::raw("
								bitacoraid,
								TO_CHAR(fecha, 'DD/MM/YYYY HH24:MI:SS') as fecha,
								usuarioid,
								text_comentario,
								estado_anterior,
								estado_actual
							"))
							->where('documentoid', $request->id)
							->orderBy('bitacoraid', 'asc')
							->get();

			// Buscar si se tienen documentos adjuntos
			foreach ($bitacora as &$item) {
				
				$empleado = Empleado::where('usuario', $item->usuarioid)->first();

				$item->registrado_por = $empleado->nombre . ' ' . $empleado->apellido;

				$adjuntos = BitacoraAdjunto::where('bitacoraid', $item->bitacoraid)->get();

				$estado_anterior = EstadoDocumento::find($item->estado_anterior);

				$item->estado_anterior = $estado_anterior;

				$estado_actual = EstadoDocumento::find($item->estado_actual);

				$item->estado_actual = $estado_actual;

				foreach ($adjuntos as &$adjunto) {
					
					$adjunto->url_download = 'http://' . $_SERVER['HTTP_HOST'] . '/apis/api-documentos-iso/public/download_attachment/' . $adjunto->id;

				}

				$item->adjuntos = $adjuntos;

			}

			return response()->json($bitacora);

		}

		public function download_attachment($id){

			$adjunto = BitacoraAdjunto::find($id);

			return response()->download($adjunto->path, $adjunto->nombre);

		}

	}

?>