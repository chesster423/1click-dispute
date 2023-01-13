<?php

/**
 * 
 */
class StripeService
{

	protected $db = null;
	protected $secret_key = null;
	
	public function __construct($db)
	{
		$this->db = $db;

		$query = "SELECT * FROM settings WHERE name = 'stripe'";

		$stripe = $this->db->GetDataFromDb($query);

		$this->secret_key = $stripe['production_key'];

		if ($stripe['dev_active']) {
			$this->secret_key = $stripe['development_key'];
		}

		\Stripe\Stripe::setApiKey($this->secret_key);
	}

	public function response($success = false, $msg = '', $data = null){
		return [
			'success' => $success,
			'msg' 	  => $msg,
			'data' 	  => $data
		];
	}

	public function getUserDetails($request) {

		if (!$request['user_id']) {
			return $this->response(false, "Some values are missing");
		}

        $query = "SELECT id, name, customerID, planID, cardID FROM users WHERE id = '" . $this->db->escape($request['user_id']) . "'";

        $user = $this->db->GetDataFromDb($query);

        if (!$user) {
            return $this->response(false, "User not found");
        }

        return $user;
    }

	public function getPlans($request) {

		// prod_HDRKWLjBrhBuNr - plan id of empire products
		// change according to liv data
		$plans = \Stripe\Plan::all(['limit' => 3, 'active'=> true, 'product' => 'prod_HOH0hHeVvhQmMs']);

		foreach ($plans->data as $key => $value) {
			$plans->data[$key]->plan_price = "$".substr($value->amount, 0, -2).".".substr($value->amount, -2);
		}

		return ['success'=>true, 'msg'=>'Plan retrieved', 'data'=> $plans];
	}


	public function getCards($request) {

		// $request['customer_id'] = 'cus_HEbWEr7DzUTbQJ';

		if (!$request['customer_id']) {
			return ['success'=>false, 'msg'=>'Customer ID is required'];
		}

		$stripe = \Stripe\Customer::allSources(
		  $request['customer_id'],
		  ['object' => 'card', 'limit' => 3]
		);

		return $this->response(true, "Cards retrieved");
	}

	public function getCardDetails($request) {

		try {

			$user = $this->getUserDetails($request);

			// $request['customer_id'] = 'cus_HEbWEr7DzUTbQJ';
			// $request['card_id'] = 'pi_1Gg8JlBr4ZAETJq9p6I2VjqF';

			if (!$user['customerID'] || !$user['cardID']) {
				return ['success'=>false, 'msg'=>'Customer ID and Card ID are required'];
			}

			$stripe = \Stripe\Customer::retrieveSource(
			  $user['customerID'],
			  $user['cardID']
			);

			return $this->response(true, "Card information retrieved", $stripe);
			
		} catch (Exception $e) {
			return $this->response(false, "An error occured", $e);
		}
		
	}

	public function saveCardDetails($request) {

        $user = $this->getUserDetails($request);

        if (!$user['customerID']) {
            return $this->response(false, "Customer ID not found");
        }

        $token = null;

        try {

            if (isset($user['cardID']) && strlen($user['cardID']) > 0) {
                
                $stripe = \Stripe\Customer::updateSource(
                    $user['customerID'],
                    $user['cardID'],
                    [
                    	'name' => $request['name'],
                        'exp_month' => $request['exp_month'],
                        'exp_year' => $request['exp_year'],
                    ]
                );

            }else{

                $token = \Stripe\Token::create([
                    'card' => [
                    		'name' => $request['name'],
                            'number' => $request['number'],
                            'exp_month' => $request['exp_month'],
                            'exp_year' => $request['exp_year'],
                            'cvc' => $request['cvc'],
                        ]
                    ]            
                );

                $token = $token->id;


                $stripe = \Stripe\Customer::createSource(
                    $user['customerID'],
                    [
                        'source' => $token,
                    ]
                );

                $save_card = $this->db->ExecuteQuery("UPDATE users SET cardID = '" . $this->db->escape($stripe->id)  . "' WHERE id = '" .  $this->db->escape($request['user_id']) . "' ");
            }

            if ($stripe) {
                return $this->response(true, "Card information saved.");
            }

        } catch (Exception $e) {
            return $this->response(false, "Something went wrong.", $e->getMessage());
        }


        return $this->response(false, "Failed to save card details");

    }

    public function removeCard($request) {

        $user = $this->getUserDetails($request);

        if (!$user['customerID']) {
            return $this->response(false, "Customer ID not found");
        }

        try {

            $stripe = \Stripe\Customer::deleteSource(
                $user['customerID'],
                $user['cardID']
            );

            if ($stripe) {
                $remove_card = $this->db->ExecuteQuery("UPDATE users SET cardID = '' WHERE id = '" .  $this->db->escape($request['user_id']) . "' ");

                return $this->response(true, "Card removed", $stripe);
            }

        } catch (Exception $e) {
            return $this->response(false, "Something went wrong.", $e->getMessage());
        }

    }

}