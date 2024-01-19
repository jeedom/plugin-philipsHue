# Philips Hue Plugin

# Description

Mit diesem Plugin können Sie Ihr Philips Hue-Ökosystem in Jeedom integrieren. Das Plugin bietet die Möglichkeit, bis zu 2 Philips Hue-Bridges gleichzeitig anzusteuern.

# Configuration

## Plugin Konfiguration

Wie jedes Jeedom-Plugin auch das Plugin **Philips Hue** muss nach der Installation aktiviert werden.

Sobald das Plugin aktiviert ist, müssen Sie die IP-Adresse eingeben, unter der Ihre Philips Hue Bridge erreichbar ist. Normalerweise sollte das Plugin Sie während der ersten Synchronisation auffordern, den Bridge-Button zu drücken.

>**TRICK**
>
>Sie können bis zu 2 Philips Hue-Brücken eingeben, die gleichzeitig mit Jeedom kommunizieren können.

Das Plugin **Philips Hue** verwendet einen eigenen Daemon, um in ständigem Kontakt mit den Philips Hue-Brücken zu bleiben. Sie können den Status auf der Plugin-Konfigurationsseite überprüfen.


## Compatibilité

Alle mit der Hue-Bridge kompatiblen Module sind mit dem Jeedom-Plugin kompatibel. 

## Gerätekonfiguration

Zugriff auf die verschiedenen Geräte **Philips Hue**, Gehe zum Menü **Plugins → Kommunikation → Philips Hue**.

# Transition

Ein kleiner eigenartiger Befehl, der für die Verwendung in einem Szenario entwickelt wurde. Hiermit kann die Dauer des Übergangs zwischen dem aktuellen Status und dem nächsten Befehl in Sekunden festgelegt werden.

Zum Beispiel möchten Sie am Morgen den Sonnenaufgang in 3 Minuten simulieren. In Ihrem Szenario müssen Sie nur den Übergangsbefehl mit aufrufen ``180`` Rufen Sie im Parameter den Befehl color auf die gewünschte Farbe auf.
