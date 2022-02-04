<?php 

	namespace App;

	use Illuminate\Database\Eloquent\Model;

	class DocumentoRevision extends Model{
		
		protected $table = 'ISO_DOCUMENTOS_REVISION';

		protected $primaryKey = 'DOCUMENTOID';

		public $timestamps = false;

		protected $connection = 'portales';
		
	}

?>