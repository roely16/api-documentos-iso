<?php 

	namespace App\Jobs;

	use App\Mail\TestMail;
	use Illuminate\Support\Facades\Mail;

	class MailJob extends Job{
		

		protected $data;

		public function __construct($data){
			
			$this->data = $data;

		}

		public function handle(){
			
			foreach ($this->data as $item) {
				
				$item = (object) $item;

				Mail::to($item->to)->send(new TestMail($item->view, $item->data));

			}

		}
	}

?>