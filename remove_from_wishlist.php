<?php error_reporting(E_ALL); 
 ini_set("display_errors", 1); 

$file = "wishlist.txt";
$json = json_decode(file_get_contents($file), true);

foreach($json as $entry) {
    if($entry != ucwords(urldecode($_GET['nzb_name'])) ) {
        $wishlist[] = $entry;
    }
}

print_r($wishlist);
$wishlist = array_unique($wishlist);
file_put_contents($file, json_encode($wishlist));
