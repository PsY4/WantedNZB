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
	"mode=qstatus&output=json";
$result = file_get_contents($url);

$status = json_decode($result, true);

//echo "<!-- ".print_r($status,1)." -->";
if ($status['state']=="Paused") { echo("<span class='warning'>En pause (".count($status['jobs'])." en attente)</span>"); }
else if ($status['state']=="IDLE") { echo("<span class='success'>En attente</span>"); }
else if ($status['state']=="Downloading") { echo("<span class='success'>Télécharge à ".$status['speed']."o/s, fini dans ".$status['timeleft']."</span>"); }


