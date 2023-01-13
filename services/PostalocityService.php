<?php

class PostalocityService
{

   protected $db = null;
   protected $api_url = [];

   protected $token = null;

   public function __construct($db)
   {
      $whitelist = ['127.0.0.1', '::1'];

      if (in_array($_SERVER['REMOTE_ADDR'], $whitelist)) {

         $this->api_url = [
            'login'              => 'https://dev.postalocity.com/user/login',
            'create_job'         => 'https://dev.postalocity.com/job',
            'get_upload_params'  => 'https://dev.postalocity.com/job/srcuploadparams',
            'add_souce'          => 'https://dev.postalocity.com/job/addsource',
            'job_start'          => 'https://dev.postalocity.com/job/run'      
         ];

      }else{

         $this->api_url = [
            'login'              => 'https://prod.postalocity.com/user/login',
            'create_job'         => 'https://prod.postalocity.com/job',
            'get_upload_params'  => 'https://prod.postalocity.com/job/srcuploadparams',
            'add_souce'          => 'https://prod.postalocity.com/job/addsource',
            'job_start'          => 'https://prod.postalocity.com/job/run'      
         ];

      }

      $this->db = $db;
   }

   public function getUserCredentials($user_id) {

      $query = "SELECT id, postalocity_username, postalocity_password FROM user_mail_settings WHERE user_id = '" . $this->db->escape($user_id) . "'";

      $data = $this->db->GetDataFromDb($query);

      return $data;

   }

   public function post($url, $data, $headers, $method = 'POST')
   {

      error_reporting(E_ALL);
      ini_set("display_errors", 1);

      $curl = curl_init();

      if ($method == 'PUT') {
         curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PUT");
      }else{
         curl_setopt($curl, CURLOPT_POST , 1);
      }

      curl_setopt_array($curl, array(
         CURLOPT_USERAGENT      => 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.17 (KHTML, like Gecko) Chrome/24.0.1312.52 Safari/537.17',
         CURLOPT_URL            => $url,
         CURLOPT_RETURNTRANSFER => 1,
         CURLOPT_POSTFIELDS     => $data,
         CURLOPT_SSL_VERIFYPEER => false,
         CURLOPT_SSL_VERIFYHOST => false,
         CURLOPT_AUTOREFERER    => true,
         CURLOPT_FOLLOWLOCATION => 1,           
      ));

      
      if (!empty($headers)) {
         curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
      }

      $response = curl_exec($curl);
      $errno = curl_errno($curl);

      if ($errno) {
         return "ERR: " . curl_getinfo($curl);
      }

      curl_close($curl);

      return $response;
   }

   public function curl_get($url, $headers)
   {

      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, $url);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
      curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

      $result = curl_exec($ch);


      $errno = curl_errno($ch);

      if ($errno) {
         return "ERR: " . curl_getinfo($ch);
      }

      curl_close($ch);

      return $result;

   }

   public function login($user_id) {

      try {

      
         /* API URL */
         $url = $this->api_url['login'];

         $whitelist = ['127.0.0.1', '::1'];

         if (in_array($_SERVER['REMOTE_ADDR'], $whitelist)) {

            $data = '
                {
                   "userName": "senaidbacinovic@gmail.com", 
                   "password": "4v22DT74pCk"
                }
            ';

         }else{

            $user = $this->getUserCredentials($user_id);

            $data = '
                {
                   "userName": "'. $user['postalocity_username'] .'", 
                   "password": "'. $user['postalocity_password'] .'"
                }
            ';

         }

         $headers = [
            "Content-Type: application/json",
            "Accept: application/json"
         ];

         $response = $this->post($url, $data, $headers);

         $data = json_decode($response, true);

         $this->token = $data['token'];

         return [
            'success' => true, 
            'data'    => $response,
         ];
        
      } catch (Exception $e) {

         return [
            'success' => false, 
            'msg'     =>'Expected HTTP Error: ' . $e->getMessage(), 
         ];

      }

   }

   public function createJob($request) {

      try {

         $token = $this->token;

         if (!$token) {
            return [
               'success' => false, 
               'msg'     => 'No token found', 
            ];
         }

         /* API URL */
         $url = $this->api_url['create_job'];

         $data = '
             {
                "paperSize": "Letter", 
                "mailingType": "Letter"
             }
         ';

         $headers = [
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: Bearer '. $token
         ];

         $response = $this->post($url, $data, $headers, 'PUT');

         $data = json_decode($response, true);

         if ($data['job']['id']) {
            return [
               'success' => true, 
               'data'    => $data,
            ];
         }

         return [
            'success' => false, 
            'msg'     => 'Failed to create job', 
            'response'=> $data,
         ];
        
      } catch (Exception $e) {

         return [
            'success' => false, 
            'msg'     => 'Expected HTTP Error: ' . $e->getMessage(), 
         ];

      }

   }

   public function getUploadParams($job_id){

      try {

         $token = $this->token;

         if (!$token) {
            return [
               'success' => false, 
               'msg'     => 'No token found', 
            ];
         }

         /* API URL */
         $url = $this->api_url['get_upload_params'];

         $data = '
             {
                "jobId": "' . $job_id . '"
             }
         ';

         $headers = [
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: Bearer '. $token
         ];

         $response = $this->post($url, $data, $headers);

         $data = json_decode($response, true);

         if ($data['type'] == 'SUCCESS') {
            return [
               'success' => true, 
               'data'    => $data,
            ];
         }

         return [
            'success' => false, 
            'msg'     => 'Failed to execute `GetUploadParams`', 
         ];

      } catch (Exception $e) {

         return [
            'success' => false, 
            'msg'     => 'Expected HTTP Error: ' . $e->getMessage(), 
         ];

      }
      
   }

   public function addSource($job_id, $request){

       try{

         $token = $this->token;

         if (!$token) {
            return [
               'success' => false, 
               'msg'     => 'No token found', 
            ];
         }

         /* API URL */
         $url = $this->api_url['add_souce'];

         $filename = $request['filename'];

         $name    = $request['recipient']['firstName'] . " " . $request['recipient']['lastName'];
         $address = $request['recipient']['address'];
         $city    = $request['recipient']['city'];
         $state   = ($request['recipient']['state']) ? $request['recipient']['state'] : 'Not specified';
         $zip     = $request['recipient']['zip'];
         $country = $request['recipient']['country'];

         $body = '
            {
                "jobId": '.$job_id.',
                "uploadUrl": "'.$filename.'",
                "deliveryAddress": {
                    "name": "'.$name.'",
                    "company2": "",
                    "company1": "",
                    "address2": "",
                    "address1": "'.$address.'",
                    "city": "'.$city.'",
                    "state": "'.$state.'",
                    "zip": "'.$zip.'",
                    "country": "'.$country.'",
                    "deliveryPoint": "",
                    "imb": "",
                    "type": 1,
                    "zone": 0,
                    "returnCode": 0,
                    "errorInfo": "",
                    "scheme5": "",
                    "aadc": ""
                }
            }
         ';

         $headers = [
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: Bearer '. $token
         ];

         $response = $this->post($url, $body, $headers);

         $data = json_decode($response, true);

         if ($data['type'] == 'SUCCESS') {
            return [
               'success' => true, 
               'data'    => $data,
            ];
         }

         return [
            'success' => false, 
            'msg'     => 'Failed to execute `Add Source`', 
            'response'=> $response,
         ];

      } catch (Exception $e) {

         return [
            'success' => false, 
            'msg'     => 'Expected HTTP Error: ' . $e->getMessage(), 
         ];

      }

   }

   public function startJob($job_id){

      try {

         $token = $this->token;

         if (!$token) {
            return [
               'success' => false, 
               'msg'     => 'No token found', 
            ];
         }

         /* API URL */
         $url = $this->api_url['job_start'];

         $body = ['id' => $job_id];
         $body = http_build_query($body);

         $headers = [
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: Bearer '. $token
         ];

         $url .= "?".$body;

         $response = $this->curl_get($url, $headers);

         $data = json_decode($response, true);

         if ($data['type'] == 'SUCCESS') {
            return [
               'success' => true, 
               'data'    => $data,
            ];
         }

         return [
            'success' => false, 
            'msg'     => 'Failed to execute start the job', 
            'response'=> $data,
         ];

      } catch (Exception $e) {

         return [
            'success' => false, 
            'msg'     => 'Expected HTTP Error: ' . $e->getMessage(), 
         ];

      }

   }


   public function handleLetter($request) {

      try {


         $from_name = $request['address']['firstName']." ".$request['address']['lastName'];

         // *********CHECK ADDRESS*********
         if (!$request['address']['state']) {
               return [
               'success'      => false, 
               'msg'          => 'Some parameters are missing', 
               'description'  => 'address_state is required',
               'data' => 
               [
                  'from' => [
                     'name' => $from_name,
                  ]
               ],
               'request' => $request
            ];
         }

         // *********CHECK USER ID*********
         if (!$request['user_id']) {
            return [
               'success' => false, 
               'msg'     => '`user_id` is missing', 
            ];
         }

         // *********UPDATE STATE*********
         if ($request['address']['state'] == 'U.S. Virgin Islands') {
            $request['address']['state'] = 'Virgin Islands';
         }

         // *********MODIFY THE REQUEST FILE********* 

         // Add `devtest` GET parameter to skip the slow process of creating PDFs.
         if (!isset($_GET['devtest'])) {
            if (isset($request['options']['restrict_identity_documents']) && $request['options']['restrict_identity_documents'] == 'true') {
               $request['pdf'] = $this->removeImages($request['pdf']);
            }

            if (isset($request['options']['upload_additional_documentation']) && $request['options']['upload_additional_documentation'] == 'true' && isset($request['uploaded_files'])) {
               $request['pdf'] = $this->prependImages($request['pdf'], $request['uploaded_files']);
            }

            $request['pdf'] = str_replace('<img style="max-width: 573px;width: 70%; height:auto;"', '<img style="max-width: 573px;width: fit-content; height:auto;"', $request['pdf']);

            $filename = $request['address']['firstName']."_".$request['address']['lastName']."-".date('Y-m-d')."-".substr( md5(rand()), 0, 7)."-".$request['transaction_code'];

            $this->savePDFDocument($request['pdf'], $filename, true, $request['options']['restrict_identity_documents']);

            $request['filename'] = $this->base_url()."/files/c2m_pdf/".$filename.".pdf";

            $file_content = $this->base_url()."/files/c2m_pdf/".$filename.".pdf";

            if (!$file_content) {
               $file_content = $request['pdf'];
            }
         }

         if (isset($_GET['devtest']) && $_GET['devtest'] == 1) {
            $request['filename'] = "https://file-examples-com.github.io/uploads/2017/10/file-sample_150kB.pdf";
         }


         /**********************************************************************/ 
         /* POSTALOCITY
         /**********************************************************************/ 

         $err_message = null;
         $job_id  = null;
         $user_id = $request['user_id'];
         $fax = isset($request['recipient']['fax']) ? $request['recipient']['fax'] : null;

         /**********************************/ 
         /* Step 1 - Login and get a token
         /**********************************/ 
         $this->login($user_id);

         /**********************************/ 
         /* Step 2 - Create a Job
         /**********************************/ 
         if ($this->token) {

            $job = $this->createJob($request);

            $job_id = $job['data']['job']['id'];

         }else{

            return [
               'success' => false, 
               'msg'     => "Invalid login credentials. Failed to create token", 
            ];

         }

         /**********************************/ 
         /* Step 3 - Add the PDF File and Recepient information
         /**********************************/ 
         if ($job['success']) {

            $source = $this->addSource($job_id, $request);

         }else{
            
            return [
               'success' => false, 
               'msg'     => "Failed to create job.", 
            ];

         }
         
         /**********************************/ 
         /* Step 4 - Start the Job.
         /**********************************/ 
         $start = null;
         if ($source['success']) {

            $start = $this->startJob($job_id);

            if ($start['success']) {

               $file_pdf = $request['pdf'];

               return [
                  'success'      => true, 
                  'msg'          => $start['data']['message'], 
                  'file_url'     => $file_content, 
                  'fax'          => $fax, 
                  'file_content' => $file_pdf,
                  'data' => [
                     'from' => [
                        'name' => $from_name
                     ]
                  ], 
               ];

            }else{

               return [
                  'success'      => false, 
                  'msg'          => "Failed to start job: ".json_encode($start), 
               ];

            }

         }else{

            return [
               'success' => false, 
               'msg'     => "Failed to add source: ".json_encode($source), 
            ];

         }
         
         return [
            'success' => false, 
            'msg'     => 'An error occured.', 
         ];
         
      } catch (Exception $e) {
         return $e->getMessage();
      }

   }

   public function deleteOldFiles() {
      //let's delete files older than 5 days
      $sql = $this->db->ExecuteQuery("SELECT id, filename FROM files_log WHERE createdAt < DATE_ADD(NOW(), INTERVAL -5 DAY) LIMIT 100");
      while($data = $sql->fetch_assoc()) {
         if (strpos($data['filename'], '.pdf') === false)
            $data['filename'] .= '.pdf';

         if (file_exists('/srv/app.30daycra.com/files/c2m_pdf/'.$data['filename']))
            unlink('/srv/app.30daycra.com/files/c2m_pdf/'.$data['filename']);

         $this->db->ExecuteQuery('DELETE FROM files_log WHERE id="'.$data['id'].'"');
      }

      $uploaded_files = (scandir('uploaded_files')) ? scandir('uploaded_files') : [];

      if ($uploaded_files) {

         foreach ($uploaded_files as $key => $value) {

            $file = 'uploaded_files/'.$value;

            if (is_file($file)) {
               if (time() - filemtime($file) >= 60 * 60 * 24 * 5) { // 5 days
                 unlink($file);
               }
            }
         }
      }

   }

   public function uploadFiles($request) {

      $file = [];

      if (isset($_FILES['attachments'])) {

         $file = $_FILES['attachments'];

         $file['new_name'] = uniqid()."-".$file['name'][0];

         $tmp = isset($file['tmp_name'][0]) ? $file['tmp_name'][0] : null;

         if ($tmp && move_uploaded_file($tmp, 'uploaded_files/'.$file['new_name'])) {
            return ['success'=>true, 'msg'=>'File saved', 'data' => $file, 'filename' => $file['new_name']];
         }
         
      }

      return ['success'=>false, 'msg'=>'An error occured when saving file', 'data' => $file];

   }

   public function removeImages($pdf_content) {

      $content = $pdf_content;

      $content = preg_replace('/<img style="max-width: 573px;width: 70%; height:auto;"[^>]+>/', '', $pdf_content);

      return $content;

   }

   public function prependImages($pdf_content, $images) {

      $imgs = '';
      $base_url = $_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'];

      $whitelist = array(
          '127.0.0.1',
          '::1'
      );

      $src = 'http://localhost/dominique-app/uploaded_files/';

      if(!in_array($_SERVER['REMOTE_ADDR'], $whitelist)){
         $src = "https://".str_replace('/action.php?entity=lob&action=send_letter', '', $base_url)."/uploaded_files/";
      }

      foreach ($images as $key => $value) {

         $img = $src.$value;

         $imgs .= '<div class="pageBreak"><img src="'.$img.'"  alt="'.$value.'" style="max-width: 573px;width: fit-content; height:650px;" /></div>';
         // $imgs .= '<div class="pageBreak" style="page-break-after:always; display: inline-block;"><img src="'.$img.'"  alt="'.$value.'" style="max-width: 573px;width: fit-content; height:auto;" /></div>';

      }

      $pdf_content = str_replace('<style type="text/css">', $imgs.'<style type="text/css">', $pdf_content);

      return $pdf_content;

   }

   public function getRecipientAddress($request) {

      $data = [];

      if (isset($request['pdf']) && (strpos(html_entity_decode($request['pdf']), '{{30daycra_start}}') !== false || strpos(html_entity_decode($request['pdf']), '<30daycra>') !== false)) {
         if (strpos(html_entity_decode($request['pdf']), '{{30daycra_start}}') !== false)
            $tags = ['{{30daycra_start}}', '{{30daycra_end}}'];
         else
            $tags = ['<30daycra>', '</30daycra>'];

         $pdf = html_entity_decode($request['pdf']);
         $recipient = explode($tags[0], $pdf);
         $recipient = explode($tags[1], $recipient[1]);
         $recipient = $recipient[0];
         $recipient = explode('<br />', $recipient);

         $startIndex = 1;

         if ($recipient[0] != '')
            $startIndex = 0;

         $address_data[0] = strip_tags($recipient[$startIndex]);
         $address_data[1] = strip_tags($recipient[($startIndex+1)]);
         $address_data[2] = strip_tags($recipient[($startIndex+2)]);
      } else {
         $reg = preg_match_all('/(<p>)(.*?)(<\/p>)/', $request['pdf'], $matches);

         if (isset($matches[2][1])) {
            $address_data = explode('<br />', $matches[2][1]);
            $address_data[0] = strip_tags($address_data[0]);
            $address_data[1] = strip_tags($address_data[1]);
            $address_data[2] = strip_tags($address_data[2]);
         }
      }

   // Getting the recipient's name
      if (isset($address_data[0])) {
         $name = explode(' ', $address_data[0]);

         $data['firstName'] = isset($name[0]) ? $name[0] : '';
         $data['lastName'] = isset($name[1]) ? str_replace($data['firstName'], '', $address_data[0]) : '';
      }

      $data['address'] = $address_data[1];

   // Getting the recipient's city, state, zip
      if (isset($address_data[2])) {
         $address = explode(', ', $address_data[2]);
         $data['city'] = $address[0];

         $address = explode(' ', $address[1]);

         $data['state'] = $address[0];
         $data['zip'] = $address[1];

         $data['country'] = 'United States';
      }

      if (preg_match('/Experian|TransUnion|Equifax/', $request['pdf'], $matches)) {

         if (isset($matches[0])) {
            $data['fax'] = $matches[0];
         }

      }

      return $data;

   }

   public function getUserAddresses($request) {

      $this->getClient($request);

      $params = ['limit' => 100];

      if (isset($request['after'])) {

         preg_match('/(after=)+(.+)/', $request['after'], $matches);

         if (isset($matches[2])) {
            $params['after'] = $matches[2];
         }

      }

      if (isset($request['before'])) {

         preg_match('/(before=)+(.+)/', $request['before'], $matches);

         if (isset($matches[2])) {
            $params['before'] = $matches[2];
         }
         
      }

      $data = $this->client->addresses()->all($params);

      return ['success'=>true, 'msg'=>'Address retrieved', 'data' => $data];

   }

   public function deleteAddress($request) {

      if (isset($request['address_id']) && isset($request['user_id'])) {

         $this->getClient($request);

         $address_id = $request['address_id'];

         $delete_address = $this->client->addresses()->delete($address_id);

         if ($delete_address) {
            return ['success'=>true, 'msg'=>'Address successfully deleted.', 'data' => $delete_address];
         }

         return ['success'=>false, 'msg'=>'Failed to delete address'];
      }

      return ['success'=>false, 'msg'=>'Some parameters are missing'];
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

      $html = preg_replace('/<style((.|\n|\r)*?)<\/style>/', '', $content);
      $html = preg_replace('/<input type=\"hidden".*>/', '', $html);
      $html = str_replace('style="page-break-after:always; display: inline-block;"', '', $html);
      $html = str_replace('style="max-width: 573px;width: fit-content; height:auto;"', 'style="max-width: 573px;width: fit-content; height: 650px;"', $html);

      $html = "<html><body>".$html."</body></html>";

      $pdf->writeHTMLCell(0, 0, '', '', $html, 0, 1, 0, true, '', true);

      $pdf->SetFillColor(0, 0, 0 );

      $pdf->Output($filepath.'files/c2m_pdf/'.$pdf_name.'.pdf', 'F');

      if (isset($signature_path)) {
         unlink($signature_path);
      }

   }

   public function base_url(){

      $request_uri = sprintf(
         "%s://%s%s",
         isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' ? 'https' : 'http',
         $_SERVER['SERVER_NAME'],
         $_SERVER['REQUEST_URI']
      );

      $base_url = preg_replace('/(\/action\.php.+action\=.+)/', '', $request_uri);

      return $base_url;

   }

}
