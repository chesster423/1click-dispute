<?php
/**
 * 
 */
class UserMailSettingController extends BaseController
{


    public function getUserMailSettings($request)
    {
        $data = [];

        $query = "SELECT * FROM user_mail_settings WHERE user_id =  '" . $this->conn->escape($request['user_id']) . "'";
        $data = $this->conn->GetDataFromDb($query);

        if (!$data) {
        	$data = [
        		'id'                   => 0,
        		'user_id'              => null,
        		'active_mail'          => null,
        		'postalocity_username' => null,
        		'postalocity_password' => null,
        		'lob_api_key'          => null,
        	];
        }

        return $this->response(true, 'Settings retrieved', $data);
    }

    public function saveUserMailSettings($request) {

    	$query = "INSERT INTO user_mail_settings 
	    (id, 
        user_id, 
        active_mail, 
        postalocity_username, 
        postalocity_password, 
        lob_api_key, 
        transunion_faxnumber,
        equifax_faxnumber,
        experian_faxnumber)
	    VALUES (
        '" . $this->conn->escape($request['id']) . "', 
        '" . $this->conn->escape($request['user_id']) . "', 
        '" . $this->conn->escape($request['active_mail']) . "', 
        '" . $this->conn->escape($request['postalocity_username']) . "', 
	    '" . $this->conn->escape($request['postalocity_password']) . "', 
        '" . $this->conn->escape($request['lob_api_key']) . "',
        '" . $this->conn->escape($request['transunion_faxnumber']) . "',
        '" . $this->conn->escape($request['equifax_faxnumber']) . "',
        '" . $this->conn->escape($request['experian_faxnumber']) . "')
	    ON DUPLICATE KEY UPDATE 
	        active_mail = VALUES(active_mail), 
            postalocity_username = VALUES(postalocity_username), 
            postalocity_password = VALUES(postalocity_password), 
            lob_api_key = VALUES(lob_api_key),
            transunion_faxnumber = VALUES(transunion_faxnumber),
            equifax_faxnumber = VALUES(equifax_faxnumber),
            experian_faxnumber = VALUES(experian_faxnumber);";

	    if ($this->conn->ExecuteQuery($query)) {
	    	return $this->response(true, "Mail settings saved!");
	    }

	    return $this->response(false, "Failed to update settings!");
    }

}