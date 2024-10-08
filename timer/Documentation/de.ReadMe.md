# Extension Timer - Version 12.x

## Vorbemerkung

Die Basis für diese Dokumentation ist die deutsche Variante `de.ReadMe.md`. Die
englische Variante wurde mit Hilfe
von `google.translate.de` übersetzt. Der Dokumentation ist eine Präsentatin
beigefügt, die ich im Jahre 2022 für das
TYPO3-Barcamp in Kamp-Lintfort vorbereitet hatte

## Motivation

TYPO3 stellt in seinen Standardtabellen die Felder `starttime` und `endtime` zur
Verfügung. Über die Felder können
Seiten zu bestimmten Zeiten ein- und ausgeblendet werden. Diese Felder werden
auch beim Caching berücksichtigt. Es gibt
aber keine Möglichkeit, periodisch zu bestimmten Zeiten Seiten, Inhaltselemente
und/oder Bilder ein- und auszublenden.

### Userstorys

#### Der interaktive Kneipengutschein.

Ein Gastwirt möchte seinen Gästen während der Happy-Hour einen Rabatt von 50%
einräumen. Diesen erhalten die Kunden als
QR-Code auf ihr Handy, wenn sie das über der Theke hängende Tagesmotto und ein
Like-Kommentar in das interaktive
Formular eingeben. Im Content-Element wird das tagesaktuelle Motto automatisch
natürlich geprüft.

#### Die jährliche Firmengründungswoche

Ein Unternehmen feiert seinen Geburtstag mit einem Rückblick auf die Erfolge zum
zurückliegenden Jahr. Diese werden im
Alltag von den Mitarbeitern eingepflegt. Um die Freischaltung dieser Sonderseite
kümmert sich das TYPO3-System. Der
Veranstaltungskalender Eine kleiner Konzertveranstalter möchte gerne periodische
Veranstaltungen wie Poetry-Slam,
Lesungen oder OffeneBühnen gemischt mit besondere Konzerttermine in einer Liste
anbieten.

#### Reaktion-Columne

Eine Partei möchte aus seiner Startseite ähnliche Inhalte wie seine Konkurrenten
deren Startseiten zeigen. Seine
Webseite soll automatisch auf die Änderungen bei den Konkurrenten reagieren. Die
Artikel sollen noch eine gewissen Zeit
wieder verschwinden.
(Hat ja eigentlich nicht mehr mit Zeit zu tun. Aber das Problem könnte mit Timer
gelöst werden…)

#### Vermarktung von virtuellen Konzerten

Eine Konzertagentur im Dezember 2021 möchte ein virtuelles Konzert in Berlin
veranstalten, das Europaweit zu empfangen
sein soll. Da die Sommerzeit 2021 endet, rechnet der Veranstalter mit
verschiedenen Zeitzonen in Europa. Die Webseite
soll für die Nutzer die jeweilige Ortszeit anzeigen.

## Idee

Eine Grundidee von der Timer-Extension ist, die Aufgabe in zwei Teilaufgaben
aufzuteilen: in die Aufgabe des
Zeitmanagement und in die Aufgabe zur Steuerung der Startzeit. Über einen Task
im Scheduler (Cronjob) kann die Seite
regelmäßig aktualisiert werden. Über Viewhelper kann das Einbinden von
Informationen innerhalb von Templates gesteuert
werden, wobei der Entwickler sich dann auch Gedanken über das Caching des
Frontends machen muss. Über Dataprozessoren
sollen leicht einfache Listen zusammenstellbar sein, die im Frontend ausgebar
sind. Die Berechnungen in den Dataprozessoren werden gecacht und einge der
Dataprozessoren löschen automatisch den Cache der korrospondierenden Seite. Die
Informationen aus den
Flexform-Feldern können in den Templates mit Viewhelpern aufbereite werden.

## Installation der Extension

Sie können auf einen der klassischen Wege installieren.

- manuell
- mit dem Extensionmanager bei den TYPO3 Admin-Tools
- mit dem Composer ``composer require porthd/timer``

Prüfen sie, ob das Datenbankschema aktualisiert wurde. Es ist das Typoscript der
Extension zu inkludieren. Je nach
Nutzungsanspruch ist noch ein Schedulertask zu aktivieren oder im eigenen
Typoscript ein Datenprozessor aufzurufen.

## Anwendungsaspekte

### Periodisch erscheinender Content oder periodisch erscheinende Seiten

Für den periodisch erscheinenden Content oder die periodisch erscheinende Seite
muss der Planer/Scheduler-Task der Extension
eingerichtet werden, damit die Felder `starttime`und `endtime` regelmäßig
aktuallisiert werden.
Der Task aktuallisiert gegebenenfalls die Felder `starttime`und `endtime` für
diejenigen Elemente aus, für die Werte im Feld `tx_timer_selector` definiert
sind und für die das Flag im Feld `tx_timer_scheduler`
der Scheduler-Auswertung auf aktiv gesetzt wurde und deren `endtime` in der
Vergangenheit liegt bzw. nicht definiert ist.

#### Contentelement `periodlist` für einfache Terminlisten

Das Content-Element `periodlist` ist ähnlich aufgebaut wie das
Content-Element `textmedia`.
Es erlaubt zusätzlich die Ausgabe von einfachen Terminlisten. Die Daten für
diese Content-Elment werden
als Flexform im Feld `pi_flexform` gespeichert.

Neben den Parametern für die periodischen Daten kann man auch zusätzlich Pfade
zum JavaScript und zum CSS angeben.
Auf diese Weise kann man ein eigenes Kalender-System einbinden. Exemplarische
wurde
hier das schlanke JavaScript-Framework von Jack
Ducasse ([https://github.com/jackducasse/caleandar](https://github.com/jackducasse/caleandar))
verwendet. Grundsätzlich kann man nautürlich auch jedes andere
Kalender-Framework verwenden.

Um das Plugin benutzen zu können, müssen sie beginnend ab Version 12.2 das
TypoScript für das Contentelement einbinden. (Die vorher notwendige Einbindung
per Extensionkonstanten ist entfernt worden.)

#### Contentelement `holidaycalendar` für Feiertag

Bemerkung: Um das Plugin benutzen zu können, müssen sie beginnend ab Version
12.2 das TypoScript für das Contentelement einbinden. (Die vorher notwendige
Einbindung per Extensionkonstanten ist entfernt worden.)

Die meisten wollen Termine nicht in unübersichtlichen TYPO3-Backend pflegen,
weil man dort schnell die Übersicht verliert.
Eine übersichtliche Auflistung von Terminen
kann man in einer Excel-Liste erreichen. Die meisten Redakteure sind auch in der
Lage,
die Daten in ihrer Excel-Tabelle als CSV-Datei zu speichern.

Das Content-Element `holidaycalendar` ist eine Weiterentwicklung des
Content-Elementes `periodlist`,
wobei hier der Fokus auf den Redakteur-Workflow gelegt wird. Es erlaubt
zusätzlich die Ausgabe von einfachen Feiertagslisten
oder anderer Terminlisten, die über einfach zu erstellende `excel`-Listen
definiert werden. Die Daten für dieses Content-Element werden
als Flexform im Feld `pi_flexform` gespeichert.

Neben den Parametern für die periodischen Daten kann man auch zusätzlich Pfade
zum JavaScript und zum CSS angeben.
Für das Out-Of-The-Box-Beispiel wurde das mächtige
Kalender-Framework [ToastUI-calendar](https://github.com/nhn/tui.calendar/)
voreingestellt.
Es ist ein mächtiges Framework, dass leider mit einer etwas spartanischen
Dokumentation daherkommt.
(Vielleicht habe ich mich mit der Integration auch nur schwergetan, weil ich
bisher wenig Erfahrungen im JavaScript-Bereich habe sammeln können.)
Für den initialen Aufruf des Content-Elments mussten mehrere JavaScripte
eingebunden werden, weshalb der `timer:forCommaList`-Viewhelper programmiert
wurde.
Die Aufzählung der verschiedenen JavaScript-Dateien kann als Komma-separierte
Liste
in einem Feld erfolgen. Die JavaSript und StyleSheet-Felder mit der
Namensbestandteil `custom` sind für jede Content-element individuell und werden
bei mehrfacher verwendung des Content-Elementes auch mehrfach geladen.
Die Dateien aus den anderen beiden Felder werden nur einmal für eine Seite
geladen, egal wie oft das Content-Element auf der Seite verwendet wird.

##### 'Alias'-Definition: nur Experiment im Kalendar-Dataprocessor

Der Dateimport kann über eine CSV-Datei oder eine Yaml-Datei erfolgen. Da die
meisten Redakteure vermutlicht mit der
Erstellung einer Yaml-Datei überfordert sind, wurde auch der Import als
CSV-Datei ermöglicht.
Eine CSV-Datei kann der Redaktuer leicht mit `excel` (Microsoft Office)
oder `calc` (LibriOffice/ Open Office) erzeugen, indem er seine Tabelle als
CSV-datei abspeichert.
Der Import der Datei in das Content-Elmente erfolgt entweder über die Angabe der
Pfade (Feld: `holidayFilePath`) oder alternativ über das File-System von TYPO3 (
Feld: `holidayFalRelation`).
Über die Felder `aliasFilePath` und `aliasFalRelation` kann der Redakteur auch
Alias-Definitionen importieren.
In den Alias-Definitionen findet man oft wiederverwendete
Definitionsbestandteile,
die den Datenarray eines Eintrags automatisch erweitern, wenn im
Feld `add.alias` der entsprechende Name der Alias-Definition zu finden ist. (*
*Achtung: Diese alias-Feature wuirde bislang noch nicht getestet.**)

Voreingestellt ist das mächtige
Kalender-Framework [ToastUI-calendar](https://github.com/nhn/tui.calendar/).

Als voreingestellte Daten zu den Feiertagen in verschiedenen Ländern (Ich
übernehme keine Garantie für die Richtigkeit und Vollständigkeit der Daten.)
wird eine CSV-Datei eingespielt, die mit `calc` aus
der [Datei (ExcelLikeListForHolidays.ods)](ExcelLikeListForHolidays.ods) erzeugt
wurde.
Die Datei sollte sich auch in `excel` einlesen und bearbeiten lassen.
Über die Punktnotation im Titel kann man hinterher die Aufbaustruktur des Arrays
im PHP steuern.

```
Title in CSV:
   title
Value in first row of CSV:
   'my value'

>>> will be transformed to >>>

php:
 0=> [
     'title' =>'my value',
 ]

=====================================================
Title in CSV:
   title.COMMA
Value in first row of CSV:
   'my value,my stuff,my idea'

>>> will be transformed to >>>

php:
 0 => [
     'title' => [
        'my value',
        'my stuff',
        'my idea',
     ],
 ]

=====================================================
Title in CSV:
   title.subtitle.label
Value in first row of CSV:
   'my value,my stuff,my idea'
>>> will be transformed to >>>
php:
 0 => [
     'title' => [
        'subtitle' =>[
            'label' => 'my value,my stuff,my idea',
        ],
     ],
 ]

```

(Für mich war erstaunlich, wie viele unterschiedliche Varianten und
Berechnungsregeln es für Feiertage gibt. )

#### Anmerkungen

##### _Anmerkung 1_

Um einen möglichst flexible Einbindung von Terminlisten zu ermöglichen, gibt es
die zwei Eingabefelder `yamlPeriodFilePath` und `yamlPeriodFalRelation`.
Das Feld `yamlPeriodFilePath` hat eher den Integrator im Blick und erlaubt vier
Varianten,
um den Ort der YAML-Datei zu spezifizieren:

1. absolute Pfadangabe ggfls. auch mit relativem Pfad
2. Pfadangabe mit dem Prefix `EXT:`
3. einfache URL beginnend mit `http://` oder mit `https://`
4. URL mit Serverpasswort im Format `Nutzername:Passwort:Url`, wobei die
   eigentliche URL mit URL beginnend mit `http://` oder mit `https://` beginnt.
   Bei den URL-Angaben wird nicht die YAML-Anweisung `import` unterstützt.

Das Feld `yamlPeriodFalRelation` hat eher den Redaktuer im Blick und erlaubt die
Einbindung der YAML-Datei über das TYPO3 Backend.
Hier hat der Redakteur auch die Möglichkeit, mehrere Dateien einzubinden, die
vom Timer wie eine große Liste behandelt werden.

##### _Anmerkung 2_

Im Attribut `data` können verschiedenen Daten hinterlegt werden, so daß über ein
passendes Partial oder Template strukturiert
Sonderinformationen wie Eintrittspreis, Vorverkaufspreise oder Ähnliches per
Datei mit übergeben werden können.
Diese Form eignet sich gut, wenn es darum geht, automatisiert Daten über das
Format einer YAML-Datei aus anderen Quellen
entgegenzunehmen. Dies erspart das Einpflegen der Daten im Backend.

#### Darstellung der Termine im Kalender

Die Flexform-Definition wurde um zwei Pfad-Felder für JavaScript und für
Stylesheets erweitert.
Auf diesem Weg ist es möglich, die Termine auch in Kalender-Form darzustellen.
Die Default-Einstellungen sind so gesetzt,
dass die Schulferien für Niedersachsen und Bremen aus dem Jahr 2022 in einem
Kalender dargestellt werden.

#### Dataprozessoren für diesen Timer

Damit die Daten eingelesen bzw. konvertiert werden können, wurden sechs
Datenprozessoren
definiert.

Der `FlexToArrayProcessor` erlaubt es, Flexform-Felder auszulesen und in
einfache Array umzuwandeln.
Auf diesem Weg kann man dynamisch die JavaAScript- und Stylesheet-Dateien vom
Inhaltselement laden lassen.

Der DataProcessor `PeriodlistProcessor` erlaubt das Auslesen der Terminliste,
die beim PeriodlistTimer in der Yaml-Datei
definiert ist. Neben den eigentlichen Feldern generiert der Datenprozessor für
die Start- und Endzeit
der Termine auch die entsprechenden DatTime-Objekte und berechnet die Anzahl der
Tage (24Stunden = 1 Tag) zwischen den Terminen.

Der DataProcessor 'RangeListQueryProcessor' erlaubt das Auslesen von
Terminlisten,
wobei der Dataprozessor auch Verbotslisten berücksichtigt. Damit kann man zum
Beispiel
Terminserien vom Typ "Jeden Dienstag nur nicht in den Schulferien" realisieren.
Genauere Informationen finden sich weiter unten.

Der DataProcessor 'HolidaycalendarProcessor' erlaubt das Einlesen von
Feiertagsterminen per CSV-Datei.
Wenn es denn unbedingt sein muss, kann man dafür auch eine YAML-Datei verwenden.
Genauere Informationen zur Definition der Feiertage finden sich weiter unten.

Der DataProcessor 'SortListQueryProcessor' erlaubt das Sortieren von
Objektlisten,
die Timer-Definitionen enthalten. Darüber kann man zum Beispiel die Ausgabe
von Fotos aus einer Galerie oder
die zeitlich gesteuerte Ausgabe von Bildern aus einer Sammlung steuern.
Genauere Informationen finden sich weiter unten.

~~Der dritte Datenprozessor `MappingProcessor` ist nötig, um die Termindaten als
JSON-String an das Fluid-Template zu übergeben.
So können die Daten leicht über ein HTML-Attribut dem Calendar-Framework zur
Verfügung gestellt werden.~~
`MappingProcessor` ist deprecated und wird in der Version 12 entfernt, weil er
keine Arrays mit mehreren Ebenen unterstützt.

~~Zukünftig wird für das Mapping als dritter
Datenprozessor `BetterMappingProcessor` verwendet werden.
Er kann helfen, einen geeigneten JSON-String an das Fluid-Template zu übergeben.
So können die Daten leicht über ein HTML-Attribut dem TuiCalendar-Framework oder
einem anderen Calendar-Framework zur Verfügung gestellt werden.~~
Der Datenprozessor `BetterMappingProcessor` war eine Übergangslösung.

Zukünftig wird für das Mapping als dritter
Datenprozessor `PhpMappingProcessor` verwendet werden.
Dieser ist im Gegensatz zum `BetterMappingProcessor` einfacher zu konfigurieren
und verfolgt einen Zielstruktur-orientierten Ansatz.
Er kann helfen, einen geeigneten JSON-String an das Fluid-Template zu übergeben.
So können die Daten leicht über ein HTML-Attribut dem TuiCalendar-Framework oder
einem anderen Calendar-Framework zur Verfügung gestellt werden.

### Contentelement `timersimul` als Beispiel

Das Content-Element `timersimul` zeigt exemplarisch die Anwendung der Viewhelper
und der Datenprozessoren. In
Produktivumgebungen sollten sie für Editoren ausblenden. Es wird entfernt
werden, wenn die Extension den Status `beta`
erreicht.

Um das Plugin benutzen zu können, müssen sie beginnend ab Version 12.2 das
TypoScript für das Contentelement einbinden. (Die vorher notwendige Einbindung
per Extensionkonstanten ist entfernt worden.)

### Nutzung der periodischen Timer (Customtimer)

Die Extension bringt derzeit mehrere periodische Timer mit. Zwei der Timer sind
noch nicht vollständig entwickelt. Unter
anderem deshalb wurde der Status der Extension auf
`experimental` gestellt.

#### Customtimer - Allgemeines

Sie können die Timer bei den Konstanten der Extension freigeben.

Eigene Timer müssen sich vom
Interface ``Porthd\Timer\Interfaces\TimerInterface`` ableiten und können
in ``ext_localconf.php`` wie folgt der Timer-extension beigefügt werden:

````
            \Porthd\Timer\Utilities\ConfigurationUtility::mergeCustomTimer(
                [\Vendor\YourNamespaceTimer\YourTimer::class, ]
            );
````

### vordefinierte Timer - Übersicht

* ~~(nicht realisiert 12.2.1) CalendarDateRelTimer - (In Vorbereitung) Die
  allermeisten religiösen, historischen, politischen, ökonmischen oder sonstigen
  Feiertage sind über ein Datum fest in einem Kalender fixiert. Die Mächtigen
  wollen vermeiden, dass sie das gemeine Volk mental überfordern (damit auch
  jeder Dölmer das Fest auch zum richtigen Zeitpunkt würdigt). Im Laufe der
  Menschheitgeschichte wurden viel verschiedene Kalendersysteme entwicklet und
  es gibt viele regional unterschiedlich wichtige Festtatge. Dieser
  Variablilität will der Timer Rechnung tragen, indem er die Berücksichtigung
  von verschiedene Kalendersysteme erlaubt.
  (Beispiel 5760 Minuten (=2 Tage) nach Ramadan (1.9.; islamischer Kalender) für
  720 Minuten (=6 Stunden)). Gleichzeitig lassen sich über diesen Timer auch
  Listen von Terminen ausgeben. Es wird dabei der Workflow unterstützt, die
  Terminlisten - also Feiertagsliste wie auch Timer-Definitionen in einer
  Exceltabelle zu definieren und dem Timer selbst die Liste als CSV-Datei zur
  Verfügung zu stellen.~~ _(Nutze HolidayTimer stattdessen)_
* DailyTimer - Tägliche für einige Minuten wiederkehrende aktive Zeiten
  (täglich ab 10:00 für 120 Minuten)
* DatePeriodTimer - Periodisch für einige Minuten wiederkehrende aktive Zeiten
  relativ zu einem Startzeitpunkt. Bedenken
  sie, dass wegen der Rückführung der Zeit auf die UTC-Zone während der
  Berechnung die Sommerzeit bei Periodizitäten
  auf Stundenbasis zu unerwarteten Ergebnissen führen kann.
  (ganzen Tag in jedem Jahr zum Geburtstag, jede Woche für 120 Minuten bis 12:00
  ab dem 13.5.1970, ..)
* DefaultTimer - Defaulttimer/Nullelement
* EasterRelTimer - Bestimmt den aktiven Zeitraum relativ zu wichtigen deutschen
  Feiertagen (Neujahr, Silvester, Welt-Handtuch-Tag, Tag-der-Dummheit) und den
  meist beweglichen wichtigen christlichen Feiertagen (erster Advent,
  Weihnachten, Rosenmontag, Karfreitag, Ostern, Himmelfahrt, Pfingsten)
  (2. Advent von 12:00-14:00, Rosenmontag von 20:00 bis 6:00 des Folgetages)
* HolidayTimer (nutzbar ab 12.2.0) - Liste zur Definition von Feiertage. Die
  Feiertage werden in einer CSV-Datei erfasst und können auch über eine
  YAML-Datei definiert werden.
  Erläuterungen zur CSV-Datei siehe weiter unten.
  Die Yaml-Datei blockt unter dem Ausdruck `holidayTimerList` alle Daten, die
  für den Holidaykalender relevant sind. Für jeden Feiertag wird ein eigenes
  Array-Element in dem YAML-Block angelegt. Die Namen der Attribute sind die
  gleichen, wie sie als Namen für die Spalten in der CSV verwendet werden.
* JewishHolidayTimer - Perioden startend relativ zu einem der jüdischen
  Feiertage bezogen auf den jüdischen Kalender
  (IMHO: Ich war lange am Überlegen, ob ich diesen Timer hinzufüge, weil ich
  Teile des Judentums für moralisch problematisch halte. Es ist bei orthodoxen
  Juden gelebte und gelobte
  Praxis (https://www.zentralratderjuden.de/fileadmin/user_upload/pdfs/Wichtige_Dokumente/ZdJ_tb2019_web.pdf
  Seite 21), dass der Penis ihrer männlichen Nachkommen aus religiösen Gründen
  beschnitten wird. Ich frage mich, welchen Wert hat eine Religion hat, die ihr
  Stolz darauf aufbaut, dass sie schwache hilflose Babies vergewaltigen kann,
  indem sie dessen Penisvorhaut unter dessen Schreien für den Gott opfern. Was
  unterscheidet dieser Religionswahn vom auserwählten Volk in seiner
  grundsätzlichen Denkweise von dem Arierwahn, dem manche Perverse heute noch
  anhängen? Aus humaniastischer Sicht endet für mich die Erziehungsfreiheit der
  Eltern zu dem Zeitpunkt, wenn sie für ihren eigenen religiösen Glauben ihren
  Kindern Gewalt antun wollen. Jedes Baby hat das Recht, seine eigene Religion
  bzw. seinen eigenen Glauben frei zu wählen. Die Eltern dürfen und sollen durch
  Reden und durch ihr eigenes Leben als Vorbild auf die Entscheidungsfindung
  durch das Kind einwirken; denn niemand kennt sicher die einzig wahre Wahrheit.
  Ich denke: Jede Vergewaltigung aus religiösen Gründen ist eine perverse
  Straftat - sei es bei den Katholiken oder bei den Juden oder bei irgendwelchen
  sonstigen Sekten. Dr. Dieter Porth)
  **!!!Achtung: ich kann nicht für die Korrektheit der jüdischen Feiertage
  garantieren, da ich mich damit überhaupt nicht auskenne. Bitte vor Gebrauch
  unbeddingt auf Korrektheit prüfen und gegebenenfalls selbst einen besseren
  Timer schreiben.**
  _Beim Testen merkte ich, dass das berechnete Datum für Yom Kippur nicht
  berücksichtigte, dass der Feiertag nach dem Sonnenuntergang des Vortages
  beginnt. Für eine Korrektur fehlte mir an dieser Stelle die Lust. So wichtig
  ist der jüdische Kalender für mich nun auch wieder nicht. Aus dem gleichen
  Grund wurden die Tests nur exemplarisch am Beispiel vom Yom Kippur
  durchgeführt._
  **Empfehlung:** _Nutzen sie stattdessen den allgemeineren
  Timer `holidayTimer` (der Listen von verschiedensten Feiertagen verarbeiten
  kann)._**
* MoonphaseRelTimer - Perioden startend relativ zu einer Mondphase für einen
  bestimmten Zeitraum
* MoonriseRelTimer - Perioden relative zum Mondaufgang oder Monduntergang für
  einen bestimmten Zeitraum
* PeriodListTimer - Liest Daten zu aktiven Perioden aus einer Yaml-Datei ein.
  Hilfreich zum Beispiel
  für Ferienlisten oder Tourenpläne von Künstlern
* RangeListTimer - Liest periodische Liste aus Yaml-Dateien oder aus der
  Tabelle `` ein und mergt sie
  bei Überlappung zu neuen aktiven Bereichen zusammen. Man kann auch eine Liste
  mit unerlaubten Bereichen definieren,
  die solche Überlappungen reduzieren können. (Beispiel: jeden Dienstag und
  Donnerstag von 12-14 Uhr [aktive Timer] außer in den Schulferien und an
  Feiertagen [forbidden timer])
* SunriseRelTimer - Perioden relativ zum Sonnenaufgang und Sonnenuntergang
* WeekdayInMonthTimer - Perioden zu bestimmten Wochentagen innerhalb eines
  Monats ab bestimmten Uhrzeiten mit bestimmter
  Dauer
  (Beispiel: jeden zweiten Freitag im Monat in dem zwei Stunden vor 19:00 )
* WeekdaylyTimer - Wöchentliche Wiederholung an einem bestimmtem Wochentag oder
  zu bestimmten Wochentagen. (Beispiel: Jeden Montag oder
  Donnerstag)

#### Anmerkungen zum Workflow beim _HolidayTimer_

##### Herausforderung

Die Einschätzung, welche Feiertage ein Redakteur benutzen können darf/soll,
werden sicher von Webseite zu Webseite unterschiedlich sein. Vielleicht will man
nur christliche, jüdische, islamische oder andere Feiertage nutzen.
Weiter wird sich wohl jeder Redakteur wünschen, dass die Auswahl der Feiertage
auf die Notwendigen beschränkt sind und dass unerwünschte Feiertage dem
Redakteur überhaupt nicht zur Auswahl stehen.
Weiterhin werden seitens der Entwickler ganz unterschiedliche Wünsche bestehen,
welche Informationen zusätzlich zu den Feiertagen noch gespeichert werden
sollen.
Gleichzeitig möchte man die Liste der Feiertage möglicht übersichtlich verwalten
können.

##### Workflow für individuelle Listen

Man verwaltet die Liste der Feiertage in einer Tabellenkalulation wie `Excel`
oder `calc` und speichert die Daten in einer CSV-Datei.
Die CSV-Datei lädt man per FTP auf dem Server hoch und gibt den Pfad zur
CSV-Datei bei den Settings für die Extension-Konfigurationen an.
Nach dem Löschen des Caches hat man dann die neue Liste im Timer `HolidayTimer`
verfügbar.

##### unterstützte Feiertagsberechnungen (aktuell nicht funktionsfähig und nicht getestet 2023-02-25)

Die Berechnug ist wie jedes menschengemachte System im Grundsatz einfach; aber
im Detail meisten hoch kompliziert, weil die Schlauen sich in ihrer Dummheit von
den Dummen abgrenzen wollen und weil manchen Menschen die Macht wollen, anderen
zu erzählen, was die Wahrheit ist. Sei es drum. Es werden aktuell folgnden
Berechnungsschmeata unterstützt, die in der CSV-Datei in der `arg.type`
anzugeben sind:

- _fixed_: Für einen definierten Kalender (`arg.calendar`) wird ein definierter
  Tag (`arg.day`) und ein definierter Monat (`arg.month`) angegeben. Tag und
  Monat sind als Zahlen anzugeben. Der Schaltmonat im jüdischen Kalender wird in
  der Regel übersprungen und wenn eine Jahr mit einem Schaltmonat vorliegt, wird
  der Zielmonat intern automatisch um eins erhöht, weil der IntlFormatter den
  Monat einfach weiterzahlt, dan in jüdischen Schaltjahren der IntlDateFormatter
  einfach 13 Monate für das Jahr zugrundelegt. Wenn sie in Schaltjahren auf den
  Schaltmonat ersten Adar (6) statt auf den zweiten Adar (7) zugreifen wollen,
  müssen sie bei Nutzung des jüdischen Kalenders in `arg.status` einen Wert
  größer als '1' hinterlegen.
- _fixedshifting_: Für einen definierten Kalender (`arg.calendar`) wird ein
  definierter Tag (`arg.day`)
  und ein definierter Monat (`arg.month`) angegeben. In manchen Ländern ist es
  üblich, für den Feiertag einen Ersatzfeiertag zur Verfügung zu stellen,
  wenn der Feiertag selbst zum Beispiel auf das Wochenende oder auf einen
  bestimmten Wochentag fällt.
  In der Spalte `arg.statuscount` findet sich bei diesem Typ eine
  komma-separierte Liste von sieben ganzen Zahlen. Die Zahlen kennzeichen, um
  wieviel Tage
  ein Feiertag verschoben wird, wenn der Feiertag zum Beispiel auf einen
  Mittwoch fällt. In der Liste repräsentiert die
  erste Zahl den Montag und die letzte Zahlden Sonntag. In dem Beispiel '
  0,1,2,0,3,2,1' würde ein Feiertag um drei Tage (5.ter Eintrag) verschoben,
  wenn er auf einen Freitag (5.ter Tag in der Woche) fällt.
  Technisch funktioniert die Mechanik analog zu `_fixed_`.
- _fixedrelated_ oder besser _xmasrelated_: Das Beispiel vierter Advent zeigt,
  dass es Feiertage gibt, die an bestimmten Wochentage relativ zu einem
  fixierten Datum (beim Advent der erste Weihnachtstag) gefeiert werden.
  Mit den Ziffern 1 = Montag bis 7 = Sonntag definiert man in `arg.status` den
  Wochentag, der dem Fix-Datum des Zielfeiertages vorangehen muss.
  In `arg.statusCount` defniert man dann, wie viel Wochen vorher der Tag
  stattfinden muss. Der erste Advent ist zum Beispiel mit `-4` der vierte
  Sonntag vor Weihnachten.
  Um Feiertagsspezialitäten wie den Buß- und Bettag - der Mittwoch vor dem
  fünften Sonntag vor Weihnachten - definieren zu können, kann man
  in `arg.secDayCount` angeben, wie viele Tage der eigentliche Feiertag vom
  berechneten Tag wirklich entfernt ist.
- _season_: Dies definiert die vier Jahreszeiten, die mit 1=Frühling, 2=Sommer
  3= Herst und 4 = Winter über das Attribut `arg.status`definiert werden.
- _seasonshifting_: Dies definiert wie _`season`_ die vier Jahreszeiten, die mit
  1=Frühling, 2=Sommer 3= Herst und 4 = Winter über das Attribut `arg.status`
  definiert werden. Wie bei _fixedshifting_ wird auch hier im
  Parameter `arg.statusCount` der jeweilige Wochentag berücksichtigt, um einen
  entsprechende Verschiebung des Feiertage bei entsprechenden Wochentagen zu
  ermöglichen.
- _matariki_: Matariki ist nur für die Feiertag Matariki in Neuseeland
  vorgesehen. Ich habe keinen Algorithmus gefunden, der diesen Feiertag in PHP
  berechnen kann. Es gibt nur einen Liste für die kommenden 30 Jahre die ich bei
  einer (!) Quelle im Internet gefunden habe. Diese Berechnungsroutine kennt
  keine Parameter.
- _easterly_: Dieses Schlüsselwort gilt nur für eine Berechnungsform beschränkt
  auf den gregorianischen oder auf den julianischen Kalender (`arg.calendar`).
  Es bestimmt einen Feiertag relativ zum Ostersonntag, der mit Hilfe der
  Osterformel von Gauß bzw. der PHP-Funktion berechnet werden kann.
  In `arg.statusCount` wird die positive oder negative Zahl der Tage relative
  zum Ostersonntag angegeben. Wenn die Zahl fehlt oder dort eine `0` steht, dann
  ist natürlich der Ostersonntag selbst gemeint.
- _weekdayly_: Hier berechnet man für einen ausgewählten
  Kalender (`arg.calendar`) den i.ten (`arg.statusCount`)
  Wochentag (`arg.status`) innerhalb eines Monats (`arg.month`). Der Wochentag
  wird über eine Nummer charakterisiert, wobei die 1 für den Montag und die 7
  für den Sonntag steht. Wenn in `arg.statusCount` eine negative Nummer steht,
  dann wird die Position des Wochentags relativ zum Monatsende bestimmt. Es
  findet keine Prüfung statt, ob das Datum noch im besagten Monat liegt. Wenn
  in `arg.secDayCount` noch ein wert angegeben ist, dann wird der Tag relativ
  zum berechnent Wochentag bestimmt. Die wird beispielsweise für den Genfer
  Bettag in der chweiz benötigt, der immer am Donnerstag nach dem ersten Sonntag
  im September gefeiert wird.
- _mooninmonth_: Manche Feste wurden im Laufe der Geschichten von einem
  Kalendersystem auf ein anderes Kalendersystem übertragen, wie dies zum
  Beispiel beim Vesakh-Fetst der Fall ist.
  Heute wird das Fest in manchen Ländern am ersten in anderen Ländern
  gegebenenfalls am zweiten Vollmond-Tag im Mai gefeiert. Verallgemeinert
  bedeutet dies für einen ausgerwählten Kalender (`arg.calendar`), dass der
  Monat im Feld `arg.month` als Nummer angegeben wird. Der Kalender wird
  in `arg.calendar` definiert. Die Phase des Mondes wird in `arg.status` mit der
  Codierung 0/new_moon = Neumond, 1/first_quarter = zunehmender Halbmond,
  2/full_moon = Vollmond und 3/last_quarter = abnehmender (islamisch?) Halbmond
  definiert. Wenn in `arg.statusCount` eine '1' angegeben ist oder wenn weine
  Angabe fehlt, wird immer die erste Mondphase im Monat verwendet, auch wenn es
  im Monat zwei gleiche Mondphasen geben sollte. Andernfalls wird die zweite
  Mondphase verwendet, sofern diese im gleichen Monat existiert.

Die Methoden sind so definiert, dass immer ein Datum bestimmt wird. Die
Proceduren zur Berechnung geben immer ein Gregorianisches Datum zurück.

##### Vorab-Erläuterungen zur internen Struktur der CSV-Liste

Man verwaltet die Liste der Feiertage in einem Tabellenkalkulationsprogramm
wie `Excel` von MS Office oder wie `calc` aus Libri Office.
Ein [calc-Beispiel](ExcelLikeListForHolidays.ods) und
auch [die daraus gespeicherte CSV-Datei](ExcelLikeListForHolidays.csv) sind hier
in der Dokumentation zu finden.
Wichtig ist bei dem Beispiel die gewählte Titelzeile (erste Zeile), weil die
Notation der Titel die Struktur eines assoziativen Arrays mit mehreren Ebenen
definiert.
Der Ausdruck 'add.rank' im Titel führt zu folgenden Array, wenn in der
entsprechenden Zeile in der CSV der Spalte der Wert `5` zugeordnet ist:

```
$liste = [
    // ...
    [
        'arg' => [
            'rank' => 5,
        ],
    ],
    // ...
];
```

Der Teilausdruck COMMA hat eine besondere Bedeutung, weil er die Angabe im Feld
dieser Spalte immer als Kommaseparierte Liste interpretiert, die automatisch in
einen Array mit getrimmten Werten umgewandelt wird.
Der Ausdruck 'add.locale.COMMA' im Titel führt zu folgenden Array, wenn in der
ensprechenden Zeile in der CSV der Spalte der Wert `de_DE, de_CH , de_AT `
zugeordnet ist:

```
$liste = [
    // ...
    [
        'arg' => [
            'locale' => [
                'de_DE',
                'de_CH',
                'de_AT',
            ],
        ],
    ],
    // ...
];
```

Die Struktur erlaubt es, eine kompakte CSV-Liste in eine lesbarere Yaml-Struktur
zu überführen.
Alternativ können sie deshalb auch eine Datei im Yaml-format verwenden, um die
Feiertage verfügbar zu haben.
Sie können für eigenen Informationen zusätzliche Spalten einfügen.
Es wird angestrebt, die Angaben immer auch im Frontend verfügbar zu machen.

##### Erläuterungen zur internen Struktur der CSV-Liste

Die Struktur lässt sich am leichtesten am Beispiel der YAML-Struktur erläutern.
Zu den einzelnen Bestandteilen sind die Erläuterungen als Kommentar mit
angeführt.

```
  -
# Hilfstitel für die Übersicht in der Excel-Datei. Der Wert wird im Programm nicht genutzt.
    title: 'Heiligabend'
# Verweis auf die Fremdsprachendatei, in welcher der Termin sprachlich definiert ist. Wenn man nur eine Sprache nutzt,
#    kann man hier auch Klartext eintragen - zum Beispiel das Gleiche wie bei `title`.
    eventtitle: 'LLL:EXT:timer/Resources/Private/Language/locallang_cal.xlf:calendarDate.christ.greg.christmasEve'
# Der Identifier sollte eindeutig in der Liste sein. In den meisten Fällen sollte wie hier die Abkürzung des Kalenders
#    plus die Abkürzung des Festtagsnamens zu einem eindeutigen Identifier führen. Ist in Zukunft gegebenenfalls
#    für den internen Gebrauch vorgesehen.
    identifier: 'greg-christmasEve'
# `type` bestimmt, wie das Datum berechnet wird. Definiert sind aktuell die für Groß- und Kleinschreibung empfindlichen
#    Begriffe `fixed`, `easterly`, `weekdayinmonth`, `weekdayly`, `mooninmonth` und `leapmonth`.
# - `fixed`: Hier wird jährlich für einen bestimmten Kalender eine definierter Tag und Monat bestimmt.
# - `easterly`: Hier wird jährlich ein gemäß der Tage relativ zum Ostersonntag ein Festtag bestimmt. Als Kalender
#   können nur der gregorianische und der Julianische Kalender ausgewählt werden. Alle anderen führen zu einer
#   Fehlermeldung. Auf diesen Wege lassen sich die meisten christlichen Festtage bestimmen.
# - `weekdayinmonth`: Hier wird ein bestimmter Wochentag für einen bestimmten Monat in einem bestimmten Kalender
#   definiert. Diese Form der Berechnung wird zum Beispiel für den `Dank-, Buss- und Bettag` in der Schweiz benötigt.
# - `weekdayly`: Hier wird relativ zu einem definierten Tag und Monat ein bestimmter Wochentag gesucht. Ein Beispiel
#   wäre der vierte Advent, der der letzte Sonntag vor Weihnachten ist. Diese Methode erlaubt auch die Berechnung von
#   einem  etwas komplexeren Fall: dem Buß- und Bettag. Dieser findet vier Tage vor dem Totensonntag statt,
#   wobei der Totensonntag seinerseits als der 5.te Sonntag vor Weihnachten definiert ist, welches seinerseits
#   bekanntlich immer am 25.12. stattfindet.
# `mooninmonth`: Hier wird innerhalb eines bestimmten Monats für einen definierten Kalender das Tag mit einer
#   bestimmten Mondstellung (Vollmond, Neumond) bestimmt. (Es wird unterstellt, dass die Schaffer von Feiertage immer
#   nur Monate auswählen, die auch genügend Tage für einen vollen Mondzyklus haben.)
# `leapmonth`: Diese Methode ist auf den jüdischen Kalender beschränkt, um das Purim-Fest berechnen zu können, dass
#   im Schaltmonat des jüdischen Kalenders liegen kann. Alle anderen Kalenderangaben führen zu einem Fehler.
    type: 'fixed'
#  Hier werden die zusätzlichen Argumente für die jeweilige Feiertagsberechnungsmethode definiert.
    arg:
      #wichtig für: `fixed`, `weekdayinmonth`, `weekdayly`, `mooninmonth` und `leapmonth`.
      month: '12'
      #wichtig für: `fixed`,  `weekdayly`, und `leapmonth`.
      day: '24'
      #wichtig für alle: `fixed`, `easterly`, `weekdayinmonth`, `weekdayly`, `mooninmonth` und `leapmonth`.
      calendar: 'gregorian'
      #wichtig für: `easterly`, `weekdayinmonth`, `weekdayly` und `mooninmonth`.
      status: ''
      #wichtig für: `easterly`, `weekdayinmonth`, `weekdayly` und `mooninmonth`.
      statusCount: ''
      #wichtig für: `weekdayly`.
      secDayCount: ''
# Hiermit soll der Feiertag charakterisiert werden. Die Angabe ist optional und wird aktuell im Programm nicht verwendet.
#   Die Angabe habe ich eingeführt, was die Motivation für Feiertage ist/sein könnte. Benutzt habe ich die Kategorien
#   `religion`,`culture`,`economic`, `politics` und `historical`. Auffällig fand ich, dass viele Feiertage religiös
#   motiviert sind. Dienen Feiertage den Religionsführern als lohnendes Ritual zur Aufrechterhaltung der geistigen
#   Konditionierung/Manipulation der Menschen?
#  - `religion`: Diese Feste dienen zur Bindung der Gläubigen an die Institution des Glaubens. Als Religionsgegner
#    könnte man sagen, dass die Feste zur Stabilisierung der Konditionierung der Gläubigen dienen.
#  - `culture`: Hier werden bestimmte Rituale von einer größeren Gruppe von Menschen gepflegt, ohne dass noch deutlich
#    erkennbar ist, aus welchen Grund das Fest überhaupt entstanden ist. Beispiele wären Silvester oder
#    auch der Muttertag.
#  - `economic`: Haupttriebfeder für den Festtag ist das ökonomische Interesse. Ein typisches Beispiel ist der
#    Valentinstag. Weihnachten als Konsumfest könnte man in Deutschland vielleicht auch zu diesen Tagen zählen.
#  - `politics`: Bei Politischen feiertagen möchte man bestimmte politische Themen oder Fragestellungen dauerhaft in
#    der Diskussion behalten. Beispiele für politische Feiertage sind der 1. Mai (Tag der Arbeit),
#    8.3 (Weltfrauentag) oder auch der 16.4 (Tag der Dummheit).
#  - `historical`: Dieser Tage steht immer unter dem Aspekt des Gedenkens an ein historisch belegtes Ereignis. Ein
#    Beispiel wäre der Tag der Deutschen Einheit, der an die Unterzeichnung der Verträge zur Deutschen
#    Wiedervereinigung erinnern soll. Der 9. November, also der Tag des Mauerfalls, wurde vermutlich nie ernsthaft als
#    Feiertag diskutiert, weil es die damaligen Machthaber und ihr vermeintliches Handeln hätte unwichtig erscheinen lassen.
    tag: 'religion'
# In diesem Block `add` können beliebige weitere Attribute definiert werden. Für die Berechnung der Feiertage
#    sind diese Daten nicht notwendig. Sie können aber zum frontend durchgeschleift werden.
    add:
# hier habe ich über Schlagworte versucht, den Motivationsbereich bzw. die Nutzergruppen näher einzugrenzen.
      category:
        - 'christian'
# Der Rank definiert die Wichtigkeit des Feiertages. In dem Wert spiegelt sich meine naive Einschätzung wieder. Er ist nicht objektivierbar.
      rank: '3'
# Hier könnte man definieren, in welchen Regionen und/oder Sprachzonen bestimmte Feiertage wichtig sind. Aktuell ist meine Liste nur exemplarisch und extrem lückenhaft.
      locale:
        - 'de_DE'
        - 'de_AT'
        - 'de_CH'
# Hier könnte man definieren, in welchen Regionen und/oder Sprachzonen bestimmte Feiertage arbeitsfreie Tage sind.
      freelocale:
        - 'de_DE'
        - 'de_AT'
        - 'de_CH'
# Man möchte nicht für jeden Feiertag immer wieder das Gleiche schreiben. Über einen Alias kann man in den Addlock weitere Informationen hineinmergen. Achtung: das Alias kann auch hier stehende Definitionen überschreiben.
      alias: ''
```

##### Wichtige Spalten/Spaltenbezeichner in der CSV

- _title_: Diese Spalte bezeichnet den Feiertag und muss immer mit mindestens
  einem Zeichen (kein Whitespace-Zeichen) gefüllt sein.
- _identifier_: Diese Spalte bezeichnet in der Liste einen eindeutigen
  Identifier für den Feiertag. Er sollte eine Abkürzung des
  genutzten Kalenders enthalten und eine Abkürzung für den Feiertag.
  Gegebenenefalls kann es hilfreich sei, in den Identifier auch den die
  Locale-Bezeichnujng für das Land einfließen zu lassen.
- _arg.timer_: die ist einen Oberbegriff, unter welchen die verschiedenen
  Parameter für die jeweiligen Timer erfasst, die für die Extension `timer`
  genutzt werden.
- _arg_: erfasst die Argumente für die verschiedenen
  Timer/Typen `fixed`, `fixedrelated`, `fixedshifting`, `weekdayly`, `easterly`
  oder `mooninmonth`. Die Parameter werden zur Berechnung der Feiertage
  verwendet.
- _arg.startYear_: Die Jahrezahl beschreibt, ab welchem Jahr des gewählten
  Kalenders der Feiertag gültig ist. Er gilt für alle
  Typen (`fixed`, `fixedrelated`, `fixedshifting`, `fixedmultiyear`, `season`, `seasonshifting`, `weekdayly`, `easterly`
  oder `mooninmonth`).
- _arg.endYear_:  Die Jahrezahl beschreibt, bis welchem Jahr des gewählten
  Kalenders einschließlich der Feiertag gültig ist/war. Er gilt wie oben für
  alle Typen.
- _arg.day_: Der Parameter beschreibt einen Tag im Monat für eine
  Feiertagsberechnung. Genutzt wird der Eintrag
  in `fixed`, `fixedrelated`, `fixedshifting`,`fixedmultiyear`, `weekdayly`.
- _arg.month_: Der Parameter beschreibt den Monat, der für die
  Feiertagsberechnung wichtig ist. Genutzt wird der Eintrag
  in `fixed`, `fixedrelated`, `fixedmultiyear`, `fixedshifting`, `weekdayly`
  oder `mooninmonth`
- _arg.type_: Beschreibt den Typ der Kalenderberechnung. Möglich
  sind `fixed`, `fixedrelated`, `fixedshifting`, `fixedmultiyear`, `weekdayly`, `easterly`
  oder `mooninmonth`. Möglich sind auch alle Identifikatoren, die die
  verschiedenen Timer als Identifikatoren zur Verfügung stellen.
- _arg.calendar_: Der Parameter defininiert welche Kalender genutzt wird. In der
  Regel ist der Kalender `gregorian`. Erlaubt sind
  weiter: `buddhist`, `chinese`, `coptic`, `dangi`, `ethiopic`, `ethiopic`, `gregorian`, `hebrew`, `indian`, `islamic`, `islamic`, `islamic`, `islamic`, `islamic`, `julianisch`, `japanese`, `persian`, `roc`.
  Der Parameter gilt für alle
  Typen (`fixed`, `fixedshifting`, `weekdayly`, `easterly` oder `mooninmonth`).
- _arg.status_: Genutzt wird der Eintrag
  in `fixedmultiyear`, `fixedrelated`, `season`, `weekdayly` oder `mooninmonth`.
  Bei `weekdayly` oder bei `fixedrelated` gibt er den Wochentag als Ziffer (1 =
  Montag, ...7 = Sonntag) an. Bei `mooninmonth` gibt er die Mondphase (1 =
  zunehmender Halbmond, 2 = Vollmond, 3 = abnehmender Halbmond, 4 = Neumond) an.
  Bei `season` gibt er den Beginn der astronomischen Jahreszeit (1 =
  Frühling-Tag-Nacht-Gleiche, 2 = Sommersonnenwende, 3 Herbst-Tag-Nacht-Gleiche,
  4 = Wintersonnenwende) an. Bei `fixedmultiyear` wird hier ein Bezugjahr
  angegeben.
- _arg.statusCount_: Genutzt wird der Eintrag
  in `fixedshifting`, `fixedrelated`, `seasonshifting`, `fixedmultiyear`, `weekdayly`,`mooninmonth`
  oder `easterly`. Bei `fixedshifting` wird über eine kommaseparierte Liste die
  Abweichung zum bestehenden Wochentag definiert. Bei `easterly` wird der
  Abstand zum Ostersonntag definiert. Bei `weekdayly` oder bei `fixedrelated`
  wird angegeben, welcher (erster, zweiter, ..) Wochentag im entsprechenden
  Monat bzw. relativ zum Fixdatum gemeint ist. Negative Zahlen zählen vom
  Monatsende aus bzw. in Richtung Vergangenheit. Bei `mooninmonth` wird
  definiert, ob die erste oder zweite Mondpahse im Monat gemeint ist.
- _arg.secDayCount_: Genutzt wird der Eintrag nur in `weekdayly`
  oder `fixedrelated`. Er definiert den Abstand in Tage relativ zum ausgewählten
  Wochentag. (Sonderdefinition für den Schweizer bzw. Eidgenössischen Dank-,
  Buss- und Bettag )

##### Verschiedene Typen der Feiertagsberechnung

- `fixed`: Dieser Type definiert den Feiertag über ein bestimmtes Datum im
  Kalender.
- `fixedrelated`: Dieser Type erklärt sich am besten über den Advent, welches
  der i.te Sonntag vorm 25.12. ist. Hier wird das Fixdatum definiert. Im
  Parameter `status` wird die Nummer des Wochentages (0=Sonntag,..,6=Samstag).
  In `statusCount` wird die Anzahl der Wochentage angegeben, wobei eine negative
  Zahl für den i-ten Wochentag vorm Fixtag meint. Wenn in `arg.secDayCount` noch
  eine Zahl steht, dann wird das Datum bestimmt, welches die entsprechende
  Anzahl von Tage vom i-ten Wochentag entfernt ist. (Dies wird zum Beispiel für
  den Buß- und Bettag benötigt.)
- `fixedshifting`: Dieser Type definiert den Feiertag über ein bestimmtes Datum
  im Kalender, wobei verschiedene Wochentage zu Abweichungen (
  Substitutuioonsfeiertag) führen können.
- `fixedmultiyear`: Dieser Type definiert den Feiertag über ein bestimmtes Datum
  im Kalender, wobei der Termin aber nur alle x Jahre gefeiert wird.
  In `arg.status` wird ein Jahr angegeben, in welchem der Tage gefeiert wurde.
  In `arg.statusCount`findet sich der Wert, nach wie vielen Jahren der Tag
  erneut gefiert wird. Der alle sechs Jahre stattfindende
  Präsidentschaftswechsel in Mexiko braucht zum Beispiel diese Variante, wenn
  ich es richtig verstanden habe.
- `season`: Hier wird der Anfang einer astronomischen Jahreszeit bestimmt (
  Tag-Nachtgleiche im Frühling, ...)
- `seasonshifting`: Hier wird der Anfang einer astronomischen Jahreszeit
  bestimmt (Tag-Nachtgleiche im Frühling, ...), wobei es an bestimmten
  Wochentage wie bei `fixedshifting` substituierende Abweichungen geben kann.
- `weekdayly`: Hier wird ein bestimmter Wochentag in einem bestimmten Monat zum
  Feiertag erklärt.
- `easterly`: Hier bezieht sich der Feiertag auf den jeweiligen Ostersonntag.
  Diese Feiertage beziehen sich entwweder auf den Julianischen oder den
  gregorianischen Kalender.
- `mooninmonth`: Hier wird eine bestimmte Mondphase in einem bestimmten Monat
  erwartet.

##### Definition von Parameter für Timer der Extension

Der erste Eintrag in der Beispieldatei deutet an, wie man Timer der Funktion in
der Excel-Datei definieren kann. Es sind dann jeweils die Daten zu füllen, die
der Timer üblicherweise braucht. (Siehe Flexform-Felder)
Der Name des Timers ist in der Spalte `arg.type` einzugeben.

#### Generelle Parameter bei allen vordefinierten Timern

Einige Parameter sind bei allen Timern gleich. Zwei Parameter behandeln den
Umgang mit Zeitzonen. Zwei weitere Parameter
bestimmen den Zeitraum, in welchen der Timer überhaupt gültig ist. Auf einen
Parameter zur Steuerung des Scheduler wurde
verzichtet. Mir ist kein Anwendungsfall eingefallen, wo ein solcher Ausschluss
wirklich sinnvoll ist.
Wer so etwas braucht, kann gern einen entsprechenden Timer programmieren.

* timeZoneOfEvent - speichert den Namen der zu verwendenden Zeitzone. wenn die
  Zeitzone des Server mit der Zeitzone des
  Event nicht übereinstimmt, wird die Server-ezeit auf die Zeitzonenzeit des
  Events umgerechnet.
  *Wertebereich*:
  Liste der generierten Zeitzonen. Die Zeitzonenproblematik ist wichtig, weil
  einige Timer die Zeiten auf UTC umrechen
  müssen. (Sonnenlauf, ...)
  *Anmerkung*:
  Aktuell werden alle Zeitzonen generiert. Es besteht die Möglichkeit, die
  Zeitzonen auf einen generelle Auswahl zu
  beschränken.
* useTimeZoneOfFrontend - ja/nein-Parameter. Wenn der Wert gesetzt ist, wird
  immer die Zeitzone des Servers verwendet.
* ultimateBeginningTimer - Ultimativer Beginn der Timers
  *Default*:
    1. Januar 0001 00:00:00
* ultimateEndingTimer - Ultimatives Ende des Timers
  *Default*:
    31. Dezember 9999 23:59:59

#### Customtimer - Developer - Motivation

Die Timer decken nicht jeden Fall ab. Sie können auch eigene Timerklasse
definieren, die das `TimerInterface`
implementieren muss. . Die binden sie über ihre  `ext_localconf.php` ein. Über
eigenen Flexform können sie ihrem Timer
eigene Parameter mitgeben.

### Viewhelper

Es gibt fünf Viewhelper:

- timer:isActive - funktioniert ähnlich wie `f:if`, wobei geprüft wird, ob ein
  Zeitpunkt im aktiven Bereich eines
  periodischen Timers liegt.
- timer:flexToArray - Wenn man eine Flexform-Definition in einen Array
  umwandelt, dass enthält der Array viele
  überflüssige Zwischenebenen. Mit dem Viewhelper lassen sich diese Ebenen
  entfernen, so dass der resultierenArray des
  Flexformarrays flacher/einfacher wird.
- timer:forCommaList - funktioniert analog wie `f:for`, nur dass statt eines
  Arrays oder eines iterierbaren Objects
  hier ein String mit einer komma-separierten Liste beim Attribute `each`
  anzugeben ist. Über den zusätzlichen Parameter `limiter` kann
  man das Komma auch durch andere Zeichen ersetzen. Über den zusätzlichen
  boolschen Schalter `trim` kann man erzwingen, dass
  bei den einzelnen Strings aus der Liste die Weißzeichen (Space, Umbruch, ...)
  am Anfang und Ende des Strings entfernt werden.
- timer:format.date - funktioniert wie `f:format.date`, wobei es zusätzlich die
  Ausgabe von Zeiten für eine bestimmte Zeitzone
  erlaubt.
- (entfernt in 12.2.0) ~~timer:format.jewishDate - funktioniert
  ähnlich `f:format.date`, wobei es die Ausgabe von Zeiten für eine bestimmte
  Zeitzone
  erlaubt und wobei die Datumsangaben in den jüdischen Kalender transformiert
  werden.
  **Deprecated - Wird in Version 12 entfernt! _Nutzen sie stattdessen den neuen
  Viewhelper `timer:format.calendarDate`_**~~
- timer:format.calendarDate - funktioniert umfassender als `f:format.date`, weil
  er neben der Berücksichtigung der Zeitzone auch die Auswahl der verschiedenen
  von PHP unterstützten Kalender
  erlaubt und auch drei statt bisher zwei Datumsformatierungsvarianten erlaubt.
  Neben der Definition als gemäß der strftime-Formatierungsregeln und der
  dateTimeInterface::format-Formatierungsregeln kann auch die
  ICU-Formatierungssprache verwendet werden.
  Ein bekanntes Manko ist, dass die Umrechnung vom chinesischen Mondkalender in
  den gregorianischen (westlichen) Sonnenkalender mit einem Fehler behaftet ist,
  der im PHP zu suchen ist. (entfernt in 12.2.0) ~~Der Timer macht den
  Viewhelper ``timer:format.jewishDate`` überflüssig.~~

#### timer:format.calendarDate - Attribute

- **flagformat** bestimmt, welche Formtierungsregeln benutzt werden sollen:
  0 = [PHP-DateTime](https://www.php.net/manual/en/datetime.format.php),
  1: [ICU-Datetime-Formatierung](https://unicode-org.github.io/icu/userguide/format_parse/datetime/)
  oder 2 = [PHP-strftime](https://www.php.net/manual/en/function.strftime.php).
- **format** definiert die Form der Ausgabe des Datums.
- **base** ist für relative Datumsangaben wie 'now', '+4 days' oder ähnliches
  wichtig.
- **timezone** definiert, für welche Zeitzone ein Datum ausgegeben werden soll.
  Eine Liste der zulässigen-Zeitzonennamen erhalten sie über die
  PHP-Funktion `timezone_abbreviations_list()`. Aber auch in der
  PHP-Dokumentation finden sie
  eine [nach Kontinenten vorgeordnete Liste](https://www.php.net/manual/en/timezones.php).
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
  persian', 18:'roc'. Zusätzlich ist als 19. auch 'julian' für den Julianischen
  Kalender erlaubt.
- **calendartarget** definiert den Kalender, für welchen das das Datum
  ausgegeben werden soll. PHP erlaubt folgende Werte: 0:'buddhist', 1:'chinese',
  2:'coptic', 3:'dangi', 4:'default', 5:'ethiopic', 6:'ethiopic-amete-alem', 8:'
  gregorian', 9:'hebrew', 10:'indian', 11:'islamic', 12:'islamic-civil', 13:'
  islamic-rgsa', 14:'islamic-tbla', 15:'islamic-umalqura', 16:'japanese', 17:'
  persian', 18:'roc'. Zusätzlich ist als 19. auch 'julian' für den Julianischen
  Kalender erlaubt.
- **locale** bestimme die regionale Lokalisierung und setzt sich aus dem
  zweibuchstabigen Sprachkürzel (de, en, fr, es, ...) und getrennt durch einen
  Unterstrich aus dem Kürzel für die Nation (DE, GB, US, AT, CH, FR, ...). Der
  Wert in __locale__ könnte zum Beispiel folgendes Aussehen
  haben: `de_DE`, `en_GB` oder auch `es_US`.

### Dataprozessoren

Da die Ergebnisse der Datenprozessoren gecacht werden, muss sich der User
überlegen, was ein sinnvoller Caching-Zeitraum ist und dies entsprechend
definieren.
Alle Dataprocessoren kennen zur Definition der Cache-Zeit den
Parameter ``cache``.
Ein fehlender Wert führt zum Defaultfall.
Bei den Dataprozessoren, die die Timerfunktionalitäten nutzen,
wird bei der Berechnung der Daten automatisch der Frontend-Cache gelöscht.

Alle Parameter werden über `stdWrap` ausgewertet.
Das heißt, statt explizieter Werte kann immer auch Typscript zur dynamischen
Definition der
Werte verwendet werden.

Es ist prüfen, ob die Dynamisierung der Parameter zu Konflikten mit dem Caching
führt.
Im Zweifel deaktivert man das Caching mit ``cache = none``.
Auch erlaubt sind dafür die Ausdrücke `0`, `` ``, ``none``, ``no``
oder ``null``.
Explizit den Defaultfall für das Caching triggert man mit ``cache = default``.

Grundsätzlich sollte im Sourcecode der jeweiligen DataProcessoren als Kommentar
ein Beispiel für die Anwendung desselben zu finden sein.
Für die Freunde der TypoScript-Programmierung sei gesagt, dass die Parameter
über die stdWrap-Methode eingelesen werden. Die rekursive Nutzung von Typoscript
zur Dynamisierung der Aufsetzung ist also möglich; auch wenn es hier
ausdrücklich nicht empfohlen wird.

#### RangeListQueryProcessor

Der Prozessor erstellt für die Datensätze mit periodischen Timern aus einer
Tabelle eine Liste von Terminen. Der
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

        # sort in reverse order
        #            reverse = false

        # name of output-object
        as = examplelist

         # deactivate the caching of the rangelist-processor
         # the frontend-cache of the current page will be cleared every time
         cache = none
    }
}

```

Siehe auch Beispiel in exemplarischen Contentelement ``timersimul``

##### _Parameter für den Dataprocessor `RangeListQueryProcessor`_

Wegen der Wiederholung von Perioden kann in der Liste ein Datensatz mehrfach
aufgezählt werden. Deshalb sind immer auch ein Start-Zeitpunkt und ein
End-Zeitpunkt zu definieren.
Bei jeder Neuberechnung der Liste wird auch der Frontend-Cache der
entsprechenden Seite gelöscht.

##### _Example in Typoscript_

```
tt_content.timer_timersimul >
tt_content.timer_timersimul < lib.contentElement
tt_content.timer_timersimul {

     templateName = timersimul

     dataProcessing.10 = Porthd\Timer\DataProcessing\RangeListQueryProcessor
     dataProcessing.10 {
         table = tx_timer_domain_model_event

         # defines, the page-id of the current page
         pidInList.stdWrap.cObject = TEXT
         pidInList.stdWrap.cObject.field = uid
         recursive = 1

         # sort in reverse order
         # reverse = false

         # name of output object
         as = examplelist

         # deactivate the caching of the rangelist-processor
         # the frontend-cache of the current page will be cleared every time
         cache = none
     }
}

```

Siehe auch Beispiel für Inhaltselement ``timersimul``

Für die Berechnung der Seiten verwendet der DataProzessor die getRecords-Methode
des ContentObjektRenderers, welcher seinerseits die Parameter
``pidInList``,
``uidInList``,
``languageField``,
``markers``,
``includeRecordsWithoutDefaultTranslation``,
``selectFields``,
``max``,
``begin``,
``groupBy``,
``orderBy``,
``join``,
``leftjoin``,
``rightjoin``,
``recursive`` und
``where`` interpretiert (
Siehe [TypoScript>CONTENT>select](https://docs.typo3.org/m/typo3/reference-typoscript/main/en-us/Functions/Select.html)
für weitere Informationen).

| Parameter        | Default                                                                                                                                | Beschreibung
|------------------|----------------------------------------------------------------------------------------------------------------------------------------|---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
|                  | **_Datensätze_**                                                                                                                       |
| if               | true                                                                                                                                   | Wenn der wert oder der Typoscript-Ausdruck falsch ergeben, wird der Dataprocessor nicht ausgeführt.
| table            | tx_timer_domain_model_event                                                                                                            | Diese Tabelle wird verwendet, um nach allen verfügbaren Datensätzen mit Timer-Informationen zu suchen.
| as               | records                                                                                                                                | Name der Outputvariablen, die z.B. im Fluid-template genutzt wird
|                  | **_Start und Allgemeines_**                                                                                                            |
| datetimeFormat   | Y-m-d H:i:s                                                                                                                            | Definiert das Format, in welchem das Datum angegeben wird. Es gelten die Zeichen, die im PHP definiert sind (siehe [Liste](https://www.php.net/manual/en/datetimeimmutable.createfromformat.php)).
| datetimeStart    | &lt;jetzt&gt;                                                                                                                          | Definiert den Zeitpunkt, mit welchem die Liste beginnen soll. Bei `reverse = false` ist es der früheste Zeitpunkt, und bei `reverse = true` ist es der späteste Zeitpunkt.
| timezone         | &lt;definiert im PHP-System&gt;                                                                                                        | Definiert die Timezone, die bei den Datumswerten verwendet werden soll.
| reverse          | false                                                                                                                                  | Definiert, ob die Liste der aktiven Bereiche absteigend oder aufsteigend sortiert ist. Bei `reverse = true` ist jeweils das Ende der aktiven Bereiche maßgeblich; Beim Defaultfall `reverse = true` ist es entsprechen der Anfang der aktiven Zeit.
| cache            | default                                                                                                                                | Definiert das Cache-Verhalten. Im Defaultfall wird die Cachingzeit  basierte auf der Timerliste berechnet. Eine Zahlenangabe übersteuert die berechnete Caching-Zeit durch einen statische Wert. Die Parameter ``0``,`` ``,``no``,``none``, oder ``null`` führen dazu, dass der Dataprozessor jedesmal neu die Daten berechnet.
|                  | **_Limit der Periode_**                                                                                                                |
| maxCount         | 25                                                                                                                                     | Begrenzt der Liste über die maximale Anzahl der Listenelemente
| maxLate          | &lt;sieben Tage relativ zum Startdaum&gt;                                                                                              | Begrenzt die Liste über ein Stop-Datum, dass nie erreicht werden kann.
| maxGap           | P7D                                                                                                                                    | Begrenzt die Liste, indem aus dem Startzeitpunkt der entsprechende Stopzeitpunkt berechnet wird. Für die Angabe der zeitlichen Differenz ist die PHP-Notation für Zeitintervalle zu verwenden (siehe [Übersicht](https://www.php.net/manual/en/class.dateinterval.php)).
|                  | **_Spezielles_**                                                                                                                       |
| userRangeCompare | `Porthd\Timer\Services\ListOfEventsService::compareForBelowList` oder `Porthd\Timer\Services\ListOfEventsService::compareForAboveList` | Für die Bestimmung der Reihenfolge werden nur die Datumswerte verwendet. Der Nutzer könnte auch andere Sortierungskriterien berücksichtigen. Zum Beispiel könnte man eine Liste haben wollen, die zuerst nach dem Startdatum und bei gleichem Startdatum nach der Dauer der aktiven Bereiche sortiert wäre.

#### SortListQueryProcessor

Die Tabelle `sys_file_reference` unterstützt nicht die Felder `starttime`
und `endtime`. Um trotzdem zeitliche
variierende Bilder zu erreichen, kann man die per Datenprozessor erhalten Medien
in einen nach Periodizität sortierte
Liste überführen lassen und umwandeln lassen und im Template entsprechend
nutzen.

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

Wegen der Wiederholung von Perioden kann in der Liste ein Datensatz mehrfach
aufgezählt werden.
Deshalb sind immer auch ein Start-Zeitpunkt und ein End-Zeitpunkt zu definieren.
Bei jeder Neuberechnung der Liste wird auch der Frontend-Cache der
entsprechenden Seite gelöscht.

Im Gegensatz zum `RangeListQueryProcessor` nutzt der `SortListQueryProcessor`
Daten, die von einem vorherigen oder übergeordneten Dataprozessor-Prozeß erzeugt
wurden.
Die Parameter sind ähnlich wie beim `RangeListQueryProcessor`.
Da der Dataprocessor aber für die Weiterverarbeitung von Timerliste verwendet
wird, wird die Methode getContent nicht mehr verwendet.
Deshalb wird die Parameter `table` durch den Parameter `fieldName` ersetzt.

| Parameter        | Default                                                                                                                                | Beschreibung
|------------------|----------------------------------------------------------------------------------------------------------------------------------------|---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
|                  | **_Datensätze_**                                                                                                                       |
| if               | true                                                                                                                                   | Wenn der wert oder der Typoscript-Ausdruck falsch ergeben, wird der Dataprocessor nicht ausgeführt.
| fieldName        | myrecords                                                                                                                              | Diese Tabelle wird verwendet, um nach allen verfügbaren Datensätzen mit Timer-Informationen zu suchen.
| as               | records                                                                                                                                | Name der Outputvariablen, die z.B. im Fluid-template genutzt wird
|                  | **_Start und Allgemeines_**                                                                                                            |
| datetimeFormat   | Y-m-d H:i:s                                                                                                                            | Definiert das Format, in welchem das Datum angegeben wird. Es gelten die Zeichen, die im PHP definiert sind (siehe [Liste](https://www.php.net/manual/en/datetimeimmutable.createfromformat.php)).
| datetimeStart    | &lt;jetzt&gt;                                                                                                                          | Definiert den Zeitpunkt, mit welchem die Liste beginnen soll. Bei `reverse = false` ist es der früheste Zeitpunkt, und bei `reverse = true` ist es der späteste Zeitpunkt.
| timezone         | &lt;definiert im PHP-System&gt;                                                                                                        | Definiert die Timezone, die bei den Datumswerten verwendet werden soll.
| reverse          | false                                                                                                                                  | Definiert, ob die Liste der aktiven Bereiche absteigend oder aufsteigend sortiert ist. Bei `reverse = true` ist jeweils das Ende der aktiven Bereiche maßgeblich; Beim Defaultfall `reverse = true` ist es entsprechen der Anfang der aktiven Zeit.
| cache            | default                                                                                                                                | Definiert das Cache-Verhalten. Im Defaultfall wird die Cachingzeit  basierte auf der Timerliste berechnet. Eine Zahlenangabe übersteuert die berechnete Caching-Zeit durch einen statische Wert. Die Parameter ``0``,`` ``,``no``,``none``, oder ``null`` führen dazu, dass der Dataprozessor jedesmal neu die Daten berechnet.
|                  | **_Limit der Periode_**                                                                                                                |
| maxCount         | 25                                                                                                                                     | Begrenzt der Liste über die maximale Anzahl der Listenelemente
| maxLate          | &lt;sieben Tage relativ zum Startdaum&gt;                                                                                              | Begrenzt die Liste über ein Stop-Datum, dass nie erreicht werden kann.
| maxGap           | P7D                                                                                                                                    | Begrenzt die Liste, indem aus dem Startzeitpunkt der entsprechende Stopzeitpunkt berechnet wird. Für die Angabe der zeitlichen Differenz ist die PHP-Notation für Zeitintervalle zu verwenden (siehe [Übersicht](https://www.php.net/manual/en/class.dateinterval.php)).
|                  | **_Spezielles_**                                                                                                                       |
| userRangeCompare | `Porthd\Timer\Services\ListOfEventsService::compareForBelowList` oder `Porthd\Timer\Services\ListOfEventsService::compareForAboveList` | Für die Bestimmung der Reihenfolge werden nur die Datumswerte verwendet. Der Nutzer könnte auch andere Sortierungskriterien berücksichtigen. Zum Beispiel könnte man eine Liste haben wollen, die zuerst nach dem Startdatum und bei gleichem Startdatum nach der Dauer der aktiven Bereiche sortiert wäre.

#### FlexToArrayProcessor

Der `FlexToArrayProcessor` ermöglicht das Lesen von `Flex`-Feldern und wandelt
sie in einfache Arrays um.
Auf diese Weise könnten die kalenderspezifischen Ressourcen einfach für das
Inhaltselement `periodlist` nachgeladen werden.

```
        30 = Porthd\Timer\DataProcessing\FlexToArrayProcessor
        30 {
            # reguläre if-Syntax, um die Anwendung des Datenprozessor zu verhindern
            #if.isTrue.field = record

            # Feld, dass den Flexform-Array enthält
            # Standard ist `tx_timer_timer`
            field = tx_timer_timer

            # Eine Definition von Flattenkeys überschreibt die Standarddefinition.
            #    Die Attribute `timer` und `general` werden als Blattnamen in meinen customTimer-flexforms verwendet
            #    Die folgende Definition ist die Standard-Vorgabe bei fehlender Definition: `data,general,timer,sDEF,lDEF,vDEF`
            flattenkeys = data,general,timer,sDEF,lDEF,vDEF

            # Ausgabevariable mit der resultierenden Liste als Array
            as = flexlist

```

##### Parameter für den FlexToArrayProcessor

| Parameter   | Default                           | Beschreibung
|-------------|-----------------------------------|---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
| if          | true                              | Wenn der Wert oder der Typoscript-Ausdruck falsch ergeben, wird der Dataprocessor nicht ausgeführt.
| field       | tx_timer_timer                    | Der Name des Feldes, welcher den Flexform-String enthält.
| flattenkeys | data,general,timer,sDEF,lDEF,vDEF | Diese Tabelle wird verwendet, um nach allen verfügbaren Datensätzen mit Timer-Informationen zu suchen.
| as          | flattenflex                       | Name der Outputvariablen, die z.B. im Fluid-template genutzt wird
| cache       | default                           | Definiert das Cache-Verhalten. Im Defaultfall wird die Cachingzeit  basierte auf der Timerliste berechnet. Eine Zahlenangabe übersteuert die berechnete Caching-Zeit durch einen statische Wert. Die Parameter ``0``,`` ``,``no``,``none``, oder ``null`` führen dazu, dass der Dataprozessor jedesmal neu die Daten berechnet.

#### (removed in 12.3.0)  ~~MappingProcessor (deprecated)~~

(removed in 12.3.0) ~~Der Datenprozessor `MappingProcessor` erlaubt das
Mappen/Abbilden von Arrays in neue Arrays oder in einen JSON-String.
So können die Daten leicht HTML-Attribute dem JavaScript zur Verfügung gestellt
werden.
Der Datenprozessor kennt einfache generische Funktionen, um zum Beispiel Events
eindeutige IDs zuzuordnen.
Weiter erlaubt er das Mappen/Abbilden von Feldinhalten und das Anlegen von neuen
Feldern mit konstanten Daten.~~

~~removed code-exampe for typoscript~~

#### BetterMappingProcessor

Manchmal nutzt man ein JavaScript-Framework wie zum Beisüpiel ein
Kalender-Framework im Frontend,
welches eine Liste von Daten in einem definierten Datenformat als JSON-String
benötigt.
Das Problem ist, dass TYPO3 die Daten zwar kennt aber in anderer Form
gespeichert hat.
Der BetterMappingProcessor hilft, vorhandene Datenlisten in leicht abgewandeltes
Datenlisten zu übersetzen.

!!!Sobald man den BetterMappingProcessor einsetzt, sollte man sich immer fragen,
ob es nicht eine bessere Lösung gibt, was sehr wahrscheinlich ist.

Der Datenprozessor `BetterMappingProcessor` erlaubt das Mappen/Abbilden von
Arrays mit Unterarrays bzw. Array von Datenobjekten in neue assoziative Arrays
oder in einen JSON-String.
Die Logik ist zum Mapping-Dataprocessor leicht abgewandelt, jetzt die Input- und
Outputfelder direkt definiert werden müssen.
Durch die Punkt-Notation ist es möglich, bei assoziativen Array mit mehreren
Ebenen die Daten aus den tieferen Ebenen einzulesen bzw. für die Ausgabe einen
assoziativen Array mit mehreren Ebenen zu erzeugen.
Der Generic-Bereich wurde um zwei Varianten erweitert.
~~Es ist für die Zukunft geplant, den Dataprozessor mit einer Schnittstelle für
eine User-Funktion zu erweitern.~~
Wie bisher erlaubt der Dataprocessor das Mappen/Abbilden von Feldinhalten, von
Datumswerten und auch das Anlegen von neuen Feldern
mit konstanten Daten.

```
        20 = Porthd\Timer\DataProcessing\BetterMappingProcessor
        20 {

            # regular if syntax
            #if.isTrue.field = record

            # The defaultvalue for the inputfield is 'holidayList';
            inputfield = holidayList
            # Each field must part of holidaycalendar
            # allowed types are
            #    `constant`(=pretext.posttext),
            #    `index`(=pretext.<indexOfDataRow>.posttext)
            #    `datetime` (=dateTimeObject->format(posttext); dateTimeObject is in the Field, which is declared be pretext)
            # every entry must be some formal
            #            generic {
            #                id {
            #                    pretext = event
            #                    posttext = holiday
            #                    type = index
            #                }
            #
            #                calendarId {
            #                    pretext = cal1
            #                    posttext =
            #                    type = constant
            #                }
            #                start {
            #                    pretext = date
            #                    posttext = Y-m-d
            #                    type = datetime
            #                }
            #            }
            generic {
                10 {
                    # the inputfield may missing
                    inField =
                    # if the outputfield is missing or the key has an typeerror, an exception will occur.
                    outField = category
                    pretext = allday
                    posttext =
                    # allowed types are `constant`, `includevalue`, `includeindex`, `datetime`
                    # if the inField is missing for type `includevalue`, a empty string will be used
                    type = constant
                }
                20 {
                    inField = dateStart
                    # the outputfield must contain a DateTime-Object
                    outField = start
                    format = Y-m-d
                    type = datetime
                }
                30 {
                    inField = dateEnd
                    outField = end
                    format = Y-m-d
                    type = datetime
                }
                40 {
                    inField = cal.eventtitle
                    outField = title
                    type = translate
                }
               50 {
                    inField = cal.add.freelocale
                    outField = class
                    type = userfunc
                    # two parameter for customfunc(parameter, $betterMappingProcessorObject)
                    #    where parameter is the associative array with the keys
                    #    `params`, `mapKey`,`mapItem` and `start`
                    userfunc =  Vendor\Namespace\CustomClass->customFunc
                }

            }

            mapping {
                10 {
                    inField = cal.identifier
                    outField = id
                }
                20 {
                    inField = cal.title
                    outField = basetitle
                }
                30 {
                    inField = cal.tag
                    outField = calendarId
                }

            }

            # outputformat has the values `array`,`json`, `yaml`
            # if the outputformat is unknown/undifined, `json` will be used by default
            outputFormat = json

            # if the output-format is yaml, then `yamlStartKey` will define a starting-key for your result-array.
            # the default is an empty string, which emans no starting-key for your array in a simplified yaml-format
            #yamlStartKey = holydayList

            # output variable with the resulting list
            # default-value is `holidayListJson`
            as = holidaycalendarJson

        }

```

##### Parameter für den BetterMappingProcessor

Hinweis: Das Definieren des Mappings wird einfacher, wenn man xdebug/var_dump
nutzt, um sich die Struktur der Transformation zu veranschaulichen.

| Parameter    | Default                                                      | Beschreibung
|--------------|--------------------------------------------------------------|------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
|              | **_Hauptebene_**                                             |
| if           | true                                                         | Wenn der Wert oder der Typoscript-Ausdruck falsch ergeben, wird der Dataprocessor nicht ausgeführt.
| generic.     | &lt;Array mit Definitionen&gt;                               | Erzeuge eine Anzahl von Daten basierend auf dem Index der zu mappenden Liste oder auf konstanten Werten (``stdWrap``-Affin).
| mapping.     | &lt;Array mit Umwandlungsanweisungen&gt;                     | Erzeugt aus den Unterfeld (`inField`) einer Liste einen Eintrag in die Resultatliste mit einem neuen assoziativen Index (`outField`). Neben Standardumwandlungen sind auch Nutzer-definierte Funktionen zuläassig.
| as           | flattenflex                                                  | Name der Outputvariablen, die z.B. im Fluid-template genutzt wird.
| outputFormat | &lt;Exception&;                                              | Diese Angabe ist Pflicht. Es gibt Ausgabeformat für den gemappten assoziativen Array. Erlaubt sind die Werte `json`, `array` und `yaml`.
| yamlStartKey |                                                              | Wenn das Ausgabeformat `yaml` ist, dann kann die List mit einem Oberbegriff versehen werden. (Hilfreichen, wenn man in einer Datei mehrere assoziative Arrays zusammenführen möchte.)
| cache        | default                                                      | Definiert das Cache-Verhalten. Im Defaultfall wird die Cachingzeit  basierte auf der Timerliste berechnet. Eine Zahlenangabe übersteuert die berechnete Caching-Zeit durch einen statische Wert. Die Parameter ``0``,`` ``,``no``,``none``, oder ``null`` führen dazu, dass der Dataprozessor jedesmal neu die Daten berechnet.
|              | **_Unterebene `mapping.`_**                                  | _einfaches Mapping_
| inField      |                                                              | Pfad zum Datum im benannten Feld im Datensatz aus der Datenliste, wobei die Punktnotation bei den Namen den Zugriff auf tieferen Ebenen in dem assoziativen Datenarray erlaubt. (Achtung: Wenn das Datum im Datenarray seinerseits ein Array ist, wird nur der erste Eintrag des Arrays übertragen, sofern dies ein Skalar - also int, float, boll oder string - ist.)
| outField     |                                                              | Pfad für die Daten im neu generierten Datensatz im assoziativen Ergebnisarray, der mit dem Dataprozessor generiert wird, wobei die Punktnotation bei den Namen auch die Erzeugung von tieferen Ebenen im assoziativen Ergebnisarray erlaubt. (Kein Check gegen Überschreibungen während des Generierungsprozesses)
|              | **_generelle Inputfelder in der Unterebene `generic.`_**     | _einfach-generisches Mapping_
| inField      |                                                              | Pfad zum Datum im benannten Feld im Datensatz aus der Datenliste, wobei die Punktnotation bei den Namen den Zugriff auf tieferen Ebenen in dem assoziativen Datenarray erlaubt.
| outField     |                                                              | Pfad für die Daten im neu generierten Datensatz im assoziativen Ergebnisarray, der mit dem Dataprozessor generiert wird, wobei die Punktnotation bei den Namen auch die Erzeugung von tieferen Ebenen im assoziativen Ergebnisarray erlaubt. (Kein Check gegen Überschreibungen während des Generierungsprozesses)
| pretext      |                                                              | Definiert einen konstanten voranzustellenden Ausdruck, der auch per Typoscript erzeugt werden kann. (Parameter einwelsen per `stdWrap`-Methode; statt statische Ausdrücke kann man für Übersetzungen auch `LLL:EXT:...`-Werte verwenden)
| posttext     |                                                              | Definiert einen konstanten nachzustellenen Ausdruck, der auch per Typoscript erzeugt werden kann. (Parameter einwelsen per `stdWrap`-Methode; statt statische Ausdrücke kann man für Übersetzungen auch `LLL:EXT:...`-Werte verwenden)
| type         |                                                              | Definiert die generische Methode der generischen Erzeugung. Definiert sind die im Nachfolgenden genannten Varianten: ... .
|              | **_`generic.type=constant`_**                                | Ignoriert jegliche Input-Information aus aktuellen Datensatz.
|              | **_`generic.type=index` oder `generic.type=includeindex` _** | Nutzt den Wert des aktuellen Indexes des ausgewählten Datensatzes aus der Datenliste (meist einen Nummer; nur im assoziativen Array oder iterativen Objekt ein String).
|              | **_`generic.type=value` oder `generic.type=includevalue` _** | Nutzt den skalaren Wert des aktuell über `inField` definierten Feldes des ausgewählten Datensatzes aus der Datenliste.
|              | **_`generic.type=translate`_**                               | Interpretiert den skalaren Wert des aktuell über `inField` definierten Feldes des ausgewählten Datensatzes aus der Datenliste als Schlüssel bzw. als Schlüsselpfad für einen Wert in einer `xlf`-Übersetzungsdatei. (Solange man nicht mit `LLL:EXT:...` einen expliziten key-Pfad defniniert, wird per Default die `localconf.xlf`-Datei der Timer-Extension verwendet, die man [auch von externen Extensions aus um eigenen Schlüssel per 'override' erweitern](https://docs.typo3.org/p/lochmueller/autoloader/7.4/en-us/Loader/LanguageOverride.html) kann.)
|              | **_`generic.type=datetime`_**                                | Konvertiert das DateTime-Objekt aus dem über `inField` definierten Feldes des ausgewählten Datensatzes aus der Datenliste in ein in 'format' definiertes Datumformat.
| format       |                                                              | Definiert das Augabeformat für den DateTime-Wert, der erzeugt werden soll. Wenn keinen Angabe gemacht wurde, wird per Default das Format `Y-m-d\TH:i:s` verwendet. Die [Parameter zur Defintion des DateTime-Formats](https://www.php.net/manual/en/datetime.format.php) findet man im php-Manual.
|              | **_`generic.type=userfunc`_**                                |
| userfunc     | &lt;Vendor\Extension\PfadZuKlasse->userFunctionName&gt;      | Definition der User-Funktion, die zwei Parameter übergeben bekommt: erstens den Parameterarray mit der aktuellen userfunc-Konfiguration (`params`), dem Key des Datensatze (`mapKey`), dem gesamten Datensatz (`mapItem`) und dem skalaren `inField`-Wert (`start`) sowie zweitens das Objekt des aktuellen BetterMapping-Prozessors.
| 'beliebig'   | &lt;bleibige Werte&gt;                                       | In dem Unterfeld (`params`), das als Parameter übergeben wird, finden sich auch belibig definierte Parameter, die hier neben dem Methodenpfad in disem Abschnitt für die `userfunc` definiert worden sind. (Der Nutzer ist also frei, für seine Userfunktion eigenen Steuer-Parameter zu definieren.)

#### PhpMappingProcessor

Manchmal nutzt man ein JavaScript-Framework wie zum Beispiel ein
Kalender-Framework im Frontend,
welches eine Liste von Daten in einem definierten Datenformat als JSON-String
benötigt.
Das Problem ist, dass man in TYPO3 die Daten nur in abweichender Datenstruktur
gespeichert hat.
Der ``PhpMappingProcessor`` soll helfen, solche Strukturabweichungen zu
überwinden.
Das Grundprinzip des ``PhpMappingProcessor`` ist einfach. Je nach Inputstruktur
kann man einen
Zielrecord oder einen Array von Zielrecords generieren lassen. Man bildet im
TypoScript
unterhalb des Attributs `output` im TYpoScript-Stil über die Schlüsselbegriffe
die Zielstruktur
des/der gewünschten Records nach. Den Zielbegriffen kann man Konstanten,
Datenreferenzen, oder Funktionen zuordnen. Die Parameter der Funktionen werden
rekursive aufgelöst.
Über diesen Weg bekommt man Übersichtlichkeit und größtmögliche Flexibilität
geliefert.
Auf der Input-Seite müssen die Daten entweder über einem assoziativen Array,
über einem stdClass-Object oder über ein Getter-Objekt verfügbar sein.

##### Beispielhaftes TypoScript

````
        15 = Porthd\Timer\DataProcessing\PhpMappingProcessor
        15 {

            outputformat = json
            as = holidaycalendarJson

            limiter {
                inputPart = '.'
                output {
                    path = @
                    part = .
                    params = ,
                    default = #
                }
            }

            # The parent-variable contain an array i.e. data. The datamapper will use it only once.
            # It can although use a part of a dataset.
            # if you use the parameter `_all`, you can use the whole array of processed data `$processedData`.
            inputOrigin = holidayList
            # The parent-variable contain an array with multiplerows. The datamapper will iterate through all rows
            # and generate a list of converted rows.
            # two values allowed 'rows' oder 'static'
            inputType = rows

            # You can define the mapping a separate file instead of using  typoscript.
            # The filename should have the ending .yaml,.yml,.csv or .json
            # Be aware that .csv-files could only contain simple array.
            # dataFile =  EXT:FilePath/FileName.json
            output {
                # A string without the brackets "(" ")" will be interpreted as a data-containing string.
                # A string with the brackets "(" and the ending ")" will be interpreted as a userfunction.
                # The part outside the brackets does not contain any space oder colons.
                # A userfunction has thisway one of the three structures:
                #    methodName(<parameterlist>) (pure php method)
                #    Namespace/Of/Class->methodName(<parameterlist>) (every time the class will newly be instanciated)
                #    Namespace/Of/Class::methodName(<parameterlist>) (static method: recommended)
                # The parameter separated by a comma by default. The comma can be escaped by `\,`.
                # Unknown methods, missing/wrong parameters will cause an exception.
                # Call of methods in the parameter-list are allowed.
                # The attributes under rows only for Security-reasons
                # The type of output of @datatPath@ is not defined. It depends on the the structure of the origin.
                # If you need a specific type like an integer a flaot, then you should use floatval(@datatPath@) or
                # intval(@datatPath@). The custum function must always handle all types - unwished types with an exception.

                category = allday
                # Remark: starting the namespace of the custom method with `\` will cause an error
                start = Porthd\Timer\UserFunc\MyDateTime->formatDateTime(@dateStart@,'Y-m-d','Europe/Berlin')
                        # (new dateTime(@dateStart@))->format('Y-m-d')
                end = Porthd\Timer\UserFunc\MyDateTime->formatDateTime(@dateEnd@,'Y-m-d','Europe/Berlin')
                        # (new dateTime(@dateEnd@))->format('Y-m-d')
                title = @cal.eventtitle@
                body = Porthd\Timer\UserFunc\ResolveLocales->reduceSingleLocaleToNation(@cal.add.freelocale@)
                id = @cal.identifier@
                basetitle = @cal.title@
                calendarId = @cal.tag@
            }
        }

````

##### Parameter

| Parameter    | Default          | Beschreibung
|--------------|------------------|--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
|              | **_Hauptebene_** |
| configFile   |                  | Statt der Konfiguration im TypoScript kann auch eine YAML-Datei mit allen Konfigurationsanweisungen erstellt werden.
| inputOrigin  | _all             | Pfad zu dem Variablen-Bereich bei den erstellten Daten im bisherigen DataProzessor-Fluss. `_all` meinden gesamten Array.
| inputType    | _all             | Es die die beiden Werte `rows` und `static`. `rows` erwartet einen Array und wendet `output` Zeile für Zeile auf `rows` an, so dass ein Array von Records entsteht. `static` wendet `output` nur einmal auf `rows` an, so dass ein einzelner Record entsteht.
| as           | json             | Name der Variable, wie man sie später im Fluid für das Output verwenden kann
| outputformat | json             | Art des Datenformats. `json` (String), `array` (PHP-Array), `yaml` (String)
| output       | **_Hauptebene_** | Ist der Startpunkt für die Record-Definition (Nachbildung des erwarteten assoziativer Array für die Daten)
| */...        |                  | Die Unterpunkte definieren die Zielstruktur des gewünschten Datenrecords. Man nutzt die Syntax von Typoscript, um die Verschachtelungsstruktur nachzubilden. Bei einer YAML-Datei wird natürlich die YAML-Syntax verwendet.
| limiter      | **_Hauptebene_** | Per Default enthält die Syntax zur Auflösung der Pfade unterschiedliche Trenner. Diese Trenner können übersteuert werden.
| */path       | @                | Um im String einen Datenbezug vom Text abzugrenzen, wird dieser Limiter verwedent. Damit werden String-Anfügungen wie z.B. in "Wir begrüßen @user@ beim Test." leicht möglich.
| */part       | .                | Eine Datenbezug beschreibt ähnlich wie bei einer Ordnerstruktur auf der festplatte einen hierarchisch gegliederten Pfad in der Inputstruktur. Der Trenner grenzt die Keywörter für die Ebenen gegeneinander ab.
| */defpart    | #                | Es kann sein, dass für einen nicht existierendes Datum ein String als Defaultwert eingetragen werden soll. Der Defaultwert ist direkt dem Pfad anzugeben. Beispiel für einen Pfadangabe: @Pfad#Defaultwert@
| */params     | ,                | Der Trenner dient dazu, um innerhalb der Parameter einer Funktion diesselben voneinander abzugrenzen. Dies ist analog zur Syntax in vielen Programmiersprachen.
| */start      | (                | Um den Funktionsnamen von den Parametern abzugrenzen, wird die erste Klammer im String als Trenner verwendet.
| */end        | )                | Wenn einen Funktion verwendet wird, muss diese mit diesem Limiter enden. Dies gilt auch, wenn eine Funktion rekursiv als Parameter verwendet wird.
| */escape     | \\               | Es kann vorkommen, dass in einem String eines der Limiter-Zeichen als Zeichen verwendet wird. Mit dem vorangestellten Escape-Zeichen wird das nachfolgende Zeichen als Text-Zeichen erkannt.
| */dynfunc    | ->               | Neben normalen PHP-Funktionen kann man auch benutzer-definierte Funktionen aus Klassen verwenden. Wenn eine Instanzierung der Funktion nötig ist, dann ist der Namespace der Klasse mit diesen Trenner '->' vom Funktinsnamen abzugrenzen. Das Mapping instanziert zuvor die Klasse neu, bevor die Methode aufgerufen wird. Es wird davon abgeraten, diesen Limiter zu ändern.
| */statfunc   | ::               | Neben normalen PHP-Funktionen kann man auch benutzer-definierte Funktionen aus Klassen verwenden. Wenn eine statische Funktion genutzt wird, dann ist der Namespace der Klasse mit diesen Trenner '::' vom Funktinsnamen abzugrenzen. Es wird davon abgeraten, diesen Limiter zu ändern. Es wird empfohlen, im Mapping gedachtnislose statische Funktionen zu verwenden.

#### PeriodlistProcessor

Der DataProcessor `PeriodlistProcessor` erlaubt das Auslesen der Terminliste,
die beim PeriodlistTimer in der Yaml-Datei
definiert ist. Neben den eigentlichen Feldern generiert der Datenprozessor für
die Start- und Endzueit der Termine auch die entsptrechenden DatTime-Objekte und
berechnet die Anzahl der Tage (24Stunden = 1 Tag) zwischen den Terminen.

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

##### Parameter für den PeriodlistProcessor

Der Dataprozessor Periodlist erlaubt die sortierte Ausgabe von verschiedenen
Zeitbereichen, die explizit auch mehrtägig sein dürfen, die immer durch jeweils
ein Start- bzw. Enddatum gekennzeichnet sind und die definitiv nicht periodisch
sind.
Diese Art der Darstellung eignet sich zum Beispiel für Listen mit Terminen für
Schulferien der verschiedenen Bundesländer oder für Listen mit Tourneedaten
verschiedener Künstler.

Die Struktur der YAML-Dateien ist weiter-Oben beim PeriodlistTimer beschrieben
worden.
Oder sie finden eine beispielhafte Datei auch
in `timer\Resources\Public\Yaml\Example_PeriodListTimerBremen.yaml`.
Beachten sie, dass sie auch weitere Attribute einfügen können, wenn sie
zusätzliche strukturierte Informationen für die Ausgabe im Frontend benötigen.
Diese Attribute bzw. die dazugehörigen statischen Informationen werden leicht
durchgeschleift.

| Parameter      | Default                         | Beschreibung
|----------------|---------------------------------|--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
|                | **_Hauptebene_**                |
| if             | true                            | Wenn der Wert oder der Typoscript-Ausdruck falsch ergeben, wird der Dataprocessor nicht ausgeführt.
| field          | tx_timer_timer                  | Name des Feldes, welches den String mit den Flexforminformationen enthält. In den Flexforminformationen ist entweder der YAML-Pfad enthalten oder die Verweise auf Referenezen zu YAML-Dateien oder zu CSV-Dateien. Beachten sie, dass YAML-Dateien ihrerseites über die `import`-Anweisung weitere yaml-Dateien inkludieren können.
| selectorfield  | tx_timer_selector               | Das Prüffeld definiert den Name des Feldes, welches die Variante der Flexform-Auswahl bestimmt. Dies muss den Wert `txTimerPeriodList` haben.
| tablename      | tt_content                      | Der Tabellenname ist wichtig, wenn der Dataprocessor im Fluidtemplate auf Daten basiert, die nicht aus der Tabelle `tt_content` oder der Tabelle `pages` kommen.
| limit.         |                                 | Definiert einen TypoScript-Array für die Intervallgrenzen für die Liste, die aus den Listen mit periodischen Daten erzeugt werden.
| flagStart      | true                            | Definiert, ob die verschiedenen Termine nach der oberen Grenze (false \| 0 \| '') oder nach der unteren Grenze (true) sortiert werden.
| maxCount       | 25                              | Maximale Anzahl an Terminen, die in die Liste überführt werden. Dies ist eine Pflichtangabe, die gegebenenfalls auch die Intervalgrenzen von `limit.` übersteuert. Es gibt keinen Wert für 'unendlich'.
| &lt;start&gt;. | &lt;definiert im TypoScript&gt; | Der Name definiert das Outputfeld für den Startwert eines Termins in der neuen Terminliste. Der kann so benannt werden, wie man es später zum Beispiel in einem JSON-String braucht.
| &lt;end&gt;.   | &lt;definiert im TypoScript&gt; | Der Name definiert das Outputfeld für den Endwert eines Termins in der neuen Terminliste. Der kann so benannt werden, wie man es später zum Beispiel in einem JSON-String braucht.
|                | **_Unterebene `limit.`_**       | _Definiert den Bereich der Ausgaben durch zwei Datumswerte_
| lower          |                                 | Definiert die untere Datumsgrenze, ab welcher Termine gesucht werden. [flagStart legt fest, ob es sich um den Wert in `start` (true) oder in `stop` (false) handelt.]
| upper          |                                 | Definiert die obere Datumsgrenze, bis zu welchem Termine gesucht werden. [flagStart legt fest, ob es sich um den Wert in `start` (true) oder in `stop` (false) handelt.]
|                | **_Unterebene `start.`_**       | _Definiert die Untere Grenze des Terminbereichs, der hier `start` heißt_
| format         |                                 | Definiert das Ausgabeformat für den Datumswert.
| source         |                                 | Name des Feldes in der Inputliste, die abweichen kann vom Index-Namen im assoziativen Array für die Ausgabe.
|                | **_Unterebene `end.`_**         | _Definiert die Untere Grenze des Terminbereichs, der hier `end` heißt_
| format         |                                 | Definiert das Ausgabeformat für den Datumswert.
| source         |                                 | Name des Feldes in der Inputliste, die abweichen kann vom Index-Namen im assoziativen Array für die Ausgabe.

#### HolidaycalendarProcessor

Der Dataprozessor dient der Auswertung von dev CSV-Dateien mit den
Feiertagsterminen.

```
        10 = Porthd\Timer\DataProcessing\HolidaycalendarProcessor
        10 {

            #!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
            #!!! every parameter will support the typoscript-functionality `stdWrap` !!!
            #!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
            #!!! The chinese-calendar is not supported yet, because the php is buggy !!!
            #!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!

            # regular if syntax
            #if.isTrue.field = record

            # This definition will override the system-definition and take this locale for the definition of locales.
            # You should normally not use this, becuse the locale should be defined in your LocalConfiguration.php.
            # The value will be seen in see GLOBALS['TYPO3_CONF_VAR']['SYS']['systemLocale'].
            # if there is no information and if this definition is missing, the locale 'en_GB.utf-8' will be used.
            locale = de_DE.utf-8
            # default is `gregorian`. Allowed are all calendars, which your intlDateFormatter in your php can handle,
            #    and the julian calendar
            calendar = gregorian
            # default is the timezone of your TYPO3-System (normally defined in the LocalConfiguration.php or
            #   see GLOBALS['TYPO3_CONF_VAR']['SYS']['phpTimeZone']
            timezone = Europe/Berlin
            start {
                # last year
                year.stdWrap.cObject = TEXT
                year.stdWrap.cObject {
                    value.stdWrap.cObject = TEXT
                    value.stdWrap.cObject {
                        data = date:Y
                        intval = 1
                        wrap = |
                    }

                    prioriCalc = 1
                }

                # inclusive monthnumber,  if missing, then it is equal to current month
                month = 1
                # inclusive daynumber,  if missing, then it is equal to current day
                day = 1
            }

            stop {
                # second next year
                year.stdWrap.cObject = TEXT
                year.stdWrap.cObject {
                    value.stdWrap.cObject = TEXT
                    value.stdWrap.cObject {
                        data = date:Y
                        intval = 1
                        wrap = |+2
                    }

                    prioriCalc = 1
                }

                # inclusive monthnumber,  if missing, then it is equal to startmonth
                month = 1
                # inclusive daynumber,  if missing, then it is equal to the startday
                day = 1
                # if `daybefore` is unequal to 0, then the date will be decremented by one day. So you can detect one year in a
                #   foreign calendar without knowing the number of days in the last month.
                # if `daybefore` is zero or if the value is missing, nothing will happen.
                daybefore = 1
            }

            # the alias-file contain a list of alias-phrases, which are merged nondestructive to the `add` part of each related holiday-definition,  under the attribute `aliasDateRel`.
            # this parameterblock is optional
            # direct path, EXT:Path or URL to the file with the alias-definition
            # the definition of `aliasConfig` will overrule the definition of `aliasPath`.
            #            aliasPath = directPath
            #            aliasConfig {
            #                flexDbField = pi_flexform
            #                pathFlexField = aliasPath
            #                falFlexField = aliasPath
            #            }

            # the holiday file can contain a list of alias-phrases, which are merged nondestructive to the `add` part of each related holiday-definition, under the attribute `aliasDateRel`
            # the holiday file has a list of holiday- or eventday-definition under the attribute 'calendarDateRel'
            # the definition of `holidayConfig` will overrule the definition of `holidayPath`.
            # the missing of both (`holidayPath` and `holidayConfig`) will cause an exception.
            # direct path, EXT:Path or URL to the file with the holiday-definition
            holidayPath = EXT:timer/Resources/Public/Csv/ExcelLikeListForHolidays.csv
            holidayConfig {
                flexDbField = pi_flexform
                pathFlexField = holidayFilePath
                falFlexField = holidayFalRelation
            }

            # name of output-variable
            as = holidayList
        }
```

##### Parameter für den HolidaycalendarProcessor

Der Dataprozessor produziert einen Liste von Feiertagen für eine bestimmtes
zeitliches Interval aus einer Liste von Feiertagen.

| Parameter      | Default                                         | Beschreibung
|----------------|-------------------------------------------------|-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
|                | **_Hauptebene_**                                |
| if             | true                                            | Wenn der Wert oder der Typoscript-Ausdruck falsch ergeben, wird der Dataprocessor nicht ausgeführt.
| aliasPath      |                                                 | Definiert explizit im Typoscript einen Pfad zu einer Datei im CSV oder YAML-Format, welchen Konfigurationen für verschiedene Aliase enthält, welche ihrerseits die Konfigurationen von Feiertagen in den Feiertragslisten ergänzen können. (Übersteuert jede `aliasConfig.`-Definition.)
| alias.         | &lt;Array mit Referenzen zu alias-Listen&gt;    | (2023/10/04 - To Do: muss noch überarbeitet werden.) Definiert die Referenz zu einen FAL-Eintrag oder zu einem Flexform-Feld, in welcher YAML- oder CSV-Daten mit den Listen der Alias-Definitionen zu finden sind.
| holidayPath    |                                                 | Definiert  explizit im Typoscript einen Pfad zu einer Datei im CSV- oder YAML-Format, welchen Konfigurationen für verschiedenen Feiertage enthält. (Übersteuert jede `holidayConfig.`-Definition.)
| holidayConfig. | &lt;Array mit Referenzen zu Feiertagslisten&gt; | (2023/10/04 - To Do: muss noch überarbeitet werden.) Erzeugt die Referenz zu einen FAL-Eintrag oder zu einem Flexform-Feld, in welcher YAML- oder CSV-Daten mit den Listen der Feiertags-Definitionen zu finden sind.
| as             | holidayList                                     | Name der Outputvariablen, die z.B. im Fluid-template genutzt wird.
| cache          | default                                         | Definiert das Cache-Verhalten. Im Defaultfall wird die Cachingzeit  basierte auf der Timerliste berechnet. Eine Zahlenangabe übersteuert die berechnete Caching-Zeit durch einen statische Wert. Die Parameter ``0``,`` ``,``no``,``none``, oder ``null`` führen dazu, dass der Dataprozessor jedesmal neu die Daten berechnet.
| calendar       | gregorian                                       | (2023/10/04: nicht wirklich getestet) Als Basiskalender wird der Gregorianische verwendet. Es kann aber auch ein alternativer Kalender verwendet werden, wenn dieser von der PHP-Extension 'IntlDateFormatter' unterstützt wird und nicht offensichtlich - aktuell die Kalender `dangi`, `chinese` - fehlerbehaftet ist. Wenn solche Kalender genutzt werden sollen, dann wird intern im gregorianischen Kalender das  Ergebnis berechnet, welches dann abschließend in den definierten Kalender zurückgerechnet wird. So ist es möglich, die Feiertage auch in nicht-gregorischnischen Kalender-Systemen darzustellen. (Es gibt jedoch meines Wissen kaum Kalender-Frameworks in JavaScript, die auch nichtgregorianische Kalendersysteme unterstützen.)
| timezone       | &lt;TYPO3 localconfiguration.php&gt;            | (2023/10/04: nicht wirklich getestet) Definiert die zugrundeliegenen Zeitzone. Per Default wird die in den TYPO3-Settings definierte Zeitzone verwendet. Diese kann aber auch überstuert werden.
| locale         | en_GB                                           | (2023/10/04: nicht wirklich getestet) Definiert das zugrundeliegenen kalender-System basierend auf der nationalen Lokalisierung.
|                | **_Unterebene `start.`_**                       | _Definiert die Untere Grenze des Terminbereichs._
| year           |                                                 | Jahreszahl. Sie ist abhängig vom Kalender (locale).
| month          |                                                 | Monatszahl. Sie ist abhängig vom Kalender (locale).
| day            |                                                 | Tag im Monat. Der Wert ist Kalenderabhängig (locale).
|                | **_Unterebene `stop.`_**                        | _Definiert die Untere Grenze des Terminbereichs._
| year           |                                                 | Jahreszahl. Sie ist abhängig vom Kalender (locale).
| month          |                                                 | Monatszahl. Sie ist abhängig vom Kalender (locale).
| day            |                                                 | Tag im Monat. Der Wert ist Kalenderabhängig (locale).
|                | **_`holidayConfig.`_**                          | _Referenz zu Daten für die Feiertags-Definitionen_
| flexDbField    |                                                 | (2023/10/04: nicht wirklich getestet) Definiert den Namen des Feldes, welches im Data-Datensatz den Flexformstring enthält.
| pathFlexField  |                                                 | (2023/10/04: nicht wirklich getestet) Definiert den Namen des Feldes innerhalb der Flexform, welches die Pfadangabe enthält.
| falFlexField   |                                                 | (2023/10/04: nicht wirklich getestet) Definiert den Namen einen FAL-Feldesin einer Flexform-Definition.
|                | **_`aliasConfig.`_**                            | _Referenz zu Daten für ergänzenden Alias-Defintion, die in Feiertags-Definitionen verwendet werden können_
| flexDbField    |                                                 | (2023/10/04: nicht wirklich getestet) Definiert den Namen des Feldes, welches im Data-Datensatz den Flexformstring enthält.
| pathFlexField  |                                                 | (2023/10/04: nicht wirklich getestet) Definiert den Namen des Feldes innerhalb der Flexform, welches die Pfadangabe enthält.
| falFlexField   |                                                 | (2023/10/04: nicht wirklich getestet) Definiert den Namen einen FAL-Feldesin einer Flexform-Definition.

