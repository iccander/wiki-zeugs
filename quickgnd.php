<!DOCTYPE html>
<html lang="de"><head><title>quickgnd</title><meta charset="utf-8">
<meta name="description" content=""><meta name="author" content="FR">
<meta http-equiv="Content-type" content="text/html; charset=utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1">
<script src="https://tools-static.wmflabs.org/cdnjs/ajax/libs/jquery/2.2.0/jquery.min.js"></script>
<script type="text/javascript" src="qs.js"></script></head><body><p>
<img src="https://upload.wikimedia.org/wikipedia/commons/8/8e/Logo_Gemeinsame_Normdatei_%28GND%29.svg" width="50" height="50" alt="GND" lang="en" loading="lazy" align="middle">
<img src="https://upload.wikimedia.org/wikipedia/commons/a/ac/Tabler-icons_arrow-big-right-lines-filled.svg" alt="=>" lang="en" loading="lazy" align="middle"> 
<img src="https://upload.wikimedia.org/wikipedia/commons/c/c7/Commons_to_Wikidata_QuickStatements.svg" height="50" alt="Quickstatements" lang="en" loading="lazy" align="middle"> 
<img src="https://upload.wikimedia.org/wikipedia/commons/a/ac/Tabler-icons_arrow-big-right-lines-filled.svg" alt="=>" lang="en" loading="lazy" align="middle"> 
<img src="https://upload.wikimedia.org/wikipedia/commons/6/66/Wikidata-logo-en.svg" height="50" alt="Wikidata" lang="en" loading="lazy" align="middle"></p>
<?php 
include_once 'queries.php';
echo '<form action="'.htmlspecialchars($_SERVER["PHP_SELF"]).'" method="get"><b>GND:</b> &nbsp; <input type="text" name="gnd" style="width: 200px" value="'; 
if(isset($_REQUEST['gnd'])) echo ($gnd=trim($_REQUEST['gnd']));
?>
"> &nbsp; 
<input type="image" src="https://upload.wikimedia.org/wikipedia/commons/8/83/Wikidata-check.svg" height="45" alt="Submit" >
<br /><br /></form>
<?php
if (isset($_REQUEST['gnd'])) $lobid=lobid($gnd); 
if (!empty($lobid["type"][0])) { // wenn GND gültig/vorhanden

// Auslesen spezieller Eigenschaften insb. Namen
$vornamen=explode(' ',$lobid["preferredNameEntityForThePerson"]["forename"][0]);
$famname=$lobid["preferredNameEntityForThePerson"]["surname"][0];

function flip(string $fullname): string
    {   // Bsp.: "Schwerin, Johann Christoph Herrmann, Graf von"
		if (($p=strpos($fullname,', ')) !== false) {
			return str_replace(',','',substr($fullname,$p+2)).' '.substr($fullname,0,$p);}
		else return $fullname; // wenn Name ohne Komma
    }
$label = flip($lobid["preferredName"]);

if (!empty($lobid["biographicalOrHistoricalInformation"][0])) $item['Dde'][0]=quote($lobid["biographicalOrHistoricalInformation"][0]);

foreach($lobid["variantName"] as $alias) {
	if (empty($item['Ade'])) {$item['Ade'][]=flip($alias);}
    else {$item['Ade'][0].="|".flip($alias);}
}
if (!empty($item['Ade'][0])) $item['Ade'][0]=quote($item['Ade'][0]);

foreach($lobid["pseudonym"] as $pseudonym) {
	if (!empty($pseudonym["label"])) $item['P742'][]=quote(flip($pseudonym["label"]));
}
if (!empty($lobid["academicDegree"])) {
	if ((strpos($lobid["academicDegree"][0],'Prof.')) !== false) {$item['P512'][]="Q121594";}
	if ((strpos($lobid["academicDegree"][0],'Dipl.-Ing.')) !== false) {$item['P512'][]="Q25929244";}
	if ((strpos($lobid["academicDegree"][0],'Mag.')) !== false) {$item['P512'][]="Q1589434";}
	if ((strpos($lobid["academicDegree"][0],'Master')) !== false) {$item['P512'][]="Q183816";}
	if ((strpos($lobid["academicDegree"][0],'Dr.')) !== false) {
		$dr="Q4618975";
		if ((strpos($lobid["academicDegree"][0],'med.')) !== false) $dr="Q913404";
		if ((strpos($lobid["academicDegree"][0],'med. dent.')) !== false) $dr="Q12198834";
		if ((strpos($lobid["academicDegree"][0],'rer. nat.')) !== false) $dr="Q503765";
		if ((strpos($lobid["academicDegree"][0],'Ing.')) !== false) $dr="Q1253774";
		if ((strpos($lobid["academicDegree"][0],'phil.')) !== false) $dr="Q46996674";
		if ((strpos($lobid["academicDegree"][0],'iur.')) !== false) $dr="Q959320";
		if ((strpos($lobid["academicDegree"][0],'jur.')) !== false) $dr="Q959320";
		if ((strpos($lobid["academicDegree"][0],'agr.')) !== false) $dr="Q11408914";
		$item['P512'][]=$dr;
	}
}
// Geschlecht
$m='Q6581097';
$w='Q6581072';
switch (substr(strrchr($lobid["gender"][0]["id"], "#"),1)){
    case 'male': $item['P21'][0]=$m;
        break;
    case 'female': $item['P21'][0]=$w;
       // break;
}
// Lebensdaten
$tag='T00:00:00Z/11';
$jahr='-00-00T00:00:00Z/9';
if (!empty($lobid["dateOfBirth"][0])) {
	if (strlen($lobid["dateOfBirth"][0])==4)  $item['P569'][0]='+'.$lobid["dateOfBirth"][0].$jahr;
	if (strlen($lobid["dateOfBirth"][0])==10) $item['P569'][0]='+'.$lobid["dateOfBirth"][0].$tag;}
if (!empty($lobid["dateOfDeath"][0])) {
	if (strlen($lobid["dateOfDeath"][0])==4)  $item['P570'][0]='+'.$lobid["dateOfDeath"][0].$jahr;
	if (strlen($lobid["dateOfDeath"][0])==10) $item['P570'][0]='+'.$lobid["dateOfDeath"][0].$tag;}

// Wirkungsdaten bislang nur; 1880-1890; 1880
// ohne Sonderfälle: ca. 1880-1890; 1880- usw.
// https://lobid.org/gnd/1071745344, https://lobid.org/gnd/12891422X, 2x - https://lobid.org/gnd/135461710X
if (strlen(trim($lobid["periodOfActivity"][0]))==4) {
	$item['P1317'][]='+'.trim($lobid["periodOfActivity"][0]).$jahr;} 
else {$wirkung = explode('-',$lobid["periodOfActivity"][0]);}
if (!empty($wirkung[1])) {
	if (strlen(trim($wirkung[1]))==4) $item['P2032'][]='+'.trim($wirkung[1]).$jahr;
	if (strlen(trim($wirkung[0]))==4) $item['P2031'][]='+'.trim($wirkung[0]).$jahr;
}
// Lobid-Kennung => Wikidata Property
// https://d-nb.info/standards/elementset/gnd#acquaintanceshipOrFriendship
$map=['placeOfBirth'=>'P19',
'placeOfDeath'=>'P20',
'placeOfActivity'=>'P937',
'titleOfNobility'=>'P97',
'professionOrOccupation'=>'P106',
'fieldOfStudy'=>'P812',
'hasChild'=>'P40',
'hasSibling'=>'P3373',
'hasAuntUncle'=>'P1038', // evt. noch + Qualifier https://www.wikidata.org/wiki/Property:P1039
'familialRelationship'=>'P1038',
'hasSpouse'=>'P26',
'hasParent'=>'P22', // => Vater (Mutter wäre P25)
'professionalRelationship'=>'P1327',
'acquaintanceshipOrFriendship'=>'P3342',
'affiliation'=>'P1416',
'functionOrRole'=>'P39'];

foreach (array_keys($map) as $key) {
	foreach($lobid[$key] as $prop){
		$i=lookup(substr(strrchr($prop["id"], "/"),1));
		if (!empty($i)) $item[$map[$key]][]=$i;
	}
}
// 1 Homepage
if (!empty($lobid["homepage"][0]["id"])) $item['P973'][0]=quote($lobid["homepage"][0]["id"]);

// Sprache
foreach($lobid["languageCode"]as $spr){
   if (($p=strpos($spr["id"],'iso639-2'))!==false){
	   $iso=substr($spr["id"],$p+9);
	   switch($iso){
			case 'ger': $item['P1412'][]="Q188";
				break;
			case 'eng': $item['P1412'][]="Q1860";
				break;
			case 'fre': $item['P1412'][]="Q150"; 
				break;
			default: 
				$query='SELECT DISTINCT (STRAFTER(STR(?u),"y/") AS ?q) WHERE {?u wdt:P219 "'.$iso.'"}';
				$item['P1412'][]=sparql($query)['q']['value'];
		}
	}
}
// VIAF, ISNI und – ganz wichtig ! – Q-ID aus Lobid auslesen
$item['P227'][0]=quote($gnd);
foreach($lobid["sameAs"]as $ids ){
	if (($viaf=strpos($ids["id"],'viaf.org')) !== false) {$item['P214'][0]=quote(substr($ids["id"],$viaf+14));} else {
		if (($wiki=strpos($ids["id"],'wikidata.org')) !== false) {$qid=substr($ids["id"],$wiki+20);} else {
			if (($isni=strpos($ids["id"],'isni.org')) !== false) {$item['P213'][0]=quote(substr($ids["id"],$isni+14));} else {
				if ((strpos($ids["id"],'kalliope-verbund.info')) !== false) {$item['P9964'][0]=quote($gnd);} else {
						if ((strpos($ids["id"],'deutsche-digitale-bibliothek.de')) !== false) {$item['P13049'][0]=quote($gnd);} 
				}
			}
		}
	}
} // ggf noch: http://id.loc.gov/rwo/agents/n2009063190 --> P244
// http://catalogue.bnf.fr/ark:/12148/cb15354728p --> P268 : 15354728p
 
// Wenn in Lobid keine Q-ID, dann in Wikidata über GND/VIAF suchen 
if (empty($qid)) $qid=sparqlGND($gnd, $item['P214'][0]);

// Q-ID des Familiennamen - erstmal nur ohne Adelstitel "von"
$query='SELECT DISTINCT (STRAFTER(STR(?u),"y/") AS ?q) WHERE {?u rdfs:label "'.$famname.'"@de; wdt:P31 ?s.?s (wdt:P279*) wd:Q101352} LIMIT 1';
$item['P734'][0]=sparql($query)['q']['value'];

$g=0;  // Q-ID der Vornamen abfragen und Geschlecht erschnüffeln
foreach ($vornamen as $vorname){
	$query='SELECT DISTINCT (STRAFTER(STR(?v),"y/") AS ?q) (STRAFTER(STR(?s),"y/") AS ?g) WHERE {?v rdfs:label "'.$vorname.'"@de;wdt:P31 ?s.?s(wdt:P279*) wd:Q202444 }';
	$data =sparql($query);
	$item['P735'][]=$data['q']['value'];
	if (empty($item['P21'][0])) { // nur wenn in GND nicht belegt, was durchaus vorkommt
		switch ($data['g']['value']) {
			case 'Q11879590': //weibl. Vorname
				$g++;
				break;
			case 'Q12308941':  //männl. Vorname
				$g--;
			//	break;
		}	
	}
}
if ($g<0) $item['P21'][0]=$m; // kein Überschreiben, da nur größer
if ($g>0) $item['P21'][0]=$w; // wenn einmal durchlaufen, d.h. unbelegt

// Ausgabe der Statements

if (empty($qid)){
	$qs="CREATE\n"; // wenn nicht in Wikidata, dann anlegen
	$ref="LAST\t";   // Referenz auf neu angelegtes
} else {$ref="{$qid}\t";} // oder auf Q-ID

$qs.="{$ref}Lmul\t\"{$label}\"\n{$ref}Lde\t\"{$label}\"\n"; //mul: "Standard für alle Sprachen"
if (empty($qid)) $qs.="{$ref}P31\tQ5\n"; // Mensch nur bei neuem Eintrag

$gndheute="\tS248\tQ36578\tS227\t\"{$gnd}\"\tS813\t+".date('Y-m-d').$tag."\n";

foreach (array_keys($item) as $key) {
	if (!empty($item[$key])) {
		foreach ($item[$key] as $i) {
			if (!empty($i)) {
				// Ausgabe des Statements
				$qs.= "{$ref}{$key}\t{$i}";
				switch ($key){
					// und der Quelle
					case 'Dde':  // Beschr. dt.
					case 'Ade':  // Alias dt.	
					case 'P734': // Familienn.
					case 'P735': // Vorn.
						$qs.="\n"; // ohne Quellenangabe
						break;
					case 'P21': 
						if ($g<>0) {$qs.="\tS887\tQ69652498\n";} // anh. Vornamen vermutetes Geschlecht
						else {$qs.=$gndheute;} 
						break;
					case 'P3342': // ohne break + default
						$qs.="\tP3831\tQ17297777"; // fungiert als: Freund
					default: $qs.=$gndheute;}
			}
		}
	}
}	
} else {$qs="Kleine Spielerei mit GND (für Personen!) und QuickStatements (QS) im Betastadium.\n\n".
		"Ist die eingegebene GND oder korrespondierende VIAF noch nicht in Wikidata vorhanden, wird eine Neuanlage für QS erzeugt. Ein Klick auf das große + schickt die Daten unmittelbar an Quickstatements.\n\n".
		"Vorher bitte immer prüfen, ob es den Namenseintrag in Wikidata vielleicht schon gibt, ohne dass ihm bislang ein GND- oder VIAF-Eintrag zugeordnet ist. ".
		"Ist dies der Fall, dann einfach in Wikidata die GND hinzufügen und eine halbe Minute warten. Danach erzeugt der Aufruf dieses Tools statt eines Neueintrags eine Ergänzung um die in der GND vorhandenen Elemente.\n\n". 
		"Bei vorhandenem Wikidata-Eintrag werden bereits vorhandene Parameter (P1234 ...) von QuickStatements im Regelfall übrigens nicht überschrieben, sondern nur um die Quellenangabe (hier GND) ergänzt. ".
		"Einzige Ausnahme: Label (Lde, Lmul), Beschreibung (Dde) und Alias (Ade) werden durch QS überschrieben. Hier ist also größte Vorsicht geboten und es sollten vorm Senden an QS die betreffenden Zeilen aus dem Textfeld gelöscht werden, wenn kein Überschreiben gewünscht ist!";
}
?><form><textarea id='quickstatement' style="height:375px;width:800px;overflow:scroll;"><?php echo($qs); ?></textarea><br /><br />
<a onclick="sendQS()" style="cursor: pointer;"><img src="https://upload.wikimedia.org/wikipedia/commons/b/bc/Plus_Wikidata.svg" height="50" alt="Wikidata-Plus" lang="en" loading="lazy"></a>
</form></body></html>