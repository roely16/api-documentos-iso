<?php 

	namespace App;

	use Illuminate\Database\Eloquent\Model;

	class EmpleadoPerfil extends Model{
		
		protected $table = 'RH_EMPLEADO_PERFIL';

		public $timestamps = false;

		protected $connection = 'rrhh';
		
	}

?>