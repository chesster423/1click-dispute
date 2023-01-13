<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: *');
require 'vendor/autoload.php';
include "lib/DB.php";
include "lib/stripe-php-5.5.0/init.php";
include "lib/tcpdf/tcpdf.php";

session_start();

foreach (glob("services/*.php") as $services)
{
    include $services;
}

include 'controller/BaseController.php';
foreach (glob("controller/*.php") as $controller)
{
	if ($controller != 'controller/BaseController.php') {
		include $controller;
	}
}
