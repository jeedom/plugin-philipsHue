Plugin para controlar lámparas Philips Hue.

# Configuración del plugin

Después de descargar el complemento, deberá ingresar la dirección IP
desde su puente de color, si no lo ha hecho ya el
descubrimiento automático.

# Configuración del equipo

> **Note**
>
> Siempre tendrá equipos "Todas las lámparas" que coincidan
> llegar al grupo 0 que existe todo el tiempo

Aquí encontrarás toda la configuración de tu equipo :

-   **Nombre del equipo de tono** : nombre de su equipo Hue,

-   **Objeto padre** : indica el objeto padre al que pertenece
    equipo,

-   **Categoría** : categorías de equipos (puede pertenecer a
    categorías múltiples),

-   **Activer** : activa su equipo,

-   **Visible** : hace que su equipo sea visible en el tablero,

A continuación encontrará la lista de pedidos. :

-   **Nom** : el nombre que se muestra en el tablero,

-   **Configuración avanzada** : permite visualizar la ventana de
    configuración de control avanzada,

-   **Options** : le permite mostrar u ocultar ciertos
    órdenes y / o grabarlos

-   **Tester** : Se usa para probar el comando

# Grupo 0 (todas las lámparas)

El grupo 0 es un poco especial porque no se puede eliminar o
modificado, necesariamente enciende todas las lámparas y también es él quien
lleva las escenas.

De hecho, puedes hacer "escenas" en Philips Hue. Esta
absolutamente debe hacerse desde la aplicación móvil
(imposible hacerlos en Jeedom). Y luego de la adición de una escena
absolutamente debe sincronizar Jeedom con el correcto (volviendo a guardar
configuración simple del complemento)

# Tansition

Un pequeño comando particular que debe usarse en un escenario,
permite decir la transición entre el estado actual y el siguiente
el comando debe durar X segundos.

Por ejemplo, en la mañana es posible que desee simular el amanecer en 3
minutos. En su escenario solo tiene que llamar al comando
transición y en el conjunto de parámetros 180, luego llame al comando
color al color deseado.

# Animation

Las animaciones son secuencias de transición, actualmente hay
existe :

-   amanecer : para simular un amanecer. El puede tomar
    parámetro :

    -   duración : para definir la duración, por defecto 720s, por ejemplo, 5 minutos
        hay que meter : duration=300

-   puesta de sol : para simular una puesta de sol. El puede tomar
    parámetro :

    -   duración : para definir la duración, por defecto 720s, por ejemplo, 5 minutos
        hay que meter : duration=300

# Botón de control remoto

Aquí está la lista de códigos para los botones. :

- 1002 para el botón de encendido
- 2002 para el botón de aumento
- 3002 para el botón minimizar
- 4002 para el botón de apagado

Lo mismo con XXX0 para la tecla presionada, XXX1 para la tecla mantenida y XXX2 para la tecla liberada.

Aquí están las secuencias para el botón On por ejemplo :

- Prensa corta : Cuando lo presionamos vamos a 1000 y cuando lo soltamos vamos a 1002
- Pulsación larga : Durante la prensa pasamos 1000, durante la prensa pasamos 1001, cuando lanzamos pasamos 1002

# FAQ

> **Tengo la impresión de que hay una diferencia en cierto color entre lo que pido y el color de la bombilla..**
>
> Parece que la cuadrícula de color de las bombillas tiene un desplazamiento, estamos buscando cómo corregir

> **¿Cuál es la frecuencia de actualización? ?**
>
> El sistema recupera información cada 2 segundos..

> **Mi equipo (lámpara / interruptor ...) no es reconocido por el complemento, cómo hacerlo ?**
>
> Hay que :
> - nosotros para escribir el equipo que desea agregar con foto y las posibilidades del mismo
> - envíenos el registro al inicio de la sincronización con el puente
> Todo contactándonos con una solicitud de soporte
