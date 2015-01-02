<?php
 error_reporting(E_ALL);
 ini_set("display_errors", 1);

include_once('config.php');

if(isset($_GET['cat']) && !empty($_GET['cat'])) {
	$cat = $_GET['cat'];
} else {
	$cat = '';
}

$url =
	"http://".IP_SERVER.":".PORT_SERVER."/sabnzbd/api?" .
	"ma_username=".SAB_USERNAME."&" .
	"ma_password=".SAB_PASSWORD."&" .
	"apikey=".SAB_API_KEY."&" .
	"mode=addurl&" .
	"name=".$_GET['nzb_url']."&" .
	"cat=" . $cat . "&" .
	"pp=3&" .
	"script=\"\"&" .
	"nzbname=".$_GET['nzb_name'];
$result = file_get_contents($url);
echo "<pre>" . $result . "</pre>";

