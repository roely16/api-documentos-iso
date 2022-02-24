<?php 

	namespace App;

	use Illuminate\Database\Eloquent\Model;

	class Permiso extends Model{
		
		protected $table = 'ISO_DOCUMENTOS_PERMISOS';

		protected $primaryKey = null;

		public $incrementing = false;
		
		public $timestamps = false;

		protected $connection = 'portales';
		
	}

?>