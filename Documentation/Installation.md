##Installation
### Code-Installation
Sie können auf einen der  klassischen Wege installieren.
- manuell
- mit dem Extensionmanager der Admin-Tools
- mit dem Composer ``composer require Porthd/timer``

Siehe https://docs.typo3.org/m/typo3/guide-installation/master/en-us/ExtensionInstallation/Index.html 

### Extension-Konfiguration im Admin Tools > Settings
#### Deaktivieren der Timer
Die Extension bringt zwei konfigurierbare Timer mit.
Diese können sie gegebenenfalls deaktivieren. 
Jeder Timer ist durch ein Bit (1, oder 2) in einem Byte definiert.  
Die Summe 3 = 1+2 aktiviert beide Timer. 
#### Deaktivieren des Testelements
Statt langer Erklärungen für die Viewhelper und Dataparozessoren 
ist ein Test-Content-Element beigefügt. das Template enthält Beispiele 
für die Nutzung der zwei Viewhelper <timer:flex> und <timer:isActive>;
