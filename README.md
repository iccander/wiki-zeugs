# quickgnd

Da man bei der GND (https://www.dnb.de/DE/Professionell/Standardisierung/GND/gnd_node.html) selbst keine Lebensdaten usw. ergänzen kann, bleibt eigentlich nur, ein Wikidata-Pendant anzulegen und dort alle Zusatzinformationen abzulegen. Um das Klonen zu automatisieren, habe ich dieses Tool programmiert, mit dem sich GND-Personendaten via QuickStatements in Wikidata überspielen lassen. Ein paar JSON- und SPARQL-Abfragen bei https://lobid.org/gnd bzw. https://www.wikidata.org sorgen dafür, dass das automatisch und fast 1:1 funktioniert.

Fürs erste läuft das Skript bei mir auf: https://vermessungs-bibliothek.de/quickgnd.php.

Wenn dort eine gültige GND-Nummer eingegeben wird, spuckt das Programm alles was es an Personendaten finden und zuordnen kann in der Befehlssyntax (https://www.wikidata.org/wiki/Help:QuickStatements/de) von QuickStatements aus. 

Ist die eingegebene GND oder korrespondierende VIAF noch nicht in Wikidata vorhanden, wird eine Neuanlage für QuickStatements erzeugt. Ein Klick auf das große + schickt die Daten unmittelbar an QuickStatements.

Vorher bitte immer prüfen, ob es den Namenseintrag in Wikidata vielleicht schon gibt, ohne dass ihm bislang ein GND- oder VIAF-Eintrag zugeordnet ist. Ist dies der Fall, dann einfach in Wikidata die GND hinzufügen und eine halbe Minute warten. Danach erzeugt der Aufruf dieses Tools statt eines Neueintrags eine Ergänzung um die in der GND vorhandenen Elemente.

Bei vorhandenem Wikidata-Eintrag werden bereits vorhandene Properties (P1234 ...) von QuickStatements im Regelfall übrigens nicht überschrieben, sondern nur um die Quellenangabe (hier GND) ergänzt. Einzige Ausnahme: Label (Lde, Lmul), Beschreibung (Dde) und Alias (Ade) werden durch QuickStatements überschrieben. Hier ist also größte Vorsicht geboten und es sollten vorm Senden an QuickStatements die betreffenden Zeilen aus dem Textfeld gelöscht werden, wenn kein Überschreiben gewünscht ist!

Das Tool ist noch nicht ganz fertig. Bei Gelegenheit will ich eine Dropdown-Auswahl für das Matching mit Wikidataeinträgen zu namensgleichen Personen ohne GND-Eintrag ergänzen. Und die Oberfläche bedarf natürlich auch noch der dringenden Aufhübschung.
