<?php
function quote($quote){return '"'.$quote.'"';
} 
function sparqlfeld($query){
    $opts=['http'=>['method'=>'GET','header'=>["Accept: application/sparql-results+json\r\nUser-Agent: curl/7.79.1\r\n"],],];
    $response=file_get_contents('https://query.wikidata.org/sparql?query='.urlencode($query),false,stream_context_create($opts));
    return json_decode($response,true)['results']['bindings'];
}
function sparql($query){
    return sparqlfeld($query)[0];
}
function jsonstring($URL){
	$ch=curl_init($URL);
	$useragent = 'User-Agent: curl/7.79.1';
	curl_setopt($ch,CURLOPT_USERAGENT,$useragent);
	curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
	$response=curl_exec($ch);
	curl_close($ch);
	return $response;
}
function lobid($GND){
	return json_decode(jsonstring("https://lobid.org/gnd/".$GND.".json"),true);
}
function sparqlGND($gnd,$viaf){
	if (!empty($viaf)) {$query ='{?u wdt:P227 "'.$gnd.'"} UNION {?u wdt:P214 '.$viaf.'}} GROUP BY ?u LIMIT 1';}
	else {$query ='?u wdt:P227 "'.$gnd.'"}';}
	return sparql('SELECT DISTINCT (STRAFTER(STR(?u),"y/") AS ?q) WHERE {'.$query)['q']['value'];
}
function lookup($GND){
	$result=lobid($GND);	// 1.) in Lobid-Datensatz 
	if (is_array($result["sameAs"]) && $result["sameAs"]) {
		foreach($result["sameAs"] as $ids ){
			if (($pos=strpos($ids["id"],'wikidata.org')) !== false) {
				$Q=substr($ids["id"],$pos+20);
				break;}
			if (($pos=strpos($ids["id"],'viaf.org')) !== false) $viaf=quote(substr($ids["id"],$pos+14));
			if (($pos=strpos($ids["id"],'sws.geonames.org')) !== false) $geoname=substr($ids["id"],$pos+17);
		}
	}	// 2. Geografika ohne QID in Lobid via geonames.org matchen
	if ((empty($Q)) AND (!empty($geoname))) $Q=sparql('SELECT (STRAFTER(STR(?u),"y/") AS ?q) WHERE {?u wdt:P1566 "'.$geoname.'"}')['q']['value'];
	if (empty($Q)) $Q=sparqlGND($GND, $viaf); 	// 3.) QID alternativ aus Wikidata 
	if (empty($Q)) {	// 4.) QID alternativ aus Frankreich & USA 
		if (is_array($result["closeMatch"]) && $result["closeMatch"]) {
			foreach($result["closeMatch"] as $ids ){
				$auskunft='';
				if (($pos=strpos($ids["id"],'data.bnf.fr/ark')) !== false) $ausk=jsonstring($ids["id"].".rdfjsonld");
				if (($pos=strpos($ids["id"],'id.loc.gov/auth')) !== false) $ausk=jsonstring($ids["id"].".json");
				if (preg_match('/wikidata\.org\/entity\/(Q\d+)/',$ausk,$treffer)){
					$Q=$treffer[1]; 	
					break;
				}
			}
		}
	}
	return $Q;
}
?>
