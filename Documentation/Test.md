#Die Extension Timer
##Motivation
TYPO3 stellt in seinen Standardtabellen die Felder Starttime und Endtime zur Verfügung. Über die Felder können Seiten zu bestimmten Zeiten ein- und ausgeblendet werden. Diese Felder werden auch beim Caching berücksichtigt.
Es gibt aber keine Möglichkeit, periodisch zu bestimmten Zeiten Seiten, Inhaltselemente und/oder Bilder ein- und auszublenden.

###Userstorys
Der Interaktive Kneipengutschein.
Ein Gastwirt möchte seinen Gästen während der Happy-Hour einen Rabatt von 50% einräumen. Diesen erhalten die Kunden als QR-Code auf ihr Handy, wenn sie das über der Theke hängende Tagesmotto und ein Like-Kommentar in das interaktive Formular eingeben.
Im Content-Element wird das tagesaktuelle Motto automatisch natürlich geprüft.
Die jährliche Firmengründungswoche
Ein Unternehmen feiert seinen Geburtstag mit einem Rückblick auf die Erfolge zum zurückliegenden Jahr. Diese werden im Alltag von den Mitarbeitern eingepflegt. Um die Freischaltung dieser Sonderseite kümmert sich das TYPO3-System.
Der Veranstaltungskalender
Eine kleiner Konzertveranstalter möchte gerne periodische Veranstaltungen wie Poetry-Slam, Lesungen oder OffeneBühnen gemischt mit besondere Konzerttermine in einer Liste anbieten.
Die Reaktion-Columne
Eine Partei möchte aus seiner Startseite ähnliche Inhalte wie seine Konkurrenten deren Startseiten zeigen. Seine Webseite soll automatisch auf die Änderungen bei den Konkurrenten reagieren. Die Artikel sollen noch eine gewissen Zeit wieder verschwinden.
(Hat ja eigentlich nicht mehr mit Zeit zu tun. Aber das Problem könnte mit Timer gelöst werden…)
Vermarktung von virtuellen Konzerten
Eine Konzertagentur im Dezember 2021 möchte ein virtuelles Konzert in Berlin veranstalten, das Europaweit zu empfangen sein soll. Da die Sommerzeit 2021 endet, rechnet der Veranstalter mit verschiedenen Zeitzonen in Europa. Die Webseite soll für die Nutzer die jeweilige Ortszeit anzeigen.

##Idee
Eine Grundidee von der Timer-Extension ist, die Aufgabe zwei Aufgaben aufzuteilen: in ein Zeitmanagement und in eine Bestimmung der Startzeit.
Über einen Task im  Scheduler (Cronjob) kann die Seite regelmäßig aktualisiert werden. Über Viewhelper kann das Einbinden von Informationen innerhalb von Templates gesteuert werden. Über Dataprozessoren sollen leicht einfache Listen Zusammenstellbar sein. Über einen DataProzessor bzw. für einen Viewhelper können die Informationen aus den Flexform-feldern in den Templates verfügbar gemacht werden.

##Installation
Installieren sie wie gewohnt die Extension. Es wird die Installation über den Composer empfohlen.
Aktualisieren sie ggfls. das Datenbankschema.
Aktivieren sie ggfls. das TypoScript. (es wird empfohlen, das TypoScript über die Skeleton-Extension zu importieren.)
Installieren sie einen Cron-Job, der den Scheduler aufruft.
Wenn sie die Timerfunktionen dail datePeriod nicht benutzen wollen, können sie diese über die Extensionkonfiguration deaktivieren.
Aktivieren sie im Scheduler den TimerTask, wobei sie angeben müssen, in welchen Tabellen die Timer aktiviert werden müssen.

### Nutzung
Die Extension bringt derzeit zwei einfache periodische Timer mit. Man muss also einmal die Perioden-Zeit beachten und innerhalb der Perioden den Zeitraum der Aktivität.
Der Daily-Timer wiederholt sich täglich. Wenn die die Laufzeit negativ ist, so dent die Aktive Zeit zum gegebenen Zeitpunkt. Ist die Laufzeit positiv, beginnt die Aktive Zeit zum angegebenen Zeitpunkt.  Dabei wird der Timer nur aktiv, wenn der angegebene Zeitpunkt den aktivierten Wochentag zugeordnet werden kann.
Das DatePeriod-Timer kennt verschiedenen Wiederholungsrhythmen. Sie können Jahre angeben, Monate, Wochen oder Tage, wobei eine Woche sieben Tagen entspricht. Dier aktive Zeit beginnt zum angegeben Zeitpunkt und die Laufzeit ist in Minuten anzugeben. (1T ag = 1440 Minuten, 1 Woche = 10080 min, 30 Tage = 43200min, …)

## Developer
### Motivation
Die Timer decken nicht jeden Fall ab. PHP erlaubt die Berechnung von Sonnaufgang und Untergang. Es gibt Scripte zur Berechnung der Mondphasen. Es gibt Skripte zur Berechnung der beweglichen Feiertage wie Ostern, chinesische Frühlingsfest. Selbst menschen-basierte Termine wie der Beginn und das Ende von Ramadan kann man erfassen, wenn man weiß, welche Interquellen zu beobachten sind.
Beachten sie, dass jeder Teimer einen eigen eindeutigen Namen  haben muss, damit er andere Timer nicht stört. Über den Namen des Timer wird unter anderem im Backend die Auswahl der zum Timer gehörenden Felxform-Datei  für die weiteren Parameter gesteuert.
### Copy und paste für eigene Timer
Wenn sie besondere Timer benötigen, so definieren sie einfach ein Klasse, die sich von dem Interface ableitet. Und binden diese ein. Die meisten Funktionen im Interface dienen nur dazu, die Bedienung im Backend zu erleichtern. Nutzen sie die beiden bestehenden Funktionen als Vorlage.
Über die Flexforms können sie alle Felder definieren, die sie für ihren Timer benötigen.
Wichtig sind die beiden Funktionen isActive(Teit, Parameter) nextActive(Zeit, Parameter)
Die Methode isActive wird im viewhelper genutzt, um zu bestimmten, ob eine Bereich aktiv zu setzen ist.
Die Methode nextActive wird im Scheduler genutzt, um das nächste Starttime-Endtime-Intervall zu bestimmen.
Empfohlen wird, auch die Flexform-Parametr zu validieren.

### Anpassungen von Templates

Für die Viewhelper wird der Namespace 
`<timer: ...` statt `<f:..` genutzt.
Viehehler timer:isActive
Es gibt drei Pflichtparameter der Name des Timer,die Parameter des Timers als Flexformstring und die Zeitzone des Frontend. Eine Zeitangabe ist optional, wenn die Zeit nicht mit der aktuellen Systemzeit übereinstimmt.
Schon beim Schreiben dieser Dokumentation drehte sich mir der Kopf, weil die Begriffe nicht ordentlich passen wollten.
ViewHelper timer:flexToArray
Der Viewhelper arbeitet ähnlich wie der f:alias-Viewhelper, weil er für den eingeschlossenen Bereich eine neue Array-Variable zur Verfügung stellt. Er braucht zwei Variablen. Einmal den flexform-String aus dem Flexfels tx_timer_timer  und einen Namen, wie die Variable im eingeschlossenen Bereich des Fluid-Templates heißen soll. Eigentlich ist es schade, dass der Alias-Viewhelper nicht auch Flexformen mappen kann.

Dataprozessoren
DataProzessoren wird man nur brauchen, um
