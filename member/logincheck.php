<?php
    //ini_set('session.gc_maxlifetime', 7200);
    //session_set_cookie_params(7200);

    if (isset($_GET['uid'])) {
        session_id($_GET['uid']);
        $uid = $_GET['uid'];
    } else
        $uid = session_id();


    session_start();

    if (isset($_SESSION['memberAcceptedTOS']) && $_SESSION['memberAcceptedTOS'] == 0 && !strpos($_SERVER['REQUEST_URI'], 'tos.php')) {
        header('Location: login.php');
    }
    
	if (!isset($_SESSION['memberLoggedIN']) || !isset($_SESSION['memberSessionKey']) || !password_verify('memberLoggedIN', $_SESSION['memberSessionKey'])) {
	    if (isset($_COOKIE['PHPSESSID'])) {
            unset($_COOKIE['PHPSESSID']);
            setcookie('PHPSESSID', null, -1, '/');
        }

        header('Location: login.php');
		exit();
	}

	$userID = $_SESSION['memberID'];

?>