<?php
error_reporting(E_ALL ^ E_NOTICE);
ini_set("display_errors", 1);
$_GET['t'] = urldecode($_GET['t']);

$years = [
    $_GET['y'],
    $_GET['y']-1,
    $_GET['y']+1
];

$remoteImage = "no-poster.png";

$ctx = stream_context_create(array('http'=>
    array(
        'timeout' => 1,  //1 Seconde
    )
));

set_error_handler("warning_handler", E_WARNING);
foreach ($years as $year) {
    $json=file_get_contents("http://omdbapi.com/?t=".urlencode($_GET['t'])."&y=".urlencode($year)."", false, $ctx);
    $info=json_decode($json);
    if ((is_object($info))&& (trim($info->Poster)!="")){
        if ($info->Poster=="N/A") {
            $remoteImage = "no-poster.png";
        } else {
            $remoteImage = $info->Poster;
            break;
        }
    }
}

$imginfo = getimagesize($remoteImage);
header("Content-type: ".$imginfo['mime']);
readfile($remoteImage);


function warning_handler($errno, $errstr) {
    global $nbWarnings;
    $nbWarnings++;
}

?>