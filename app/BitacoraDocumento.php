<?php 

	namespace App;

	use Illuminate\Database\Eloquent\Model;

	class BitacoraDocumento extends Model{
		
		protected $table = 'ISO_BITACORA_DOCUMENTOS';

		protected $primaryKey = 'BITACORAID';

		public $timestamps = false;

		protected $connection = 'portales';
		
	}

?>