<?php
include( 'app.php' );

/**
 * 
 */
class StripeWebhook extends UserController
{

    protected $stripe = null;
    protected $sendgrid = null;

    public function init($request){

        $this->stripe = new StripeService($this->conn);
        $this->sendgrid = new SendGridService($this->conn);

        switch ($request->type) {
            case 'invoice.payment_failed':

                $this->handleFailedPayment($request);

                break;

            case 'invoice.payment_succeeded':
                
                $this->handleSuccessPayment($request);

                break;
            
            default:
                # code...
                break;
        }

    }

    public function handleFailedPayment($request) {

        $object = $request->data->object;

        $customerID = $object->customer;
        $customer = \Stripe\Customer::retrieve( $customerID );
        $email    = strtolower($object->email);

        $name = ""; 
        $firstName = "";

        if (isset($customer->metadata->name)){
            $name = $customer->metadata->name;
        }

        if ($name != "" && strpos($name, " ") !== false) {
            $firstName = explode(' ', $name);
            $firstName = ucfirst(strtolower($firstName[0]));
        } else {
            $firstName = $name;
        }

        $mail = $this->conn->GetDataFromDb("SELECT * FROM settings WHERE name = 'mail_settings'");
        $pass = null;

        $subject = $mail['data']['failed_payment']['subject'];
        $body = str_replace(['::FIRST_NAME::', '::EMAIL::', '::PASSWORD::'], [$firstName, $email, $pass], $mail['data']['failed_payment']['body']);

        $this->sendgrid->sendAnEmail($email, $subject, $body, $name);
    }

    public function handleSuccessPayment($request) {

        $object = $request->data->object;

        $planID = null;
        $paymentPlans = ["price_1HIEKbDQd1d8RmQFtJsU2JVY"];

        if (isset($object->lines->data[0]->plan->id)) {
            $planID = $object->lines->data[0]->plan->id;
        }

        echo "PLAN ID SELECTED: ".$planID."\n\n";

        if (in_array($planID, $paymentPlans)) {
            echo "PLAN ID VALID : true\n\n";

            $customerID = $object->customer;
            $customer = \Stripe\Customer::retrieve( $customerID );
            $email    = strtolower($customer->email);

            echo "RETRIEVING CUSTOMER: ".$customerID."\n\n";
            echo "CUSTOMER RETRIEVED :".json_encode($customer)."\n\n";
            echo "EMAIL :".$email."\n\n";

            $name = ""; $firstName = "";

            if (isset($customer->metadata->name)){
                $name = $customer->metadata->name;
                echo "NAME :".$name."\n\n";
            }
                
            if ($name != "" && strpos($name, " ") !== false) {
                $firstName = explode(' ', $name);
                $firstName = ucfirst(strtolower($firstName[0]));
            } else {
                $firstName = $name;
            }

            $cardID = "";

            if ($customer->sources->data[0]->id)
                $cardID = $customer->sources->data[0]->id;

            echo "CARD ID: $cardID";

            $data = array(
                "cardID" => $cardID,
                "name" => $name,
                "email" => $email,
                "accType" => 'yearly',
                "planID" => $planID,
                "amountPaid" => $object->amount_paid,
                "customerID" => $customerID,
                "interval" => $object->lines->data[0]->plan->interval
            );

            echo "SAVING USER DATA:".json_encode($data)."\n\n";

            $response = $this->signupOrUpdate($data, $this->sendgrid);

            echo json_encode(["success" => true, "email" => $email, "customerID" => $customerID, "planID" => $planID]);
        } else {
            echo json_encode(["success" => false, "reason" => "No PLAN ID that we are looking for..."]);
        } 
    } 

}

$input  = @file_get_contents( "php://input" );
$data   = json_decode( $input );

if (isset($data)) {

    $stripe_webhook = new StripeWebhook($DB);

    $stripe_webhook->init($data);
}

echo json_encode(['success'=>false, 'msg'=>'An error occured']);

