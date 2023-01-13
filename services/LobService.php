<?php

class LobService
{

   protected $db = null;
   protected $api_key = null;
   private $client = null;

   public function __construct($db)
   {
      $this->db = $db;
   }

   public function getClient($request) {

      $query = "SELECT * FROM user_mail_settings WHERE user_id = '" . $this->db->escape($request['user_id']) . "'";

      $lob = $this->db->GetDataFromDb($query);

      $this->client = new \Lob\Lob($lob['lob_api_key']);

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

      $this->deletePDFFiles();

      $uploaded_files = (scandir('uploaded_files')) ? scandir('uploaded_files') : [];

      if ($uploaded_files) {

         foreach ($uploaded_files as $key => $value) {

            $file = 'uploaded_files/'.$value;

            if (is_file($file)) {
               if (time() - filemtime($file) >= 60 * 60 * 24 * 3) { // 3 days
                 unlink($file);
               }
            }
         }
      }

   }

   public function deletePDFFiles() {

      $pdf_files = (scandir('files/c2m_pdf/')) ? scandir('files/c2m_pdf/') : [];
      $deleted_files = [];

      if ($pdf_files) {

         foreach ($pdf_files as $key => $value) {

            $file = 'files/c2m_pdf/'.$value;

            if (is_file($file)) {               
               if (time() - filemtime($file) >= 60 * 60 * 24 * 14) { // 14 days                  
                  unlink($file);
                  $deleted_files[] = $file;
               }
            }
         }
      }

      return ['success'=>true, 'msg'=>'PDF Files deleted', 'data' => $deleted_files];
   }

   public function uploadFiles($request) {

      $file = [];

      if (isset($_FILES['attachments'])) {

         $file = $_FILES['attachments'];

         $file['new_name'] = uniqid()."-".$file['name'][0];

         $tmp = isset($file['tmp_name'][0]) ? $file['tmp_name'][0] : null;

         // return false if file is greater than 1MB
         if (filesize($tmp) > 1000000) {
            return ['success'=>false, 'msg'=>'Max allowed size is: 1MB', 'data' => $file];
         }

         if ($tmp && move_uploaded_file($tmp, 'uploaded_files/'.$file['new_name'])) {
            return ['success'=>true, 'msg'=>'File saved', 'data' => $file, 'filename' => $file['new_name']];
         }
         
      }

      return ['success'=>false, 'msg'=>'An error occured when saving file', 'data' => $file];

   }

   public function handleLetter($request) {

      // Test return for RingCentral API after Lob Success
      // return [ 
      //    'success'      => true, 
      //    'msg'          => 'Test callback', 
      //    'data'         => [
      //       'from' => [
      //          'name' => 'Test User'
      //       ]
      //    ], 
      //    'file_url'     => 'test.url', 
      //    'fax'          => isset($request['recipient']['fax']) ? $request['recipient']['fax'] : null, 
      //    'file_content' => $request['pdf']
      // ];

      if (!$request['address']['state']) {
            return [
            'success'      => false, 
            'msg'          => 'Some parameters are missing', 
            'description'  => 'address_state is required',
            'data' => 
            [
               'from' => [
                  'name' => $request['address']['firstName']." ".$request['address']['lastName'],
               ]
            ],
            'request' => $request
         ];
      }

      if ($request['address']['state'] == 'U.S. Virgin Islands') {
         $request['address']['state'] = 'Virgin Islands';
      }

      $file_content = null;      

      if (isset($request['options']['restrict_identity_documents']) && $request['options']['restrict_identity_documents'] == 'true') {
         $request['pdf'] = $this->removeImages($request['pdf']);
      }

      if (isset($request['options']['upload_additional_documentation']) && $request['options']['upload_additional_documentation'] == 'true' && isset($request['uploaded_files'])) {
         $request['pdf'] = $this->prependImages($request['pdf'], $request['uploaded_files']);
      }

      $request['pdf'] = str_replace('<img style="max-width: 573px;width: 70%; height:auto;"', '<img style="max-width: 573px;width: fit-content; height:auto;"', $request['pdf']);

      // We will save the file in our server and provide them the link instead of sending them the whole HTML code.
      // Reason: Lob won't accept char length >= 10000
      // if (strlen($request['pdf']) >= 10000) {
      // }
      /*UPDATE Aug 3, 2021: Always save the file in our server*/
      $filename = $request['address']['firstName']."_".$request['address']['lastName']."-".date('Y-m-d')."-".substr( md5(rand()), 0, 7)."-".$request['transaction_code'];

      $this->savePDFDocument($request['pdf'], $filename, true, $request['options']['restrict_identity_documents']);

      $file_content = $this->base_url()."/files/c2m_pdf/".$filename.".pdf";

      $this->db->ExecuteQuery("INSERT INTO files_log (filename,createdAt) VALUES ('$filename',NOW())");
         
      if (!$file_content) {
         $file_content = $request['pdf'];
      }
      
      $user_mail_settings = $this->getClient($request);

      try {

         $to_address = [
            'to[name]'              => $request['recipient']['firstName']." ".$request['recipient']['lastName'],
            'to[address_line1]'     => $request['recipient']['address'],
            'to[address_line2]'     => '',
            'to[address_city]'      => $request['recipient']['city'],
            'to[address_zip]'       => $request['recipient']['zip'],
            'to[address_state]'     => ($request['recipient']['state']) ? $request['recipient']['state'] : 'Not specified',
         ];

         $from_address = [
            'from[name]'            => $request['address']['firstName']." ".$request['address']['lastName'],
            'from[address_line1]'   => $request['address']['address'],
            'from[address_line2]'   => '',
            'from[address_city]'    => $request['address']['city'],
            'from[address_zip]'     => $request['address']['zip'],
            'from[address_state]'   => ($request['address']['state']) ? $request['address']['state'] : 'Not specified'
         ];

         $letter_data = [
            'file'        => $file_content,
            'description' => 'Sending letter via Lob',
            'color'       => false,
            'double_sided'=> true,
            'address_placement' => 'insert_blank_page',
            'return_envelope'   => false,
            'mail_type'   => (isset($request['options']['mail_type'])) ? $request['options']['mail_type'] : 'usps_standard',
         ];

         $fax = isset($request['recipient']['fax']) ? $request['recipient']['fax'] : null;

         $letter_data = array_merge_recursive($letter_data, $to_address, $from_address);

         if (isset($request['options']['letter_type']) && $request['options']['letter_type'] == 'certified') {

            $letter_data['mail_type'] = 'usps_first_class';

            if (isset($request['options']['with_certified_return_receipt']) && $request['options']['with_certified_return_receipt'] == 'certified_return_receipt_yes') {              
               $letter_data['extra_service'] = 'certified_return_receipt';
            }else{
               $letter_data['extra_service'] = 'certified';
            }

         }

         $letter = $this->client->letters()->create($letter_data);

         $success = false;
         if ($letter) {
            $success = true;
         }

         $query = "INSERT INTO mail_logs (transaction_code, mail_system, user_id, transaction_name, success, log_msg) VALUES 
         ('" . $this->db->escape($request['transaction_code']) . "', 'lob','" . $this->db->escape($request['user_id']) . "', 'Create letters', '" . $success ."', '" . json_encode($letter) . "')";
         $this->db->ExecuteQuery($query);

         if ($success) {

            $file_pdf = $request['pdf'];

            return ['success'=>true, 'msg'=>'Letter successfully created', 'data' => $letter, 'file_url' => $file_content, 'fax' => $fax, 'file_content' => $file_pdf];
         }

         return ['success'=>false, 'msg'=> 'An error occured', 'data' => $letter, 'file_url' => $file_content, 'fax' => $fax, 'file_content' => $file_pdf];

      } catch (Exception $e) {

         return [
            'success'=>false,
            'description'=> $e->getMessage(),
            "data" => array(
            "from" => $from_address, "to" => $to_address),
            "request" => $request
         ];
      }

   }

   public function removeImages($pdf_content) {

      $content = $pdf_content;

      $content = preg_replace('/<img style="max-width: 573px;width: 70%; height:auto;"[^>]+>/', '', $pdf_content);

      return $content;

   }

   public function prependImages($pdf_content, $images) {

      sort($images);

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
            $address_data[0] = isset($address_data[0]) ? strip_tags($address_data[0]) : null;
            $address_data[1] = isset($address_data[1]) ? strip_tags($address_data[1]) : null;
            $address_data[2] = isset($address_data[2]) ? strip_tags($address_data[2]) : null;
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

         $data['state'] = isset($address[0]) ? $address[0] : null;
         $data['zip'] = isset($address[1]) ? $address[1] : null;

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