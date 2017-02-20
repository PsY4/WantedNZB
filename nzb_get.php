<?php
error_reporting(E_ALL ^ E_NOTICE);
ini_set("display_errors", 1);
include_once('simple_html_dom.php');
include_once('config.php');
$nbWarnings = 0;

// ************************************
// PARAMS du SCRIPT
// ************************************
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
    'edAge'=>'999',
    'edYear'=>''
);

$nbResultsBinnews = 0;
$startBinnews = microtime(true);

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

			$search_results[] = array(
				'type' => $l_type,
				'titre' => trim($l_title),
				'annee' => $the_year,
				'langue' => $l_lng,
                'link' => $l_link,
                'fichier' => $l_file,
				'taille' => $l_taille/*,
				'code' =>$ligne->outertext*/
			);
		}
	}
}
else {
    echo 'An error occured : ' . $result['error'];
}
$stopBinnews = microtime(true);
$elapsedBinnews = $stopBinnews - $startBinnews;


// *************************************
// REQUETTES à BINSEARCH.INFO
// *************************************
$final_results = array();
$nbResultsBinsearch = 0;
$startBinsearch = microtime(true);

if (is_array($search_results)) {
    array_splice($search_results, 25); // On ne garde que les 25 premiers résultats

    $ctx = stream_context_create(array('http'=>
        array(
            'timeout' => 2,  //5 Seconds
        )
    ));
	foreach ($search_results as $search_result) {

	    // NZB search URL : https://www.binsearch.info/index.php?q=63647&m=&max=25&adv_g=&adv_age=999&adv_sort=date&minsize=&maxsize=&font=&postdate=
	    $search_url =
            'https://www.binsearch.info/?q='.
            urlencode(
                str_replace('&quot;', '"', $search_result['fichier'])
            ).
            '&m='.
            '&max=25'.
            '&adv_col=on'.
            '&adv_g='.
            '&adv_age=999'.
            '&adv_sort=date'.
            '&minsize='.
            //'&maxsize='.   // renvoie une 403 !!!
            '&font='.
            '&postdate=';

	    // Result page download
        set_error_handler("warning_handler", E_WARNING);
        $result = file_get_contents($search_url, false, $ctx);

        if ($result !== FALSE) {
            // Extract lines of results
            $partials = explode("<input type=\"checkbox\" name=\"",$result);
            array_shift($partials); // Remove document start
            array_shift($partials); // Remove document start
            array_pop($partials); // Remove document end

            // Decode each line
            foreach ($partials as $k => $v) {
                $nbResultsBinsearch++;
                $partials[$k] = str_ireplace("<td>","|", $partials[$k]); // Isolate columns
                $partials[$k] = strip_tags($partials[$k]); // Remove HTML tags from line source code
                $partials[$k] = str_ireplace("\" >","|", $partials[$k]); // Isolate id
                $partials[$k] = str_ireplace("collection size: ","|", $partials[$k]); // Isolate name
                $partials[$k] = str_ireplace(", parts available: ","|", $partials[$k]); // Isolate filesize

                $partials[$k] = html_entity_decode($partials[$k]); // formatting

                $tmp_res = explode('|',$partials[$k]);

                $filesize = 0;
                $search_result['nzbs'][] = array(
                                    'id' => $tmp_res[0],
                                    'name' => $tmp_res[2],
                                    'size' => ((strpos($tmp_res[3],"GB")!==false)?(intval($tmp_res[3]*1000)):(intval($tmp_res[3]))),
                                    'date' => $tmp_res[7],
                                    'comment' => $tmp_res[4]
                                    );

            }

            // Result count
            if(sizeof($partials)<1) continue;

            // Résults
            $final_results[] = $search_result;
        }
	}
	$stopBinsearch = microtime(true);
	$elapsedBinsearch = $stopBinsearch - $startBinsearch;

	// Mise en tableau des résultats
	$nbResultsFinal = 0;
	$release_l = array();
	foreach ($final_results as $release) {
		foreach ($release['nzbs'] as $line) {
			if (
				$dlType != 'movies' ||
				($dlType === 'movies' && $line['size'] > DL_MIN_SIZE_MB && $line['size'] < DL_MAX_SIZE_MB)
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
									'link' => $release['link'],
									'lng' => $release['langue'],
									'size' => $line['size'],
									'date' => $line['date'],
									'file' => $line['name'],
									'nzb' => urlencode("https://www.binsearch.info/?action=nzb&".$line['id']."=1"),
									'score' => $tmp_score
									);
				$nbResultsFinal++;
			}
		}
	}

	// Tri par score
	usort($release_l, "cmp");

	echo "<li>
			<h5><nobr>
				BINNEWS.IN : ".$nbResultsBinnews." résultats en ".sprintf("%0.2f",$elapsedBinnews)." secondes.<br />
				BINSEARCH.INFO : ".$nbResultsBinsearch." résultats en ".sprintf("%0.2f",$elapsedBinsearch)." secondes".
                    (($nbWarnings>0)?" (".$nbWarnings." erreurs)":"") .
                ".<br />
				FINAL : ".$nbResultsFinal." résultats en ".sprintf("%0.2f",($elapsedBinnews+$elapsedBinsearch))." secondes.<br />
			</nobr></h5>
		</li>";
	foreach ($release_l as $line) {
		echo "<li class=\"result\">
                <a href='".$line['link']."' target='_blank'>
                    <img src='imdb_image.php?t=".urlencode($line['name'])."&y=".$line['year']."'>
                </a>
                <a href='#' onclick=\"send_nzb('".$line['nzb']."','".urlencode($line['name'])."','".$dlType."'); this.parentNode.style.backgroundColor = 'lightgreen'; return false;\">
                    <h2><nobr>".$line['name'].(($line['year'])?" (".$line['year'].")":"")."</nobr></h2>
                    <h3>".$line['type']." - ".$line['lng']." - ".$line['size']." Mo - ".$line['date']." jours</h3>
                    <h4><nobr>".$line['file']."</nobr></h4>
				</a>
			</li>";
	}
}

// Wishlist
$file = "wishlist.txt";
$wishlist_items = json_decode(file_get_contents($file), true);

if (!is_array($wishlist_items) || !in_array($_GET['q'],$wishlist_items)) {
	// Ajout de la recherche aux favoris
	echo "<li>
				<a href='#' onclick=\"save_wanted('".urlencode($_GET['q'])."'); this.parentNode.style.backgroundColor = 'lightgreen'; return false;\">
					<img src='wishlist.png' class='grayscale'>
					<h2><nobr>Ajouter aux favoris</nobr></h2>
					<h4><nobr>Si vous ne trouvez pas la release qui vous convient, cliquez ici.<br />Il sera disponnible en recherche rapide à chaque visite</nobr></h4>
				</a>
			</li>
			";
} else {
	// Suppression de la WishList
	echo "<li>
				<a href='#' onclick=\"remove_wanted('".urlencode($_GET['q'])."'); this.parentNode.style.backgroundColor = 'lightgreen'; return false;\">
					<img src='wishlist.png' class='grayscale'>
					<h2><nobr>Supprimer des favoris</nobr></h2>
					<h4><nobr>Si vous avez trouvé la release qui vous convient,<br />cliquez ici pour supprimer ".urlencode($_GET['q'])." des favoris.</nobr></h4>
				</a>
			</li>
			";
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