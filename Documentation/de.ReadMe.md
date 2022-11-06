#Extension Timer
## Vorbemerkung
Die Basis für diese Dokumentation ist die deutsche Variante `de.ReadMe.md`.
Die englische Variante wurde mit Hilfe von `google.translate.de` übersetzt.
Der Dokumentation ist eine Präsentatin beigefügt, die ich im Jahre 2022 für das TYPO3-Barcamp in Kamp-Lintfort vorbereitet hatte

##Motivation
TYPO3 stellt in seinen Standardtabellen die Felder `starttime` und `endtime` zur Verfügung. Über die Felder können Seiten zu bestimmten Zeiten ein- und ausgeblendet werden. Diese Felder werden auch beim Caching berücksichtigt.
Es gibt aber keine Möglichkeit, periodisch zu bestimmten Zeiten Seiten, Inhaltselemente und/oder Bilder ein- und auszublenden.

###Userstorys
####Der Interaktive Kneipengutschein.
Ein Gastwirt möchte seinen Gästen während der Happy-Hour einen Rabatt von 50% einräumen. Diesen erhalten die Kunden als QR-Code auf ihr Handy, wenn sie das über der Theke hängende Tagesmotto und ein Like-Kommentar in das interaktive Formular eingeben.
Im Content-Element wird das tagesaktuelle Motto automatisch natürlich geprüft.
####Die jährliche Firmengründungswoche
Ein Unternehmen feiert seinen Geburtstag mit einem Rückblick auf die Erfolge zum zurückliegenden Jahr. Diese werden im Alltag von den Mitarbeitern eingepflegt. Um die Freischaltung dieser Sonderseite kümmert sich das TYPO3-System.
Der Veranstaltungskalender
Eine kleiner Konzertveranstalter möchte gerne periodische Veranstaltungen wie Poetry-Slam, Lesungen oder OffeneBühnen gemischt mit besondere Konzerttermine in einer Liste anbieten.
####Reaktion-Columne
Eine Partei möchte aus seiner Startseite ähnliche Inhalte wie seine Konkurrenten deren Startseiten zeigen. Seine Webseite soll automatisch auf die Änderungen bei den Konkurrenten reagieren. Die Artikel sollen noch eine gewissen Zeit wieder verschwinden.
(Hat ja eigentlich nicht mehr mit Zeit zu tun. Aber das Problem könnte mit Timer gelöst werden…)
####Vermarktung von virtuellen Konzerten
Eine Konzertagentur im Dezember 2021 möchte ein virtuelles Konzert in Berlin veranstalten, das Europaweit zu empfangen sein soll. Da die Sommerzeit 2021 endet, rechnet der Veranstalter mit verschiedenen Zeitzonen in Europa. Die Webseite soll für die Nutzer die jeweilige Ortszeit anzeigen.

##Idee
Eine Grundidee von der Timer-Extension ist, die Aufgabe in zwei Teilaufgaben aufzuteilen: in die Aufgabe des 
Zeitmanagement und in die Aufgabe zur Steuerung der Startzeit.
Über einen Task im  Scheduler (Cronjob) kann die Seite regelmäßig aktualisiert werden. 
Über Viewhelper kann das Einbinden von Informationen innerhalb von Templates gesteuert werden, wobei der 
Entwickler sich dann auch Gedanken über das Caching des Frontends machen muss.
Über Dataprozessoren sollen leicht einfache Listen zusammenstellbar sein, die im Frontend ausgebar sind.
Die Informationen aus den Flexform-Feldern können in den Templates mit Viewhelpern aufbereite werden.

##Installation der Extension
Sie können auf einen der  klassischen Wege installieren.
- manuell
- mit dem Extensionmanager der Admin-Tools
- mit dem Composer ``composer require porthd/timer``

Prüfen sie, ob das Datenbankschema aktualisiert wurde.
Es ist das Typoscript der Extension zu inkludieren. 
Je nach Nutzungsanspruch ist noch ein Schedulertask zu aktivieren oder im eigenen Typoscript ein Dataprocessor aufzurufen.

##Anwendungsaspekte
### Periodisch erscheinender Content oder periodisch erscheinende Seiten
Für periodisch erscheinden Content oder periodisch erscheinden Seite muss ein der Consolen-Task der Extension eingerichtet werden.
Er wertet out-of-the-box die Elemente aus, für die eine Timer definiert ist und für die das Flag der Scheduler-Auswertung 
auf aktiv gesetzt wurde.

### Contentelement `timersimul` als Beispiel
Das Content-Element `timersimul` zeigt exemplarisch die Anwendung der Viewhelper und der Dataprocessoren. 
In Produktivumgebungen sollten sie für Editoren ausblenden. Es wird entfernt werden, wenn die Extension den Status `beta` erreicht. 

### Nutzung der periodischen Timer (Customtimer)
Die Extension bringt derzeit mehrere periodische Timer mit. 
Zwei der Timer sind noch nicht vollständig entwickelt. Unter anderem deshalb wurde der Status der Extension auf 
`experimental` gestellt.

#### Customtimer - Allgemeines
Sie können die Timer bei den Konstanten der Extension freigeben.

Eigene Timer müssen sich vom Interface ``Porthd\Timer\CustomTimer\TimerInterface`` ableiten und können
in ``ext_localconf.php`` wie folgt der Timer-extension beigefügt werden:
````
            \Porthd\Timer\Utilities\ConfigurationUtility::mergeCustomTimer(
                [\Vendor\YourNamespaceTimer\YourTimer::class, ]
            );
````

#### CustomTimer - Übersicht
* DailyTimer - tägliche für einige Minuten wiederkehrende aktive Zeiten  
  (täglich ab 10:00 für 120 Minuten)
* DatePeriodTimer - periodisch für einige Minuten wiederkehrende aktive Zeiten relativ zu einem Startzeitpunkt. Bedenken sie,
  dass wegen der Rückführung der der Zeit auf die UTC-Zone während der Berechnung
  die Sommerzeit bei Periodizitäten auf Stundenbasis zu unerwarteten Ergebnissen führen kann.
  (ganzen Tag in jedem Jahr zum Geburtstag, jede Woche für 120 Minuten bis 12:00 ab dem 13.5.1970, ..)
* DefaultTimer - Defaulttimer/Nullelement
* EasterRelTimer - aktiver Zeitraum relativ zu wichtigen, meist beweglichen christlichen Feiertagen    
  (2. Advent von 12:00-14:00, Rosenmontag von 20:00 bis 6:00 des Folgetages)
* MoonphaseRelTimer - Perioden startend relativ zu einer Mondphase für einen bestimmten Zeitraum
* MoonriseRelTimer - Perioden relative zum Mondaufgang oder Monduntergang für einen bestimmten Zeitraum
* PeriodListTimer (unfertig 20221105) liest daten zu aktiven Perioden aus einer Yaml-Datei ein.
  Hilfreich zum Beispiel für Ferienlisten oder TZourenpläne von Künstlern
* RangeListTimer (unfertig 20221105) liest periodische Liste aus Yaml-Dateien oder aus der Tabelle `` ein und mergt
  sie bei Überlappung zu neuen aktiven Bereichen zusammen. Man kann auch eine Liste mit unerlaubten Bereichen definieren,
  die solche Überlappungen reduzieren können. (Beispiel: jeden Dienstag von 12-14 Uhr außer in den Schulferien und an Feiertagen)
* SunriseRelTimer - Perioden relativ zum Sonnenaufgang und Sonnenuntergang
* WeekdayInMonthTimer - Perioden zu bestimmten Wochentagen innerhalb eines Monats ab bestimmten Uhrzeiten mit bestimmter Dauer
  (Beispiel: jeden zweiten Freitag im Monat in dem zwei Stunden vor 19:00 )
* WeekdaylyTimer - Ganzer tag eines bestimmter Wochentags oder bestimmter Wochentage. (Beispiel: Jeden Montag oder Donnerstag)


#### CustomTimer - Generelle Parameter bei allen Timern
Einige Parameter sind bei allen Timern gleich. Zwei Parameter behandeln den Umgang mit Zeitzonen. Zwei weitere Parameter bestimmen den Zeitraum, in welchen der Timer überhaupt gültig ist.
Auf einen Parameter zur Steuerung des Scheduler wurde verzichtet. Mir ist kein Anwendungsfall eingefallen, wo ein solcher Ausschluss wirklich sinnvoll ist.   
Wer so etwas braucht, kann gern einen entsprechenden Timer programmieren.

* timeZoneOfEvent - speichert den Namen der zu verwendenden Zeitzone. wenn die Zeitzone des Server mit der Zeitzone des Event nicht übereinstimmt, wird die Server-ezeit auf die Zeitzonenzeit des Events umgerechnet.  
  *Wertebereich*:   
  Liste der generierten Zeitzonen. Die Zeitzonenproblematik ist wichtig, weil einige Timer die Zeiten auf UTC umrechen müssen. (Sonnenlauf, ...)    
  *Anmerkung*:   
  Aktuell werden alle Zeitzonen generiert. Es besteht die Möglichkeit, die Zeitzonen auf einen generelle Auswahl zu beschränken.
* useTimeZoneOfFrontend - ja/nein-Parameter. Wenn der Wert gesetzt ist, wird immer die Zeitzone des Servers verwendet.
* ultimateBeginningTimer - Ultimativer Beginn der Timers  
  *Default*:
    1. Januar 0001 00:00:00
* ultimateEndingTimer - Ultimatives Ende des Timers  
  *Default*:
    31. Dezember 9999 23:59:59



#### Customtimer - Developer - Motivation
Die Timer decken nicht jeden Fall ab. Sie können auch eigene Timerklasse definieren, die das `TimerInterface` implementieren muss. .
Die binden sie über ihre  `ext_localconf.php` ein. Über eigenen Flexform können sie ihrem Timer eigene 
Parameter mitgeben. 

### Viewhelper
Es fgibt drei Viewhelper:
- timer:isActive - funktioniert ähnlich wie `f:if`, wobei geprüft wird, ob ein Zeitpunkt im aktiven Bereich eines periodischen Timers liegt.
- timer:flexToArray - Wenn man eine Flexform-Definition in einen Array umwandelt, dass enthält der Array viele überflüssige Zwischenebenen. Mit dem Viewhelper lassen sich diese Ebenen entfernen, so dass der resultierenArray des Flexformarrays flacher/einfacher wird. 
- timer:format.date - funktioniert wie `f:format.date`, wobei es die Ausgabe von Zeiten für eine bestimmte Zeitzone erlaubt.

### Dataprozessoren
Da die Ergebnisse der Dataprocessoren gecacht werrden, muss sich der User überlebgen, was ein sinnvoller Cachinh´g-zeitraum ist und dies entsprechend definieren. 
#### RangeListQueryProcessor
Der Prozessor erstellt für die Datensätze mit periodischen Timern aus einer Tabelle eine Liste von Terminen. Der Dataprocessor funktioniert ählich wie der `DbQueryProcessor`.
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
Siehe auch Beispiel in exemplarischen Contentelemtnt ``timersimul``

#### SortListQueryProcessor
Die Tabelle `sys_file_reference` unterstützt nicht die Felder `starttime` und `endtime`.
Um trotzdem zeitliche variierende Bilder zu erreichen, kann man die per Dataprocessor erhalten Medien in 
einen nach Periodizität sortierte Liste überführen lassen und umwandeln lassen und im Template entsprechend nutzen.
 
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
