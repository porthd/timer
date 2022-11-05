##CustomTimer
### Allgemeines
Sie können die Timer bei den Konstanten der Extension freigeben. 

Eigene Timer müssen sich vom Interface ``Porthd\Timer\CustomTimer\TimerInterface`` ableiten und können 
in ``ext_localconf.php`` wie folgt der Timer-extension beigefügt werden:
````
            \Porthd\Timer\Utilities\ConfigurationUtility::mergeCustomTimer(
                [\Vendor\YourNamespaceTimer\YourTimer::class, ]
            );
````

### CustomTimer der Timer-Extension
#### Übersicht
* DailyTimer - tägliche für einige Minuten wiederkehrende aktive Zeiten  
  (täglich ab 10:00 für 120 Minuten)
* DatePeriodTimer - periodisch für einige Minuten wiederkehrende aktive Zeiten relativ zu einem Startzeitpunkt  
  (ganzen Tag in jedem Jahr zum Geburtstag, jede Woche für 120 Minuten bis 12:00 ab dem 13.5.1970, ..)
* DefaultTimer - Defaulttimer/Nullelement  
  (immer)
* EasterRelTimer - aktiver Zeitraum relativ zu wichtigen, meist beweglichen christlichen Feiertagen    
  (2. Advent von 12:00-14:00, Rosenmontag von 20:00 bis 6:00 des Folgetages)
* MoonphaseRelTimer
* MoonriseRelTimer
* PeriodListTimer
* RangeListTimer
* SunriseRelTimer
* WeekdayInMonthTimer
* WeekdaylyTimer
#### Generelle Parameter bei allen Timern
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
  

#### Parameter von DailyTimer
Die Funktion wird für jeden Tage berechent. Dabei muss die Startzeit im besagten Tageszeitraum liegen. 

* startTimeSeconds - Startzeitpunkt für das tägliche Ereignis 
  *Uhrzeitbereich*:  
  [00:00, 00:01, ..., 23:59] 
* durationMinutes - Länge des aktiven Zeitraum in Minuten   
  *Ganzzahlen-Bereich*:  
  [-1439,-1438, ... ,1439] ohne die ``0``
* activeWeekday - Auswahl liste für aktive Wochentage   
  *Wertebereich*:  
  [1 (=Montag), 2 (=Dienstag), 4 (=Mittwoch), 8 (=Donnerstag), 16 (=Freitag), 32 (=Samstag), 64 (=Sonntag)]   
  *Hinweis*:   
  Die Addition der (Zweierpotenz-)Werte erlaubt die Erfassung mehrerer Wochentage in einer Zahl. ``Dienstag (2) & Donnerstag (8)`` berechnet sich eineindeutig als ``2+8 = 10``. Das Wochenende ist entsprechend ``96``.
  
Beispiel
- Dienstag und Donnerstag von 10:00-14:30
- Montag bis Freitag von 09:00-18:00
- Samstag von 9:00 bis 3:00 am Folgetag

#### Parameter von DatePeriodTimer
In diesem Timer werden aktiven Intervalle berechnen nach folgenden Schema.   
... [start + x*Periode, start+ x*Periode + Aktivzeit], [start + (x+1)*Periode, start+ (x+1)*Periode + Aktivzeit], ...   
Das x wird dabei so brerechnet, dass man bestimmen kann, ob ein bestimmtes Datum in einem Aciven Intervall liegt oder was das nächste/vorherige aktive Zeitintervall war.

* startTimeSeconds (=start ) meint einen Startzeitpunkt bestehend aus Datum und Uhrzeit (ohne Sekunden). 
  Das Format ist die Zifferndarstellung von Jahr-Monat-Tag Stunde:Minute (Y-m-d H:i:s)  
  Alle Daten zwischen dem Jahr 0001 und 9999 sind erlaubt. 
* durationMinutes - Aktive Zeit ab dem Startzeitpunkt in Minuten. Negative Zahlen meinen den Zeitraum vor dem Startzeitpunkt bzw. besser Start-Berechnungs-Zeitpunkt.  
  *Wertebereich*: jede ganze Zahl ohne die null
* periodLength - Zahlenwert zur Länge der Periode.  
  Jede Zahl größer oder gleich null.   
  *Anmerkung*:  
  Eine Periode mit der Länge ``0`` ist eine unmögliche Periode - also ein ***einmaliges*** Ereignis!  
* periodUnit - dies ist die Maßeinheit der Periode. Minuten, Stunden, Tage, Wochen, Monate, Jahre.   
  *erlaubte Werte*:  
  [TM (=Minuten), TH (=Stunden), DD (=Tage), DW (=Wochen), DM (=Monate), DY (=Jahre)   
  *Anmerkung*:  
  Minuten, Stunden, Tage und Wochen lassen sich leicht ineinander umrechnen. Gleiches gilt für Jahren und Monate. 
  Insbesondere das Rechnen mit Monaten  kann manchmal zu unerwartenden Ergebnissen führen.
  Wenn sie zum Beispiel den 31.1. mit einem Monat addieren, dann erhalten sie je nach Jahr entweder den 2.3. oder den 3.3.
  Das Rechen mit Monaten ist unkritisch, solange das Tagesdfatum kleiner 29 ist.

Beispiel   
* alle sieben Tage von 14:00-16:00 ab dienstag dem 29.12.2020
* alle drei Monate zwischen 14:00 und 18:00 ab dem 15.1.2000 

Achtung:  
Für einen Korrekte berechnung der Intervalle muss die Länge der Periode immer größer als die aktive Zeit sein. 
Aktuell wird KEIN Fehler anzeigt, wenn durationTime größer periodLength+PeriodUnit kleiner oder gleich durationTime ist.

#### Parameter von EasterRelTimer
* namedDateMidnight -DerPflichtwert bezeichnet den Zeitpunkt 00:00 für ausgewählte christliche Feiertage.    
  *erlaubte Werte*:  
  [0 (=Ostersonntag), 1 (=Himmerfahrt), 2 (=Pfingstsonntag), 3 (=Erster Advent), 4 (=Erster Weihnachtstag), 5 (=Rosenmontag), 6 (=Karfreitag)]
* relMinToSelectedTimerEvent - Bestimmt den Startberechnungszeitpunkt in Minuten relative zu 00:00 des ausgewählten christlichen Feiertag   
  *Wertebereich*:  
  [-475200 ... 475200] inklusive 0  
  Beachte: (475200 min / (1440 min/Tag))  = 330 Tage
  *Beispiel*:  
  * 1440 (realtiv zu Ostersonntag) = Ostermontag     
  * 12*1440 (relativ zu Weihnachten) = Heiligen Drei Könige (6. Jan.)  
* calendarUse - Parameter für die Funktion im PHP. Wichtig für die korrekte Berechnung von früheren Ostertagen verschiedener christilicher Strömungen. Siehe https://www.php.net/manual/en/function.easter-date.php     
  *Wertebereich*: 
  [0,1,2,3]       
  *Default*: 0 (passt für die gesetzlichen christlichen Feiertage in Deutschland)
* durationMinutes - Aktive Zeit ab dem Startzeitpunkt in Minuten. Negative Zahlen meinen den Zeitraum vor dem Startzeitpunkt bzw. besser Start-Berechnungs-Zeitpunkt.  
  *Wertebereich*:   
  [-475200 ... 475200] Ohne '0'
  

### Extension-Konfiguration im Admin Tools > Settings
#### Deaktivieren der Timer
Die Extension bringt zwei konfigurierbare Timer mit.
Diese können sie gegebenenfalls deaktivieren. 
Jeder Timer ist durch ein Bit (1, oder 2) in einem Byte definiert.  
Die Summe 3 = 1+2 aktiviert beide Timer. 
#### Deaktivieren des Testelements
Statt langer Erklärungen ifür die Viewhelper und Dataparozessoren 
ist ein Test-Content-Element beigefügt. das Template enthält Beispiele 
für die Nutzung der drei Viewhelper <timer:flex>, <timer:rangeList> und <timer:isActive>;
