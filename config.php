<?php
	$servername = "localhost";
	$dbname = "dominique_live";

	if ($_SERVER["SERVER_NAME"] == "localhost" || strpos($_SERVER['SERVER_NAME'], 'ngrok.io') !== false) {
		$dbusername = "root";
		$dbpassword = "";
	} else {
		$dbname = "dominique_db";
		$dbusername = "senaid";
		$dbpassword = "S32nW7Sy^A_yygQ6";
	}
?>