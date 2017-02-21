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
?>
<a href='http://<?=SAB_USERNAME.":".SAB_PASSWORD."@".IP_SERVER.":".PORT_SERVER?>' target="_blank">
<img src='sabstatus.png'>
<?php
if ($status['state']=="Paused") {
    echo("<h2><span class='warning'><strong>En pause (".count($status['jobs'])." en attente)</strong></span></h2>");
} else if ($status['state']=="IDLE") {
    echo("<h2><span class='success'>En attente</span></h2>
        <h4><nobr>SabNzbd est en ettente, il démarrera dès que vous aurez ajouté<br />une release à télécharger. </nobr></h4>");
} else if ($status['state']=="Downloading") {
    echo("<h2><span class='success'>Télécharge à <strong>".$status['speed']."o/s</strong>, finit dans <strong>".$status['timeleft']."</strong></span></h2>");
    $nbjobs=0;
    foreach ($status['jobs'] as $job) {
        $nbjobs++;
        echo "<h4><nobr>[".$job['timeleft']."] ".$job['filename']."</nobr></h4>";
    }
}
?>
</a>
