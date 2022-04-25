<?php 

	namespace App\Http\Controllers;

	use Illuminate\Http\Request;

	use App\DocumentoRevision;
	use App\TipoDocumento;
	use App\EstadoDocumento;
	use App\Empleado;
	use App\Area;
	use App\DocumentoQR;
	use App\Perfil;
	use App\RolAlterno;
	use App\DocumentoPortal;

	use Carbon\Carbon;

	use DB;

	use App\Http\Controllers\UploadDocumentController;

	class DetailDocumentController extends Controller{

		public function get_detail(Request $request){

			$documento_revision = DocumentoRevision
									::select(DB::raw('
										codigo as "código",
										nombre,
										codarea as "sección",
										tipodocumentoid as "tipo de documento", 
										comentarios
									'))
									->where('documentoid', $request->id)
									->first();			

			$area = Area::find($documento_revision->{'sección'});
			$documento_revision->{'sección'} = $area->descripcion;

			$tipo_documento = TipoDocumento::find($documento_revision->{'tipo de documento'});
			$documento_revision->{'tipo de documento'} = $tipo_documento->nombre;

			// Buscar el documento
			$documento = DocumentoRevision::find($request->id);

			if (!$tipo_documento->generar_qr) {

				$ssl = isset($_SERVER['HTTPS']) ? 'https://' : 'http://';

				$response = [
					"documento" => $documento_revision,
					"full_document" => $documento,
					"pdf_path" => $ssl . $_SERVER['HTTP_HOST'] . '/apis/api-documentos-iso/public/' . $documento->documento,
				];

				return response()->json($response, 200);

			}

			// Generar nuevamente el pdf con los qr 
			$upload_controller = new UploadDocumentController();

			// Buscar la información de los QR generados previamente
			$codigos_qr = DocumentoQR::where('id_documento', $request->id)->orderBy('id', 'asc')->get();

			// Si el documento es el primero y no se requiere preview en el visor
			if (!$documento->parent_documentoid && !$request->preview) {

				$codigos_qr = DocumentoQR::where('id_documento', $documento->documentoid)->orderBy('id', 'asc')->get();

			}

			$qr = [];

			foreach ($codigos_qr as $codigo) {
				
				$nombre_rol = null;

				if ($codigo->path_qr) {
					
					$empleado = Empleado::where('usuario', $codigo->responsable_firma)->first();

					if ($codigo->rol_alternativo) {
						
						$rol_alternativo = RolAlterno::find($codigo->rol_alternativo);
						$nombre_rol = $rol_alternativo->rol;

					}else{

						$perfil = Perfil::find($codigo->rol_responsable);
						$nombre_rol = $perfil->nombre;
					}
					
				}

				$temp = [
					"tag" => $codigo->etiqueta,
					"label" => ucfirst($codigo->etiqueta),
					"qr" => $codigo->path_qr ? true : false,
					"url" => $codigo->url_qr,
					"responsable" => $codigo->path_qr ? $empleado->nombre . ' ' . $empleado->apellido : null,
					"rol" => $codigo->path_qr ? $nombre_rol : null,
					"qr_path" => $codigo->path_qr,
					"nit" => $codigo->path_qr ? $empleado->nit : null,
					"usuario" => $codigo->responsable_firma,
					"perfil" => $codigo->rol_responsable
				];

				$qr [] = $temp;

			}

			$output_path = $request->pub_path ? $request->pub_path : 'temp_files/' . uniqid() .'_preview.pdf';

			$data = (object) [
				"ajustes" => [
					"posicion_vertical" => $documento->posicion_vertical,
					"margen_horizontal" => $documento->margen_horizontal,
				],
				"file_path" => $documento->documento,
				"output_path" => $output_path,
				"qr" => $qr
			];

			$result = $upload_controller->create_pdf($data);

			$ssl = isset($_SERVER['HTTPS']) ? 'https://' : 'http://';

			$response = [
				"documento" => $documento_revision,
				"full_document" => $documento,
				"pdf_path" => $ssl . $_SERVER['HTTP_HOST'] . '/apis/api-documentos-iso/public/' . $output_path . '#toolbar=0&navpanes=0&scrollbar=0',
				"error_pdf" => $result["status"] == 100 ? true : false
			];

			return response()->json($response, 200);

		}

		public function get_detail_edit(Request $request){

			$documento_revision = DocumentoRevision::find($request->id);

			//Separar el código del documento para su edición 
			$arr_codigo = explode('-', $documento_revision->codigo);

			$documento_revision->codigo_tipo = $arr_codigo[0];
			$documento_revision->codigo_seccion = $arr_codigo[1];
			$documento_revision->codigo_numero = $arr_codigo[2];

			return response()->json($documento_revision);

		}

		public function update_detail_info(Request $request){

			try {

				// Formar el nuevo código
				$codigo = $request->codigo_tipo . '-' . $request->codigo_seccion . '-' . $request->codigo_numero;

				// Actualizar los documentos que son para revisión

				$result = DB::connection('portales')->table('ISO_DOCUMENTOS_REVISION')
						->where('DOCUMENTOID', $request->documentoid)
						->orWhere('PARENT_DOCUMENTOID', $request->documentoid)
						->update([
							'nombre' => $request->nombre,
							'comentarios' => $request->comentarios,
							'codigo' => $codigo
						]);

				// Formar el nuevo nombre que tendra en el portal
				$documento = DocumentoRevision::find($request->documentoid);

				$nombre_portal = $documento->codigo . ' ' . $documento->nombre;

				// Actualizar en la tabla de documentos publicados en el portal
				$result = DocumentoPortal::where('codigo', $request->codigo)
							->where('deleted_at', null)
							->update([
								'nombre' => $nombre_portal,
								'codigo' => $documento->codigo
							]);		

				return response()->json($result, 200);

			} catch (\Throwable $th) {
				
				return response()->json($th->getMessage, 400);

			}

		}

		public function delete_document(Request $request){

			$result = DB::connection('portales')
						->table('iso_documentos_revision')
						->where('documentoid', $request->id)
						->orWhere('parent_documentoid', $request->id)
						->update([
							"deleted_at" => Carbon::now()
						]);

			$response = [
				"deleted" => $result
			];

			return response()->json($response);

		}

	}

?>