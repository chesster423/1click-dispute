<?php

/**
 * 
 */
class SendGridService
{

	protected $db = null;
	protected $sendgrid_key = null;

	public function __construct($db)
	{
		$this->db = $db;

		$query = "SELECT * FROM settings WHERE name = 'sendgrid'";

		$sendgrid = $this->db->GetDataFromDb($query);

		$this->sendgrid_key = $sendgrid['production_key'];

		if ($sendgrid['dev_active']) {
			$this->sendgrid_key = $sendgrid['development_key'];
		}

	}


	public function sendAnEmail($email, $subject, $body) {

	    $headers = array(
	        'Authorization: Bearer '.$this->sendgrid_key,
	        'Content-Type: application/json'
	    );

	    $data = array(
	        'personalizations' => array(array(
	            "to" => array(
	                array("email" => $email, "name" => "")
	            )
	        )),
	        "from" => array("email" => "info@30daycra.com", "name" => "30dayCRA"),
	        "subject" => $subject,
	        "content" => array(array(
	            "type" => "text/html",
	            "value" => $body
	        ))
	    );

	    $ch = curl_init();
	    curl_setopt($ch, CURLOPT_URL, "https://api.sendgrid.com/v3/mail/send");
	    curl_setopt($ch, CURLOPT_POST, 1);
	    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
	    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
	    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	    curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13");
	    curl_exec($ch);
	    curl_close($ch);

	    return true;
	}

	public function sendEmailWithAttachment($request) {

		try {

			$filepath = str_replace('controller', '', __DIR__).'/../files/pdf';
			$filename = $request['email'].".pdf";

			$body = "
			    Name: ".$request['name']."<br>
			    Email: ".$request['email']."<br><br>
			    
			    Please see the attached PDF document below.
			";

		    $email = new \SendGrid\Mail\Mail();
			$email->setFrom("info@30daycra.com");
			$email->setSubject("[YFS Academy Mailer] Signed Terms of Service");

			if ($_SERVER['REMOTE_ADDR']=='127.0.0.1' || $_SERVER['REMOTE_ADDR']=='::1') {
				$email->addTo("chesster423@gmail.com", "Developer Guy");
			}else{
	            if ($request['email'] == "senaidbacinovic@gmail.com"){
	            	$email->addTo("senaidbacinovic@gmail.com", "Developer Guy");
			    	$ccEmails = [
	                    "chesster423@gmail.com" => "Developer Guy 2"
	                ];
	                $email->addCcs($ccEmails);
	            }else {
	                $email->addTo("info@30daycra.com", "30dayCRA");
	                //$ccEmails = ["senaidbacinovic@gmail.com" => "Senaid B"];
	                //$email->addCcs($ccEmails);
	            }
			}

			$email->addContent("text/html", $body);

			$file_encoded = base64_encode(file_get_contents($filepath.'/'.$filename));
			$email->addAttachment(
			    $file_encoded,
			    "application/text",
			    $filename.".pdf",
			    "attachment"
			);

			$sendgrid = new \SendGrid($this->sendgrid_key);

		    $response = $sendgrid->send($email);
		    
		} catch (Exception $e) {
            return ['success'=>false, 'msg'=> 'Caught exception: '. $e->getMessage() ."\n"];
		}

		if ($response) {
			return ['success'=>true, 'msg'=>''];
		}

	    return ['success'=>false, 'msg'=>'Something went wrong on sending email'];
	}

	public function sendYFSEmailWithAttachment($request) {

		$response = false;

		try {

			$filepath = str_replace('controller', '', __DIR__).'/../files/yfs/pdf';
			$filename = $request['email']."-pdf";

			$body = "
			    Name: ".$request['full_name']."<br>
			    Email: ".$request['email']."<br><br>
			    
			    Please see the attached PDF document below.
			";

		    $email = new \SendGrid\Mail\Mail();
			$email->setFrom("info@30daycra.com");
			$email->setSubject($request['full_name'] . " [30dayCRA] Signed Terms of Service");

			if ($_SERVER['REMOTE_ADDR']=='127.0.0.1' || $_SERVER['REMOTE_ADDR']=='::1') {
				$email->addTo("chesster423@gmail.com", "Developer Guy");
			}else{
	            if ($request['email'] == "senaidbacinovic@gmail.com"){
	            	$email->addTo("senaidbacinovic@gmail.com", "Developer Guy");
			    	$ccEmails = [
	                    "chesster423@gmail.com" => "Developer Guy 2"
	                ];
	                $email->addCcs($ccEmails);
	            }else {
	                $email->addTo("info@30daycra.com", "30dayCRA");
	                $ccEmails = ["senaidbacinovic@gmail.com" => "Senaid B"];
	                $email->addCcs($ccEmails);
	            }
			}

			$email->addContent("text/html", $body);

			$file_encoded = base64_encode(file_get_contents($filepath.'/'.$filename));
			$email->addAttachment(
			    $file_encoded,
			    "application/text",
			    $filename.".pdf",
			    "attachment"
			);

			$this->sendgrid_key = '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';

			$sendgrid = new \SendGrid($this->sendgrid_key);

		    $response = $sendgrid->send($email);
		    
		} catch (Exception $e) {
            return ['success'=>false, 'msg'=> 'Caught exception: '. $e->getMessage() ."\n"];
		}

		if ($response) {
			return ['success'=>true, 'msg'=> $response];
		}

	    return ['success'=>false, 'msg'=>'Something went wrong on sending email'];
	}
}