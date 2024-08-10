# Alterações Philips Hue

# Plugin de registro de mudanças Philips Hue

>**IMPORTANTE**
>
>Como lembrete, se não houver informações sobre a atualização, isso significa que se trata apenas da atualização da documentação, tradução ou texto

- Controles aprimorados de ativação/desativação do sensor
- Gerenciamento de módulos com o mesmo serviço diversas vezes (como módulos com saídas duplas de relé)

# 28/02/2024

- Melhor tratamento de casos onde duas cenas possuem o mesmo nome
- Adicionada imagem ausente para módulos

# 10/02/2024

- Correções de bugs

# 07/02/2024

- Corrigido um bug que alterava a configuração dos controles de salas, grupos de lâmpadas e zonas durante a sincronização

# 25/01/2024

- Gerenciamento de transição aprimorado

# 24/01/2024

- Corrigido um bug que em certos casos poderia causar a ocorrência de eventos duplicados.

# 19/01/2024

- Solução alternativa para corrigir o bug de brilho ao ativar o Hue apiv2

# 17/01/2024

- Retomada do brilho anterior durante uma ligação
- Adicionadas transições para zonas, salas e luzes agrupadas
- Revisão completa da criação de pedidos : não precisa ter configuração para que sua lâmpada tenha os controles corretos, tudo vem da ponte
- Adicionado comando de alerta
- IMPORTANTE : para quem tem sockets é possível que tenha algum erro de sincronização, portanto deve deletar o comando status nos sockets e reiniciar a sincronização

# 16/01/2024

- Adicionadas ilustrações de produtos HUE (LTV001, LTA011, LTA009, 5047431P6, 929003479601)

# 15/01/2024

- Gerenciamento de transição aprimorado
- LTC002 (teto ambiente matiz)

# 10/01/2024

- Suporte para cenas em zonas

# 01/08/2024

- Reescrita completa do plugin para usar a API Hue 2.0
- Requer ressincronização para andar
- AVISO : Para os sensores, os comandos mudam completamente, então você precisa revisar seus cenários
- IMPORTANTE : certos comandos não estarão mais disponíveis com esta nova versão, incluindo alertas, arco-íris e animações
- IMPORTANTE : As cenas agora são do tipo ação outro, então há um comando não cena
- MUITO IMPORTANTE : Somente a ponte v2 é compatível, se você estiver na ponte v1, definitivamente não deve atualizar porque o Philips Hue não portou a API v2 para a ponte v1.


# 10/04/2021

- Adicionando módulo
- Correções de bugs

# 16/06/2021

- Corrigir adaptive_light para adaptive_lighting

# 07/06/2021

- Adicionando uma animação adaptive_light
- Corrigido um problema com a descoberta de cenas na ponte 2ª Hue

# 15/03/2021

- Adição da lâmpada Hue White A67 E27 1600lm
- Otimizações e correções de bugs
- Modernização da interface
- Otimização de imagem
- Adicionado um novo switch Dimmer matiz
- Adição do plugue inteligente (ligado / desligado apenas sem feedback de status no momento)

# 11/12/2020

- Correção de uma falha de sobrecarga da CPU ao desativar um sensor (o daemon deve ser reiniciado após a atualização para aplicar a correção)

# 25/06/2020

- Suporte para várias pontes (2 no momento)

# 05/11/2020

- Adição de uma ordem para saber se a lâmpada está acessível ou não

# 01/02/2020

- Adicionada imagem para lâmpadas genéricas

# 10/10/2019

- Correção da redefinição do estado da lâmpada para 0 quando ele é ligado novamente

# 23/09/2019

- Correções de bugs
- Optimisations

# 01/08/2019

- Suporte para Feller EDIZIOdue colore
- Logs de sincronização aprimorados

# 24/04/2019

- Adicione um botão para excluir um pedido
- Correção das configurações das lâmpadas Ikea (cuidado, elas devem ser retiradas do jeedom e refazer uma sincronização)

# 20/04/2019

- Suporte para SML002
- Suporte para feedback de status de soquetes OSRAM SMART (atenção requer uma nova inclusão)

# 17/01/2019

- Adição da lâmpada LTC016
- Adicione um botão de sincronização na página de gerenciamento de equipamentos

# 16/01/2019

- Adicionada configuração de cores genéricas e luzes não coloridas
- Suporte para botões Niko 4
- Bug fix

# 15/01/2019

- Atualização de documentação
- Correção de um bug no estado dos botões ao reiniciar a ponte
- Adicionando a excursão Hue ao ar livre

# 16/10/2018

- Correção de bug na inversão de presença para o sensor de movimento (para os já criados será necessário marcar a caixa de inversão na linha do comando Presença)

# 12/12/2018

- Corrigido um bug no status das peças (ligado / desligado) se não houver lâmpada colorida nele
- Adição RB 145
- Adição LPT003

# 09/09/2018

- Adicionando o plugue branco vivo

# 27/06/2018

- Correções de bugs (obrigado @ mixman68)

# 31/05/2018

-	LTC001 (teto ambiente Hue)

# 14/04/2018

- Correção do tempo dos valores do sensor
- Painel FLOALT WS 30x90
- Lâmpada TRADFRI E14 WS opala 400lm
-	TRADFRI E27 WS opala 980lm
-	TRADFRI E27 cor 600lm

# 23/02/2018

-	Lâmpada TRADFRI E27 W opala 1000lm
-	Lâmpada TRADFRI GU10 WS 400lm
-	Lâmpada TRADFRI E27 opala 1000lm

# 21/01/2018

- Mudar para o novo sistema de documentação
- Adição do modelo MWB001
- Adicionando o modelo ltw010
- Adição do modelo OSRAM
- Adição do modelo da lâmpada TRADFRI GU10 W 400lm

# 20/11/2017

- Adição do modelo LCT015

# 28/03/2017

- Adição de animações de nascer e pôr do sol (tenha cuidado com todos
    lâmpadas não são necessariamente compatíveis)

# 21/01/2017

- Suporte de movimento de matiz
- Suporte para torneira matiz
- Correção de cenas
- Correção de mudança de cor
- Adicionando imagens de módulo
- Suporte para mais módulos
- Adição de gerenciamento de temperatura de cor
