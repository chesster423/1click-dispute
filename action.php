<?php
include('app.php');

if (isset($_GET['action']) && isset($_GET['entity'])) {
	
	$action = $_GET['action'];
	$entity = $_GET['entity'];

	$data = [];

	$request_body = file_get_contents('php://input');

	$data = (array)json_decode($request_body);

	if (!$data) {
		$data = $_REQUEST;
	}

	$action_exceptions = ['login', 'login_member', 'reset_password'];

	if (!in_array($action, $action_exceptions) && !isset($_GET['is_admin']) && isset($data['_token'])) {

		$verify = new UserController($DB);
		
		$response = $verify->authenticateUserRequest($data);

		if (!$response['success']) {
			exit(json_encode($response));
		}else{
			unset($data['_token']);
		}
	}

	//make sure people can't use extension in case of expired account
	if ($entity == 'user' && $action == 'get_user') {
	    $userId = $DB->escape($data['id']);
	    if ($DB->NumOfRows("SELECT id FROM users WHERE expireOn > NOW() AND id='$userId'") == 0) {
	        exit(json_encode(array('success' => false,'msg' => 'Error 401 : Unauthorized Access')));
        }
    }

	switch ($entity) {

		case 'admin':

			$a = new AdminController($DB);

			switch ($action) {
				case 'get_admin':
                    exit(json_encode($a->find($data)));
                    break;
                case 'update_admin':
                    exit(json_encode($a->updateAdmin($data)));
                    break;
				default:
					exit(json_encode(['data'=> $data, 'success'=>false, 'error'=> 'Action not found']));
					break;
			}

			break;

		case 'user':

			$u = new UserController($DB);

			switch ($action) {
				case 'get_user':
                    exit(json_encode($u->findUser($data)));
                    break;
                case 'get_users':
                    exit(json_encode($u->getUsers($data)));
                    break;
                case 'create_user':
                    $sendgrid = new SendGridService($DB);
                    exit(json_encode($u->createUser($data, $sendgrid)));
                    break;
				case 'update_user':
					exit(json_encode($u->updateUser($data)));
					break;
				case 'signupOrUpdate':
					$sendgrid = new SendGridService($DB);
					exit(json_encode($u->signupOrUpdate($data, $sendgrid)));
					break;
				case 'accept_user':
				    $sendgrid = new SendGridService($DB);
					exit(json_encode($u->acceptUser($data, $sendgrid)));
					break;
				case 'check_rc_token':
					exit(json_encode($u->checkRCToken($data)));
					break;	
				default:
					exit(json_encode(['data'=> $data, 'success'=>false, 'error'=> 'Action not found']));
					break;
			}

			break;

		case 'setting':

			$s = new SettingController($DB);

			switch ($action) {
                case 'save_settings':
                	exit(json_encode($s->saveKeys($data)));
                    break;
                case 'get_settings':
                	exit(json_encode($s->getSettings()));
                    break;
                case 'get_tos':
                	exit(json_encode($s->getTOS($data)));
                    break;	
                case 'save_tos':
                	exit(json_encode($s->saveTOS($data)));
                    break;
                case 'get_mail_settings':
                	exit(json_encode($s->getMailSettings($data)));
                    break;
                case 'save_mail_settings':
                	exit(json_encode($s->saveMailSettings($data)));
                    break;	
				default:
					exit(json_encode(['data'=> $data, 'success'=>false, 'error'=> 'Action not found']));
					break;
			}

			break;

		case 'auth':

			$a = new AuthController($DB);

			switch ($action) {
                case 'login':
                	exit(json_encode($a->loginAdmin($data)));
                    break;
                case 'login_member':
                	exit(json_encode($a->loginMember($data)));
                    break;
                case 'reset_password':
                	$sendgrid = new SendGridService($DB);
                	exit(json_encode($a->resetPassword($data, $sendgrid)));
                    break;
				default:
					exit(json_encode(['data'=> $data, 'success'=>false, 'error'=> 'Action not found']));
					break;
			}

			break;

		case 'stripe':

			$s = new StripeService($DB);

			switch ($action) {
                case 'get_plans':
                	exit(json_encode($s->getPlans($data)));
                    break;
                case 'get_cards':
                	exit(json_encode($s->getCards($data)));
                    break;
                case 'get_card_details':
                	exit(json_encode($s->getCardDetails($data)));
                	break;
                case 'save_card_details':
                	exit(json_encode($s->saveCardDetails($data)));
                    break;
                case 'remove_card':
                	exit(json_encode($s->removeCard($data)));
                    break;
				default:
					exit(json_encode(['data'=> $data, 'success'=>false, 'error'=> 'Action not found']));
					break;
			}

			break;

		case 'click2mail':
			
			$c2m = new Click2MailService($DB);
			$data['recipient'] = $c2m->getRecipientAddress($data);

			switch ($action) {
		        case 'save_document':
		            exit(json_encode($c2m->createPDF($data)));
		            break;
		        case 'create_addresslist':
		            exit(json_encode($c2m->createAddressList($data)));
		            break;
		        case 'create_job':
		            exit(json_encode($c2m->createJob($data)));
		            break;
		        default:
		            # code...
		            break;
		    }
			break;

		case 'lob':
			
			$lob = new LobService($DB);
			$lob->deleteOldFiles();

			switch ($action) {
                case 'send_letter':
		        	//it will exist only when user manually try again to submit the letter
                    if (!isset($data['recipient']) || empty($data['recipient']['address']))
		    			$data['recipient'] = $lob->getRecipientAddress($data);

		            exit(json_encode($lob->handleLetter($data)));
		            break;
		        case 'get_user_addresses':
		            exit(json_encode($lob->getUserAddresses($data)));
		            break;
		        case 'delete_address':
		            exit(json_encode($lob->deleteAddress($data)));
		            break;
		        case 'upload_additional_files':
		            exit(json_encode($lob->uploadFiles($data)));
		            break;
		        case 'delete_pdf_files':
		            exit(json_encode($lob->deletePDFFiles()));
		            break;
		        default:
		            # code...
		            break;
		    }
			break;

		case 'postalocity':
			
			$p = new PostalocityService($DB);

			switch ($action) {
		        case 'send_letter':
		        	//it will exist only when user manually try again to submit the letter
					if (!isset($data['recipient']))
		    			$data['recipient'] = $p->getRecipientAddress($data);

		            exit(json_encode($p->handleLetter($data)));
		            break;
		        case 'get_user_addresses':
		            exit(json_encode($p->getUserAddresses($data)));
		            break;
		        case 'create_letter':
		            exit(json_encode($p->handleLetter($data)));
		            break;
		        case 'login':
		            exit(json_encode($p->login($data)));
		            break;
		        case 'create_job':
		            exit(json_encode($p->createJob($data)));
		            break;
		        default:
		            # code...
		            break;
		    }
			break;

		case 'ringcentral':
			
			$r = new RingCentralService($DB);			

			switch ($action) {
		        case 'send_fax':
		            exit(json_encode($r->sendFax($data)));
		            break;
		        default:
		            # code...
		            break;
		    }
			break;

		case 'mail_log':
			
			$m = new MailLogController($DB);

			switch ($action) {
		        case 'get_user_logs':
		            exit(json_encode($m->getUserLogs($data)));
		            break;
		        default:
		            # code...
		            break;
		    }
			break;

		case 'user_mail_settings':
			
			$u = new UserMailSettingController($DB);

			switch ($action) {
		        case 'get_user_mail_settings':
		            exit(json_encode($u->getUserMailSettings($data)));
		            break;
		        case 'save_user_mail_settings':
		            exit(json_encode($u->saveUserMailSettings($data)));
		            break;
		        default:
		            # code...
		            break;
		    }
			break;

		case 'yfs_user':
			
			$u = new UserController($DB);

			switch ($action) {
		        case 'verify':
		            exit(json_encode($u->verifyYFSUser($data)));
		            break;
		        case 'accept':
		        	$sendgrid = new SendGridService($DB);
		            exit(json_encode($u->acceptTOS($data, $sendgrid)));
		            break;
		        default:
		            # code...
		            break;
		    }
			break;
					
		default:
			exit(json_encode(['data'=> $data, 'success'=>false, 'error'=> 'No action found']));
			break;
	}
}
