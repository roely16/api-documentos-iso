<?php 

	namespace App;

	use Illuminate\Database\Eloquent\Model;

	class Empleado extends Model{
		
		protected $table = 'RH_EMPLEADOS';

		protected $primaryKey = 'NIT';

		public $timestamps = false;

		protected $connection = 'rrhh';
		
	}

?>