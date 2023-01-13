<?php
    if (isset($_GET['uid'])) {
        session_id($_GET['uid']);
        $uid = $_GET['uid'];
    } else
        $uid = session_id();

    session_start();

	unset($_SESSION['adminLoggedIN']);
	unset($_SESSION['adminSessionKey']);

    if (isset($_COOKIE['PHPSESSID'])) {
        unset($_COOKIE['PHPSESSID']);
        setcookie('PHPSESSID', null, -1, '/');
    }

	session_destroy();
    header("Location: login.php");
	exit();
?>