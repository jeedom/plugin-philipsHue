# Changelog Philips Hue

# Changelog plugin Philips Hue

>**IMPORTANT**
>
>Pour rappel s'il n'y a pas d'information sur la mise à jour, c'est que celle-ci concerne uniquement de la mise à jour de documentation, de traduction ou de texte

# 13/08/2024

- Correction d'un bug sur la detection de l'état du démon
- Amélioration des commandes d'activation/desactivation des capteurs
- Gestion des modules avec plusieurs fois le meme service (comme les modules à double sorties relais)
- Optimisation du démon

# 28/02/2024

- Meilleure gestion des cas ou deux scénes ont le meme nom
- Ajout d'image manquante pour les modules

# 10/02/2024

- Correction de bugs

# 07/02/2024

- Correction d'un bug qui changait la configuration des commandes pour les pieces, groupe de lampe et zone lors de la synchronisation

# 25/01/2024

- Amélioration de la gestion des transition

# 24/01/2024

- Correction d'un bug qui dans certain cas pouvait faire arriver les évènements en double.

# 19/01/2024

- Workarround pour corrigé le bug de la luminosité lors d'un on de l'apiv2 de Hue

# 17/01/2024

- Reprise de la luminosité précedente lors d'un on
- Ajout des transistions sur les zones, pieces et lumieres groupées
- Refonte complete de la créations des commandes : plus besoin d'avoir une configuration pour que votre lampe ait les bonnes commandes, tout vient du pont
- Ajout de la commande alerte
- IMPORTANT : pour ceux qui ont des prises il est possible que vous ayez une erreur a la synchronisation, il faut donc sur les prises supprimer la commande état et relancer la synchronisation

# 16/01/2024

- Ajout d'illustrations produits HUE (LTV001, LTA011, LTA009, 5047431P6, 929003479601)

# 15/01/2024

- Amélioration de la gestion des transitions
- LTC002 (Hue Ambiance Ceiling)

# 10/01/2024

- Support des scénes sur les zones

# 08/01/2024

- Réécriture complete du plugin pour utiliser l'api hue 2.0
- Necessite une resynchronisation pour marcher
- ATTENTION : Pour les capteurs les commandes changent completement il faut donc revoir vos scénarios
- IMPORTANT : certaine commandes ne seront plus disponible avec cette nouvelle version dont les alertes, l'arc en ciel et les animation
- IMPORTANT : Les scenes sont maintenant de type action other, il y a donc une commande pas scene
- TRES IMPORTANT : Seul le pont v2 est compatible, si vous etes sur le pont v1 alors il ne faut surtout pas mettre à jour car Philips Hue n'a pas porté l'api v2 sur le pont v1.


# 04/10/2021

- Ajout de module
- Correction de bugs

# 16/06/2021

- Correction de adaptive_light en adaptive_lighting

# 07/06/2021

- Ajout d'une animation adaptive_light
- Correction d'un soucis sur la decouverte des scenes sur le 2eme pont Hue

# 15/03/2021

- Ajout du Hue White Bulb A67 E27 1600lm
- Optimisations et corrections de bugs
- Modernisation de l'interface
- Optimisation des images
- Ajout du nouveau hue Dimmer Switch
- Ajout du smart plug (on/off seulement pas de retour d'état pour le moement)

# 11/12/2020

- Correction d'un défaut de surcharge CPU lors de la désactivation d'un capteur (il faut relancer le démon suite à la mise à jour pour appliquer la correction)

# 25/06/2020

- Prise en charge de plusieurs ponts (2 pour le moment)

# 11/05/2020

- Ajout d'une commande pour savoir si l'ampoule est joignable ou non

# 02/01/2020

- Ajout d'image pour les ampoules génériques

# 10/10/2019

- Correction de la remise à 0 de l'état de la lampe lorsqu'on la rallume

# 23/09/2019

- Correction de bugs
- Optimisations

# 01/08/2019

- Prise en charge du Feller EDIZIOdue colore
- Amélioration des logs de synchronisation

# 24/04/2019

- Ajout d'un bouton pour supprimer une commande
- Correction des configurations pour les ampoules Ikea (attention il faut les supprimer de jeedom et refaire une synchronisation)

# 20/04/2019

- Prise en charge du SML002
- Prise en charge du retour d'état des prise OSRAM SMART (attention nécessite une nouvelle inclusion)

# 17/01/2019

- Ajout de la lampe LTC016
- Ajout d'un bouton de synchronisation sur la page de gestion des équipements

# 16/01/2019

- Ajout de configuration de lumières génériques couleurs et non couleurs
- Prise en charge de Niko 4 boutons
- Correction de bug

# 15/01/2019

- Mise à jour de la documentation
- Correction d'un bug sur l'état des bouton lors du redémarrage du pont
- Ajout Hue lightstrip outdoor

# 16/10/2018

- Correction d'un bug sur l'inversion de présence pour le motion sensor (pour ceux déjà créés, il faudra cocher la case d'inversion sur la ligne de la commande Présence)

# 12/10/2018

- Correction d'un bug sur le statut des pièces (allumée/éteinte) si il n'y a pas de lampe de couleur dans celle-ci
- Ajout RB 145
- Ajout LPT003

# 09/07/2018

- Ajout du living white plug

# 27/06/2018

- Correction de bugs (merci @mixman68)

# 31/05/2018

-	LTC001 (Hue ambiance ceiling)

# 14/04/2018

- Correction de l'heure des valeurs des capteurs
- FLOALT panel WS 30x90
- TRADFRI bulb E14 WS opal 400lm
-	TRADFRI E27 WS opal 980lm
-	TRADFRI E27 couleur 600lm

# 23/02/2018

-	TRADFRI bulb E27 W opal 1000lm
-	TRADFRI bulb GU10 WS 400lm
-	TRADFRI bulb E27 opal 1000lm

# 21/01/2018

- Passage sur le nouveau système de documentation
- Ajout du modèle MWB001
- Ajout du modèle ltw010
- Ajout du modèle OSRAM
- Ajout du modèle TRADFRI bulb GU10 W 400lm

# 20/11/2017

- Ajout du modèle LCT015

# 28/03/2017

- Ajout d’animations lever et coucher de soleil (attention toutes les
    lampes ne sont pas forcément compatibles)

# 21/01/2017

- Prise en charge du hue motion
- Prise en charge du hue tap
- Correction des scènes
- Correction du décalage des couleurs
- Ajout des images des modules
- Prise en charge de plus de modules
- Ajout de la gestion de la température des couleurs
