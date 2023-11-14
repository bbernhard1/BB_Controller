# PID_Controller
Beschreibung des Moduls.

### Inhaltsverzeichnis

1. [Funktionsumfang](#1-funktionsumfang)
2. [Voraussetzungen](#2-voraussetzungen)
3. [Software-Installation](#3-software-installation)
4. [Einrichten der Instanzen in IP-Symcon](#4-einrichten-der-instanzen-in-ip-symcon)
5. [Statusvariablen und Profile](#5-statusvariablen-und-profile)
6. [WebFront](#6-webfront)
7. [PHP-Befehlsreferenz](#7-php-befehlsreferenz)

### 1. Funktionsumfang

* Das Modul stellt einen universellen P-I-D Regler bereit. Das Augenmerk liegt auf möglichst universelle und breite Einsetz- und Konfigurierbarkeit.

Eine sehr gute Beschreibung des verhaltens und der Komponenten eines P-I-D Reglers ist auf dieser Webseite nachlesbar:
https://de-academic.com/dic.nsf/dewiki/641023#I-Regler_.28I-Anteil.29
 

### 2. Voraussetzungen

- IP-Symcon ab Version 5.5

### 3. Software-Installation

* Über den Module Store das 'PID_Controller'- Modul installieren.
* Alternativ über das Module Control folgende URL hinzufügen:
https://github.com/bbernhard1/BB_Controller/tree/master/PID_Controller


### 4. Einrichten der Instanzen in IP-Symcon

 Unter 'Instanz hinzufügen' kann das 'PID_Controller'-Modul mithilfe des Schnellfilters gefunden werden.  
	- Weitere Informationen zum Hinzufügen von Instanzen in der [Dokumentation der Instanzen](https://www.symcon.de/service/dokumentation/konzepte/instanzen/#Instanz_hinzufügen)

__Konfigurationsseite__:

___Bereich Eingangs / Ausgangsvariablen___
Name     | Beschreibung
-------- | ------------------
 Sollwert        | Optional: Variable welche den Sollwert enthält, wenn nichts ausgewählt ist, so wird die TargetVariable des Moduls benutzt
 Istwert         | Optional: Variable welche den Istwert enthält , wenn nichts ausgewählt ist, so wird die TargetVariable des Moduls benutzt(aktuellen Messwert) enthält, alternativ kann der Istwert auch per Script übergeben werden
 Stellwert       | Optional: Variable welche den Stellwert des enthält. Dieser wird immer in den Bereich 0-100% skaliert"
 Stellwert Script| Optional: Script welches bei jdem Update des Stellwertes aufgerufen wird. Der Stellwert wird nach $_IPS['Value'] übergeben. 
 Mittelwert      | Mittelwertbildung aus den jeweils aktuellsten x Werten des Istwertes (wenn '1' dann erfolgt keine Mittelwertbildung, es wird der jeweils aktuellste Istwert verwendet).

___Bereich PID Reglereinstellung___
Name     | Beschreibung
-------- | ------------------
 Proportional    | Anteil des Proportionalwertertes an der Stellgröße (0..1)
 Integral        | Anteil des Integralwertes an der Stellgröße (0..1)
 Differential    | Anteil des Differentialwertes an der Stellgröße (0..1)    
 Fix/Proportional| 'AUS': Bewertung des Integralanteils mit fixen Zeitintervalle 'EIN': Bewertung des Integralanteils proportional zur Nachregelzeit
 Zeitkonstante   | lt. Fix/Proportional entweder Integrationszeit oder Nachregelzeit

Name     | Beschreibung
-------- | ------------------
Berechungsinterval | Intervall zur Neuberechnung des Stellwertes. Wenn '0' so löst jedes Aktualisierung des Eingangswertes eine Neuberechnung aus
Mindeständerung    | Die Stellwertvariable wird nur dann aktualisiert wenn sich der berechnete Wert mindestens um diese Schwelle ändert
Skalierung         | Zur Anpassung des Stellwertes an den Austeurbereich des Aktors


### 5. Statusvariablen und Profile

Die Statusvariablen/Kategorien werden automatisch angelegt. Das Löschen einzelner kann zu Fehlfunktionen führen.


#### Statusvariablen

Name   | Typ     | Beschreibung
------ | ------- | ------------
Module Active      |  Boolean       |   Ein/Ausschalten der Stellwertberechnung 
Module Reset       |  Boolean       |   Rücksetzten aller persistenten Berechnungswerte (für Optimierungs und Debugzwecke)
Recalculate Output |  Boolean       |   Manuelles auslösen der Stellwertberechnung (für Optimierungs und Debugzwecke) 
TargetValue        |  Float         |   Manuelles überschreiben bzw. Anzeige des Sollwerets (für Optimierungs und Debugzwecke) 
Actual Value       |  Float         |   Manuelles überschrieben bzw. Anzeige des Istwertes (für Optimierungs und Debugzwecke)  
Output Value (0..100)| Float        |   Stellwert

#### Profile
Es werden keine Variablenprofile registriert

### 6. WebFront

Das Modul beitet keine speziellen Funktionen fürs Webfront.  

### 7. PHP-Befehlsreferenz

PID_SetActive(integer $InstanzID, boolean Value); Ein/Ausschalten der Stellwertberechnung
PID_SetActualValue(integer InstanceID, float Value);  Setzen des Istwertes aus Scripten oder Ablaufplänen, alternative zur Definition einer Istwertvariable  
PID_SetTargetValue(integer InstanceID, float Value);  Setzen des Sollwertes aus Scripten oder Ablaufplänen, alternative zur Definition einer Sollwertvariable
PID_ResetInstance(integer $InstanzID);    Rücksetzten aller persistenten Berechnungswerte 
PID_UpdateOutputValue(integer InstanceID); Auslösen einer Neuberechnung des Stelwlertes aus Scripten oder Ablaufplänen  

