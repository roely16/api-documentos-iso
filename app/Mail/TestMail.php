<?php 

	namespace App\Mail;

	use Illuminate\Bus\Queueable;
	use Illuminate\Mail\Mailable;
	use Illuminate\Queue\SerializesModels;

	class TestMail extends Mailable{

		protected $data;

		protected $view_name;

		public function __construct($view, $data){

			$this->view_name = $view;

			$this->data = $data;

		}

		public function build(){

			
			return $this->subject('Control de Documentos ISO')->markdown($this->view_name, ['header' => "Header"])->with($this->data);

		}

	}

?>