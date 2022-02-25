<?php 

	namespace App;

	use Illuminate\Database\Eloquent\Model;

	class DocumentoPortal extends Model{
		
		protected $table = 'ISO_DOCUMENTOS';

		protected $primaryKey = 'DOCUMENTOID';

		public $timestamps = false;

		protected $connection = 'portales';
		
	}

?>