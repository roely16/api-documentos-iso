<?php 

	namespace App;

	use Illuminate\Database\Eloquent\Model;

	class Menu extends Model{

		protected $table = 'ISO_DOCUMENTOS_MENU_APP';

		protected $primaryKey = 'ID';

		public $timestamps = false;

		protected $connection = 'portales';
		
	}

?>