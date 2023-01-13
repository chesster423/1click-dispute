<?php
/**
 * 
 */
class UserController extends BaseController
{

    public function getUsers($request = []) {
        
        $data = [];

        $query = "SELECT name, email, expireOn, accepted_tos, id, user_type FROM users ORDER BY id DESC";

        $data = $this->conn->GetRows($query);

        foreach ($data as $key => $value) {
            $data[$key]['expiry_beautified'] = date('m/d/Y', strtotime($value['expireOn']));

            if (new DateTime() > new DateTime($value['expireOn']))
                $data[$key]["isExpired"] = true;
            else
                $data[$key]["isExpired"] = false;
        }

        return $this->response(true, "", $data);

    }

    public function findUser($request) {

        $data = [];

        $query = "SELECT u.*, ums.active_mail, ums.lob_api_key, ums.ringcentral_enabled FROM users u 
        LEFT JOIN user_mail_settings ums ON u.id = ums.user_id WHERE u.id = '" . $this->conn->escape($request['id']) .  "'";

        $data = $this->conn->GetDataFromDb($query);

        if ($data) {            
            return $this->response(true, "Data found", $data);
        }

        return $this->response(false, "No user found");
    }

    public function createUser($request, $sendgrid) {

        if (isset($request['expireOn'])) {
            $request['expireOn'] = date('Y-m-d H:i:s',  strtotime($request['expireOn']));
        }
        
        $request['password'] = $this->conn->generateRandomString(10);

        $response = parent::create($request);

        if ($response['success']) {
            $firstName = $request['name'];

            if (strpos($firstName, " ") !== false) {
                $firstName = explode(' ', $firstName);
                $firstName = $firstName[0];
            }

            $this->sendWelcomeEmail($request['email'], $firstName, $request['password'], $sendgrid);
        }

        return $response;
    }

    public function updateUser($request) {

        //disable account
        if (isset($request['expireOn']) && !isset($request['user_type']))
            $request['expireOn'] = 'NOW()';
        else if (isset($request['expireOn'])) //edit account
            $request['expireOn'] = date('Y-m-d H:i:s', strtotime($request['expireOn']));

        if (isset($request['email'])) {
            $check_email = $this->conn->GetDataFromDb("SELECT email FROM users WHERE email = '" . $this->conn->escape($request['email']) . "' AND id <> '" . $this->conn->escape($request['id']) . "' ");

            if (count($check_email)) {
                return $this->response(false, "Email is already in use");
            }
        }

        if (isset($request['new_password']) && isset($request['changePasswordByAdmin'])) {
            $request['password'] = password_hash($request['new_password'], PASSWORD_BCRYPT);
        }

        //password change from members area
        if (isset($request['current_password']) && isset($request['new_password']) && isset($request['confirm_password'])) {

            if ($request['new_password'] != $request['confirm_password']) {
                return $this->response(false, "New password and confirm password don't match");
            }

            $password_data = $this->conn->GetDataFromDb("SELECT password FROM users WHERE id = '" . $this->conn->escape($request['id']) . "'");

            if (!password_verify($request['current_password'], $password_data['password'])) {
                return $this->response(false, "Incorrect old password");
            }

            $request['password'] = password_hash($request['new_password'], PASSWORD_BCRYPT);

        }

        unset($request['current_password']);
        unset($request['new_password']);
        unset($request['confirm_password']);
        unset($request['expiry_beautified']);
        unset($request['isExpired']);
        unset($request['changePasswordByAdmin']);

        $_SESSION['memberName'] = $request['name'];

        return parent::update($request);
    }

    public function signupOrUpdate($request, $sendgrid) {

        $expire = "INTERVAL 1 MONTH";

        if ($request['interval'] == 'year')
            $expire = "INTERVAL 1 YEAR";

        $password = $this->randomPassword();
        $ePassword = password_hash($password, PASSWORD_BCRYPT);

        $email = $this->conn->escape($request['email']);
        $accType = $this->conn->escape($request['accType']);
        $planID = $this->conn->escape($request['planID']);
        $cardID = $this->conn->escape($request['cardID']);
        $name = $this->conn->escape($request['name']);
        $customerID = $this->conn->escape($request['customerID']);
        $firstName = $name;
        if (strpos($name, " ") !== false) {
            $tName = explode(' ', $name);
            $firstName = $tName[0];
        }

        if ($this->conn->NumOfRows("SELECT id FROM users WHERE LOWER(email) = '$email'") == 0) {

            $query = "INSERT INTO users(cardID,customerID,name,email,password,lastUpdated,createdOn,expireOn,planID,accType)
                    VALUES ('$cardID','$customerID','$name','$email', '$ePassword', NOW(), NOW(), DATE_ADD(NOW(), $expire), '$planID', '$accType')";

            $this->conn->ExecuteQuery($query);

            $this->sendWelcomeEmail($email, $firstName, $password, $sendgrid);
        } else {
            $query = "UPDATE users SET cardID='$cardID',customerID='$customerID',expireOn = DATE_ADD(NOW(), $expire), accType='$accType', planID='$planID' WHERE LOWER(email)='$email'";
            $this->conn->ExecuteQuery($query);
            $this->sendReBillEmail($firstName,$email,$sendgrid);
        }

        return $this->response(true, "User saved!");
    }

    private function sendWelcomeEmail($email, $firstName, $pass, $sendgrid) {

        if (!$firstName) {
            $firstName = "Hello";
        }

        $settings = new SettingController($this->conn);
        $mail = $settings->getMailSettings();

        $subject = $mail['data']['new_purchase']['subject'];

        $body = str_replace(['::FIRST_NAME::', '::EMAIL::', '::PASSWORD::'], [$firstName, $email, $pass], $mail['data']['new_purchase']['body']);

        $sendgrid->sendAnEmail($email, $subject, $body);
    }

    private function sendReBillEmail($firstName, $email, $sendgrid) {

        if (!$firstName) {
            $firstName = "Hello";
        }

        $settings = new SettingController($this->conn);
        $mail = $settings->getMailSettings();
        $pass = null;

        $subject = $mail['data']['rebill']['subject'];
        $body = str_replace(['::FIRST_NAME::', '::EMAIL::', '::PASSWORD::'], [$firstName, $email, $pass], $mail['data']['rebill']['body']);

        $sendgrid->sendAnEmail($email, $subject, $body);
    }

    public function authenticateUserRequest($request) {

        $query = "SELECT id, token FROM users WHERE expireOn > NOW() AND id = '" . $this->conn->escape($request['user_id']) . "' AND token = '" . $this->conn->escape($request['_token']) . "' ";

        $data = $this->conn->GetDataFromDb($query);

        if (!empty($data)) {
            return $this->response(true, 'User verified');
        }

        return $this->response(false, 'Error 401 : Unauthorized Access');
    }

    public function acceptUser($request, $sendgrid) {

        if (!$request['id']) {
            return $this->response(false, 'Missing `id` as parameter');
        }

        $query = "SELECT id, email, name FROM users WHERE id = '" . $this->conn->escape($request['id']) . "'";

        $data = $this->conn->GetDataFromDb($query);

        if ($data && $this->saveSignature($request['signature'], $data['email'])) {

            $this->savePDFDocument($request['id'], $data['name'], $data['email']);

            $sendgrid->sendEmailWithAttachment($data);

            $_SESSION['memberAcceptedTOS'] = 1;

            return parent::update($request);
        }

    }

    public function saveSignature($xml, $email) {

        try {

            $dom = new DOMDocument;
            $dom->preserveWhiteSpace = FALSE;
            $dom->loadXML($xml);

            $save = $dom->save('/srv/app.30daycra.com/files/signatures/'.$email.'.xml');

            if ($save) {
                return true;
            }
            return false;

        } catch (Exception $e) {
            exit(json_encode($e->getMessage()));
        }
    }

    public function savePDFDocument($id, $name, $email) {

        try {

            $s = new SettingController($this->conn);
            $tos = $s->getTOS(['id' => $id]);

            if (!$tos['data']['content']) {
                return $this->response(false, 'No Terms of Service found.');
            }

            $pdf_content = $tos['data']['content'];

            $pdf = new TCPDF('P', 'mm', 'A4');
            
            $pdf->AddPage();
            $pdf->ImageSVG('files/signatures/'.$email.'.xml', 115, 10, '', '', '', '', '', 0, false);
            $pdf->writeHTMLCell(0, 50, 140, 40, '<p>_______________________<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Signature</p>');

            $pdf->writeHTML($pdf_content, 1, 0, 1, 0);

            $pdf->SetFillColor(0, 0, 0 );

            $filepath = str_replace('controller', '', __DIR__);

            $pdf->Output($filepath.'files/pdf/'.$email.'.pdf', 'F');

        } catch (Exception $e) {
            exit(json_encode($e));
        }

    }

    public function verifyYFSUser($request) {

        if (!$request['id']) {
            return $this->response(false, 'No ID found');
        }

        $data = [];

        $query = "SELECT * FROM yfs_users WHERE yfs_id = '" . $this->conn->escape($request['id']) . "'";

        $data = $this->conn->GetDataFromDb($query);

        if ($data) {
            return $this->response(true, 'User verified');
        }

        return $this->response(false, 'User not found');

    }

    public function acceptTOS($request, $sendgrid) {

        if (!$request['id']) {
            return $this->response(false, 'Missing `id` as parameter');
        }

        $query = "INSERT INTO yfs_users (yfs_id, name, email, details, signature, tos_url) VALUES 
        (
        '" . $this->conn->escape($request['id']) . "',
        '" . $this->conn->escape($request['full_name']) . "',
        '" . $this->conn->escape($request['email']) . "',
        '" . $this->conn->escape(json_encode($request)) . "',
        '" . $this->conn->escape($request['signature']) . "',  
        '" . $this->conn->escape($request['tos_url']) . "')";

        $insert = $this->conn->ExecuteQuery($query);

        if ($insert && $this->saveYFSSignature($request['signature'], $request['email'])) {

            $html = file_get_contents('tos/tos.html');    

            $dom = new DOMDocument();
            libxml_use_internal_errors(true);

            $dom->loadHTML($html);
            libxml_use_internal_errors(false);            

            $final_content = '
            <html>
            <head>
                <link href="https://www.30daycra.com/assets/lander.css">
                <title></title>
            </head>
            <body>
            <br>
            ' . $html . '
            </body>
            </html>';

            $final_content = str_replace(['<br>', '<div></div>'], ['', ''], $final_content);
            $final_content = preg_replace("/<div class\=\".+\">/", '', $final_content);
            $final_content = preg_replace("/<div id\=\".+\">/", '', $final_content);
            $final_content = preg_replace('/\s+/', ' ', $final_content);

            $this->saveYFSDocument($request['email'], $request['full_name'] ,$final_content);

            $sg = $sendgrid->sendYFSEmailWithAttachment($request);

            return $this->response(true, 'YFS User saved');
        }

        return $this->response(false, 'An error occured');

    }

    public function getTOSContent($url) {

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
        $content = curl_exec($ch);
        curl_close($ch);

        return $content;

    }

    public function saveYFSSignature($xml, $email) {

        try {

            $dom = new DOMDocument;
            $dom->preserveWhiteSpace = FALSE;
            $dom->loadXML($xml);

            $path = ($_SERVER['REMOTE_ADDR'] == 'localhost' || $_SERVER['REMOTE_ADDR'] == '::1') ? 'files/yfs/signatures/'.$email.'.xml' : '/srv/app.30daycra.com/files/yfs/signatures/'.$email.'.xml';

            $save = $dom->save($path);

            if ($save) {
                return true;
            }
            return false;

        } catch (Exception $e) {
            exit(json_encode($e->getMessage()));
        }
    }

    public function saveYFSDocument($email, $name ,$html) {

        try {

            $pdf_content = $html;

            $pdf = new TCPDF('P', 'mm', 'A4');
            
            $pdf->AddPage();
            $pdf->ImageSVG('files/yfs/signatures/'.$email.'.xml', 115, 10, '', '', '', '', '', 0, false);
            $pdf->writeHTMLCell(0, 50, 140, 40, '<center><p>_______________________<br>'.$name.'<br>'.$email.'</p></center><br><br>');

            $pdf->writeHTML($pdf_content, 1, 0, 1, 0);

            $pdf->SetFillColor(0, 0, 0 );

            $filepath = str_replace('controller', '', __DIR__);

            $pdf->Output($filepath.'files/yfs/pdf/'.$email.'-pdf', 'F');

        } catch (Exception $e) {
            exit(json_encode($e));
        }
    }

    public function setRingCentralToken($accessToken, $refreshToken, $endPointId) {

        $user_id = $_SESSION['ext_user_id'];

        if (!$user_id) {
            return $this->response(false, 'user_id is missing');
        }

        if (!$accessToken) {
            return $this->response(false, 'No token found.');
        }

        $query = "
            INSERT INTO 
                ringCentralTokens (userId, access_token, refresh_token, endpoint_id, createdAt, expiresAt) 
            VALUES 
               ('$user_id', '$accessToken', '$refreshToken', '$endPointId', NOW(), DATE_ADD(NOW(), INTERVAL 7 DAY))
        ";
        
        if ($this->conn->ExecuteQuery($query)) {
            return $this->response(true, 'RingCentral token saved!');
        }

        return $this->response(false, 'An error occured');

    }

    public function checkRCToken($request) {

        if (!$request['user_id']) {
            return $this->response(false, "Application cannot identify the user. Please try again.");
        }

        // Save user's id on SESSION for RingCentral's reference when getting the user's token.
        $user_id = $_SESSION['ext_user_id'] = $this->conn->escape($request['user_id']);

        if (!$_SESSION['ext_user_id']) {
            return $this->response(false, "Error: $_SESSION not working. ".$_SESSION['ext_user_id']."-".time());
        }

        $query = "SELECT access_token, refresh_token, endpoint_id FROM ringCentralTokens WHERE NOW() < expiresAt AND userId = '$user_id'";
        $data = $this->conn->GetDataFromDb($query);

        if (empty($data)) {
            return $this->response(false, "No active session found");
        }

        if (!$data['access_token']) {
            return $this->response(false, "RingCentral session not found. Please login to your RingCentral account.");
        }

        $RSC = new RingCentralCustom($user_id, $data['access_token'], $data['refresh_token'], $data['endpoint_id']);

        if (!$RSC->isValidRCToken()) {
            $data = $RSC->getNewTokens();

            if (isset($data['access_token'])) {
                $this->conn->ExecuteQuery("DELETE FROM ringCentralTokens WHERE userId = '$user_id'");
                $this->setRingCentralToken($data['access_token'], $data['refresh_token'], $data['endpoint_id']);
            } else
                return $this->response(false, "RingCentral session has been expired. Please login again.");
        }

        return $this->response(true, "RingCentral session found. ".$_SESSION['ext_user_id']."-".time(), $data);
    }
}
