<!DOCTYPE html>
<html lang="de"><head><title>QuickGND</title>
<meta charset="utf-8">
<meta name="description" content=""><meta name="author" content="FR">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" type="text/css" href="https://tools-static.wmflabs.org/static/jquery-ui/1.11.1/jquery-ui.min.css">
<script src="https://tools-static.wmflabs.org/cdnjs/ajax/libs/jquery/2.2.0/jquery.min.js"></script>
<script src="https://tools-static.wmflabs.org/static/jquery-ui/1.11.1/jquery-ui.min.js"></script>
<script type="text/javascript" src="scripts.js" defer></script>
<style>label {display:block;padding-top:20px;font-family: sans-serif;}</style></head><body><p>
<img src="https://upload.wikimedia.org/wikipedia/commons/8/8e/Logo_Gemeinsame_Normdatei_%28GND%29.svg" width="50" height="50" alt="GND" lang="en" loading="lazy" align="middle">
<img src="https://upload.wikimedia.org/wikipedia/commons/a/ac/Tabler-icons_arrow-big-right-lines-filled.svg" alt="=>" lang="en" loading="lazy" align="middle"> 
<img src="https://upload.wikimedia.org/wikipedia/commons/c/c7/Commons_to_Wikidata_QuickStatements.svg" height="50" alt="Quickstatements" lang="en" loading="lazy" align="middle"> 
<img src="https://upload.wikimedia.org/wikipedia/commons/a/ac/Tabler-icons_arrow-big-right-lines-filled.svg" alt="=>" lang="en" loading="lazy" align="middle"> 
<img src="https://upload.wikimedia.org/wikipedia/commons/6/66/Wikidata-logo-en.svg" height="50" alt="Wikidata" lang="en" loading="lazy" align="middle"></p>
<?php 
include_once 'queries.php';
$missing = [];  $item=[]; $wirkung=[]; $qs='';
echo '<form action="'.htmlspecialchars($_SERVER["SCRIPT_NAME"]).'" method="GET">';
echo '<div style="float:left;margin-right:15px;">';
echo '<label for="person">Person in GND</label>'; 
echo '<input type="text" id="person" class="search-gnd" style="width:250px" placeholder="suchen"/></div>';
echo '<div style="float:left;margin-right:15px;"><label for="id">GND-ID</label><input type="text" name="gnd" id="id" style="width: 80px" '; 
if(isset($_REQUEST['gnd'])) {
	echo 'value="'.($gnd=trim($_REQUEST['gnd'])).'">';
} else {
	echo 'placeholder="eingeben">';
} ?>
</div>
<input type="image" src="https://upload.wikimedia.org/wikipedia/commons/8/83/Wikidata-check.svg" height="45" alt="Submit" style="float:left;padding-top:15px;margin-right:35px;">
<?php
if (isset($_REQUEST['gnd'])) $lobid=lobid($gnd); 
if (!empty($lobid["type"][0])) { // wenn GND gültig/vorhanden

// Auslesen spezieller Eigenschaften insb. Namen
$vornamen = preg_split('/\s+/', trim($lobid["preferredNameEntityForThePerson"]["forename"][0] ?? ''), -1, PREG_SPLIT_NO_EMPTY);
$famname  = trim($lobid["preferredNameEntityForThePerson"]["surname"][0] ?? '');

function flip(string $fullname): string
    {   // Bsp.: "Schwerin, Johann Christoph Herrmann, Graf von"
		if (($p=strpos($fullname,', ')) !== false) {
			return str_replace(',','',substr($fullname,$p+2)).' '.substr($fullname,0,$p);}
		else return $fullname; // wenn Name ohne Komma
    }
$label = flip($lobid["preferredName"] ?? '');

if ($info = array_filter((array)($lobid["biographicalOrHistoricalInformation"] ?? [])))
    $item['Dde'][0] = quote(implode(', ', $info));
// Implode statt Schleife! Bsp.: https://lobid.org/gnd/107830498X.json

foreach (($lobid["variantName"]??[]) as $alias) $item["Ade"][]=quote(flip($alias));

foreach (($lobid["pseudonym"]??[]) as $pseudonym) {
	if (!empty($pseudonym["label"])) $item['P742'][]=quote(flip($pseudonym["label"]));
}
if (!empty($lobid["academicDegree"][0])) { // https://lobid.org/gnd/118803050.json  
    $grad = $lobid["academicDegree"][0];
    if (str_contains($grad,'Dipl.-Ing.')) $item['P512'][] = "Q25929244";
    if (str_contains($grad,'Mag.')) $item['P512'][] = "Q1589434";
    if (str_contains($grad,'Master')) $item['P512'][] = "Q183816";
    if (str_contains($grad,'Dr.')) { 
        $dr = "Q4618975"; 
        if (str_contains($grad,'med. dent.')) $dr = "Q12198834"; 
        elseif (str_contains($grad,'med.')) $dr = "Q913404"; 
        if (str_contains($grad,'rer. nat.')) $dr = "Q503765"; 
        if (str_contains($grad,'Ing.')) $dr = "Q1253774"; 
        if (str_contains($grad,'phil.')) $dr = "Q46996674"; 
        if (str_contains($grad,'iur.') || str_contains($grad,'jur.')) $dr = "Q959320"; 
        if (str_contains($grad,'agr.')) $dr = "Q11408914"; 
        $item['P512'][] = $dr; 
    } 
}
// Geschlecht
$m='Q6581097';
$w='Q6581072';
switch (substr(strrchr($lobid["gender"][0]["id"] ?? '', "#"),1)){
    case 'male': $item['P21'][0]=$m;
        break;
    case 'female': $item['P21'][0]=$w;
        break;
}
// Lebensdaten
$tag='T00:00:00Z/11';
$jahr='-00-00T00:00:00Z/9';
foreach (['dateOfBirth' => 'P569', 'dateOfDeath' => 'P570'] as $field => $prop) {
    $len  = strlen($date = $lobid[$field][0] ?? '');
    if ($len === 4)  $item[$prop][0] = '+' . $date . $jahr;
    if ($len === 10) $item[$prop][0] = '+' . $date . $tag;
}
if (($period = trim($lobid["periodOfActivity"][0] ?? '')) !== '') {
    strlen($period) === 4
        ? $item['P1317'][]='+'.$period . $jahr
        : $wirkung = preg_split('/\s*-\s*/', $period);
}
if (!empty($wirkung[1])) {
	if (strlen($wirkung[1])===4) $item['P2032'][]='+'.$wirkung[1].$jahr;
	if (strlen($wirkung[0])===4) $item['P2031'][]='+'.$wirkung[0].$jahr;
}
// Lobid-Kennung => Wikidata Property, mit GND-IDs --> z.B. $lobid["placeOfBirth"][0]["id"]
// https://d-nb.info/standards/elementset/gnd#acquaintanceshipOrFriendship
$map=['placeOfBirth'=>'P19',
'placeOfDeath'=>'P20',
'placeOfActivity'=>'P937',
'titleOfNobility'=>'P97',
'professionOrOccupation'=>'P106', //z.B. https://lobid.org/gnd/13732569X
'fieldOfStudy'=>'P812',  //z.B. https://lobid.org/gnd/1311448969
'hasChild'=>'P40', // z.B. https://lobid.org/gnd/1136749489
'hasSibling'=>'P3373',
'hasAuntUncle'=>'P1038', // evt. noch + Q https://www.wikidata.org/wiki/Property:P1039
'familialRelationship'=>'P1038',
'hasSpouse'=>'P26',
'professionalRelationship'=>'P1327',
'hasColleague'=>'P1327',  // same, https://d-nb.info/standards/elementset/agrelon#hasColleague
'acquaintanceshipOrFriendship'=>'P3342', // https://lobid.org/gnd/135461710X
'hasFriend'=>'P3342',  // same, https://d-nb.info/standards/elementset/agrelon#hasFriend
'memberOfTheFamily'=>'P53',
'relatedWork'=>'P800', // bedeutendes Werk (P800) = relevantes wissenschaftliches oder künstlerisches Werk des Subjekts
'playedInstrument'=>'P1303',  // https://d-nb.info/standards/elementset/gnd#playedInstrument
'affiliation'=>'P1416',
'relatedCorporateBody'=>'P1416', //same, https://d-nb.info/standards/elementset/gnd#relatedCorporateBody
'functionOrRole'=>'P39',
'hasTeacher'=>'P1066', // WD: student of, https://d-nb.info/standards/elementset/agrelon#hasTeacher 
'hasStudent'=>'P802', // https://d-nb.info/standards/elementset/agrelon#hasStudent
'functionOrRole'=>'P39',
'hasParent'=>'ELTERN', // geschlechtsspezifische Behandlung erforderlich
];
$gnds = [];  // gebündelte SPARQL-Abfrage aller in $map definierten GNDs auf einmal
foreach (array_keys($map) as $key) {
	foreach (($lobid[$key] ?? []) as $prop) {
		$lobidGND = substr(strrchr($prop["id"], "/"), 1);
		$lobidGND && $gnds[$map[$key]][] = $lobidGND;
	}
}
$flatGnds = !empty($gnds) ? array_merge(...array_values($gnds)) : [];   // GNDs aus $gnds flachziehen
$allgnds = array_unique([...$flatGnds, $gnd]); // Dubletten entfernen; Qid der eigenen GND in Wikidata gleich mit nachschlagen
$table = sparqlfeld('SELECT DISTINCT (STRAFTER(STR(?u), "/entity/") AS ?qid) ?gnd WHERE { VALUES ?gnd { "'.implode('" "',$allgnds).'" } ?u p:P227 ?g . ?g ps:P227 ?gnd .}');
$lookup = [];
foreach ($table as $row) if (!empty($row['gnd']['value']) && !empty($row['qid']['value'])) {
	$lookup[$row['gnd']['value']] = $row['qid']['value'];
}
foreach ($gnds as $property => $values) { // GNDs den jeweiligen Wikidata-Eigenschaften zuordnen
    foreach ($values as $lobidGND) {
        if (!empty($lookup[$lobidGND])) { 
            $targetQid = $lookup[$lobidGND];
            if ($property === 'ELTERN') {
                $g = sparql('SELECT (STRAFTER(STR(?sex), "y/") AS ?q) WHERE { wd:'.$targetQid.' wdt:P21 ?sex }')['q']['value'] ?? '';
                $item[$g === $w ? 'P25' : 'P22'][] = $targetQid;  // Mutter oder Vater (Fallback)
            } else $item[$property][] = $targetQid; 
        } else $missing[] = $lobidGND;
	}
}
// Homepages
$urls = array_column((array)($lobid['homepage'] ?? []), 'id');
$item['P973'] = array_map('quote', array_filter($urls, 'is_string'));

// Sprache
foreach(($lobid["languageCode"] ?? []) as $spr){
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
				$query='SELECT (STRAFTER(STR(?u),"y/") AS ?q) WHERE {?u wdt:P219 "'.$iso.'"}';
				$item['P1412'][]=sparql($query)['q']['value'];
		}
	}
}
// VIAF, ISNI, ORCID und – ganz wichtig ! – Q-ID aus Lobid auslesen
$item['P227'][0]=quote($gnd);
foreach(($lobid["sameAs"] ?? []) as $ids ){
	if (($pos=strpos($ids["id"],'viaf.org')) !== false) {$item['P214'][0]=quote(substr($ids["id"],$pos+14));} else {
		if (($pos=strpos($ids["id"],'wikidata.org')) !== false) {$qid=substr($ids["id"],$pos+20);} else {
			if (($pos=strpos($ids["id"],'isni.org')) !== false) {$item['P213'][0]=quote(substr($ids["id"],$pos+14));} else {
				if ((strpos($ids["id"],'kalliope-verbund.info')) !== false) {$item['P9964'][0]=quote($gnd);} else {
					if ((strpos($ids["id"],'deutsche-digitale-bibliothek.de')) !== false) {$item['P13049'][0]=quote($gnd);} else {
						if ((strpos($ids["id"],'lagis-hessen')) !== false) {$item['P13226'][0]=quote($gnd);} else {
							if (($pos=strpos($ids["id"],'orcid.org')) !== false) {$item['P496'][0]=quote(substr($ids["id"],$pos+10));}
						}
					}
				}
			}
		}
	}
}
// Wenn in Lobid keine Q-ID, dann in Wikidata über GND/VIAF suchen 
if (empty($qid)) $qid = $lookup[$gnd];  // schon in Gesamtabruf mit nachgeschlagen!
if (empty($qid)) $qid=sparqlGND($gnd, $item['P214'][0]);  // nur noch VIAF wirksam

// ORCID match
if ((empty($qid)) AND (!empty($item['P496']))) {
	$query='SELECT (STRAFTER(STR(?u),"y/") AS ?q) WHERE {?u wdt:P496 '.$item['P496'][0].'}';
	$qid=sparql($query)['q']['value'];
}
// Q-ID des Familiennamen - erstmal noch ohne Adelstitel "von"
// es gibt auch Fälle, in denen es keinen Familiennamen hat
if (!empty($famname)) {
	$query='SELECT (STRAFTER(STR(?u),"y/") AS ?q) WHERE {?u rdfs:label "'.$famname.'"@de; wdt:P31 ?s.?s (wdt:P279*) wd:Q101352}';
	$item['P734'][0]=sparql($query)['q']['value'];
}
$g=0;  // Q-ID der Vornamen mit Geschlecht, Sortierung nach numerischem QID-Wert  
if (is_array($vornamen) && $vornamen) foreach ($vornamen as $vorname){	// wenn Array > 0
	$query='SELECT (STRAFTER(STR(?v),"y/") AS ?q) (SUBSTR(STR(?s),40) AS ?g) WHERE { VALUES ?s {wd:Q12308941 wd:Q11879590} 
		?v rdfs:label "'.$vorname.'"@de; wdt:P31 ?s} ORDER BY STRLEN(STR(?v)) ?v';
	$data =sparql($query);
	$item['P735'][]=$data['q']['value'];
	if (empty($item['P21'][0])) { // nur wenn in GND nicht belegt, was durchaus vorkommt
		switch ($data['g']['value']) {
			case '0': $g++; //weibl. Vorname
				break;
			case '1': $g--; //männl. Vorname
		}	
	}
}
if ($g<0) $item['P21'][0]=$m; // kein Überschreiben, da nur größer
if ($g>0) $item['P21'][0]=$w; // wenn einmal durchlaufen, d.h. unbelegt

// Ausgabe

$qslabel='</form><br style="clear:both;" /><label id="output" for="quickstatement">QuickStatements für GND <a href="https://lobid.org/gnd/'.$gnd.'" target="_blank" rel="noreferrer noopener">'.$gnd.'</a>';
if (empty($qid)){
	$qs="CREATE\n"; // wenn nicht in Wikidata, dann anlegen
	$ref="LAST\t";   // Referenz auf neu angelegtes
	echo '<div style="float:left;margin-right:10px;"><label for="wikidata">Wikidata</label>';
	echo '<select id="wikidata" style="background-color:White;width:350px"><option value="LAST">-- unverknüpfte Person auswählen --</option>';	
	$query='SELECT DISTINCT (STRAFTER(STR(?item),"y/") AS ?q) ?itemLabel ?itemDescription 
(GROUP_CONCAT(DISTINCT YEAR(?dob); SEPARATOR = "/") AS ?geb) 
(GROUP_CONCAT(DISTINCT YEAR(?dod); SEPARATOR = "/") AS ?tod) WHERE {
 VALUES ?such {"'.$label.'"@de "'.$label.'"@en "'.$label.'"@fr "'.$label.'"@mul "'.$label.'"@it}
  { ?item wdt:P31 wd:Q5;
      (rdfs:label|skos:altLabel) ?such.
    OPTIONAL { ?item wdt:P569 ?dob. }
    OPTIONAL { ?item wdt:P570 ?dod. }
  } MINUS { ?item wdt:P227 ?gnd }
  SERVICE wikibase:label { bd:serviceParam wikibase:language "de,mul,en,fr,it". } }
GROUP BY ?item ?itemLabel ?itemDescription
ORDER BY DESC (?geb) 
LIMIT 20';
$data =sparqlfeld($query);
foreach ($data as $datb) {
	$feld=$datb['itemLabel']['value'].' | '.$datb['geb']['value'].'-'.$datb['tod']['value'].' | '.$datb['itemDescription']['value'];
	echo '<option value="'.$datb['q']['value'].'">'.$feld.'</option>';
}
	echo '</select></div>';
} else {
	$ref="{$qid}\t"; // oder auf Q-ID
	$qslabel.=' = <a href="https://www.wikidata.org/wiki/'.$qid.'" target="_blank" rel="noreferrer noopener">'.$qid.'</a>';
	// Wikidata-Daten in Datenfeld eintragen
	echo '<div style="float:left;margin-right:10px;"><label for="wikidata">Wikidata</label>'; 
	// --> Wikidata
	$query='SELECT ?itemLabel ?itemDescription (YEAR(?dob) AS ?geb) (YEAR(?dod) AS ?tod) WHERE { VALUES ?item { wd:'.$qid.
	' } OPTIONAL { ?item wdt:P569 ?dob. } OPTIONAL { ?item wdt:P570 ?dod. } SERVICE wikibase:label {bd:serviceParam wikibase:language "de,mul,en,fr,it".}}';
	$data =sparql($query);
	$feld=$data['itemLabel']['value'].' | '.$data['geb']['value'].'-'.$data['tod']['value'].' | '.$data['itemDescription']['value'];
	echo '<select id="wikidata" style="background-color:White;width:350px"><option value="">'.$feld.'</option></select></div>';
	} 
echo "{$qslabel}</label>\n";

$qs.="{$ref}Lmul\t\"{$label}\"\n{$ref}Lde\t\"{$label}\"\n"; //mul: "Standard für alle Sprachen"
if (empty($qid)) $qs.="{$ref}P31\tQ5\n"; // Mensch nur bei neuem Eintrag

$gndheute="\tS248\tQ36578\tS227\t\"{$gnd}\"\tS813\t+".date('Y-m-d').$tag."\n";

foreach ($item as $prop => $vals) $item[$prop] = array_unique($vals);  // Dubletten innerhalb derselben Property
// property-übergreifende Bereinigung von potentiell doppelten Verwandtenangaben
$family = array_unique(array_merge($item['P22'] ?? [],$item['P25'] ?? [],$item['P40'] ?? [],$item['P3373'] ?? [],$item['P26'] ?? []));
$item['P1038'] = array_values(array_diff($item['P1038'] ?? [], $family));

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
} else { // kein Parameter GND --> leere Startseite
	$qs="Mit diesem Tool lassen sich Personendaten aus der GND via QuickStatements nach Wikidata portieren.\n\n".
		"Ist die gesuchte oder direkt eingegebene GND (bzw. eine korrespondierende VIAF- oder ORCID-ID) noch nicht in Wikidata vorhanden, wird eine Neuanlage für QuickStatements erzeugt. Ein Klick auf das große + schickt die Daten unmittelbar an QuickStatements.\n\n".
		"Im Wikidata-Dropdown-Menü stehen Personen als mögliche Verknüpfungspartner zur Auswahl, die in Wikidata einen gleichen Namenseintrag haben und noch nicht mit einer GND verknüpft sind. Aufgelistet sind hier nur exakte Namensübereinstimmungen. Deshalb vorher trotzdem immer noch einmal prüfen, ob es den Namenseintrag in Wikidata vielleicht doch schon gibt! Ist dies der Fall, dann einfach in Wikidata die GND hinzufügen und eine halbe Minute warten. Danach erzeugt der Aufruf dieses Tools statt eines Neueintrags eine Ergänzung um die in der GND vorhandenen Elemente.\n\n". 
		"Bei vorhandenem Wikidata-Eintrag werden bereits vorhandene Parameter (P1234 ...) von QuickStatements im Regelfall übrigens nicht überschrieben, sondern nur um die Quellenangabe (hier GND) ergänzt. ".
		"Einzige Ausnahme: Label (Lde, Lmul) und Beschreibung (Dde) werden durch QuickStatements überschrieben. Hier ist also größte Vorsicht geboten und es sollten vorm Senden an QuickStatements die betreffenden Zeilen aus dem Textfeld gelöscht werden, wenn kein Überschreiben gewünscht ist!";
	echo '<div style="float:left;margin-right:10px;"><label for="wikidata" style="color:Gray;">Wikidata</label>'; 
    echo '<select disabled id="wikidata" style="background-color:White;width:350px"><option value="">-- unverknüpfte Person auswählen --</option></select></div>';	
	echo '</form><br style="clear:both;" /><label for="quickstatement">QuickStatements</label>';
} 
//in jedem Fall auszuführender Code:

echo '<form><textarea id="quickstatement" style="height:350px;width:800px;overflow:scroll;">'.$qs.'</textarea><br />';
if (is_array($missing) && $missing) {
	echo '<div style="font-family: sans-serif;">';
	echo '<b style="color:Red;">Hinweis:</b> Wegen fehlender Wikidata-Zuordnung des GND-Identifikators nicht auflösbare Entitäten:<br />';
	foreach (array_unique($missing) as $mssng) echo ' &bull; <a target="_blank" href="https://d-nb.info/gnd/'.$mssng.'">'.$mssng.'</a>'; 
	echo ' --> Bitte erst in Wikidata zuordnen. Danke!'; 
}
?> <br /><br />
<a onclick="sendQS()" style="cursor: pointer;"><img src="https://upload.wikimedia.org/wikipedia/commons/b/bc/Plus_Wikidata.svg" height="50" alt="Wikidata-Plus" lang="en" loading="lazy"></a>
</form><br /><br />
<div style="font-family: sans-serif;">Quellcode auf <a href="https://github.com/iccander/wiki-zeugs/blob/main/quickgnd.php" target="_blank" rel="noreferrer noopener">GitHub</a>. 
Viel Spaß! <a href="https://lobid.org/gnd/1127162497" target="_blank" rel="noreferrer noopener">F.R.</a></div></body></html>
