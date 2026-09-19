# Painel Comercial EdusIA — versão Hostinger (guia simples)

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
3. Em **"Criar novo banco de dados MySQL"**, digite um nome, por exemplo `edusia_crm`, e clique em criar.
   - O Hostinger vai colocar um prefixo automático, tipo `u123456789_edusia_crm`. Tudo bem, é normal.
4. Logo abaixo, crie um **usuário** para esse banco (ex.: `edusia_user`) e uma **senha**.
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

## Novidades desta versão

Se seu painel já estava funcionando (banco `contacts`/`interactions`/`users`/
`sellers` já criados), você só precisa rodar a tabela nova abaixo no SQL do
phpMyAdmin e reenviar `index.html` e `api.php` (o `config.php` não muda).

```sql
CREATE TABLE IF NOT EXISTS access_log (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NULL,
  nome VARCHAR(120) NOT NULL,
  email VARCHAR(190) NOT NULL,
  ip VARCHAR(64) DEFAULT '',
  ocorrido_em DATETIME NOT NULL,
  INDEX idx_access_log_data (ocorrido_em)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

O que mudou no painel:

- **Valores por vendedor:** cada vendedor só vê o **valor em R$** das próprias
  propostas — as dos colegas aparecem como "🔒 valor restrito". Só quem é
  **administrador** vê o valor de todo mundo e o KPI "Pipeline em aberto
  (total geral)"; para os demais, o mesmo KPI mostra só "Meu pipeline em
  aberto". Isso é reforçado no servidor (`api.php`), não só escondido na
  tela — nem inspecionando o site dá para ver o valor de outro vendedor.
- **Editar contato:** botão **"Editar contato"** no painel de detalhes, ao
  lado de "Remover contato".
- **Exportar Excel e PDF:** dois novos botões na barra de ferramentas, além
  do CSV que já existia.
- **Alerta de atraso mais visível:** aparece uma faixa vermelha no topo
  quando há follow-ups vencidos — clique nela (ou no botão **"⚠ Só
  atrasados"**) para filtrar só esses contatos.
- **Relatório de acessos** (só administrador): botão no topo mostra quem
  entrou no painel, quando e de qual IP.
- **Ranking de vendas** (todo mundo vê): botão **"🏆 Ranking"** mostra a
  posição de cada vendedor pelo número de negócios fechados-ganho — sem
  mostrar valores para quem não é administrador.
- **Dica de abordagem por perfil DISC**: quando o contato tem um perfil DISC
  identificado, aparece automaticamente uma caixa com dicas de como
  convencer aquele tipo de perfil (D, I, S ou C) — sem precisar de IA, é um
  texto fixo baseado na metodologia DISC.
- **Ajuda para identificar o perfil DISC**: no cadastro/edição do contato,
  se o vendedor não sabe o perfil, tem uma lista de frases típicas que o
  lead pode ter dito (ex.: "quero ver quem já usou e os resultados" → perfil
  C) — clicar na frase mais parecida já marca o perfil sugerido.

---

## E-mails automáticos de follow-up

O painel pode enviar e-mails sozinho, uma vez por dia, para cada interação
cujo campo **"Próximo follow-up"** cair na data de hoje (e o negócio ainda
não estiver fechado): um e-mail para o **lead** (se ele tiver e-mail
cadastrado) e um alerta interno para o **vendedor responsável** (se ele
tiver conta no painel). Cada follow-up só gera um e-mail — depois de
enviado, não é repetido no dia seguinte.

### Se seu banco já existia antes desta função

Rode este SQL uma vez no phpMyAdmin (adiciona a coluna que controla se o
e-mail já foi enviado):

```sql
ALTER TABLE interactions
  ADD COLUMN followup_email_sent_at DATETIME NULL,
  ADD INDEX idx_interactions_followup (data_followup);
```

### Ativando o envio automático (Cron Job)

1. Em `config.php`, confira/ajuste estas 3 linhas (adicione se ainda não
   existirem):
   ```php
   'email_from' => 'crm@seudominio.com.br',
   'email_from_name' => 'Sua Empresa — Painel Comercial',
   'painel_url' => 'https://seudominio.com.br/crm/',
   ```
   O `email_from` precisa ser um e-mail do **mesmo domínio** do site (ex.:
   `crm@edusia.com.br` se o painel está em `edusia.com.br`) — isso reduz
   bastante a chance do e-mail cair em spam.
2. No hPanel, vá em **"Avançado" → "Cron Jobs"**.
3. Crie um novo cron job com periodicidade **"Uma vez por dia"** (escolha um
   horário, ex.: 8h da manhã).
4. No campo de comando, cole (trocando pelo caminho real da sua conta —
   você vê o caminho completo no topo do Gerenciador de Arquivos):
   ```
   php /home/SEU_USUARIO/public_html/crm/cron_followup.php
   ```
5. Salve. Pronto — a partir de amanhã, os e-mails passam a sair sozinhos.

> ⚠️ **Sobre entregabilidade:** este script usa a função `mail()` nativa do
> PHP, que é simples e gratuita, mas tem mais chance de cair na caixa de
> spam do que um serviço de e-mail dedicado (Gmail/Workspace, SendGrid,
> Brevo etc.). Se notar muitos e-mails não entregues, é possível trocar o
> `cron_followup.php` para enviar via SMTP autenticado — avise que ajusto.

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
   'frase_padrao' => 'Uma frase institucional sua, opcional, que a IA pode encaixar quando fizer sentido.',
   ```
3. Salve e teste: abra um contato, cole uma resposta de exemplo do
   comprador na caixa da IA e clique em gerar.

Se `anthropic_api_key` ficar em branco, o botão aparece normalmente mas
mostra um erro claro ao clicar — o resto do painel continua funcionando
sem problema.

> ⚠️ O texto do comprador e o histórico da negociação são enviados à API
> da Anthropic para gerar a sugestão. Não é armazenado por ela além do
> necessário para processar a resposta, mas é bom estar ciente disso ao
> lidar com dados sensíveis de clientes.

Depois de gerar a sugestão, o botão **"📱 Abrir no WhatsApp"** abre o
WhatsApp (Web ou app, dependendo do dispositivo) já com o número do
contato e a mensagem sugerida preenchida — só falta o vendedor conferir e
apertar enviar. Funciona tanto no celular quanto no computador. Se o
contato não tiver telefone cadastrado, o botão avisa em vez de abrir uma
conversa em branco.

---

## Prospectar Clientes (busca no Google Maps + IA)

A aba **"🧭 Prospectar Clientes"**, no topo do painel, ajuda o vendedor a
encontrar empresas em potencial numa cidade, sem precisar já ter o
contato: informa a **cidade** e o **ramo de atuação** (ex.: "clínica
odontológica", "escritório de contabilidade", "imobiliária") e a IA busca
no Google Maps. Cada empresa encontrada vira um **prospect** numa lista
separada — não entra direto como lead no CRM, para o vendedor revisar
antes.

> ⚠️ O Google Maps não usa código CNAE (isso é uma classificação da
> Receita Federal) — a busca funciona por palavra-chave/ramo de negócio,
> que na prática cobre o mesmo objetivo.

### O que dá para fazer com cada prospect

- Ver endereço, telefone, site e nota do Google.
- **Gerar mensagem de abordagem com IA**, separada para **e-mail** ou
  **WhatsApp** — a IA já considera o ramo de atuação da empresa.
- **Abrir no WhatsApp/e-mail** com a mensagem pronta (mesmo mecanismo do
  assistente de resposta: o vendedor revisa e aperta enviar).
- **Registrar a resposta** recebida e mudar o status (Novo, Contatado,
  Respondeu, Descartado, Virou lead).
- **"✅ Adicionar como lead"**: quando a empresa responde e vale a pena
  seguir, transforma o prospect em contato de verdade no CRM (pede o
  nome de quem respondeu, telefone e e-mail).

### Ativando

1. Crie um projeto no **Google Cloud Console** (console.cloud.google.com),
   ative a **"Places API"** e configure uma forma de pagamento (tem cota
   gratuita mensal, e cada busca custa poucos centavos).
2. Em "APIs e Serviços" → "Credenciais", crie uma **API Key**. Por
   segurança, restrinja essa chave para funcionar só com a Places API.
3. Em `config.php`, adicione:
   ```php
   'google_maps_code' => 'SUA_CHAVE_AQUI',
   ```
4. Rode a migração SQL abaixo no phpMyAdmin (cria a tabela de prospects):
   ```sql
   CREATE TABLE IF NOT EXISTS prospects (
     id VARCHAR(40) PRIMARY KEY,
     place_id VARCHAR(120) NOT NULL UNIQUE,
     nome VARCHAR(255) NOT NULL,
     endereco VARCHAR(500) DEFAULT '',
     cidade VARCHAR(120) DEFAULT '',
     ramo VARCHAR(255) DEFAULT '',
     telefone VARCHAR(60) DEFAULT '',
     email VARCHAR(255) DEFAULT '',
     site VARCHAR(255) DEFAULT '',
     rating DECIMAL(2,1) DEFAULT NULL,
     vendedor VARCHAR(120) NOT NULL,
     status VARCHAR(30) NOT NULL DEFAULT 'novo',
     resposta TEXT,
     mensagem_sugerida TEXT,
     contact_id VARCHAR(40) NULL,
     created_at DATETIME NOT NULL,
     INDEX idx_prospects_vendedor (vendedor),
     INDEX idx_prospects_status (status)
   ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
   ```
5. Suba o `index.html` e o `api.php` atualizados. Pronto — a aba já
   aparece no topo do painel para todo mundo.

Se `google_maps_code` ficar em branco, a busca mostra um erro claro ao
clicar — o resto do painel continua funcionando sem problema. A geração
de mensagem por IA reaproveita a mesma `anthropic_api_key` já configurada
para o assistente de resposta.

---

## Se algo der errado

| Mensagem/sintoma | O que fazer |
|---|---|
| "E-mail ou senha inválidos" | Confira se digitou certinho; se esqueceu a senha, peça para o administrador (Eduardo Carvalho) redefinir pelo "Gerenciar equipe", ou recrie a conta. |
| "Sessão inválida. Faça login novamente." | Sua sessão expirou ou foi encerrada — é só entrar de novo com e-mail e senha. |
| "Não foi possível conectar à API" | Confirme se `api.php` e `config.php` estão na mesma pasta que `index.html` no servidor. |
| "Falha ao conectar ao banco de dados" | Revise `db_name`, `db_user` e `db_pass` em `config.php` — algum deles está errado. |
| Página em branco | Confirme se o site tem SSL ativo (hPanel → SSL → ativar, é gratuito) e acesse com `https://`. |

## Perguntas que você não precisa resolver agora

- **Backup:** dentro do painel tem um botão **"Exportar CSV"** — use-o de vez
  em quando para guardar uma cópia dos dados fora do banco.
