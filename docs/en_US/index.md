Plugin pour commander les lampes Philips Hue.

== Plugin configuration

Après téléchargement du plugin, il vous faudra renseigner l’adresse IP
de votre pont hue, si ce n’est pas déja fait par le système de
découverte automatique.

Configuration des équipements 
=============================

> **Note**
>
> Vous aurez toujours un équipement "Toutes les lampes" qui correspond
> en faite au groupe 0 qui existe tout le temps

You can find here the full configuration of your device :

-   **Nom de l’équipement Hue** : nom de votre équipement Hue,

-   **Parent Object** : means the parent object the equipment depend
    equipment,

-   **Catégorie** : les catégories de l’équipement (il peut appartenir à
    plusieurs catégories),

-   ** Enable **: to make your equipment active,

-   ** Visible **: makes your equipment visible on the dashboard,

En-dessous vous retrouvez la liste des commandes :

-   **Nom** : le nom affiché sur le dashboard,

-   **Configuration avancé** : permet d’afficher la fenetre de
    configuration avancée de la commande,

-   **Options** : vous permet d’afficher ou de masquer certaines
    commandes et/ou d’en historiser

-   **Tester** : permet de tester la commande

Le groupe 0 (Toute les lampes) 
==============================

Le groupe 0 est un peu particulier car il ne peut être supprimé ou
modifié, il pilote forcement toute les lampes et c’est aussi lui qui
porte les scénes.

En effet vous pouvez faire des "scenes" sur les Philips Hue. Celle-ci
doivent absolument être faite à partir de l’application mobile
(impossible de les faire dans Jeedom). Et suite à l’ajout d’une scene
vous devez absolument synchroniser Jeedom avec le bon (en resauvegardant
simple la configuration du plugin)

Tansition 
=========

Commande un peu particulier qui doit être utilisée dans un scénario,
elle permet de dire la transistion entre l’état actuel et la prochaine
commande doit durée X secondes.

Par exemple le matin vous pouvez vouloir simuler le levé du soleil en 3
minutes. Dans votre scénario vous avez donc juste à appeller la commande
transition et en parametre mettre 180, ensuite appeller la commande
couleur vers la couleur voulu.

Animation 
=========

Les animations sont des enchainements de transition, actuellement il
existe :

-   sunrise : pour simuler un levé de soleil. Il peut prendre en
    parametre :

    -   duration : pour definir la durée, par defaut 720s, ex pour 5min
        il faut mettre : duration=300

-   sunset : pour simuler un couché de soleil. Il peut prendre en
    parametre :

    -   duration : pour definir la durée, par defaut 720s, ex pour 5min
        il faut mettre : duration=300

FAQ
===

J’ai l’impression qu’il y a un décalage sur certaine couleur entre ce que je demande et la couleur de l’ampoule.

:   Il semble que la grille de couleur des ampoules aient un offset,
    nous cherchons comment corriger

<! - ->

Quelle est la fréquence de rafraîchissement ?

:   Le systeme recupère les informations toutes les 2s.


