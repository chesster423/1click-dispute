<?php
include 'services/Click2MailSDK/c2mAPIRest.php';

/**
 * 
 */
class Click2MailService
{
	
	public $username = null;
	public $password = null;
	public $status = null;
	public $restUrl = null;

	public $response = [];
	protected $client = null;
	private $db = null;

	public function __construct($db)
	{
		$this->db = $db;
	}

	public function getClient($request) {

      	$query = "SELECT * FROM user_mail_settings WHERE user_id = '" . $this->db->escape($request['user_id']) . "'";

      	$c2m = $this->db->GetDataFromDb($query);

      	$this->username = $c2m['c2m_username'];
		$this->password = $c2m['c2m_password'];
		$this->restUrl = 'https://stage-rest.click2mail.com';
		$this->status = 'staging';
		
		$whitelist = array(
		    '127.0.0.1',
		    '::1'
		);

		if(!in_array($_SERVER['REMOTE_ADDR'], $whitelist)){
		    $this->restUrl = 'https://rest.click2mail.com';
			$this->status = 'live';
		}

      	$this->client = new initc2mAPIRest($this->username, $this->password, $this->status); 

   }


	public function createPDF($request){

		$this->getClient($request);

		$filename = $request['address']['firstName']."_".$request['address']['lastName']."-".date('Y-m-d')."-".substr( md5(rand()), 0, 7)."-".$request['transaction_code'];

		$pdf = str_replace('width: 70%;', '', $request['pdf']);

		$this->savePDFDocument($pdf, $filename);

		$file = "files/c2m_pdf/".$filename.".pdf";

		$document_class = $request['options']['letter_type'];

		$this->client->document_create($file, $filename, $document_class);

		$success = false;

		$data = [
			'document_id' => $this->client->documentId,
		];

		$log_msg = null;
		$save_success = 0;

		if ($data['document_id']) {
			$success = true;
			$save_success = 1;
		}

		$log_msg = json_encode($data);

		$query = "INSERT INTO mail_logs (transaction_code, mail_system, user_id, transaction_name, success, log_msg) VALUES 
		('" . $this->db->escape($request['transaction_code']) . "', 'c2m', '" . $this->db->escape($request['user_id']) . "', 'Create Document', '" . $save_success . "', '" . $log_msg . "')";

		$this->db->ExecuteQuery($query);

		return compact('success', 'data');

	}

	public function createAddressList($request) {

		$this->getClient($request);

		$this->client->addAddress($request);

		$this->client->addressList_create($this->client->createAddressList());

		$data = [
			'addresslist_id' => $this->client->addressListId,
		];

		$log_msg = null;
		$save_success = 0;

		if ($data['addresslist_id']) {
			$success = true;
			$save_success = 1;
		}

		$log_msg = json_encode($data);

		$query = "INSERT INTO mail_logs (transaction_code, mail_system, user_id, transaction_name, success, log_msg) VALUES 
		('" . $this->db->escape($request['transaction_code']) . "', 'c2m','" . $this->db->escape($request['user_id']) . "', 'Create Address List', '" . $save_success . "', '" . $log_msg . "')";

		$this->db->ExecuteQuery($query);

		return compact('success', 'data');
	}

	public function createJob($request) {

		$this->getClient($request);

		$data = array(
			"documentClass" => "Letter 8.5 x 11",
			"layout" => "Address on Separate Page",
			"productionTime"=> "Next Day",
			"envelope"=> "Best Fit",
			"color"=> "Black and White",
			"paperType"=> "White 24#",
			"mailClass" => "Standard",
			"printOption"=> "Printing Both sides",
			"documentId"=> $request['doc_id'],
			"addressId"=> $request['address_id'],
			'rtnName' => $request['address']['firstName']." ".$request['address']['lastName'],
			'rtnaddress1' => $request['address']['address'],
			'rtnCity' => $request['address']['city'],
			'rtnState' => $request['address']['state'],
			'rtnZip' => $request['address']['zip'],
		);

		$output =$this->client->rest_Call2($this->client->get_restUrl(). "/molpro/jobs/", $data, "POST");
		$this->client->jobId = (string) $output->id;

		$this->client->job_Submit();

		$output->name = $request['address']['firstName']." ".$request['address']['lastName'];

		$o = json_decode(json_encode($output), true);

		$log_msg = null;
		$save_success = 0;
		$success = false;
		$msg = 'Failed to save Job';

		if ($o['id'] != 0) {
			$success = true;
			$save_success = 1;
			$msg = 'Job saved successfully';
		}

		$log_msg = json_encode($data);

		$query = "INSERT INTO mail_logs (transaction_code, mail_system,user_id, transaction_name, success, log_msg) VALUES 
		('" . $this->db->escape($request['transaction_code']) . "', 'c2m','" . $this->db->escape($request['user_id']) . "', 'Create Job', '" . $save_success . "', '" . $log_msg . "')";

		$this->db->ExecuteQuery($query);

		return ['success'=>$success, 'msg'=>$msg, 'data'=> $o];
	}

	public function savePDFDocument($pdf_content, $pdf_name, $isLob = false) {

	    if ($isLob)
    		$pdf = new TCPDF('P', 'mm', 'ANSI_A');
	    else
    		$pdf = new TCPDF('P', 'mm', 'A4');

    	$pdf->SetTopMargin(10);
		$pdf->SetLeftMargin(20);

		$pdf->SetPrintHeader(false);
		$pdf->SetPrintFooter(false);

		$pdf->SetAutoPageBreak(true, PDF_MARGIN_BOTTOM + PDF_MARGIN_FOOTER + 5);

		$pdf->AddPage();

		$filepath = str_replace('services', '', __DIR__);

		// Get img_base64_encoded image and add @ at the beginning.
		$get_base64 = preg_match('/(<img src=\")(data:image\/png;base64.*)(\")(\salt\=)/', $pdf_content, $matches);

		$signature_path = null;		

		if (isset($matches[2])) {

			$filename = uniqid();

			$signature = file_get_contents($matches[2]);

			$signature_path = $filepath.'files/signatures/'.$filename;

			file_put_contents($signature_path, $signature);

			$pdf_content = str_replace($matches[2], $signature_path, $pdf_content);
		}
		

		$content = $pdf_content;

		$pdf->writeHTML("<html><body>".$content."</body></html>", 1, 0, 1, 0);

		$pdf->SetFillColor(0, 0, 0 );

		$pdf->Output($filepath.'files/c2m_pdf/'.$pdf_name.'.pdf', 'F');

		unlink($signature_path);

	}

	public function getRecipientAddress($request) {

		$data = [];

		$reg = preg_match_all('/(<p>)(.*?)(<\/p>)/', $request['pdf'], $matches);

		if (isset($matches[2][1])) {
			$address_data = explode('<br />', $matches[2][1]);

			// Getting the recipient's name
			if (isset($address_data[0])) {
				$name = explode(' ', $address_data[0]);

				$data['firstName'] = isset($name[0]) ? $name[0] : '';
				$data['lastName'] = isset($name[ count($name) ]) ? $name[ count($name) ] : '';

			}

			$data['address'] = $address_data[1];
			// Getting the recipient's city, state, zip
			if (isset($address_data[2])) {

				$address = explode(' ', $address_data[2]);
				$data['city'] = str_replace(',', '', $address[1]);
				$data['state'] = $address[2];
				$data['zip'] = $address[3];
				$data['country'] = 'United States';
			}

		}

		return $data;

	}

}