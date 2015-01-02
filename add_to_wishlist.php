<?php error_reporting(E_ALL); 
 ini_set("display_errors", 1); 

$file = "wishlist.txt";
$json = json_decode(file_get_contents($file), true);

$json[] = ucwords(urldecode($_GET['nzb_name']));
print_r($json);


$json = array_unique($json);


file_put_contents($file, json_encode($json));
