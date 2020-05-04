Plugin to control Philips Hue lamps.

# Plugin configuration

After downloading the plugin, you will need to enter the IP address
from your hue bridge, if not already done by the
automatic discovery.

# Equipment configuration

> **Note**
>
> You will always have "All lamps" equipment that matches
> make it to group 0 which exists all the time

Here you find all the configuration of your equipment :

-   **Hue equipment name** : name of your Hue equipment,

-   **Parent object** : indicates the parent object to which belongs
    equipment,

-   **Category** : equipment categories (it may belong to
    multiple categories),

-   **Activer** : makes your equipment active,

-   **Visible** : makes your equipment visible on the dashboard,

Below you find the list of orders :

-   **Nom** : the name displayed on the dashboard,

-   **Advanced configuration** : allows to display the window of
    advanced control configuration,

-   **Options** : allows you to show or hide certain
    orders and / or to record them

-   **Tester** : Used to test the command

# Group 0 (All lamps)

Group 0 is a bit special because it cannot be deleted or
modified, it necessarily drives all the lamps and it is also he who
carries the scenes.

Indeed you can do "scenes" on the Philips Hue. This
must absolutely be made from the mobile app
(impossible to do them in Jeedom). And following the addition of a scene
you absolutely must synchronize Jeedom with the correct one (by resaving
simple plugin configuration)

# Tansition

A little particular command which must be used in a scenario,
it allows to say the transition between the current state and the next
command must duration X seconds.

For example in the morning you may want to simulate the sunrise in 3
minutes. In your scenario you just have to call the command
transition and in parameter set 180, then call the command
color to desired color.

# Animation

The animations are transition sequences, currently there
exist :

-   sunrise : to simulate a sunrise. He can take
    setting :

    -   duration : to define the duration, by default 720s, ex for 5min
        it is necessary to put : duration=300

-   sunset : to simulate a sunset. He can take
    setting :

    -   duration : to define the duration, by default 720s, ex for 5min
        it is necessary to put : duration=300

# Remote control button

Here is the list of codes for the buttons :

- 1002 for the On button
- 2002 for the increase button
- 3002 for the minimize button
- 4002 for the off button

The same with XXX0 for the key pressed, XXX1 for the key held and XXX2 for the key released.

Here are the sequences for the On button for example :

- Short press : When pressed we go to 1000 and when we release we go to 1002
- Long press : During the press we pass on 1000, during the press we pass on 1001, when we release we pass on 1002

# FAQ

> **I have the impression that there is a difference in certain color between what I ask and the color of the bulb.**
>
> It seems that the color grid of the bulbs has an offset, we are looking for how to correct

> **What is the refresh rate ?**
>
> The system retrieves information every 2s.

> **My equipment (lamp / switch ....) is not recognized by the plugin, how to do ?**
>
> It is necessary :
> - us to write the equipment you want to add with photo and possibilities of it
> - send us the log at the start of synchronization with the bridge
> All by contacting us with a support request
