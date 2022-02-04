<?php 

	namespace App;

	use Illuminate\Database\Eloquent\Model;

	class Perfil extends Model{
		
		protected $table = 'RRHH_PERFIL';

		protected $primaryKey = 'ID';

		public $timestamps = false;

		protected $connection = 'rrhh';
		
	}

?>