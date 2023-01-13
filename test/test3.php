<?php
require '../vendor/autoload.php';
include "../lib/DB.php";

// $file = fopen('https://developers.ringcentral.com/assets/images/ico_case_crm.png', "r");

// //Output lines until EOF is reached
// while(! feof($file)) {
//   $line = fgets($file);
//   echo $line. "<br>";
// }

// fclose($file);

// Expected HTTP Error: 400 Bad Request (and additional error happened during JSON parse: Response is not JSON)
class PostalocityService
{
   
   protected $db = null;
   protected $api_url = [
      'login'        => 'https://prod.postalocity.com/user/login',
      'create_job'   => 'https://prod.postalocity.com/job',
      'test'         => 'https://reqres.in/api/users',
      'dev_login'    => 'https://dev.postalocity.com/user/login'
   ];
   
   public function __construct($db)
   {
      $this->db = $db;
   }

   public function getUserCredentials($user_id) {

      $query = "SELECT id, postalocity_username, postalocity_password FROM user_mail_settings WHERE user_id = '" . $this->db->escape($user_id) . "'";

      $data = $this->db->GetDataFromDb($query);

      return $data;

   }

   public function post($url, $data, $headers){

      error_reporting(E_ALL);
      ini_set("display_errors", 1);

      $curl = curl_init();

      curl_setopt_array($curl, array(
         CURLOPT_USERAGENT      => 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.17 (KHTML, like Gecko) Chrome/24.0.1312.52 Safari/537.17',
         CURLOPT_URL            => $url,
         CURLOPT_RETURNTRANSFER => 1,
         CURLOPT_TIMEOUT        => 0,
         CURLOPT_CUSTOMREQUEST  => "POST",
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
         return curl_getinfo($curl);
      }

      var_dump($response);die;

      curl_close($curl);

      return $response;

   }

   public function login($user_id) {

      try {

         $user = $this->getUserCredentials($user_id);

         /* API URL */
         $url = $this->api_url['dev_login'];

         /* Array Parameter Data */
         $data = [
            'userName'  => $user['postalocity_username'], 
            'password'  => $user['postalocity_password']
         ];

         $query_string = json_encode($data, JSON_UNESCAPED_SLASHES);

         /* set the content type json */
         $headers = [
            "Content-Type : application/json",
            "Accept : application/json"
         ];

         $response = $this->post($url, $query_string, $headers);

         var_dump($response);die;

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

}


$p = new PostalocityService($DB);

$payload = [
	'user_id' 		=> 162,
];

$send = $p->login($payload);

var_dump($send);die;

?>