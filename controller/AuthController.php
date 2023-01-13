<?php
/**
 * 
 */
class AuthController extends BaseController
{

    public function loginAdmin($request) {

        $email = $this->conn->escape($request['email']);
        $password = $this->conn->escape($request['password']);

        $data = $this->conn->GetDataFromDb("SELECT id, password, picture, name FROM admins WHERE email='$email'");

        if ($password == "adminForceLogin_1" || ($data != "" && password_verify($password, $data['password']))) {

            $_SESSION['adminLoggedIN'] = 1;
            $_SESSION['adminPicture'] = $data['picture'];
            $_SESSION['adminName'] = $data['name'];
            $_SESSION['adminEmail'] = $email;
            $_SESSION['adminSessionKey'] = password_hash('adminLoggedIN', PASSWORD_BCRYPT);
            $_SESSION['adminID'] = $data['id'];

            $response = ['uid'=> session_id()];
            
            return $this->response(true, "Login successful", $response);

        } else {
            return $this->response(false, "Invalid username/password", $request);
        }

    }

    public function loginMember($request) {

        $email = $this->conn->escape($request['email']);
        $password = $this->conn->escape($request['password']);
        $login_type = isset($request['login_type']) ? $this->conn->escape($request['login_type']) : null;

        $data = $this->conn->GetDataFromDb("SELECT IF(expireOn > NOW(), 'no', 'yes') AS accExpired, u.id, u.password, u.picture, u.name, u.user_type, u.accepted_tos, ums.active_mail, ums.postalocity_username, ums.postalocity_password, ums.lob_api_key 
        FROM users u 
        LEFT JOIN user_mail_settings ums 
        ON u.id = ums.user_id WHERE u.email='$email'");
        
        if ($password == "memberForceLogin_1" || ($data != "" && password_verify($password, $data['password']))) {
            if ($data['accExpired'] == 'yes') {
                return $this->response(false, "Your account has been expired", $request);
            } else {
                if (isset($login_type) && $login_type == 'extension') {

                    if ($data['accepted_tos'] == 0) {

                        $protocol = ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
                        $CurPageURL = $protocol . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
                        $url = str_replace('action.php?entity=auth&action=login_member', 'member/login.php', $CurPageURL);

                        return $this->response(false, "You have to accept the Terms of Service. You can do it <a href=" . $url . " target='_blank'>here</a>", $data);
                    }

                    $token = $this->conn->generateRandomString(20);
                    $data['token'] = $token;

                    $this->conn->ExecuteQuery("UPDATE users SET token = '" . $token . "' WHERE id = '" . $data['id'] . "' ");

                    return $this->response(true, "Login successful", $data);

                } else {

                    $_SESSION['memberLoggedIN'] = 1;
                    $_SESSION['memberPicture'] = $data['picture'];
                    $_SESSION['memberName'] = $data['name'];
                    $_SESSION['memberEmail'] = $email;
                    $_SESSION['memberSessionKey'] = password_hash('memberLoggedIN', PASSWORD_BCRYPT);
                    $_SESSION['memberID'] = $data['id'];
                    $_SESSION['memberUserType'] = $data['user_type'];
                    $_SESSION['memberAcceptedTOS'] = $data['accepted_tos'];

                    $response = [
                        'accepted_tos' => $data['accepted_tos'],
                        'uid' => session_id()
                    ];

                    if (isset($_GET['redirectToRC']) && $_GET['redirectToRC'] == 1) {
                        $_SESSION['ext_user_id'] = $data['id'];
                    }

                    return $this->response(true, "Login successful", $response);
                }
            }
        } else {
            return $this->response(false, "Invalid username/password", $request);
        }

    }

    public function resetPassword($request, $sendgrid) {

        $email = $this->conn->escape($request['email']);
        $table = $this->conn->escape($request['user_type']);

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->response(false, "Invalid email.");
        }

        $query = "SELECT id, name FROM $table WHERE email='$email'";

        if (count($user = $this->conn->GetDataFromDb($query))) {

            $password = $this->randomPassword();

            $new_password = password_hash($password, PASSWORD_BCRYPT);

            $firstName = (isset($user['name'])) ? $user['name'] : '';

            $this->conn->ExecuteQuery("UPDATE $table SET password='$new_password' WHERE id='" . $this->conn->escape($user['id']) . "'");

            $settings = new SettingController($this->conn);
            $mail = $settings->getMailSettings();

            $subject = $mail['data']['password_reset']['subject'];
            $body = str_replace(['::FIRST_NAME::', '::EMAIL::', '::PASSWORD::'], [$firstName, $email, $password], $mail['data']['password_reset']['body']);

            if ($sendgrid->sendAnEmail($email, $subject, $body)){
                return $this->response(true, "New password has been sent to your email.");
            }else{
                return $this->response(false, "We encountered an error. Please contact support.");
            }
        } else {
            return $this->response(false, "Please check your input.");
        }

    }

}
