<?php
include( 'app.php' );

class RingCentralOAuth extends UserController {

    protected $production_client_id = 'DtJSdS4dTRSfdXy2J0dYgQ';
    protected $production_client_secret = "omlxwevPQ0ukveBDjs0caw9kMXkYEpRQa_V48QD8vf7g";
    protected $sandbox_client_id = "ybdLq3gaR1uNdpiO1XwXzA";
    protected $sandbox_client_secret = "wpFXwQYPTV6JtbqFOpbJpgOv2OnZcnQ2eVGXSPUqf0Yw";
    protected $redirect_uri = "https://app.30daycra.com/oauthRedirect.php";
    private $production_url = "https://platform.ringcentral.com/";
    private $sandbox_url = "https://platform.devtest.ringcentral.com/";

	public function post($url, $data, $headers){

		try {

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

			curl_close($curl);

			return $response;

		} catch (Exception $e) {

			return [
				'success' => false, 
				'msg'     =>'Expected HTTP Error: ' . $e->getMessage(), 
			];
			
		}

	}

	public function getToken($code) {

		try {
            $url = $this->production_url;
            $clientIdToUse = $this->production_client_id;
            $clientSecretToUse = $this->production_client_secret;

            //chesster423@gmail.com account
            if ($_SESSION['ext_user_id'] == 157) {
                $url = $this->sandbox_url;
                $clientIdToUse = $this->sandbox_client_id;
                $clientSecretToUse = $this->sandbox_client_secret;
            }

			/* API URL */
			$api_url = $url . 'restapi/oauth/token';

			$refresh_token_ttl = 3600*24*7;			

			$query_string ="grant_type=authorization_code&code=" . $code . "&client_id=" . $clientIdToUse . "&redirect_uri=" . $this->redirect_uri . "&refresh_token_ttl=" . $refresh_token_ttl;

			$headers = [
				"Content-Type: application/x-www-form-urlencoded",
                "Authorization: Basic " . base64_encode($clientIdToUse . ':' . $clientSecretToUse),
            ];

			$response = $this->post($api_url, $query_string, $headers);

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

	public function setUserToken($accessToken, $refreshToken, $endPointId) {

		return $this->setRingCentralToken($accessToken, $refreshToken, $endPointId);

	}

}


if (isset($_GET['code'])) {

	try {

		$response = null;

		$r = new RingCentralOAuth($DB);

		$code = $_GET['code'];

		$get_token = json_decode($r->getToken($code)['data'], true);

		$accessToken = $get_token['access_token'] ?? null;
		$refreshToken = $get_token['refresh_token'] ?? null;
		$endPointId = $get_token['endpoint_id'] ?? null;

		// $token = 'test';
		// $_SESSION['ext_user_id'] = 162;

		$response = $r->setUserToken($accessToken, $refreshToken, $endPointId);

	}catch (Exception $e) {

		$response = $e;

	}

}else{
	header("HTTP/1.1 401 Unauthorized");
    exit;
}

?>
<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<title>RingCentral - OAuth Token</title>

	<link rel="stylesheet" href="lib/css/font-awesome-4.7.0/css/font-awesome.css">
</head>

<style type="text/css">
	img.rc-logo {
		margin-top: 50px;
	}

	i.rc-token-success{
		margin-top: 20px;
		font-size: 50px !important;
		color: #86eb53;
	}

	i.rc-token-failed{
		margin-top: 20px;
		font-size: 50px !important;
		color: #f53a3a;
	}

	p.rc-text{
		font-family: sans-serif;
		color: #585858;
	}

</style>

<body>

	<?php if ($response['success']): ?>
	<center>
		<img src="lib/images/ringcentral_logo.svg" class="rc-logo" alt="ringcentral_logo"><br>
		<i class="fa fa-check rc-token-success"></i><br>
		<p class="rc-text">RingCentral session has been successfully created! You can now close this tab and proceed using RingCentral's API.</p>
	</center>
	<?php endif; ?>

	<?php if (!$response['success']): ?>
	<center>
		<img src="lib/images/ringcentral_logo.svg" class="rc-logo" alt="ringcentral_logo"><br>
		<i class="fa fa-times-circle rc-token-failed"></i><br>
		<p class="rc-text">An error occured. Failed to create a session. Please try again.</p>
		<?php if(isset($_GET['dev']) && $_GET['dev'] == 1): ?>
		<p><?= var_dump($get_token); ?></p>
		<br>
		<p><?= var_dump($response); ?></p>
		<?php endif; ?>
	</center>
	<?php endif; ?>

</body>
</html>
