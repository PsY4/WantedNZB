<?php  
error_reporting(E_ALL ^ E_NOTICE); 
ini_set("display_errors", 1); 
include_once('simple_html_dom.php');

// ************************************
// PARAMS du SCRIPT
// ************************************
define("RELEASE_MIN_SIZE",3000);
define("RELEASE_MAX_SIZE",15000);

$dlTypes = array(
	'movies' => array(
		'fr' => array(39, 49),
		'vo' => array(102, 105)
	),
	'tv_shows' => array(
		'fr' => array(7, 26, 44),
		'vo' => array(56, 59)
	),
	'music'=> array(
		'fr' => array(8, 51),
		'vo' => array(8, 51)
	)
);

if(!isset($_GET['type']) || empty($_GET['type'])) {
	$dlType = 'movies';
} else {
	$dlType = $_GET['type'];
}

if(!isset($_GET['lang']) || empty($_GET['lang'])) {
	$dlLang = 'fr';
} else {
	$dlLang = $_GET['lang'];
}

if($dlLang === 'all') {
	$cats = array_unique(array_merge($dlTypes[$dlType]['fr'], $dlTypes[$dlType]['vo']));
} else {
	$cats = $dlTypes[$dlType][$dlLang];
}

// *************************************
// REQUETTE à BINNEWS.IN
// *************************************
$post_data = array(
	'chkInit' => 1,
    'edTitre' => $_GET['q'],
    'chkTitre' => 'on',
    'chkFichier' => 'on',
    'chkCat' => 'on',
    'cats' => $cats,
    'edAge'=>'',
    'edYear'=>''
);

$result = post_request('http://www.binnews.in/_bin/search2.php', $post_data);
if ($result['status'] == 'ok'){
    $html = str_get_html($result['content']);
	$res = $html->find('table[id=tabliste]');
	$table_resultats = $res[0];
	if (!($table_resultats==null)) {
		$lignes = $table_resultats->find('tr[class=ligneclaire],tr[class=lignefoncee]');
		foreach ($lignes as $ligne) {
			$ligne = str_get_html( preg_replace('/<table(.*?)>(.*?)<\\/table>/','',$ligne->innertext())); // On supprime les sous-tables
			$l_type = $ligne->find('td', 1)->find('span', 0)->plaintext;
			$l_title = $ligne->find('a[class=c16]', 0)->plaintext;
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
			
			
			$search_results[] = array(
				'type' => $l_type,
				'titre' => trim($l_title),
				'annee' => $the_year,
				'langue' => $l_lng,
				'fichier' => $l_file,
				'taille' => $l_taille/*,
				'code' =>$ligne->outertext*/
			);
		}
	}
}
else {
    echo 'A error occured: ' . $result['error']; 
}
	
// *************************************
// REQUETTES à NZB.cc
$final_results = array();

if (is_array($search_results)) {
	foreach ($search_results as $search_result) {
		$ress = array();
		$post_data = array(
			'q'=> $search_result['fichier'],
	        'r'=> 0
	        );
		$result = strip_tags(gzinflate(substr(file_get_contents('http://nzb.cc/q.php?r=0&q='.urlencode(str_replace('&quot;', '"', $search_result['fichier'])), null),10)));
		$result = html_entity_decode(str_ireplace("&quot;","",$result));
		$vars = explode(";",$result);
		// Nombre de résultats
		$vars[0] = str_ireplace("var t=","",$vars[0]);
		if((int)$vars[0] === 0) continue;
		// Résultats
		$vars[1] = str_ireplace("var d=[","",$vars[1]);
		$vars[1] = substr($vars[1],0,-1);
		//echo "================================\r\n".print_r(explode("],[",$vars[1]), 1)."\r\n";
		$vars[1] = explode("],[",$vars[1]);
		for ($i=0; $i<sizeof($vars[1]); $i++)  {
			if (substr($vars[1][$i],0,1)=="[") $vars[1][$i] = substr($vars[1][$i],1);
			if (substr($vars[1][$i],-1)=="]") $vars[1][$i] = substr($vars[1][$i],0,-1);
			//echo "================================\r\n".print_r(str_getcsv($vars[1][$i], ",", "\""), 1)."\r\n";
			$tmp_var = str_getcsv($vars[1][$i], ",", "\"");
			$vars[1][$i] = explode(',',$vars[1][$i]);
		
			$search_result['nzbs'][] = array(
									'id' => $tmp_var[0],
									'name' => str_ireplace("\"","",$tmp_var[1]),
									'size' => $tmp_var[5],
									'date' => str_ireplace(" days","",str_ireplace("\"","",$tmp_var[6])),
									'comment' => $tmp_var[7]
									);
		}
		
		$final_results[] = $search_result;
	}
	// Mise en tableau des résultats
	$release_l = array();
	foreach ($final_results as $release) {
		foreach ($release['nzbs'] as $line) {
			if (
				$dlType != 'movies' ||
				($dlType === 'movies' && $line['size'] > RELEASE_MIN_SIZE && $line['size'] < RELEASE_MAX_SIZE)
			) {
				
				// Calcul du score de la release
				$tmp_score = 0;
				$tmp_score+= intval($line['size']/1024);	// Taille de la release
				switch ($release['lng']) {					// Langue de la release
					case "VO+FR": $tmp_score+=50; break;
					case "FR": $tmp_score+=35; break;
					case "STFR": continue; break;
					default: break;
				}
				$tmp_score-= intval($line['date'])/10;	// Date de la release
				
				// Création de la ligne
				$release_l[]= array('name' => $release['titre'],
									'year' => $release['annee'],
									'type' => $release['type'],
									'lng' => $release['langue'],
									'size' => $line['size'],
									'date' => $line['date'],
									'file' => $line['name'],
									'nzb' => urlencode("http://nzb.cc/nzb.php?c=".$line['id']),
									'score' => $tmp_score
									);
									
	
			}
		}
	}

	// Tri par score
	usort($release_l, "cmp");
	
	foreach ($release_l as $line) {
		echo "<li class=\"result\">
				<a href='#' onclick=\"send_nzb('".$line['nzb']."','".urlencode($line['name'])."','".$dlType."'); this.parentNode.style.backgroundColor = 'lightgreen'; return false;\">
					<img src='imdb_image.php?t=".urlencode($line['name'])."&y=".$line['year']."'>
					<h2><nobr>".$line['name'].(($line['year'])?" (".$line['year'].")":"")."</nobr></h2>
					<h3>".$line['type']." - ".$line['lng']." - ".$line['size']." Mo - ".$line['date']." jours</h3>
					<h4><nobr>".$line['file']."</nobr></h4>
				</a>
			</li>";
	}
}

// Ajout de l'enregistrement aux favoris
echo "<li class=\"result\">
			<a href='#' onclick=\"save_wanted('".urlencode($_GET['q'])."'); this.parentNode.style.backgroundColor = 'lightgreen'; return false;\">
				<img src='sab.png' class='grayscale'>
				<h2><nobr>Ajouter à la WishList</nobr></h2>
				<h4><nobr>Si vous ne trouvez pas la release qui vous convient, cliquez ici.<br />Il sera disponnible en recherche rapide à chaque visite</nobr></h4>
			</a>
		</li>";
?>

<?php // ************ FONCTIONS 
function cmp($a, $b) { return ($a["score"]<$b["score"]); }

function post_request($url, $data, $referer='') {
 
    // Convert the data array into URL Parameters like a=b&foo=bar etc.
    if (is_array($data)) {
    	$data = http_build_query($data);
    	$data = preg_replace('/%5B[0-9]+%5D/simU', '%5B%5D', $data);
    }
 
    // parse the given URL
    $url = parse_url($url);
 
    if ($url['scheme'] != 'http') { 
        die('Error: Only HTTP request are supported !');
    }
 
    // extract host and path:
    $host = $url['host'];
    $path = $url['path'];
 
    // open a socket connection on port 80 - timeout: 30 sec
    $fp = fsockopen($host, 80, $errno, $errstr, 30);
 
    if ($fp){
 
        // send the request headers:
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
 
    // close the socket connection:
    fclose($fp);
 
    // split the result header from the content
    $result = explode("\r\n\r\n", $result, 2);
 
    $header = isset($result[0]) ? $result[0] : '';
    $content = isset($result[1]) ? $result[1] : '';
 
    // return as structured array:
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
?>