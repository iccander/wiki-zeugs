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
	$Q='';		// 1.) in Lobid-Datensatz 
	$result=lobid($GND);
	if (is_array($result["sameAs"]) && $result["sameAs"]) {
		foreach($result["sameAs"] as $ids ){
			if (($pos=strpos($ids["id"],'wikidata.org')) !== false) {
				$Q=substr($ids["id"],$pos+20);
				break;}
			if (($pos=strpos($ids["id"],'viaf.org')) !== false) $viaf=quote(substr($ids["id"],$pos+14));
		}
	}             // 2.) alternativ in Wikidata 
	if ($Q=='') $Q=sparqlGND($GND, $viaf);
	if (($Q=='') or empty($Q)) {	// 3.) alternativ in Frankreich, USA 
		if (is_array($result["closeMatch"]) && $result["closeMatch"]) {
			foreach($result["closeMatch"] as $ids ){
				$auskunft='';
				if (($pos=strpos($ids["id"],'data.bnf.fr/ark')) !== false) $ausk=jsonstring($ids["id"].".rdfjsonld");
				if (($pos=strpos($ids["id"],'id.loc.gov/auth')) !== false) $ausk=jsonstring($ids["id"].".json");
				if (preg_match('/wikidata\.org\/entity\/(Q\d+)/', $ausk, $treffer)) {
					$Q=$treffer[1];
					break;
				}
			}
		}
	}
	return $Q;
}
?>
