# Changelog Philips Hue

# Changelog Plugin Philips Hue

>**Wichtig**
>
>Zur Erinnerung: Wenn keine Informationen zum Update vorhanden sind, bedeutet dies, dass es sich nur um die Aktualisierung von Dokumentation, Übersetzung oder Text handelt

# 13.08.2024

- Ein Fehler bei der Daemon-Statuserkennung wurde behoben
- Verbesserte Steuerung zum Aktivieren/Deaktivieren von Sensoren
- Verwaltung von Modulen mit dem gleichen Dienst mehrmals (z. B. Module mit doppelten Relaisausgängen))
- Daemon-Optimierung

# 28.02.2024

- Bessere Handhabung von Fällen, in denen zwei Szenen denselben Namen haben
- Fehlendes Bild für Module hinzugefügt

# 10.02.2024

- Fehlerbehebungen

# 02.07.2024

- Es wurde ein Fehler behoben, der die Konfiguration der Steuerungen für Räume, Lampengruppen und Zonen während der Synchronisierung änderte

# 25.01.2024

- Verbessertes Übergangsmanagement

# 24.01.2024

- Es wurde ein Fehler behoben, der in bestimmten Fällen dazu führen konnte, dass doppelte Ereignisse auftraten.

# 19.01.2024

- Problemumgehung zur Behebung des Helligkeitsfehlers beim Einschalten von Hue apiv2

# 17.01.2024

- Wiederaufnahme der vorherigen Helligkeit während eines Einschaltvorgangs
- Übergänge zu gruppierten Zonen, Räumen und Lichtern hinzugefügt
- Komplette Überarbeitung der Auftragserstellung : Es ist keine Konfiguration erforderlich, damit Ihre Lampe über die richtigen Bedienelemente verfügt. Alles kommt von der Brücke
- Alarmbefehl hinzugefügt
- Wichtig : Für diejenigen, die über Sockets verfügen, liegt möglicherweise ein Synchronisierungsfehler vor. Sie müssen daher den Statusbefehl auf den Sockets löschen und die Synchronisierung neu starten

# 16.01.2024

- HUE-Produktabbildungen hinzugefügt (LTV001, LTA011, LTA009, 5047431P6, 929003479601))

# 15.01.2024

- Verbessertes Übergangsmanagement
- LTC002 (Hue Ambiance Decke))

# 10.01.2024

- Unterstützung für Szenen in Zonen

# 01.08.2024

- Komplette Neufassung des Plugins zur Verwendung der hue 2.0 API
- Zum Gehen ist eine Neusynchronisation erforderlich
- WARNUNG : Für die Sensoren ändern sich die Befehle vollständig, sodass Sie Ihre Szenarien überprüfen müssen
- Wichtig : Einige Befehle sind mit dieser neuen Version nicht mehr verfügbar, darunter Warnungen, Regenbogen und Animationen
- Wichtig : Szenen sind jetzt vom Typ Aktion andere, also gibt es einen Befehl nicht Szene
- SEHR WICHTIG : Nur die v2-Bridge ist kompatibel. Wenn Sie sich auf der v1-Bridge befinden, sollten Sie auf keinen Fall aktualisieren, da Philips Hue die v2-API nicht auf die v1-Bridge portiert hat.


# 10.04.2021

- Modul hinzufügen
- Fehlerbehebungen

# 16.06.2021

- Adaptive_light an adaptive_lighting anpassen

# 07.06.2021

- Eine adaptive_light-Animation hinzufügen
- Ein Problem beim Auffinden von Szenen auf der 2. Hue-Brücke wurde behoben

# 15.03.2021

- Zugabe der Hue White Bulb A67 E27 1600lm
- Optimierungen und Fehlerbehebungen
- Modernisierung der Schnittstelle
- Bildoptimierung
- Neuer Farbton-Dimmerschalter hinzugefügt
- Hinzufügen des Smart Plug (Ein / Aus nur momentan keine Statusrückmeldung)

# 11.12.2020

- Korrektur eines CPU-Überlastungsfehlers beim Deaktivieren eines Sensors (der Dämon muss nach dem Update neu gestartet werden, um die Korrektur anzuwenden)

# 25.06.2020

- Unterstützung für mehrere Brücken (derzeit 2)

# 05/11/2020

- Hinzufügen einer Bestellung, um zu wissen, ob die Glühbirne erreichbar ist oder nicht

# 01/02/2020

- Bild für generische Glühbirnen hinzugefügt

# 2019.10.10

- Korrektur des Zurücksetzens des Lampenzustands auf 0, wenn er wieder eingeschaltet wird

# 2019.09.23

- Fehlerbehebungen
- Optimisations

# 2019.01.08

- Unterstützung für Feller EDIZIOdue colore
- Verbesserte Synchronisationsprotokolle

# 24.04.2019

- Fügen Sie eine Schaltfläche hinzu, um eine Bestellung zu löschen
- Korrektur der Konfigurationen für die Ikea-Lampen (Vorsicht, sie müssen aus dem Jeedom entfernt werden und eine Synchronisation wiederholen)

# 20.04.2019

- Unterstützung für SML002
- Unterstützung für Statusrückmeldungen von OSRAM SMART-Sockets (Aufmerksamkeit erfordert eine neue Aufnahme)

# 17.01.2019

- Zugabe der LTC016 Lampe
- Fügen Sie auf der Geräteverwaltungsseite eine Synchronisierungsschaltfläche hinzu

# 16.01.2019

- Konfiguration von generischen Farb- und Nichtfarblichtern hinzugefügt
- Unterstützung für Niko 4-Tasten
- Fehlerbehebung

# 2019.01.15

- Aktualisierung der Dokumentation
- Korrektur eines Fehlers im Status der Schaltflächen beim Neustart der Bridge
- Hinzufügen von Hue Lightstrip im Freien

# 16.10.2008

- Korrektur eines Fehlers bei der Anwesenheitsinversion für den Bewegungssensor (für die bereits erstellten muss das Kontrollkästchen "Inversion" in der Zeile des Befehls "Anwesenheit" aktiviert werden)

# 10/12/2018

- Es wurde ein Fehler im Status von Teilen (Ein / Aus) behoben, wenn keine farbige Lampe darin war
- Zusatz RB 145
- Zusatz LPT003

# 07/09/2018

- Hinzufügen des lebenden weißen Steckers

# 27.06.2008

- Fehlerbehebungen (danke @ mixman68)

# 2018.05.31

-	LTC001 (Hue Ambiente Decke)

# 14.04.2008

- Korrektur der Zeit der Sensorwerte
- FLOALT Panel WS 30x90
- TRADFRI Birne E14 WS Opal 400lm
-	TRADFRI E27 WS opal 980lm
-	TRADFRI E27 Farbe 600lm

# 23.02.2008

-	TRADFRI Birne E27 W opal 1000lm
-	TRADFRI Birne GU10 WS 400lm
-	TRADFRI Birne E27 Opal 1000lm

# 21.01.2008

- Wechseln Sie zum neuen Dokumentationssystem
- Hinzufügung des Modells MWB001
- Hinzufügen des Modells ltw010
- Hinzufügung des OSRAM-Modells
- Ergänzung der TRADFRI-Glühlampe GU10 W 400lm

# 20.11.2017

- Hinzufügung des Modells LCT015

# 28.03.2017

- Hinzufügen von Sonnenauf- und -untergangsanimationen (Vorsicht)
    Lampen sind nicht unbedingt kompatibel)

# 21.01.2017

- Farbton Bewegungsunterstützung
- Unterstützung für Farbtonabgriffe
- Korrektur von Szenen
- Farbverschiebungskorrektur
- Modulbilder hinzufügen
- Unterstützung für weitere Module
- Hinzufügen eines Farbtemperaturmanagements
