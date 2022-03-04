<?php 

	namespace App;

	use Illuminate\Database\Eloquent\Model;

	class RolAlterno extends Model{
		
		protected $table = 'ISO_DOCUMENTOS_ROL_ALTER';

		protected $primaryKey = 'ID';

		public $timestamps = false;

		protected $connection = 'portales';
		
	}

?>