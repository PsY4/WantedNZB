<?php
error_reporting(E_ALL ^ E_NOTICE);
ini_set("display_errors", 1);
$_GET['t'] = urldecode($_GET['t']);
$json=file_get_contents("http://omdbapi.com/?t=".urlencode($_GET['t'])."&y=".urlencode($_GET['y'])."");
$info=json_decode($json);
if ((is_object($info))&& (trim($info->Poster)!="")){
	$remoteImage = $info->Poster;
} else {
	$remoteImage = "no-poster.png";
}
$imginfo = getimagesize($remoteImage);
header("Content-type: ".$imginfo['mime']);
readfile($remoteImage);
?>