<?php
/**
 * 
 */
class MailLogController extends BaseController
{


    public function getUserLogs($request)
    {

        $query = "SELECT * FROM mail_logs WHERE user_id =  '" . $this->conn->escape($request['user_id']) . "' ORDER BY created_at DESC";
        $user = $this->conn->GetDataFromDb("SELECT name FROM users WHERE id = '" . $this->conn->escape($request['user_id']) . "' LIMIT 100");

        $logs = $this->conn->GetRows($query);

        $data_logs = [];

        foreach ($logs as $key => $value) {

            if (!array_key_exists($value['transaction_code'], $data_logs)) {
                $data_logs[ $value['transaction_code'] ] = [];
            }

            $value['simple_date'] = date('F d, Y', strtotime($value['created_at']));

            $data_logs[ $value['transaction_code'] ][] = $value;
        }

        $data = [
            'user' => $user,
            'logs' => $data_logs
        ];

        return $this->response(true, 'Logs retrieved', $data);
    }

}