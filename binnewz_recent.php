<?php
error_reporting(E_ALL ^ E_NOTICE);
ini_set("display_errors", 1);
include_once('simple_html_dom.php');
include_once('config.php');
$nbWarnings = 0;

// *************************************
// REQUETTE à BINNEWS.IN
// *************************************
$post_data = array(
	'chkInit' => 1,
    'edTitre' => "",
    'chkTitre' => 'on',
    'chkFichier' => 'on',
    'chkCat' => 'on',
    'cats' => array(39, 49),
    'edAge'=>'3',
    'edYear'=>''
);

$nbResultsBinnews = 0;
$release_l = array();

$result = post_request('http://www.binnews.in/_bin/search2.php', $post_data);
if ($result['status'] == 'ok'){
    $html = str_get_html($result['content']);
	$res = $html->find('table[id=tabliste]');
	$table_resultats = $res[0];
	if (!($table_resultats==null)) {
		$lignes = $table_resultats->find('tr[class=ligneclaire],tr[class=lignefoncee]');
		foreach ($lignes as $ligne) {
			$nbResultsBinnews++;
			$ligne = str_get_html( preg_replace('/<table(.*?)>(.*?)<\\/table>/','',$ligne->innertext())); // On supprime les sous-tables
			$l_type = $ligne->find('td', 1)->find('span', 0)->plaintext;
			$l_title = $ligne->find('a[class=c16]', 0)->plaintext;
			$l_link = $ligne->find('a[class=c16]', 0)->getAttribute('href');
			$l_lng = $ligne->find('td', 3)->plaintext . $ligne->find('td', 3)->find('img', 0)->getAttribute('alt');
			$l_file = $ligne->find('td', 5)->plaintext;
			$l_taille = $ligne->find('td', 6)->plaintext;
			//echo "$l_title : $l_type ($l_taille) $l_lng = $l_file\n";

			$the_year="";
			// Nettoyage du titre (on ne garde que le nom et l'année)
			preg_match_all("/\(([^)]*)\)/",$l_title,$matches); // On extrait tout ce qui est entre parenthèses
			$l_title = preg_replace("/\([^)]+\)/","",$l_title); // Titre seul
			foreach ($matches[1] as $mm) {
				if (($mm>1900) && ($mm<2050)) {
					//$l_title .= " (".$mm.")"; // Année
					$the_year = $mm;
				}
			}

            $release = array(
				'type' => $l_type,
				'titre' => trim($l_title),
				'annee' => $the_year,
				'langue' => $l_lng,
				'fichier' => $l_file,
				'link' => $l_link,
				'taille' => $l_taille/*,
				'code' =>$ligne->outertext*/
			);

            // Création de la ligne
            $release_l[]= array(
                'name' => $release['titre'],
                'year' => $release['annee'],
                'type' => $release['type'],
                'lng' => $release['langue'],
                'link' => $release['link'],
                'size' => $release['taille']
            );

        }
	}
}
else {
    echo 'An error occured : ' . $result['error'];
}

$nbResultsFinal++;

foreach ($release_l as $line) {
    echo "<li class=\"result wishlist\">
                <a href='".$line['link']."' target='_blank'>
                    <img src='imdb_image.php?t=".urlencode($line['name'])."&y=".$line['year']."'>
                </a>
                <a href='#' onclick=\"load_wanted('".ucwords(urldecode($line['name']))."'); this.parentNode.style.backgroundColor = 'lightgreen'; return false;\">
                    <h2><nobr>".$line['name'].(($line['year'])?" (".$line['year'].")":"")."</nobr></h2>
                    <h3>".$line['type']." - ".$line['lng']." - ".$line['size']."</h3>
                    <h4><nobr>Sortie récente - Cliquez ici pour lancer la recherche</nobr></h4>
                </a>
        </li>";
}


 // ************ FONCTIONS
function cmp($a, $b) { return ($a["score"]<$b["score"]); }

function post_request($url, $data, $referer='') {
    if (is_array($data)) {
    	$data = http_build_query($data);
    	$data = preg_replace('/%5B[0-9]+%5D/simU', '%5B%5D', $data);
    }
    $url = parse_url($url);

    if ($url['scheme'] != 'http') {
        die('Error: Only HTTP request are supported !');
    }

    $host = $url['host'];
    $path = $url['path'];

    $fp = fsockopen($host, 80, $errno, $errstr, 30);
    if ($fp){

        fputs($fp, "POST $path HTTP/1.1\r\n");
        fputs($fp, "Host: $host\r\n");
        if ($referer != '')
            fputs($fp, "Referer: $referer\r\n");

        fputs($fp, "Content-type: application/x-www-form-urlencoded\r\n");
        fputs($fp, "Content-length: ". strlen($data) ."\r\n");
        fputs($fp, "Connection: close\r\n\r\n");
        fputs($fp, $data);

        $result = '';
        while(!feof($fp)) {
            // receive the results of the request
            $result .= fgets($fp, 128);
        }
    }
    else {
        return array(
            'status' => 'err',
            'error' => "$errstr ($errno)"
        );
    }

    fclose($fp);

    $result = explode("\r\n\r\n", $result, 2);
    $header = isset($result[0]) ? $result[0] : '';
    $content = isset($result[1]) ? $result[1] : '';

    return array(
        'status' => 'ok',
        'header' => $header,
        'content' => $content
    );
}

function multi_explode(array $delimiter,$string){
    $d = array_shift($delimiter);
    if ($d!=NULL){
        $tmp = explode($d,$string);
        foreach ($tmp as $key => $o){
            $out[$key] = multi_explode($delimiter,$o);
        }
    } else {
        return $string;
    }
    return $out;
}

function warning_handler($errno, $errstr) {
    global $nbWarnings;
    $nbWarnings++;
}