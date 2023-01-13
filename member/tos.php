<?php
require_once 'logincheck.php';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <title>Terms of Service</title>
    <!-- Font Awesome -->

    <link type="text/css" href="../css/style.css" rel="stylesheet"> 

    <link href="https://ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/themes/south-street/jquery-ui.css" rel="stylesheet">
    <link type="text/css" href="../js/js-signature/jquery.signature.package-1.2.0/css/jquery.signature.css" rel="stylesheet"> 
	
	<style type="text/css">
		.kbw-signature { width: 400px; height: 200px; }
	</style>
</head>

<body class="fixed-sn tos" ng-app="cpi-app">
	<div ng-controller="TOSController">	
		<loader-directive></loader-directive>
		<div class="tos-backdrop">
	        <div class="tos-container">
	            <p ng-bind-html="tos.content"></p>
	            <hr>
	            <center>
	            	<span>Your Signature</span><br>
	            	<div id="sig" ng-model="tos.sig"></div><br>
	            	<input type="checkbox" name="" ng-model="tos.accept_cb"> I accept the terms of service
	            	<br>
	            	<button style="margin-top: 20px;" ng-click="clearSignature()">Clear Signature</button>
	            	<button style="margin-top: 20px;" ng-click="acceptTOS(); submitted = true" ng-init="submitted = false" ng-disabled="!tos.accept_cb || !tos.sig || submitted == true">Submit</button>
	            </center>
	        </div>
	    </div>
	</div>


<?php require_once "includes/footer.php"; ?>
<?php include_once("includes/scripts.php"); ?>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
<script src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js"></script>
<script type="text/javascript" src="../js/js-signature/jquery.signature.package-1.2.0/js/jquery.signature.js"></script>



