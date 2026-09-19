---
name: crm-vendas-hostinger
description: Gera um CRM de vendas completo (PHP + MySQL, pronto para hospedagem compartilhada Hostinger) com login individual por vendedor, controle de acesso por perfil (vendedor só vê os próprios leads; administrador vê tudo), histórico de conversas, perfil DISC, ranking de vendas sem vazar valor entre vendedores, exportação CSV/Excel/PDF e manuais de uso em PDF (um para administrador, um só para vendedor) — tudo com o nome e os pilares/categorias da empresa do usuário. Use esta skill sempre que o usuário pedir para criar, montar ou adaptar um CRM de vendas, painel comercial, sistema de leads, funil de vendas ou controle de propostas para uma equipe pequena hospedada no Hostinger (ou hospedagem compartilhada PHP/MySQL equivalente) — mesmo que ele não diga "CRM" explicitamente, como "preciso de um sistema para minha equipe de vendas acompanhar clientes" ou "quero um painel onde cada vendedor só veja os próprios leads". Funciona para qualquer empresa: basta informar o nome dela (e, se quiser, os pilares/categorias de produto e a equipe inicial) que a skill adapta textos, marca e conteúdo.
---

# CRM de Vendas para Hostinger (PHP + MySQL)

Esta skill gera uma cópia completa e funcional do CRM de vendas construído
originalmente para o EdusIA Instituto — adaptada para **qualquer empresa**,
a partir de um template já testado (`assets/template/`). Não escreva o
sistema do zero: copie e personalize o template, é muito mais confiável do
que recriar ~1500 linhas de HTML/JS/PHP a cada pedido.

## O que o CRM gerado já faz, de fábrica

- Cadastro de contatos (leads/clientes) com perfil DISC, origem do lead e
  vendedor responsável.
- Histórico de conversas por contato: canal, resumo, temperatura, próxima
  ação/follow-up, pilar, produto/serviço, valor da proposta e estágio da
  negociação.
- **Login individual por e-mail/senha** (sem token único compartilhado),
  com troca de senha e logout.
- **Controle de acesso por perfil, reforçado no servidor (`api.php`), não só
  na tela:** vendedor só recebe (e só pode ver/editar) os próprios leads;
  administrador tem acesso total a todos os leads e valores da equipe.
- **Ranking de vendas** visível a todos, por negócios fechados-ganho — o
  valor em R$ só aparece na própria linha do vendedor e para administradores;
  as demais linhas mostram um cadeado.
- KPIs (total de leads, taxa de conversão, pipeline em aberto, follow-ups
  atrasados, leads por vendedor), filtros, alerta visual de atraso.
- Exportação em CSV, Excel (SheetJS) e PDF (jsPDF).
- **E-mails automáticos de follow-up** (opcional, via Cron Job do Hostinger):
  quando a data de follow-up de uma interação chega, envia um e-mail ao lead
  e um alerta interno ao vendedor responsável.
- **Assistente de resposta com IA** (opcional, requer API key da Anthropic):
  o vendedor cola a resposta do comprador e a IA sugere um texto persuasivo
  para convencê-lo a avançar — o vendedor sempre revisa antes de enviar, e
  pode abrir a sugestão direto no WhatsApp com um clique.
- **Aba "Prospectar Clientes"** (opcional): busca empresas em potencial
  por cidade + ramo de atuação, no Google Maps (API key do Google) e/ou
  por CNPJ na Casa dos Dados (razão social/nome fantasia, com dados como
  situação cadastral, porte e sócios), numa lista separada de "prospects"
  para o vendedor revisar; dá pra completar um prospect achado no Maps com
  os dados de CNPJ da Casa dos Dados. A IA gera mensagem de abordagem para
  e-mail ou WhatsApp, e o vendedor pode converter o prospect em lead de
  verdade no CRM com um clique.
- **Ajuda para identificar e dica de abordagem por perfil DISC** (sem custo,
  texto fixo): no cadastro, uma lista de frases típicas ("quero ver quem já
  usou e os resultados" → perfil C) ajuda a identificar o perfil do lead
  com um clique; depois, o painel mostra automaticamente dicas de como
  convencer aquele tipo de perfil.
- Área de administrador: gerenciar equipe (criar/remover contas) e relatório
  de acessos (quem entrou, quando, de qual IP).
- Tema claro/escuro automático, responsivo, sem dependências além de duas
  bibliotecas de exportação carregadas por CDN.

## Quando usar

Dispare sempre que o pedido envolver montar um CRM/painel comercial para uma
equipe de vendas pequena, especialmente quando o usuário mencionar Hostinger,
hospedagem compartilhada, PHP, ou pedir explicitamente que "cada vendedor só
veja os próprios leads". Funciona tanto para o primeiro pedido ("crie um CRM
para minha empresa X") quanto para pedidos de evolução em um projeto que já
usa este template (olhe se existe uma pasta com `api.php` + `index.html` +
`schema.sql` parecidos com os de `assets/template/` — nesse caso, edite os
arquivos existentes em vez de gerar um projeto novo do zero).

## Passo 1 — Reunir as informações da empresa

Pergunte ao usuário (uma pergunta objetiva, tudo pode vir numa única
resposta):

1. **Nome da empresa** (obrigatório) — ex.: "Nogueira Consultoria",
   "Vértice Imóveis". Vai aparecer no título, cabeçalho e rodapé do painel.
2. **Pilares ou categorias de produto/serviço** (opcional) — a lista curta
   que aparece no cabeçalho e nos campos de proposta (ex.: "Formação,
   Consultoria, Labs, Studio, Eventos" no exemplo original; para uma
   imobiliária poderia ser "Locação, Venda, Administração"). Se o usuário não
   tiver esse conceito, use uma lista genérica de 1-2 itens (ex.: "Vendas")
   ou pergunte se o negócio nem se organiza por pilares.
3. **Equipe inicial de vendas** (opcional) — nomes completos de quem vai
   usar o painel. Se não for informado, deixe a lista de vendedores vazia
   (a equipe é cadastrada depois, pelo próprio painel).
4. **Domínio/URL onde vai hospedar** (opcional) — só para preencher exemplos
   no manual e no README; se não souber ainda, use um placeholder genérico.

Não invente esses dados nem prossiga sem o nome da empresa — é a única
informação realmente obrigatória; os outros itens têm um padrão razoável se
o usuário não responder.

## Passo 2 — Calcular os valores de personalização

A partir do nome da empresa, derive:

- `EMPRESA`: o nome como o usuário informou (ex.: "Nogueira Consultoria").
- `EMPRESA_CURTO`: uma versão curta para textos de uma linha (ex.: primeira
  palavra ou uma sigla natural — "Nogueira").
- `LOGO_A` / `LOGO_B`: o cabeçalho tem um efeito de "duas cores" no nome
  (no original, "Edus" em roxo + "IA" em cor normal). Escolha um corte
  natural do `EMPRESA_CURTO` — prefixo de 3 a 6 letras em `LOGO_A`, o
  restante em `LOGO_B`. Se o nome for curto demais para dividir bem (ex.:
  "Vix"), pode repetir o nome inteiro em `LOGO_A` e deixar `LOGO_B` vazio.
- `SLUG`: `EMPRESA_CURTO` em minúsculas, sem espaços/acentos, só
  `[a-z0-9_]` (ex.: "nogueira") — usado em nomes de banco de dados e chaves
  internas de armazenamento no navegador.
- `PILARES` (lista): a partir dos pilares informados (ou do padrão que você
  escolheu no Passo 1), gere:
  - `PILARES_SUBTITLE`: `"Pilar1 · Pilar2 · Pilar3"`
  - `PILARES_CHIPS_HTML`: uma linha `<span class="pillar-chip">Pilar1</span>`
    por pilar, uma por linha, indentada com 6 espaços (veja o padrão já
    presente no template ao redor do placeholder).
  - `PILARES_OPTIONS_HTML`: `<option>Pilar1</option><option>Pilar2</option>...`
    tudo em uma linha só.
  - `PILARES_JS_ARRAY`: `["Pilar1","Pilar2","Pilar3"]` (array JS válido).
- `VENDEDORES_SQL_VALUES`: se houver nomes de equipe iniciais, gere
  `('Nome 1'),\n  ('Nome 2'),\n  ('Nome 3')` (vírgulas entre linhas, ponto e
  vírgula final já está no arquivo). Se não houver ninguém ainda, use uma
  única linha comentada explicando que a lista pode ficar vazia e os nomes
  serão adicionados pelo próprio painel (dropdown "+ Adicionar novo
  vendedor..." ou tela "Gerenciar equipe").

## Passo 3 — Copiar e personalizar os arquivos

1. Escolha (ou pergunte) o destino: por padrão, crie uma pasta
   `<slug>-crm/` no diretório de trabalho atual.
2. Copie estes 6 arquivos de `assets/template/` para o destino, sem alterar
   nome nem estrutura:
   - `index.html`
   - `api.php`
   - `schema.sql`
   - `config.sample.php`
   - `cron_followup.php`
   - `README.md`
3. Em cada um, substitua todos os placeholders `{{EMPRESA}}`,
   `{{EMPRESA_CURTO}}`, `{{LOGO_A}}`, `{{LOGO_B}}`, `{{SLUG}}`,
   `{{PILARES_SUBTITLE}}`, `{{PILARES_CHIPS_HTML}}`, `{{PILARES_OPTIONS_HTML}}`,
   `{{PILARES_JS_ARRAY}}` e `{{VENDEDORES_SQL_VALUES}}` pelos valores
   calculados no Passo 2. Use busca-e-substituição simples (o template foi
   desenhado para isso — cada placeholder aparece em contexto óbvio).
4. Depois de substituir, valide a sintaxe antes de entregar:
   - `php -l api.php` e `php -l config.sample.php` (não pode dar erro)
   - Para o JavaScript embutido no `index.html`, extraia o conteúdo entre
     `<script>(function(){` e `})();\n</script>` e rode `node --check` nele
     (o arquivo tem só um bloco de script relevante; os `<script src=...>`
     de bibliotecas externas ficam de fora).
5. **Nunca** deixe um `{{...}}` sem substituir no resultado final — isso
   quebraria o painel. Faça uma busca por `{{` no arquivo final para
   conferir.

## Passo 4 — Gerar os dois manuais em PDF

Use o script pronto (não escreva um gerador do zero — ele já cobre os dois
perfis e mantém a identidade visual do painel):

```bash
python3 assets/manuais/gerar_manual.py \
  --empresa "Nome da Empresa" --perfil admin \
  --url "https://dominio-do-cliente.com/crm/" \
  --out <destino>/docs/Manual_Administrador.pdf

python3 assets/manuais/gerar_manual.py \
  --empresa "Nome da Empresa" --perfil vendedor \
  --url "https://dominio-do-cliente.com/crm/" \
  --out <destino>/docs/Manual_Vendedor.pdf
```

Requer o pacote Python `reportlab` (`pip install reportlab` se ainda não
estiver instalado). O manual do vendedor **omite** a área de administrador e
a tabela de comparação de acessos — troca por uma seção curta "O que é
exclusivo do administrador", para não confundir quem só vai usar a parte de
vendedor.

Se o usuário pedir só um dos dois manuais, gere só o que foi pedido — não é
preciso sempre gerar os dois.

## Passo 5 — Entregar e explicar os próximos passos

1. Envie os arquivos gerados ao usuário (ou deixe no repositório, se for o
   caso — confirme com o usuário antes de fazer commit/push, como em
   qualquer projeto).
2. Resuma o que falta para colocar no ar, seguindo o próprio `README.md`
   gerado (que já tem o passo a passo de hPanel/phpMyAdmin): criar o banco
   MySQL, rodar o `schema.sql`, configurar o `config.php` a partir do
   `config.sample.php`, e enviar os 3 arquivos (`index.html`, `api.php`,
   `config.php`) para uma pasta em `public_html` no Hostinger.
3. Deixe claro que a primeira conta de administrador precisa ser criada
   manualmente no banco (gerando hash de senha + token com
   `php -r "echo password_hash(...); echo bin2hex(random_bytes(24));"`,
   como o próprio `README.md` explica) — depois disso, todo o resto da
   equipe é cadastrado pela tela "Gerenciar equipe".

## Adaptando o template para casos fora do padrão

- **Sem pilares/categorias de produto:** simplifique `PILARES` para uma
  lista de 1 item (ex.: `["Vendas"]`) — o campo "Pilar" na proposta ainda
  funciona, só fica menos protagonista.
- **Empresa que já tem um domínio/servidor Hostinger ativo:** pule direto
  para o Passo 3 e 4, sem precisar reexplicar o Passo 1 de criação de conta
  se o usuário já disser que só quer o código.
- **Pedido de ajuste pontual num projeto já gerado por esta skill** (ex.:
  "muda a cor do painel", "adiciona um campo X"): não regenere tudo do
  zero — edite diretamente os arquivos existentes do projeto do usuário,
  usando o template em `assets/template/` só como referência de como o
  recurso foi implementado originalmente.
- **Personalização visual (cores, fontes):** o CSS já embutido no
  `index.html` usa variáveis (`--violet`, `--paper`, etc.) no topo do
  arquivo — é mais seguro trocar os valores dessas variáveis do que reescrever
  regras de CSS espalhadas.
