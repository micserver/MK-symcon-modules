# solaredge

Das Modul liest die Inverter Daten über die solaredge API

### Inhaltverzeichnis

1. [Funktionsumfang](#1-funktionsumfang)
2. [Voraussetzungen](#2-voraussetzungen)
3. [Software-Installation](#3-software-installation)
4. [Einrichten der Instanzen in IP-Symcon](#4-einrichten-der-instanzen-in-ip-symcon)
5. [Statusvariablen und Profile](#5-statusvariablen-und-profile)
6. [WebFront](#6-webfront)
7. [PHP-Befehlsreferenz](#7-php-befehlsreferenz)

### 1. Funktionsumfang

* Auslesen der Daten einer solaredge PV Anlage
* 
* ...

### 2. Voraussetzungen

- IP-Symcon ab Version 5.0 (Abwärtskompatibilität nicht getestet!)

### 3. Software-Installation

Über das Modul-Control folgende URL hinzufügen.  
`git://github.com/micserver/MK-symcon-modules.git`  

### 4. Einrichten der Instanzen in IP-Symcon

- Unter "Instanz hinzufügen" ist das 'solaredge API auslesen'-Modul unter dem Hersteller '(Sonstige)' aufgeführt.  

__Konfigurationsseite__:

Name                   | Beschreibung
---------------------- | ---------------------------------
API Key | ...erhält man über den 'Admin' Reiter des solaredge Web Interface im Bereich 'Anlagenzugriff'
Standort-ID | ...erhält man über den 'Admin' Reiter des solaredge Web Interface im Bereich 'Anlagenzugriff'
Archivierung | Flag zum Aktivieren der Archovierung (je Variable)
Update Intervall | Intervall API AUfruf (Achtung, solaredge limitiert die Anzahl der Aufrufe/Tag. Siehe 'Daily Limitation' API-Handbuch 

### 5. Statusvariablen und Profile

Die Statusvariablen/Kategorien werden automatisch angelegt. Das Löschen einzelner kann zu Fehlfunktionen führen.

##### Statusvariablen

Name         | Typ       | Beschreibung
------------ | --------- | ----------------
Active       | Boolean   | De-/Aktiviert die Alarmierung. Wird die Alarmierung deaktiviert, so wird auch der ggf. vorhandene Alarm deaktiviert.
Alert        | Boolean   | De-/Aktiviert den Alarm.

##### Profile:

Es werden keine zusätzlichen Profile hinzugefügt

### 6. WebFront

Über das WebFront kann ...

### 7. PHP-Befehlsreferenz

todo

`boolean ARM_SetActive(integer $InstanzID, boolean $Value);`
Schaltet das Alarmierungsmodul mit der InstanzID $InstanzID  auf den Wert $Value (true = An; false = Aus).  
Die Funktion liefert keinerlei Rückgabewert.  
`ARM_SetActive(12345, true);`

`boolean ARM_SetAlert(integer $InstanzID, boolean $Value);`
Schaltet den Alarm mit der InstanzID $InstanzID auf den Wert $Value (true = An; false = Aus).  
Die Funktion liefert keinerlei Rückgabewert.  
`ARM_SetAlert(12345, false);`
