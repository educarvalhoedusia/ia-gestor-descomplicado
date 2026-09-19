# Painel Comercial {{EMPRESA}} — versão Hostinger (guia simples)

Siga esta ordem exata, sem pular passos. Leva uns 15 minutos, mesmo sem
experiência técnica.

## O que você vai fazer, em 5 passos

1. Criar um banco de dados no hPanel (só clicar em botões).
2. Colar um código pronto no phpMyAdmin (copiar e colar, uma vez).
3. Editar 1 arquivo de texto com 4 informações (copiar e colar, com atenção).
4. Enviar 3 arquivos para o Hostinger (arrastar e soltar).
5. Abrir o link e usar.

---

### Passo 1 — Criar o banco de dados

1. Entre no **hPanel** (painel do Hostinger).
2. No menu, procure **"Bancos de dados"** → **"Bancos de dados MySQL"**.
3. Em **"Criar novo banco de dados MySQL"**, digite um nome, por exemplo `{{SLUG}}_crm`, e clique em criar.
   - O Hostinger vai colocar um prefixo automático, tipo `u123456789_{{SLUG}}_crm`. Tudo bem, é normal.
4. Logo abaixo, crie um **usuário** para esse banco (ex.: `{{SLUG}}_user`) e uma **senha**.
   - Clique em **"Gerar"** para criar uma senha forte automaticamente, e **copie e guarde essa senha** num lugar seguro (ex.: nas notas do celular).
5. Associe esse usuário ao banco que você criou, com **todos os privilégios** marcados.

📝 **Anote em algum lugar, você vai precisar logo mais:**
- Nome do banco: `______________`
- Usuário do banco: `______________`
- Senha do banco: `______________`
- Host: normalmente é `localhost` (não precisa anotar, já vem assim)

---

### Passo 2 — Criar as tabelas (copiar e colar)

1. Volte em **"Bancos de dados MySQL"** e clique no botão **"phpMyAdmin"** ao lado do banco que você criou.
2. Vai abrir uma tela nova. No menu de cima, clique em **"SQL"**.
3. Abra o arquivo `schema.sql` (está nesta mesma pasta), selecione todo o conteúdo e copie.
4. Cole na caixa de texto grande do phpMyAdmin e clique em **"Executar"** (ou "Go").
5. Deve aparecer uma mensagem verde de sucesso. Pronto, as tabelas foram criadas.

---

### Passo 3 — Configurar o arquivo `config.php`

1. Nesta pasta, faça uma cópia do arquivo `config.sample.php` e renomeie a cópia para `config.php`.
2. Abra `config.php` em qualquer editor de texto simples (Bloco de Notas serve) e troque **apenas** estas 5 linhas pelos dados que você anotou no Passo 1:

```php
'db_host' => 'localhost',
'db_name' => 'COLE_AQUI_O_NOME_DO_BANCO',
'db_user' => 'COLE_AQUI_O_USUARIO_DO_BANCO',
'db_pass' => 'COLE_AQUI_A_SENHA_DO_BANCO',
'api_token' => 'NXUqLwqxjJdOmgfRCMJby5mTrOPl_CfD',
```

> O `api_token` acima já vem pronto para você usar (é a "senha de entrada" do
> painel para toda a equipe). Pode usar como está, ou trocar por outra frase
> longa sem espaços — só lembre que é essa mesma frase que todos vão digitar
> para entrar no painel.

3. Salve o arquivo.

---

### Passo 4 — Enviar os arquivos para o Hostinger

1. No hPanel, vá em **"Arquivos"** → **"Gerenciador de Arquivos"**.
2. Entre na pasta `public_html`.
3. Crie uma pasta nova chamada `crm` (botão "Nova pasta").
4. Entre na pasta `crm` e clique em **"Enviar"** (upload). Envie estes 3 arquivos desta pasta local:
   - `index.html`
   - `api.php`
   - `config.php` (o que você acabou de editar no Passo 3 — **não** envie o `config.sample.php`)

---

### Passo 5 — Usar o painel

1. Abra no navegador: `https://SEUDOMINIO.com/crm/` (troque `SEUDOMINIO.com` pelo seu domínio real).
2. Entre com o **e-mail e senha** da sua conta (veja "Login individual" abaixo).
3. Pronto — cadastre um contato de teste para confirmar que está tudo salvando.

---

## Login individual por pessoa

Cada vendedor tem sua própria conta (e-mail + senha), em vez de um token único
compartilhado. Só a **primeira conta (administrador)** precisa ser criada
direto no banco — todas as outras são cadastradas depois, de dentro do
próprio painel, pelo botão **"Gerenciar equipe"** (só visível para quem é
administrador).

### Criando a primeira conta (administrador)

Como senhas não devem ficar guardadas em texto puro em lugar nenhum
(nem aqui, nem no banco), gere o hash da senha e um token antes de rodar o
SQL. Se você tiver PHP instalado em algum computador (ou peça para quem está
te ajudando), rode:

```
php -r "echo password_hash('SUA_SENHA_AQUI', PASSWORD_DEFAULT) . PHP_EOL; echo bin2hex(random_bytes(24)) . PHP_EOL;"
```

Isso imprime duas linhas: o **hash** (primeira) e um **token** aleatório
(segunda). Cole os dois no `INSERT` de exemplo no final do `schema.sql`,
junto com seu nome e e-mail, e rode no phpMyAdmin.

Depois disso, entre no painel com esse e-mail e senha — você já é
administrador e pode cadastrar o resto da equipe direto pela tela
**"Gerenciar equipe"**, sem precisar mexer em SQL de novo.

Qualquer pessoa logada pode trocar a própria senha a qualquer momento pelo
botão **"Trocar minha senha"**, no canto superior direito do painel.

O `api_token` que ainda existe em `config.php` continua funcionando como uma
"chave mestra" de administrador (útil se você perder acesso a todas as
contas), mas não aparece mais na tela de login — o dia a dia da equipe é só
e-mail e senha.

---

## O que o painel já faz, de fábrica

- **Acesso por perfil:** vendedores só veem e cadastram os próprios leads;
  administradores veem os leads e valores de toda a equipe. Isso é reforçado
  no servidor (`api.php`), não só escondido na tela.
- **Editar/remover contato**, com histórico completo de conversas por lead.
- **Exportar CSV, Excel e PDF** com um clique.
- **Alerta de follow-up atrasado**, com filtro rápido "⚠ Só atrasados".
- **Relatório de acessos** (só administrador): quem entrou, quando e de qual IP.
- **Ranking de vendas** (toda a equipe vê a posição de todos; o valor em R$
  só aparece na própria linha e para administradores).
- **Dica de abordagem por perfil DISC**: quando o contato tem um perfil DISC
  identificado, aparece automaticamente uma caixa com dicas de como
  convencer aquele tipo de perfil (D, I, S ou C) — sem precisar de IA, é um
  texto fixo baseado na metodologia DISC.
- **Ajuda para identificar o perfil DISC**: no cadastro/edição do contato,
  se o vendedor não sabe o perfil, tem uma lista de frases típicas que o
  lead pode ter dito (ex.: "quero ver quem já usou e os resultados" → perfil
  C) — clicar na frase mais parecida já marca o perfil sugerido.

---

## E-mails automáticos de follow-up (opcional)

O painel pode enviar e-mails sozinho, uma vez por dia, para cada interação
cujo campo **"Próximo follow-up"** cair na data de hoje (e o negócio ainda
não estiver fechado): um e-mail para o **lead** (se ele tiver e-mail
cadastrado) e um alerta interno para o **vendedor responsável** (se ele
tiver conta no painel). Cada follow-up só gera um e-mail — depois de
enviado, não é repetido no dia seguinte.

Isso é opcional e não vem ativado sozinho — para ligar:

1. Em `config.php`, confira/ajuste estas 3 linhas:
   ```php
   'email_from' => 'crm@seudominio.com.br',
   'email_from_name' => '{{EMPRESA}} — Painel Comercial',
   'painel_url' => 'https://seudominio.com.br/crm/',
   ```
   O `email_from` precisa ser um e-mail do **mesmo domínio** do site — isso
   reduz bastante a chance do e-mail cair em spam.
2. No hPanel, vá em **"Avançado" → "Cron Jobs"**.
3. Crie um novo cron job com periodicidade **"Uma vez por dia"**.
4. No campo de comando, cole (trocando pelo caminho real da sua conta):
   ```
   php /home/SEU_USUARIO/public_html/crm/cron_followup.php
   ```
5. Salve. A partir do dia seguinte, os e-mails passam a sair sozinhos.

> ⚠️ Este script usa a função `mail()` nativa do PHP (simples e gratuita,
> mas com mais chance de cair em spam do que um serviço de e-mail
> dedicado). Para maior entregabilidade, é possível trocar por SMTP
> autenticado (Gmail/Workspace, SendGrid, Brevo etc.).

---

## Assistente de resposta com IA (opcional)

Em cada contato, acima de "Registrar nova conversa", tem uma caixa
**"🤖 Assistente de resposta (IA)"**: o vendedor cola ali o que o lead
respondeu e clica em "Gerar sugestão de resposta" — a IA (Claude, da
Anthropic) lê o histórico da negociação e sugere um texto persuasivo para
convencer o lead a avançar. O vendedor sempre revisa e edita antes de
enviar; a IA nunca manda nada sozinha.

### Ativando

1. Crie uma conta em **console.anthropic.com**, adicione uma forma de
   pagamento (é pré-pago, cobrado por uso — cada sugestão custa frações de
   centavo) e gere uma **API key** em "API Keys".
2. Em `config.php`, adicione (ou preencha, se já existir vazia):
   ```php
   'anthropic_api_key' => 'sk-ant-SUA_CHAVE_AQUI',
   'ai_model' => 'claude-haiku-4-5-20251001',
   ```
3. Salve e teste: abra um contato, cole uma resposta de exemplo do
   comprador na caixa da IA e clique em gerar.

Se `anthropic_api_key` ficar em branco, o botão aparece normalmente mas
mostra um erro claro ao clicar — o resto do painel continua funcionando
sem problema.

Depois de gerar a sugestão, o botão **"📱 Abrir no WhatsApp"** abre o
WhatsApp (Web ou app) já com o número do contato e a mensagem sugerida
preenchida.

---

## Prospectar Clientes (Google Maps + Casa dos Dados + IA)

A aba **"🧭 Prospectar Clientes"**, no topo do painel, ajuda o vendedor a
encontrar empresas em potencial numa cidade. Tem duas fontes de busca,
que podem ser usadas juntas:

- **🔍 Buscar no Google Maps**: informa **cidade** e **ramo de atuação**
  (ex.: "clínica odontológica", "imobiliária") e a IA busca no Google
  Maps — traz endereço, telefone, site e nota.
- **📇 Buscar por CNPJ** (Casa dos Dados): informa **cidade**, **UF** e
  **ramo** e busca direto na base de CNPJs ativos — traz razão social,
  situação cadastral, porte, data de abertura, capital social e sócios
  (não traz e-mail/telefone).

Cada empresa vira um **prospect** numa lista separada — não entra direto
como lead, para o vendedor revisar antes.

> ⚠️ O Google Maps não usa código CNAE — a busca funciona por
> palavra-chave/ramo de negócio. A busca por CNPJ pesquisa o texto
> informado dentro da razão social/nome fantasia das empresas.

Para cada prospect dá para: ver endereço/telefone/site/nota, **"🔎 Buscar
dados completos (CNPJ)"** (completa com dados da Casa dos Dados um
prospect achado pelo Google Maps), **gerar mensagem de abordagem com IA**
(e-mail ou WhatsApp), **abrir no WhatsApp/e-mail** já com a mensagem
pronta, **registrar a resposta** recebida e, quando valer a pena,
**"✅ Adicionar como lead"** para virar contato de verdade no CRM.

### Ativando o Google Maps

1. No Google Cloud Console, ative a **"Places API"** e configure
   faturamento (tem cota gratuita mensal).
2. Crie uma **API Key** em "APIs e Serviços" → "Credenciais".
3. Em `config.php`: `'google_maps_code' => 'SUA_CHAVE_AQUI',`

### Ativando a Casa dos Dados

1. Crie uma conta em **portal.casadosdados.com.br** e contrate um plano
   de API.
2. Gere o token em **"Chave da API"** no painel da conta.
3. Em `config.php`: `'casa_dos_dados_token' => 'SEU_TOKEN_AQUI',`

### Migração do banco

Rode no phpMyAdmin a migração que cria a tabela `prospects` (veja
`schema.sql` do projeto — a tabela já vem criada completa em instalações
novas; para instalações que já tinham só a busca por Google Maps, use o
`ALTER TABLE` comentado logo depois em `schema.sql`).

Suba o `index.html` e o `api.php` atualizados.

Deixar `google_maps_code` ou `casa_dos_dados_token` em branco desativa só
aquela fonte de busca (mostra um erro claro ao clicar) — o resto do
painel continua funcionando. A geração de mensagem reaproveita a mesma
`anthropic_api_key` do assistente de resposta.

---

## Se algo der errado

| Mensagem/sintoma | O que fazer |
|---|---|
| "E-mail ou senha inválidos" | Confira se digitou certinho; se esqueceu a senha, peça para um administrador redefinir pelo "Gerenciar equipe", ou recrie a conta. |
| "Sessão inválida. Faça login novamente." | Sua sessão expirou ou foi encerrada — é só entrar de novo com e-mail e senha. |
| "Não foi possível conectar à API" | Confirme se `api.php` e `config.php` estão na mesma pasta que `index.html` no servidor. |
| "Falha ao conectar ao banco de dados" | Revise `db_name`, `db_user` e `db_pass` em `config.php` — algum deles está errado. |
| Página em branco | Confirme se o site tem SSL ativo (hPanel → SSL → ativar, é gratuito) e acesse com `https://`. |

## Perguntas que você não precisa resolver agora

- **Backup:** dentro do painel tem um botão **"Exportar CSV"** — use-o de vez
  em quando para guardar uma cópia dos dados fora do banco.
