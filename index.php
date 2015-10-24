<?php
/* error_reporting(E_ALL);
 ini_set("display_errors", 1); */
?>
<!DOCTYPE HTML>
<html>
<head>
    <meta charset="utf-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
	<title>WANTED - Cherche ton film !</title>
	<meta name="description" content="">
	<meta name="HandheldFriendly" content="True">
	<link rel="stylesheet" href="http://fonts.googleapis.com/css?family=PT+Sans:regular,bold" type="text/css" />
	<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js"></script>
	<script type="text/javascript">
		$(document).ready(function() {
		    // Focus le champ de recherche à l'arrivée sur la page
		    $('input#search').focus();
		    // Lance le timer de màj du statut SABNzbd+ toutes les 5 minutes
		    setInterval(function () {update_status()}, 5*60*1000);
		    // El le lance une première fois
		    update_status();
			// Click sur la loupe lance la recherche
			$('div.icon').click(function(){
			    var search_string = $("input#search").val();
				if (search_string == '') $("ul#results").fadeOut();
				else {
			    	$("ul#results").fadeIn();
			    	search();
			    };
			});
			// Touche ENTREE lance la recherche
			$('input#search').keyup(function (event) {
		        var key = event.keyCode || event.which;
		        if (key === 13) {
				    var search_string = $("input#search").val();
					if (search_string == '') $("ul#results").fadeOut();
					else{
				    	$("ul#results").fadeIn();
				    	search();
				    };
		        }
		        return false;
		    });
		});

		// Fonction de recherche
		function search() {
		    var query_value = $('input#search').val();
		    var type = $('#types input[type="radio"]:checked').val();
		    var lang = $('#langs input[type="radio"]:checked').val();

		    $('b#search-string').html(query_value);
			if(query_value !== ''){
				$.ajax({
					type: "GET",
					url: "nzb_get.php",
					data: {
						q: query_value,
						type: type,
						lang: lang
				    },
					cache: false,
					success: function(html){
						$("ul#results").html(html);
					},
					beforeSend : function() {
						$("ul#results").html("<img src='wait.gif' />");
					}
				});
			}return false;
		}

		// Fonction d'ajout aux DL
		function send_nzb(url, name, cat) {
			cat = (typeof cat === "undefined") ? "" : cat;

			$.ajax({
				type: "GET",
				url: "add_nzb.php",
				data: { nzb_url: url, nzb_name: name, cat: cat },
				cache: false,
				success: function(html){ }
			});
			return false;
		}

		// Fonction d'ajout à la wishlist [ToReDo]
		function save_wanted(name) {
			$.ajax({
				type: "GET",
				url: "add_to_wishlist.php",
				data: { nzb_name: name },
				cache: false,
				success: function(html){ location="/"; }
			});
			return false;
		}

		// Fonction dde suppression dans la wishlist [ToReDo]
		function remove_wanted(name) {
			$.ajax({
				type: "GET",
				url: "remove_from_wishlist.php",
				data: { nzb_name: name },
				cache: false,
				success: function(html){ location="/"; }
			});
			return false;
		}

		// Fonction d'e récup du status SABNZBD+
		function update_status() {
			$.ajax({
				type: "GET",
				url: "get_nzb_status.php",
				data: { },
				cache: false,
				success: function(html){ $(".footer").html(html); }
			});
			return false;
		}

		// Relance d'un élément de la wanted
		function load_wanted(name) {
			$("input#search").val(name);
			search();

		}
		</script>
</head>
<body>
	<div id="main">
		<center><a href="/"><img src='wanted.png'></a></center>
		<input type="text" id="search" autocomplete="off"><div class="icon"></div>
		<div id="types">
			<strong>Type : </strong>
			<label>
				<input type="radio" value="movies" name="type" checked="checked" />
				Film
			</label>
			<label>
				<input type="radio" value="tv_shows" name="type" />
				Série
			</label>
			<label>
				<input type="radio" value="music" name="type" />
				Musique
			</label>
		</div>
		<div id="langs">
			<strong>Langue : </strong>
			<label>
				<input type="radio" value="fr" name="lang" checked="checked" />
				FR
			</label>
			<label>
				<input type="radio" value="vo" name="lang" />
				VO
			</label>
			<label>
				<input type="radio" value="all" name="lang" />
				Tout
			</label>
		</div>
		<h4 id="results-text">Recherche de : <b id="search-string"></b></h4>
		<ul id="results"><?php
			// Au chargement de la page, on charge la wishlist
			$films = json_decode(file_get_contents("wishlist.txt"), true);
			if (is_array($films))
				foreach ($films as $film) {
					echo 	"<li class=\"result wishlist\">
								<a href='#' onclick=\"load_wanted('".ucwords(urldecode($film))."'); this.parentNode.style.backgroundColor = 'lightgreen'; return false;\">
									<img class='grayscale' src='imdb_image.php?t=".urlencode($film)."'>
									<h2><nobr>".ucwords(urldecode($film))."</nobr></h2>
									<h4><nobr>Dans les favoris<br />Cliquez ici pour relancer la recherche</nobr></h4>
								</a>
							</li><script>$(document).ready(function() {\$(\"#results\").show();});</script>";

				}
		?></ul>
		<br /><br />
	</div>
	<div class="footer">
	</div>
<style>
html, body, div, span, applet, object, iframe, h1, h2, h3, h4, h5, h6, p, blockquote, pre, a, abbr, acronym, address, big, cite, code, del, dfn, em, img, ins, kbd, q, s, samp, small, strike, strong, sub, sup, tt, var, u, i, center, dl, dt, dd, ol, ul, li, fieldset, form, label, legend, table, caption, tbody, tfoot, thead, tr, th, td, article, aside, canvas, details, embed, figure, figcaption, footer, header, hgroup, menu, nav, output, ruby, section, summary, time, mark, audio, video {
	margin: 0;
	padding: 0;
	border: 0;
	font-size: 100%;
	font: inherit;
	vertical-align: baseline;
	font-weight: normal;
	-webkit-font-smoothing: antialiased;
}
/* HTML5 display-role reset for older browsers */
article, aside, details, figcaption, figure, footer, header, hgroup, menu, nav, section {
	display: block;
}
body {
	line-height: 1;
}
ol, ul {
	list-style: none;
}
blockquote, q {
	quotes: none;
}
blockquote:before, blockquote:after, q:before, q:after {
	content: '';
	content: none;
}
table {
	border-collapse: collapse;
	border-spacing: 0;
}
a {
	outline: none;
	text-decoration: none;
}
::selection {
	background:#4096ee;
	color:#fff;
}

::-moz-selection {
	background:#4096ee;
	color:#fff;
}

::-webkit-selection {
	background:#4096ee;
	color:#fff;
}

.footer {
    display:block;
    position: fixed;
    bottom: 0;
    height: 25px;
    width: 100%;
	padding: 2px;
	margin: 0;
	background-color: #ddd;
	border :0;
	box-sizing: border-box;
	text-align:center;
	font-weight:bold;
}

.success { color:green}
.error {color:red}
.warning {color:orange}
/******************************************************************
General CSS
******************************************************************/
*, div, strong {
	font-family: 'PT Sans', Verdana, Arial, sans-serif;
}

p {
	font-family: Verdana, Arial, sans-serif;
	line-height: 1.6em;
	color: #616161;
	font-size: 10px;
}
h1 {
	font-family: 'PT Sans', Verdana, Arial, sans-serif;
	font-weight: bold;
	line-height: 1.6em;
	color: #616161;
	text-decoration: none;
	font-size: 20px;
}
h2 {
	font-family: 'PT Sans', Verdana, Arial, sans-serif;
	line-height: 1.6em;
	font-weight: bolder;
	color: #616161;
	text-decoration: none;
	font-size: 16px
}
h3 {
	font-family: 'PT Sans', Verdana, Arial, sans-serif;
	line-height: 1.6em;
	color: #616161;
	text-decoration: none;
	font-size: 14px;
}
h4 {
	font-family: 'PT Sans', Verdana, Arial, sans-serif;
	line-height: 1.6em;
	color: #616161;
	text-decoration: none;
	font-size: 12px;
}
h5 {
	font-family: 'PT Sans', Verdana, Arial, sans-serif;
	line-height: 1.6em;
	color: #ababab;
	text-decoration: none;
	font-size: 10px;
}
h6 {
	font-family: 'PT Sans', Verdana, Arial, sans-serif;
	line-height: 1.6em;
	color: #ababab;
	text-decoration: none;
	font-size: 8px;
}
/******************************************************************
Main CSS
******************************************************************/
img.grayscale {
    filter: url("data:image/svg+xml;utf8,<svg xmlns=\'http://www.w3.org/2000/svg\'><filter id=\'grayscale\'><feColorMatrix type=\'matrix\' values=\'0.3333 0.3333 0.3333 0 0 0.3333 0.3333 0.3333 0 0 0.3333 0.3333 0.3333 0 0 0 0 0 1 0\'/></filter></svg>#grayscale"); /* Firefox 10+, Firefox on Android */
    filter: gray; /* IE6-9 */
    -webkit-filter: grayscale(100%); /* Chrome 19+, Safari 6+, Safari 6+ iOS */
}
div#main {
	width: 468px;
	margin: 20px auto 20px auto;
}
.title {
	line-height: 1.2em;
	position: relative;
}
div.icon {
	margin-top: 4px;
	float: left;
	width: 31px;
	height: 30px;
	background-image: url(magnify.gif);
	background-repeat: no-repeat;
	-webkit-transition-property: background-position, color;
	-webkit-transition-duration: .2s, .1s;
	-webkit-transition-timing-function: linear, linear;
	-moz-transition-property: background-position, color;
	-moz-transition-duration: .2s, .1s;
	-ms-transition-duration: .2s, .1s;
	-ms-transition-timing-property: linear, linear;
	-o-transition-property: background-position, color;
	-o-transition-duration: .2s, .1s;
	-o-transition-timing-property: linear, linear;
	transition-property: background-position, color;
	transition-duration: .2s, .1s;
	transition-timing-property: linear, linear;
	float: right;
}
div.icon:hover {
	background-position: 0px -30px;
	cursor: pointer;
}
input#search {
	width: 420px;
	height: 25px;
	padding: 5px;
	margin-bottom: 15px;
	-webkit-border-radius: 2px;
	-moz-border-radius: 2px;
	border-radius: 2px;
	outline: none;
	border: 1px solid #ababab;
	font-size: 20px;
	line-height: 25px;
	color: #ababab;
	float: left;
}
input#search:hover, input#search:focus {
	color: #3b3b3b;
	border: 1px solid #36a2d2;
	-moz-box-shadow: 0 1px 3px rgba(0, 0, 0, 0.25) inset, 0 1px 0 rgba(255, 255, 255, 1);
	-webkit-box-shadow: 0 1px 3px rgba(0, 0, 0, 0.25) inset, 0 1px 0 rgba(255, 255, 255, 1);
	box-shadow: 0 1px 3px rgba(0, 0, 0, 0.25) inset, 0 1px 0 rgba(255, 255, 255, 1);
}
h4#results-text {
	visibility:hidden
}
ul#results {
	display: none;
	width: 468px;
	margin-top: 4px;
	border: 1px solid #ababab;
	-webkit-border-radius: 2px;
	-moz-border-radius: 2px;
	border-radius: 2px;
	-webkit-box-shadow: rgba(0, 0, 0, .15) 0 1px 3px;
	-moz-box-shadow: rgba(0,0,0,.15) 0 1px 3px;
	box-shadow: rgba(0, 0, 0, .15) 0 1px 3px;
}
ul#results li {
	padding: 8px;
	cursor: pointer;
	border-top: 1px solid #cdcdcd;
	transition: background-color .3s ease-in-out;
	-moz-transition: background-color .3s ease-in-out;
	-webkit-transition: background-color .3s ease-in-out;
	overflow: hidden;
}
ul#results li.result:hover {
	background-color: #F7F7F7;
	background-image:url(sab.png) ;
	background-size: 70px 70px;
	background-position: right center;
	background-repeat:no-repeat;
}
ul#results li.wishlist:hover img{
    filter: none;
    -webkit-filter: grayscale(0%); /* Chrome 19+, Safari 6+, Safari 6+ iOS */
}

ul#results li.wishlist:hover {
	background-color: #F7F7F7;
	background-image:url(reload.png) ;
	background-size: 70px 70px;
	background-position: right center;
	background-repeat:no-repeat;
}
ul#results li:first-child {
	border-top: none;
}
ul#results li h2, ul#results li h3, ul#results li h4 {
	transition: color .3s ease-in-out;
	-moz-transition: color .3s ease-in-out;
	-webkit-transition: color .3s ease-in-out;
	color: #616161;
	padding-left : 5px;
	float: left;
	width: 70%;
}
ul#results li img {
	height: 70px;
	float: left;
}
ul#results li:hover h3, ul#results li:hover h4  {
	color: #3b3b3b;
	font-weight: bold;
}

#types, #langs {
	display: block;
	float: none;
	clear: both;
}

strong {
	font-weight: bold;
}

</style>
	</body>
</html>