<?php 

	namespace App;

	use Illuminate\Database\Eloquent\Model;

	class ISOSeccion extends Model{
		
		protected $table = 'ISO_SECCIONES';

		protected $primaryKey = 'SECCIONID';

		public $timestamps = false;

		protected $connection = 'portales';
		
	}

?>