<?php 

	namespace App;

	use Illuminate\Database\Eloquent\Model;

	class ImagenPerfil extends Model{
		
		protected $table = 'RH_RUTA_PDF';

		protected $primaryKey = 'NIT';

		public $timestamps = false;

		protected $connection = 'rrhh';
		
	}

?>