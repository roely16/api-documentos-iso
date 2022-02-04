<?php 

	namespace App;

	use Illuminate\Database\Eloquent\Model;

	class DocumentoQR extends Model{
		
		protected $table = 'ISO_DOCUMENTO_QR';

		protected $primaryKey = 'ID';

		protected $connection = 'portales';
		
	}

?>