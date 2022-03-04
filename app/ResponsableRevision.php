<?php 

	namespace App;

	use Illuminate\Database\Eloquent\Model;

	class ResponsableRevision extends Model{
		
		protected $table = 'ISO_DOCUMENTOS_RESP_REVISION';

		protected $primaryKey = null;

		public $timestamps = false;

		protected $connection = 'portales';
		
	}

?>