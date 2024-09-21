# Extension Timer - Version 13.x

<a name="Inhaltsverzeichnis"></a>

## Inhaltsverzeichnis

- [Motivation](#Motivation)
- [Installation](#Installation)
- [Für Redakteure](#Redakteure)
    - [Varianten für Definition von Wiederholungen von Inhaltselementen und Seiten](#Wiederholungen)
    - [Nutzen von Terminlisten](#TerminlistenUse)
    - [Testfälle / Code-Beispiele](#Beispiele)
- [Für Integratoren](#Integratoren)
    - [Viewhelper für Datum in nicht-gregorianischen Formaten](#Formaten)
    - [Viewhelper für Flexform-Daten](#FlexformDaten)
    - [Remapping von Daten](#Remapping)
- [Für Entwickler](#Entwickler)
    - [Eigene Datenmodelle](#Datenmodelle)
    - [Terminlisten](#Terminlisten)
- [Dokumentation vor Version 13](#vorVersion13)

<a name="Motivation"></a>

## Motivation

Die Idee zu dieser Extension entstand, weil eine Kneipe das
Event ``Vollmond-Party``
anbot und weil TYPO3 keine Möglchkeit zu Definition von periodischen Anzeige von
Inhalten biete.

Für die Version 13 wurde die Dokumentation gestrafft und überarbeitet.

<a name="Installation"></a>

## Installation

- Installieren sie auf einen der beiden klassischen Wege:
    - mit dem Extensionmanager bei den TYPO3 Admin-Tools
    - mit dem Composer ``composer require porthd/timer``
- Sie müssen die Planer-Aufgabe ``update`` aktivieren, wobei der Planer über
  einen Cronjob mindestens doppelt so häufig getriggert werden sollte, wie das
  kleinste Wiederholungsintervall lang ist.
- Gegebenenfalls müssten Sie für die Testfälle zusätzlich das entsprechende
  Typoscript installieren.

<a name="Redakteure"></a>

## Für Redakteure

<a name="Wiederholungen"></a>

### Varianten für Definition von Wiederholungen von Inhaltselementen und Seiten

Bei den Seiteneigenschaften oder beim Inhaltselement definieren sie im
Tab `Perioden`
beim Auswahlfeld `tx_timer_selector` das Timer-Modell das Modell der
Wiederholung.
Über das Toggle-Feld `tx_timer_scheduler` definieren sie, ob der Scheduler die
Seite oder das Content-Element überhaupt beachten soll.
Nach dem automatischen Abspeichern definieren sie in dem Tab `Perioden` im Feld
der Flexform-Parameter `tx_timer_timer` die eigentlichen Parameter für die
konkrete Wiederholung.

Die Extension hat derzeit folgende astronomische und kalendarische Arten der
Wiederholung bzw. Wiederholungslisten vordefiniert.
Die Auswahl der Parameter wird im Nachfolgenden am Beispiel vorgestellt.

#### Tab `allgemeine Einstellungen` in jedem Modell für Wiederholungen

Jedes Modell der Wiederholung enthält im Feld der Flexform-Parameter ein
Tab `Allgemeines`.
In diesem Tab wird in dem Toggle-Feld `useTimeZoneOfFrontend` und in dem
Auswahlfeld `timeZoneOfEvent`
der generelle Umgang mit Zeitzonen definiert.
In den Datumsfeldern innerhalb dieses Tab werden ein entgültiger
Startzeitpunkt `ultimateBeginningTimer`
und ein entgültiger Entzeitpunkt `ultimateEndingTimer` definiert. Diese beiden
Felder defineren, ab wann ein Inhaltselemnt
bzw. eine Seite frühestens zum ersten Mal bzw. spätestens zum letzten Mal
gezeigt werden soll.
Diese Felder ersetzen die TYPO3-Felder `starttime` und `endtime`, wenn das
oben genanne Toggle-Feld `tx_timer_scheduler` auf aktiv gesetzt ist.
Die Felder sind nötig, weil die Planer-Aufgabe `updateTimer` die
TYPO3-Felder `starttime` und `endtime` nutzt,
um Inhalte und Seiten jeweils berechneten Zeitpunkt periodisch ein- und
auszublenden.

#### Sonne und Mond

##### Wiederholungen relativ zu einer der vier Mondphasen [MoonphaseRelTimer.php](..%2FClasses%2FCustomTimer%2FMoonphaseRelTimer.php)

Beispiel: Genau 3600 Minuten (zwei Tage und zwölf Stunden) (
Berechnungsfeld `Startzeitpunkt definiert durch den Abstand in Minuten relativ zum Beginn des Tages mit der gewählten Mondphase`)
nach Beginn jeden Tages mit dem abnehmenden Halbmond (Auswahlfeld `Mond Phase`)
ist das Inhaltselement für 200 Minuten (drei Stunden und 20 Minuten) (
Berechnungsfeld `Zeitspanne zwischen -14400 und +14400 Minuten`) aktiv.

##### Wiederholungen relativ zum Mondaufgang und -untergang [MoonriseRelTimer.php](..%2FClasses%2FCustomTimer%2FMoonriseRelTimer.php)

Beispiel: 360 Minuten (6 Stunden) (
Berechnungsfeld `Minuten relativ zum gewählten Mondstand`) nach dem Monaufgang (
Auswahlfeld `Status des Mondes`) in Bremen, definiert durch Längengrad (
Zahlfeld `Längengrad`) und Breitengrad (Zahlfeld `Breitengrad`), ist das
Inhaltelement für 420 Minuten (7 Stunden) (
Berechnungsfeld `Zeitspanne zwischen -1439 und +1439 Minuten`) aktiv.

##### Wiederholungen relativ zum Sonnenstand [SunriseRelTimer.php](..%2FClasses%2FCustomTimer%2FSunriseRelTimer.php)

Beispiel: Genau 300 Minuten (5 Stunden) (
Auswahlfeld `Minuten relativ zum ausgewählten Zeitmarker`) nach dem
Sonnenaufgang (Auswahlfeld `Sonnenstand als Startzeitpunkt`) am Anus der Welt,
bestimmt durch Breitengrad (Zahlfeld `Breitengrad`) und Längengrad (
Zahlfeld `Längengrad`), wird das Inhaltselement für 420 Minuten (sieben
Stunden) (Zahlfeld `Zeitspanne zwischen -1340 und +1340 Minuten`) gezeigt. Wenn
die Zeitspanne über einen zweiten Sonnenstand (
Auswahlfeld `Aktive Zeitdauer definiert durch feste Zeiten oder durch den Abstand zu nächsten hier festgelegten Sonnenstand (natürliche Reihenfolge beachten)`)
definiert wird, wird dies bevorzugt.

#### gregorianisscher Kalender

##### Wiederholungen relativ zur Tageszeit [DailyTimer.php](..%2FClasses%2FCustomTimer%2FDailyTimer.php)

Beispiel: Jeden Wochentag außer Sonntags (Checkboxes bei `aktiver Wochentag`) ab
11:35 (Zeitfeld `Bezugszeitpunkt für aktiven Zeitspanne`) für zwei Stunden (
Berechnungsfeld `Zeitspanne zwischen -1439 und +1439 Minuten`).

##### _Wiederholungen relativ zu Fixtagen in nicht-gregorianischen

Kalendern [CalendarDateRelTimer.php](..%2FClasses%2FCustomTimer%2FCalendarDateRelTimer.php)_

_In Planung. Sponsoren gesucht._

##### Wiederholungen relativ zu bestimmten Tagen im Gregorianischen Kalender [DatePeriodTimer.php](..%2FClasses%2FCustomTimer%2FDatePeriodTimer.php)

Beispiel: Ab dem 23.1.2015 12:35 (Zeitfeld `Beginn der Periode`) für 120
Minuten (
Berechnungsfeld `Aktive Zeitspanne relativ zum Periodenstart`) alle
5 (Zahlfeld `Periodenlänge`) Tage (Select-Feld `Zeitraum Einheit`).

**Anmerkung:** Bei der Zeitraum-Einheit `Monat` wird einfach der Monat im Datum
jeweils um eins erhöht. Wenn der Tag im Datum größer als 28 ist, kann dies zu
Irritationen führen.
Der Monat nach dem 30.1. ist entsprechend der 30.2.. Das Datum 30.2 gibt es im
Kalender nicht. Es wird von PHP auf ein korrektes Datum umgerechnet. Je nach
Schaltjahr oder nicht wird aus dem Datum 30.2 entweder das Datum 1.3.(
Schaltjahr) oder 2.3.(kein Schaltjahr).

##### Wiederholungen relativ zum Osterfeiertag [EasterRelTimer.php](..%2FClasses%2FCustomTimer%2FEasterRelTimer.php)

Beispiel: Vom Beginn des Ostersonntags (
Auswahlfeld `Auswahl benannter Feiertage wie Ostern oder Weihnachten`) genau 200
Stunden (
Berechnungsfeld `Minuten relativ zum ausgewählten benannten Datum`) entfernt ist
gemäß des Gregorianischen Kalenders (
Auswahlfeld `Verwendung des Kalenders für Ostern; siehe (*)`) für die Zeit von
1440 Minuten (= 1 Tag) (
Berechnungsfeld `Zeitspanne zwischen -444444 und +444444 Minuten`) ein
Inhaltselement aktiv.

**Anmerkung:** Bei den Feiertagen werden als Ostern-basierte Feiertage der
Ostersonntag, der Pfingstsonntag, Rosenmontag, Karfreitag und Christi
Himmelfahrt unterstützt.
In der Auswahlliste finden sich weiter die Kalender-fixierten Feiertage erster
Advent, erster Weihnachtstag, Silvester, Neujahr, Tage der Arbeit,
Welthandtuchtag und der Welttag der Dummheit.

##### Wiederholungen relativ zu Wochentagen im Monat  [WeekdayInMonthTimer.php](..%2FClasses%2FCustomTimer%2FWeekdayInMonthTimer.php)

Beispiel: An jeden drittletzten (
Schalterfeld `Position des Wochentages innerhalb des Monats` plus
Schalter `Schalte ON, wenn der(die) aktive(n) Wochentag(e) vom Monatsende her gezählt werden soll(en)`)
Freitag (Schalterfeld `Aktiver Wochentag`) in den Monaten Februar, Mai, August
und November (Schalterfeld `Aktiver Monat`) wird für 120 Minuten (-2 Stunden) (
Zahlfeld `Zeitspanne zwischen -1439 und +1439 Minuten`) bis zum Zeitpunkt 12:
00 (Zeitfeld `Startzeitpunkt`) das Inhaltselement angezeigt.

##### Wiederholungen relativ zu Wochentagen [WeekdaylyTimer.php](..%2FClasses%2FCustomTimer%2FWeekdaylyTimer.php)

Beispiel: An jedem Freitag (Schalterfeld `Wähle aktive Wochentag(e)`) wird das
Inhaltselement aktiviert.

#### nicht-gregorianisscher Kalender

##### Wiederholungen relativ zu jüdischen Feiertagen [JewishHolidayTimer.php](..%2FClasses%2FCustomTimer%2FJewishHolidayTimer.php)

Beispiel: Vom Beginn des Yom Kippur-Festes (
Auswahlfeld:`Auswahl benannter Feiertage wie Yom Kippur`), genau 14400 Minuten (
10 Tage) (Berechnungsfeld `Minuten relativ zum ausgewählten benannten Datum`)
entfernt, ist für die Zeit von 1440 Minuten (= 1 Tag) (
Berechnungsfeld `Aktive Zeitspanne relativ zum Periodenstart in Minuten`) ein
Inhaltselement aktiv.

##### Wiederholungen relativ zu Feiertagen [HolidayTimer.php](..%2FClasses%2FCustomTimer%2FHolidayTimer.php)

Beispiel: Sie möchten ein Inhaltselement ab 6:00 morgens (
Berechnungsfeld: `Startzeitpunkt definiert durch den Abstand zwischen -275.600 und +275.600 Minuten relativ zum Beginn der nächten Ferien`)
für 8 Stunden (
Berechnungsfeld `Zeitspanne zwischen -250.000 und +250.000 Minuten`) für alle
Ferien aus einer hinterlegten Datei (
Textfeld `Lokaler Pfad oder Link zur Datei im CSV-Format mit der Liste der Feiertage im CSV- oder yaml-Format`)
und/oder aus einer hochgeladenen Datei (
Buttonfeld `Hochgeladene Dateien mit der Listen der Feiertage im CSV- oder yaml-Format`)
anzeigen lassen.

**Anmerkung:** Die Ferien können im yaml-Format folgendes Aussehen haben, wobei
zusätzliche Felder unterhalb von `data` möglich sind.

````yaml
periodlist:
    -   title: 'Winterferien Bremen'
        data:
            description: '- free to fill and free to add new attributes -'
        start: '2022-01-31 00:00:00'
        stop: '2022-02-01 23:59:59'
        zone: 'Europe/Berlin'
#...
````

Bei CSV-Dateien ist in der Kopfzeile für `data`-Felder eine Punkt-Notation zu
verwenden.

````csv
title,data.description,start,stop,zone
"Winterferien Bremen","- free to fill and free to add new attributes -","2022-01-31 00:00:00","2022-02-01 23:59:59","Europe/Berlin"
````

#### Listen

##### Wiederholengen relativ zu Zeitbereichen aus einer Liste [PeriodListTimer.php](..%2FClasses%2FCustomTimer%2FPeriodListTimer.php)

Beispiel: In einer Datei (
Textfeld: `Lokaler Pfad oder Link zur Datei mit der Liste`) oder einer
hochgeladenen Datei (Buttonfeld: `Hochgeladene Dateien mit der Liste`) im YAML-
oder
CSV-Format wird die Liste der aktiven Zeitbereiche definiert.

##### Wiederholungen berechnet aus Aktive- und Verbotslisten [RangeListTimer.php](..%2FClasses%2FCustomTimer%2FRangeListTimer.php)

Beispiel: Jeden Freitag (
Textfeld `Dateipfad der Yaml-Definitionen mit Liste der aktiven Perioden` plus
Multiauswahlfeld `Liste der Zeilen in der Datenbank mit Definitionen für aktive Zeiträume`)
außer in den Ferien und an Feiertagen (
Textfeld `Dateipfad der Yaml-Definitionen mit Liste verbotener Zeiträume` plus
Multiauswahlfeld `Liste der Zeilen in der Datenbank mit Definitionen für verbotene Zeiträume`).
Die Zahl der Rekursionen (
Zahlfeld `Maximale Anzahl von Recursionen bei Bestimmung der Überschneidungen`)
ist beschränkt, um denkbare unendliche Berechnungsloops durch das Landen von
Dateien mit RangeList-Timern zu beschränken.

**Anmerkung:**  Die resultierenden aktiven Bereiche werden verkleinert und
gegebenenfalls unterteilt durch die Liste der verbotenen Bereiche.
Zerstückelungen von aktiven Bereichen sind möglich.

<a name="TerminlistenUse"></a>

### Nutzen von Terminlisten

<a name="Beispiele"></a>

### Testfälle / Code-Beispiele

Die Testfälle erlauben einen ersten Test, ob die Extension wie gewünscht
funktioniert.
Die Testfälle sind als Content-Elemente definiert. Für die Inhaltselemente muss
jeweils dessen Typoscript installiert sein.

#### Inhaltselement `timersimul`

Dieses Inhaltselement erlaubt den Test des Verhaltens verschiedener Viewhelper,
die das System mitbringt.
Für Integratoren kann es hilreich sein, wenn sie Code-Beispiele für die
Viewhelper suchen.

#### Inhaltselement `timerholidaycalendar`

Dieses Testelement zeigt im Frontend einen Kalender an, der verschiedenen
Feiertage
verschiedener Nationen im Kalender markiert.
Die Liste der Feiertage ist in einer CSV-Datei markiert und ist unvollständig.

<a name="Integratoren"></a>

## Für Integratoren

<a name="Formaten"></a>

### Viewhelper für Datum in nicht-gregorianischen Formaten

Der Viewhelper `timer:format.calendarDate` erlaubt die Ausgabe von Datumswerten
in anderen Kalendern als dem gregorianischen Kalender.

````
<!-- fluid -->
<div>
    <timer:format.calendarDate calendartarget="persian" locale="de_DE" format="d.m.Y  H:i:s" flagformat="0">
        1600000000
    </timer:format.calendarDate>
</div>
<!-- Output HTML -->
<div>
    23.06.1399 14:26:40
</div>
<!-- gregorian date: 13.09.2020 14:26:40 -->
````

- **flagformat** bestimmt, welche Formtierungsregeln benutzt werden sollen:
  0 = [PHP-DateTime](https://www.php.net/manual/en/datetime.format.php),
  1: [ICU-Datetime-Formatierung](https://unicode-org.github.io/icu/userguide/format_parse/datetime/)
  oder 2 = [PHP-strftime](https://www.php.net/manual/en/function.strftime.php).
- **format** definiert die Form der Ausgabe des Datums und der Zeit. Unterstützt
  werden
    - DateTime-Notation (PHP) https://www.php.net/manual/en/datetime.format.php
    - strftime-Notation (
      PHP) https://www.php.net/manual/en/function.strftime.php
    - ICU-Regeln (International Components for
      Unicode) https://unicode-org.github.io/icu/userguide/format_parse/datetime/
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
  in  `datestring`. PHP erlaubt folgende Werte:
    - 0:buddhist
    - 1:chinese
    - 2:coptic
    - 3:dangi
    - 4:default
    - 5:ethiopic
    - 6:ethiopic-amete-alem
    - 8:gregorian
    - 9:hebrew
    - 10:indian
    - 11:islamic
    - 12:islamic-civil
    - 13:islamic-rgsa
    - 14:islamic-tbla
    - 15:islamic-umalqura
    - 16:japanese
    - 17:persian
    - 18:roc (?)
    - 19:julian für den Julianischen Kalender
- **calendartarget** definiert den Kalender, für welchen das das Datum
  ausgegeben werden soll. PHP erlaubt die im vorherigen Feld definierten Werte.
- **locale** bestimme die regionale Lokalisierung und setzt sich aus dem
  zweibuchstabigen Sprachkürzel (de, en, fr, es, ...) und getrennt durch einen
  Unterstrich aus dem Kürzel für die Nation (DE, GB, US, AT, CH, FR, ...). Der
  Wert in __locale__ könnte zum Beispiel folgendes Aussehen
  haben: `de_DE`, `en_GB` oder auch `es_US`.

<a name="FlexformDaten"></a>

### Viewhelper für Flexform-Daten

Der Viewhelper hilft direkt den String aus Flexform-Feldern nutzen zu können,
ohne im Fluid den Flexform-Überbau beachten zu müssen.

````
<!-- Fluid -->
       <timer:flex flexstring="{data.tx_timer_timer}"
                   as="timerflex"
                   flattenkeys="data,general,timer,sDEF,lDEF,vDEF"
       >
           <f:for each="{timerflex}" as="value" key="key">
               <tr>
                   <td>{key}</td>
                   <td>{value}</td>
               </tr>
           </f:for>
       </timer:flex>

````

<a name="Remapping"></a>

### Remapping von Array-Daten

Für das Umstzrukturieren von Array-Daten eignet sich der
Dataprozessor ``PhpMappingProcessor``.
Sein Grundprinzip ist einfach.
Der Ausgangsarray werden mit `inputOrigin` an den Dataprocessor übergeben. Ohne
Angabe wird der akutelle Prozessorarray verwendet.
Man bildet im TypoScript
unterhalb des Attributs `output` die Zielstruktur
des zu erzeugenden Zielarrays nach. Den Endpunkten im Zielarray ordnet man dann

- Konstanten
- Referenzen zu Daten im Input-Array oder
- Funktionen zu.

Die Parameter der Funktionen werden rekursiv aufgelöst.
Die Zielarray oder den JSON-String (`outputformat`) steht im Template als
Variable mit dem bei `as` festgelegten Namen zur Verfügung.

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

            inputOrigin = holidayList
            inputType = rows

            output {

                category = allday
                start = Porthd\Timer\UserFunc\MyDateTime->formatDateTime(@dateStart@,'Y-m-d','Europe/Berlin')
                end = Porthd\Timer\UserFunc\MyDateTime->formatDateTime(@dateEnd@,'Y-m-d','Europe/Berlin')
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
|--------------|------------------|---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
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
| */dynfunc    | ->               | Neben normalen PHP-Funktionen kann man auch benutzer-definierte Funktionen aus Klassen verwenden. Wenn eine Instanzierung der Funktion nötig ist, dann ist der Namespace der Klasse mit diesen Trenner '->' vom Funktionsnamen abzugrenzen. Das Mapping instanziert zuvor die Klasse neu, bevor die Methode aufgerufen wird. Es wird davon abgeraten, diesen Limiter zu ändern.
| */statfunc   | ::               | Neben normalen PHP-Funktionen kann man auch benutzer-definierte Funktionen aus Klassen verwenden. Wenn eine statische Funktion genutzt wird, dann ist der Namespace der Klasse mit diesen Trenner '::' vom Funktinsnamen abzugrenzen. Es wird davon abgeraten, diesen Limiter zu ändern. Es wird empfohlen, im Mapping gedachtnislose statische Funktionen zu verwenden.

<a name="Entwickler"></a>

## Für Entwickler

<a name="Datenmodelle"></a>

### Eigene Datenmodelle

Der Entwickler kann eigenen Datenmodelle entwickeln, um zum Beispiel bestimmte
Event anzukündigen.
Für die Verwendung der Extension Timer muss nur sichergestellt werden, dass im
Datenmodell auch die folgenden Felder mit den TCA-Definitionen aus der Extension
vorliegen:

- tx_timer_scheduler
- tx_timer_timer
- tx_timer_selector

<a name="vorVersion13"></a>

## Dokumentation vor Version 13

Die aktuelle Dokumentation wurde gestrafft. Neben dem Code entält die
Dokumentation noch Hinweise, die sie hier nicht finden.

- [Deutsch ](de.Prior13_ReadMe.md)
- [Englisch ](Prior13_ReadMe.md)
