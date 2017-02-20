<?php
error_reporting(E_ALL ^ E_NOTICE);
ini_set("display_errors", 1);
$_GET['t'] = urldecode($_GET['t']);

$remoteImage = "not-found.png";
set_error_handler("warning_handler", E_WARNING);
$nb_tries = 0;

while (($remoteImage == "not-found.png") && ($nb_tries<10)) {
    if($nb_tries>0) sleep(2);

    /*
    $ca = curl_init();
    curl_setopt($ca, CURLOPT_URL, "http://api.themoviedb.org/3/configuration?api_key=a670671806cc02c1b45833822ab537ca");
    curl_setopt($ca, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ca, CURLOPT_HEADER, FALSE);
    curl_setopt($ca, CURLOPT_HTTPHEADER, array("Accept: application/json"));
    $response = curl_exec($ca);
    curl_close($ca);
    $config = json_decode($response, true);

    // Mis en commentaire car renvoie toujours http://image.tmdb.org/t/p/w185 en chemin des miniature de posters
    */
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "http://api.themoviedb.org/3/search/movie?query=".urlencode($_GET['t'])."&year=".urlencode($_GET['y'])."&api_key=a670671806cc02c1b45833822ab537ca");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_HEADER, FALSE);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array("Accept: application/json"));
    $response = curl_exec($ch);
    curl_close($ch);
    $result = json_decode($response, true);

    if (($result['total_results']>0) || ($result['status_code']==25)) {
        //$remoteImage = $config['images']['base_url'] . $config['images']['poster_sizes'][2] . $result['results'][0]['poster_path'];
        $remoteImage = "http://image.tmdb.org/t/p/w185" . $result['results'][0]['poster_path'];
        $imginfo = getimagesize($remoteImage);
    } else {
        $remoteImage = "no-poster.png";
    }
    $nb_tries++;
}
header("Content-type: ".$imginfo['mime']);
readfile($remoteImage);

function warning_handler($errno, $errstr) {
    global $remoteImage;
    $remoteImage = "not-found.png";
}

?>