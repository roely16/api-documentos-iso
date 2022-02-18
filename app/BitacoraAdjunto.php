<?php 

	namespace App;

	use Illuminate\Database\Eloquent\Model;

	class BitacoraAdjunto extends Model{
		
		protected $table = 'ISO_BITACORA_ADJUNTO';

		protected $primaryKey = 'ID';

		public $timestamps = false;

		protected $connection = 'portales';
		
	}

?>