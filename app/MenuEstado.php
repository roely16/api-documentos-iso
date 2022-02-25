<?php 

	namespace App;

	use Illuminate\Database\Eloquent\Model;

	class MenuEstado extends Model{
		
		protected $table = 'ISO_DOCUMENTOS_MENU_ESTADO';

		protected $primaryKey = null;

		public $timestamps = false;

		protected $connection = 'portales';
		
	}

?>