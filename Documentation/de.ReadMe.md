# Extension Timer - Version 11.x

## Vorbemerkung

Die Basis für diese Dokumentation ist die deutsche Variante `de.ReadMe.md`. Die englische Variante wurde mit Hilfe
von `google.translate.de` übersetzt. Der Dokumentation ist eine Präsentatin beigefügt, die ich im Jahre 2022 für das
TYPO3-Barcamp in Kamp-Lintfort vorbereitet hatte

## ACHTUNG

- ~~8 November 2022: Die Timer `RangeListTimer` und `PeriodListTimer` funktionieren bisher nicht wie gewünscht.~~
- 27 Dezember 2022: Die Timer `RangeListTimer` und `PeriodListTimer` sollten jetzt funktionieren. Es sind entsprechende Tests definiert worden.


## Motivation

TYPO3 stellt in seinen Standardtabellen die Felder `starttime` und `endtime` zur Verfügung. Über die Felder können
Seiten zu bestimmten Zeiten ein- und ausgeblendet werden. Diese Felder werden auch beim Caching berücksichtigt. Es gibt
aber keine Möglichkeit, periodisch zu bestimmten Zeiten Seiten, Inhaltselemente und/oder Bilder ein- und auszublenden.

### Userstorys

#### Der interaktive Kneipengutschein.

Ein Gastwirt möchte seinen Gästen während der Happy-Hour einen Rabatt von 50% einräumen. Diesen erhalten die Kunden als
QR-Code auf ihr Handy, wenn sie das über der Theke hängende Tagesmotto und ein Like-Kommentar in das interaktive
Formular eingeben. Im Content-Element wird das tagesaktuelle Motto automatisch natürlich geprüft.

#### Die jährliche Firmengründungswoche

Ein Unternehmen feiert seinen Geburtstag mit einem Rückblick auf die Erfolge zum zurückliegenden Jahr. Diese werden im
Alltag von den Mitarbeitern eingepflegt. Um die Freischaltung dieser Sonderseite kümmert sich das TYPO3-System. Der
Veranstaltungskalender Eine kleiner Konzertveranstalter möchte gerne periodische Veranstaltungen wie Poetry-Slam,
Lesungen oder OffeneBühnen gemischt mit besondere Konzerttermine in einer Liste anbieten.

#### Reaktion-Columne

Eine Partei möchte aus seiner Startseite ähnliche Inhalte wie seine Konkurrenten deren Startseiten zeigen. Seine
Webseite soll automatisch auf die Änderungen bei den Konkurrenten reagieren. Die Artikel sollen noch eine gewissen Zeit
wieder verschwinden.
(Hat ja eigentlich nicht mehr mit Zeit zu tun. Aber das Problem könnte mit Timer gelöst werden…)

#### Vermarktung von virtuellen Konzerten

Eine Konzertagentur im Dezember 2021 möchte ein virtuelles Konzert in Berlin veranstalten, das Europaweit zu empfangen
sein soll. Da die Sommerzeit 2021 endet, rechnet der Veranstalter mit verschiedenen Zeitzonen in Europa. Die Webseite
soll für die Nutzer die jeweilige Ortszeit anzeigen.

## Idee

Eine Grundidee von der Timer-Extension ist, die Aufgabe in zwei Teilaufgaben aufzuteilen: in die Aufgabe des
Zeitmanagement und in die Aufgabe zur Steuerung der Startzeit. Über einen Task im Scheduler (Cronjob) kann die Seite
regelmäßig aktualisiert werden. Über Viewhelper kann das Einbinden von Informationen innerhalb von Templates gesteuert
werden, wobei der Entwickler sich dann auch Gedanken über das Caching des Frontends machen muss. Über Dataprozessoren
sollen leicht einfache Listen zusammenstellbar sein, die im Frontend ausgebar sind. Die Informationen aus den
Flexform-Feldern können in den Templates mit Viewhelpern aufbereite werden.

## Installation der Extension

Sie können auf einen der klassischen Wege installieren.

- manuell
- mit dem Extensionmanager bei den TYPO3 Admin-Tools
- mit dem Composer ``composer require porthd/timer``

Prüfen sie, ob das Datenbankschema aktualisiert wurde. Es ist das Typoscript der Extension zu inkludieren. Je nach
Nutzungsanspruch ist noch ein Schedulertask zu aktivieren oder im eigenen Typoscript ein Datenprozessor aufzurufen.

## Anwendungsaspekte

### Periodisch erscheinender Content oder periodisch erscheinende Seiten

Für periodisch erscheinden Content oder periodisch erscheinden Seite muss ein der Consolen-Task der Extension
eingerichtet werden. Er wertet out-of-the-box die Elemente aus, für die eine Timer definiert ist und für die das Flag
der Scheduler-Auswertung auf aktiv gesetzt wurde.

### Contentelement `periodlist` für einfache Terminlisten

Das Content-Element `periodlist` ist ähnlich aufgebaut wie das Content-Element `textmedia`.
Es erlaubt zusätzlich die Ausgabe von einfachen Terminlisten, sofern für den voreingestellten
Timer `periodlisttimer` eine gültige yaml-Datei mit einer Terminliste hinterlegt wird.
#### Anmerkungen
##### _Anmerkung 1_
Um einen möglichst flexible Einbindung von Terminlisten zu ermöglichen, gibt es die zwei Eingabefelder `yamlPeriodFilePath` und `yamlPeriodFalRelation`.
Das Feld `yamlPeriodFilePath` hat eher den Integrator im Blick und erlaubt  vier Varianten,
um den Ort der YAML-Datei zu spezifizieren:
1. absolute Pfadangabe ggfls. auch mit relativem Pfad
2. Pfadangabe mit dem Prefix `EXT:`
3. einfache URL beginnend mit `http://` oder mit `https://`
4. URL mit Serverpasswort im Format `Nutzername:Passwort:Url`, wobei die eigentliche URL mit URL beginnend mit `http://` oder mit `https://` beginnt. Bei den URL-Angaben wird nicht die YAML-Anweisung `import` unterstützt.

Das Feld `yamlPeriodFalRelation` hat eher den Redaktuer im Blick und erlaubt die Einbindung der YAML-Datei über das TYPO3 Backend.
Hier hat der Redakteur auch die Möglichkeit, mehrere Dateien einzubinden, die vom Timer wie eine große Liste behandelt werden.

##### _Anmerkung 2_
Im Attribut `data` können verschiedenen Daten hinterlegt werden, so daß über ein passendes Partial oder Template strukturiert
Sonderinformationen wie Eintrittspreis, Vorverkaufspreise oder Ähnliches per Datei mit übergeben werden können.
Diese Form eignet sich gut, wenn es darum geht, automatisiert Daten über das Format einer YAML-Datei aus anderen Quellen
entgegenzunehmen. Dies erspart das Einpflegen der Daten im Backend.

#### Darstellung der Termine im Kalender

Die Flexform-Definition wurde um zwei Pfad-Felder für JavaScript und für Stylesheets erweitert.
Auf diesem Weg ist es möglich, die Termine auch in Kalender-Form darzustellen. Die Default-Einstellungen sind so gesetzt,
dass die Schulferien für Niedersachsen und Bremen aus dem Jahr 2022 in einem Kalender dargestellt werden.

#### Dataprozessoren für diesen Timer
Damit die Daten eingelesen werden können, wurden drei Datenprozessoren definiert.
Der `FlexToArrayProcessor` erlaubt es, Flexform-Felder auszulesen und in einfache Array umzuwandeln.
Auf diesem Weg kann man dynamisch die JavaAScript- und Stylesheet-Dateien vom Inhaltselement laden lassen.
Der DataProcessor `PeriodlistProcessor` erlaubt das Auslesen der Terminliste, die beim PeriodlistTimer in der Yaml-Datei
definiert ist. Neben den eigentlichen Feldern generiert der Datenprozessor für die Start- und Endzueit der Termine auch die entsptrechenden DatTime-Objekte und berechnet die Anzahl der Tage (24Stunden = 1 Tag) zwischen den Terminen.
Der dritte Datenprozessor `MappingProcessor` ist nötig, um die Termindaten als JSON-String an das Fluid-Template zu übergeben.
So können die Daten leicht über ein HTML-Attribut dem Calendar-Framework zur Verfügung gestellt werden.

### Contentelement `timersimul` als Beispiel

Das Content-Element `timersimul` zeigt exemplarisch die Anwendung der Viewhelper und der Datenprozessoren. In
Produktivumgebungen sollten sie für Editoren ausblenden. Es wird entfernt werden, wenn die Extension den Status `beta`
erreicht.

### Nutzung der periodischen Timer (Customtimer)

Die Extension bringt derzeit mehrere periodische Timer mit. Zwei der Timer sind noch nicht vollständig entwickelt. Unter
anderem deshalb wurde der Status der Extension auf
`experimental` gestellt.

#### Customtimer - Allgemeines

Sie können die Timer bei den Konstanten der Extension freigeben.

Eigene Timer müssen sich vom Interface ``Porthd\Timer\Interfaces\TimerInterface`` ableiten und können
in ``ext_localconf.php`` wie folgt der Timer-extension beigefügt werden:

````
            \Porthd\Timer\Utilities\ConfigurationUtility::mergeCustomTimer(
                [\Vendor\YourNamespaceTimer\YourTimer::class, ]
            );
````

#### CustomTimer - Übersicht


* DailyTimer - Tägliche für einige Minuten wiederkehrende aktive Zeiten
  (täglich ab 10:00 für 120 Minuten)
* DatePeriodTimer - Periodisch für einige Minuten wiederkehrende aktive Zeiten relativ zu einem Startzeitpunkt. Bedenken
  sie, dass wegen der Rückführung der Zeit auf die UTC-Zone während der Berechnung die Sommerzeit bei Periodizitäten
  auf Stundenbasis zu unerwarteten Ergebnissen führen kann.
  (ganzen Tag in jedem Jahr zum Geburtstag, jede Woche für 120 Minuten bis 12:00 ab dem 13.5.1970, ..)
* DefaultTimer - Defaulttimer/Nullelement
* EasterRelTimer - Bestimmt den aktiven Zeitraum relativ zu wichtigen deutschen Feiertagen (Neujahr, Silvester, Welt-Handtuch-Tag, Tag-der-Dummheit) und den meist beweglichen wichtigen christlichen Feiertagen (erster Advent, Weihnachten, Rosenmontag, Karfreitag, Ostern, Himmelfahrt, Pfingsten)
  (2. Advent von 12:00-14:00, Rosenmontag von 20:00 bis 6:00 des Folgetages)
* JewishHolidayTimer (in Progress 2022-12-28) - Perioden startend relativ zu einem der jüdischen Feiertage bezogen auf den jüdischen Kalender
  (IMHO: Ich war lange am Überlegen, ob ich diesen Timer hinzufüge, weil ich Teile des Judentums für moralisch problematisch halte. Es ist bei orthodoxen Juden gelebte und gelobte Praxis (https://www.zentralratderjuden.de/fileadmin/user_upload/pdfs/Wichtige_Dokumente/ZdJ_tb2019_web.pdf Seite 21), dass der Penis ihrer männlichen Nachkommen aus religiösen Gründen beschnitten wird. Ich frage mich, welchen Wert hat eine Religion hat, die ihr Stolz darauf aufbaut, dass sie schwache hilflose Babies vergewaltigen kann, indem sie dessen Penisvorhaut unter dessen Schreien für den Gott opfern. Was unterscheidet dieser Religionswahn vom auserwählten Volk in seiner grundsätzlichen Denkweise von dem Arierwahn, dem manche Perverse heute noch anhängen? Aus humaniastischer Sicht endet für mich die Erziehungsfreiheit der Eltern zu dem Zeitpunkt, wenn sie für ihren eigenen religiösen Glauben ihren Kindern Gewalt antun wollen. Jedes Baby hat das Recht, seine eigene Religion bzw. seinen eigenen Glauben frei zu wählen. Die Eltern dürfen und sollen durch Reden und durch ihr eigenes Leben als Vorbild auf die Entscheidungsfindung durch das Kind einwirken; denn niemand kennt sicher die einzig wahre Wahrheit. Ich denke: Jede Vergewaltigung aus religiösen Gründen ist eine perverse Straftat - sei es bei den Katholiken oder bei den Juden oder bei irgendwelchen sonstigen Sekten. Dr. Dieter Porth)
  **!!!Achtung: ich kann nicht für die Korrektheit der jüdischen Feiertage garantieren, da ich mich damit überhaupt nicht auskenne. Bitte vor Gebrauch unbeddingt auf Korrektheit prüfen und gegebenenfalls selbst einen besseren Timer schreiben.**
  _Beim Testen merkte ich, dass das berechnete Datum für Yom Kippur nicht berücksichtigte, dass der Feiertag nach dem Sonnenuntergang des Vortages beginnt. Für eine Korrektur fehlte mir an dieser Stelle die Lust. So wichtig ist der jüdische Kalender für mich nun auch wieder nicht. Aus dem gleichen Grund wurden die Tests nur exemplarisch am Beispiel vom Yom Kippur durchgeführt._
* MoonphaseRelTimer - Perioden startend relativ zu einer Mondphase für einen bestimmten Zeitraum
* MoonriseRelTimer - Perioden relative zum Mondaufgang oder Monduntergang für einen bestimmten Zeitraum
* PeriodListTimer - Liest Daten zu aktiven Perioden aus einer Yaml-Datei ein. Hilfreich zum Beispiel
  für Ferienlisten oder Tourenpläne von Künstlern
* RangeListTimer - Liest periodische Liste aus Yaml-Dateien oder aus der Tabelle `` ein und mergt sie
  bei Überlappung zu neuen aktiven Bereichen zusammen. Man kann auch eine Liste mit unerlaubten Bereichen definieren,
  die solche Überlappungen reduzieren können. (Beispiel: jeden Dienstag und Donnerstag von 12-14 Uhr [aktive Timer] außer in den Schulferien und an
  Feiertagen [forbidden timer])
* SunriseRelTimer - Perioden relativ zum Sonnenaufgang und Sonnenuntergang
* WeekdayInMonthTimer - Perioden zu bestimmten Wochentagen innerhalb eines Monats ab bestimmten Uhrzeiten mit bestimmter
  Dauer
  (Beispiel: jeden zweiten Freitag im Monat in dem zwei Stunden vor 19:00 )
* WeekdaylyTimer - Wöchentliche Wiederholung an einem bestimmtem Wochentag oder zu bestimmten Wochentagen. (Beispiel: Jeden Montag oder
  Donnerstag)

#### CustomTimer - Generelle Parameter bei allen Timern

Einige Parameter sind bei allen Timern gleich. Zwei Parameter behandeln den Umgang mit Zeitzonen. Zwei weitere Parameter
bestimmen den Zeitraum, in welchen der Timer überhaupt gültig ist. Auf einen Parameter zur Steuerung des Scheduler wurde
verzichtet. Mir ist kein Anwendungsfall eingefallen, wo ein solcher Ausschluss wirklich sinnvoll ist.
Wer so etwas braucht, kann gern einen entsprechenden Timer programmieren.

* timeZoneOfEvent - speichert den Namen der zu verwendenden Zeitzone. wenn die Zeitzone des Server mit der Zeitzone des
  Event nicht übereinstimmt, wird die Server-ezeit auf die Zeitzonenzeit des Events umgerechnet.
  *Wertebereich*:
  Liste der generierten Zeitzonen. Die Zeitzonenproblematik ist wichtig, weil einige Timer die Zeiten auf UTC umrechen
  müssen. (Sonnenlauf, ...)
  *Anmerkung*:
  Aktuell werden alle Zeitzonen generiert. Es besteht die Möglichkeit, die Zeitzonen auf einen generelle Auswahl zu
  beschränken.
* useTimeZoneOfFrontend - ja/nein-Parameter. Wenn der Wert gesetzt ist, wird immer die Zeitzone des Servers verwendet.
* ultimateBeginningTimer - Ultimativer Beginn der Timers
  *Default*:
    1. Januar 0001 00:00:00
* ultimateEndingTimer - Ultimatives Ende des Timers
  *Default*:
    31. Dezember 9999 23:59:59

#### Customtimer - Developer - Motivation

Die Timer decken nicht jeden Fall ab. Sie können auch eigene Timerklasse definieren, die das `TimerInterface`
implementieren muss. . Die binden sie über ihre  `ext_localconf.php` ein. Über eigenen Flexform können sie ihrem Timer
eigene Parameter mitgeben.

### Viewhelper

Es gibt vier Viewhelper:

- timer:isActive - funktioniert ähnlich wie `f:if`, wobei geprüft wird, ob ein
  Zeitpunkt im aktiven Bereich eines
  periodischen Timers liegt.
- timer:flexToArray - Wenn man eine Flexform-Definition in einen Array
  umwandelt, dass enthält der Array viele
  überflüssige Zwischenebenen. Mit dem Viewhelper lassen sich diese Ebenen
  entfernen, so dass der resultierenArray des
  Flexformarrays flacher/einfacher wird.
- timer:format.date - funktioniert wie `f:format.date`, wobei es zusätzlich die
  Ausgabe von Zeiten für eine bestimmte Zeitzone
  erlaubt.
- timer:format.jewishDate - funktioniert ähnlich `f:format.date`, wobei es die
  Ausgabe von Zeiten für eine bestimmte Zeitzone
  erlaubt und wobei die Datumsangaben in den jüdischen Kalender transformiert
  werden.
- timer:format.calendarDate - funktioniert umfassender als `f:format.date`, weil
  er neben der Berücksichtigung der Zeitzone auch die Auswahl der verschiedenen
  von PHP unterstützten Kalender
  erlaubt und auch drei statt bisher zwei Datumsformatierungsvarianten erlaubt.
  Neben der Definition als gemäß der strftime-Formatierungsregeln und der
  dateTimeInterface::format-Formatierungsregeln kann auch die
  ICU-Formatierungssprache verwendet werden.
  Ein bekanntes Manko ist, dass die Umrechnung vom chinesischen Mondkalender in
  den gregorianischen (westlichen) Sonnenkalender mit einem Fehler behaftet ist,
  der im PHP zu suchen ist. Der Timer macht den
  Viewhelper ``timer:format.jewishDate`` überflüssig.

#### timer:format.calendarDate - Attribute

- **flagformat** bestimmt, welche Formtierungsregeln benutzt werden sollen: 0
  = [PHP-DateTime](https://www.php.net/manual/en/datetime.format.php),
  1: [ICU-Datetime-Formatierung](https://unicode-org.github.io/icu/userguide/format_parse/datetime/)
  oder 2 = [PHP-strftime](https://www.php.net/manual/en/function.strftime.php).
- **format** definiert die Form der Ausgabe des Datums.
- **base** ist für relative Datumsangaben wie 'now', '+4 days' oder ähnliches
  wichtig.
- **timezone** definiert, für welche Zeitzone ein Datum ausgegeben werden soll.
  Eine Liste der zulässigen-Zeitzonennamen erhalten sie über die
  PHP-Funktion `timezone_abbreviations_list()`. Aber auch in der
  PHP-Dokumentation finden sie
  eine [nach Kontinenten vorgeordnete Liste](https://www.php.net/manual/en/timezones.php)
  .
- **date** erlaubt die Angabe eines Datums aus dem gregorianischen (westlichen)
  Kalender, sofern bei `datestring` keine Angabe gemacht wurde. Sie können den
  Wert für `date` implizit definieren, indem sie den Wert mit den
  Viewhelper-Tags einschließen. Wenn sie keine Angaben machen, wird -
  wenn `datestring` leer ist oder fehlt - automatisch das aktuelle Datum
  verwendet.
- **datestring** erlaubt die Angabe eines Datums aus einem nicht-gregorianischen
  Kalender im Format ``Jahr/Monat/Tag Stunde/Minute/Sekunde``, wobei das Jahr
  vierstellig sein muss und alle anderen Angaben zweistellig. Für die Stunde ist
  jeder Wert von 0 bis 23 zulässig. Für die Monate sind Werte zwischen 1 und 13
  zulässig.
- **calendarsource** definiert den zugrunde liegenden Kalender für das Datum
  in  `datestring`. PHP erlaubt folgende Werte: 0:'buddhist', 1:'chinese', 2:'
  coptic', 3:'dangi', 4:'default', 5:'ethiopic', 6:'ethiopic-amete-alem', 8:'
  gregorian', 9:'hebrew', 10:'indian', 11:'islamic', 12:'islamic-civil', 13:'
  islamic-rgsa', 14:'islamic-tbla', 15:'islamic-umalqura', 16:'japanese', 17:'
  persian', 18:'roc'.
- **calendartarget** definiert den Kalender, für welchen das das Datum
  ausgegeben werden soll. PHP erlaubt folgende Werte: 0:'buddhist', 1:'chinese',
  2:'coptic', 3:'dangi', 4:'default', 5:'ethiopic', 6:'ethiopic-amete-alem', 8:'
  gregorian', 9:'hebrew', 10:'indian', 11:'islamic', 12:'islamic-civil', 13:'
  islamic-rgsa', 14:'islamic-tbla', 15:'islamic-umalqura', 16:'japanese', 17:'
  persian', 18:'roc'.
- **locale** bestimme die regionale Lokalisierung und setzt sich aus dem
  zweibuchstabigen Sprachkürzel (de, en, fr, es, ...) und getrennt durch einen
  Unterstrich aus dem Kürzel für die Nation (DE, GB, US, AT, CH, FR, ...). Der
  Wert in __locale__ könnte zum Beispiel folgendes Aussehen haben: `de_DE`
  , `en_GB` oder auch `es_US`.

### Dataprozessoren

Da die Ergebnisse der Datenprozessoren gecacht werden, muss sich der User
überlegen, was ein sinnvoller
Caching-Zeitraum ist und dies entsprechend definieren.

Grundsätzlich sollte im Sourcecode der jeweiligen DataProcessoren als Kommentar
ein Beispiel für die Anwendung desselben zu finden sein.
Für die Freunde der TypoScript-Programmierung sei gesagt, dass die Parameter
über die stdWrap-Methode eingelesen werden. Die rekursive Nutzung von Typoscript
zur Dynamisierung der Aufsetzung ist also möglich; auch wenn es hier
ausdrücklich nicht empfohlen wird.

#### RangeListQueryProcessor

Der Prozessor erstellt für die Datensätze mit periodischen Timern aus einer Tabelle eine Liste von Terminen. Der
Datenprozessor funktioniert ähnlich wie der `DbQueryProcessor`.

##### _Beispiel in Typoscript_
```
tt_content.timer_timersimul >
tt_content.timer_timersimul < lib.contentElement
tt_content.timer_timersimul {

    templateName = Timersimul

    dataProcessing.10 = Porthd\Timer\DataProcessing\RangeListQueryProcessor
    dataProcessing.10 {
        table = tx_timer_domain_model_event

        # get the date, which are defined on the pages, declared in the fielld `pages`
        pidInList.stdWrap.cObject = TEXT
        pidInList.stdWrap.cObject.field = pages
        recursive = 1

        # sort in reverse order
        #            reverse = false

        # name of output-object
        as = examplelist
    }
}

```

Siehe auch Beispiel in exemplarischen Contentelement ``timersimul``

##### _Parameter für den Dataprocessor `RangeListQueryProcessor`_
Wegen der Wiederholung von Perioden kann in der Liste ein Datensatz mehrfach aufgezählt werden. Deshalb sind immer auch ein Start-Zeitpunkt und ein End-Zeitpunkt zu definieren.

| Parameter      | Default                                                                                                                                | Beschreibung
|----------------|----------------------------------------------------------------------------------------------------------------------------------------|--------------
|                | **_Datensätze_**                                                                                                                       |
| if             | true                                                                                                                                   | Wenn der wert oder der Typoscript-Ausdruck falsch ergeben, wird der Dataprocessor nicht ausgeführt.
| table          | tx_timer_domain_model_event                                                                                                            | Diese Tabelle wird verwendet, um nach allen verfügbaren Datensätzen mit Timer-Informationen zu suchen.
| pidInList      |                                                                                                                                        | Komma-separierte Liste von numerischen IDs für Seiten, die Datensätze für die Bestimmung der Liste mit Timer-Event enthalten können.
| as             | records                                                                                                                                | Komma-separierte Liste von numerischen IDs für Seiten, die Datensätze für die Bestimmung der Liste mit Timer-Event enthalten können.
|                | **_Start und Allgemeines_**                                                                                                            |
| datetimeFormat | Y-m-d H:i:s                                                                                                                            | Definiert das Format, in welchem das Datum angegeben wird. Es gelten die Zeichen, die im PHP definiert sind (siehe [Liste](https://www.php.net/manual/en/datetimeimmutable.createfromformat.php)).
| datetimeStart  | &lt;jetzt&gt;                                                                                                                          | Definiert den Zeitpunkt, mit welchem die Liste beginnen soll. Bei `reverse = false` ist es der früheste Zeitpunkt, und bei `reverse = true` ist es der späteste Zeitpunkt.
| timezone       | &lt;definiert im PHP-System&gt;                                                                                                        | Definiert die Timezone, die bei den Datumswerten verwendet werden soll.
| reverse        | false                                                                                                                                  | Definiert, ob die Liste der aktiven Bereiche absteigend oder aufsteigend sortiert ist. Bei `reverse = true` ist jeweils das Ende der aktiven Bereiche maßgeblich; Beim Defaultfall `reverse = true` ist es entsprechen der Anfang der aktiven Zeit.
|                | **_Limit der Periode_**                                                                                                                |
| maxCount       | 25                                                                                                                                     | Begrenzt der Liste über die maximale Anzahl der Listenelemente
| maxLate        | &lt;sieben Tage relativ zum Startdaum&gt;                                                                                              | Begrenzt die Liste über ein Stop-Datum, dass nie erreicht werden kann.
| maxGap         | P7D                                                                                                                                    | Begrenzt die Liste, indem aus dem Startzeitpunkt der entsprechende Stopzeitpunkt berechnet wird. Für die Angabe der zeitlichen Differenz ist die PHP-Notation für Zeitintervalle zu verwenden (siehe [Übersicht](https://www.php.net/manual/en/class.dateinterval.php)).
|                | **_Spezielles_**                                                                                                                       |
|    userRangeCompare            | `Porthd\Timer\Services\ListOfEventsService::compareForBelowList` oder `Porthd\Timer\Services\ListOfEventsService::compareForAboveList` | Für die Bestimmung der Reihenfolge werden nur die Datumswerte verwendet. Der Nutzer könnte auch andere Sortierungskriterien berücksichtigen. Zum Beispiel könnte man eine Liste haben wollen, die zuerst nach dem Startdatum und bei gleichem Startdatum nach der Dauer der aktiven Bereiche sortiert wäre.

#### SortListQueryProcessor

Die Tabelle `sys_file_reference` unterstützt nicht die Felder `starttime` und `endtime`. Um trotzdem zeitliche
variierende Bilder zu erreichen, kann man die per Datenprozessor erhalten Medien in einen nach Periodizität sortierte
Liste überführen lassen und umwandeln lassen und im Template entsprechend nutzen.

##### _Beispiel in TypoScript_
```
        dataProcessing {
            ...
            20 = TYPO3\CMS\Frontend\DataProcessing\FilesProcessor
            20 {
                references.fieldName = media
                references.table = pages
                as = myfiles
            }

            30 = Porthd\Timer\DataProcessing\SortListQueryProcessor
            30 {
                fieldName = myfiles
                # lenght of the sorted list. Perhaps with identical images at different positions
                hartBreak = 25
                as = mysortedfiles
            }
            ...
        }

```

Beachten sie, dass FLUIDTEMPLATE gecacht wird. Deshalb:

```
    stdWrap {
        cache {
            key = backendlayout_{page:uid}_{siteLanguage:languageId}
            key.insertData = 1
            lifetime = 3600
        }
    }
```

##### _Parameter für den Dataprocessor `SortListQueryProcessor`_
Wegen der Wiederholung von Perioden kann in der Liste ein Datensatz mehrfach aufgezählt werden. Deshalb sind immer auch ein Start-Zeitpunkt und ein End-Zeitpunkt zu definieren.

Im Gegensatz zum `RangeListQueryProcessor` nutzt der `SortListQueryProcessor` Daten, die von einem  vorherigen oder übergeordneten Dataprozessor-Prozeß erzeugt wurden.
Die Parameter `table` plus `pidInList` entfallen deshalb und der Parameter `fieldName` kommt neu hinzu.

| Parameter        | Default                                                                                                                                | Beschreibung
|------------------|----------------------------------------------------------------------------------------------------------------------------------------|--------------
|                  | **_Datensätze_**                                                                                                                       |
| if               | true                                                                                                                                   | Wenn der wert oder der Typoscript-Ausdruck falsch ergeben, wird der Dataprocessor nicht ausgeführt.
| fieldName        | myrecords                                                                                                                              | Diese Tabelle wird verwendet, um nach allen verfügbaren Datensätzen mit Timer-Informationen zu suchen.
| as               | sortedrecords                                                                                                                          | Name des Objekts, welches die einzelnen Events enthält und ans Fluid-Template übergeben wird. Die Genaue Struktur schaue man sich `&lt;f:debug>{sortedrecords}</f:debug>` an.
|                  | **_Start und Allgemeines_**                                                                                                            |
| datetimeFormat   | Y-m-d H:i:s                                                                                                                            | Definiert das Format, in welchem das Datum angegeben wird. Es gelten die Zeichen, die im PHP definiert sind (siehe [Liste](https://www.php.net/manual/en/datetimeimmutable.createfromformat.php)).
| datetimeStart    | &lt;jetzt&gt;                                                                                                                          | Definiert den Zeitpunkt, mit welchem die Liste beginnen soll. Bei `reverse = false` ist es der früheste Zeitpunkt, und bei `reverse = true` ist es der späteste Zeitpunkt.
| timezone         | &lt;definiert im PHP-System&gt;                                                                                                        | Definiert die Timezone, die bei den Datumswerten verwendet werden soll.
| reverse          | false                                                                                                                                  | Definiert, ob die Liste der aktiven Bereiche absteigend oder aufsteigend sortiert ist. Bei `reverse = true` ist jeweils das Ende der aktiven Bereiche maßgeblich; Beim Defaultfall `reverse = true` ist es entsprechen der Anfang der aktiven Zeit.
|                  | **_Limit der Periode_**                                                                                                                |
| maxCount         | 25                                                                                                                                     | Begrenzt der Liste über die maximale Anzahl der Listenelemente
| maxLate          | &lt;sieben Tage relativ zum Startdaum&gt;                                                                                              | Begrenzt die Liste über ein Stop-Datum, dass nie erreicht werden kann.
| maxGap           | P7D                                                                                                                                    | Begrenzt die Liste, indem aus dem Startzeitpunkt der entsprechende Stopzeitpunkt berechnet wird. Für die Angabe der zeitlichen Differenz ist die PHP-Notation für Zeitintervalle zu verwenden (siehe [Übersicht](https://www.php.net/manual/en/class.dateinterval.php)).
|                  | **_Spezielles_**                                                                                                                       |
| userRangeCompare | `Porthd\Timer\Services\ListOfEventsService::compareForBelowList` oder `Porthd\Timer\Services\ListOfEventsService::compareForAboveList` | Für die Bestimmung der Reihenfolge werden nur die Datumswerte verwendet. Der Nutzer könnte auch andere Sortierungskriterien berücksichtigen. Zum Beispiel könnte man eine Liste haben wollen, die zuerst nach dem Startdatum und bei gleichem Startdatum nach der Dauer der aktiven Bereiche sortiert wäre.


#### FlexToArrayProcessor
Der `FlexToArrayProcessor` ermöglicht das Lesen von `Flex`-Feldern und wandelt sie in einfache Arrays um.
Auf diese Weise könnten die kalenderspezifischen Ressourcen einfach für das Inhaltselement `periodlist` nachgeladen werden.

```
        30 = Porthd\Timer\DataProcessing\FlexToArrayProcessor
        30 {
            # reguläre if-Syntax, um die Anwendung des Datenprozessor zu verhindern
            #if.isTrue.field = record

            # Feld, dass den Flexform-Array enthält
            # Standard ist `tx_timer_timer`
            field = tx_timer_timer

            # Feld mit Selektor für Flexform-Array
            # Standard bei fehlender Definition ist 'tx_timer_selector'
            # selectorField = tx_timer_selector

            # Eine Definition von Flattenkeys überschreibt die Standarddefinition.
            #    Die Attribute `timer` und `general` werden als Blattnamen in meinen customTimer-flexforms verwendet
            #    Die folgende Definition ist die Standard-Vorgabe bei fehlender Definition: `data,general,timer,sDEF,lDEF,vDEF`
            flattenkeys = data,general,timer,sDEF,lDEF,vDEF

            # Ausgabevariable mit der resultierenden Liste als Array
            as = flexlist

```

#### MappingProcessor
Der Datenprozessor `MappingProcessor` erlaubt das Mappen/Abbilden von Arrays in neue Arrays oder in einen JSON-String.
So können die Daten leicht HTML-Attribute dem JavaScript zur Verfügung gestellt werden.
Der Datenprozessor kennt einfache generische Funktionen, um zum Beispiel Events eindeutige IDs zuzuordnen.
Weiter erlaubt er das Mappen/Abbilden von Feldinhalten und das Anlegen von neuen Feldern mit konstanten Daten.

```

        20 = Porthd\Timer\DataProcessing\MappingProcessor
        20 {

            # reguläre if-Syntax
            #if.isTrue.field = record

            # Name des Feldes mit einem Array, das von einem früheren Datenprozessor generiert wurde
            inputfield = periodlist

            # Jedes Feld muss Teil der Periodenliste sein
            # Jeder Eintrag muss formal sein
            generic {
                # Definieren Sie einen Index, z. B. `event1holiday` im Feld `id`
                'id' {
                    pretext = event
                    posttext = holiday
                    Typ = index
                }
                # Definiere eine Konstante, z. B. `cal1` im Feld `calendarId`
                calendarID {
                    pretext = cal1
                    posttext =
                    Typ = constant
                }
            }

            mapping {
                # sourceFieldName in Periodenliste (siehe Eingabefeld) => targetFieldName
                # Bei der Zuordnung wird zwischen Groß- und Kleinschreibung unterschieden.
                title = Titel
                startJson = date
                diffDaysDatetime = days
            }

            # Ausgabeformat hat die Werte `array`,`json`
            # Wenn das Ausgabeformat unbekannt ist, ist json der Standardwert
            outputFormat = json

            # Ausgabevariable mit der resultierenden Liste
            # Standardwert ist `periodlist`
            asString = periodListJson

        }

```

#### PeriodlistProcessor
Der DataProcessor `PeriodlistProcessor` erlaubt das Auslesen der Terminliste, die beim PeriodlistTimer in der Yaml-Datei
definiert ist. Neben den eigentlichen Feldern generiert der Datenprozessor für die Start- und Endzueit der Termine auch die entsptrechenden DatTime-Objekte und berechnet die Anzahl der Tage (24Stunden = 1 Tag) zwischen den Terminen.

```
        10 = Porthd\Timer\DataProcessing\PeriodlistProcessor
        10 {

            # reguläre if-Syntax
            #if.isTrue.field = record

            # Zeitliche begrenzung der Auswahl von Daten
            #limit {
            #    # unterer Zeitpunkt; stdWrap wird unterstützt
            #    lower = TEXT
            #    lower {
            #        data = date:U
            #        strftime = %Y-%m-%d %H:%M:%S
            #
            #    }
            #    ## obere Zeitgrenze; stdWrap wird unterstützt
            #    #upper = TEXT
            #    #upper {
            #    #    data = date:U
            #    #    strftime = %Y-%m-%d %H:%M:%S#
            #    #}
            #}

            # hart verdrateter Hilfsmechanismus, um die Datenfelder mit dem Start- bzw. Endzeitpunkt in ein gewünschtes Format zu umzuwandeln und in einem zusätzlichen Feld zu speichern
            dateToString {
                # startJson is the targetfieldName in the following datenprozessor mappingProcessor
                startJson {
                    # use the format-parameter defined in https://www.php.net/manual/en/datetime.format.php
                    # escaping of named parameters with the backslash in example \T
                    format = Y-m-d
                    # allowed are only `diffDaysDatetime`, `startDatetime` und `endDatetime`,because these are automatically created datetime-Object for the list
                    #   These fields are datetime-object and they are generated from the estimated fields `start`and `stop` by this datenprozessor
                    source = startDatetime
                }
           #     endJson {
           #         format = Y-m-d
           #         source = stopDatetime
           #     }
            }

            # Beschränkung der Liste auf eine maximale Anzahl von Elementen
            # Wenn nichts angegeben wird, wird die Liste auf 25 Element beschränkt
             maxCount = 100

            # NAme der Ausgabevariablen, die an das Fluid-Template übergeben wird.
            # Wenn nicht angegeben wird, ist der Standardwert `periodlist`.
            as = periodlist

            ## Bei `flagStart = 1` oder `flagStart = true`  wird für den Vergleich von Ober- und untergrenze das Feld `start`  aus der Referenzliste verwendet. Dies ist der Standardzustand.
            ## Bei `flagStart = 0` oder `flagStart = false`,  wird für den Vergleich von Ober- und untergrenze das Feld `stop`  aus der Referenzliste verwendet.
            #flagStart = false
        }

```
