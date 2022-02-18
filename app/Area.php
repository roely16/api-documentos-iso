<?php 

	namespace App;

	use Illuminate\Database\Eloquent\Model;

	class Area extends Model{
		
		protected $table = 'RH_AREAS';

		protected $primaryKey = 'CODAREA';

		public $timestamps = false;

		protected $connection = 'rrhh';
		
	}

?>