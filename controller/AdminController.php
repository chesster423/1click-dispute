<?php
/**
 * 
 */
class AdminController extends BaseController
{

    public function updateAdmin($request) {

        if (isset($request['expireOn'])) {
            $request['expireOn'] = date('Y-m-d H:i:s',  strtotime($request['expireOn']));
        }
        
        if (isset($request['email'])) {
            $check_email = $this->conn->GetDataFromDb("SELECT email FROM admins WHERE email = '" . $this->conn->escape($request['email']) . "' AND id <> '" . $this->conn->escape($request['id']) . "' ");

            if (count($check_email)) {
                return $this->response(false, "Email is already in use");
            }
        }

        if (isset($request['current_password']) && isset($request['new_password']) && isset($request['confirm_password'])) {

            if ($request['new_password'] != $request['confirm_password']) {
                return $this->response(false, "New password and confirm password don't match");
            }

            $password_data = $this->conn->GetDataFromDb("SELECT password FROM admins WHERE id = '" . $this->conn->escape($request['id']) . "'");

            if (!password_verify($request['current_password'], $password_data['password'])) {
                return $this->response(false, "Incorrect old password");
            }

            $request['password'] = password_hash($request['new_password'], PASSWORD_BCRYPT);

        }

        unset($request['current_password']);
        unset($request['new_password']);
        unset($request['confirm_password']);
        unset($request['expiry_beautified']);

        $_SESSION['adminName'] = $request['name'];

        return parent::update($request);
    }


}