<?php
/**
 * 
 */
class SettingController extends BaseController
{

    public function saveKeys($request) {

        $delete_query = $this->conn->ExecuteQuery("DELETE FROM settings WHERE name <> 'tos' AND name <> 'mail_settings'");
        $data = [];

        if ($delete_query) {

            foreach ($request as $key => $value) {
                if ($value->name) {
                    $data[] = [
                        'name' => $value->name,
                        'description' => $value->description,
                        'production_key' => $value->production_key,
                        'development_key' => $value->development_key,
                        'dev_active' => ($value->dev_active) ? 1 : 0,
                        'dev_username' => $value->dev_username,
                        'dev_password' => $value->dev_password,
                        'dev_url' => $value->dev_url,
                        'prod_username' => $value->prod_username,
                        'prod_password' => $value->prod_password,
                        'prod_url' => $value->prod_url,
                    ];
                }

            }

            return parent::create_bulk($data);
        }

        return $this->response(false, "An error occured", $request);

    }


    public function getSettings() {

        $data = [
            'stripe' => [
                'name' => 'stripe',
                'description' => 'Stripe Key',
                'production_key' => null,
                'development_key' => null,
                'dev_active' => 0,
            ],
            'sendgrid' => [
                'name' => 'sendgrid',
                'description' => 'Sendgrid Key',
                'production_key' => null,
                'development_key' => null,
                'dev_active' => 0,
            ],
            'c2m' => [
                'name' => 'c2m',
                'description' => 'Click2Mail Credentials',
                'dev_active' => 0,
                'dev_username' => null,
                'dev_password' => null,
                'dev_url' => null,
                'prod_username' => null,
                'prod_password' => null,
                'prod_url' => null,
            ],
        ];

        $settings = $this->conn->GetRows("SELECT * FROM settings ORDER BY id ASC");

        foreach ($settings as $key => $value) {
            $data[$value['name']]['production_key'] = $value['production_key'];
            $data[$value['name']]['development_key'] = $value['development_key'];
            $data[$value['name']]['dev_active'] = ($value['dev_active']) ? true : false;
            $data[$value['name']]['dev_username'] = $value['dev_username'];
            $data[$value['name']]['dev_password'] = $value['dev_password'];
            $data[$value['name']]['dev_url'] = $value['dev_url'];
            $data[$value['name']]['prod_username'] = $value['prod_username'];
            $data[$value['name']]['prod_password'] = $value['prod_password'];
            $data[$value['name']]['prod_url'] = $value['prod_url'];
        }

        return $this->response(true, 'Settings retrieved', $data);

    }

    public function getTOS($request) {

        $tos = $this->conn->GetDataFromDb("SELECT * FROM settings WHERE name = 'tos'");

        if ($tos) {

            if (isset($request['id'])) {

                $user = $this->conn->GetDataFromDb("SELECT * FROM users WHERE id = '" . $this->conn->escape($request['id']) . "'");

                $tos['content'] = str_replace(['::DATE::', '::EMAIL::', '::NAME::'], [date('M d, Y'), $user['email'], $user['name'] ], $tos['content']);

            }

            return $this->response(true, 'TOS retrieved', $tos);
        }

        return $this->response(false, 'No TOS found');

    }

    public function saveTOS($request) {

        $delete_query = $this->conn->ExecuteQuery("DELETE FROM settings WHERE name = 'tos'");

        $values = "('tos', '-', '-', '-', '" . $this->conn->escape($request['content']) . "')";

        $query = "INSERT INTO settings 
        (name, production_key, development_key, description, content)
        VALUES $values";

        if ($this->conn->ExecuteQuery($query)) {
            return $this->response(true, "Terms of Service saved!");
        }

        return $this->response(false, 'An error occured');

    }

    public function getMailSettings($request = []) {

        $mail_settings = $this->conn->GetDataFromDb("SELECT * FROM settings WHERE name = 'mail_settings'");

        if ($mail_settings) {
            return $this->response(true, 'Mail settings retrieved', json_decode($mail_settings['content'], true));
        }

        return $this->response(false, 'No Mail settings found');

    }

    public function saveMailSettings($request) {

        $delete_query = $this->conn->ExecuteQuery("DELETE FROM settings WHERE name = 'mail_settings'");

        $values = "('mail_settings', '-', '-', '-', '" . $this->conn->escape( json_encode($request['content']) ) . "')";

        $query = "INSERT INTO settings 
        (name, production_key, development_key, description, content)
        VALUES $values";

        if ($this->conn->ExecuteQuery($query)) {
            return $this->response(true, "Mail settings saved!");
        }

        return $this->response(false, 'An error occured');

    }

}