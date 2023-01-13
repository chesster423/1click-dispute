<?php
use RingCentral\SDK\Http\HttpException;
use RingCentral\SDK\Http\ApiResponse;
use RingCentral\SDK\SDK;

class RingCentralService
{
   protected $db = null;

   public function __construct($db)
   {
      $this->db = $db;
   }

   public function getUserRCSettings($user_id) {

      $id = $this->db->escape($user_id);

      $query = "SELECT 
      transunion_faxnumber,
      equifax_faxnumber,
      experian_faxnumber  
      FROM user_mail_settings WHERE user_id = '" . $id . "'";

      $data = $this->db->GetDataFromDb($query);

      return $data;

   }

   public function getUserOAuthToken($id) {

      $id = $this->db->escape($id);

      $query = "SELECT access_token FROM ringCentralTokens WHERE userId = '" . $id . "'";

      $data = $this->db->GetDataFromDb($query);

      $token = $data['access_token'];

      return $token;

   }

   public function sendFax($request) {

      $fax_number = null;

      try{

         if (!$request['fax'] || $request['fax'] == '') {
            return ['success'=>false, 'msg'=>'No fax found.'];
         }

         if (!$request['user_id'] || !$request['file']) {
            return ['success'=>false, 'msg'=>'`user_id` or `file` is missing'];
         }      

         $credentials = $this->getUserRCSettings($request['user_id']);

         // $request['file'] is a url ex. example.com/files/myfile.pdf
         $file = $request['file'];

         // $rcsdk = new SDK($credentials['ringcentral_id'], $credentials['ringcentral_secret'], $credentials['ringcentral_server_url'], 'Demo', '1.0.0');

         // $platform = $rcsdk->platform();

         // Authorize         
         // $auth = $platform->login($credentials['ringcentral_username'], 101, $credentials['ringcentral_password']);
         // $bearerToken = $auth->json()->access_token;

         // Send Fax *REMOVED 10-5-2021. Will use oauth token saved in the db.
         // Assign fax number based on what is in the letter(TransUnion, Equifax, Experian). 
         switch ($request['fax']) {
            case 'TransUnion':
               $fax_number = $credentials['transunion_faxnumber'];
               break;
            case 'Experian':
               $fax_number = $credentials['experian_faxnumber'];
               break;
            case 'Equifax':
               $fax_number = $credentials['equifax_faxnumber'];
               break;
            default:
               # code...
               break;
         }

         // TEST returns
         // return [
         //    'success'   => false, 
         //    'fax'       => $request['fax'],
         //    'file'      => $request['file'],
         //    'user_id'   => $request['user_id']
         // ];
         // return [
         //    'success'   => true, 
         //    'fax'       => $request['fax'],
         //    'file'      => $request['file'],
         //    'user_id'   => $request['user_id'],
         //    'data'      => ['uri' => 'success.com']
         // ];

         $bearerToken = $this->getUserOAuthToken($request['user_id']);

         $filename = str_replace("https://app.30daycra.com/files/c2m_pdf/", "", $file);

         $RSC = new RingCentralCustom($request['user_id'], $bearerToken);
         $result = $RSC->sendRequest($filename, $fax_number);

         if (isset($result['uri'])) {
            return [
               'success'   => true, 
               'msg'       => 'Sent Fax ' . $result['uri'],
               'data'      => $result,
               'fax'       => $fax_number,
               'file'      => $request['file'],            
            ];
         }

         return [
            'success'   => false, 
            'msg'       => 'An error occured',
            'data'      => $result,
            'fax'       => $fax_number,
            'file'      => $request['file'],            
         ];


      }catch (\RingCentral\SDK\Http\ApiException $e) {
         // Getting error messages using PHP native interface
         return [
            'success'   => false, 
            'msg'       => 'Expected HTTP Error: ' . $e->getMessage(), 
            'fax'       => $fax_number,
            'file'      => $request['file'],
         ];
      }

   }

}
