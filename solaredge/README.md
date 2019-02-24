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
  * Stand 02/2019 => Abfrage des'Site Power Flow'


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

Die Variablen werden automatisch angelegt. Das Löschen einzelner kann zu Fehlfunktionen führen.

##### Statusvariablen

Name         | Typ       | Beschreibung
------------ | --------- | ----------------
Archive      | Boolean   | De-/Aktiviert der Archivierung


##### Profile:

Es werden keine zusätzlichen Profile hinzugefügt

