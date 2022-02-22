<?php 

	namespace App\Http\Controllers;

	use Illuminate\Http\Request;

	use App\Mail\TestMail;
	use Illuminate\Support\Facades\Mail;

	use App\Jobs\MailJob;

	class MailController extends Controller{
		
		public function test_mail(){

			$data = [
				[
					"to" => "gerson.roely@gmail.com",
					"view" => "mails.test",
					"data" => [
						"name" => "Herson Chur"
					]
				]
			];

			dispatch(new MailJob($data));

		}
	
	}

?>