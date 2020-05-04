Plugin para controlar lâmpadas Philips Hue.

# Configuração do plugin

Depois de baixar o plug-in, você precisará inserir o endereço IP
da sua ponte de matiz, se ainda não tiver sido feito pelo
descoberta automática.

# Configuração do equipamento

> **Note**
>
> Você sempre terá o equipamento "Todas as lâmpadas" que corresponda
> chegar ao grupo 0, que existe o tempo todo

Aqui você encontra toda a configuração do seu equipamento :

-   **Nome do equipamento Hue** : nome do seu equipamento Hue,

-   **Objeto pai** : indica o objeto pai ao qual pertence
    o equipamento,

-   **Categoria** : categorias de equipamentos (pode pertencer a
    várias categorias),

-   **Activer** : torna seu equipamento ativo,

-   **Visible** : torna seu equipamento visível no painel,

Abaixo você encontra a lista de pedidos :

-   **Nom** : o nome exibido no painel,

-   **Configuração avançada** : permite exibir a janela de
    configuração de controle avançado,

-   **Options** : permite mostrar ou ocultar certos
    pedidos e / ou registrá-los

-   **Tester** : permite testar o comando

# Grupo 0 (todas as lâmpadas)

O grupo 0 é um pouco especial porque não pode ser excluído ou
modificado, ele necessariamente aciona todas as lâmpadas e também é ele quem
carrega as cenas.

Na verdade, você pode fazer "cenas" no Philips Hue. Isto
absolutamente deve ser feito a partir do aplicativo móvel
(impossível fazê-los em Jeedom). E após a adição de uma cena
você absolutamente deve sincronizar o Jeedom com o correto (salvando novamente
configuração simples de plugins)

# Tansition

Um pequeno comando específico que deve ser usado em um cenário,
permite dizer a transição entre o estado atual e o próximo
comando deve durar X segundos.

Por exemplo, de manhã, você pode simular o nascer do sol em 3
minutos. No seu cenário, você apenas precisa chamar o comando
transição e no conjunto de parâmetros 180, chame o comando
cor à cor desejada.

# Animation

As animações são sequências de transição, atualmente existem
existe :

-   nascer do sol : para simular um nascer do sol. Ele pode levar
    parâmetro :

    -   duração : para definir a duração, por padrão 720s, ex por 5min
        você tem que colocar : duration=300

-   pôr do sol : para simular um pôr do sol. Ele pode levar
    parâmetro :

    -   duração : para definir a duração, por padrão 720s, ex por 5min
        você tem que colocar : duration=300

# Botão de controle remoto

Aqui está a lista de códigos para os botões :

- 1002 para o botão Ligar
- 2002 para o botão de aumento
- 3002 para o botão minimizar
- 4002 para o botão desligar

O mesmo com XXX0 para a tecla pressionada, XXX1 para a tecla pressionada e XXX2 para a tecla liberada.

Aqui estão as sequências para o botão On, por exemplo :

- Pressão curta : Quando pressionado, vamos para 1000 e, quando liberamos, vamos para 1002
- Pressão longa : Durante a imprensa, passamos a 1000, durante a imprensa, passamos a 1001, quando liberamos, passamos a 1002

# FAQ

> **Tenho a impressão de que há uma diferença em determinada cor entre o que peço e a cor da lâmpada.**
>
> Parece que a grade de cores das lâmpadas tem um deslocamento, estamos procurando como corrigir

> **Qual é a taxa de atualização ?**
>
> O sistema recupera informações a cada 2s.

> **Meu equipamento (lâmpada / interruptor ....) não é reconhecido pelo plug-in, como fazer ?**
>
> Você deve :
> - nós escrevemos o equipamento que você deseja adicionar com a foto e as possibilidades dele
> - envie-nos o log no início da sincronização com a ponte
> Tudo entrando em contato conosco com uma solicitação de suporte
