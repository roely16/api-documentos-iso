<?php 

	namespace App;

	use Illuminate\Database\Eloquent\Model;

	class EstadoDocumento extends Model{
		
		protected $table = 'ISO_ESTADOS_DOCUMENTOS';

		protected $primaryKey = 'ESTADOID';

		public $timestamps = false;

		protected $connection = 'portales';
		
	}

?>