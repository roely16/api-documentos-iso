<?php 

	namespace App;

	use Illuminate\Database\Eloquent\Model;

	class TipoDocumento extends Model{
		
		protected $table = 'ISO_TIPOS_DOCUMENTOS';

		protected $primaryKey = 'TIPODOCUMENTOID';

		public $timestamps = false;

		protected $connection = 'portales';
		
	}

?>