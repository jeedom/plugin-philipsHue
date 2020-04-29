Plugin zur Steuerung von Philips Hue-Lampen.

# Plugin Konfiguration

Nach dem Herunterladen des Plugins müssen Sie die IP-Adresse eingeben
von Ihrer Farbtonbrücke, falls nicht bereits von der
automatische Erkennung.

# Gerätekonfiguration

> **Note**
>
> Sie haben immer die passende Ausrüstung "Alle Lampen"
> Schaffe es bis zur Gruppe 0, die ständig existiert

Hier finden Sie die gesamte Konfiguration Ihrer Geräte :

-   **Name der Farbtonausrüstung** : Name Ihrer Hue-Ausrüstung,

-   **Übergeordnetes Objekt** : gibt das übergeordnete Objekt an, zu dem es gehört
    Ausrüstung,

-   **Kategorie** : Gerätekategorien (es kann gehören
    mehrere Kategorien),

-   **Activer** : macht Ihre Ausrüstung aktiv,

-   **Visible** : macht Ihre Ausrüstung auf dem Armaturenbrett sichtbar,

Nachfolgend finden Sie die Liste der Bestellungen :

-   **Nom** : Der im Dashboard angezeigte Name,

-   **Erweiterte Konfiguration** : ermöglicht die Anzeige des Fensters von
    erweiterte Steuerungskonfiguration,

-   **Options** : ermöglicht es Ihnen, bestimmte ein- oder auszublenden
    Bestellungen und / oder um sie aufzuzeichnen

-   **Tester** : Wird zum Testen des Befehls verwendet

# Gruppe 0 (Alle Lampen)

Gruppe 0 ist etwas Besonderes, da sie nicht gelöscht werden kann oder
modifiziert treibt es zwangsläufig alle lampen an und es ist auch er wer
trägt die Szenen.

In der Tat können Sie "Szenen" auf dem Philips Hue machen. Das hier
muss unbedingt aus der mobilen App gemacht werden
(unmöglich, sie in Jeedom zu tun). Und nach dem Hinzufügen einer Szene
Sie müssen Jeedom unbedingt mit dem richtigen synchronisieren (durch erneutes Speichern
einfache Plugin-Konfiguration)

# Tansition

Ein kleiner bestimmter Befehl, der in einem Szenario verwendet werden muss,
es erlaubt den Übergang zwischen dem aktuellen Zustand und dem nächsten zu sagen
Befehl muss X Sekunden dauern.

Zum Beispiel möchten Sie am Morgen den Sonnenaufgang in 3 simulieren
Minuten. In Ihrem Szenario müssen Sie nur den Befehl aufrufen
Übergang und im Parametersatz 180, dann den Befehl aufrufen
Farbe auf die gewünschte Farbe.

# Animation

Die Animationen sind Übergangssequenzen, die derzeit vorhanden sind
existiert :

-   Sonnenaufgang : einen Sonnenaufgang simulieren. Er kann nehmen
    Parameter :

    -   Dauer : um die Dauer zu definieren, standardmäßig 720s, zB für 5min
        du musst setzen : Dauer = 300

-   Sonnenuntergang : einen Sonnenuntergang simulieren. Er kann nehmen
    Parameter :

    -   Dauer : um die Dauer zu definieren, standardmäßig 720s, zB für 5min
        du musst setzen : Dauer = 300

# Fernbedienungstaste

Hier ist die Liste der Codes für die Schaltflächen :

- 1002 für die Ein-Taste
- 2002 für den Erhöhungsknopf
- 3002 für die Minimierungstaste
- 4002 für die Aus-Taste

Das gleiche gilt für XXX0 für die gedrückte Taste, XXX1 für die gehaltene Taste und XXX2 für die freigegebene Taste.

Hier sind zum Beispiel die Sequenzen für die Schaltfläche Ein :

- Kurz drücken : Wenn gedrückt, gehen wir zu 1000 und wenn wir loslassen, gehen wir zu 1002
- Lange drücken : Während der Presse geben wir 1000 weiter, während der Presse geben wir 1001 weiter, wenn wir loslassen, geben wir 1002 weiter

# Faq

> **Ich habe den Eindruck, dass es in bestimmten Farben einen Unterschied zwischen dem, was ich frage, und der Farbe der Glühbirne gibt.**
>
> Es scheint, dass das Farbraster der Glühbirnen einen Versatz hat. Wir suchen nach einer Korrektur

> **Was ist die Bildwiederholfrequenz? ?**
>
> Das System ruft alle 2 Sekunden Informationen ab.

> **Meine Ausrüstung (Lampe / Schalter ....) wird vom Plugin nicht erkannt ?**
>
> Du musst :
> - wir schreiben die Ausrüstung, die Sie hinzufügen möchten, mit Foto und Möglichkeiten davon
> - Senden Sie uns das Protokoll zu Beginn der Synchronisation mit der Bridge
> Alles, indem Sie uns mit einer Support-Anfrage kontaktieren
