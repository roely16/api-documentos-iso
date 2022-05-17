<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use DB;

use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\LabelAlignment;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Response\QrCodeResponse;

use Illuminate\Support\Facades\Storage;

class FixQRController extends Controller{

	public function fix_qr(){

		$codigos = DB::connection('portales')->select("	SELECT *
														FROM ISO_DOCUMENTO_QR
														WHERE ETIQUETA = 'elabora'
														AND (URL_QR NOT LIKE 'https://%' AND URL_QR NOT LIKE 'http://%')
														ORDER BY ID DESC");

		foreach ($codigos as &$codigo) {
			
			$new_url = 'https://udicat.muniguate.com/apis/api-documentos-iso/public/' . $codigo->url_qr;

			$codigo->new_url = $new_url;

			// $qrCode = new QrCode($new_url);
			// $qrCode->setSize(300);
			// $qrCode->setMargin(10); 
			// $qrCode->setWriterByName('png');

			// $qr_name = uniqid() . '.png';
			// $qrCode->writeFile(base_path('public') . '/new_qr/' . $qr_name);

			// // Actualizar cada uno de los registros
			// $result = DB::connection('portales')
			// 			->table('ISO_DOCUMENTO_QR')
			// 			->where('ID', $codigo->id)
			// 			->update([
			// 				'path_qr' => '/qr_codes/' . $qr_name,
			// 				'url_qr' => $new_url
			// 			]);

		}

		return response()->json($codigos);

	}

}