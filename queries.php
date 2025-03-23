<?php
function quote($quote){return '"'.$quote.'"';
}
function sparql($query){
    $opts=['http'=>['method'=>'GET','header'=>["Accept: application/sparql-results+json\r\nUser-Agent: curl/7.79.1\r\n"],],];
    $response=file_get_contents('https://query.wikidata.org/sparql?query='.urlencode($query),false,stream_context_create($opts));
    return json_decode($response,true)['results']['bindings'][0];
}
function lobid($GND){
	$ch=curl_init("https://lobid.org/gnd/".$GND.".json");
	curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
	$response=curl_exec($ch);
	curl_close($ch);
	return json_decode($response,true);
}
function sparqlGND($gnd,$viaf){
	if (!empty($viaf)) {$query ='{?u wdt:P227 "'.$gnd.'"} UNION {?u wdt:P214 '.$viaf.'}} GROUP BY ?u LIMIT 1';}
	else {$query ='?u wdt:P227 "'.$gnd.'"}';}
	return sparql('SELECT DISTINCT (STRAFTER(STR(?u),"y/") AS ?q) WHERE {'.$query)['q']['value'];
}
function lookup($GND){
	$Q='';
	foreach(lobid($GND)["sameAs"] as $ids ){
		if (($pos=strpos($ids["id"],'wikidata.org')) !== false) {
			$Q=substr($ids["id"],$pos+20);
			break;}
		if (($pos=strpos($ids["id"],'viaf.org')) !== false) $viaf=quote(substr($ids["id"],$pos+14));
	}
	if ($Q=='') $Q=sparqlGND($GND, $viaf);
	return $Q;
}
?>
